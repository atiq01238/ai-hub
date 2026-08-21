<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Tool;
use App\Models\NewsItem;
use App\Models\Article;
use Illuminate\Http\Response;

class SeoSitemapController extends Controller
{
    public function tools(): Response
    {
        $items = Tool::query()->where('status','published')->select(['slug','updated_at'])->orderBy('id')->get();
        return $this->xml($items, fn($item)=>route('tools.show',$item));
    }

    public function models(): Response
    {
        $items = AiModel::query()->whereIn('status',['active','preview'])->select(['slug','updated_at'])->orderBy('id')->get();
        return $this->xml($items, fn($item)=>route('models.show',$item));
    }

    public function news(): Response
    {
        $items=NewsItem::query()->where('status','published')->whereNull('duplicate_of_id')->select(['slug','updated_at'])->orderByDesc('published_at')->get();
        return $this->xml($items, fn($item)=>route('news.show',$item));
    }

    public function articles(): Response
    {
        $items=Article::query()->where('status','published')->where('approval_status','approved')->select(['slug','updated_at'])->orderByDesc('published_at')->get();
        return $this->xml($items, fn($item)=>route('articles.show',$item));
    }

    private function xml($items, callable $url): Response
    {
        $body = view('frontend.sitemaps.entities', compact('items','url'))->render();
        return response($body, 200)->header('Content-Type','application/xml; charset=UTF-8');
    }
}
