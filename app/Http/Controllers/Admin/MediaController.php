<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    // Maps the chip labels shown in the UI to the real folders we actually
    // upload into (see ToolController/CompanyController/ArticleController).
    // Only folders your app really writes to are listed — no fake categories.
    private array $folders = [
        'Tool Images'    => 'tools',
        'Company Logos'  => 'companies',
        'Article Images' => 'articles',
    ];

    public function index(Request $request)
    {
        $disk = Storage::disk('public');
        $activeFolder = $request->query('folder');
        $search = $request->query('search');

        $paths = $activeFolder && in_array($activeFolder, $this->folders)
            ? $disk->allFiles($activeFolder)
            : $disk->allFiles();

        $files = collect($paths)
            ->filter(fn ($path) => ! $search || str_contains(strtolower($path), strtolower($search)))
            ->map(fn ($path) => [
                'path' => $path,
                'name' => basename($path),
                'url'  => $disk->url($path),
                'size' => $disk->size($path),
            ])
            ->sortByDesc(fn ($file) => $disk->lastModified($file['path']))
            ->values();

        $totalFiles = $files->count();
        $totalBytes = $files->sum('size');

        return view('media.index', [
            'files'        => $files,
            'folders'      => $this->folders,
            'activeFolder' => $activeFolder,
            'totalFiles'   => $totalFiles,
            'totalSize'    => $this->formatBytes($totalBytes),
        ]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['path' => ['required', 'string']]);

        // Only allow deleting inside the folders we actually manage —
        // guards against a crafted path trying to delete something else.
        $allowed = collect($this->folders)->contains(fn ($folder) => str_starts_with($data['path'], $folder . '/'));

        abort_unless($allowed, 403);

        Storage::disk('public')->delete($data['path']);

        return redirect()
            ->back()
            ->with('status', 'File deleted. Note: if a Tool/Company/Article still references this file, its image will now show broken.');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
