<?php

namespace Tests\Feature;

use App\Contracts\CustomDomainVerifier;
use App\Models\LandingPage;
use App\Models\LandingPageEvent;
use App\Models\User;
use App\Support\LandingPageAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingPageFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_host_root_renders_login_page_for_guest(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Login Dashboard')
            ->assertSee('Masuk ke Dashboard')
            ->assertDontSee('Landing Page Bio + Analytics');
    }

    public function test_app_host_root_redirects_authenticated_user_to_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Root Redirect User',
            'email' => 'root-user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_user_can_register_and_receive_a_landing_page(): void
    {
        $response = $this->post('/register', [
            'name' => 'Klinik Sehat',
            'email' => 'owner@example.com',
            'whatsapp_number' => '6281234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('landing_pages', [
            'title' => 'Klinik Sehat',
            'slug' => 'klinik-sehat',
            'whatsapp_number' => '6281234567890',
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_update_page_settings(): void
    {
        $user = User::query()->create([
            'name' => 'Brand Satu',
            'email' => 'brand@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $landingPage = $user->landingPage()->create([
            'slug' => 'brand-satu',
            'title' => 'Brand Satu',
            'headline' => 'Judul lama',
            'bio' => 'Bio lama',
            'whatsapp_number' => '628111111111',
            'whatsapp_message' => 'Halo',
            'cta_label' => 'Chat',
            'theme' => 'sunset',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('dashboard.page.update'), [
                'slug' => 'brand-satu-pro',
                'title' => 'Brand Satu Pro',
                'headline' => 'Promo dan edukasi terbaru',
                'bio' => 'Landing page baru untuk sosial media.',
                'avatar_url' => 'https://example.com/avatar.jpg',
                'whatsapp_number' => '628222222222',
                'whatsapp_message' => 'Halo, saya ingin tahu promo terbaru.',
                'cta_label' => 'Hubungi Sekarang',
                'theme' => 'mint',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('dashboard'));

        $landingPage->refresh();

        $this->assertSame('brand-satu-pro', $landingPage->slug);
        $this->assertSame('Brand Satu Pro', $landingPage->title);
        $this->assertSame('mint', $landingPage->theme);
        $this->assertTrue($landingPage->is_active);
    }

    public function test_authenticated_user_can_upload_avatar_file(): void
    {
        Storage::fake('public');

        $user = User::query()->create([
            'name' => 'Avatar Brand',
            'email' => 'avatar@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $landingPage = $user->landingPage()->create([
            'slug' => 'avatar-brand',
            'title' => 'Avatar Brand',
            'headline' => 'Avatar upload test',
            'bio' => 'Upload avatar ke storage lokal.',
            'whatsapp_number' => '628777777777',
            'whatsapp_message' => 'Halo Avatar',
            'cta_label' => 'Chat',
            'theme' => 'sunset',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('dashboard.page.update'), [
                'slug' => 'avatar-brand',
                'title' => 'Avatar Brand',
                'headline' => 'Avatar upload test',
                'bio' => 'Upload avatar ke storage lokal.',
                'avatar_url' => '',
                'avatar_file' => UploadedFile::fake()->image('avatar.png', 300, 300),
                'whatsapp_number' => '628777777777',
                'whatsapp_message' => 'Halo Avatar',
                'cta_label' => 'Chat',
                'theme' => 'sunset',
                'custom_domain' => '',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('dashboard'));

        $landingPage->refresh();

        $this->assertNotNull($landingPage->avatar_path);
        $this->assertNull($landingPage->avatar_url);
        Storage::disk('public')->assertExists($landingPage->avatar_path);

        $this->get(route('public.pages.show', $landingPage))
            ->assertOk()
            ->assertSee('/storage/'.$landingPage->avatar_path, false);
    }

    public function test_public_page_records_view_cta_and_link_click_events(): void
    {
        $user = User::query()->create([
            'name' => 'Studio Glow',
            'email' => 'studio@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $landingPage = $user->landingPage()->create([
            'slug' => 'studio-glow',
            'title' => 'Studio Glow',
            'headline' => 'Perawatan wajah dan promo',
            'bio' => 'Semua link penting ada di sini.',
            'whatsapp_number' => '628333333333',
            'whatsapp_message' => 'Halo Studio Glow',
            'cta_label' => 'Chat via WhatsApp',
            'theme' => 'night',
            'is_active' => true,
        ]);

        $link = $landingPage->links()->create([
            'label' => 'Promo Treatment',
            'description' => 'Lihat detail promo bulan ini',
            'url' => 'https://example.com/promo',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->withHeader('referer', 'https://instagram.com/studioglow')
            ->get(route('public.pages.show', $landingPage))
            ->assertOk()
            ->assertSee('Studio Glow');

        $this->assertDatabaseHas('landing_page_events', [
            'landing_page_id' => $landingPage->id,
            'event_type' => LandingPageEvent::PAGE_VIEW,
        ]);

        $this->get(route('public.pages.cta', $landingPage))
            ->assertRedirect($landingPage->whatsappUrl());

        $this->assertDatabaseHas('landing_page_events', [
            'landing_page_id' => $landingPage->id,
            'event_type' => LandingPageEvent::CTA_CLICK,
            'clicked_url' => $landingPage->whatsappUrl(),
        ]);

        $this->get(route('public.pages.links', [$landingPage, $link]))
            ->assertRedirect('https://example.com/promo');

        $this->assertDatabaseHas('landing_page_events', [
            'landing_page_id' => $landingPage->id,
            'landing_page_link_id' => $link->id,
            'event_type' => LandingPageEvent::LINK_CLICK,
            'clicked_url' => 'https://example.com/promo',
        ]);
    }

    public function test_user_can_store_link_without_scheme_and_public_redirect_uses_https(): void
    {
        $user = User::query()->create([
            'name' => 'Link Normalizer',
            'email' => 'link-normalizer@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $landingPage = $user->landingPage()->create([
            'slug' => 'link-normalizer',
            'title' => 'Link Normalizer',
            'headline' => 'Normalize website link',
            'bio' => 'Testing URL normalization.',
            'whatsapp_number' => '628313131313',
            'whatsapp_message' => 'Halo Link',
            'cta_label' => 'Chat via WhatsApp',
            'theme' => 'night',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.links.store'), [
                'label' => 'Website',
                'description' => 'Kunjungi Website Kami',
                'url' => 'example.com/program',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('dashboard'));

        $link = $landingPage->links()->firstOrFail();

        $this->assertSame('https://example.com/program', $link->url);

        $this->get(route('public.pages.links', [$landingPage, $link]))
            ->assertRedirect('https://example.com/program');

        $this->assertDatabaseHas('landing_page_events', [
            'landing_page_id' => $landingPage->id,
            'landing_page_link_id' => $link->id,
            'event_type' => LandingPageEvent::LINK_CLICK,
            'clicked_url' => 'https://example.com/program',
        ]);
    }

    public function test_user_cannot_store_link_with_invalid_domain(): void
    {
        $user = User::query()->create([
            'name' => 'Invalid Link',
            'email' => 'invalid-link@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $user->landingPage()->create([
            'slug' => 'invalid-link',
            'title' => 'Invalid Link',
            'headline' => 'Invalid URL test',
            'bio' => 'Testing invalid URL validation.',
            'whatsapp_number' => '628414141414',
            'whatsapp_message' => 'Halo Invalid',
            'cta_label' => 'Chat via WhatsApp',
            'theme' => 'mint',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('dashboard.links.store'), [
                'label' => 'Website',
                'description' => 'Link rusak',
                'url' => 'www.v',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('url');

        $this->assertDatabaseMissing('landing_page_links', [
            'label' => 'Website',
            'url' => 'https://www.v',
        ]);
    }

    public function test_analytics_summary_returns_detailed_timeline_deltas_and_sources(): void
    {
        Carbon::setTestNow('2026-04-17 12:00:00');

        $user = User::query()->create([
            'name' => 'Insight Studio',
            'email' => 'insight@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $landingPage = $user->landingPage()->create([
            'slug' => 'insight-studio',
            'title' => 'Insight Studio',
            'headline' => 'CTA tracking',
            'bio' => 'Dashboard analytics test',
            'whatsapp_number' => '628444444444',
            'whatsapp_message' => 'Halo Insight',
            'cta_label' => 'Chat via WhatsApp',
            'theme' => 'sunset',
            'is_active' => true,
        ]);

        $this->createEvent($landingPage->id, LandingPageEvent::PAGE_VIEW, 'sess-a', 'https://instagram.com/insight', now()->subDays(1));
        $this->createEvent($landingPage->id, LandingPageEvent::PAGE_VIEW, 'sess-b', 'https://instagram.com/insight', now()->subDays(1));
        $this->createEvent($landingPage->id, LandingPageEvent::CTA_CLICK, 'sess-a', 'https://instagram.com/insight', now()->subDays(1));
        $this->createEvent($landingPage->id, LandingPageEvent::LINK_CLICK, 'sess-b', 'https://instagram.com/insight', now()->subDays(1));
        $this->createEvent($landingPage->id, LandingPageEvent::PAGE_VIEW, 'sess-c', 'https://tiktok.com/@insight', now()->subDays(3));
        $this->createEvent($landingPage->id, LandingPageEvent::CTA_CLICK, 'sess-c', 'https://tiktok.com/@insight', now()->subDays(3));

        $this->createEvent($landingPage->id, LandingPageEvent::PAGE_VIEW, 'prev-a', 'https://facebook.com/insight', now()->subDays(8));
        $this->createEvent($landingPage->id, LandingPageEvent::CTA_CLICK, 'prev-a', 'https://facebook.com/insight', now()->subDays(8));

        $analytics = app(LandingPageAnalytics::class)->summarize($landingPage, 7);

        $this->assertSame('7 hari terakhir', $analytics['period_label']);
        $this->assertCount(7, $analytics['timeline']);
        $this->assertSame(3, $analytics['views']);
        $this->assertSame(3, $analytics['unique_visitors']);
        $this->assertSame(2, $analytics['cta_clicks']);
        $this->assertSame(1, $analytics['link_clicks']);
        $this->assertSame(66.7, $analytics['conversion_rate']);
        $this->assertSame('instagram.com', $analytics['top_referrers']->first()['label']);
        $this->assertSame(66.7, $analytics['top_referrers']->first()['share']);
        $this->assertSame(200.0, $analytics['metrics']['views']['delta']);
        $this->assertSame('up', $analytics['metrics']['views']['direction']);
        $this->assertSame(100.0, $analytics['funnel'][0]['conversion_from_views']);
        $this->assertSame(66.7, $analytics['funnel'][2]['conversion_from_views']);

        Carbon::setTestNow();
    }

    public function test_dashboard_renders_detailed_analytics_for_selected_range(): void
    {
        Carbon::setTestNow('2026-04-17 12:00:00');

        $user = User::query()->create([
            'name' => 'Dashboard Studio',
            'email' => 'dashboard@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $landingPage = $user->landingPage()->create([
            'slug' => 'dashboard-studio',
            'title' => 'Dashboard Studio',
            'headline' => 'Visual analytics',
            'bio' => 'Testing dashboard render',
            'whatsapp_number' => '628555555555',
            'whatsapp_message' => 'Halo Dashboard',
            'cta_label' => 'Chat via WhatsApp',
            'theme' => 'mint',
            'is_active' => true,
        ]);

        $this->createEvent($landingPage->id, LandingPageEvent::PAGE_VIEW, 'sess-dashboard', 'https://instagram.com/dashboard', now()->subDays(2));
        $this->createEvent($landingPage->id, LandingPageEvent::CTA_CLICK, 'sess-dashboard', 'https://instagram.com/dashboard', now()->subDays(2));

        $this->actingAs($user)
            ->get(route('dashboard', ['range' => 30]))
            ->assertOk()
            ->assertSee('Analytics landing page yang lebih detail')
            ->assertSee('30 Hari')
            ->assertSee('Trend Performance')
            ->assertSee('Traffic Source')
            ->assertSee('Conversion Funnel');

        Carbon::setTestNow();
    }

    public function test_dashboard_accepts_custom_date_range_filter(): void
    {
        Carbon::setTestNow('2026-04-17 12:00:00');

        $user = User::query()->create([
            'name' => 'Custom Range Studio',
            'email' => 'custom-range@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $landingPage = $user->landingPage()->create([
            'slug' => 'custom-range-studio',
            'title' => 'Custom Range Studio',
            'headline' => 'Date filtered analytics',
            'bio' => 'Testing custom date filter',
            'whatsapp_number' => '628666666666',
            'whatsapp_message' => 'Halo Custom',
            'cta_label' => 'Chat via WhatsApp',
            'theme' => 'night',
            'is_active' => true,
        ]);

        $this->createEvent($landingPage->id, LandingPageEvent::PAGE_VIEW, 'custom-1', 'https://instagram.com/custom', Carbon::parse('2026-04-10 10:00:00'));
        $this->createEvent($landingPage->id, LandingPageEvent::CTA_CLICK, 'custom-1', 'https://instagram.com/custom', Carbon::parse('2026-04-10 10:05:00'));
        $this->createEvent($landingPage->id, LandingPageEvent::PAGE_VIEW, 'custom-2', 'https://tiktok.com/@custom', Carbon::parse('2026-04-12 12:00:00'));
        $this->createEvent($landingPage->id, LandingPageEvent::PAGE_VIEW, 'outside-range', 'https://facebook.com/custom', Carbon::parse('2026-04-16 12:00:00'));

        $this->actingAs($user)
            ->get(route('dashboard', [
                'start_date' => '2026-04-09',
                'end_date' => '2026-04-12',
            ]))
            ->assertOk()
            ->assertSee('Custom Date Active')
            ->assertSee('09 Apr 2026 - 12 Apr 2026')
            ->assertSee('Custom range')
            ->assertSee('Komparasi: 05 Apr 2026 - 08 Apr 2026');

        Carbon::setTestNow();
    }

    public function test_user_can_export_analytics_as_csv(): void
    {
        Carbon::setTestNow('2026-04-17 12:00:00');

        $user = User::query()->create([
            'name' => 'Export Ready',
            'email' => 'export@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $landingPage = $user->landingPage()->create([
            'slug' => 'export-ready',
            'title' => 'Export Ready',
            'headline' => 'CSV analytics',
            'bio' => 'Testing analytics export.',
            'whatsapp_number' => '628888888888',
            'whatsapp_message' => 'Halo Export',
            'cta_label' => 'Chat via WhatsApp',
            'theme' => 'mint',
            'is_active' => true,
        ]);

        $this->createEvent($landingPage->id, LandingPageEvent::PAGE_VIEW, 'sess-export', 'https://instagram.com/export', now()->subDay());
        $this->createEvent($landingPage->id, LandingPageEvent::CTA_CLICK, 'sess-export', 'https://instagram.com/export', now()->subDay());

        $response = $this->actingAs($user)
            ->get(route('dashboard.analytics.export', ['range' => 7]));

        $response
            ->assertOk()
            ->assertDownload('analytics-export-ready-2026-04-17.csv');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Ringkasan Analytics', $content);
        $this->assertStringContainsString('Export Ready', $content);
        $this->assertStringContainsString('"Page View",1,0,Baru', $content);
        $this->assertStringContainsString('Traffic Source', $content);
        $this->assertStringContainsString('instagram.com,1,100%', $content);

        Carbon::setTestNow();
    }

    public function test_dashboard_can_filter_by_source_and_export_excel(): void
    {
        Carbon::setTestNow('2026-04-17 12:00:00');

        $user = User::query()->create([
            'name' => 'Source Filter',
            'email' => 'source-filter@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $landingPage = $user->landingPage()->create([
            'slug' => 'source-filter',
            'title' => 'Source Filter',
            'headline' => 'Filter analytics by source',
            'bio' => 'Testing per-source analytics.',
            'whatsapp_number' => '628878787878',
            'whatsapp_message' => 'Halo Source',
            'cta_label' => 'Chat via WhatsApp',
            'theme' => 'night',
            'is_active' => true,
        ]);

        $this->createEvent($landingPage->id, LandingPageEvent::PAGE_VIEW, 'insta-1', 'https://instagram.com/source', now()->subDay());
        $this->createEvent($landingPage->id, LandingPageEvent::CTA_CLICK, 'insta-1', 'https://instagram.com/source', now()->subDay());
        $this->createEvent($landingPage->id, LandingPageEvent::PAGE_VIEW, 'tiktok-1', 'https://tiktok.com/@source', now()->subDay());
        $this->createEvent($landingPage->id, LandingPageEvent::LINK_CLICK, 'tiktok-1', 'https://tiktok.com/@source', now()->subDay());

        $response = $this->actingAs($user)
            ->get(route('dashboard', ['range' => 7, 'source' => 'instagram.com']))
            ->assertOk()
            ->assertSee('Source: instagram.com')
            ->assertSee('instagram.com');

        $response->assertViewHas('analytics', function (array $analytics) {
            return $analytics['source_filter'] === 'instagram.com'
                && $analytics['views'] === 1
                && $analytics['cta_clicks'] === 1
                && $analytics['link_clicks'] === 0
                && $analytics['top_referrers']->first()['label'] === 'instagram.com';
        });

        $response = $this->actingAs($user)
            ->get(route('dashboard.analytics.export', [
                'range' => 7,
                'source' => 'instagram.com',
                'format' => 'excel',
            ]));

        $response
            ->assertOk()
            ->assertDownload('analytics-source-filter-2026-04-17.xls')
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Source Filter', $content);
        $this->assertStringContainsString('instagram.com', $content);
        $this->assertStringNotContainsString('tiktok.com', $content);

        Carbon::setTestNow();
    }

    public function test_custom_domain_routes_render_public_page_and_track_connection(): void
    {
        config([
            'app.url' => 'https://linkpulse.test',
            'landing.custom_domain_scheme' => 'https',
            'landing.custom_domain_target' => 'linkpulse.test',
            'landing.app_hosts' => ['linkpulse.test', 'localhost', '127.0.0.1'],
        ]);

        $user = User::query()->create([
            'name' => 'Custom Domain Brand',
            'email' => 'custom-domain@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $landingPage = $user->landingPage()->create([
            'slug' => 'custom-domain-brand',
            'title' => 'Custom Domain Brand',
            'headline' => 'Landing page by host',
            'bio' => 'Testing custom domain route resolution.',
            'whatsapp_number' => '628999999999',
            'whatsapp_message' => 'Halo Custom Domain',
            'cta_label' => 'Chat via WhatsApp',
            'theme' => 'night',
            'is_active' => true,
            'custom_domain' => 'promo.brand.test',
        ]);

        $link = $landingPage->links()->create([
            'label' => 'Promo Domain',
            'description' => 'Lihat promo di custom domain',
            'url' => 'https://example.com/domain-promo',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get('http://promo.brand.test/')
            ->assertOk()
            ->assertSee('Custom Domain Brand');

        $landingPage->refresh();

        $this->assertNotNull($landingPage->custom_domain_connected_at);

        $this->get('http://promo.brand.test/cta')
            ->assertRedirect($landingPage->whatsappUrl());

        $this->get('http://promo.brand.test/links/'.$link->id)
            ->assertRedirect('https://example.com/domain-promo');

        $this->assertDatabaseHas('landing_page_events', [
            'landing_page_id' => $landingPage->id,
            'event_type' => LandingPageEvent::PAGE_VIEW,
        ]);

        $this->assertDatabaseHas('landing_page_events', [
            'landing_page_id' => $landingPage->id,
            'event_type' => LandingPageEvent::CTA_CLICK,
        ]);

        $this->assertDatabaseHas('landing_page_events', [
            'landing_page_id' => $landingPage->id,
            'landing_page_link_id' => $link->id,
            'event_type' => LandingPageEvent::LINK_CLICK,
        ]);
    }

    public function test_custom_domain_status_is_auto_verified_and_can_be_rechecked(): void
    {
        Carbon::setTestNow('2026-04-17 15:30:00');

        $verifier = new class implements CustomDomainVerifier {
            public int $syncCalls = 0;

            public function sync(LandingPage $landingPage): LandingPage
            {
                $this->syncCalls++;

                $landingPage->forceFill([
                    'custom_domain_dns_status' => 'verified',
                    'custom_domain_dns_target' => 'linkpulse.test',
                    'custom_domain_dns_checked_at' => now(),
                    'custom_domain_dns_message' => 'CNAME domain sudah mengarah ke target aplikasi.',
                    'custom_domain_ssl_status' => 'valid',
                    'custom_domain_ssl_issuer' => "Let's Encrypt",
                    'custom_domain_ssl_expires_at' => now()->addDays(30),
                    'custom_domain_ssl_checked_at' => now(),
                    'custom_domain_ssl_message' => 'Sertifikat SSL aktif dan dapat digunakan.',
                    'custom_domain_connected_at' => now(),
                ])->save();

                return $landingPage->refresh();
            }

            public function clear(LandingPage $landingPage): LandingPage
            {
                $landingPage->forceFill([
                    'custom_domain_dns_status' => null,
                    'custom_domain_dns_target' => null,
                    'custom_domain_dns_checked_at' => null,
                    'custom_domain_dns_message' => null,
                    'custom_domain_ssl_status' => null,
                    'custom_domain_ssl_issuer' => null,
                    'custom_domain_ssl_expires_at' => null,
                    'custom_domain_ssl_checked_at' => null,
                    'custom_domain_ssl_message' => null,
                    'custom_domain_connected_at' => null,
                ])->save();

                return $landingPage->refresh();
            }
        };

        $this->app->instance(CustomDomainVerifier::class, $verifier);

        $user = User::query()->create([
            'name' => 'Verifier Brand',
            'email' => 'verifier@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $landingPage = $user->landingPage()->create([
            'slug' => 'verifier-brand',
            'title' => 'Verifier Brand',
            'headline' => 'Status checker',
            'bio' => 'Testing DNS dan SSL checker.',
            'whatsapp_number' => '6281010101010',
            'whatsapp_message' => 'Halo Verifier',
            'cta_label' => 'Chat via WhatsApp',
            'theme' => 'sunset',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('dashboard.page.update'), [
                'slug' => 'verifier-brand',
                'title' => 'Verifier Brand',
                'headline' => 'Status checker',
                'bio' => 'Testing DNS dan SSL checker.',
                'avatar_url' => '',
                'whatsapp_number' => '6281010101010',
                'whatsapp_message' => 'Halo Verifier',
                'cta_label' => 'Chat via WhatsApp',
                'theme' => 'sunset',
                'custom_domain' => 'promo.verifier.test',
                'is_active' => '1',
            ])
            ->assertRedirect(route('dashboard'));

        $landingPage->refresh();

        $this->assertSame('promo.verifier.test', $landingPage->custom_domain);
        $this->assertSame('verified', $landingPage->custom_domain_dns_status);
        $this->assertSame('valid', $landingPage->custom_domain_ssl_status);
        $this->assertSame("Let's Encrypt", $landingPage->custom_domain_ssl_issuer);
        $this->assertSame(1, $verifier->syncCalls);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('DNS Status')
            ->assertSee('SSL Status')
            ->assertSee("Let's Encrypt");

        $this->actingAs($user)
            ->post(route('dashboard.domain.verify'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(2, $verifier->syncCalls);

        Carbon::setTestNow();
    }

    protected function createEvent(
        int $landingPageId,
        string $eventType,
        string $sessionId,
        ?string $referrer,
        Carbon $createdAt,
    ): void {
        LandingPageEvent::unguarded(function () use ($landingPageId, $eventType, $sessionId, $referrer, $createdAt): void {
            LandingPageEvent::query()->create([
                'landing_page_id' => $landingPageId,
                'event_type' => $eventType,
                'session_id' => $sessionId,
                'referrer' => $referrer,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        });
    }
}
