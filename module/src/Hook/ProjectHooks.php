<?php

declare(strict_types=1);

namespace Drupal\dq_project\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Markup;

/**
 * Project recipe — theme hooks and Schema.org JSON-LD.
 *
 * Native object-oriented hooks (Drupal 11.1.8+). This module is a separate
 * extension, so its #[Hook] preprocess methods stack with the generated theme's
 * own preprocess and with other recipe modules (e.g. dq_blog also implements
 * preprocess_views_view) — each guards by view id, no dispatcher needed.
 *
 * Markup lives in the theme; this module only prepares variables and builds the
 * hand-rolled Schema.org JSON-LD (no SEO/metatag module).
 */
final class ProjectHooks {

  /**
   * Implements hook_preprocess_HOOK() for views templates.
   *
   * Scoped to the projects view; preprocess_views_view fires for every view.
   */
  #[Hook('preprocess_views_view')]
  public function preprocessViewsView(array &$variables): void {
    $view = $variables['view'];
    if ($view->id() !== 'projects') {
      return;
    }

    // Flatten rows to title/url/thumbnail for the grid template. Thumbnails use
    // the core 'medium' image style; fall back to the original file.
    $fileUrlGenerator = \Drupal::service('file_url_generator');
    $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('medium');

    $projects = [];
    foreach ($view->result as $row) {
      $node = $row->_entity ?? NULL;
      if (!$node) {
        continue;
      }
      $image = NULL;
      if ($node->hasField('field_project_image') && !$node->get('field_project_image')->isEmpty()) {
        if ($file = $node->get('field_project_image')->entity) {
          $uri = $file->getFileUri();
          $image = $style ? $style->buildUrl($uri) : $fileUrlGenerator->generateString($uri);
        }
      }
      $projects[] = [
        'title' => $node->label(),
        'url' => $node->toUrl()->toString(),
        'image' => $image,
      ];
    }

    $variables['projects'] = $projects;
    $variables['structured_data'] = $this->projectListJsonld($view->result);
  }

  /**
   * Builds a Schema.org ItemList JSON-LD element for the projects grid.
   *
   * Each entry is a ListItem whose item is a CreativeWork (name, absolute URL,
   * image). Hand-built — no SEO/metatag module.
   *
   * @param \Drupal\views\ResultRow[] $results
   *   The view's result rows.
   *
   * @return array|null
   *   A renderable html_tag element, or NULL when there are no rows.
   */
  private function projectListJsonld(array $results): ?array {
    $fileUrlGenerator = \Drupal::service('file_url_generator');
    $items = [];
    $position = 1;
    foreach ($results as $row) {
      $node = $row->_entity ?? NULL;
      if (!$node) {
        continue;
      }
      $item = [
        '@type' => 'CreativeWork',
        'name' => $node->label(),
        'url' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
      ];
      if ($node->hasField('field_project_image') && !$node->get('field_project_image')->isEmpty()) {
        if ($file = $node->get('field_project_image')->entity) {
          $item['image'] = $fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        }
      }
      $items[] = ['@type' => 'ListItem', 'position' => $position++, 'item' => $item];
    }
    if (!$items) {
      return NULL;
    }

    $data = [
      '@context' => 'https://schema.org',
      '@type' => 'ItemList',
      'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
      'numberOfItems' => count($items),
      'itemListElement' => $items,
    ];
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

    return [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#attributes' => ['type' => 'application/ld+json'],
      '#value' => Markup::create($json),
    ];
  }

}
