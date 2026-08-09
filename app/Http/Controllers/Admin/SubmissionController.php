<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        return $this->filteredIndex($request);
    }

    public function all(Request $request)
    {
        return $this->filteredIndex($request);
    }

    public function approve(int $id)
    {
        Submission::findOrFail($id)->update(['status' => 'approved']);

        return redirect()->back()->with('status', 'Submission approved.');
    }

    public function reject(Request $request, int $id)
    {
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:1000']]);

        Submission::findOrFail($id)->update([
            'status'      => 'rejected',
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);

        return redirect()->back()->with('status', 'Submission rejected.');
    }

    public function requestInfo(Request $request, int $id)
    {
        $data = $request->validate(['admin_notes' => ['required', 'string', 'max:1000']]);

        Submission::findOrFail($id)->update([
            'status'      => 'needs_info',
            'admin_notes' => $data['admin_notes'],
        ]);

        return redirect()->back()->with('status', 'Info requested — note saved.');
    }

    private function filteredIndex(Request $request)
    {
        $query = Submission::latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $submissions = $query->paginate(20)->withQueryString();
        $pendingCount = Submission::where('status', 'pending')->count();

        return view('submissions.index', compact('submissions', 'pendingCount'));
    }
}
