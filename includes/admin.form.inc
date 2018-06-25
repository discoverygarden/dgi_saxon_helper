<?php

/**
 * @file
 * Administration form and friends.
 */

/**
 * Administration form.
 */
function dgi_saxon_helper_admin_form($form, &$form_state) {
  form_load_include($form_state, 'inc', 'dgi_saxon_helper', 'includes/admin.form');

  $form['dgi_saxon_helper_executable'] = array(
    '#type' => 'textfield',
    '#title' => t('Saxon Executable'),
    '#description' => t('The path to the Saxon executable. Defaults to "@default".', array(
      '@default' => DGI_SAXON_HELPER_DEFAULT_EXECUTABLE_PATH,
    )),
    '#default_value' => variable_get('dgi_saxon_helper_executable', DGI_SAXON_HELPER_DEFAULT_EXECUTABLE_PATH),
  );

  return system_settings_form($form);
}
