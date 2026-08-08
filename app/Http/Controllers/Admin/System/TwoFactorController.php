<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{

    public function index(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('system.2fa');
    }
}
