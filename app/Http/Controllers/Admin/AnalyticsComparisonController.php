<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Support\LandingPageAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalyticsComparisonController extends Controller
{
    public function __construct(
        protected LandingPageAnalytics $analytics,
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
            'q' => ['nullable', 'string', 'max:120'],
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

        $landingPages = LandingPage::query()
            ->with('user')
            ->when(filled($validated['q'] ?? null), function ($query) use ($validated) {
                $search = trim((string) $validated['q']);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('is_active')
            ->latest()
            ->get();

        $availableSources = $this->analytics->availableSourcesForLandingPages($landingPages->pluck('id'));

        $comparisons = $landingPages->map(function (LandingPage $landingPage) use (
            $isCustomRange,
            $customStartDate,
            $customEndDate,
            $range,
            $selectedSource,
        ) {
            $summary = $isCustomRange
                ? $this->analytics->summarizeBetween($landingPage, $customStartDate, $customEndDate, true, $selectedSource)
                : $this->analytics->summarize($landingPage, $range, $selectedSource);

            return [
                'landing_page' => $landingPage,
                'summary' => $summary,
                'public_url' => $landingPage->preferredPublicUrl(),
                'top_source' => $summary['top_referrers']->first()['label'] ?? 'Tidak ada data',
            ];
        })->sortByDesc(fn (array $row) => $row['summary']['views'])->values();

        $firstComparison = $comparisons->first();
        $selectedSource = $firstComparison['summary']['source_filter']
            ?? $this->analytics->normalizeSource($selectedSource);
        $periodLabel = $firstComparison['summary']['period_label'] ?? ($isCustomRange
            ? $customStartDate?->format('d M Y').' - '.$customEndDate?->format('d M Y')
            : $range.' hari terakhir');

        $aggregate = [
            'total_pages' => $comparisons->count(),
            'total_views' => $comparisons->sum(fn (array $row) => $row['summary']['views']),
            'total_cta_clicks' => $comparisons->sum(fn (array $row) => $row['summary']['cta_clicks']),
            'average_cta_rate' => round((float) $comparisons->avg(fn (array $row) => $row['summary']['conversion_rate']), 1),
            'top_page' => $comparisons->sortByDesc(fn (array $row) => $row['summary']['views'])->first(),
            'best_conversion_page' => $comparisons
                ->filter(fn (array $row) => $row['summary']['views'] > 0)
                ->sortByDesc(fn (array $row) => $row['summary']['conversion_rate'])
                ->first(),
        ];

        return view('admin.analytics.index', [
            'comparisons' => $comparisons,
            'filters' => $validated,
            'rangeOptions' => $rangeOptions,
            'selectedRange' => $isCustomRange ? null : $range,
            'selectedSource' => $selectedSource,
            'availableSources' => $availableSources,
            'customStartDate' => $customStartDate?->toDateString(),
            'customEndDate' => $customEndDate?->toDateString(),
            'isCustomRange' => $isCustomRange,
            'periodLabel' => $periodLabel,
            'aggregate' => $aggregate,
        ]);
    }
}
