---
title: Configuration
description: Global settings and per-panel plugin options.
---

## Global settings

Set them in `.env`, or publish the config file:

```bash
php artisan vendor:publish --tag=filament-skeleton-loading-config
```

| Setting | Env | Default | Effect |
| --- | --- | --- | --- |
| `enabled` | `SKELETON_LOADING_ENABLED` | `true` | Off: Filament's own grey placeholder and spinner everywhere, the trait included, and no page loader. |
| `defer_tables` | `SKELETON_LOADING_DEFER_TABLES` | `false` | Every table of the panels using the plugin loads after the page, with skeleton rows. |
| `show_skeletons_on_table_update` | `SKELETON_LOADING_SHOW_ON_TABLE_UPDATE` | `false` | Every table of the panels using the plugin shows skeleton rows while it searches, sorts, filters or changes page. |

## Per panel

| Method | Default | Effect |
| --- | --- | --- |
| `shimmer(bool $condition = true)` | on | Light sweeping across the skeleton. |
| `pageLoader(bool $condition = true)` | off | Full-screen loader between pages. See [Page loader](/filament-skeleton-loading/guides/page-loader/). |
| `pageLoaderSymbol(string\|Htmlable\|Closure $symbol)` | three dots | Brand symbol of the page loader. |
| `pageLoaderDelay(int $milliseconds)` | `300` | How long a page may take before the loader shows. Negative values become `0`. |
| `pageLoaderLabel(string\|Closure $label)` | "Loading…" | Text under the symbol, also read by screen readers. |
| `deferTables(bool\|Closure $condition = true)` | the `defer_tables` setting | Calls `deferLoading()` on the panel's tables; a closure receives each table and decides. |
| `showSkeletonsOnTableUpdate(bool\|Closure $condition = true)` | the `show_skeletons_on_table_update` setting | Calls `loadingSkeleton()` on the panel's tables; a closure receives each table and decides. |
| `rounded(int\|string $size = 'md')` | the theme's radius | Corner radius of the skeletons. See [Rounded placeholders](/filament-skeleton-loading/guides/widgets-and-tables/#rounded-placeholders). |
| `selectSearchSkeletons(bool $condition = true)` | off | Skeleton lines while a searchable select waits for its options. |
| `globalSearchSkeletons(bool $condition = true)` | off | Skeleton lines under the global search field while it waits for results. |

```php
use MortalKiller\FilamentSkeletonLoading\SkeletonLoadingPlugin;

$panel->plugin(
    SkeletonLoadingPlugin::make()
        ->shimmer(false)
        ->deferTables(),
);
```

## Reduced motion and dark mode

The shimmer and the page loader always stand still for users who prefer reduced motion, whatever `shimmer()` says. Skeleton colours follow Filament's light and dark mode.

![Dashboard skeletons in dark mode](/filament-skeleton-loading/screenshots/dashboard-dark.png)

## Accessibility

Every skeleton is a `role="status"` region with `aria-busy="true"` and Filament's translated "Loading" label for screen readers. The shapes themselves are `aria-hidden`.
