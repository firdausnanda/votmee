<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\RankingPost;
use App\Services\SeoService;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends Controller
{
    private const PAGE_SIZE = 1000;

    public function robots(SeoService $seo): Response
    {
        return response("User-agent: *\nDisallow:\nSitemap: ".$seo->absoluteUrl('/sitemap.xml')."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function index(SeoService $seo): Response
    {
        $sitemaps = [$seo->absoluteUrl('/sitemaps/static.xml')];
        $counts = [
            'rankings' => RankingPost::query()->where('status', 'published')->count(),
            'objects' => $this->indexableObjects()->count(),
            'articles' => $this->indexableArticles()->count(),
        ];

        foreach ($counts as $type => $count) {
            for ($page = 1; $page <= (int) ceil($count / self::PAGE_SIZE); $page++) {
                $sitemaps[] = $seo->absoluteUrl('/sitemaps/'.$type.'/'.$page.'.xml');
            }
        }

        $entries = collect($sitemaps)->map(fn (string $url): string => '<sitemap><loc>'.$this->escape($url).'</loc></sitemap>')->implode('');

        return $this->xml('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$entries.'</sitemapindex>');
    }

    public function staticPages(SeoService $seo): Response
    {
        $urls = [];

        foreach (['id', 'en'] as $locale) {
            $urls[] = $this->urlEntry($seo->url($locale));
            $urls[] = $this->urlEntry($seo->url($locale, '/explore'));

            foreach (['/privacy', '/terms', '/support'] as $path) {
                $urls[] = $this->urlEntry($seo->url($locale, $path));
            }
        }

        return $this->urlSet($urls);
    }

    public function content(string $type, int $page, SeoService $seo): Response
    {
        abort_unless(in_array($type, ['rankings', 'objects', 'articles'], true) && $page >= 1, 404);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $urls = [];

        if ($type === 'rankings') {
            $posts = RankingPost::query()->where('status', 'published')->orderBy('id')->skip($offset)->take(self::PAGE_SIZE)->get();

            foreach ($posts as $post) {
                $urls[] = $this->urlEntry($seo->url($seo->locale($post->language_code), '/ranking/'.$post->slug), $post->updated_at?->toIso8601String());
            }
        } elseif ($type === 'objects') {
            $objects = $this->indexableObjects()->orderBy('id')->skip($offset)->take(self::PAGE_SIZE)->get();
            $summaries = ObjectContentRevision::query()->whereIn('object_id', $objects->pluck('id'))
                ->whereNull('article_id')->where('status', 'published')->orderByDesc('published_at')->orderByDesc('id')->get()
                ->unique('object_id')->keyBy('object_id');

            foreach ($objects as $object) {
                $summary = $summaries->get($object->id);
                $urls[] = $this->urlEntry($seo->url($seo->locale($summary->language_code), '/object/'.$object->slug), $summary->published_at?->toIso8601String());
            }
        } else {
            $articles = $this->indexableArticles()->with('publishedRevision')->orderBy('id')->skip($offset)->take(self::PAGE_SIZE)->get();

            foreach ($articles as $article) {
                $revision = $article->publishedRevision;
                $urls[] = $this->urlEntry($seo->url($seo->locale($revision->language_code), '/article/'.$article->id), $revision->published_at?->toIso8601String());
            }
        }

        abort_if($urls === [], 404);

        return $this->urlSet($urls);
    }

    private function indexableObjects(): Builder
    {
        return MasterObject::query()->where('status', 'verified')
            ->whereHas('contentRevisions', fn ($query) => $query->whereNull('article_id')->where('status', 'published'));
    }

    private function indexableArticles(): Builder
    {
        return ObjectArticle::query()->whereHas('object', fn ($query) => $query->whereIn('status', ['verified', 'merged']))
            ->whereHas('publishedRevision', fn ($query) => $query->where('status', 'published')->whereColumn('article_id', 'object_articles.id'));
    }

    private function urlEntry(string $url, ?string $lastModified = null): string
    {
        return '<url><loc>'.$this->escape($url).'</loc>'.($lastModified ? '<lastmod>'.$this->escape($lastModified).'</lastmod>' : '').'</url>';
    }

    /** @param array<int, string> $urls */
    private function urlSet(array $urls): Response
    {
        return $this->xml('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.implode('', $urls).'</urlset>');
    }

    private function xml(string $content): Response
    {
        return response($content, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
