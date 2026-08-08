<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestlabController extends Controller
{

    public function index(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('testlab.index');
    }

    public function results(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('testlab.index');
    }
}
