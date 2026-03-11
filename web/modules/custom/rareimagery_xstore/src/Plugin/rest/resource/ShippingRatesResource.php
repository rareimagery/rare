<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rareimagery_xstore\Service\PrintfulSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides shipping rates REST resource.
 *
 * @RestResource(
 *   id = "shipping_rates",
 *   label = @Translation("Shipping Rates"),
 *   uri_paths = {
 *     "create" = "/api/shipping/rates"
 *   }
 * )
 */
class ShippingRatesResource extends ResourceBase {

  protected PrintfulSyncService $printfulSync;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    PrintfulSyncService $printful_sync,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->printfulSync = $printful_sync;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('rareimagery_xstore.printful_sync'),
    );
  }

  public function post(Request $request): ModifiedResourceResponse {
    $content = json_decode($request->getContent(), TRUE);

    $items = $content['items'] ?? [];
    $address = $content['address'] ?? [];

    $rates = $this->printfulSync->getShippingRates($items, $address);

    return new ModifiedResourceResponse(['rates' => $rates]);
  }

}
