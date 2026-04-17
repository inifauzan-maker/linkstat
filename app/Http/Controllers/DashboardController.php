<?php

namespace App\Http\Controllers;

use App\Contracts\CustomDomainVerifier;
use App\Models\LandingPage;
use App\Support\LandingPageAnalytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected LandingPageAnalytics $analytics,
        protected CustomDomainVerifier $domainVerifier,
    ) {
    }

    public function index(Request $request): View
    {
        $rangeOptions = [
            7 => '7 Hari',
            14 => '14 Hari',
            30 => '30 Hari',
        ];

        $validated = $request->validate([
            'range' => ['nullable', 'integer', Rule::in(array_keys($rangeOptions))],
            'start_date' => ['nullable', 'date_format:Y-m-d', 'required_with:end_date'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'required_with:start_date', 'after_or_equal:start_date'],
            'source' => ['nullable', 'string', 'max:120'],
        ]);

        $range = (int) ($validated['range'] ?? 7);
        $selectedSource = $validated['source'] ?? null;
        $customStartDate = filled($validated['start_date'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $validated['start_date'])->startOfDay()
            : null;
        $customEndDate = filled($validated['end_date'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $validated['end_date'])->endOfDay()
            : null;
        $isCustomRange = $customStartDate !== null && $customEndDate !== null;

        $landingPage = $this->ensureLandingPage($request);
        $landingPage->load('links');
        $analytics = $isCustomRange
            ? $this->analytics->summarizeBetween($landingPage, $customStartDate, $customEndDate, true, $selectedSource)
            : $this->analytics->summarize($landingPage, $range, $selectedSource);
        $availableSources = $this->analytics->availableSourcesForLandingPage($landingPage);
        $exportParams = $isCustomRange
            ? [
                'start_date' => $customStartDate?->toDateString(),
                'end_date' => $customEndDate?->toDateString(),
            ]
            : ['range' => $range];

        if ($analytics['source_filter'] !== null) {
            $exportParams['source'] = $analytics['source_filter'];
        }

        return view('dashboard.index', [
            'landingPage' => $landingPage,
            'analytics' => $analytics,
            'themes' => LandingPage::themes(),
            'publicUrl' => $landingPage->preferredPublicUrl(),
            'defaultPublicUrl' => $landingPage->defaultPublicUrl(),
            'customDomainUrl' => $landingPage->hasCustomDomain() ? $landingPage->customDomainUrl() : null,
            'selectedRange' => $isCustomRange ? null : $range,
            'rangeOptions' => $rangeOptions,
            'isCustomRange' => $isCustomRange,
            'customStartDate' => $customStartDate?->toDateString(),
            'customEndDate' => $customEndDate?->toDateString(),
            'avatarUrl' => $landingPage->avatarImageUrl(),
            'manualAvatarUrl' => $landingPage->usesUploadedAvatar() ? '' : $landingPage->avatar_url,
            'selectedSource' => $analytics['source_filter'],
            'availableSources' => $availableSources,
            'exportCsvUrl' => route('dashboard.analytics.export', [...$exportParams, 'format' => 'csv']),
            'exportExcelUrl' => route('dashboard.analytics.export', [...$exportParams, 'format' => 'excel']),
            'customDomainTarget' => config('landing.custom_domain_target'),
            'customDomainScheme' => config('landing.custom_domain_scheme', 'https'),
            'domainVerifyUrl' => route('dashboard.domain.verify'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $landingPage = $this->ensureLandingPage($request);

        $validated = $request->validate([
            'slug' => ['required', 'alpha_dash', 'min:3', 'max:32', Rule::unique('landing_pages', 'slug')->ignore($landingPage->id)],
            'title' => ['required', 'string', 'max:80'],
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:320'],
            'avatar_url' => ['nullable', 'url', 'max:255'],
            'avatar_file' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'whatsapp_number' => ['required', 'string', 'max:25'],
            'whatsapp_message' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['required', 'string', 'max:40'],
            'theme' => ['required', Rule::in(array_keys(LandingPage::themes()))],
            'custom_domain' => ['nullable', 'string', 'max:120'],
        ]);

        $customDomain = LandingPage::normalizeCustomDomain($validated['custom_domain'] ?? null);

        if (filled($validated['custom_domain'] ?? null) && (! $customDomain || ! LandingPage::isValidCustomDomain($customDomain))) {
            return back()
                ->withInput()
                ->withErrors([
                    'custom_domain' => 'Gunakan format domain yang valid seperti promo.brandanda.com.',
                ]);
        }

        if ($customDomain !== null && LandingPage::isAppHost($customDomain)) {
            return back()
                ->withInput()
                ->withErrors([
                    'custom_domain' => 'Custom domain harus berbeda dari domain utama aplikasi.',
                ]);
        }

        if ($customDomain !== null && LandingPage::query()
            ->where('custom_domain', $customDomain)
            ->where('id', '!=', $landingPage->id)
            ->exists()) {
            return back()
                ->withInput()
                ->withErrors([
                    'custom_domain' => 'Domain ini sudah dipakai oleh landing page lain.',
                ]);
        }

        $attributes = [
            'slug' => strtolower($validated['slug']),
            'title' => $validated['title'],
            'headline' => $validated['headline'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'],
            'whatsapp_message' => $validated['whatsapp_message'] ?? null,
            'cta_label' => $validated['cta_label'],
            'theme' => $validated['theme'],
            'is_active' => $request->boolean('is_active'),
            'custom_domain' => $customDomain,
            'custom_domain_connected_at' => $landingPage->custom_domain === $customDomain
                ? $landingPage->custom_domain_connected_at
                : null,
        ];

        if ($request->boolean('remove_avatar')) {
            $this->deleteStoredAvatar($landingPage);
            $attributes['avatar_url'] = null;
            $attributes['avatar_path'] = null;
        }

        if ($request->hasFile('avatar_file')) {
            $this->deleteStoredAvatar($landingPage);

            $attributes['avatar_path'] = $request->file('avatar_file')->store(
                'avatars/'.$request->user()->getKey(),
                'public'
            );
            $attributes['avatar_url'] = null;
        } elseif (filled($validated['avatar_url'] ?? null)) {
            $this->deleteStoredAvatar($landingPage);
            $attributes['avatar_url'] = $validated['avatar_url'];
            $attributes['avatar_path'] = null;
        }

        $landingPage->update($attributes);

        if ($landingPage->hasCustomDomain()) {
            $this->domainVerifier->sync($landingPage);
        } else {
            $this->domainVerifier->clear($landingPage);
        }

        return redirect()->route('dashboard')->with('status', 'Landing page berhasil diperbarui.');
    }

    public function verifyDomain(Request $request): RedirectResponse
    {
        $landingPage = $this->ensureLandingPage($request);

        if (! $landingPage->hasCustomDomain()) {
            return redirect()
                ->route('dashboard')
                ->withErrors([
                    'custom_domain' => 'Isi custom domain terlebih dahulu sebelum menjalankan verifikasi.',
                ]);
        }

        $this->domainVerifier->sync($landingPage);

        return redirect()->route('dashboard')->with('status', 'Verifikasi DNS dan SSL selesai dijalankan.');
    }

    protected function ensureLandingPage(Request $request): LandingPage
    {
        return $request->user()->landingPage()->firstOrCreate([], [
            ...LandingPage::defaultAttributes($request->user()->name, '628000000000', false),
            'bio' => 'Lengkapi profil ini untuk mulai membagikan link unik ke audiens Anda.',
            'whatsapp_message' => 'Halo, saya melihat landing page Anda.',
        ]);
    }

    protected function deleteStoredAvatar(LandingPage $landingPage): void
    {
        if (! $landingPage->usesUploadedAvatar()) {
            return;
        }

        Storage::disk('public')->delete($landingPage->avatar_path);
    }
}
