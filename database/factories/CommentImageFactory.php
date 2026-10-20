<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\CommentImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CommentImage>
 */
class CommentImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'comment_id' => Comment::factory(),
            'path' => fn (array $attributes): string => 'comments/'.$attributes['comment_id'].'/'.Str::lower((string) Str::ulid()).'.webp',
            'width' => 1200,
            'height' => 900,
            'position' => 0,
        ];
    }
}
