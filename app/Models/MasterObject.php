<?php

namespace App\Models;

use Database\Factories\MasterObjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;

#[Fillable(['category_id', 'brand_id', 'parent_id', 'submitted_by', 'verified_by', 'merged_into_id', 'canonical_name', 'normalized_name', 'slug', 'identity_attributes', 'status', 'created_by', 'updated_by'])]
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
}
