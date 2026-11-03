<?php

use App\Services\MetaCatalogService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('meta:sync-catalog', function (MetaCatalogService $catalog) {
    if (! $catalog->isConfigured()) {
        $this->warn('Meta catalog is not configured (META_CATALOG_ID / META_CATALOG_TOKEN).');

        return;
    }

    $this->info($catalog->sync() . ' products sent to the Meta catalog.');
})->purpose('Send all products to the Meta catalog');

Schedule::command('meta:sync-catalog')->hourly();
