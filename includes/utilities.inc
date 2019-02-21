<?php

/**
 * @file
 * Utility functions...
 */

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
 * @throws DgiSaxonHelperTransformationException
 *   Thrown if the transformation outputs anything to stderr.
 */
function dgi_saxon_helper_transform_string_to_string($xslt_path, $xml, array $params = array()) {
  try {
    $input = tmpfile();
    fwrite($input, $xml);
    fseek($input, 0);
    $output = tmpfile();

    dgi_saxon_helper_transform($input, $output, $xslt_path, $params);
    fseek($output, 0);
    return stream_get_contents($output);
  }
  finally {
    fclose($input);
    fclose($output);
  }
}

/**
 * Transform from and to resources.
 *
 * @param resource $input
 *   A stream/file pointer for the input to the transformation.
 * @param resource $output
 *   A stream/file resource to capture the output of the transformation.
 * @param string $xslt
 *   A path/URI to the XSLT to use.
 * @param array $params
 *   An associative array of parameters to pass to the XSLT, keys as parameter
 *   names, values as values.
 *
 * @throws DgiSaxonHelperTransformationException
 *   Thrown if the transformation outputs anything to stderr.
 */
function dgi_saxon_helper_transform($input, $output, $xslt, array $params = array()) {
  $xslt_path = escapeshellarg(drupal_realpath($xslt));
  $pipes = array();
  $equal_join_and_escape = function ($key, $value) {
    return escapeshellarg("$key=$value");
  };
  $escaped = array_map($equal_join_and_escape, array_keys($params), $params);
  $param_string = implode(' ', $escaped);
  $command = variable_get('dgi_saxon_helper_executable', DGI_SAXON_HELPER_DEFAULT_EXECUTABLE_PATH);

  try {
    $process = proc_open("$command -versionmsg:off -ext:on - $xslt_path $param_string",
      array(
        0 => $input,
        1 => $output,
        2 => array(
          'pipe',
          'w',
        ),
      ),
      $pipes
    );

    // If the stderr pipe was written to, something went wrong.
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    if (!empty($stderr)) {
      throw new DgiSaxonHelperTransformationException(filter_xss($stderr));
    }
  }
  finally {
    if (isset($process)) {
      proc_close($process);
    }
  }
}
