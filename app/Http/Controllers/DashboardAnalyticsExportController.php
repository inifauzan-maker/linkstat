<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Support\LandingPageAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardAnalyticsExportController extends Controller
{
    public function __construct(
        protected LandingPageAnalytics $analytics,
    ) {
    }

    public function __invoke(Request $request): StreamedResponse
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
            'format' => ['nullable', Rule::in(['csv', 'excel'])],
        ]);

        $range = (int) ($validated['range'] ?? 7);
        $format = $validated['format'] ?? 'csv';
        $selectedSource = $validated['source'] ?? null;
        $customStartDate = filled($validated['start_date'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $validated['start_date'])->startOfDay()
            : null;
        $customEndDate = filled($validated['end_date'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $validated['end_date'])->endOfDay()
            : null;
        $isCustomRange = $customStartDate !== null && $customEndDate !== null;

        $landingPage = $this->ensureLandingPage($request);
        $analytics = $isCustomRange
            ? $this->analytics->summarizeBetween($landingPage, $customStartDate, $customEndDate, true, $selectedSource)
            : $this->analytics->summarize($landingPage, $range, $selectedSource);

        $filenameBase = sprintf('analytics-%s-%s', $landingPage->slug, now()->format('Y-m-d'));

        return $format === 'excel'
            ? $this->streamExcel($landingPage, $analytics, $filenameBase)
            : $this->streamCsv($landingPage, $analytics, $filenameBase);
    }

    protected function streamCsv(LandingPage $landingPage, array $analytics, string $filenameBase): StreamedResponse
    {
        return response()->streamDownload(function () use ($analytics, $landingPage): void {
            $stream = fopen('php://output', 'wb');

            if (! $stream) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");

            $this->writeCsvContent($stream, $landingPage, $analytics);

            fclose($stream);
        }, $filenameBase.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function streamExcel(LandingPage $landingPage, array $analytics, string $filenameBase): StreamedResponse
    {
        return response()->streamDownload(function () use ($landingPage, $analytics): void {
            echo $this->buildExcelContent($landingPage, $analytics);
        }, $filenameBase.'.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    /**
     * @param resource $stream
     */
    protected function writeCsvContent($stream, LandingPage $landingPage, array $analytics): void
    {
        fputcsv($stream, ['Ringkasan Analytics']);
        fputcsv($stream, ['Landing Page', $landingPage->title]);
        fputcsv($stream, ['Periode', $analytics['period_label']]);
        fputcsv($stream, ['Komparasi', $analytics['previous_period_label']]);
        fputcsv($stream, ['Source', $analytics['source_label']]);
        fputcsv($stream, []);

        fputcsv($stream, ['Metrik', 'Nilai Saat Ini', 'Periode Sebelumnya', 'Delta']);

        foreach ($this->metricExportRows($analytics) as $row) {
            fputcsv($stream, $row);
        }

        fputcsv($stream, []);
        fputcsv($stream, ['Timeline Harian']);
        fputcsv($stream, ['Tanggal', 'Page View', 'Visitor Unik', 'CTA Click', 'Link Click', 'CTA Rate']);

        foreach ($analytics['timeline'] as $day) {
            fputcsv($stream, [
                $day['date'],
                $day['views'],
                $day['unique_visitors'],
                $day['cta_clicks'],
                $day['link_clicks'],
                $day['cta_rate'].'%',
            ]);
        }

        fputcsv($stream, []);
        fputcsv($stream, ['Traffic Source']);
        fputcsv($stream, ['Source', 'View', 'Share']);

        foreach ($analytics['top_referrers'] as $referrer) {
            fputcsv($stream, [
                $referrer['label'],
                $referrer['count'],
                $referrer['share'].'%',
            ]);
        }

        fputcsv($stream, []);
        fputcsv($stream, ['Top Link']);
        fputcsv($stream, ['Label', 'URL', 'Klik', 'Share']);

        foreach ($analytics['top_links'] as $link) {
            fputcsv($stream, [
                $link->label,
                $link->url,
                $link->clicks_count,
                $link->clicks_share.'%',
            ]);
        }
    }

    protected function buildExcelContent(LandingPage $landingPage, array $analytics): string
    {
        $metricRows = $this->metricExportRows($analytics);

        $escape = fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

        $html = [
            '<html><head><meta charset="UTF-8"></head><body>',
            '<table border="1">',
            '<tr><th colspan="2">Ringkasan Analytics</th></tr>',
            '<tr><td>Landing Page</td><td>'.$escape($landingPage->title).'</td></tr>',
            '<tr><td>Periode</td><td>'.$escape($analytics['period_label']).'</td></tr>',
            '<tr><td>Komparasi</td><td>'.$escape($analytics['previous_period_label']).'</td></tr>',
            '<tr><td>Source</td><td>'.$escape($analytics['source_label']).'</td></tr>',
            '</table>',
            '<br>',
            '<table border="1">',
            '<tr><th>Metrik</th><th>Nilai Saat Ini</th><th>Periode Sebelumnya</th><th>Delta</th></tr>',
        ];

        foreach ($metricRows as $row) {
            $html[] = '<tr><td>'.$escape($row[0]).'</td><td>'.$escape($row[1]).'</td><td>'.$escape($row[2]).'</td><td>'.$escape($row[3]).'</td></tr>';
        }

        $html[] = '</table><br><table border="1">';
        $html[] = '<tr><th colspan="6">Timeline Harian</th></tr>';
        $html[] = '<tr><th>Tanggal</th><th>Page View</th><th>Visitor Unik</th><th>CTA Click</th><th>Link Click</th><th>CTA Rate</th></tr>';

        foreach ($analytics['timeline'] as $day) {
            $html[] = '<tr>'
                .'<td>'.$escape($day['date']).'</td>'
                .'<td>'.$escape($day['views']).'</td>'
                .'<td>'.$escape($day['unique_visitors']).'</td>'
                .'<td>'.$escape($day['cta_clicks']).'</td>'
                .'<td>'.$escape($day['link_clicks']).'</td>'
                .'<td>'.$escape($day['cta_rate'].'%').'</td>'
                .'</tr>';
        }

        $html[] = '</table><br><table border="1">';
        $html[] = '<tr><th colspan="3">Traffic Source</th></tr>';
        $html[] = '<tr><th>Source</th><th>View</th><th>Share</th></tr>';

        foreach ($analytics['top_referrers'] as $referrer) {
            $html[] = '<tr>'
                .'<td>'.$escape($referrer['label']).'</td>'
                .'<td>'.$escape($referrer['count']).'</td>'
                .'<td>'.$escape($referrer['share'].'%').'</td>'
                .'</tr>';
        }

        $html[] = '</table><br><table border="1">';
        $html[] = '<tr><th colspan="4">Top Link</th></tr>';
        $html[] = '<tr><th>Label</th><th>URL</th><th>Klik</th><th>Share</th></tr>';

        foreach ($analytics['top_links'] as $link) {
            $html[] = '<tr>'
                .'<td>'.$escape($link->label).'</td>'
                .'<td>'.$escape($link->url).'</td>'
                .'<td>'.$escape($link->clicks_count).'</td>'
                .'<td>'.$escape($link->clicks_share.'%').'</td>'
                .'</tr>';
        }

        $html[] = '</table></body></html>';

        return implode('', $html);
    }

    protected function metricExportRows(array $analytics): array
    {
        $rows = [
            'Page View' => $analytics['metrics']['views'],
            'Visitor Unik' => $analytics['metrics']['unique_visitors'],
            'CTA Click' => $analytics['metrics']['cta_clicks'],
            'Link Click' => $analytics['metrics']['link_clicks'],
            'CTA Rate' => $analytics['metrics']['conversion_rate'],
        ];

        return collect($rows)->map(function (array $metric, string $label) {
            $delta = match ($metric['direction']) {
                'new' => 'Baru',
                'flat' => 'Stabil',
                default => ($metric['delta'] > 0 ? '+' : '').number_format($metric['delta'], 1).'%',
            };

            return [
                $label,
                $metric['value'],
                $metric['previous'],
                $delta,
            ];
        })->values()->all();
    }

    protected function ensureLandingPage(Request $request): LandingPage
    {
        return $request->user()->landingPage()->firstOrCreate([], [
            ...LandingPage::defaultAttributes($request->user()->name, '628000000000', false),
            'bio' => 'Lengkapi profil ini untuk mulai membagikan link unik ke audiens Anda.',
            'whatsapp_message' => 'Halo, saya melihat landing page Anda.',
        ]);
    }
}
