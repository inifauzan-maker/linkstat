<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\LandingPageLink;
use App\Support\LandingPageTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PublicLandingPageController extends Controller
{
    public function __construct(
        protected LandingPageTracker $tracker,
    ) {
    }

    public function root(Request $request): View|RedirectResponse
    {
        if (LandingPage::isAppHost($request->getHost())) {
            if (Auth::check()) {
                return redirect()->route(
                    $request->user()?->isAdmin() ? 'admin.users.index' : 'dashboard'
                );
            }

            return view('auth.login');
        }

        $landingPage = $this->resolveCustomDomainLandingPage($request);

        return $this->renderLandingPage($request, $landingPage, true);
    }

    public function show(Request $request, LandingPage $landingPage): View
    {
        return $this->renderLandingPage($request, $landingPage, false);
    }

    public function cta(Request $request, LandingPage $landingPage): RedirectResponse
    {
        return $this->redirectToCta($request, $landingPage);
    }

    public function link(Request $request, LandingPage $landingPage, LandingPageLink $link): RedirectResponse
    {
        return $this->redirectToLink($request, $landingPage, $link);
    }

    public function domainCta(Request $request): RedirectResponse
    {
        return $this->redirectToCta($request, $this->resolveCustomDomainLandingPage($request), true);
    }

    public function domainLink(Request $request, LandingPageLink $link): RedirectResponse
    {
        return $this->redirectToLink($request, $this->resolveCustomDomainLandingPage($request), $link, true);
    }

    protected function renderLandingPage(Request $request, LandingPage $landingPage, bool $usingCustomDomain): View
    {
        $this->abortUnlessPubliclyAvailable($landingPage);

        if ($usingCustomDomain) {
            $landingPage->markCustomDomainConnected($request->getHost());
        }

        $landingPage->load('activeLinks');
        $this->tracker->recordPageView($landingPage, $request);

        return view('public.show', [
            'landingPage' => $landingPage,
            'theme' => $landingPage->themeConfig(),
            'avatarUrl' => $landingPage->avatarImageUrl(),
            'usingCustomDomain' => $usingCustomDomain,
        ]);
    }

    protected function redirectToCta(
        Request $request,
        LandingPage $landingPage,
        bool $usingCustomDomain = false,
    ): RedirectResponse {
        $this->abortUnlessPubliclyAvailable($landingPage);

        if ($usingCustomDomain) {
            $landingPage->markCustomDomainConnected($request->getHost());
        }

        $this->tracker->recordCtaClick($landingPage, $request);

        return redirect()->away($landingPage->whatsappUrl());
    }

    protected function redirectToLink(
        Request $request,
        LandingPage $landingPage,
        LandingPageLink $link,
        bool $usingCustomDomain = false,
    ): RedirectResponse {
        $this->abortUnlessPubliclyAvailable($landingPage);
        abort_if($link->landing_page_id !== $landingPage->id || ! $link->is_active, 404);

        if ($usingCustomDomain) {
            $landingPage->markCustomDomainConnected($request->getHost());
        }

        $this->tracker->recordLinkClick($landingPage, $link, $request);

        return redirect()->away($link->url);
    }

    protected function resolveCustomDomainLandingPage(Request $request): LandingPage
    {
        $landingPage = LandingPage::resolveByHost($request->getHost());

        abort_unless($landingPage instanceof LandingPage, 404);

        return $landingPage;
    }

    protected function abortUnlessPubliclyAvailable(LandingPage $landingPage): void
    {
        abort_unless(
            $landingPage->is_active && $landingPage->user()->where('is_active', true)->exists(),
            404
        );
    }
}
