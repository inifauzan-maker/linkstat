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

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

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

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

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
}
