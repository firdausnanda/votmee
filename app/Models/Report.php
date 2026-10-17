<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['reporter_id', 'target_type', 'target_id', 'reason', 'status'])]
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;
}
