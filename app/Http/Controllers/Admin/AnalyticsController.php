<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics)
    {
    }

    public function website(Request $request) { return $this->show('website', $request); }
    public function tools(Request $request) { return $this->show('tools', $request); }
    public function search(Request $request) { return $this->show('search', $request); }
    public function comparisons(Request $request) { return $this->show('comparisons', $request); }
    public function content(Request $request) { return $this->show('content', $request); }
    public function trending(Request $request) { return $this->show('trending', $request); }

    public function export(Request $request, string $tab): StreamedResponse
    {
        abort_unless(in_array($tab, ['website', 'tools', 'search', 'comparisons', 'content', 'trending'], true), 404);

        $days = $this->days($request);
        $rows = $this->analytics->exportRows($tab, $days);
        $filename = "analytics-{$tab}-" . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function show(string $tab, Request $request)
    {
        return view('analytics.index', $this->analytics->dashboard($tab, $this->days($request)));
    }

    private function days(Request $request): int
    {
        $days = (int) $request->integer('days', 30);
        return in_array($days, [1, 7, 30, 90, 365], true) ? $days : 30;
    }
}
