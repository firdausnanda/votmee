<?php

namespace App\Models;

use Database\Factories\MissionCompletionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'mission', 'completed_on', 'amount', 'claimed_at'])]
class MissionCompletion extends Model
{
    /** @use HasFactory<MissionCompletionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'completed_on' => 'date',
            'claimed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
