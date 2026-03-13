<?php

namespace Drupal\rareimagery_xstore\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Handles invite code enable/disable actions.
 */
class InviteCodeController extends ControllerBase {

  /**
   * Disable an invite code.
   */
  public function disable($nid) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if ($node && $node->bundle() === 'invite_code') {
      $node->setUnpublished();
      $node->save();
      $this->messenger()->addStatus($this->t('Invite code disabled.'));
    }
    return new RedirectResponse('/admin/config/rareimagery/invites');
  }

  /**
   * Enable an invite code.
   */
  public function enable($nid) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if ($node && $node->bundle() === 'invite_code') {
      $node->setPublished();
      $node->save();
      $this->messenger()->addStatus($this->t('Invite code enabled.'));
    }
    return new RedirectResponse('/admin/config/rareimagery/invites');
  }

}
