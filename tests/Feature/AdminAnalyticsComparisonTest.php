<?php

namespace Tests\Feature;

use App\Models\LandingPageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAnalyticsComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_compare_multiple_landing_pages_with_source_filter(): void
    {
        Carbon::setTestNow('2026-04-17 12:00:00');

        $admin = User::factory()->admin()->create();

        $instagramUser = User::query()->create([
            'name' => 'Instagram Brand',
            'email' => 'instagram-brand@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $instagramPage = $instagramUser->landingPage()->create([
            'slug' => 'instagram-brand',
            'title' => 'Instagram Brand',
            'headline' => 'Page A',
            'bio' => 'Landing page A.',
            'whatsapp_number' => '628111111111',
            'whatsapp_message' => 'Halo A',
            'cta_label' => 'Chat',
            'theme' => 'sunset',
            'is_active' => true,
        ]);

        $tiktokUser = User::query()->create([
            'name' => 'TikTok Brand',
            'email' => 'tiktok-brand@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $tiktokPage = $tiktokUser->landingPage()->create([
            'slug' => 'tiktok-brand',
            'title' => 'TikTok Brand',
            'headline' => 'Page B',
            'bio' => 'Landing page B.',
            'whatsapp_number' => '628222222222',
            'whatsapp_message' => 'Halo B',
            'cta_label' => 'Chat',
            'theme' => 'mint',
            'is_active' => true,
        ]);

        $this->createEvent($instagramPage->id, LandingPageEvent::PAGE_VIEW, 'ig-1', 'https://instagram.com/brand', now()->subDay());
        $this->createEvent($instagramPage->id, LandingPageEvent::CTA_CLICK, 'ig-1', 'https://instagram.com/brand', now()->subDay());
        $this->createEvent($tiktokPage->id, LandingPageEvent::PAGE_VIEW, 'tt-1', 'https://tiktok.com/@brand', now()->subDay());
        $this->createEvent($tiktokPage->id, LandingPageEvent::CTA_CLICK, 'tt-1', 'https://tiktok.com/@brand', now()->subDay());

        $response = $this->actingAs($admin)
            ->get(route('admin.analytics.index', [
                'range' => 7,
                'source' => 'instagram.com',
            ]));

        $response
            ->assertOk()
            ->assertSee('Perbandingan beberapa landing page')
            ->assertSee('Source: instagram.com')
            ->assertSee('Instagram Brand')
            ->assertSee('TikTok Brand');

        $response->assertViewHas('comparisons', function ($comparisons) {
            $instagram = $comparisons->first(fn (array $row) => $row['landing_page']->title === 'Instagram Brand');
            $tiktok = $comparisons->first(fn (array $row) => $row['landing_page']->title === 'TikTok Brand');

            if (! $instagram || ! $tiktok) {
                return false;
            }

            return $instagram['summary']['views'] === 1
                && $instagram['summary']['cta_clicks'] === 1
                && $instagram['top_source'] === 'instagram.com'
                && $tiktok['summary']['views'] === 0
                && $tiktok['summary']['cta_clicks'] === 0;
        });

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
