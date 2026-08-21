<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportPreviewStore
{
    public function put(string $type, int $userId, array $rows): string
    {
        $token = Str::random(40);
        $directory = storage_path('app/import-previews');
        File::ensureDirectoryExists($directory);
        File::put($directory.'/'.$token.'.json', json_encode([
            'type' => $type,
            'user_id' => $userId,
            'created_at' => now()->toIso8601String(),
            'rows' => $rows,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $token;
    }

    public function get(string $token, int $userId, string $type): array
    {
        $path = storage_path('app/import-previews/'.$token.'.json');
        abort_unless(File::exists($path), 419, 'Import preview expired. Upload the file again.');
        $payload = json_decode(File::get($path), true) ?: [];
        abort_unless(($payload['user_id'] ?? null) === $userId && ($payload['type'] ?? null) === $type, 403);
        return $payload;
    }

    public function forget(string $token): void
    {
        File::delete(storage_path('app/import-previews/'.$token.'.json'));
    }

    public function purgeExpired(int $hours = 2): void
    {
        $directory = storage_path('app/import-previews');
        if (! File::isDirectory($directory)) return;
        foreach (File::files($directory) as $file) {
            if ($file->getMTime() < now()->subHours($hours)->timestamp) File::delete($file->getPathname());
        }
    }
}
