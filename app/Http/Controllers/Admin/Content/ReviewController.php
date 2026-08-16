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
        $query = Review::with(['tool', 'user']);

        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q->where('verdict', 'like', "%{$search}%")
                ->orWhere('body', 'like', "%{$search}%")
                ->orWhereHas('tool', fn ($t) => $t->where('name', 'like', "%{$search}%")));
        }
        if ($status = $request->query('status')) $query->where('status', $status);
        if ($type = $request->query('type')) $query->where('review_type', $type);
        if ($toolId = $request->query('tool_id')) $query->where('tool_id', $toolId);
        if ($rating = $request->query('rating')) $query->where('rating', '>=', (float) $rating);

        return view('content.reviews.index', [
            'reviews' => $query->latest()->paginate(20)->withQueryString(),
            'tools' => Tool::orderBy('name')->get(),
            'counts' => [
                'all' => Review::count(),
                'pending' => Review::where('status', 'pending')->count(),
                'published' => Review::where('status', 'published')->count(),
                'flagged' => Review::where('status', 'flagged')->count(),
            ],
        ]);
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

        $review = Review::create($data);

        return redirect()->route('admin.content.reviews.show', $review->id)->with('status', 'Editorial review saved.');
    }

    public function show(int $id)
    {
        $review = Review::with(['tool', 'user'])->findOrFail($id);
        return view('content.reviews.show', compact('review'));
    }

    public function approve(int $id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'published']);
        return back()->with('status', 'Review approved.');
    }

    public function flag(int $id)
    {
        Review::findOrFail($id)->update(['status' => 'flagged']);
        return back()->with('status', 'Review flagged.');
    }

    public function destroy(int $id)
    {
        Review::findOrFail($id)->delete();
        return redirect()->route('admin.content.reviews.index')->with('status', 'Review deleted.');
    }

    private function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))->map(fn ($v) => trim($v))->filter()->values()->all();
    }
}
