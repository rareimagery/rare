<?php

namespace Drupal\rareimagery_xstore\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;

/**
 * Service for loading creator profile theme configuration.
 */
class CreatorThemeService {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $file_url_generator,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Loads theme data for a creator store node.
   *
   * @param \Drupal\node\NodeInterface $store_node
   *   The x_creator_store node.
   *
   * @return array|null
   *   Theme data array, or NULL if no published theme exists.
   */
  public function loadByCreatorStore(NodeInterface $store_node): ?array {
    $theme_nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'creator_profile_theme',
        'field_theme_creator' => $store_node->id(),
        'status' => 1,
      ]);

    if (empty($theme_nodes)) {
      return NULL;
    }

    $theme_node = reset($theme_nodes);

    // Check if theme is published.
    if ($theme_node->hasField('field_theme_published')
      && !$theme_node->get('field_theme_published')->isEmpty()
      && !$theme_node->get('field_theme_published')->value) {
      return NULL;
    }

    return $this->buildThemeData($theme_node);
  }

  /**
   * Builds the theme data array from a theme node.
   */
  protected function buildThemeData(NodeInterface $theme_node): array {
    $bg_image_url = '';
    if ($theme_node->hasField('field_bg_image') && !$theme_node->get('field_bg_image')->isEmpty()) {
      $file = $theme_node->get('field_bg_image')->entity;
      if ($file instanceof File) {
        $bg_image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }
    // Fall back to external URL if no uploaded image.
    if (empty($bg_image_url)) {
      $bg_image_url = $this->getStringValue($theme_node, 'field_bg_image_url');
    }

    // Social links (multi-value link field).
    $social_links = [];
    if ($theme_node->hasField('field_social_links') && !$theme_node->get('field_social_links')->isEmpty()) {
      foreach ($theme_node->get('field_social_links') as $item) {
        $social_links[] = [
          'title' => $item->title ?? '',
          'url' => $item->uri ?? '',
        ];
      }
    }

    // Layout blocks (JSON in text_long field).
    $layout_blocks = [];
    $layout_raw = $this->getStringValue($theme_node, 'field_layout_blocks');
    if ($layout_raw) {
      $decoded = json_decode($layout_raw, TRUE);
      if (is_array($decoded)) {
        $layout_blocks = $decoded;
      }
    }

    return [
      'uuid' => $theme_node->uuid(),
      'themeNodeId' => (int) $theme_node->id(),
      // Background.
      'bgColor' => $this->getStringValue($theme_node, 'field_bg_color'),
      'bgImageUrl' => $bg_image_url ?: NULL,
      'bgRepeat' => $this->getStringValue($theme_node, 'field_bg_repeat'),
      'bgOverlayColor' => $this->getStringValue($theme_node, 'field_bg_overlay_color'),
      'bgOverlayOpacity' => $this->getFloatValue($theme_node, 'field_bg_overlay_opacity'),
      'bgAnimation' => $this->getStringValue($theme_node, 'field_bg_animation'),
      // Music.
      'musicUrl' => $this->getStringValue($theme_node, 'field_music_url'),
      'musicAutoplay' => (bool) ($theme_node->hasField('field_music_autoplay') ? $theme_node->get('field_music_autoplay')->value : FALSE),
      'musicLoop' => (bool) ($theme_node->hasField('field_music_loop') ? $theme_node->get('field_music_loop')->value : TRUE),
      'musicVolume' => $this->getIntValue($theme_node, 'field_music_volume', 80),
      'musicTrackTitle' => $this->getStringValue($theme_node, 'field_music_track_title'),
      'musicArtist' => $this->getStringValue($theme_node, 'field_music_artist'),
      'musicPlayerPosition' => $this->getStringValue($theme_node, 'field_music_player_position') ?? 'floating',
      // Typography.
      'fontHeading' => $this->getStringValue($theme_node, 'field_font_heading'),
      'fontBody' => $this->getStringValue($theme_node, 'field_font_body'),
      'fontAccent' => $this->getStringValue($theme_node, 'field_font_accent'),
      'fontSizeScale' => $this->getFloatValue($theme_node, 'field_font_size_scale') ?? 1.0,
      // Colors.
      'colorPrimary' => $this->getStringValue($theme_node, 'field_color_primary'),
      'colorSecondary' => $this->getStringValue($theme_node, 'field_color_secondary'),
      'colorText' => $this->getStringValue($theme_node, 'field_color_text'),
      'colorLink' => $this->getStringValue($theme_node, 'field_color_link'),
      'colorLinkHover' => $this->getStringValue($theme_node, 'field_color_link_hover'),
      'colorAccent' => $this->getStringValue($theme_node, 'field_color_accent'),
      'colorScrollbarThumb' => $this->getStringValue($theme_node, 'field_color_scrollbar_thumb'),
      'colorScrollbarTrack' => $this->getStringValue($theme_node, 'field_color_scrollbar_track'),
      // Layout.
      'layoutBlocks' => $layout_blocks,
      'aboutMeHtml' => $this->getStringValue($theme_node, 'field_about_me_html'),
      // Social.
      'socialInstagram' => $this->getStringValue($theme_node, 'field_social_instagram'),
      'socialYoutube' => $this->getStringValue($theme_node, 'field_social_youtube'),
      'socialTiktok' => $this->getStringValue($theme_node, 'field_social_tiktok'),
      'socialFacebook' => $this->getStringValue($theme_node, 'field_social_facebook'),
      'socialDiscord' => $this->getStringValue($theme_node, 'field_social_discord'),
      'socialWebsite' => $this->getStringValue($theme_node, 'field_social_website'),
      'socialLinks' => $social_links,
      // Meta.
      'themeName' => $this->getStringValue($theme_node, 'field_theme_name'),
      'themePublished' => (bool) ($theme_node->hasField('field_theme_published') ? $theme_node->get('field_theme_published')->value : FALSE),
    ];
  }

  /**
   * Safely gets a string field value.
   */
  protected function getStringValue(NodeInterface $node, string $field_name): ?string {
    if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
      return NULL;
    }
    return $node->get($field_name)->value;
  }

  /**
   * Safely gets a float value from a string field.
   */
  protected function getFloatValue(NodeInterface $node, string $field_name): ?float {
    $value = $this->getStringValue($node, $field_name);
    return $value !== NULL ? (float) $value : NULL;
  }

  /**
   * Safely gets an integer field value.
   */
  protected function getIntValue(NodeInterface $node, string $field_name, int $default = 0): int {
    if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
      return $default;
    }
    return (int) $node->get($field_name)->value;
  }

}
