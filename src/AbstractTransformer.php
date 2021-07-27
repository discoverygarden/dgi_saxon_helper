<?php

namespace Drupal\dgi_saxon_helper;

/**
 * Abstract Saxon transformer service.
 */
abstract class AbstractTransformer implements TransformerInterface {

  /**
   * {@inheritdoc}
   */
  public function transformStringToString($xslt_path, $xml, array $params = []) {
    try {
      $input = tmpfile();
      fwrite($input, $xml);
      fseek($input, 0);
      $output = tmpfile();

      $this->transform($input, $output, $xslt_path, $params);
      fseek($output, 0);
      return stream_get_contents($output);
    }
    finally {
      fclose($input);
      fclose($output);
    }
  }

}
