<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\MediaUrl;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicMediaController extends Controller
{
    /**
     * Serve public catalog media through Laravel itself.
     *
     * This makes copied Windows/XAMPP projects independent of storage:link
     * and prevents APP_URL (localhost vs 127.0.0.1:8000) from breaking logos.
     */
    public function show(string $path): BinaryFileResponse
    {
        $relative = MediaUrl::diskPath(rawurldecode($path));
        abort_unless($relative, 404);

        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        abort_unless(in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'avif'], true), 404);

        $disk = Storage::disk('public');
        if ($disk->exists($relative)) {
            return response()->file($disk->path($relative), [
                'Cache-Control' => 'public, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        $legacyPublicFile = public_path('storage/' . $relative);
        abort_unless(is_file($legacyPublicFile), 404);

        return response()->file($legacyPublicFile, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
