<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LandingPageLink extends Model
{
    protected $fillable = [
        'landing_page_id',
        'label',
        'description',
        'url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LandingPageEvent::class);
    }

    public static function normalizeExternalUrl(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        $normalized = trim((string) $url);

        if ($normalized === '') {
            return null;
        }

        if (Str::startsWith($normalized, '//')) {
            $normalized = 'https:'.$normalized;
        }

        if (! preg_match('/^[a-z][a-z0-9+\-.]*:\/\//i', $normalized)) {
            $normalized = 'https://'.$normalized;
        }

        return $normalized;
    }

    public static function isValidExternalUrl(?string $url): bool
    {
        $normalized = static::normalizeExternalUrl($url);

        if ($normalized === null || ! filter_var($normalized, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = Str::lower((string) parse_url($normalized, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = Str::lower((string) parse_url($normalized, PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        if (! Str::contains($host, '.')) {
            return false;
        }

        $labels = array_values(array_filter(explode('.', $host)));

        if (count($labels) < 2) {
            return false;
        }

        $topLevelDomain = end($labels);

        return is_string($topLevelDomain) && strlen($topLevelDomain) >= 2;
    }

    public function destinationUrl(): string
    {
        return static::normalizeExternalUrl($this->url) ?? $this->url;
    }
}
