<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Company;
use App\Models\SocialPost;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    private array $folders = [
        'Tool Images' => 'tools',
        'Company Logos' => 'companies',
        'Article Images' => 'articles',
        'Social Images' => 'social',
    ];

    public function index(Request $request)
    {
        $disk = Storage::disk('public');
        $activeFolder = $request->query('folder');
        $search = $request->query('search');
        $paths = $activeFolder && in_array($activeFolder, $this->folders, true) ? $disk->allFiles($activeFolder) : $disk->allFiles();

        $files = collect($paths)->filter(fn ($path) => !$search || str_contains(strtolower($path), strtolower($search)))
            ->map(function ($path) use ($disk) {
                $usage = $this->usageFor($path);
                return [
                    'path' => $path,
                    'name' => basename($path),
                    'url' => $disk->url($path),
                    'size' => $disk->size($path),
                    'usage_count' => $usage['count'],
                    'used_by' => $usage['labels'],
                ];
            })->sortByDesc(fn ($file) => $disk->lastModified($file['path']))->values();

        return view('media.index', [
            'files' => $files,
            'folders' => $this->folders,
            'activeFolder' => $activeFolder,
            'totalFiles' => $files->count(),
            'totalSize' => $this->formatBytes($files->sum('size')),
        ]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['path' => ['required', 'string']]);
        $allowed = collect($this->folders)->contains(fn ($folder) => str_starts_with($data['path'], $folder . '/'));
        abort_unless($allowed, 403);

        $usage = $this->usageFor($data['path']);
        if ($usage['count'] > 0) {
            return back()->withErrors(['media' => 'This file is still used by: ' . implode(', ', $usage['labels']) . '. Detach it before deleting.']);
        }

        Storage::disk('public')->delete($data['path']);
        return back()->with('status', 'Unused media file deleted safely.');
    }

    private function usageFor(string $path): array
    {
        $labels = [];
        $articleCount = Article::where('featured_image_path', $path)->count();
        $socialCount = SocialPost::where('image_path', $path)->count();
        $toolCount = Tool::where('logo_path', $path)->orWhere('cover_image_path', $path)->orWhere('og_image_path', $path)->count();
        $companyCount = Company::where('logo_path', $path)->count();

        if ($articleCount) $labels[] = "{$articleCount} article(s)";
        if ($socialCount) $labels[] = "{$socialCount} social post(s)";
        if ($toolCount) $labels[] = "{$toolCount} tool record(s)";
        if ($companyCount) $labels[] = "{$companyCount} company record(s)";

        return ['count' => $articleCount + $socialCount + $toolCount + $companyCount, 'labels' => $labels];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
