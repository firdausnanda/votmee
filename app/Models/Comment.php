<?php

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['ranking_post_id', 'user_id', 'parent_id', 'body', 'status'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;
}
