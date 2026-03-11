<?php
$fields = \Drupal\field\Entity\FieldConfig::loadMultiple();
foreach ($fields as $f) {
  $bundle = $f->getTargetBundle();
  if (in_array($bundle, ['online', 'creator_x_profile', 'user'])) {
    echo $f->getTargetEntityTypeId() . '.' . $bundle . '.' . $f->getName()
      . ' (' . $f->getType() . ') - ' . $f->getLabel() . PHP_EOL;
  }
}
