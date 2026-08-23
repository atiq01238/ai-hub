<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\EmailPreference;
use App\Services\EmailIntelligenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailPreferenceController extends Controller
{
    public function edit(Request $request, EmailIntelligenceService $email): View
    {
        return view('frontend.account.email-preferences', [
            'preference' => $email->ensurePreferences($request->user()),
        ]);
    }

    public function update(Request $request, EmailIntelligenceService $email): RedirectResponse
    {
        $data = $request->validate([
            'email_enabled'=>'nullable|boolean', 'breaking_news'=>'nullable|boolean', 'new_models'=>'nullable|boolean',
            'new_tools'=>'nullable|boolean', 'followed_entities'=>'nullable|boolean', 'benchmark_updates'=>'nullable|boolean',
            'price_changes'=>'nullable|boolean', 'weekly_digest'=>'nullable|boolean',
        ]);
        $preference = $email->ensurePreferences($request->user());
        foreach (array_keys(EmailPreference::defaults()) as $field) $data[$field] = $request->boolean($field);
        $data['unsubscribed_at'] = $data['email_enabled'] ? null : now();
        $preference->update($data);
        return back()->with('status', 'Email preferences updated.');
    }

    public function unsubscribe(Request $request, \App\Models\User $user): View
    {
        $preference = EmailPreference::firstOrCreate(['user_id'=>$user->id], EmailPreference::defaults());
        $preference->update([
            'email_enabled'=>false, 'breaking_news'=>false, 'new_models'=>false, 'new_tools'=>false,
            'followed_entities'=>false, 'benchmark_updates'=>false, 'price_changes'=>false, 'weekly_digest'=>false,
            'unsubscribed_at'=>now(),
        ]);
        return view('frontend.email-unsubscribed', compact('user'));
    }
}
