<?php

namespace Drupal\ai_provider_x\Form;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure X API access.
 */
class XConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   */
  const CONFIG_NAME = 'ai_provider_x.settings';

  /**
   * The AI Provider service.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProviderManager;

  /**
   * Constructs a new XConfigForm object.
   */
  final public function __construct(AiProviderPluginManager $ai_provider_manager) {
    $this->aiProviderManager = $ai_provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  final public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ai.provider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'x_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);

    $form['api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('X Ai API Key'),
      '#description' => $this->t('The API Key.'),
      '#default_value' => $config->get('api_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config(static::CONFIG_NAME)
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

    $this->aiProviderManager->defaultIfNone('chat', 'x', 'grok-2-latest');
    $this->aiProviderManager->defaultIfNone('chat_with_image_vision', 'x', 'grok-2-vision-1212');

    parent::submitForm($form, $form_state);
  }

}
