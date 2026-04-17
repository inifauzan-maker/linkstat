<?php

namespace App\Support;

use App\Models\LandingPage;
use App\Models\LandingPageEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LandingPageAnalytics
{
    public function summarize(LandingPage $landingPage, int $days = 7, ?string $source = null): array
    {
        $days = max(1, $days);
        $currentStart = now()->subDays($days - 1)->startOfDay();
        $currentEnd = now()->endOfDay();

        return $this->summarizeBetween($landingPage, $currentStart, $currentEnd, false, $source);
    }

    public function summarizeBetween(
        LandingPage $landingPage,
        Carbon $currentStart,
        Carbon $currentEnd,
        bool $isCustomRange = true,
        ?string $source = null,
    ): array {
        $currentStart = $currentStart->copy()->startOfDay();
        $currentEnd = $currentEnd->copy()->endOfDay();
        $days = $currentStart->copy()->startOfDay()->diffInDays($currentEnd->copy()->startOfDay()) + 1;
        $previousStart = (clone $currentStart)->subDays($days);
        $previousEnd = (clone $currentStart)->subSecond();
        $normalizedSource = $this->normalizeSource($source);

        $currentEvents = $landingPage->events()
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->get([
                'event_type',
                'landing_page_link_id',
                'session_id',
                'referrer',
                'created_at',
            ]);

        $previousEvents = $landingPage->events()
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->get([
                'event_type',
                'landing_page_link_id',
                'session_id',
                'referrer',
                'created_at',
            ]);

        $currentEvents = $this->filterEventsBySource($currentEvents, $normalizedSource);
        $previousEvents = $this->filterEventsBySource($previousEvents, $normalizedSource);

        $current = $this->buildPeriodSummary($currentEvents, $currentStart, $days);
        $previous = $this->buildPeriodSummary($previousEvents, $previousStart, $days);
        $topLinks = $this->buildTopLinks($landingPage, $currentEvents, $current['link_clicks']);

        return [
            'is_custom_range' => $isCustomRange,
            'current_start' => $currentStart->toDateString(),
            'current_end' => $currentEnd->toDateString(),
            'period_days' => $days,
            'period_label' => $isCustomRange
                ? $currentStart->format('d M Y').' - '.$currentEnd->format('d M Y')
                : $days.' hari terakhir',
            'period_caption' => $isCustomRange ? 'Custom range' : 'Preset range',
            'previous_period_label' => $isCustomRange
                ? $previousStart->format('d M Y').' - '.$previousEnd->format('d M Y')
                : $days.' hari sebelumnya',
            'source_filter' => $normalizedSource,
            'source_label' => $normalizedSource ?? 'Semua source',
            'views' => $current['views'],
            'unique_visitors' => $current['unique_visitors'],
            'cta_clicks' => $current['cta_clicks'],
            'link_clicks' => $current['link_clicks'],
            'conversion_rate' => $current['conversion_rate'],
            'daily' => $current['daily'],
            'timeline' => $current['daily'],
            'averages' => $current['averages'],
            'best_day' => $current['best_day'],
            'best_conversion_day' => $current['best_conversion_day'],
            'top_referrers' => $current['top_referrers'],
            'funnel' => $current['funnel'],
            'metrics' => [
                'views' => $this->buildMetric($current['views'], $previous['views']),
                'unique_visitors' => $this->buildMetric($current['unique_visitors'], $previous['unique_visitors']),
                'cta_clicks' => $this->buildMetric($current['cta_clicks'], $previous['cta_clicks']),
                'link_clicks' => $this->buildMetric($current['link_clicks'], $previous['link_clicks']),
                'conversion_rate' => $this->buildMetric($current['conversion_rate'], $previous['conversion_rate']),
            ],
            'top_links' => $topLinks,
        ];
    }

    public function availableSourcesForLandingPage(LandingPage $landingPage): Collection
    {
        return $this->availableSourcesForLandingPages([$landingPage->getKey()]);
    }

    public function availableSourcesForLandingPages(array|Collection $landingPageIds): Collection
    {
        $ids = collect($landingPageIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $events = LandingPageEvent::query()
            ->whereIn('landing_page_id', $ids->all())
            ->where('event_type', LandingPageEvent::PAGE_VIEW)
            ->get(['referrer']);

        return $this->countSources($events);
    }

    protected function buildPeriodSummary(Collection $events, Carbon $startDate, int $days): array
    {
        $eventsByDay = $events->groupBy(
            fn (LandingPageEvent $event) => $event->created_at->toDateString()
        );

        $daily = Collection::times($days, function (int $offset) use ($days, $eventsByDay, $startDate) {
            $date = (clone $startDate)->addDays($offset - 1);
            $dayKey = $date->toDateString();
            $items = $eventsByDay->get($dayKey, collect());

            $pageViews = $items->where('event_type', LandingPageEvent::PAGE_VIEW);
            $views = $pageViews->count();
            $uniqueVisitors = $pageViews
                ->pluck('session_id')
                ->filter()
                ->unique()
                ->count();
            $ctaClicks = $items->where('event_type', LandingPageEvent::CTA_CLICK)->count();
            $linkClicks = $items->where('event_type', LandingPageEvent::LINK_CLICK)->count();

            return [
                'date' => $dayKey,
                'label' => $date->format('d M'),
                'short_label' => $date->format($days > 14 ? 'd M' : 'D'),
                'views' => $views,
                'unique_visitors' => $uniqueVisitors,
                'cta_clicks' => $ctaClicks,
                'link_clicks' => $linkClicks,
                'cta_rate' => $views > 0 ? round(($ctaClicks / $views) * 100, 1) : 0,
            ];
        });

        $views = $events->where('event_type', LandingPageEvent::PAGE_VIEW)->count();
        $uniqueVisitors = $events
            ->where('event_type', LandingPageEvent::PAGE_VIEW)
            ->pluck('session_id')
            ->filter()
            ->unique()
            ->count();
        $ctaClicks = $events->where('event_type', LandingPageEvent::CTA_CLICK)->count();
        $linkClicks = $events->where('event_type', LandingPageEvent::LINK_CLICK)->count();
        $conversionRate = $views > 0 ? round(($ctaClicks / $views) * 100, 1) : 0;

        $topReferrers = $this->countSources(
            $events->where('event_type', LandingPageEvent::PAGE_VIEW)
        )->take(5)->values();

        return [
            'views' => $views,
            'unique_visitors' => $uniqueVisitors,
            'cta_clicks' => $ctaClicks,
            'link_clicks' => $linkClicks,
            'conversion_rate' => $conversionRate,
            'daily' => $daily,
            'averages' => [
                'views_per_day' => round($views / $days, 1),
                'cta_clicks_per_day' => round($ctaClicks / $days, 1),
                'link_clicks_per_day' => round($linkClicks / $days, 1),
            ],
            'best_day' => $views > 0 ? $daily->sortByDesc('views')->first() : null,
            'best_conversion_day' => $daily->where('views', '>', 0)->sortByDesc('cta_rate')->first(),
            'top_referrers' => $topReferrers,
            'funnel' => [
                [
                    'label' => 'Page View',
                    'total' => $views,
                    'conversion_from_views' => $views > 0 ? 100.0 : 0.0,
                ],
                [
                    'label' => 'Visitor Unik',
                    'total' => $uniqueVisitors,
                    'conversion_from_views' => $views > 0 ? round(($uniqueVisitors / $views) * 100, 1) : 0.0,
                ],
                [
                    'label' => 'CTA Click',
                    'total' => $ctaClicks,
                    'conversion_from_views' => $views > 0 ? round(($ctaClicks / $views) * 100, 1) : 0.0,
                ],
                [
                    'label' => 'Link Click',
                    'total' => $linkClicks,
                    'conversion_from_views' => $views > 0 ? round(($linkClicks / $views) * 100, 1) : 0.0,
                ],
            ],
        ];
    }

    protected function buildTopLinks(LandingPage $landingPage, Collection $events, int $filteredLinkClicks): Collection
    {
        $links = $landingPage->relationLoaded('links')
            ? $landingPage->links
            : $landingPage->links()->get();

        return $links
            ->map(function ($link) use ($events, $filteredLinkClicks) {
                $clicksCount = $events
                    ->where('event_type', LandingPageEvent::LINK_CLICK)
                    ->where('landing_page_link_id', $link->getKey())
                    ->count();

                $link->clicks_count = $clicksCount;
                $link->clicks_share = $filteredLinkClicks > 0
                    ? round(($clicksCount / $filteredLinkClicks) * 100, 1)
                    : 0;

                return $link;
            })
            ->sort(function ($left, $right) {
                $byClicks = $right->clicks_count <=> $left->clicks_count;

                if ($byClicks !== 0) {
                    return $byClicks;
                }

                $bySortOrder = $left->sort_order <=> $right->sort_order;

                if ($bySortOrder !== 0) {
                    return $bySortOrder;
                }

                return $left->getKey() <=> $right->getKey();
            })
            ->values();
    }

    protected function buildMetric(float|int $current, float|int $previous): array
    {
        $delta = $this->percentageDelta($current, $previous);
        $direction = match (true) {
            $delta === null => 'new',
            $delta > 0 => 'up',
            $delta < 0 => 'down',
            default => 'flat',
        };

        return [
            'value' => $current,
            'previous' => $previous,
            'delta' => $delta,
            'direction' => $direction,
        ];
    }

    protected function percentageDelta(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function normalizeSource(?string $referrer): ?string
    {
        if (! filled($referrer)) {
            return null;
        }

        $value = trim(Str::lower((string) $referrer));

        if ($value === '') {
            return null;
        }

        if (Str::contains($value, '://')) {
            $host = parse_url($value, PHP_URL_HOST);

            if (! is_string($host) || $host === '') {
                return null;
            }

            return Str::replaceStart('www.', '', Str::lower($host));
        }

        return Str::replaceStart('www.', '', $value);
    }

    protected function filterEventsBySource(Collection $events, ?string $source): Collection
    {
        if ($source === null) {
            return $events;
        }

        return $events->filter(
            fn (LandingPageEvent $event) => $this->normalizeReferrer($event->referrer) === $source
        )->values();
    }

    protected function countSources(Collection $events): Collection
    {
        $views = $events->count();

        return $events
            ->map(fn ($event) => $this->normalizeReferrer($event->referrer))
            ->countBy()
            ->sortDesc()
            ->map(fn (int $count, string $label) => [
                'label' => $label,
                'count' => $count,
                'share' => $views > 0 ? round(($count / $views) * 100, 1) : 0,
            ])
            ->values();
    }

    protected function normalizeReferrer(?string $referrer): string
    {
        $normalized = $this->normalizeSource($referrer);

        return $normalized ?? 'direct / unknown';
    }
}
