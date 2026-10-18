<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;

#[Fillable(['slug', 'name', 'created_by', 'updated_by'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, Userstamps;

    public function rankingPosts(): HasMany
    {
        return $this->hasMany(RankingPost::class);
    }
}
