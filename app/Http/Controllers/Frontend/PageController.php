<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PageController extends Controller
{
    public function about()
    {
        return view('frontend.pages.about');
    }

    public function methodology()
    {
        return view('frontend.pages.methodology');
    }

    public function contact()
    {
        return view('frontend.pages.contact');
    }

    public function privacy()
    {
        return view('frontend.pages.privacy');
    }

    public function terms()
    {
        return view('frontend.pages.terms');
    }

    public function cookies()
    {
        return view('frontend.pages.cookies');
    }

    public function disclosures()
    {
        return view('frontend.pages.disclosures');
    }

    public function storeContact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'topic' => ['required', 'in:general,feedback,data_correction,partnership,press,technical'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'company_name' => ['nullable', 'max:0'],
        ]);

        unset($data['company_name']);
        $data['user_id'] = $request->user()?->id;
        $data['email'] = $request->user()?->email ?? $data['email'];
        $data['status'] = 'new';
        $data['ip_hash'] = hash('sha256', ($request->ip() ?? 'unknown') . '|' . config('app.key'));
        $data['user_agent'] = mb_substr((string) $request->userAgent(), 0, 500);

        $duplicate = ContactMessage::query()
            ->where('email', $data['email'])
            ->where('subject', $data['subject'])
            ->where('created_at', '>=', now()->subHour())
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'subject' => 'A matching message from this email was already received recently.',
            ]);
        }

        ContactMessage::create($data);

        return redirect()->route('contact')->with('status', 'Message received. Thank you for helping us improve AI Hub.');
    }
}
