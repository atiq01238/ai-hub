<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DiscoverySource;
use App\Models\NewsSource;
use Illuminate\Http\Request;

class NewsSourceController extends Controller
{
    public function index()
    {
        $sources = NewsSource::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();

        return view('system.news-sources', compact('sources', 'companies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:150'],
            'type'              => ['required', 'in:rss,api'],
            'url'               => ['required', 'url', 'max:2048'],
            'default_category'  => ['nullable', 'string', 'max:100'],
            'company_id'        => ['nullable', 'exists:companies,id'],
        ]);

        $source = NewsSource::create($data);
        DiscoverySource::firstOrCreate(
            ['news_source_id' => $source->id],
            [
                'enabled' => true,
                'trusted' => !empty($source->company_id),
                'detect_tools' => true,
                'detect_models' => true,
                'minimum_confidence' => !empty($source->company_id) ? 50 : 60,
            ]
        );

        return redirect()->route('admin.system.news-sources')->with('status', 'Source added.');
    }

    public function toggle(int $id)
    {
        $source = NewsSource::findOrFail($id);
        $source->status = $source->status === 'active' ? 'inactive' : 'active';
        $source->save();

        return redirect()->back()->with('status', $source->name . ' is now ' . $source->status . '.');
    }

    public function destroy(int $id)
    {
        NewsSource::findOrFail($id)->delete();

        return redirect()->route('admin.system.news-sources')->with('status', 'Source removed.');
    }
}