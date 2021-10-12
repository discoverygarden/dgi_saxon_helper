<?php

namespace Drupal\dgi_saxon_helper;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Saxon transformer service.
 */
class Transformer extends AbstractTransformer {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The dgi_saxon_helper.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructor.
   */
  public function __construct(
    FileSystemInterface $file_system,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory
  ) {
    $this->fileSystem = $file_system;
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory->get('dgi_saxon_helper.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function transform($input, $output, $xslt, array $xslt_params = [], array $saxon_params = ['s' => '-']) {
    $module_path = $this->moduleHandler->getModule('dgi_saxon_helper')->getPath();
    list($xslt_path, $additional_descriptors) = $this->ensureDereferencable($xslt);
    $pipes = [];

    $parameters = fopen('php://temp', 'r+b');
    foreach ($xslt_params as $xsl_key => $xsl_value) {
      fwrite($parameters, "$xsl_key=$xsl_value\036");
    }
    fseek($parameters, 0);
    $saxon_params_escape = function ($key, $value) {
      return escapeshellarg("-$key:$value");
    };
    $saxon_escaped = array_map($saxon_params_escape, array_keys($saxon_params), $saxon_params);
    $saxon_param_string = implode(' ', $saxon_escaped);
    $saxon_executable = $this->config->get('saxon_executable');
    assert(is_executable($saxon_executable), 'Saxon is executable.');
    $saxon_command = escapeshellarg($saxon_executable);
    $bash_executable = $this->config->get('bash_executable');
    assert(is_executable($bash_executable), 'Bash is executable.');
    $bash_command = escapeshellarg($bash_executable);
    try {
      $process = proc_open(
        implode(' ', [
          $bash_command,
          "$module_path/shell_scripts/saxonb-xslt.sh",
          $saxon_command,
          $saxon_param_string,
          escapeshellarg($xslt_path),
        ]),
        [
          0 => $input,
          1 => $output,
          2 => [
            'pipe',
            'w',
          ],
          3 => $parameters,
        ] + $additional_descriptors,
        $pipes,
        NULL,
        ['LANG' => 'en_US.UTF-8']
      );

      // If the stderr pipe was written to, something went wrong.
      $stderr = stream_get_contents($pipes[2]);
      fclose($pipes[2]);
      if (!empty($stderr)) {
        throw new TransformationException(Xss::filter($stderr));
      }
    }
    finally {
      if (isset($process)) {
        proc_close($process);
      }
    }
  }

  /**
   * Static cache of XSLT files, URIs mapping to file resources.
   *
   * @var resource[]
   */
  protected static $files = [];

  /**
   * Helper; ensure the given XSLT should be dereferencable by Saxon.
   *
   * @param string $xslt
   *   The XSLT to manipulate.
   *
   * @return array
   *   An array containing:
   *   - a URI/path which Saxon should be able to dereference to fetch the
   *     underlying resource; and,
   *   - an array which can contain additional descriptors to pass to the XSLT
   *     engine via the proc_open invocation.
   */
  protected function ensureDereferencable($xslt) {
    // XXX: Seems... bad to be referencing a static method from a service class?
    // ... kinda... counter-intuitive? It's how it's recommended by Drupal,
    // though...
    // @see https://www.drupal.org/project/drupal/issues/3034072
    $scheme = StreamWrapperManager::getScheme($xslt);
    if (in_array($scheme, ['http', 'https'])) {
      // XXX: http could probably continue to be handled by Saxon's URI resolver
      // ... but at the same time, seems safer to handle here.
      if (!isset(static::$files[$xslt])) {
        // XXX: Control the number of items in our file cache/stash business...
        // We only expect one for now, but... paranoia?
        if (count(static::$files) >= 10) {
          list($to_cull, $to_keep) = array_chunk(static::$files, 5);
          array_map('fclose', $to_cull);
          static::$files = $to_keep;
        }

        static::$files[$xslt] = fopen('php://temp', 'r+b');
        $original = fopen($xslt, 'rb');
        stream_copy_to_stream($original, static::$files[$xslt]);
      }

      fseek(static::$files[$xslt], 0);

      // XXX: We reference the magic "/dev/fd/#"... which causes the process to
      // find whatever has been passed for the given descriptor. Here we use "4"
      // ... somewhat arbitrary, could be anything bigger, essentially. (0, 1
      // and 2 are the standard in/out/err that ever process gets (not
      // necessarily in that order), we presently use 3 above to pass the
      // parameters to our Saxon boot-strapping script, so 4's the next one
      // available.
      return ['/dev/fd/4', [4 => static::$files[$xslt]]];
    }

    return [$this->fileSystem->realpath($xslt), []];
  }

}
