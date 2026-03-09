<?php

namespace Drupal\ai_provider_x;

use Drupal\ai\OperationType\Chat\StreamedChatMessage;
use Drupal\ai\OperationType\Chat\StreamedChatMessageIterator;

/**
 * X Ai Chat message iterator.
 */
class XChatMessageIterator extends StreamedChatMessageIterator {

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \Generator {
    foreach ($this->iterator->getIterator() as $data) {
      $metadata = $data->metadata ?? [];
      if (!empty($metadata) && is_array($metadata)) {
        $metadata = json_encode($metadata, TRUE);
      }
      yield new StreamedChatMessage(
        $data->choices[0]->delta->role ?? '',
        $data->choices[0]->delta->content ?? '',
        $metadata,
      );
    }
  }

}
