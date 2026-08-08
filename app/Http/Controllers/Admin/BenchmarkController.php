<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BenchmarkController extends Controller
{

    public function index(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('benchmarks.index');
    }

    public function create(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('benchmarks.index');
    }

    public function store(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('benchmarks.index');
    }
}
