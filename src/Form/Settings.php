<?php

namespace Drupal\dgi_saxon_helper\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin settings form.
 */
class Settings extends ConfigFormBase {

  const SETTINGS = 'dgi_saxon_helper.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dgi_saxon_helper_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['saxon_executable'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Saxon Executable'),
      '#default_value' => $config->get('saxon_executable'),
    ];
    $form['bash_executable'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bash(-compatible) Executable'),
      '#default_value' => $config->get('bash_executable'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('saxon_executable', $form_state->getValue('saxon_executable'))
      ->set('bash_executable', $form_state->getValue('bash_executable'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
