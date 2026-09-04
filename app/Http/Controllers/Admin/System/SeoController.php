<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoHealthService;

class SeoController extends Controller
{
    public function index(SeoHealthService $health)
    {
        $seoHealth = $health->snapshot();

        return view('system.seo', compact('seoHealth'));
    }
}
