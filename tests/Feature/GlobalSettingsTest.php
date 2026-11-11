<?php

declare(strict_types=1);

use Filament\Tables\Table;
use MortalKiller\FilamentSkeletonLoading\SkeletonLoadingPlugin;
use MortalKiller\FilamentSkeletonLoading\Tests\Fixtures\LatestOrders;

it('ships a config file with skeletons on and table deferral and update skeletons off', function () {
    expect(config('filament-skeleton-loading.enabled'))->toBeTrue()
        ->and(config('filament-skeleton-loading.defer_tables'))->toBeFalse()
        ->and(config('filament-skeleton-loading.show_skeletons_on_table_update'))->toBeFalse();
});

it('leaves tables alone by default', function () {
    $table = Table::make(app(LatestOrders::class));

    expect($table->isLoadingDeferred())->toBeFalse()
        ->and($table->hasLoadingSkeleton())->toBeFalse();
});

it('defers every table when the defer_tables setting is on', function () {
    config(['filament-skeleton-loading.defer_tables' => true]);

    expect(Table::make(app(LatestOrders::class))->isLoadingDeferred())->toBeTrue();
});

it('lets the plugin decide per table over the setting', function () {
    config(['filament-skeleton-loading.defer_tables' => true]);
    SkeletonLoadingPlugin::current()->deferTables(fn (Table $table): bool => false);

    expect(Table::make(app(LatestOrders::class))->isLoadingDeferred())->toBeFalse();
});

it('adds update skeletons to every table when the show_skeletons_on_table_update setting is on', function () {
    config(['filament-skeleton-loading.show_skeletons_on_table_update' => true]);

    expect(Table::make(app(LatestOrders::class))->hasLoadingSkeleton())->toBeTrue();
});

it('adds update skeletons from the plugin without the setting', function () {
    SkeletonLoadingPlugin::current()->showSkeletonsOnTableUpdate();

    expect(Table::make(app(LatestOrders::class))->hasLoadingSkeleton())->toBeTrue();
});

it('lets the plugin decide update skeletons per table over the setting', function () {
    config(['filament-skeleton-loading.show_skeletons_on_table_update' => true]);
    SkeletonLoadingPlugin::current()->showSkeletonsOnTableUpdate(fn (Table $table): bool => false);

    expect(Table::make(app(LatestOrders::class))->hasLoadingSkeleton())->toBeFalse();
});

it('leaves the first load alone when adding update skeletons', function () {
    SkeletonLoadingPlugin::current()->showSkeletonsOnTableUpdate();

    expect(Table::make(app(LatestOrders::class))->isLoadingDeferred())->toBeFalse();
});
