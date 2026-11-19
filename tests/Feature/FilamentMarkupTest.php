<?php

declare(strict_types=1);

/*
 * Canary tests for the Filament internals this package builds on: the lazy loading section view and
 * the loading containers that the stylesheet turns into skeletons. They are not covered by semver, so
 * a Filament release can change them. A failure here means Filament changed; review the package's
 * override and stylesheet against the new markup.
 */

use Composer\InstalledVersions;
use MortalKiller\FilamentSkeletonLoading\Tests\Fixtures\StockWidget;

function filamentSource(string $package, string $path): string
{
    $file = InstalledVersions::getInstallPath($package).'/'.$path;

    expect($file)->toBeReadableFile();

    return (string) file_get_contents($file);
}

/** @return list<string> */
function bladeProps(string $source): array
{
    expect(preg_match('/@props\(\[(.*?)\]\)/s', $source, $match))->toBe(1);
    preg_match_all("/'([A-Za-z]+)'\s*=>/", $match[1], $names);

    return $names[1];
}

/**
 * What a loading container holds besides Filament's loading indicator and its screen-reader label. The
 * stylesheet hides everything inside these containers, so this must stay empty.
 */
function loadingContainerExtras(string $source, string $opening): string
{
    $start = strpos($source, $opening);
    expect($start)->not->toBeFalse();

    $bodyStart = strpos($source, '>', $start + strlen($opening)) + 1;
    $body = substr($source, $bodyStart, strpos($source, '</div>', $bodyStart) - $bodyStart);

    return trim((string) preg_replace([
        '/\{\{\s*\\\\?(?:Filament\\\\Support\\\\)?generate_loading_indicator_html\(.*?\)\s*\}\}/s',
        '/<\?=\s*generate_loading_indicator_html\(.*?\)->toHtml\(\)\s*\?>/s',
        '/<span class="fi-sr-only">.*?<\/span>/s',
    ], '', $body));
}

it('still renders lazy widgets through the loading section view it overrides', function () {
    expect(app(StockWidget::class)->placeholder()->render())->toContain('fsl-skeleton--generic');
});

it('overrides the loading section with the same props as Filament', function () {
    $filament = filamentSource('filament/support', 'resources/views/components/loading-section.blade.php');
    $override = (string) file_get_contents(__DIR__.'/../../resources/views/overrides/components/loading-section.blade.php');

    expect(bladeProps($override))->toEqualCanonicalizing(bladeProps($filament));
});

it('finds only the loading indicator inside the deferred table container', function () {
    $source = filamentSource('filament/tables', 'resources/views/index.blade.php');

    expect(loadingContainerExtras($source, 'class="fi-ta-table-loading-ctn"'))->toBe('');
});

it('finds only the loading indicator inside the lazy schema container', function () {
    $source = filamentSource('filament/schemas', 'src/Schema.php');

    // Filament 4 has no deferred schemas.
    if (! str_contains($source, "'fi-sc-loading'")) {
        expect(version_compare((string) InstalledVersions::getVersion('filament/schemas'), '5.8.1', '<'))->toBeTrue();

        return;
    }

    $deferredHtml = substr($source, (int) strpos($source, "'fi-sc-loading'"));

    // Newer Filament versions keep the accessible label inside the indicator and render
    // a separate error message when loading fails. Both must survive our CSS overrides.
    if (str_contains($deferredHtml, 'class="fi-sc-loading-indicator"')) {
        expect($deferredHtml)
            ->toContain('x-show="! hasSchemaLoadingFailed"')
            ->toContain('x-show="hasSchemaLoadingFailed"')
            ->toContain('class="fi-sc-loading-error"');

        if (str_contains($deferredHtml, 'class="fi-loading-placeholder-content"')) {
            // Newest markup wraps its loading placeholder and has a screen-reader label.
            expect($deferredHtml)->toContain('class="fi-sr-only"');
        } else {
            // Earlier releases have an inline loading icon and a plain text label.
            expect($deferredHtml)
                ->toContain('generate_loading_indicator_html(size: IconSize::Small)')
                ->toContain("<?= e(__('filament::loading.label')) ?>");
        }

        expect($source)->toContain("'x-bind:aria-busy' => \"hasSchemaLoadingFailed ? 'false' : 'true'\"");

        return;
    }

    // Older Filament releases render a loading indicator directly inside the container.
    expect(loadingContainerExtras($deferredHtml, '<div <?= $attributes->toHtml() ?>'))->toBe('');
});

it('finds only the loading indicator inside the notifications loading container', function () {
    $source = filamentSource('filament/notifications', 'resources/views/database-notifications.blade.php');

    // Added in Filament 5.11.x.
    if (! str_contains($source, 'class="fi-no-notifications-loading-ctn"')) {
        expect(version_compare((string) InstalledVersions::getVersion('filament/notifications'), '5.11.2', '<'))->toBeTrue();

        return;
    }

    expect(loadingContainerExtras($source, 'class="fi-no-notifications-loading-ctn"'))->toBe('');
});
