---
title: SkeletonLoadingPlugin
description: Panel-level configuration for Filament Skeleton Loading.
---

`MortalKiller\FilamentSkeletonLoading\SkeletonLoadingPlugin` registers the package on a Filament panel.

```php
use MortalKiller\FilamentSkeletonLoading\SkeletonLoadingPlugin;

$panel->plugin(SkeletonLoadingPlugin::make());
```

Register it on each panel that should use the skeletons, the page loader or table deferral. Panels without it keep Filament's own loading states: the stylesheet is not loaded there, and lazy widgets and sections draw Filament's own placeholder. The global `enabled` setting turns everything off, the plugin included.

## Constants

| Constant | Value |
| --- | --- |
| `ID` | `mortalkiller-skeleton-loading`, the plugin id on the panel. |
| `PACKAGE` | `mortalkiller/filament-skeleton-loading`, the Filament asset package name. |

## Static methods

| Method | Description |
| --- | --- |
| `make(): self` | Resolve a plugin instance from the container. |
| `current(): ?self` | The plugin registered on the current panel, or `null`. |
| `isEnabled(): bool` | The global `filament-skeleton-loading.enabled` setting. |

## Configuration methods

All of them return the plugin, so they chain.

| Method | Description |
| --- | --- |
| `rounded(int\|string $size = 'md'): self` | Sets the corner radius for skeleton bars and deferred table cells. Accepts non-negative integers as pixels, `none`, `xs`, `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`, `4xl`, `full`, and non-negative pixel lengths such as `8px` or `2.5px`. Invalid values throw `InvalidArgumentException`. |
| `shimmer(bool $condition = true): self` | Light sweeping across the skeletons (on by default). Always still for reduced motion. |
| `pageLoader(bool $condition = true): self` | Full-screen loader between pages (off by default). |
| `pageLoaderSymbol(string\|Htmlable\|Closure $symbol): self` | Brand symbol of the page loader, rendered as trusted HTML. A closure may return a string, an `Htmlable` or a view. |
| `pageLoaderDelay(int $milliseconds): self` | How long a page may take before the loader shows (300 by default, never below 0). |
| `pageLoaderLabel(string\|Closure $label): self` | Text under the symbol; the translated "Loading…" when not set. |
| `selectSearchSkeletons(bool $condition = true): self` | Skeleton lines while a searchable select waits for its options (off by default). |
| `globalSearchSkeletons(bool $condition = true): self` | Skeleton lines under the global search field while it waits for results (off by default). |
| `deferTables(bool\|Closure $condition = true): self` | Call `deferLoading()` on the panel's tables. A `Closure(Table): bool` decides per table. Not called: the `defer_tables` setting decides. |
| `showSkeletonsOnTableUpdate(bool\|Closure $condition = true): self` | Call `loadingSkeleton()` on the panel's tables, so searching, sorting, filtering and changing page show skeleton rows. A `Closure(Table): bool` decides per table. Not called: the `show_skeletons_on_table_update` setting decides. |

An explicit size takes precedence over the theme. Named sizes from `xs` to `4xl` resolve against the matching theme token. With `rounded()` omitted, the package uses `--fsl-bone-radius`, then `--radius-md`, then `6px`.

```php
SkeletonLoadingPlugin::make()->rounded(8); // 8px
SkeletonLoadingPlugin::make()->rounded('2.5px');
SkeletonLoadingPlugin::make()->selectSearchSkeletons(); // Select search only
```

## Read methods

| Method | Description |
| --- | --- |
| `getId(): string` | The plugin id (`ID`). |
| `hasShimmer(): bool` | Whether the shimmer is on. |
| `hasPageLoader(): bool` | Whether the page loader is on. |
| `renderPageLoader(): string` | The page loader markup, as injected at the end of the panel's `<body>`. |

## Native loading states

Tables that enable Filament's `loadingSkeleton()`, on the table or through `showSkeletonsOnTableUpdate()`, inherit the panel's skeleton radius and colours. The initial placeholder for a deferred table follows the visible columns or content grid when Filament exposes table data to the content hook. Older Filament versions use the fixed placeholder.

## Deferred schema placeholder

The package adds `skeletonPlaceholder(SchemaPlaceholder $placeholder): Schema` to Filament schemas. Choose `Form`, `Infolist` or `List` from the `MortalKiller\FilamentSkeletonLoading\Enums\SchemaPlaceholder` enum. The schema must already use Filament's deferred loading. Call it after any plain `extraAttributes([...])` on the same schema, which would otherwise replace it.

## Lifecycle

- `register(Panel $panel)` loads the stylesheet through the panel's `STYLES_AFTER` render hook, adds the page loader to its `BODY_END` render hook when it is on, and a style that hides the shimmer when `shimmer(false)` is set.
- `boot(Panel $panel)` configures every table built while that panel is current, so `deferTables()`, `showSkeletonsOnTableUpdate()` and their settings apply only to the panels that use the plugin.
