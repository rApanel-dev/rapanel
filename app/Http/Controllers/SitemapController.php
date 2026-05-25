<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $static = [
            ['loc' => url('/'),                'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => url('/info/item-db'),    'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => url('/info/mob-db'),     'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => url('/info/mvp-card'),   'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => url('/info/map-db'),     'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => url('/info/who-sell'),   'priority' => '0.5', 'changefreq' => 'hourly'],
            ['loc' => url('/downloads'),       'priority' => '0.6', 'changefreq' => 'weekly'],
        ];

        $news = [];
        if (class_exists(News::class)) {
            $news = News::select('slug', 'updated_at')
                ->orderByDesc('updated_at')
                ->limit(500)
                ->get()
                ->map(fn($n) => [
                    'loc'        => url("/news/{$n->slug}"),
                    'lastmod'    => $n->updated_at?->toAtomString(),
                    'priority'   => '0.6',
                    'changefreq' => 'monthly',
                ])->all();
        }

        $xml = view('sitemap', ['urls' => array_merge($static, $news)]);

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
