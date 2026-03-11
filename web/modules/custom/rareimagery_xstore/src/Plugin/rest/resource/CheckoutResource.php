<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides checkout REST resource.
 *
 * @RestResource(
 *   id = "checkout",
 *   label = @Translation("Checkout"),
 *   uri_paths = {
 *     "create" = "/api/checkout/{order_id}"
 *   }
 * )
 */
class CheckoutResource extends ResourceBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager'),
    );
  }

  public function post(int $order_id, Request $request): ModifiedResourceResponse {
    $content = json_decode($request->getContent(), TRUE);

    $order = $this->entityTypeManager->getStorage('commerce_order')->load($order_id);
    if (!$order) {
      throw new NotFoundHttpException('Order not found.');
    }

    if ($order->getState()->getId() !== 'draft') {
      throw new BadRequestHttpException('Order is not in draft state.');
    }

    // Set email.
    if (!empty($content['email'])) {
      $order->setEmail($content['email']);
    }

    // Set billing profile.
    if (!empty($content['billing_address'])) {
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      $billing_profile = $profile_storage->create([
        'type' => 'customer',
        'address' => [
          'country_code' => $content['billing_address']['country'] ?? 'US',
          'given_name' => $content['billing_address']['firstName'] ?? '',
          'family_name' => $content['billing_address']['lastName'] ?? '',
          'address_line1' => $content['billing_address']['address1'] ?? '',
          'address_line2' => $content['billing_address']['address2'] ?? '',
          'locality' => $content['billing_address']['city'] ?? '',
          'administrative_area' => $content['billing_address']['state'] ?? '',
          'postal_code' => $content['billing_address']['zip'] ?? '',
        ],
      ]);
      $billing_profile->save();
      $order->setBillingProfile($billing_profile);
    }

    $order->save();

    // Transition to placed state.
    $order->getState()->applyTransitionById('place');
    $order->save();

    return new ModifiedResourceResponse([
      'order_id' => (int) $order->id(),
      'order_number' => $order->getOrderNumber(),
      'state' => $order->getState()->getId(),
    ]);
  }

}
