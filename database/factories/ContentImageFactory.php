<?php

namespace Database\Factories;

use App\Models\ContentImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentImage>
 */
class ContentImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uploader_id' => User::factory(),
            'path' => fn (array $attributes): string => 'content/'.$attributes['uploader_id'].'/'.Str::lower((string) Str::ulid()).'.webp',
            'width' => 1200,
            'height' => 800,
        ];
    }
}
