<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\NewsItem;
use App\Models\SocialPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SocialController extends Controller
{
    public function index(Request $request)
    {
        $query = SocialPost::with('newsItem');

        if ($platform = $request->query('platform')) {
            $query->where('platform', $platform);
        }

        $posts = $query->latest()->paginate(12)->withQueryString();
        $recentNews = NewsItem::latest()->take(10)->get();

        return view('content.social.index', compact('posts', 'recentNews'));
    }

    public function create(Request $request)
    {
        $newsItem = $request->query('news_id') ? NewsItem::find($request->query('news_id')) : null;

        return view('content.social.form', compact('newsItem'));
    }

    public function store(Request $request)
    {
        SocialPost::create($this->fromRequest($request));

        return redirect()->route('admin.content.social.index')->with('status', 'Post saved.');
    }

    public function edit(int $id)
    {
        $post = SocialPost::findOrFail($id);

        return view('content.social.form', compact('post'));
    }

    public function update(Request $request, int $id)
    {
        $post = SocialPost::findOrFail($id);
        $post->update($this->fromRequest($request, $post));

        return redirect()->route('admin.content.social.index')->with('status', 'Post updated.');
    }

    public function destroy(int $id)
    {
        $post = SocialPost::findOrFail($id);

        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete();

        return redirect()->route('admin.content.social.index')->with('status', 'Post deleted.');
    }

    private function fromRequest(Request $request, ?SocialPost $post = null): array
    {
        $data = $request->validate([
            'news_item_id'  => ['nullable', 'exists:news_items,id'],
            'platform'      => ['required', 'in:x,facebook,instagram,linkedin,youtube,tiktok'],
            'content'       => ['required', 'string', 'max:2000'],
            'status'        => ['required', 'in:draft,scheduled,published'],
            'scheduled_at'  => ['nullable', 'date'],
            'image'         => ['nullable', 'image', 'max:4096'],
        ]);

        if ($data['status'] === 'published' && ! ($post?->published_at)) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('image')) {
            if ($post?->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $data['image_path'] = $request->file('image')->store('social', 'public');
        }
        unset($data['image']);

        return $data;
    }
}
