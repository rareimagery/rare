<?php

namespace Drupal\rareimagery_xstore\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for xAI (Grok) API settings.
 */
class XaiSettingsForm extends ConfigFormBase {

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
    return 'rareimagery_xstore_xai_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rareimagery_xstore.settings');

    $form['xai'] = [
      '#type' => 'details',
      '#title' => $this->t('xAI API Settings'),
      '#open' => TRUE,
    ];

    $form['xai']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('xai.api_key'),
      '#required' => TRUE,
      '#description' => $this->t('Your xAI API key from the xAI console.'),
    ];

    $form['xai']['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Model'),
      '#options' => [
        'grok-4-1-fast-reasoning' => 'Grok 4.1 Fast Reasoning',
        'grok-3' => 'Grok 3',
        'grok-3-fast' => 'Grok 3 Fast',
        'grok-3-mini' => 'Grok 3 Mini',
        'grok-3-mini-fast' => 'Grok 3 Mini Fast',
      ],
      '#default_value' => $config->get('xai.model') ?: 'grok-4-1-fast-reasoning',
      '#description' => $this->t('The default Grok model used for content generation.'),
    ];

    $form['xai']['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#default_value' => $config->get('xai.temperature') ?? 0.7,
      '#min' => 0,
      '#max' => 2,
      '#step' => 0.1,
      '#description' => $this->t('Controls randomness. Lower = more focused, higher = more creative.'),
    ];

    $form['xai_features'] = [
      '#type' => 'details',
      '#title' => $this->t('Feature Toggles'),
      '#open' => TRUE,
    ];

    $form['xai_features']['enable_product_descriptions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('AI Product Descriptions'),
      '#default_value' => $config->get('xai.enable_product_descriptions') ?? TRUE,
      '#description' => $this->t('Allow creators to generate product descriptions with Grok.'),
    ];

    $form['xai_features']['enable_store_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('AI Store Content'),
      '#default_value' => $config->get('xai.enable_store_content') ?? TRUE,
      '#description' => $this->t('Allow AI-generated store bios and taglines from X profile data.'),
    ];

    $form['xai_features']['enable_storefront_generator'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('AI Storefront Generator'),
      '#default_value' => $config->get('xai.enable_storefront_generator') ?? FALSE,
      '#description' => $this->t('Enable the full storefront code generator endpoint.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('rareimagery_xstore.settings')
      ->set('xai.api_key', $form_state->getValue('api_key'))
      ->set('xai.model', $form_state->getValue('model'))
      ->set('xai.temperature', (float) $form_state->getValue('temperature'))
      ->set('xai.enable_product_descriptions', (bool) $form_state->getValue('enable_product_descriptions'))
      ->set('xai.enable_store_content', (bool) $form_state->getValue('enable_store_content'))
      ->set('xai.enable_storefront_generator', (bool) $form_state->getValue('enable_storefront_generator'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
