<?php

namespace App\Services\System;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class BackupService
{
    public function directory(): string
    {
        $path = storage_path('app/backups');
        File::ensureDirectoryExists($path);
        return $path;
    }

    public function list(): array
    {
        return collect(File::files($this->directory()))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'zip')
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $this->bytes($file->getSize()),
                'bytes' => $file->getSize(),
                'created_at' => \Illuminate\Support\Carbon::createFromTimestamp($file->getMTime()),
                'type' => $this->detectType($file->getFilename()),
                'status' => 'completed',
            ])
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    public function create(string $type = 'full'): array
    {
        if (! in_array($type, ['full', 'database', 'files'], true)) {
            throw new RuntimeException('Unsupported backup type.');
        }
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP Zip extension is required to create backups.');
        }

        $stamp = now()->format('Y-m-d_H-i-s');
        $filename = "ai-hub_{$type}_{$stamp}.zip";
        $zipPath = $this->directory() . DIRECTORY_SEPARATOR . $filename;
        $tempDir = storage_path('app/backup-temp/' . uniqid('backup_', true));
        File::ensureDirectoryExists($tempDir);

        try {
            if (in_array($type, ['full', 'database'], true)) {
                $this->dumpDatabase($tempDir . DIRECTORY_SEPARATOR . 'database.sql');
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not create backup archive.');
            }

            if (File::exists($tempDir . DIRECTORY_SEPARATOR . 'database.sql')) {
                $zip->addFile($tempDir . DIRECTORY_SEPARATOR . 'database.sql', 'database/database.sql');
            }
            if (File::exists($tempDir . DIRECTORY_SEPARATOR . 'database.sqlite')) {
                $zip->addFile($tempDir . DIRECTORY_SEPARATOR . 'database.sqlite', 'database/database.sqlite');
            }

            if (in_array($type, ['full', 'files'], true)) {
                $this->addDirectory($zip, storage_path('app/public'), 'storage/app/public');
            }

            $zip->addFromString('metadata.json', json_encode([
                'application' => config('app.name', 'AI Orbit'),
                'type' => $type,
                'created_at' => now()->toIso8601String(),
                'environment' => app()->environment(),
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $zip->close();

            return ['name' => $filename, 'path' => $zipPath];
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    public function path(string $filename): string
    {
        $safe = basename($filename);
        $path = $this->directory() . DIRECTORY_SEPARATOR . $safe;
        if (! File::exists($path)) {
            abort(404);
        }
        return $path;
    }

    public function delete(string $filename): void
    {
        File::delete($this->path($filename));
    }

    private function dumpDatabase(string $target): void
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        if ($driver === 'sqlite') {
            $db = $connection['database'] ?? null;
            if (! $db || ! File::exists($db)) throw new RuntimeException('SQLite database file was not found.');
            File::copy($db, str_replace('.sql', '.sqlite', $target));
            return;
        }

        if ($driver !== 'mysql') {
            throw new RuntimeException("Database backups currently support MySQL and SQLite. Current driver: {$driver}.");
        }

        $binary = env('MYSQLDUMP_PATH', 'mysqldump');
        if (PHP_OS_FAMILY === 'Windows' && $binary === 'mysqldump' && File::exists('C:\\xampp\\mysql\\bin\\mysqldump.exe')) {
            $binary = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        }

        $host = $connection['host'] ?? '127.0.0.1';
        $port = $connection['port'] ?? '3306';
        $database = $connection['database'] ?? '';
        $username = $connection['username'] ?? '';
        $password = $connection['password'] ?? '';

        $command = sprintf(
            '"%s" --host=%s --port=%s --user=%s %s --single-transaction --quick --skip-lock-tables %s > %s 2>&1',
            $binary,
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            $password !== '' ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($target)
        );

        exec($command, $output, $exitCode);
        if ($exitCode !== 0 || ! File::exists($target) || File::size($target) === 0) {
            @File::delete($target);
            throw new RuntimeException('Database dump failed. Set MYSQLDUMP_PATH in .env if mysqldump is not available in PATH.');
        }
    }

    private function addDirectory(ZipArchive $zip, string $directory, string $prefix): void
    {
        if (! File::isDirectory($directory)) return;
        foreach (File::allFiles($directory) as $file) {
            $relative = ltrim(str_replace('\\', '/', $file->getRelativePathname()), '/');
            $zip->addFile($file->getPathname(), trim($prefix, '/') . '/' . $relative);
        }
    }

    private function detectType(string $name): string
    {
        foreach (['database', 'files', 'full'] as $type) {
            if (str_contains($name, "_{$type}_")) return $type;
        }
        return 'archive';
    }

    private function bytes(float|int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB']; $i = 0;
        while ($bytes >= 1024 && $i < 4) { $bytes /= 1024; $i++; }
        return number_format($bytes, $i >= 2 ? 1 : 0) . ' ' . $units[$i];
    }
}
