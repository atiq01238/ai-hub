<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ArticleController extends Controller
{

    public function index(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('content.articles.index');
    }

    public function drafts(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('content.articles.index');
    }

    public function editor(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('content.articles.editor');
    }

    public function guides(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('content.articles.index');
    }
}
