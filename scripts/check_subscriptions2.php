<?php

// Subscription types
try {
  $subTypes = \Drupal::entityTypeManager()->getStorage("commerce_subscription_type")->loadMultiple();
  echo "Subscription types: " . implode(", ", array_keys($subTypes)) . "\n";
  foreach ($subTypes as $id => $type) {
    echo "  $id: " . $type->label() . "\n";
  }
} catch (\Exception $e) {
  echo "No subscription types: " . $e->getMessage() . "\n";
}

// Billing schedules
try {
  $schedules = \Drupal::entityTypeManager()->getStorage("commerce_billing_schedule")->loadMultiple();
  echo "Billing schedules: " . implode(", ", array_keys($schedules)) . "\n";
  foreach ($schedules as $id => $schedule) {
    echo "  $id: " . $schedule->label() . "\n";
  }
} catch (\Exception $e) {
  echo "No billing schedules: " . $e->getMessage() . "\n";
}

// Payment gateways
try {
  $gateways = \Drupal::entityTypeManager()->getStorage("commerce_payment_gateway")->loadMultiple();
  echo "Payment gateways: " . implode(", ", array_keys($gateways)) . "\n";
} catch (\Exception $e) {
  echo "No payment gateways\n";
}
