{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($items as $item)
<url><loc>{{ $url($item) }}</loc><lastmod>{{ $item->updated_at?->toAtomString() }}</lastmod></url>
@endforeach
</urlset>
