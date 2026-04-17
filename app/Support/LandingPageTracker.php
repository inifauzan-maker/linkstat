<?php

namespace App\Support;

use App\Models\LandingPage;
use App\Models\LandingPageEvent;
use App\Models\LandingPageLink;
use Illuminate\Http\Request;

class LandingPageTracker
{
    public function recordPageView(LandingPage $landingPage, Request $request): void
    {
        $this->record($landingPage, $request, LandingPageEvent::PAGE_VIEW);
    }

    public function recordCtaClick(LandingPage $landingPage, Request $request): void
    {
        $this->record(
            landingPage: $landingPage,
            request: $request,
            eventType: LandingPageEvent::CTA_CLICK,
            clickedUrl: $landingPage->whatsappUrl(),
        );
    }

    public function recordLinkClick(LandingPage $landingPage, LandingPageLink $link, Request $request): void
    {
        $this->record(
            landingPage: $landingPage,
            request: $request,
            eventType: LandingPageEvent::LINK_CLICK,
            link: $link,
            clickedUrl: $link->url,
        );
    }

    protected function record(
        LandingPage $landingPage,
        Request $request,
        string $eventType,
        ?LandingPageLink $link = null,
        ?string $clickedUrl = null,
    ): void {
        LandingPageEvent::query()->create([
            'landing_page_id' => $landingPage->id,
            'landing_page_link_id' => $link?->id,
            'event_type' => $eventType,
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->headers->get('referer'),
            'clicked_url' => $clickedUrl,
        ]);
    }
}
