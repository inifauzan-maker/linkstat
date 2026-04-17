<?php

namespace App\Http\Controllers;

use App\Models\LandingPageLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LandingPageLinkController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $landingPage = $request->user()->landingPage()->firstOrFail();

        $validated = $this->validateLinkPayload($request);

        $landingPage->links()->create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? ($landingPage->links()->max('sort_order') + 1),
            'is_active' => true,
        ]);

        return redirect()->route('dashboard')->with('status', 'Link baru berhasil ditambahkan.');
    }

    public function update(Request $request, LandingPageLink $link): RedirectResponse
    {
        $landingPage = $request->user()->landingPage()->firstOrFail();
        abort_if($link->landing_page_id !== $landingPage->id, 404);

        $validated = $this->validateLinkPayload($request);

        $link->update([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? $link->sort_order,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('dashboard')->with('status', 'Link berhasil diperbarui.');
    }

    public function destroy(Request $request, LandingPageLink $link): RedirectResponse
    {
        $landingPage = $request->user()->landingPage()->firstOrFail();
        abort_if($link->landing_page_id !== $landingPage->id, 404);

        $link->delete();

        return redirect()->route('dashboard')->with('status', 'Link berhasil dihapus.');
    }

    protected function validateLinkPayload(Request $request): array
    {
        $normalizedUrl = LandingPageLink::normalizeExternalUrl($request->input('url'));

        $request->merge([
            'url' => $normalizedUrl,
        ]);

        return $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:120'],
            'url' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! LandingPageLink::isValidExternalUrl(is_string($value) ? $value : null)) {
                        $fail('Masukkan URL yang valid, misalnya https://example.com atau example.com.');
                    }
                },
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
