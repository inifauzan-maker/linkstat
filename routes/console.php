<?php

use App\Contracts\CustomDomainVerifier;
use App\Models\LandingPage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('domains:verify {--domain=}', function () {
    $verifier = app(CustomDomainVerifier::class);
    $query = LandingPage::query()->whereNotNull('custom_domain');
    $requestedDomain = LandingPage::normalizeCustomDomain($this->option('domain'));

    if ($requestedDomain !== null) {
        $query->where('custom_domain', $requestedDomain);
    }

    $processed = 0;

    $query->each(function (LandingPage $landingPage) use ($verifier, &$processed): void {
        $verifier->sync($landingPage);
        $processed++;
    });

    $this->info("Verifikasi selesai untuk {$processed} custom domain.");
})->purpose('Verify DNS and SSL status for custom domains');

Schedule::command('domains:verify')->hourly();
