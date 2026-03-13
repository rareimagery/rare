<?php

$bundles = \Drupal::service("entity_type.bundle.info")->getBundleInfo("commerce_subscription");
echo "Subscription bundles: " . implode(", ", array_keys($bundles)) . "\n";

$subs = \Drupal::entityTypeManager()->getStorage("commerce_subscription")->loadMultiple();
echo "Existing subscriptions: " . count($subs) . "\n";

// Check subscription type entities
$subTypes = \Drupal::entityTypeManager()->getStorage("commerce_subscription_type")->loadMultiple();
echo "Subscription types: " . implode(", ", array_keys($subTypes)) . "\n";
foreach ($subTypes as $id => $type) {
  echo "  $id: " . $type->label() . "\n";
}

// Check billing schedules
$schedules = \Drupal::entityTypeManager()->getStorage("commerce_billing_schedule")->loadMultiple();
echo "Billing schedules: " . implode(", ", array_keys($schedules)) . "\n";
foreach ($schedules as $id => $schedule) {
  echo "  $id: " . $schedule->label() . "\n";
}
