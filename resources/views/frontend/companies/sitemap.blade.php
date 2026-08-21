{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('companies.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    @foreach($companies as $company)
        <url>
            <loc>{{ route('companies.show', ['company' => $company->slug]) }}</loc>
            @if($company->seo_lastmod)<lastmod>{{ $company->seo_lastmod->toAtomString() }}</lastmod>@endif
            <changefreq>weekly</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach
</urlset>
