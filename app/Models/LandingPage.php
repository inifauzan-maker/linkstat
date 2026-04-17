<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LandingPage extends Model
{
    protected $fillable = [
        'user_id',
        'slug',
        'title',
        'headline',
        'bio',
        'avatar_url',
        'avatar_path',
        'whatsapp_number',
        'whatsapp_message',
        'cta_label',
        'theme',
        'is_active',
        'custom_domain',
        'custom_domain_connected_at',
        'custom_domain_dns_status',
        'custom_domain_dns_target',
        'custom_domain_dns_checked_at',
        'custom_domain_dns_message',
        'custom_domain_ssl_status',
        'custom_domain_ssl_issuer',
        'custom_domain_ssl_expires_at',
        'custom_domain_ssl_checked_at',
        'custom_domain_ssl_message',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'custom_domain_connected_at' => 'datetime',
            'custom_domain_dns_checked_at' => 'datetime',
            'custom_domain_ssl_expires_at' => 'datetime',
            'custom_domain_ssl_checked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(LandingPageLink::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeLinks(): HasMany
    {
        return $this->hasMany(LandingPageLink::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(LandingPageEvent::class);
    }

    public function normalizedWhatsAppNumber(): string
    {
        $number = preg_replace('/\D+/', '', $this->whatsapp_number) ?? '';

        if ($number === '') {
            return '';
        }

        if (Str::startsWith($number, '0')) {
            return '62'.substr($number, 1);
        }

        return $number;
    }

    public function whatsappUrl(): string
    {
        $baseUrl = 'https://wa.me/'.$this->normalizedWhatsAppNumber();

        if (! filled($this->whatsapp_message)) {
            return $baseUrl;
        }

        return $baseUrl.'?text='.urlencode($this->whatsapp_message);
    }

    public function usesUploadedAvatar(): bool
    {
        return filled($this->avatar_path);
    }

    public function avatarImageUrl(): ?string
    {
        if ($this->usesUploadedAvatar()) {
            return '/storage/'.ltrim($this->avatar_path, '/');
        }

        return filled($this->avatar_url) ? $this->avatar_url : null;
    }

    public function hasCustomDomain(): bool
    {
        return filled($this->custom_domain);
    }

    public function dnsVerified(): bool
    {
        return $this->custom_domain_dns_status === 'verified';
    }

    public function sslValid(): bool
    {
        return $this->custom_domain_ssl_status === 'valid';
    }

    public function publicPath(): string
    {
        return '/u/'.$this->slug;
    }

    public function ctaPath(bool $usingCustomDomain = false): string
    {
        return $usingCustomDomain ? '/cta' : $this->publicPath().'/cta';
    }

    public function linkPath(LandingPageLink $link, bool $usingCustomDomain = false): string
    {
        return $usingCustomDomain
            ? '/links/'.$link->getKey()
            : $this->publicPath().'/links/'.$link->getKey();
    }

    public function defaultPublicUrl(): string
    {
        return url($this->publicPath());
    }

    public function customDomainUrl(?string $path = null): string
    {
        $scheme = config('landing.custom_domain_scheme', 'https');
        $path = trim((string) $path, '/');

        return $scheme.'://'.$this->custom_domain.($path === '' ? '' : '/'.$path);
    }

    public function preferredPublicUrl(): string
    {
        return $this->hasCustomDomain()
            ? $this->customDomainUrl()
            : $this->defaultPublicUrl();
    }

    public function matchesCustomDomain(?string $host): bool
    {
        return filled($host) && filled($this->custom_domain)
            && Str::lower($this->custom_domain) === Str::lower((string) $host);
    }

    public function markCustomDomainConnected(string $host): void
    {
        if (! $this->matchesCustomDomain($host) || $this->custom_domain_connected_at !== null) {
            return;
        }

        $this->forceFill([
            'custom_domain_connected_at' => now(),
        ])->save();
    }

    public function themeConfig(): array
    {
        $themes = self::themes();

        return $themes[$this->theme] ?? $themes['sunset'];
    }

    public static function themes(): array
    {
        return [
            'sunset' => [
                'name' => 'Sunset Punch',
                'background' => 'radial-gradient(circle at top left, rgba(255, 183, 77, 0.32), transparent 28%), radial-gradient(circle at top right, rgba(255, 89, 94, 0.32), transparent 22%), linear-gradient(160deg, #1f0f19 0%, #0f172a 42%, #070c16 100%)',
                'surface' => 'rgba(17, 24, 39, 0.78)',
                'surface_soft' => 'rgba(255, 255, 255, 0.08)',
                'text' => '#fff6e9',
                'muted' => '#f3dfc8',
                'accent' => '#ff8a3d',
                'accent_soft' => '#ffd166',
                'border' => 'rgba(255, 255, 255, 0.12)',
                'button' => 'linear-gradient(135deg, #ff8a3d 0%, #ff5e5b 100%)',
            ],
            'mint' => [
                'name' => 'Mint Broadcast',
                'background' => 'radial-gradient(circle at top left, rgba(46, 204, 113, 0.2), transparent 30%), radial-gradient(circle at bottom right, rgba(52, 152, 219, 0.24), transparent 25%), linear-gradient(165deg, #062926 0%, #041c1a 48%, #031111 100%)',
                'surface' => 'rgba(6, 41, 38, 0.78)',
                'surface_soft' => 'rgba(255, 255, 255, 0.07)',
                'text' => '#eafff8',
                'muted' => '#c6f1e4',
                'accent' => '#4ade80',
                'accent_soft' => '#22d3ee',
                'border' => 'rgba(255, 255, 255, 0.11)',
                'button' => 'linear-gradient(135deg, #4ade80 0%, #22d3ee 100%)',
            ],
            'night' => [
                'name' => 'Night Studio',
                'background' => 'radial-gradient(circle at top left, rgba(125, 90, 255, 0.18), transparent 25%), radial-gradient(circle at bottom, rgba(255, 111, 145, 0.16), transparent 20%), linear-gradient(160deg, #111827 0%, #0b1220 48%, #020617 100%)',
                'surface' => 'rgba(15, 23, 42, 0.82)',
                'surface_soft' => 'rgba(255, 255, 255, 0.07)',
                'text' => '#eff4ff',
                'muted' => '#c9d6f5',
                'accent' => '#7c3aed',
                'accent_soft' => '#fb7185',
                'border' => 'rgba(255, 255, 255, 0.12)',
                'button' => 'linear-gradient(135deg, #7c3aed 0%, #fb7185 100%)',
            ],
        ];
    }

    public static function defaultAttributes(string $title, string $whatsappNumber, bool $isActive = true): array
    {
        return [
            'slug' => self::generateUniqueSlug($title),
            'title' => $title,
            'headline' => 'Konten, promo, dan CTA WhatsApp dalam satu halaman.',
            'bio' => 'Gunakan halaman ini untuk mengarahkan audiens ke WhatsApp, katalog, promo terbaru, dan konten edukasi.',
            'whatsapp_number' => $whatsappNumber,
            'whatsapp_message' => 'Halo, saya tertarik dengan info dari landing page Anda.',
            'cta_label' => 'Chat via WhatsApp',
            'theme' => 'sunset',
            'is_active' => $isActive,
        ];
    }

    public static function generateUniqueSlug(string $value): string
    {
        $baseSlug = Str::slug($value);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'page';
        $slug = $baseSlug;
        $counter = 1;

        while (self::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public static function normalizeCustomDomain(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $candidate = trim(Str::lower((string) $value));

        if (! Str::contains($candidate, '://')) {
            $candidate = 'http://'.$candidate;
        }

        $host = parse_url($candidate, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return rtrim(Str::lower($host), '.');
    }

    public static function isValidCustomDomain(?string $host): bool
    {
        if (! filled($host)) {
            return false;
        }

        return (bool) preg_match(
            '/^(?=.{3,120}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
            (string) $host
        );
    }

    public static function appHosts(): array
    {
        return collect(config('landing.app_hosts', []))
            ->filter()
            ->map(fn ($host) => Str::lower((string) $host))
            ->unique()
            ->values()
            ->all();
    }

    public static function isAppHost(?string $host): bool
    {
        return filled($host) && in_array(Str::lower((string) $host), self::appHosts(), true);
    }

    public static function resolveByHost(?string $host): ?self
    {
        $host = self::normalizeCustomDomain($host);

        if (! filled($host) || self::isAppHost($host)) {
            return null;
        }

        return self::query()->where('custom_domain', $host)->first();
    }
}
