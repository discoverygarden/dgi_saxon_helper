<?php

namespace Drupal\dgi_saxon_helper;

/**
 * Interface for the "Transformer" service.
 */
interface TransformerInterface {

  /**
   * Helper; transform XML from a string, and return a string.
   *
   * Similarly implemented in various files around.
   *
   * @param string $xslt_path
   *   The path to the XSLT.
   * @param string $xml
   *   The XML to transform.
   * @param array $params
   *   An associative array of parameters to pass to the XSLT, keys as parameter
   *   names, values as values.
   *
   * @return string
   *   The result of the transformation.
   *
   * @throws \Drupal\dgi_saxon_helper\TransformationException
   *   Thrown if the transformation outputs anything to stderr.
   */
  public function transformStringToString($xslt_path, $xml, array $params = []);

  /**
   * Transform from and to resources.
   *
   * @param resource $input
   *   A stream/file pointer for the input to the transformation.
   * @param resource $output
   *   A stream/file resource to capture the output of the transformation.
   * @param string $xslt
   *   A path/URI to the XSLT to use.
   * @param array $xslt_params
   *   An associative array of parameters to pass to the XSLT, keys as parameter
   *   names, values as values.
   * @param array $saxon_params
   *   An array of parameters to pass of to Saxon. Defaults to opening the
   *   source from stdin.
   *
   * @throws \Drupal\dgi_saxon_helper\TransformationException
   *   Thrown if the transformation outputs anything to stderr.
   */
  public function transform($input, $output, $xslt, array $xslt_params = [], array $saxon_params = ['s' => '-']);

}
