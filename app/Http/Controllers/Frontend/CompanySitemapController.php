<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Support\Carbon;

class CompanySitemapController extends Controller
{
    public function __invoke()
    {
        $companies = Company::query()
            ->whereIn('status', ['active', 'acquired'])
            // Keep empty/thin placeholder profiles out of the sitemap until they
            // have either public coverage or enough first-party profile data.
            ->where(function ($query) {
                $query->whereHas('tools', fn ($q) => $q->where('status', 'published'))
                    ->orWhereHas('models', fn ($q) => $q->whereIn('status', ['active', 'preview']))
                    ->orWhereHas('newsItems', fn ($q) => $q->where('status', 'published')->whereNull('duplicate_of_id')->where(fn ($news) => $news->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate')))
                    ->orWhereHas('articles', fn ($q) => $q->where('status', 'published')->where('approval_status', 'approved'))
                    ->orWhere(function ($profile) {
                        $profile->whereNotNull('website')
                            ->whereNotNull('description')
                            ->whereRaw('CHAR_LENGTH(TRIM(description)) >= 160');
                    });
            })
            ->withMax(['tools as tools_updated_at' => fn ($q) => $q->where('status', 'published')], 'updated_at')
            ->withMax(['models as models_updated_at' => fn ($q) => $q->whereIn('status', ['active', 'preview'])], 'updated_at')
            ->withMax(['newsItems as news_updated_at' => fn ($q) => $q->where('status', 'published')->whereNull('duplicate_of_id')->where(fn ($news) => $news->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'))], 'updated_at')
            ->withMax(['articles as articles_updated_at' => fn ($q) => $q->where('status', 'published')->where('approval_status', 'approved')], 'updated_at')
            ->orderBy('id')
            ->get()
            ->map(function ($company) {
                $company->seo_lastmod = collect([
                    $company->updated_at,
                    $company->tools_updated_at ? Carbon::parse($company->tools_updated_at) : null,
                    $company->models_updated_at ? Carbon::parse($company->models_updated_at) : null,
                    $company->news_updated_at ? Carbon::parse($company->news_updated_at) : null,
                    $company->articles_updated_at ? Carbon::parse($company->articles_updated_at) : null,
                ])->filter()->sortDesc()->first();

                return $company;
            });

        return response()
            ->view('frontend.companies.sitemap', compact('companies'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=900, s-maxage=900');
    }
}
