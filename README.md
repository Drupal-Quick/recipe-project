# recipe-project

> **Work in progress** — this is a proof-of-concept recipe used to develop and test the [drupal-quick](https://github.com/Drupal-Quick/drupal-quick) scaffolding workflow. The API and config are not yet stable.

A `drupal-recipe` package that adds a Project content type and grid view to a standard Drupal install.

## What it does

- Creates a **Project** content type with three fields: body, a Link field (`field_project_link`), and a Thumbnail image (`field_project_image`)
- Creates a **Projects** view (`/projects`) with a three-column thumbnail grid, plus a block display
- Ships **theme assets** that `dq:scaffold` injects into the generated theme:
  - `templates/content/node--project.html.twig` — project card markup with Schema.org `CreativeWork` JSON-LD
  - `templates/views/views-view--projects.html.twig` — the three-column grid with `ItemList` JSON-LD
  - `includes/project.theme.inc` — preprocessors for the above (wired via the `dq_starterkit` preprocess dispatcher)

## Dependencies

Requires the `link` and `image` modules. Both are part of Drupal core and are installed automatically when the recipe is applied.

## Usage

This recipe is consumed automatically by [drupal-quick](https://github.com/Drupal-Quick/drupal-quick). Add `"project"` to the `recipes:` list in `config.dq.yml` and run `composer exec dq-install` followed by `drush dq:scaffold`. See the [drupal-quick workflow](https://github.com/Drupal-Quick/drupal-quick/blob/main/docs/workflow.md) for the full steps.

To apply it manually:

```bash
composer require drupal-quick/recipe-project
drush recipe recipes/recipe-project
```
