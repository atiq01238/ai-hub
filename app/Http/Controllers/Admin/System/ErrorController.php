<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ErrorController extends Controller
{

    public function index(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('system.errors');
    }

    public function show(Request $request, int $id)
    {
        // TODO: fetch record by $id from the database
        return view('system.error-detail', ['id' => $id]);
    }
}
