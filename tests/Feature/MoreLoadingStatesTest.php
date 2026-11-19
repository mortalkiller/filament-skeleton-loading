<?php

declare(strict_types=1);

use MortalKiller\FilamentSkeletonLoading\Tests\Fixtures\ItemsRelationManager;

function packageCss(): string
{
    return (string) file_get_contents(__DIR__.'/../../resources/css/skeleton-loading.css');
}

it('draws a table skeleton for relation managers', function () {
    $html = app(ItemsRelationManager::class)->placeholder()->render();

    expect($html)->toContain('fsl-skeleton--table')
        ->and(substr_count($html, 'fsl-row'))->toBe(5);
});

it('draws notification placeholders in the notifications panel', function () {
    expect(packageCss())->toContain('.fi-no-notifications-loading-ctn::before');
});

it('draws form rows in lazy loaded sections', function () {
    expect(packageCss())->toContain('.fi-sc-loading::before');
});

it('keeps lazy schema error messages visible and removes skeletons after a failure', function () {
    $css = packageCss();

    expect($css)
        ->toContain('.fi-sc-loading > :not(.fi-sr-only):not(.fi-sc-loading-indicator):not(.fi-sc-loading-error)')
        ->toContain('.fi-sc-loading > .fi-sc-loading-indicator > .fi-loading-indicator')
        ->toContain('.fi-sc-loading > .fi-sc-loading-indicator > .fi-loading-placeholder-content')
        ->toContain('.fi-sc-loading > .fi-sc-loading-indicator > span:not(.fi-sr-only)')
        ->toContain('.fi-sc-loading[aria-busy="false"]::before')
        ->toContain('.fi-sc-loading[aria-busy="false"]::after')
        ->not->toContain('.fi-sc-loading > :not(.fi-sr-only) {');
});

it('keeps every loading container still for reduced motion', function () {
    $reducedMotion = substr(packageCss(), strpos(packageCss(), 'prefers-reduced-motion'));

    expect($reducedMotion)->toContain('.fi-no-notifications-loading-ctn::after')
        ->toContain('.fi-sc-loading::after');
});
