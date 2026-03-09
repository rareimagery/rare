<?php

namespace Drupal\rareimagery_x_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for X Import configuration.
 */
class XImportSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['rareimagery_x_import.settings'];
  }

  public function getFormId() {
    return 'rareimagery_x_import_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rareimagery_x_import.settings');

    $form['x_api_bearer_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('X API Bearer Token'),
      '#description' => $this->t('Leave blank to auto-generate from X_CONSUMER_KEY/X_CONSUMER_SECRET environment variables.'),
      '#default_value' => $config->get('x_api_bearer_token'),
      '#maxlength' => 512,
    ];

    $form['xai_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('xAI (Grok) API Key'),
      '#description' => $this->t('From console.x.ai. Falls back to XAI_API_KEY environment variable.'),
      '#default_value' => $config->get('xai_api_key'),
      '#maxlength' => 256,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('rareimagery_x_import.settings')
      ->set('x_api_bearer_token', $form_state->getValue('x_api_bearer_token'))
      ->set('xai_api_key', $form_state->getValue('xai_api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
