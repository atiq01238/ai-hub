<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MediaController extends Controller
{

    public function index(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('media.index');
    }
}
