---
title: Config, views and translations
description: Package config, view and translation namespaces, and CSS hooks.
---

## Config

`config/filament-skeleton-loading.php`, published with the `filament-skeleton-loading-config` tag:

| Key | Env | Default |
| --- | --- | --- |
| `enabled` | `SKELETON_LOADING_ENABLED` | `true` |
| `defer_tables` | `SKELETON_LOADING_DEFER_TABLES` | `false` |
| `show_skeletons_on_table_update` | `SKELETON_LOADING_SHOW_ON_TABLE_UPDATE` | `false` |

## Views

Namespace `filament-skeleton-loading`, published with the `filament-skeleton-loading-views` tag:

| View | Purpose |
| --- | --- |
| `filament-skeleton-loading::placeholder` | The typed skeleton drawn by `HasSkeletonPlaceholder`. |
| `filament-skeleton-loading::page-loader` | The page loader markup and external script reference. |

The package also puts its own copy of `filament::components.loading-section` first in the `filament` view namespace, so Filament's lazy widgets and sections draw the generic skeleton in the panels that use the plugin. In other panels the copy renders Filament's own view. This only happens while the package is enabled.

## Translations

Namespace `filament-skeleton-loading`, key `loader.label`, published with the `filament-skeleton-loading-translations` tag. Shipped languages: `en`, `pt`, `pt_PT`, `pt_BR`. Skeletons use Filament's own "Loading" translation.

## Stylesheet and CSS hooks

The stylesheet `skeleton-loading` is registered as a Filament asset of the `mortalkiller/filament-skeleton-loading` package, loaded on request: the plugin adds it only to the panels that use it.

| Selector | Element |
| --- | --- |
| `.fsl-skeleton`, `.fsl-skeleton--{kind}` | A skeleton, by kind. |
| `.fsl-bone` | One grey shape, with the shimmer on `::after`. |
| `#fsl-page-loader`, `.is-visible` | The page loader, and its state while a page is loading. |
| `.fsl-loader-piece` | A piece of the loader symbol, animated in order. |
