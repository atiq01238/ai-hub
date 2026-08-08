<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Tool;
use Illuminate\Http\Request;

class PublicReviewController extends Controller
{
    public function create(Tool $tool)
    {
        return view('reviews.create', compact('tool'));
    }

    public function store(Request $request, Tool $tool)
    {
        $data = $request->validate([
            'rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'body'   => ['nullable', 'string', 'max:2000'],
        ]);

        Review::create([
            'tool_id' => $tool->id,
            'user_id' => $request->user()->id,
            'rating'  => $data['rating'],
            'body'    => $data['body'] ?? null,
            'status'  => 'pending', // an admin has to approve it before it counts
        ]);

        return redirect()
            ->back()
            ->with('status', 'Thanks! Your review will show once it\'s approved.');
    }
}
