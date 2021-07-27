<?php

namespace Drupal\dgi_saxon_helper;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;

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
    $xslt_path = escapeshellarg($this->fileSystem->realpath($xslt));
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
          $xslt_path,
        ]),
        [
          0 => $input,
          1 => $output,
          2 => [
            'pipe',
            'w',
          ],
          3 => $parameters,
        ],
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

}
