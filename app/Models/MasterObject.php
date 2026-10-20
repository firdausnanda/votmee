<?php

namespace App\Models;

use Database\Factories\MasterObjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Mattiverse\Userstamps\Traits\Userstamps;

#[Fillable(['category_id', 'brand_id', 'parent_id', 'submitted_by', 'verified_by', 'merged_into_id', 'canonical_name', 'normalized_name', 'slug', 'identity_attributes', 'image_path', 'pending_image_path', 'pending_image_by', 'status', 'created_by', 'updated_by'])]
class MasterObject extends Model
{
    /** @use HasFactory<MasterObjectFactory> */
    use HasFactory, HasUlids, Userstamps;

    protected $table = 'objects';

    protected function casts(): array
    {
        return ['identity_attributes' => 'array'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(ObjectAlias::class, 'object_id');
    }

    public function contentRevisions(): HasMany
    {
        return $this->hasMany(ObjectContentRevision::class, 'object_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(ObjectArticle::class, 'object_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'object_id');
    }

    /** @return Collection<int, string> */
    public function mergedFamilyIds(): Collection
    {
        $ids = collect([$this->id]);
        $frontier = [$this->id];

        while ($frontier !== []) {
            $frontier = self::query()->where('status', 'merged')->whereIn('merged_into_id', $frontier)->pluck('id')->all();
            $ids = $ids->merge($frontier);
        }

        return $ids;
    }
}
