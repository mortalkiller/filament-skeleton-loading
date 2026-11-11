---
title: Local development
description: Work on the package with Testbench and the Workbench demo panel.
---

```bash
composer install
```

## Workbench demo panel

The Workbench is a Filament panel at `/admin` with lazy stats, bar chart, doughnut and table widgets, an Orders page with a deferred table, an Invoices page whose table shows skeleton rows while it changes page, searches, filters, sorts or switches tabs (`showSkeletonsOnTableUpdate()`), and a slow Reports page for the page loader. The same demo without the plugin, with Filament's own loading states, is at `/plain`.

```bash
composer browser:prepare
composer serve
```

The demo widgets and the Reports page wait 1.5 seconds or more so the skeletons stay on screen. Set `WORKBENCH_LATENCY_MS` to change it (`0` turns it off):

```bash
WORKBENCH_LATENCY_MS=4000 composer serve
```

`WORKBENCH_TABLE_UPDATE_SKELETONS=0` runs the panel without `showSkeletonsOnTableUpdate()` and `WORKBENCH_SHIMMER=0` with `shimmer(false)`. Pass the same variable to `npm run test:browser` so the table update tests expect that behaviour.

The PHP built-in server handles one request at a time. With several lazy widgets, set `PHP_CLI_SERVER_WORKERS=4` so they load in parallel.

## Using an unreleased version in an application

Point the application to a local clone with a Composer path repository:

```json
{
    "repositories": [
        { "type": "path", "url": "../filament-skeleton-loading", "options": { "symlink": true } }
    ]
}
```

Use generic examples only; never publish real customer applications, production paths, infrastructure details or credentials.
