<?php
// Auto-create Store node when User is created via API
public function onUserInsert(UserInsertEvent $event) {
  $user = $event->getAccount();
  if ($user->get('field_x_username')->value) {
    $node = Node::create([
      'type' => 'store',
      'title' => $user->get('field_x_username')->value . "'s Store",
      'field_x_username' => $user->get('field_x_username')->value,
      'field_pfp' => ['target_id' => $pfp_fid], // from JSON:API payload
      'field_background' => ['target_id' => $bg_fid],
      'field_top_posts' => ['value' => $user->get('field_top_posts')->value],
      'field_best_followers' => ['value' => $user->get('field_best_followers')->value],
      'field_status' => 'pending',
    ]);
    $node->save();
  }
}