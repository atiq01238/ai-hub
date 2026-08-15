<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DataVerificationController extends Controller
{
    public function index(Request $request)
    {
        $items = NewsItem::with(['company', 'newsSource'])
            ->whereIn('verification_status', ['unverified', 'needs_verification'])
            ->orderByRaw("CASE WHEN verification_status = 'needs_verification' THEN 0 ELSE 1 END")
            ->orderByDesc('importance')
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return view('system.data-verification', compact('items'));
    }

    public function verify(Request $request, int $id)
    {
        $item = NewsItem::findOrFail($id);
        $item->forceFill([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verification_notes' => $this->note($request, 'Verified manually by an administrator.'),
        ])->save();

        return back()->with('status', 'News item verified.');
    }

    public function needsVerification(Request $request, int $id)
    {
        $item = NewsItem::findOrFail($id);
        $item->forceFill([
            'verification_status' => 'needs_verification',
            'verified_at' => null,
            'verification_notes' => $this->note($request, 'Flagged for additional verification.'),
        ])->save();

        return back()->with('status', 'News item flagged for verification.');
    }

    private function note(Request $request, string $fallback): string
    {
        $value = trim((string) $request->input('verification_notes', ''));
        return Str::limit($value !== '' ? $value : $fallback, 1000);
    }
}
