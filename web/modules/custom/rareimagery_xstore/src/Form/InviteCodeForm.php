<?php

namespace Drupal\rareimagery_xstore\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin form to create and manage invite codes.
 */
class InviteCodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rareimagery_xstore_invite_code_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Generate code section.
    $form['generate'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Generate New Invite Code'),
    ];

    $form['generate']['code_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code Prefix'),
      '#default_value' => 'RARE',
      '#size' => 10,
      '#description' => $this->t('Prefix for the generated code (e.g., RARE → RARE-XXXXXX).'),
    ];

    $form['generate']['max_uses'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Uses'),
      '#default_value' => 1,
      '#min' => 1,
      '#max' => 100,
      '#description' => $this->t('How many times this code can be used.'),
    ];

    $form['generate']['note'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Note (optional)'),
      '#description' => $this->t('Who is this code for? e.g., "For John"'),
      '#maxlength' => 255,
    ];

    $form['generate']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Invite Code'),
      '#submit' => ['::generateCode'],
    ];

    // Existing codes table.
    $form['existing'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Existing Invite Codes'),
    ];

    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'invite_code')
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    $nids = $query->execute();
    $nodes = $storage->loadMultiple($nids);

    $header = [
      $this->t('Code'),
      $this->t('Note'),
      $this->t('Uses'),
      $this->t('Status'),
      $this->t('Created'),
      $this->t('Actions'),
    ];

    $rows = [];
    foreach ($nodes as $node) {
      $code = $node->get('field_invite_code')->value ?? '';
      $maxUses = $node->get('field_max_uses')->value ?? 1;
      $currentUses = $node->get('field_current_uses')->value ?? 0;
      $status = $node->isPublished() ? $this->t('Active') : $this->t('Disabled');
      $created = \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'short');

      // Get note from title (we store note in title after the code).
      $title = $node->getTitle();
      $note = str_replace($code, '', $title);
      $note = trim($note, ' -–—');

      $rows[] = [
        $code,
        $note ?: '—',
        $currentUses . ' / ' . $maxUses,
        $status,
        $created,
        $node->isPublished()
          ? $this->t('<a href=":url">Disable</a>', [':url' => '/admin/config/rareimagery/invites/disable/' . $node->id()])
          : $this->t('<a href=":url">Enable</a>', [':url' => '/admin/config/rareimagery/invites/enable/' . $node->id()]),
      ];
    }

    $form['existing']['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No invite codes yet. Generate one above.'),
    ];

    return $form;
  }

  /**
   * Generate a new invite code.
   */
  public function generateCode(array &$form, FormStateInterface $form_state) {
    $prefix = strtoupper(trim($form_state->getValue('code_prefix') ?: 'RARE'));
    $maxUses = (int) $form_state->getValue('max_uses') ?: 1;
    $note = trim($form_state->getValue('note') ?: '');

    // Generate random 6-char code.
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $random = '';
    for ($i = 0; $i < 6; $i++) {
      $random .= $chars[random_int(0, strlen($chars) - 1)];
    }
    $code = $prefix . '-' . $random;

    $title = $note ? $code . ' — ' . $note : $code;

    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $node = $storage->create([
      'type' => 'invite_code',
      'title' => $title,
      'uid' => \Drupal::currentUser()->id(),
      'status' => 1,
      'field_invite_code' => $code,
      'field_max_uses' => $maxUses,
      'field_current_uses' => 0,
    ]);
    $node->save();

    $this->messenger()->addStatus($this->t('Invite code created: <strong>@code</strong> (max @uses use(s))', [
      '@code' => $code,
      '@uses' => $maxUses,
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handled by generateCode.
  }

}
