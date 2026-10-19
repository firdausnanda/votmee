<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Database\Seeder;

class RankingPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $admin = $users->firstWhere('role', 'admin') ?? $users->first();

        $postsData = [
            [
                'title' => 'Smartphone Flagship Terbaik 2026',
                'criteria' => 'Penilaian berdasarkan performa chipset, kualitas kamera malam, daya tahan baterai, dan dukungan sistem operasi jangka panjang.',
                'category_slug' => 'teknologi',
                'language_code' => 'id',
                'region_code' => 'ID',
                'objects' => ['iPhone 16 Pro Max', 'Samsung Galaxy S25 Ultra', 'Google Pixel 9 Pro', 'Xiaomi 15 Ultra'],
            ],
            [
                'title' => 'Destinasi Wisata Alam Terindah di Indonesia',
                'criteria' => 'Kategori dinilai dari keasrian alam, keunikan lanskap, keanekaragaman hayati, serta fasilitas penunjang bagi wisatawan.',
                'category_slug' => 'wisata',
                'language_code' => 'id',
                'region_code' => 'ID',
                'objects' => ['Raja Ampat Papua', 'Labuan Bajo Komodo', 'Ubud Bali', 'Gunung Bromo Tengger'],
            ],
            [
                'title' => 'Kuliner Nusantara Paling Lezat & Otentik',
                'criteria' => 'Kekayaan rempah-rempah, popularitas rasa di lidah masyarakat, keotentikan resep turun-temurun, serta daya tarik global.',
                'category_slug' => 'kuliner',
                'language_code' => 'id',
                'region_code' => 'ID',
                'objects' => ['Rendang Daging Padang', 'Sate Ayam Madura', 'Nasi Goreng Spesial', 'Bakso Malang Komplit'],
            ],
            [
                'title' => 'Mobil Listrik & Ramah Lingkungan Pilihan Keluarga',
                'criteria' => 'Jarak tempuh baterai, efisiensi konsumsi daya, kenyamanan suspensi kabin, dan ketersediaan jaringan SPKLU.',
                'category_slug' => 'otomotif',
                'language_code' => 'id',
                'region_code' => 'ID',
                'objects' => ['Hyundai Ioniq 5', 'Honda CR-V Hybrid', 'BYD Seal Performance', 'Toyota Innova Zenix Hybrid'],
            ],
            [
                'title' => 'Karya Sinematik & Film Indonesia Paling Berkesan',
                'criteria' => 'Kualitas sinematografi, kedalaman alur cerita, penokohan karakter, serta dampak budaya bagi penikmat seni tanah air.',
                'category_slug' => 'hiburan',
                'language_code' => 'id',
                'region_code' => 'ID',
                'objects' => ['Gadis Kretek', 'Laskar Pelangi', 'Pengabdi Setan', 'Petualangan Sherina 2'],
            ],
        ];

        $rankingService = app(RankingService::class);

        foreach ($postsData as $index => $data) {
            $category = Category::where('slug', $data['category_slug'])->first();

            if (! $category) {
                continue;
            }

            $objectIds = MasterObject::whereIn('canonical_name', $data['objects'])
                ->pluck('id')
                ->all();

            if (count($objectIds) < 2) {
                continue;
            }

            $creator = $users->get($index % $users->count()) ?? $admin;

            // Check if a post with similar title already exists
            $existing = RankingPost::where('title', $data['title'])->first();

            if (! $existing) {
                $rankingService->publish($creator, [
                    'title' => $data['title'],
                    'criteria' => $data['criteria'],
                    'category_id' => $category->id,
                    'language_code' => $data['language_code'],
                    'region_code' => $data['region_code'],
                    'object_ids' => $objectIds,
                ]);
            }
        }
    }
}
