<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rareimagery_xstore\Service\StoreManagerService;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides Stripe Connect onboarding REST resource.
 *
 * @RestResource(
 *   id = "stripe_connect_onboarding",
 *   label = @Translation("Stripe Connect Onboarding"),
 *   uri_paths = {
 *     "create" = "/api/dashboard/stores/{store_nid}/stripe-onboarding"
 *   }
 * )
 */
class StripeConnectOnboardingResource extends ResourceBase {

  protected StoreManagerService $storeManager;
  protected AccountProxyInterface $currentUser;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    StoreManagerService $store_manager,
    AccountProxyInterface $current_user,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storeManager = $store_manager;
    $this->currentUser = $current_user;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('rareimagery_xstore.store_manager'),
      $container->get('current_user'),
    );
  }

  public function post(int $store_nid): ModifiedResourceResponse {
    if (!$this->storeManager->userOwnsStore($this->currentUser->id(), $store_nid)) {
      throw new AccessDeniedHttpException('You do not own this store.');
    }

    $stripe_secret = getenv('STRIPE_SECRET_KEY');
    if (empty($stripe_secret)) {
      return new ModifiedResourceResponse(['error' => 'Stripe is not configured.'], 500);
    }

    \Stripe\Stripe::setApiKey($stripe_secret);

    // Create or get existing Stripe Connect account.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $store_node = $node_storage->load($store_nid);
    $existing_account_id = $store_node->get('field_stripe_account_id')->value ?? '';

    if (empty($existing_account_id)) {
      $account = \Stripe\Account::create(['type' => 'express']);
      $store_node->set('field_stripe_account_id', $account->id);
      $store_node->save();
      $existing_account_id = $account->id;
    }

    $account_link = \Stripe\AccountLink::create([
      'account' => $existing_account_id,
      'refresh_url' => \Drupal::request()->getSchemeAndHttpHost() . '/dashboard/settings',
      'return_url' => \Drupal::request()->getSchemeAndHttpHost() . '/dashboard/settings',
      'type' => 'account_onboarding',
    ]);

    return new ModifiedResourceResponse(['url' => $account_link->url]);
  }

}
