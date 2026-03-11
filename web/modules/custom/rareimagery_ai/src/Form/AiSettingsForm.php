<?php

namespace Drupal\rareimagery_ai\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for AI Admin module.
 */
class AiSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['rareimagery_ai.settings'];
  }

  public function getFormId() {
    return 'rareimagery_ai_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rareimagery_ai.settings');

    $form['default_provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Default AI Provider'),
      '#options' => [
        'claude' => 'Claude (Anthropic)',
        'xai' => 'Grok (xAI)',
      ],
      '#default_value' => $config->get('default_provider') ?: 'claude',
    ];

    $form['claude'] = [
      '#type' => 'details',
      '#title' => $this->t('Claude (Anthropic)'),
      '#open' => TRUE,
    ];

    $form['claude']['claude_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('claude.api_key'),
      '#description' => $this->t('Anthropic API key.'),
    ];

    $form['claude']['claude_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#options' => [
        'claude-opus-4-6' => 'Claude Opus 4.6',
        'claude-sonnet-4-6' => 'Claude Sonnet 4.6',
        'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5',
      ],
      '#default_value' => $config->get('claude.model') ?: 'claude-sonnet-4-6',
    ];

    $form['xai'] = [
      '#type' => 'details',
      '#title' => $this->t('Grok (xAI)'),
      '#open' => TRUE,
    ];

    $form['xai']['xai_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('xai.api_key'),
      '#description' => $this->t('xAI API key. Falls back to rareimagery_xstore xAI key if empty.'),
    ];

    $form['xai']['xai_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#options' => [
        'grok-4-1-fast-reasoning' => 'Grok 4.1 Fast Reasoning',
        'grok-3' => 'Grok 3',
        'grok-3-fast' => 'Grok 3 Fast',
        'grok-3-mini' => 'Grok 3 Mini',
        'grok-3-mini-fast' => 'Grok 3 Mini Fast',
      ],
      '#default_value' => $config->get('xai.model') ?: 'grok-3-fast',
    ];

    $form['system_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('System Prompt'),
      '#default_value' => $config->get('system_prompt'),
      '#rows' => 6,
      '#description' => $this->t('Instructions given to the AI about its role and behavior.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('rareimagery_ai.settings')
      ->set('default_provider', $form_state->getValue('default_provider'))
      ->set('claude.api_key', $form_state->getValue('claude_api_key'))
      ->set('claude.model', $form_state->getValue('claude_model'))
      ->set('xai.api_key', $form_state->getValue('xai_api_key'))
      ->set('xai.model', $form_state->getValue('xai_model'))
      ->set('system_prompt', $form_state->getValue('system_prompt'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
