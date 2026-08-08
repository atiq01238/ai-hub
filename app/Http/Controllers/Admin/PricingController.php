<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PricingController extends Controller
{

    public function index(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('pricing.index');
    }

    public function api(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('pricing.index');
    }

    public function history(Request $request)
    {
        // TODO: replace with real query/paginated data
        return view('pricing.history');
    }
}
