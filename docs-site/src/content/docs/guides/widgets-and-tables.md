---
title: Widgets and tables
description: Typed skeletons for lazy widgets and relation managers, and skeleton rows for deferred tables.
---

## Typed widget skeletons

Add `HasSkeletonPlaceholder` to a lazy widget or relation manager. Both are lazy by default in Filament.

```php
use Filament\Widgets\ChartWidget;
use MortalKiller\FilamentSkeletonLoading\Concerns\HasSkeletonPlaceholder;

class RevenueChart extends ChartWidget
{
    use HasSkeletonPlaceholder;

    protected ?string $heading = 'Revenue per month';

    // ...
}
```

The trait picks the shape from the class:

| Class | Skeleton |
| --- | --- |
| `StatsOverviewWidget` | One tile per column of the widget (4 when the columns are not a plain number). |
| `ChartWidget` | Bars for bar charts and unknown types, a line for `line` and `radar`, a ring for `doughnut`, `pie` and `polarArea`. The chart's real heading is shown above it. |
| `TableWidget`, `RelationManager` | A header and 5 rows. |
| Anything else | The generic skeleton: a title and two lines. |

![Stats overview skeleton: one tile per column](/filament-skeleton-loading/screenshots/widget-stats-light.png)

![Chart skeletons: bars and a ring under the real headings](/filament-skeleton-loading/screenshots/widget-charts-light.png)

![Table widget skeleton: a header and five rows](/filament-skeleton-loading/screenshots/widget-table-light.png)

The skeleton keeps the widget's column span, column start and placeholder height, so the layout does not jump when the content arrives.

### Fine-tuning

```php
protected ?int $skeletonItems = 3; // stat tiles (stats widgets)
protected ?int $skeletonRows = 8;  // rows (table widgets and relation managers)
```

Stats placeholders use the declared integer column count or four tiles by default. Set `$skeletonItems` for widgets with a custom or responsive layout. The placeholder leaves metric calculation to the deferred widget request.

### Widgets without the trait

Every lazy widget, relation manager or Livewire component that renders Filament's `loading-section` view draws the generic skeleton, with no code change.

## Deferred tables

Use Filament's own deferred loading:

```php
public function table(Table $table): Table
{
    return $table
        ->deferLoading()
        ->columns([/* ... */]);
}
```

While the records load, the package draws skeleton rows or cards. On versions with Filament's table content hook data, the placeholder follows the visible columns or content grid. Older versions use fixed skeleton rows.

![Deferred table drawing skeleton rows](/filament-skeleton-loading/screenshots/deferred-table-light.png)

To defer every table of a panel, call `deferTables()` on the plugin or turn the `defer_tables` setting on. A closure decides per table:

```php
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

SkeletonLoadingPlugin::make()
    ->deferTables(fn (Table $table): bool => $table->getLivewire() instanceof ListRecords);
```

Deferral only covers the first load. While a loaded table searches, sorts, filters or changes page, Filament keeps the old rows on screen unless the table has `loadingSkeleton()`. Enable it on a table to get skeleton rows in the package's style:

```php
return $table
    ->loadingSkeleton()
    ->columns([/* ... */]);
```

or on every table of the panel with `showSkeletonsOnTableUpdate()` or the `show_skeletons_on_table_update` setting (both off by default). They change only these updates, not the first load or the package's other skeletons. A closure decides per table:

```php
SkeletonLoadingPlugin::make()
    ->showSkeletonsOnTableUpdate(fn (Table $table): bool => $table->getLivewire() instanceof ListRecords);
```

## Rounded placeholders

Set the radius on the panel plugin for widget skeleton bars and deferred table cells.

```php
$panel->plugin(
    SkeletonLoadingPlugin::make()->rounded('lg'),
);
```

Use `rounded(8)` or `rounded('8px')` for an exact radius or `rounded('none')` for square corners. With the method omitted, the plugin follows the theme's `--fsl-bone-radius`, then `--radius-md`, with a `6px` fallback. The outer table border and chart ring keep their own shape. Notification and deferred schema placeholders have their own styling.

## Search skeletons

Opt in per panel when you want skeleton feedback while Filament's native searchable selects and global search wait for results:

```php
SkeletonLoadingPlugin::make()
    ->selectSearchSkeletons()   // searchable selects
    ->globalSearchSkeletons();  // global search
```

Each method turns one surface on; both are off by default. Filament keeps its native messages and keyboard interaction.

## Notifications and lazy sections

- The database notifications panel draws three placeholder notifications while it loads.
- Lazy loaded schemas (`Section::make()->schema([...])->lazyLoaded()`) draw form rows, a label and a field each, while they load.

Both work as soon as the plugin is registered.

### Deferred schema shapes

Deferred schemas use a form placeholder by default. A deferred schema on a `ViewRecord` page gets a read-only layout, and a schema directly inside a repeater gets a list layout. To select a shape explicitly, call the fluent method on the schema you defer:

```php
use MortalKiller\FilamentSkeletonLoading\Enums\SchemaPlaceholder;

$schema
    ->lazyLoaded()
    ->skeletonPlaceholder(SchemaPlaceholder::Infolist);
```

The enum provides `Form`, `Infolist` and `List`. The method selects the visual shape while Filament controls when the schema loads.

`skeletonPlaceholder()` stores its choice in the schema's extra attributes. Call it after any `extraAttributes([...])` on the same schema, or pass `merge: true` to `extraAttributes()`, because a plain `extraAttributes([...])` replaces the attributes set before it. The same applies to the automatic choice: a plain `extraAttributes([...])` falls back to the form placeholder.

## Tests in your application

A deferred table has no records until it loads. Load it before asserting:

```php
Livewire::test(ListOrders::class)
    ->loadTable()
    ->assertCanSeeTableRecords($orders);
```

If you turn `defer_tables` on, pin `SKELETON_LOADING_DEFER_TABLES` in `phpunit.xml` so the tests do not depend on `.env`.
