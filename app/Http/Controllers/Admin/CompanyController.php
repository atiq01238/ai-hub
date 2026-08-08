<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $companies = Company::withCount('tools')
            ->latest()
            ->paginate(20);

        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.form');
    }

    public function store(Request $request)
    {
        Company::create($this->fromRequest($request));

        return redirect()
            ->route('admin.companies.index')
            ->with('status', 'Company created.');
    }

    public function show(int $id)
    {
        $company = Company::withCount('tools')->findOrFail($id);

        return view('companies.show', compact('company'));
    }

    public function edit(int $id)
    {
        $company = Company::findOrFail($id);

        return view('companies.form', compact('company'));
    }

    public function update(Request $request, int $id)
    {
        $company = Company::findOrFail($id);
        $company->update($this->fromRequest($request, $company));

        return redirect()
            ->route('admin.companies.show', $company->id)
            ->with('status', 'Company updated.');
    }

    public function destroy(int $id)
    {
        $company = Company::findOrFail($id);

        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $company->delete();

        return redirect()
            ->route('admin.companies.index')
            ->with('status', 'Company deleted.');
    }

    private function fromRequest(Request $request, ?Company $company = null): array
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'website'      => ['nullable', 'url', 'max:255'],
            'status'       => ['required', 'in:active,acquired,inactive'],
            'founded_year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'description'  => ['nullable', 'string'],
            'logo'         => ['nullable', 'image', 'max:2048'],
        ]);

        $data['slug'] = Str::slug($data['name']);

        if ($request->hasFile('logo')) {
            if ($company?->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('companies', 'public');
        }
        unset($data['logo']);

        return $data;
    }
}
