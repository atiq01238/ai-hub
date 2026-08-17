<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        return $this->listing($request, null, 'content');
    }

    public function communityIndex(Request $request)
    {
        return $this->listing($request, 'user', 'community');
    }

    public function editor()
    {
        return view('content.reviews.editor', [
            'tools' => Tool::orderBy('name')->get(),
            'reviewers' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tool_id' => ['required', 'exists:tools,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'verdict' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'pros_input' => ['nullable', 'string'],
            'cons_input' => ['nullable', 'string'],
            'quality' => ['nullable', 'numeric', 'between:1,5'],
            'speed' => ['nullable', 'numeric', 'between:1,5'],
            'features' => ['nullable', 'numeric', 'between:1,5'],
            'ease_of_use' => ['nullable', 'numeric', 'between:1,5'],
            'value' => ['nullable', 'numeric', 'between:1,5'],
            'rating' => ['required', 'numeric', 'between:1,5'],
            'status' => ['required', 'in:pending,published'],
        ]);

        $data['review_type'] = 'editorial';
        $data['pros'] = $this->lines($data['pros_input'] ?? '');
        $data['cons'] = $this->lines($data['cons_input'] ?? '');
        $data['rating_breakdown'] = collect(['quality', 'speed', 'features', 'ease_of_use', 'value'])
            ->mapWithKeys(fn ($key) => [$key => isset($data[$key]) ? (float) $data[$key] : null])
            ->filter(fn ($value) => $value !== null)->all();
        unset($data['pros_input'], $data['cons_input'], $data['quality'], $data['speed'], $data['features'], $data['ease_of_use'], $data['value']);

        if ($data['status'] === 'published') {
            $data['moderated_by'] = $request->user()->id;
            $data['moderated_at'] = now();
        }

        $review = Review::create($data);

        return redirect()->route('admin.content.reviews.show', $review->id)->with('status', 'Editorial review saved.');
    }

    public function show(int $id)
    {
        return $this->detail($id, 'content');
    }

    public function communityShow(int $id)
    {
        return $this->detail($id, 'community', 'user');
    }

    public function approve(Request $request, int $id)
    {
        $data = $request->validate([
            'moderation_note' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::findOrFail($id)->update([
            'status' => 'published',
            'moderation_note' => $data['moderation_note'] ?? null,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return back()->with('status', 'Review approved.');
    }

    public function flag(Request $request, int $id)
    {
        $data = $request->validate([
            'moderation_note' => ['required', 'string', 'max:1000'],
        ]);

        Review::findOrFail($id)->update([
            'status' => 'flagged',
            'moderation_note' => $data['moderation_note'],
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return back()->with('status', 'Review flagged with a moderation reason.');
    }

    public function destroy(Request $request, int $id)
    {
        Review::findOrFail($id)->delete();

        $route = $request->input('context') === 'community'
            ? 'admin.community.reviews.index'
            : 'admin.content.reviews.index';

        return redirect()->route($route)->with('status', 'Review moved to the recovery bin.');
    }

    private function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))->map(fn ($v) => trim($v))->filter()->values()->all();
    }

    private function listing(Request $request, ?string $forcedType, string $context)
    {
        $query = Review::query()->with(['tool', 'user', 'moderator']);

        if ($forcedType) {
            $query->where('review_type', $forcedType);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($q) => $q->where('verdict', 'like', "%{$search}%")
                ->orWhere('body', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                ->orWhereHas('tool', fn ($tool) => $tool->where('name', 'like', "%{$search}%")));
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['pending', 'published', 'flagged'], true)) {
                $query->where('status', $status);
            }
        }

        if (! $forcedType && ($type = $request->query('type'))) {
            if (in_array($type, ['user', 'editorial'], true)) {
                $query->where('review_type', $type);
            }
        }

        if ($toolId = $request->integer('tool_id')) {
            $query->where('tool_id', $toolId);
        }

        if ($rating = $request->query('rating')) {
            if (in_array((string) $rating, ['3', '4', '5'], true)) {
                $query->where('rating', '>=', (float) $rating);
            }
        }

        $countQuery = Review::query()->when($forcedType, fn ($q) => $q->where('review_type', $forcedType));

        return view('content.reviews.index', [
            'reviews' => $query->latest()->paginate(20)->withQueryString(),
            'tools' => Tool::orderBy('name')->get(),
            'counts' => [
                'all' => (clone $countQuery)->count(),
                'pending' => (clone $countQuery)->where('status', 'pending')->count(),
                'published' => (clone $countQuery)->where('status', 'published')->count(),
                'flagged' => (clone $countQuery)->where('status', 'flagged')->count(),
            ],
            'context' => $context,
        ]);
    }

    private function detail(int $id, string $context, ?string $forcedType = null)
    {
        $review = Review::query()
            ->with(['tool', 'user', 'moderator'])
            ->withCount('reports')
            ->when($forcedType, fn ($q) => $q->where('review_type', $forcedType))
            ->findOrFail($id);

        return view('content.reviews.show', compact('review', 'context'));
    }
}