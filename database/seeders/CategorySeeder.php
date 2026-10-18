<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();

        $categories = [
            ['name' => 'Teknologi', 'slug' => 'teknologi'],
            ['name' => 'Wisata', 'slug' => 'wisata'],
            ['name' => 'Kuliner', 'slug' => 'kuliner'],
            ['name' => 'Otomotif', 'slug' => 'otomotif'],
            ['name' => 'Hiburan', 'slug' => 'hiburan'],
            ['name' => 'Gadget', 'slug' => 'gadget'],
            ['name' => 'Pendidikan', 'slug' => 'pendidikan'],
            ['name' => 'Finansial', 'slug' => 'finansial'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ]
            );
        }
    }
}
