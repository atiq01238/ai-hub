<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Tool;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function index()
    {
        // Real audit: which published Tools/Articles are missing an SEO title
        // or meta description — these are the ones worth fixing first.
        $toolsMissing = Tool::where('status', 'published')
            ->where(fn ($q) => $q->whereNull('seo_title')->orWhereNull('meta_description'))
            ->get();

        $articlesMissing = Article::where('status', 'published')
            ->where(fn ($q) => $q->whereNull('seo_title')->orWhereNull('meta_description'))
            ->get();

        $toolsTotal = Tool::where('status', 'published')->count();
        $articlesTotal = Article::where('status', 'published')->count();

        return view('system.seo', compact('toolsMissing', 'articlesMissing', 'toolsTotal', 'articlesTotal'));
    }
}
