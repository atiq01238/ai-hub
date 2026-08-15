<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query()->withCount(['tools', 'models']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('website', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'acquired', 'inactive'], true)) {
            $query->where('status', $request->status);
        }

        $companies = $query->latest()->paginate(20)->withQueryString();

        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.form');
    }

    public function store(Request $request)
    {
        Company::create($this->fromRequest($request));

        return redirect()->route('admin.companies.index')->with('status', 'Company created.');
    }

    public function show(int $id)
    {
        $company = Company::withCount(['tools', 'models'])
            ->with(['tools' => fn ($q) => $q->latest()->limit(8), 'models' => fn ($q) => $q->latest()->limit(8)])
            ->findOrFail($id);

        return view('companies.show', compact('company'));
    }

    public function edit(int $id)
    {
        return view('companies.form', ['company' => Company::findOrFail($id)]);
    }

    public function update(Request $request, int $id)
    {
        $company = Company::findOrFail($id);
        $company->update($this->fromRequest($request, $company));

        return redirect()->route('admin.companies.show', $company->id)->with('status', 'Company updated.');
    }

    public function destroy(int $id)
    {
        $company = Company::withCount(['tools', 'models'])->findOrFail($id);

        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $company->delete();

        return redirect()->route('admin.companies.index')
            ->with('status', 'Company deleted. Related tools/models were kept and detached safely.');
    }

    private function fromRequest(Request $request, ?Company $company = null): array
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'website'      => ['nullable', 'url', 'max:255'],
            'status'       => ['required', Rule::in(['active', 'acquired', 'inactive'])],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:' . (date('Y') + 1)],
            'description'  => ['nullable', 'string'],
            'logo'         => ['nullable', 'image', 'max:2048'],
        ]);

        $baseSlug = Str::slug($data['name']) ?: 'company';
        $slug = $baseSlug;
        $counter = 2;
        while (Company::where('slug', $slug)->when($company, fn ($q) => $q->where('id', '!=', $company->id))->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }
        $data['slug'] = $slug;

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
