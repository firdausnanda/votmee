<?php

namespace App\Services;

use App\Models\MasterObject;
use App\Models\ObjectContentRevision;

class SeoService
{
    public function locale(?string $languageCode): string
    {
        return in_array($languageCode, ['id', 'en'], true) ? $languageCode : 'id';
    }

    public function url(string $locale, string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/').'/'.$locale.$path;
    }

    public function absoluteUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, string>  $alternates
     * @param  array<int, array<string, mixed>>  $structuredData
     * @return array<string, mixed>
     */
    public function page(
        string $locale,
        string $path,
        string $title,
        string $description,
        bool $index = true,
        array $alternates = [],
        ?string $imagePath = null,
        array $structuredData = [],
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $this->url($locale, $path),
            'locale' => $locale,
            'robots' => $index ? 'index, follow' : 'noindex, follow',
            'alternates' => $alternates,
            'image' => $imagePath ? $this->absoluteUrl($imagePath) : null,
            'structuredData' => $structuredData,
        ];
    }

    public function publishedSummary(MasterObject $object): ?ObjectContentRevision
    {
        if ($object->status !== 'verified') {
            return null;
        }

        return ObjectContentRevision::query()
            ->where('object_id', $object->id)
            ->whereNull('article_id')
            ->where('status', 'published')
            ->latest('published_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ], $items, array_keys($items)),
        ];
    }
}
