<?php

namespace Drupal\dgi_saxon_helper_migrate\Plugin\migrate\process;

use Drupal\dgi_saxon_helper\TransformationException;
use Drupal\dgi_saxon_helper\TransformerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Perform a transformation using Saxon.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_saxon_helper_migrate.process"
 * )
 */
class Saxon extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The transformer service.
   *
   * @var \Drupal\dgi_saxon_helper\TransformerInterface
   */
  protected $service;

  /**
   * Path to the XSLT to run.
   *
   * @var string
   */
  protected $xslt;

  /**
   * Parameters for the XSLT.
   *
   * @var array
   */
  protected $params;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TransformerInterface $service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->service = $service;
    $this->xslt = $this->configuration['path'];
    $this->params = $this->configuration['parameters'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('dgi_saxon_helper.transformer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    try {
      return $this->service->transformStringToString($this->xslt, $value, $this->params);
    }
    catch (TransformationException $e) {
      throw new MigrateSkipRowException(strtr('Failed to transform MODS: !message', [
        '!message' => $e->getMessage(),
      ]));
    }
  }

}
