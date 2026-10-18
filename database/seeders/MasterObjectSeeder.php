<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MasterObject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterObjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();

        $objectGroups = [
            'teknologi' => [
                'iPhone 16 Pro Max',
                'Samsung Galaxy S25 Ultra',
                'Google Pixel 9 Pro',
                'Xiaomi 15 Ultra',
                'Asus ROG Phone 9',
                'MacBook Pro M4',
            ],
            'wisata' => [
                'Raja Ampat Papua',
                'Labuan Bajo Komodo',
                'Ubud Bali',
                'Gunung Bromo Tengger',
                'Danau Toba Samosir',
            ],
            'kuliner' => [
                'Rendang Daging Padang',
                'Sate Ayam Madura',
                'Nasi Goreng Spesial',
                'Bakso Malang Komplit',
                'Soto Betawi Kuah Susu',
            ],
            'otomotif' => [
                'Hyundai Ioniq 5',
                'Honda CR-V Hybrid',
                'BYD Seal Performance',
                'Toyota Innova Zenix Hybrid',
                'Wuling Binguo EV',
            ],
            'hiburan' => [
                'Gadis Kretek',
                'Laskar Pelangi',
                'Pengabdi Setan',
                'Petualangan Sherina 2',
            ],
        ];

        foreach ($objectGroups as $categorySlug => $names) {
            $category = Category::where('slug', $categorySlug)->first();

            if (! $category) {
                continue;
            }

            foreach ($names as $name) {
                $slug = Str::slug($name);
                $normalized = strtolower(trim($name));

                $existing = MasterObject::where('category_id', $category->id)
                    ->where('normalized_name', $normalized)
                    ->first();

                if (! $existing) {
                    MasterObject::create([
                        'id' => (string) Str::ulid(),
                        'category_id' => $category->id,
                        'canonical_name' => $name,
                        'normalized_name' => $normalized,
                        'slug' => $slug,
                        'status' => 'verified',
                        'submitted_by' => $admin?->id,
                        'verified_by' => $admin?->id,
                        'created_by' => $admin?->id,
                        'updated_by' => $admin?->id,
                    ]);
                }
            }
        }
    }
}
