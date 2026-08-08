<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{

    public function index(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('submissions.index');
    }

    public function all(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('submissions.index');
    }
}
