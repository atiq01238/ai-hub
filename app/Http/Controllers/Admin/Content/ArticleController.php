<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        return $this->filteredIndex($request);
    }

    public function drafts(Request $request)
    {
        $request->merge(['status' => 'draft']);

        return $this->filteredIndex($request);
    }

    public function guides(Request $request)
    {
        $request->merge(['category' => 'Guide']);

        return $this->filteredIndex($request);
    }

    public function editor(Request $request, ?int $id = null)
    {
        $article = $id ? Article::findOrFail($id) : null;
        $companies = Company::orderBy('name')->get();
        $authors = User::orderBy('name')->get();

        return view('content.articles.editor', compact('article', 'companies', 'authors'));
    }

    public function store(Request $request)
    {
        $article = Article::create($this->fromRequest($request));

        return redirect()
            ->route('admin.content.articles.show', $article->id)
            ->with('status', 'Article created.');
    }

    public function show(int $id)
    {
        $article = Article::with(['author', 'company'])->findOrFail($id);

        return view('content.articles.show', compact('article'));
    }

    public function update(Request $request, int $id)
    {
        $article = Article::findOrFail($id);
        $article->update($this->fromRequest($request, $article));

        return redirect()
            ->route('admin.content.articles.show', $article->id)
            ->with('status', 'Article updated.');
    }

    public function destroy(int $id)
    {
        $article = Article::findOrFail($id);

        if ($article->featured_image_path) {
            Storage::disk('public')->delete($article->featured_image_path);
        }

        $article->delete();

        return redirect()
            ->route('admin.content.articles.index')
            ->with('status', 'Article deleted.');
    }

    private function filteredIndex(Request $request)
    {
        $query = Article::with(['author', 'company']);

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $articles = $query->latest()->paginate(20)->withQueryString();

        return view('content.articles.index', compact('articles'));
    }

    private function fromRequest(Request $request, ?Article $article = null): array
    {
        $data = $request->validate([
            'title'                => ['required', 'string', 'max:255'],
            'user_id'               => ['required', 'exists:users,id'],
            'company_id'            => ['nullable', 'exists:companies,id'],
            'content'                => ['nullable', 'string'],
            'summary'                => ['nullable', 'string', 'max:500'],
            'category'               => ['nullable', 'string', 'max:100'],
            'tags_input'             => ['nullable', 'string'],
            'related_tools_input'    => ['nullable', 'string'],
            'related_models_input'   => ['nullable', 'string'],
            'seo_title'              => ['nullable', 'string', 'max:255'],
            'meta_description'       => ['nullable', 'string', 'max:255'],
            'status'                 => ['required', 'in:draft,published,scheduled'],
            'published_at'           => ['nullable', 'date'],
            'featured_image'         => ['nullable', 'image', 'max:4096'],
        ]);

        foreach ([
            'tags' => 'tags_input',
            'related_tools' => 'related_tools_input',
            'related_models' => 'related_models_input',
        ] as $column => $input) {
            $data[$column] = collect(explode(',', $data[$input] ?? ''))
                ->map(fn ($v) => trim($v))
                ->filter()
                ->values()
                ->all();
            unset($data[$input]);
        }

        $data['slug'] = $article?->slug ?? (Str::slug($data['title']) . '-' . Str::random(6));

        if ($data['status'] === 'published' && ! ($article?->published_at)) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            if ($article?->featured_image_path) {
                Storage::disk('public')->delete($article->featured_image_path);
            }
            $data['featured_image_path'] = $request->file('featured_image')->store('articles', 'public');
        }
        unset($data['featured_image']);

        return $data;
    }
}
