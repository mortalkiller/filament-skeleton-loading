<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use MortalKiller\FilamentSkeletonLoading\SkeletonLoadingPlugin;
use MortalKiller\FilamentSkeletonLoading\Tests\Fixtures\LatestOrders;
use MortalKiller\FilamentSkeletonLoading\Tests\Fixtures\RevenueChart;
use MortalKiller\FilamentSkeletonLoading\Tests\Fixtures\StockWidget;

it('runs in a panel without the plugin', function () {
    expect(Filament::getCurrentPanel()->getId())->toBe('plain')
        ->and(SkeletonLoadingPlugin::current())->toBeNull();
});

it('keeps Filament\'s own lazy loading section', function () {
    expect(view('filament::components.loading-section')->render())
        ->toContain('fi-loading-section')
        ->not->toContain('fsl-');

    expect(app(StockWidget::class)->placeholder()->render())
        ->toContain('fi-loading-section')
        ->not->toContain('fsl-');
});

it('keeps Filament\'s placeholder for widgets that use the trait', function () {
    expect(app(RevenueChart::class)->placeholder()->render())
        ->toContain('fi-loading-section')
        ->not->toContain('fsl-');
});

it('does not load the stylesheet', function () {
    expect(FilamentView::renderHook(PanelsRenderHook::STYLES_AFTER)->toHtml())->not->toContain('skeleton-loading')
        ->and(FilamentAsset::renderStyles())->not->toContain('skeleton-loading');
});

it('leaves the tables of a panel without the plugin alone', function () {
    config([
        'filament-skeleton-loading.defer_tables' => true,
        'filament-skeleton-loading.show_skeletons_on_table_update' => true,
    ]);

    $table = Table::make(app(LatestOrders::class));

    expect($table->isLoadingDeferred())->toBeFalse()
        ->and($table->hasLoadingSkeleton())->toBeFalse();
});
