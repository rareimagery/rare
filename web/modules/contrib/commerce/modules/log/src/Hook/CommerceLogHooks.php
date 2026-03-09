<?php

namespace Drupal\commerce_log\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for Commerce Log.
 */
class CommerceLogHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_preprocess_commerce_order().
   */
  #[Hook('preprocess_commerce_order')]
  public function preprocessCommerceOrder(&$variables): void {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $variables['elements']['#commerce_order'];
    $variables['order']['activity'] = [
      '#type' => 'view',
      '#name' => 'commerce_activity',
      '#display_id' => 'default',
      '#arguments' => [$order->id(), 'commerce_order'],
      '#embed' => TRUE,
      '#title' => $this->t('Order activity'),
    ];
  }

  /**
   * Implements hook_form_FORM_ID_alter() for 'commerce_order_add_form'.
   */
  #[Hook('form_commerce_order_add_form_alter')]
  public function commerceOrderAddFormAlter(array &$form, FormStateInterface $form_state): void {
    $form['#submit'][] = [static::class, 'commerceOrderAddFormSubmit'];
  }

  /**
   * Submission handler for the "order add form".
   */
  public static function commerceOrderAddFormSubmit(array $form, FormStateInterface $form_state): void {
    /** @var \Drupal\commerce_log\LogStorageInterface $log_storage */
    $log_storage = \Drupal::entityTypeManager()->getStorage('commerce_log');
    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($form_state->getValue('order_id'));
    $log_storage->generate($order, 'order_created_admin')->save();
  }

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $data['views']['commerce_log_admin_comment_form'] = [
      'title' => $this->t('Admin comment form'),
      'help' => $this->t('Displays a form that allows admins with the proper permission to add a log as comment. Requires an entity ID argument.'),
      'area' => [
        'id' => 'commerce_log_admin_comment_form',
      ],
    ];

    return $data;
  }

}
