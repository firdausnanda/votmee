<?php

namespace App\Services;

use App\Models\MasterObject;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ObjectMatchingService
{
    public function normalize(string $name): string
    {
        $name = Str::lower(trim($name));
        $name = preg_replace('/[^\pL\pN]+/u', ' ', $name) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $name) ?? '');
    }

    /** @return Collection<int, MasterObject> */
    public function search(string $query, array $identityAttributes = [], ?int $brandId = null): Collection
    {
        $normalized = $this->normalize($query);

        if ($normalized === '') {
            return collect();
        }

        $matches = MasterObject::query()
            ->whereIn('status', ['verified', 'pending'])
            ->where(function ($builder) use ($normalized) {
                $builder->where('normalized_name', 'like', $normalized.'%')
                    ->orWhereHas('aliases', fn ($aliases) => $aliases->where('normalized_alias', 'like', $normalized.'%'));
            })
            ->with('aliases')
            ->limit(30)
            ->get();

        if ($matches->count() < 10 && MasterObject::query()->getConnection()->getDriverName() === 'mysql') {
            $booleanQuery = implode(' ', array_map(fn (string $token): string => $token.'*', explode(' ', $normalized)));
            $fulltextMatches = MasterObject::query()
                ->whereIn('status', ['verified', 'pending'])
                ->where(function ($builder) use ($booleanQuery): void {
                    $builder->whereRaw('MATCH(canonical_name) AGAINST (? IN BOOLEAN MODE)', [$booleanQuery])
                        ->orWhereHas('aliases', fn ($aliases) => $aliases->whereRaw('MATCH(alias) AGAINST (? IN BOOLEAN MODE)', [$booleanQuery]));
                })
                ->with('aliases')
                ->limit(30)
                ->get();
            $matches = $matches->concat($fulltextMatches)->unique('id');
        }

        return $matches
            ->filter(function (MasterObject $object) use ($identityAttributes, $brandId): bool {
                if ($brandId !== null && $object->brand_id !== null && $object->brand_id !== $brandId) {
                    return false;
                }

                foreach ($identityAttributes as $key => $value) {
                    $knownValue = $object->identity_attributes[$key] ?? null;

                    if ($knownValue !== null && $this->normalize((string) $knownValue) !== $this->normalize((string) $value)) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(function (MasterObject $object) use ($normalized): int {
                if ($object->normalized_name === $normalized) {
                    return 3;
                }

                return $object->aliases->contains('normalized_alias', $normalized) ? 2 : 1;
            })
            ->values();
    }
}
