<?php

declare(strict_types=1);

return [

    /*
     * Turn the skeletons on or off for the whole app. Off: Filament's own grey placeholder and
     * spinner come back, and the trait falls back to them.
     */
    'enabled' => (bool) env('SKELETON_LOADING_ENABLED', true),

    /*
     * Defer every table of every panel that uses the plugin (Filament's `deferLoading()`), so they
     * all show skeleton rows while loading. The plugin's `deferTables()` overrides this per panel.
     */
    'defer_tables' => (bool) env('SKELETON_LOADING_DEFER_TABLES', false),

    /*
     * Skeleton rows while a loaded table updates (changing page, filtering, searching, sorting), on
     * every table of every panel that uses the plugin, instead of the old rows staying on screen.
     * Uses Filament's `loadingSkeleton()`. Only those updates: the first load and the other
     * skeletons stay as they are. The plugin's `showSkeletonsOnTableUpdate()` overrides this per panel.
     */
    'show_skeletons_on_table_update' => (bool) env('SKELETON_LOADING_SHOW_ON_TABLE_UPDATE', false),

];
