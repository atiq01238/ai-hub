{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($companies as $company)
        <url>
            <loc>{{ route('companies.show', ['company' => $company->slug]) }}</loc>
            @if($company->seo_lastmod)<lastmod>{{ $company->seo_lastmod->toAtomString() }}</lastmod>@endif
        </url>
    @endforeach
</urlset>
