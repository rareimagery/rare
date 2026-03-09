<?php

namespace Drupal\rareimagery_x_import\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\rareimagery_x_import\Service\GrokService;
use Drupal\rareimagery_x_import\Service\XApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for importing an X profile with Grok AI.
 */
class XProfileImportForm extends FormBase {

  protected XApiService $xApi;
  protected GrokService $grok;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected FileSystemInterface $fileSystem;

  public function __construct(XApiService $x_api, GrokService $grok, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system) {
    $this->xApi = $x_api;
    $this->grok = $grok;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rareimagery_x_import.x_api'),
      $container->get('rareimagery_x_import.grok'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
    );
  }

  public function getFormId() {
    return 'rareimagery_x_profile_import';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['x_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('X Username'),
      '#description' => $this->t('Enter the X/Twitter username (without @). Example: elonmusk'),
      '#required' => TRUE,
      '#maxlength' => 64,
    ];

    // Load available commerce stores for linking.
    $stores = $this->entityTypeManager->getStorage('commerce_store')->loadMultiple();
    $store_options = ['' => $this->t('- Create new store -')];
    foreach ($stores as $store) {
      $store_options[$store->id()] = $store->getName();
    }

    $form['linked_store'] = [
      '#type' => 'select',
      '#title' => $this->t('Link to Commerce Store'),
      '#options' => $store_options,
      '#description' => $this->t('Select an existing store or leave blank to create a new one.'),
    ];

    $form['use_grok'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Analyze with Grok AI'),
      '#description' => $this->t('Use Grok to analyze engagement, recommend products, and rank top posts.'),
      '#default_value' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import X Profile'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $username = trim($form_state->getValue('x_username'), '@ ');
    $store_id = $form_state->getValue('linked_store');
    $use_grok = $form_state->getValue('use_grok');

    // Step 1: Fetch profile from X API.
    $this->messenger()->addStatus($this->t('Fetching X profile for @%username...', ['%username' => $username]));
    $profile = $this->xApi->getUserProfile($username);

    if (empty($profile)) {
      $this->messenger()->addError($this->t('Could not fetch X profile for @%username. Check the username and API credentials.', ['%username' => $username]));
      return;
    }

    $user_id = $profile['id'];
    $follower_count = $profile['public_metrics']['followers_count'] ?? 0;
    $bio = $profile['description'] ?? '';
    $pfp_url = $profile['profile_image_url'] ?? '';
    // Get higher resolution PFP.
    $pfp_url = str_replace('_normal', '_400x400', $pfp_url);

    // Step 2: Fetch tweets and followers.
    $tweets = $this->xApi->getUserTweets($user_id, 20);
    $followers = $this->xApi->getUserFollowers($user_id, 8);

    // Step 3: Grok analysis (optional).
    $grok_analysis = NULL;
    if ($use_grok && !empty($tweets)) {
      $this->messenger()->addStatus($this->t('Analyzing profile with Grok AI...'));
      $grok_analysis = $this->grok->analyzeProfile($profile, $tweets);
    }

    // Step 4: Download profile image.
    $pfp_file = NULL;
    if (!empty($pfp_url)) {
      $pfp_file = $this->downloadImage($pfp_url, 'creator-pfps', $username . '-pfp');
    }

    // Step 5: Determine top posts.
    $top_posts = [];
    if (!empty($grok_analysis['top_posts'])) {
      foreach (array_slice($grok_analysis['top_posts'], 0, 8) as $post) {
        $top_posts[] = json_encode($post);
      }
    }
    elseif (!empty($tweets)) {
      // Fallback: sort by engagement.
      usort($tweets, function ($a, $b) {
        $score_a = ($a['public_metrics']['like_count'] ?? 0) + ($a['public_metrics']['retweet_count'] ?? 0) * 2;
        $score_b = ($b['public_metrics']['like_count'] ?? 0) + ($b['public_metrics']['retweet_count'] ?? 0) * 2;
        return $score_b - $score_a;
      });
      foreach (array_slice($tweets, 0, 8) as $tweet) {
        $top_posts[] = json_encode([
          'text' => $tweet['text'],
          'likes' => $tweet['public_metrics']['like_count'] ?? 0,
          'retweets' => $tweet['public_metrics']['retweet_count'] ?? 0,
        ]);
      }
    }

    // Step 6: Format followers.
    $top_followers = [];
    foreach (array_slice($followers, 0, 8) as $follower) {
      $top_followers[] = json_encode([
        'username' => $follower['username'],
        'name' => $follower['name'],
        'avatar' => $follower['profile_image_url'] ?? '',
        'followers' => $follower['public_metrics']['followers_count'] ?? 0,
      ]);
    }

    // Step 7: Build metrics.
    $metrics = '';
    if (!empty($grok_analysis)) {
      $metrics = json_encode([
        'engagement_score' => $grok_analysis['engagement_score'] ?? NULL,
        'audience_quality' => $grok_analysis['audience_quality'] ?? NULL,
        'content_themes' => $grok_analysis['content_themes'] ?? [],
        'recommended_products' => $grok_analysis['recommended_products'] ?? [],
        'summary' => $grok_analysis['summary'] ?? '',
      ]);
    }

    // Step 8: Create the Creator X Profile node.
    $node_data = [
      'type' => 'creator_x_profile',
      'title' => ($profile['name'] ?? $username) . ' - X Profile',
      'status' => 1,
      'field_x_username' => $username,
      'field_bio_description' => [
        'value' => $bio,
        'format' => 'basic_html',
      ],
      'field_follower_count' => $follower_count,
    ];

    if ($pfp_file) {
      $node_data['field_profile_picture'] = [
        'target_id' => $pfp_file->id(),
        'alt' => $username . ' profile picture',
      ];
    }

    if (!empty($top_posts)) {
      $node_data['field_top_posts'] = array_map(function ($post) {
        return ['value' => $post];
      }, $top_posts);
    }

    if (!empty($top_followers)) {
      $node_data['field_top_followers'] = array_map(function ($f) {
        return ['value' => $f];
      }, $top_followers);
    }

    if (!empty($metrics)) {
      $node_data['field_metrics'] = ['value' => $metrics];
    }

    // Link to store if selected.
    if (!empty($store_id)) {
      $node_data['field_linked_store'] = ['target_id' => $store_id];
    }

    $node = $this->entityTypeManager->getStorage('node')->create($node_data);
    $node->save();

    // Step 9: Create bidirectional link on the store.
    if (!empty($store_id)) {
      $store = $this->entityTypeManager->getStorage('commerce_store')->load($store_id);
      if ($store && $store->hasField('field_linked_x_profile')) {
        $store->set('field_linked_x_profile', ['target_id' => $node->id()]);
        $store->save();
      }
    }

    $this->messenger()->addStatus($this->t('Creator X Profile created for @%username (Node ID: @nid)', [
      '%username' => $username,
      '@nid' => $node->id(),
    ]));

    if (!empty($grok_analysis['summary'])) {
      $this->messenger()->addStatus($this->t('Grok summary: %summary', ['%summary' => $grok_analysis['summary']]));
    }

    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }

  /**
   * Download an image from URL and save as a Drupal file entity.
   */
  protected function downloadImage(string $url, string $directory, string $filename): ?\Drupal\file\FileInterface {
    try {
      $response = \Drupal::httpClient()->request('GET', $url, ['timeout' => 15]);
      $data = $response->getBody()->getContents();

      $content_type = $response->getHeaderLine('Content-Type');
      $ext = 'jpg';
      if (str_contains($content_type, 'png')) {
        $ext = 'png';
      }

      $dir = 'public://' . $directory;
      $this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
      $file_uri = $dir . '/' . $filename . '.' . $ext;

      $file = \Drupal::service('file.repository')->writeData($data, $file_uri, FileSystemInterface::EXISTS_REPLACE);
      return $file;
    }
    catch (\Exception $e) {
      $this->messenger()->addWarning($this->t('Could not download image: @error', ['@error' => $e->getMessage()]));
      return NULL;
    }
  }

}
