<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;

#[Fillable(['reporter_id', 'target_type', 'target_id', 'reason', 'status', 'created_by', 'updated_by'])]
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory, Userstamps;

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
