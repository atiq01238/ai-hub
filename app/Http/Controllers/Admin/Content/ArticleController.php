<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\ArticleWorkflowEvent;
use App\Models\Category;
use App\Models\Company;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $guide = Category::where('slug', 'guide')->orWhere('name', 'Guide')->first();
        $request->merge($guide ? ['category_id' => $guide->id] : ['category' => 'Guide']);
        return $this->filteredIndex($request, 'Guides');
    }

    public function editor(Request $request, ?int $id = null)
    {
        $article = $id ? Article::with(['relatedToolTerms', 'relatedModelTerms', 'tagTerms'])->findOrFail($id) : null;

        return view('content.articles.editor', [
            'article' => $article,
            'companies' => Company::orderBy('name')->get(),
            'authors' => User::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'tools' => Tool::orderBy('name')->get(),
            'models' => AiModel::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        [$data, $relations] = $this->fromRequest($request);

        $article = DB::transaction(function () use ($data, $relations) {
            $article = Article::create($data);
            $this->syncRelations($article, $relations);
            ArticleWorkflowEvent::create([
                'article_id' => $article->id,
                'user_id' => auth()->id(),
                'from_status' => null,
                'to_status' => $article->approval_status,
                'action' => 'created',
                'comment' => 'Article created.',
            ]);
            return $article;
        });

        return redirect()->route('admin.content.articles.show', $article->id)->with('status', 'Article created.');
    }

    public function show(int $id)
    {
        $article = Article::with([
            'author', 'reviewer', 'company', 'categoryTerm', 'relatedToolTerms',
            'relatedModelTerms', 'tagTerms', 'workflowEvents.user',
        ])->findOrFail($id);

        return view('content.articles.show', compact('article'));
    }

    public function update(Request $request, int $id)
    {
        $article = Article::findOrFail($id);
        [$data, $relations] = $this->fromRequest($request, $article);

        $approvalInvalidated = $article->approval_status === 'approved' && $this->contentChanged($article, $data, $relations);
        if ($approvalInvalidated) {
            $data['approval_status'] = 'draft';
            $data['approved_at'] = null;
            $data['status'] = 'draft';
        }

        DB::transaction(function () use ($article, $data, $relations, $approvalInvalidated) {
            $article->update($data);
            $this->syncRelations($article, $relations);
            if ($approvalInvalidated) {
                ArticleWorkflowEvent::create([
                    'article_id' => $article->id,
                    'user_id' => auth()->id(),
                    'from_status' => 'approved',
                    'to_status' => 'draft',
                    'action' => 'approval_invalidated',
                    'comment' => 'Approved content was edited and requires review again.',
                ]);
            }
        });

        return redirect()->route('admin.content.articles.show', $article->id)->with('status', 'Article updated.');
    }

    public function destroy(int $id)
    {
        $article = Article::findOrFail($id);
        if ($article->featured_image_path) Storage::disk('public')->delete($article->featured_image_path);
        $article->delete();

        return redirect()->route('admin.content.articles.index')->with('status', 'Article deleted.');
    }

    private function filteredIndex(Request $request, ?string $pageTitle = null)
    {
        $query = Article::with(['author', 'reviewer', 'company', 'categoryTerm']);

        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('summary', 'like', "%{$search}%"));
        }
        if ($status = $request->query('status')) $query->where('status', $status);
        if ($approval = $request->query('approval_status')) $query->where('approval_status', $approval);
        if ($categoryId = $request->query('category_id')) $query->where('category_id', $categoryId);
        elseif ($category = $request->query('category')) $query->where('category', $category);

        return view('content.articles.index', [
            'articles' => $query->latest()->paginate(20)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
            'pageTitle' => $pageTitle,
        ]);
    }

    private function fromRequest(Request $request, ?Article $article = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'exists:users,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'content' => ['nullable', 'string'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'tool_ids' => ['nullable', 'array'],
            'tool_ids.*' => ['integer', 'exists:tools,id'],
            'model_ids' => ['nullable', 'array'],
            'model_ids.*' => ['integer', 'exists:ai_models,id'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $relations = [
            'tag_ids' => $data['tag_ids'] ?? [],
            'tool_ids' => $data['tool_ids'] ?? [],
            'model_ids' => $data['model_ids'] ?? [],
        ];
        unset($data['tag_ids'], $data['tool_ids'], $data['model_ids']);

        $data['slug'] = $article?->slug ?? (Str::slug($data['title']) . '-' . Str::lower(Str::random(6)));

        if (!empty($data['category_id'])) {
            $data['category'] = Category::find($data['category_id'])?->name;
        }

        // Publication is only allowed after approval. Existing published articles remain editable.
        if (in_array($data['status'], ['published', 'scheduled'], true) && (($article?->approval_status ?? 'draft') !== 'approved')) {
            $data['status'] = 'draft';
        }

        if ($data['status'] === 'published' && empty($data['published_at'])) $data['published_at'] = now();
        if ($data['status'] === 'scheduled' && empty($data['published_at'])) {
            abort(422, 'A scheduled article requires a publish date/time.');
        }

        if ($request->hasFile('featured_image')) {
            if ($article?->featured_image_path) Storage::disk('public')->delete($article->featured_image_path);
            $data['featured_image_path'] = $request->file('featured_image')->store('articles', 'public');
        }
        unset($data['featured_image']);

        return [$data, $relations];
    }

    private function contentChanged(Article $article, array $data, array $relations): bool
    {
        foreach (['title', 'content', 'summary', 'category_id', 'company_id', 'seo_title', 'meta_description'] as $field) {
            if (array_key_exists($field, $data) && (string) ($article->{$field} ?? '') !== (string) ($data[$field] ?? '')) {
                return true;
            }
        }

        $currentTags = $article->tagTerms()->pluck('tags.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $currentTools = $article->relatedToolTerms()->pluck('tools.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $currentModels = $article->relatedModelTerms()->pluck('ai_models.id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        return $currentTags !== collect($relations['tag_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all()
            || $currentTools !== collect($relations['tool_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all()
            || $currentModels !== collect($relations['model_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    private function syncRelations(Article $article, array $relations): void
    {
        $article->tagTerms()->sync($relations['tag_ids']);
        $article->relatedToolTerms()->sync($relations['tool_ids']);
        $article->relatedModelTerms()->sync($relations['model_ids']);

        // Keep old JSON columns populated for backward compatibility with existing public templates.
        $article->updateQuietly([
            'tags' => Tag::whereIn('id', $relations['tag_ids'])->pluck('name')->values()->all(),
            'related_tools' => Tool::whereIn('id', $relations['tool_ids'])->pluck('name')->values()->all(),
            'related_models' => AiModel::whereIn('id', $relations['model_ids'])->pluck('name')->values()->all(),
        ]);
    }
}
