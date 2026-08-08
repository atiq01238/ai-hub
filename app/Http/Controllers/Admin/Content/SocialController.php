<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SocialController extends Controller
{

    public function index(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('content.social.index');
    }
}
