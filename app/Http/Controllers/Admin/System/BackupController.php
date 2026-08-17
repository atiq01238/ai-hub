<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\System\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    public function index(BackupService $backups)
    {
        $items = $backups->list();
        $totalBytes = collect($items)->sum('bytes');
        $lastBackup = collect($items)->first();
        $backupDirectory = $backups->directory();
        $freeBytes = @disk_free_space($backupDirectory) ?: 0;

        return view('system.backups', compact('items', 'totalBytes', 'lastBackup', 'freeBytes'));
    }

    public function store(Request $request, BackupService $backups)
    {
        $data = $request->validate(['type' => ['required', 'in:full,database,files']]);
        try {
            $backup = $backups->create($data['type']);
            return back()->with('status', 'Backup created: ' . $backup['name']);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', $e->getMessage());
        }
    }

    public function download(string $filename, BackupService $backups)
    {
        return response()->download($backups->path($filename));
    }

    public function destroy(string $filename, BackupService $backups)
    {
        $backups->delete($filename);
        return back()->with('status', 'Backup deleted.');
    }
}
