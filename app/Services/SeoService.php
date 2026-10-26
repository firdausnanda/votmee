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
     * Alternate URLs of a page that exists in every supported language, with Indonesian as the default.
     *
     * @return array{id: string, en: string, x-default: string}
     */
    public function localeAlternates(string $path = ''): array
    {
        return [
            'id' => $this->url('id', $path),
            'en' => $this->url('en', $path),
            'x-default' => $this->url('id', $path),
        ];
    }

    /**
     * @param  array<int, array{position: int, name: string, url?: string}>  $items
     * @return array<string, mixed>
     */
    public function itemList(string $name, array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $name,
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'numberOfItems' => count($items),
            'itemListElement' => array_map(fn (array $item): array => ['@type' => 'ListItem', ...$item], $items),
        ];
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
