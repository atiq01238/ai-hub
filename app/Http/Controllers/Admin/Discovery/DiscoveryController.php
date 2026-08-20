<?php

namespace App\Http\Controllers\Admin\Discovery;

use App\Http\Controllers\Controller;
use App\Models\AiDiscovery;
use App\Models\AiModel;
use App\Models\DiscoverySource;
use App\Models\Tool;
use App\Models\NewsItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiscoveryController extends Controller
{
    public function index(Request $request)
    {
        $query = AiDiscovery::query()->with(['newsSource', 'company', 'matchedTool', 'matchedModel', 'reviewer']);

        if ($request->filled('type')) {
            $query->where('entity_type', $request->string('type')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        } else {
            $query->where('status', 'pending');
        }
        if ($request->filled('confidence')) {
            $query->where('confidence', '>=', max(0, min(100, $request->integer('confidence'))));
        }
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('candidate_name', 'like', "%{$search}%")
                    ->orWhere('headline', 'like', "%{$search}%");
            });
        }

        $discoveries = $query->latest()->paginate(20)->withQueryString();
        $stats = [
            'pending' => AiDiscovery::where('status', 'pending')->count(),
            'models' => AiDiscovery::where('status', 'pending')->whereIn('entity_type', ['model', 'model_update'])->count(),
            'tools' => AiDiscovery::where('status', 'pending')->whereIn('entity_type', ['tool', 'tool_update'])->count(),
            'high_confidence' => AiDiscovery::where('status', 'pending')->where('confidence', '>=', 85)->count(),
        ];
        $sources = DiscoverySource::with('newsSource')->orderByDesc('trusted')->orderBy('id')->get();
        $enabledSourceIds = $sources->where('enabled', true)->pluck('news_source_id');
        $runtime = [
            'health_status' => Setting::get('ai_discovery_health_status', 'not_checked'),
            'health_checked_at' => Setting::get('ai_discovery_health_checked_at'),
            'health_message' => Setting::get('ai_discovery_health_message', 'Health check has not run yet.'),
            'enabled_sources' => $enabledSourceIds->count(),
            'unanalyzed' => $enabledSourceIds->isEmpty() ? 0 : NewsItem::whereIn('news_source_id', $enabledSourceIds)->whereNull('discovery_analyzed_at')->count(),
        ];

        return view('discovery.index', compact('discoveries', 'stats', 'sources', 'runtime'));
    }

    public function show(int $id)
    {
        $discovery = AiDiscovery::with(['newsSource', 'company', 'newsItem', 'matchedTool', 'matchedModel', 'reviewer'])->findOrFail($id);
        return view('discovery.show', compact('discovery'));
    }

    public function ignore(Request $request, int $id)
    {
        $discovery = AiDiscovery::findOrFail($id);
        $this->review($discovery, 'ignored', $request->user()?->id);
        return back()->with('status', 'Discovery ignored.');
    }

    public function restore(Request $request, int $id)
    {
        $discovery = AiDiscovery::findOrFail($id);
        $discovery->update(['status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null]);
        return back()->with('status', 'Discovery moved back to pending review.');
    }

    public function merge(Request $request, int $id)
    {
        $discovery = AiDiscovery::findOrFail($id);
        $this->review($discovery, 'merged', $request->user()?->id);
        return back()->with('status', 'Discovery marked as an existing product/model update.');
    }

    public function convertToTool(Request $request, int $id)
    {
        $discovery = AiDiscovery::findOrFail($id);

        if ($discovery->matched_tool_id) {
            $this->review($discovery, 'merged', $request->user()?->id);
            return redirect()->route('admin.tools.edit', $discovery->matched_tool_id)
                ->with('status', 'Existing tool found. Discovery marked as an update.');
        }

        $tool = DB::transaction(function () use ($discovery, $request) {
            $base = Str::slug($discovery->candidate_name) ?: 'discovered-tool';
            $slug = $base;
            $counter = 2;
            while (Tool::where('slug', $slug)->exists()) $slug = $base . '-' . $counter++;

            $tool = Tool::create([
                'company_id' => $discovery->company_id,
                'name' => $discovery->candidate_name,
                'slug' => $slug,
                'short_description' => Str::limit((string) $discovery->summary, 255, ''),
                'description' => $discovery->summary,
                'status' => 'draft',
            ]);

            $discovery->update([
                'matched_tool_id' => $tool->id,
                'status' => 'approved',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
            ]);
            return $tool;
        });

        return redirect()->route('admin.tools.edit', $tool->id)
            ->with('status', 'Draft tool created from discovery. Review details before publishing.');
    }

    public function convertToModel(Request $request, int $id)
    {
        $discovery = AiDiscovery::findOrFail($id);

        if ($discovery->matched_model_id) {
            $this->review($discovery, 'merged', $request->user()?->id);
            return redirect()->route('admin.models.edit', $discovery->matched_model_id)
                ->with('status', 'Existing model found. Discovery marked as an update.');
        }

        $model = DB::transaction(function () use ($discovery, $request) {
            $base = Str::slug($discovery->candidate_name) ?: 'discovered-model';
            $slug = $base;
            $counter = 2;
            while (AiModel::where('slug', $slug)->exists()) $slug = $base . '-' . $counter++;

            $model = AiModel::create([
                'company_id' => $discovery->company_id,
                'name' => $discovery->candidate_name,
                'slug' => $slug,
                'release_date' => $discovery->newsItem?->published_at?->toDateString(),
                'capability_notes' => $discovery->summary,
                'status' => 'preview',
            ]);

            $discovery->update([
                'matched_model_id' => $model->id,
                'status' => 'approved',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
            ]);
            return $model;
        });

        return redirect()->route('admin.models.edit', $model->id)
            ->with('status', 'Preview model created from discovery. Review specifications before publishing.');
    }

    public function scanNow()
    {
        $exit = Artisan::call('discovery:scan', ['--limit' => 500]);
        $output = trim(Artisan::output());

        if ($exit !== 0) {
            return back()->with('error', $output !== '' ? $output : 'Discovery scan failed. Check the application log.');
        }

        Artisan::call('discovery:health-check');

        return back()->with('status', $output !== '' ? $output : 'Discovery scan completed.');
    }

    public function updateSource(Request $request, int $id)
    {
        $source = DiscoverySource::findOrFail($id);
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'trusted' => ['nullable', 'boolean'],
            'detect_tools' => ['nullable', 'boolean'],
            'detect_models' => ['nullable', 'boolean'],
            'minimum_confidence' => ['required', 'integer', 'min:30', 'max:95'],
        ]);

        $source->update([
            'enabled' => $request->boolean('enabled'),
            'trusted' => $request->boolean('trusted'),
            'detect_tools' => $request->boolean('detect_tools'),
            'detect_models' => $request->boolean('detect_models'),
            'minimum_confidence' => $data['minimum_confidence'],
        ]);

        return back()->with('status', 'Discovery source settings updated.');
    }

    private function review(AiDiscovery $discovery, string $status, ?int $userId): void
    {
        $discovery->update([
            'status' => $status,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
        ]);
    }
}
