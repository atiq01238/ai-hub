<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ComparisonController extends Controller
{

    public function index(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('comparisons.index');
    }

    public function builder(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('comparisons.builder');
    }

    public function metrics(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('comparisons.index');
    }
}
