<?php

namespace Drupal\rareimagery_xstore\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Printful API settings.
 */
class PrintfulSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['rareimagery_xstore.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rareimagery_xstore_printful_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rareimagery_xstore.settings');

    $form['printful'] = [
      '#type' => 'details',
      '#title' => $this->t('Printful API Settings'),
      '#open' => TRUE,
    ];

    $form['printful']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('printful.api_key'),
      '#required' => TRUE,
      '#description' => $this->t('Your Printful API key from the Printful dashboard.'),
    ];

    $form['printful']['webhook_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook Secret'),
      '#default_value' => $config->get('printful.webhook_secret'),
      '#description' => $this->t('The webhook verification secret for validating Printful callbacks.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('rareimagery_xstore.settings')
      ->set('printful.api_key', $form_state->getValue('api_key'))
      ->set('printful.webhook_secret', $form_state->getValue('webhook_secret'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
