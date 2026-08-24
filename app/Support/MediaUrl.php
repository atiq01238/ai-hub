<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUrl
{
    /**
     * Resolve catalog media to a request-relative URL.
     *
     * AI Orbit historically stored public media in more than one format:
     *   storage/ai-hub/tools/logos/chatgpt.png
     *   /storage/ai-hub/tools/logos/chatgpt.png
     *   tools/logos/chatgpt-xxxx.png
     *   storage/app/public/tools/logos/chatgpt-xxxx.png
     *
     * Returning a relative /media URL deliberately avoids APP_URL/port
     * mismatches and does not require a working public/storage symlink.
     */
    public static function resolve(?string $path, ?string $fallback = null): ?string
    {
        $path = trim((string) $path);

        if ($path !== '' && Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        $relative = self::diskPath($path);

        if ($relative && self::exists($relative)) {
            return '/media/' . self::encodePath($relative);
        }

        if ($fallback !== null && trim($fallback) !== '') {
            $fallback = trim($fallback);

            if (Str::startsWith($fallback, ['http://', 'https://', '//', 'data:'])) {
                return $fallback;
            }

            $fallbackRelative = self::diskPath($fallback);
            if ($fallbackRelative && self::exists($fallbackRelative)) {
                return '/media/' . self::encodePath($fallbackRelative);
            }

            $publicFallback = ltrim(str_replace('\\', '/', $fallback), '/');
            if (is_file(public_path($publicFallback))) {
                return '/' . self::encodePath($publicFallback);
            }
        }

        return null;
    }

    /**
     * Convert every known legacy/public path shape to the canonical path
     * relative to storage/app/public.
     */
    public static function diskPath(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        // Accept accidental absolute paths copied from a local XAMPP install.
        foreach (['storage/app/public/', 'public/storage/'] as $marker) {
            if (str_contains($normalized, $marker)) {
                $normalized = Str::after($normalized, $marker);
                break;
            }
        }

        // Older seeders stored storage/... while some historical accessors
        // could effectively produce storage/storage/... URLs. Normalize both.
        while (Str::startsWith($normalized, 'storage/')) {
            $normalized = Str::after($normalized, 'storage/');
        }

        $normalized = trim($normalized, '/');

        if ($normalized === '' || str_contains($normalized, "\0")) {
            return null;
        }

        $segments = explode('/', $normalized);
        if (in_array('..', $segments, true)) {
            return null;
        }

        return $normalized;
    }

    /**
     * Database path variants used while older records are still being
     * migrated organically by normal admin edits/imports.
     */
    public static function databaseVariants(?string $path): array
    {
        $relative = self::diskPath($path);
        if (! $relative) {
            return [];
        }

        return array_values(array_unique([
            $relative,
            'storage/' . $relative,
            '/storage/' . $relative,
            'storage/app/public/' . $relative,
            'public/storage/' . $relative,
        ]));
    }

    public static function exists(?string $path): bool
    {
        $relative = self::diskPath($path);
        if (! $relative) {
            return false;
        }

        if (Storage::disk('public')->exists($relative)) {
            return true;
        }

        // ZIP/copy based Windows projects can contain a real public/storage
        // directory instead of a symlink. Support that layout as well.
        return is_file(public_path('storage/' . $relative));
    }

    public static function placeholder(): string
    {
        return '/images/frontend/logo-placeholder.svg';
    }

    private static function encodePath(string $path): string
    {
        return collect(explode('/', trim($path, '/')))
            ->map(static fn (string $segment): string => rawurlencode($segment))
            ->implode('/');
    }
}
