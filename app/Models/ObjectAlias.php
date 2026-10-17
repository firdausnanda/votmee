<?php

namespace App\Models;

use Database\Factories\ObjectAliasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['object_id', 'category_id', 'alias', 'normalized_alias'])]
class ObjectAlias extends Model
{
    /** @use HasFactory<ObjectAliasFactory> */
    use HasFactory;

    public function object(): BelongsTo
    {
        return $this->belongsTo(MasterObject::class, 'object_id');
    }
}
