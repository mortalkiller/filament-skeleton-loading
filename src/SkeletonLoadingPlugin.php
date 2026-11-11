<?php

declare(strict_types=1);

namespace MortalKiller\FilamentSkeletonLoading;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Panel;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\View\ComponentAttributeBag;
use Filament\Tables\Table;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use MortalKiller\FilamentSkeletonLoading\Enums\SchemaPlaceholder;

final class SkeletonLoadingPlugin implements Plugin
{
    public const ID = 'mortalkiller-skeleton-loading';

    public const PACKAGE = 'mortalkiller/filament-skeleton-loading';

    private bool $shimmer = true;

    private ?string $radius = null;

    private bool $selectSearchSkeletons = false;

    private bool $globalSearchSkeletons = false;

    private bool $pageLoader = false;

    private string|Htmlable|Closure|null $pageLoaderSymbol = null;

    private int $pageLoaderDelay = 300;

    private string|Closure|null $pageLoaderLabel = null;

    /** @var bool|Closure(Table): bool|null null: the `defer_tables` setting decides */
    private bool|Closure|null $deferTables = null;

    /** @var bool|Closure(Table): bool|null null: the `show_skeletons_on_table_update` setting decides */
    private bool|Closure|null $showSkeletonsOnTableUpdate = null;

    public static function make(): self
    {
        return app(self::class);
    }

    /** The plugin registered on the current panel, if any. */
    public static function current(): ?self
    {
        $panel = Filament::getCurrentPanel();
        $plugin = $panel?->hasPlugin(self::ID) ? $panel->getPlugin(self::ID) : null;

        return $plugin instanceof self ? $plugin : null;
    }

    /** The global switch (`filament-skeleton-loading.enabled`, env SKELETON_LOADING_ENABLED). */
    public static function isEnabled(): bool
    {
        return (bool) config('filament-skeleton-loading.enabled', true);
    }

    public function getId(): string
    {
        return self::ID;
    }

    /** A light sweeping across the skeleton (on by default; always still for reduced motion). */
    public function shimmer(bool $condition = true): self
    {
        $this->shimmer = $condition;

        return $this;
    }

    public function hasShimmer(): bool
    {
        return $this->shimmer;
    }

    /** Round skeleton bars and cells using a theme size or a non-negative CSS pixel length. */
    public function rounded(int|string $size = 'md'): self
    {
        if (is_int($size)) {
            $size .= 'px';
        }

        $fallback = match ($size) {
            'xs' => '0.125rem',
            'sm' => '0.25rem',
            'md' => '6px',
            'lg' => '0.5rem',
            'xl' => '0.75rem',
            '2xl' => '1rem',
            '3xl' => '1.5rem',
            '4xl' => '2rem',
            default => null,
        };

        $this->radius = match (true) {
            $fallback !== null => "var(--radius-{$size}, {$fallback})",
            $size === 'none' => '0',
            $size === 'full' => '9999px',
            preg_match('/\A(?:[0-9]+(?:\.[0-9]+)?|\.[0-9]+)px\z/', $size) === 1 => $size,
            default => throw new InvalidArgumentException('The skeleton radius must be a theme size or a non-negative pixel length.'),
        };

        return $this;
    }

    /** Skeleton lines while a searchable select waits for its options (off by default). */
    public function selectSearchSkeletons(bool $condition = true): self
    {
        $this->selectSearchSkeletons = $condition;

        return $this;
    }

    /** Skeleton lines under the global search field while it waits for results (off by default). */
    public function globalSearchSkeletons(bool $condition = true): self
    {
        $this->globalSearchSkeletons = $condition;

        return $this;
    }

    /**
     * A full-screen loader between pages of the panel (off by default). Shown only when the next
     * page takes longer than the delay; never for Livewire requests or `download` links. A click or
     * Escape hides it when the browser stays on the page.
     */
    public function pageLoader(bool $condition = true): self
    {
        $this->pageLoader = $condition;

        return $this;
    }

    public function hasPageLoader(): bool
    {
        return $this->pageLoader;
    }

    /**
     * The brand symbol drawn by the page loader (markup, a view or a closure returning one). Its
     * elements with class `fsl-loader-piece` come in one after the other.
     */
    public function pageLoaderSymbol(string|Htmlable|Closure $symbol): self
    {
        $this->pageLoaderSymbol = $symbol;

        return $this;
    }

    /** How long a page may take before the loader shows (milliseconds). */
    public function pageLoaderDelay(int $milliseconds): self
    {
        $this->pageLoaderDelay = max(0, $milliseconds);

        return $this;
    }

    public function pageLoaderLabel(string|Closure $label): self
    {
        $this->pageLoaderLabel = $label;

        return $this;
    }

    public function renderPageLoader(): string
    {
        $symbol = value($this->pageLoaderSymbol);

        // A view (or anything renderable) is rendered here, so Blade never escapes it as text.
        $symbol = match (true) {
            $symbol === null => null,
            $symbol instanceof Renderable => new HtmlString($symbol->render()),
            $symbol instanceof Htmlable => $symbol,
            default => new HtmlString((string) $symbol),
        };

        return view('filament-skeleton-loading::page-loader', [
            'symbol' => $symbol,
            'delay' => $this->pageLoaderDelay,
            'label' => value($this->pageLoaderLabel) ?? __('filament-skeleton-loading::loader.label'),
        ])->render();
    }

