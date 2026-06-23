<?php

/**
 * @file
 * Project recipe — theme preprocess callbacks and Schema.org JSON-LD.
 *
 * Injected into the custom theme by dq:scaffold when the project recipe is
 * applied. Registers under the base hook 'views_view'; the starterkit's
 * dispatcher fans base-hook registrations out to Views' per-view suggestion
 * hooks. dq:scaffold replaces STARTERKIT with the theme machine name.
 */

use Drupal\Core\Render\Markup;

STARTERKIT_add_preprocessor('views_view', function (array &$variables): void {
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
  $variables['structured_data'] = _STARTERKIT_project_list_jsonld($view->result);
});

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
function _STARTERKIT_project_list_jsonld(array $results): ?array {
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
