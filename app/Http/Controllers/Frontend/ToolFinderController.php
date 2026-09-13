<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ToolFinderEvent;
use App\Services\Frontend\ToolFinderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ToolFinderController extends Controller
{
    public function index()
    {
        return view('frontend.tools.finder', [
            'shortcuts' => config('tool_finder.shortcuts', []),
            'criteria' => $this->defaultCriteria(),
            'results' => collect(),
            'finderRan' => false,
            'adjustmentTips' => [],
            'finderEventId' => null,
        ]);
    }

    public function find(Request $request, ToolFinderService $finder)
    {
        $shortcuts = array_keys((array) config('tool_finder.shortcuts', []));

        $validated = $request->validate([
            'task' => ['nullable', 'string', 'min:3', 'max:220', 'required_without:shortcut'],
            'shortcut' => ['nullable', 'string', Rule::in($shortcuts), 'required_without:task'],
            'budget' => ['nullable', Rule::in(['any', 'free', '10', '25', '50', '50plus'])],
            'experience' => ['nullable', Rule::in(['any', 'beginner', 'intermediate', 'advanced'])],
            'priority' => ['nullable', Rule::in(['any', 'quality', 'value', 'ease', 'privacy', 'api'])],
            'filters' => ['nullable', 'array', 'max:8'],
            'filters.*' => [Rule::in([
                'free_plan',
                'api',
                'open_source',
                'mobile',
                'browser_extension',
                'team_collaboration',
                'commercial_use',
                'privacy_focused',
            ])],
        ]);

        $criteria = array_merge($this->defaultCriteria(), $validated);
        $criteria['filters'] = array_values(array_unique((array) ($validated['filters'] ?? [])));

        $match = $finder->find($criteria, 10);
        $finderEventId = $this->recordFinderEvent($request, $match);

        return view('frontend.tools.finder', [
            'shortcuts' => config('tool_finder.shortcuts', []),
            'criteria' => $match['criteria'],
            'results' => $match['results'],
            'finderRan' => true,
            'intent' => $match['intent'],
            'candidateCount' => $match['candidate_count'],
            'adjustmentTips' => $match['adjustment_tips'] ?? [],
            'finderEventId' => $finderEventId,
        ]);
    }

    public function click(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'min:1'],
            'tool_id' => ['required', 'integer', 'exists:tools,id'],
            'action' => ['required', Rule::in(['view', 'compare'])],
        ]);

        if (! $this->analyticsAllowed($request) || ! Schema::hasTable('tool_finder_events')) {
            return response()->json(['ok' => true]);
        }

        try {
            $eventQuery = ToolFinderEvent::query()->whereKey((int) $validated['event_id']);

            if ($request->user()) {
                $eventQuery->where('user_id', $request->user()->id);
            } else {
                $eventQuery
                    ->whereNull('user_id')
                    ->where('session_key', $this->sessionFingerprint($request));
            }

            $event = $eventQuery->first();
            if ($event) {
                $event->update([
                    'clicked_tool_id' => (int) $validated['tool_id'],
                    'clicked_action' => $validated['action'],
                ]);
            }
        } catch (\Throwable $e) {
            Log::debug('Tool Finder click analytics skipped.', ['message' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }

    private function recordFinderEvent(Request $request, array $match): ?int
    {
        if (! $this->analyticsAllowed($request) || ! Schema::hasTable('tool_finder_events')) {
            return null;
        }

        try {
            $criteria = (array) ($match['criteria'] ?? []);
            $intent = (array) ($match['intent'] ?? []);
            $results = $match['results'] ?? collect();
            $top = $results->first();
            $topToolId = is_array($top) ? ($top['tool']->id ?? null) : null;
            $task = trim((string) ($criteria['task'] ?? ''));

            $event = ToolFinderEvent::create([
                'user_id' => $request->user()?->id,
                'task' => $task !== '' ? Str::lower($task) : null,
                'shortcut' => $criteria['shortcut'] ?: null,
                'budget' => $criteria['budget'] ?? 'any',
                'experience' => $criteria['experience'] ?? 'any',
                'priority' => $criteria['priority'] ?? 'any',
                'filters' => array_values((array) ($criteria['filters'] ?? [])),
                'detected_use_cases' => array_values((array) ($intent['use_cases'] ?? [])),
                'detected_preferences' => array_values((array) ($intent['preferences'] ?? [])),
                'result_count' => $results->count(),
                'top_tool_id' => $topToolId,
                'session_key' => $this->sessionFingerprint($request),
            ]);

            return $event->id;
        } catch (\Throwable $e) {
            // Finder must continue to work even before the analytics migration is run.
            Log::debug('Tool Finder analytics record skipped.', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function analyticsAllowed(Request $request): bool
    {
        if (! config('analytics.enabled', true)) {
            return false;
        }

        if (config('analytics.respect_dnt', true) && $request->headers->get('DNT') === '1') {
            return false;
        }

        if (config('analytics.exclude_admins', true) && $request->user()?->hasAdminPanelAccess()) {
            return false;
        }

        return true;
    }

    private function sessionFingerprint(Request $request): string
    {
        return hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
    }

    private function defaultCriteria(): array
    {
        return [
            'task' => '',
            'shortcut' => '',
            'budget' => 'any',
            'experience' => 'any',
            'priority' => 'any',
            'filters' => [],
        ];
    }
}
