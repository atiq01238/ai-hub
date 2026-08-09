<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;

class IntegrationController extends Controller
{
    public function index()
    {
        // Real checks against actual .env / config values — not decoration.
        // "Connected" here means "a value is present", not that we've
        // verified the credential actually works (that would need a live
        // test call per service, which is a bigger task).
        $integrations = [
            [
                'name' => 'Email',
                'icon' => 'mail',
                'connected' => filled(config('mail.mailers.smtp.username')) || config('mail.default') !== 'log',
                'detail' => 'Mailer: ' . config('mail.default'),
            ],
            [
                'name' => 'File Storage',
                'icon' => 'hard-drive',
                'connected' => is_dir(storage_path('app/public')),
                'detail' => 'Disk: ' . config('filesystems.default'),
            ],
            [
                'name' => 'News API',
                'icon' => 'newspaper',
                'connected' => filled(env('NEWS_API_KEY')),
                'detail' => filled(env('NEWS_API_KEY')) ? 'API key present' : 'No API key set — see the News module setup notes',
            ],
            [
                'name' => 'Database',
                'icon' => 'database',
                'connected' => true, // if this page loaded at all, the DB connection works
                'detail' => 'Connection: ' . config('database.default'),
            ],
        ];

        return view('system.integrations', compact('integrations'));
    }
}