    /**
     * Load every table of the panel after the page (Filament's `deferLoading()`), showing skeleton
     * rows meanwhile. A closure receives the table and decides per table. Not called: the
     * `defer_tables` setting decides (env SKELETON_LOADING_DEFER_TABLES).
     *
     * @param  bool|Closure(Table): bool  $condition
     */
    public function deferTables(bool|Closure $condition = true): self
    {
        $this->deferTables = $condition;

        return $this;
    }

    /**
     * Skeleton rows while a loaded table updates: changing page or records per page, filtering,
     * searching, sorting, switching tabs or grouping (Filament's `loadingSkeleton()`), drawn in the
     * panel's skeleton style. Only those updates: the first load and the package's other skeletons
     * stay as they are. Off by default. A closure receives the table and decides per table. Not
     * called: the `show_skeletons_on_table_update` setting decides (env SKELETON_LOADING_SHOW_ON_TABLE_UPDATE).
     *
     * @param  bool|Closure(Table): bool  $condition
     */
    public function showSkeletonsOnTableUpdate(bool|Closure $condition = true): self
    {
        $this->showSkeletonsOnTableUpdate = $condition;

        return $this;
    }

    public function register(Panel $panel): void
    {
        if (self::isEnabled()) {
            $panel->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => collect(FilamentAsset::getStyles([self::PACKAGE]))
                    ->map(fn (Css $stylesheet): string => $stylesheet->getHtml()->toHtml())
                    ->implode(''),
            );

            $panel->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => $this->radius === null
                    ? ''
                    : '<style>:root{--fsl-rounded:'.$this->radius.'}</style>',
            );

            if (defined(TablesRenderHook::class.'::CONTENT_BEFORE')) {
                $panel->renderHook(TablesRenderHook::CONTENT_BEFORE, fn (array $data): string => $this->renderDeferredTable($panel, $data));
            }

            if ($this->selectSearchSkeletons) {
                $panel->renderHook(PanelsRenderHook::BODY_START, fn (): string => '<span hidden data-fsl-search></span>');
            }

            if ($this->globalSearchSkeletons) {
                $panel->renderHook(
                    PanelsRenderHook::GLOBAL_SEARCH_END,
                    fn (): string => view('filament-skeleton-loading::search')->render(),
                );
            }
        }

        if (self::isEnabled() && $this->pageLoader) {
            $panel->renderHook(PanelsRenderHook::BODY_END, fn (): string => $this->renderPageLoader());
        }

        if (self::isEnabled() && ! $this->shimmer) {
            $panel->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<style>.fsl-bone::after,.fi-ta-table-loading-ctn::after,.fi-no-notifications-loading-ctn::after,.fi-sc-loading::after{display:none!important}.fi-ta-content-loading{--loading-skeleton-animation:none}</style>',
            );
        }
    }

    /** @param array<string, mixed> $data */
    private function renderDeferredTable(Panel $panel, array $data): string
    {
        $table = $data['table'] ?? null;

        if (Filament::getCurrentPanel() !== $panel || ! $table instanceof Table || ! array_key_exists('records', $data) || $data['records'] !== null) {
            return '';
        }

        $grid = $table->getContentGrid();
        $isCardLayout = $grid !== null || $table->hasColumnsLayout();
        $columns = count($table->getVisibleColumns());

        if (! $isCardLayout && $columns === 0) {
            return '';
        }

        return view('filament-skeleton-loading::deferred-table', [
            'columns' => min($columns, 12),
            'isCardLayout' => $isCardLayout,
            'attributes' => $isCardLayout ? (new ComponentAttributeBag)->grid($grid ?? ['default' => 1]) : null,
        ])->render();
    }

    public function boot(Panel $panel): void
    {
        if (! self::isEnabled()) {
            return;
        }

        Schema::configureUsing(function (Schema $schema) use ($panel): void {
            if (Filament::getCurrentPanel() !== $panel) {
                return;
            }

            $schema->extraAttributes(function () use ($schema): array {
                // Earlier Filament versions do not expose deferred schemas.
                // @phpstan-ignore function.alreadyNarrowedType (The analysis environment uses Filament 5.)
                if (! method_exists($schema, 'isLoadingDeferred') || ! $schema->isLoadingDeferred()) {
                    return [];
                }

                $kind = match (true) {
                    $schema->getParentComponent() instanceof Repeater => SchemaPlaceholder::List,
                    $schema->getLivewire() instanceof ViewRecord => SchemaPlaceholder::Infolist,
                    default => SchemaPlaceholder::Form,
                };

                return ['data-fsl-schema-default' => $kind->value];
            }, merge: true);
        });

        // Read at each table, so the setting and later plugin calls are honoured.
        Table::configureUsing(function (Table $table) use ($panel): void {
            if (Filament::getCurrentPanel()?->getId() !== $panel->getId()) {
                return;
            }

            if ($this->appliesTo($table, $this->deferTables, 'defer_tables')) {
                $table->deferLoading();
            }

            if ($this->appliesTo($table, $this->showSkeletonsOnTableUpdate, 'show_skeletons_on_table_update')) {
                $table->loadingSkeleton();
            }
        });
    }

    /**
     * A plugin option wins over its setting; a closure receives the table and decides.
     *
     * @param  bool|Closure(Table): bool|null  $option
     */
    private function appliesTo(Table $table, bool|Closure|null $option, string $setting): bool
    {
        $condition = $option ?? (bool) config("filament-skeleton-loading.{$setting}", false);

        return $condition instanceof Closure ? (bool) $condition($table) : $condition;
    }
}
