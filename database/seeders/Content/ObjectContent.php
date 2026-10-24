<?php

namespace Database\Seeders\Content;

/**
 * Hand-written encyclopedia content for the demo objects: community summaries, articles and
 * the discussions under them, written by the same members as the rankings in CommunityContent.
 */
class ObjectContent
{
    /**
     * Summaries per object, oldest revision first. `days_ago` is when the revision was written;
     * each revision carries its moderation `status` and, once reviewed, the moderator's `reason`.
     *
     * @return list<array{object: string, revisions: list<array<string, mixed>>}>
     */
    public static function summaries(): array
    {
        return [
            ['object' => 'iPhone 16 Pro Max', 'revisions' => [
                ['by' => 'bagaswicak', 'days_ago' => 20, 'status' => 'published', 'language' => 'id',
                    'body' => 'iPhone 16 Pro Max adalah ponsel flagship Apple yang dirilis pada September 2024. Ponsel ini memakai chip A18 Pro, layar Super Retina XDR 6,9 inci dengan ProMotion 120 Hz, serta bodi berbahan titanium. Kamera utamanya 48 MP dengan telefoto 5x, dan untuk pertama kalinya hadir tombol Camera Control di sisi kanan.',
                    'sources' => ['https://www.apple.com/id/iphone-16-pro/specs/']],
                ['by' => 'putrimhrn', 'days_ago' => 9, 'status' => 'published', 'language' => 'id', 'format' => 'markdown',
                    'body' => "iPhone 16 Pro Max adalah ponsel flagship Apple yang dirilis pada September 2024.\n\n- **Chip:** A18 Pro (3 nm)\n- **Layar:** Super Retina XDR 6,9 inci, ProMotion 120 Hz, Always-On\n- **Kamera:** utama 48 MP, ultra wide 48 MP, telefoto 5x 12 MP\n- **Bodi:** titanium grade 5, tombol Action dan Camera Control\n\nApple menyebut baterainya sebagai yang terlama di jajaran iPhone saat peluncuran, hingga 33 jam pemutaran video. Perekaman video 4K 120 fps dalam Dolby Vision menjadi salah satu nilai jual utamanya bagi kreator konten.",
                    'sources' => ['https://www.apple.com/id/iphone-16-pro/specs/', 'https://www.apple.com/newsroom/2024/09/apple-debuts-iphone-16-pro-and-iphone-16-pro-max/']],
            ]],
            ['object' => 'Samsung Galaxy S25 Ultra', 'revisions' => [
                ['by' => 'bagaswicak', 'days_ago' => 19, 'status' => 'published', 'language' => 'id',
                    'body' => 'Samsung Galaxy S25 Ultra adalah ponsel flagship Samsung yang diumumkan pada Januari 2025. Ponsel ini memakai Snapdragon 8 Elite for Galaxy, layar Dynamic AMOLED 2X 6,9 inci, kamera utama 200 MP dengan telefoto 5x 50 MP, dan S Pen bawaan. Samsung menjanjikan tujuh tahun pembaruan sistem operasi dan keamanan.',
                    'sources' => ['https://www.samsung.com/id/smartphones/galaxy-s25-ultra/specs/']],
            ]],
            ['object' => 'Google Pixel 9 Pro', 'revisions' => [
                ['by' => 'rizkyhdyt', 'days_ago' => 18, 'status' => 'needs_revision', 'language' => 'id',
                    'reason' => 'Isi ringkasan masih berupa opini pribadi. Mohon tulis fakta spesifikasinya dan sertakan sumber resmi.',
                    'body' => 'Pixel 9 Pro itu HP kamera terbaik, warnanya paling natural dibanding merek lain. Cocok buat yang suka foto.',
                    'sources' => ['https://www.youtube.com/watch?v=pixel9review']],
                ['by' => 'bagaswicak', 'days_ago' => 16, 'status' => 'published', 'language' => 'id',
                    'body' => 'Google Pixel 9 Pro adalah ponsel flagship Google yang dirilis pada Agustus 2024. Ponsel ini memakai chip Google Tensor G4, layar Super Actua 6,3 inci, RAM 16 GB, dan tiga kamera belakang 50 MP + 48 MP ultra wide + 48 MP telefoto 5x. Pixel 9 Pro mendapat tujuh tahun pembaruan OS dan fitur AI Gemini bawaan.',
                    'sources' => ['https://store.google.com/product/pixel_9_pro_specs']],
            ]],
            ['object' => 'Xiaomi 15 Ultra', 'revisions' => [
                ['by' => 'bagaswicak', 'days_ago' => 15, 'status' => 'published', 'language' => 'id',
                    'body' => 'Xiaomi 15 Ultra adalah ponsel flagship Xiaomi yang dirilis global pada Maret 2025. Sistem kameranya dikembangkan bersama Leica, dengan sensor utama 1 inci 50 MP dan telefoto periskop 200 MP. Ponsel ini memakai Snapdragon 8 Elite dan baterai 5.410 mAh dengan pengisian kabel 90 W.',
                    'sources' => ['https://www.mi.co.id/id/product/xiaomi-15-ultra/specs/']],
            ]],
            ['object' => 'Poco F7', 'revisions' => [
                ['by' => 'fajarrmd', 'days_ago' => 12, 'status' => 'published', 'language' => 'id',
                    'body' => 'Poco F7 adalah ponsel kelas menengah atas dari Poco, sub-merek Xiaomi, yang menyasar pengguna yang mementingkan performa. Ponsel ini memakai Snapdragon 8s Gen 4, baterai 6.500 mAh dengan pengisian 90 W, dan layar AMOLED 6,83 inci 120 Hz.',
                    'sources' => ['https://www.po.co/id/product/poco-f7/specs/']],
            ]],
            ['object' => 'Poco X7 Pro', 'revisions' => [
                ['by' => 'yogaaditya', 'days_ago' => 13, 'status' => 'published', 'language' => 'id',
                    'body' => 'Poco X7 Pro adalah ponsel kelas menengah Poco yang diluncurkan pada Januari 2025. Ponsel ini memakai MediaTek Dimensity 8400-Ultra, layar AMOLED 6,67 inci 120 Hz, dan baterai 6.000 mAh. Di kelas harganya, X7 Pro dikenal karena skor performa yang tinggi untuk gaming.',
                    'sources' => ['https://www.po.co/id/product/poco-x7-pro/specs/']],
            ]],
            ['object' => 'Asus ROG Phone 9', 'revisions' => [
                ['by' => 'bagaswicak', 'days_ago' => 12, 'status' => 'published', 'language' => 'id',
                    'body' => 'Asus ROG Phone 9 adalah ponsel gaming dari lini Republic of Gamers milik Asus yang diumumkan pada November 2024. Ponsel ini memakai Snapdragon 8 Elite, layar AMOLED 6,78 inci 185 Hz, tombol sentuh AirTrigger di sisi bodi, dan panel AniMe Vision berisi LED mini di bagian belakang.',
                    'sources' => ['https://rog.asus.com/id/phones/rog-phone-9/spec/']],
            ]],
            ['object' => 'MacBook Air M4', 'revisions' => [
                ['by' => 'intanpermata', 'days_ago' => 9, 'status' => 'published', 'language' => 'id',
                    'body' => 'MacBook Air M4 adalah laptop tipis Apple yang dirilis pada Maret 2025, tersedia dalam ukuran 13 dan 15 inci. Laptop ini memakai chip Apple M4 dengan CPU 10 inti, RAM mulai 16 GB, desain tanpa kipas, dan daya tahan baterai hingga 18 jam menurut Apple.',
                    'sources' => ['https://www.apple.com/id/macbook-air/specs/']],
            ]],
            ['object' => 'Lenovo ThinkPad E14', 'revisions' => [
                ['by' => 'galihpratama', 'days_ago' => 8, 'status' => 'pending', 'language' => 'id',
                    'body' => 'Lenovo ThinkPad E14 adalah laptop bisnis kelas menengah dari seri ThinkPad Essential. Laptop ini dikenal dengan keyboard yang nyaman, TrackPoint merah khas ThinkPad, serta pilihan prosesor Intel Core Ultra atau AMD Ryzen. Layarnya 14 inci dengan rasio 16:10.',
                    'sources' => ['https://www.lenovo.com/id/in/p/laptops/thinkpad/thinkpade/thinkpad-e14-gen-6-14-inch-intel/len101t0098']],
            ]],
            ['object' => 'Raja Ampat Papua', 'revisions' => [
                ['by' => 'salsa.nur', 'days_ago' => 22, 'status' => 'published', 'language' => 'id', 'format' => 'markdown',
                    'body' => "Raja Ampat adalah kepulauan di Provinsi Papua Barat Daya yang terdiri dari lebih dari 1.500 pulau kecil. Wilayah ini berada di jantung **Segitiga Terumbu Karang** dan tercatat memiliki lebih dari 550 spesies karang serta 1.400 spesies ikan karang.\n\nEmpat pulau utamanya adalah Waigeo, Batanta, Salawati, dan Misool. Akses umum dari Sorong menggunakan kapal cepat ke Waisai. Pengunjung wajib membayar Pin Konservasi yang dananya dipakai untuk pengelolaan kawasan.",
                    'sources' => ['https://www.indonesia.travel/id/id/destinasi/papua/raja-ampat.html', 'https://unesdoc.unesco.org/ark:/48223/pf0000372300']],
            ]],
            ['object' => 'Labuan Bajo Komodo', 'revisions' => [
                ['by' => 'salsa.nur', 'days_ago' => 20, 'status' => 'published', 'language' => 'id',
                    'body' => 'Labuan Bajo adalah kota di ujung barat Pulau Flores, Nusa Tenggara Timur, yang menjadi pintu masuk Taman Nasional Komodo. Taman nasional ini ditetapkan sebagai Situs Warisan Dunia UNESCO pada 1991 dan menjadi habitat asli komodo. Wisata populernya antara lain trekking di Pulau Padar, Pink Beach, dan snorkeling di Manta Point.',
                    'sources' => ['https://whc.unesco.org/en/list/609/', 'https://www.indonesia.travel/id/id/destinasi/bali-nusa-tenggara/labuan-bajo.html']],
            ]],
            ['object' => 'Gunung Bromo Tengger', 'revisions' => [
                ['by' => 'salsa.nur', 'days_ago' => 17, 'status' => 'published', 'language' => 'id',
                    'body' => 'Gunung Bromo adalah gunung api aktif setinggi 2.329 mdpl di Jawa Timur, bagian dari Taman Nasional Bromo Tengger Semeru. Bromo terkenal dengan lautan pasir dan pemandangan matahari terbit dari Penanjakan. Masyarakat Tengger menggelar upacara Yadnya Kasada setiap tahun di kawah Bromo.',
                    'sources' => ['https://bromotenggersemeru.org/']],
                ['by' => 'wulansari', 'days_ago' => 6, 'status' => 'rejected', 'language' => 'id',
                    'reason' => 'Perubahan menghapus sebagian besar isi dan menambahkan promosi jasa tur. Ringkasan sebelumnya tetap dipakai.',
                    'body' => 'Bromo bagus banget buat sunrise. Paket open trip Bromo murah mulai 250 ribu, hubungi kami untuk jadwal minggu ini.',
                    'sources' => ['https://bromotenggersemeru.org/']],
            ]],
            ['object' => 'Danau Toba Samosir', 'revisions' => [
                ['by' => 'salsa.nur', 'days_ago' => 14, 'status' => 'published', 'language' => 'id',
                    'body' => 'Danau Toba adalah danau vulkanik terbesar di Indonesia dan Asia Tenggara, terletak di Sumatera Utara. Danau ini terbentuk dari letusan supervulkan sekitar 74.000 tahun lalu. Di tengahnya terdapat Pulau Samosir, pusat budaya Batak Toba dengan desa adat seperti Tomok dan Huta Siallagan. Kaldera Toba ditetapkan sebagai UNESCO Global Geopark pada 2020.',
                    'sources' => ['https://www.unesco.org/en/iggp/geoparks/toba-caldera']],
            ]],
            ['object' => 'Ubud Bali', 'revisions' => [
                ['by' => 'citradewi', 'days_ago' => 11, 'status' => 'published', 'language' => 'id',
                    'body' => 'Ubud adalah kawasan di Kabupaten Gianyar, Bali, yang dikenal sebagai pusat seni dan budaya Bali. Daya tariknya meliputi sawah terasering Tegallalang, Monkey Forest, Pasar Seni Ubud, serta pertunjukan tari tradisional di Puri Saren. Ubud juga populer untuk wisata yoga dan kuliner sehat.',
                    'sources' => ['https://www.indonesia.travel/id/id/destinasi/bali-nusa-tenggara/ubud.html']],
            ]],
            ['object' => 'Lembang Bandung', 'revisions' => [
                ['by' => 'radityakrn', 'days_ago' => 5, 'status' => 'published', 'language' => 'id',
                    'body' => 'Lembang adalah kecamatan di Kabupaten Bandung Barat yang berada di dataran tinggi sekitar 1.300 mdpl. Udara sejuknya menjadikan Lembang tujuan akhir pekan favorit warga Jakarta dan Bandung. Tempat populernya antara lain Gunung Tangkuban Perahu, Farmhouse, Floating Market, dan Observatorium Bosscha.',
                    'sources' => ['https://www.bandungbaratkab.go.id/']],
            ]],
            ['object' => 'Kepulauan Seribu', 'revisions' => [
                ['by' => 'demouser', 'days_ago' => 4, 'status' => 'published', 'language' => 'id',
                    'body' => 'Kepulauan Seribu adalah kabupaten administrasi di Provinsi DKI Jakarta yang terdiri dari sekitar 110 pulau di Teluk Jakarta. Pulau yang sering dikunjungi wisatawan antara lain Pulau Tidung, Pulau Pari, Pulau Harapan, dan Pulau Pramuka. Akses umumnya lewat kapal dari Pelabuhan Muara Angke atau Marina Ancol.',
                    'sources' => ['https://pulauseribu.jakarta.go.id/']],
            ]],
            ['object' => 'Rendang Daging Padang', 'revisions' => [
                ['by' => 'rinakartika', 'days_ago' => 17, 'status' => 'published', 'language' => 'id', 'format' => 'markdown',
                    'body' => "Rendang adalah masakan daging khas Minangkabau, Sumatera Barat, yang dimasak lama dengan santan dan bumbu rempah hingga kering.\n\n**Bumbu utama:** cabai, bawang merah, bawang putih, jahe, lengkuas, kunyit, serai, daun jeruk, dan daun kunyit.\n\nProses memasaknya bisa memakan waktu 4 sampai 8 jam. Rendang yang benar-benar kering dapat bertahan berminggu-minggu tanpa pendingin, sehingga dulu menjadi bekal perantau Minang. Rendang beberapa kali masuk daftar makanan terlezat dunia versi CNN Travel.",
                    'sources' => ['https://warisanbudaya.kemdikbud.go.id/?newdetail&detailCatat=1340']],
            ]],
            ['object' => 'Sate Ayam Madura', 'revisions' => [
                ['by' => 'rinakartika', 'days_ago' => 16, 'status' => 'published', 'language' => 'id',
                    'body' => 'Sate ayam Madura adalah sate potongan daging ayam yang dibakar di atas arang lalu disajikan dengan bumbu kacang, kecap manis, irisan bawang merah, dan lontong. Hidangan ini berasal dari Pulau Madura, Jawa Timur, dan kini dijajakan di hampir seluruh kota di Indonesia oleh pedagang asal Madura.',
                    'sources' => ['https://www.indonesia.travel/id/id/ide-liburan/kuliner-khas-madura.html']],
            ]],
            ['object' => 'Bakso Malang Komplit', 'revisions' => [
                ['by' => 'rinakartika', 'days_ago' => 13, 'status' => 'published', 'language' => 'id',
                    'body' => 'Bakso Malang adalah variasi bakso khas Kota Malang, Jawa Timur. Selain bola daging, satu porsi komplit biasanya berisi siomay, tahu goreng, tahu isi, pangsit goreng, dan mi, disiram kuah kaldu sapi bening. Penyajian yang beragam inilah yang membedakannya dari bakso Solo atau bakso Wonogiri.',
                    'sources' => ['https://malangkota.go.id/']],
            ]],
            ['object' => 'Bubur Ayam', 'revisions' => [
                ['by' => 'nadiasaf', 'days_ago' => 8, 'status' => 'published', 'language' => 'id',
                    'body' => 'Bubur ayam adalah bubur beras gurih yang disajikan dengan suwiran ayam, cakwe, kerupuk, bawang goreng, daun bawang, kecap asin, dan sambal. Hidangan ini menjadi menu sarapan populer di berbagai kota di Indonesia. Gaya penyajiannya beragam, misalnya bubur Cianjur yang berkuah kuning dan bubur Jakarta yang lebih kental.',
                    'sources' => ['https://id.wikipedia.org/wiki/Bubur_ayam']],
            ]],
            ['object' => 'Nasi Uduk Betawi', 'revisions' => [
                ['by' => 'rinakartika', 'days_ago' => 7, 'status' => 'published', 'language' => 'id',
                    'body' => 'Nasi uduk Betawi adalah nasi yang dimasak dengan santan, serai, daun salam, dan lengkuas sehingga beraroma gurih. Nasi ini biasa disajikan dengan semur jengkol, ayam goreng, telur balado, bihun goreng, emping, dan sambal kacang. Nasi uduk merupakan kuliner khas masyarakat Betawi di Jakarta.',
                    'sources' => ['https://warisanbudaya.kemdikbud.go.id/']],
            ]],
            ['object' => 'Hyundai Ioniq 5', 'revisions' => [
                ['by' => 'fajarrmd', 'days_ago' => 15, 'status' => 'published', 'language' => 'id',
                    'body' => 'Hyundai Ioniq 5 adalah mobil listrik crossover yang dibangun di atas platform E-GMP. Di Indonesia, Ioniq 5 dirakit di pabrik Hyundai Cikarang sejak 2022 dan tersedia dengan pilihan baterai Standard Range dan Long Range. Mobil ini mendukung pengisian cepat 800 V dan fitur Vehicle-to-Load untuk menyalurkan listrik ke perangkat lain.',
                    'sources' => ['https://www.hyundai.com/id/id/find-a-car/ioniq-5/highlights']],
            ]],
            ['object' => 'BYD Atto 3', 'revisions' => [
                ['by' => 'fajarrmd', 'days_ago' => 14, 'status' => 'published', 'language' => 'id',
                    'body' => 'BYD Atto 3 adalah SUV listrik kompak dari produsen asal Tiongkok, BYD, yang mulai dipasarkan di Indonesia pada awal 2024. Mobil ini memakai Blade Battery berbasis LFP dengan jarak tempuh hingga sekitar 480 km (NEDC) untuk varian Extended Range.',
                    'sources' => ['https://www.byd.com/id/car/atto3']],
            ]],
            ['object' => 'Toyota Innova Zenix Hybrid', 'revisions' => [
                ['by' => 'rizkyhdyt', 'days_ago' => 3, 'status' => 'published', 'language' => 'id',
                    'body' => 'Toyota Innova Zenix Hybrid adalah MPV generasi ketiga dari keluarga Innova yang diluncurkan pada November 2022. Berbeda dari generasi sebelumnya, Zenix memakai platform TNGA-C monokok dan penggerak roda depan. Varian hybrid memadukan mesin bensin 2.0 liter dengan motor listrik dan diproduksi di Karawang.',
                    'sources' => ['https://www.toyota.astra.co.id/product/innova-zenix']],
            ]],
            ['object' => 'Gadis Kretek', 'revisions' => [
                ['by' => 'putrimhrn', 'days_ago' => 11, 'status' => 'published', 'language' => 'id',
                    'body' => 'Gadis Kretek adalah serial drama Indonesia yang tayang di Netflix pada November 2023, diadaptasi dari novel karya Ratih Kumala. Serial lima episode ini disutradarai Kamila Andini dan Ifa Isfansyah, dibintangi Dian Sastrowardoyo dan Ario Bayu, dan berlatar industri kretek di Jawa pada 1960-an.',
                    'sources' => ['https://www.netflix.com/title/81415970']],
            ]],
            ['object' => 'Pengabdi Setan', 'revisions' => [
                ['by' => 'putrimhrn', 'days_ago' => 10, 'status' => 'published', 'language' => 'id',
                    'body' => 'Pengabdi Setan adalah film horor Indonesia 2017 karya sutradara Joko Anwar, sebuah remake dari film berjudul sama tahun 1980. Film ini menjadi salah satu film horor Indonesia terlaris dan dilanjutkan dengan Pengabdi Setan 2: Communion pada 2022.',
                    'sources' => ['https://www.imdb.com/title/tt7076834/']],
            ]],
            ['object' => 'GoPay', 'revisions' => [
                ['by' => 'mayaanggr', 'days_ago' => 7, 'status' => 'published', 'language' => 'id',
                    'body' => 'GoPay adalah layanan dompet digital milik grup GoTo yang terintegrasi dengan aplikasi Gojek dan Tokopedia, serta tersedia sebagai aplikasi GoPay tersendiri. GoPay terdaftar dan diawasi oleh Bank Indonesia dan dapat dipakai untuk pembayaran QRIS, transfer, serta tagihan.',
                    'sources' => ['https://gopay.co.id/']],
            ]],
            ['object' => 'DANA', 'revisions' => [
                ['by' => 'mayaanggr', 'days_ago' => 6, 'status' => 'published', 'language' => 'id',
                    'body' => 'DANA adalah dompet digital asal Indonesia yang diluncurkan pada 2018. DANA mendukung pembayaran QRIS, transfer ke rekening bank, pembayaran tagihan, dan pembelian pulsa. Layanannya berizin dan diawasi Bank Indonesia.',
                    'sources' => ['https://www.dana.id/']],
            ]],
            ['object' => 'Dicoding', 'revisions' => [
                ['by' => 'lestarin', 'days_ago' => 4, 'status' => 'published', 'language' => 'id',
                    'body' => 'Dicoding adalah platform belajar pemrograman asal Bandung yang didirikan pada 2015. Kelasnya mencakup pengembangan Android, web, machine learning, dan cloud, banyak di antaranya disusun bersama mitra industri seperti Google dan AWS. Dicoding juga menjadi penyelenggara program beasiswa seperti Bangkit.',
                    'sources' => ['https://www.dicoding.com/about']],
            ]],
            ['object' => 'Sony WH-1000XM6', 'revisions' => [
                ['by' => 'emmacarter', 'days_ago' => 10, 'status' => 'published', 'language' => 'en',
                    'body' => 'The Sony WH-1000XM6 is a wireless over-ear headphone with active noise cancelling, released in May 2025. It uses Sony\'s HD Noise Cancelling Processor QN3, twelve microphones for noise cancelling and calls, and returns to a folding design. Sony rates battery life at up to 30 hours with noise cancelling on.',
                    'sources' => ['https://electronics.sony.com/audio/headphones/headband/p/wh1000xm6-b']],
            ]],
            ['object' => 'Bose QuietComfort Ultra Headphones', 'revisions' => [
                ['by' => 'emmacarter', 'days_ago' => 9, 'status' => 'published', 'language' => 'en',
                    'body' => 'The Bose QuietComfort Ultra Headphones are Bose\'s flagship noise-cancelling headphones, first launched in 2023. They offer CustomTune sound calibration, Immersive Audio spatial processing and up to 24 hours of battery life. A second generation released in 2025 added lossless USB-C audio.',
                    'sources' => ['https://www.bose.com/p/headphones/bose-quietcomfort-ultra-headphones/QCUH-HEADPHONEARN.html']],
            ]],
            ['object' => 'Severance', 'revisions' => [
                ['by' => 'liamwalker', 'days_ago' => 6, 'status' => 'published', 'language' => 'en',
                    'body' => 'Severance is an Apple TV+ psychological thriller created by Dan Erickson and largely directed by Ben Stiller. Employees of Lumon Industries undergo a procedure that splits their work memories from their personal lives. The first season premiered in 2022 and the second in January 2025.',
                    'sources' => ['https://tv.apple.com/show/severance/umc.cmc.1srk2goyh2q2zdxcx605w8vtx']],
            ]],
            ['object' => 'Succession', 'revisions' => [
                ['by' => 'emmacarter', 'days_ago' => 6, 'status' => 'published', 'language' => 'en',
                    'body' => 'Succession is an HBO drama series created by Jesse Armstrong that ran for four seasons from 2018 to 2023. It follows the Roy family, owners of the global media conglomerate Waystar RoyCo, as the children of founder Logan Roy fight over who will take control of the company. The series won the Emmy for Outstanding Drama Series three times.',
                    'sources' => ['https://www.hbo.com/succession']],
            ]],
            ['object' => 'Shōgun', 'revisions' => [
                ['by' => 'emmacarter', 'days_ago' => 5, 'status' => 'published', 'language' => 'en',
                    'body' => 'Shōgun is a 2024 historical drama series on FX and Hulu, based on James Clavell\'s 1975 novel. Set in feudal Japan in 1600, it follows Lord Yoshii Toranaga, the English navigator John Blackthorne and the translator Toda Mariko. The first season won a record 18 Emmy Awards in a single season.',
                    'sources' => ['https://www.fxnetworks.com/shows/shogun']],
            ]],
        ];
    }

    /**
     * Articles per object, oldest revision first. `type` is "experience" (personal use) or
     * "informative" (needs sources). A key `ref` lets discussions point at the article.
     *
     * @return list<array<string, mixed>>
     */
    public static function articles(): array
    {
        return [
            ['ref' => 's25-sebulan', 'object' => 'Samsung Galaxy S25 Ultra', 'type' => 'experience', 'by' => 'dimaspras', 'revisions' => [
                ['days_ago' => 15, 'status' => 'published', 'language' => 'id', 'title' => 'Sebulan Pakai Galaxy S25 Ultra untuk Kerja dan Foto',
                    'body' => "Saya pindah dari S23 Ultra ke S25 Ultra bulan lalu, terutama karena butuh baterai yang lebih awet untuk kerja lapangan.\n\n## Baterai\n\nDengan pemakaian berat (hotspot, Google Maps, banyak foto), baterai masih tersisa sekitar 20% saat sampai rumah jam 8 malam. Untuk pemakaian biasa bisa tembus satu setengah hari.\n\n## Kamera\n\nZoom 5x adalah lensa yang paling sering saya pakai. Hasilnya tajam untuk foto papan nama atau dokumen dari jauh. Foto malam di lensa utama bagus, tapi di lensa ultra wide masih cukup berderau.\n\n## S Pen\n\nS Pen di generasi ini tidak lagi mendukung Bluetooth, jadi fitur Air Actions hilang. Untuk saya yang cuma pakai untuk catat dan tanda tangan dokumen, ini tidak masalah.\n\n**Kesimpulan:** kalau datang dari Ultra dua generasi sebelumnya, peningkatannya terasa. Dari S24 Ultra, menurut saya belum perlu ganti.",
                    'sources' => []],
            ]],
            ['ref' => 'pixel-vs-iphone-malam', 'object' => 'Google Pixel 9 Pro', 'type' => 'experience', 'by' => 'tiaraamelia', 'revisions' => [
                ['days_ago' => 12, 'status' => 'published', 'language' => 'id', 'title' => 'Adu Foto Malam Pixel 9 Pro di Jalanan Jakarta',
                    'body' => "Saya jalan dari Blok M ke Kota Tua bawa Pixel 9 Pro dan iPhone teman, lalu memotret objek yang sama di kondisi minim cahaya.\n\nPixel cenderung menjaga warna lampu jalan tetap hangat dan bayangan tetap gelap, sehingga suasananya terasa seperti aslinya. iPhone lebih terang dan detail di area gelap lebih banyak terlihat, tapi warnanya sedikit lebih dingin.\n\nUntuk foto orang di malam hari, Night Sight Portrait di Pixel lebih jarang menghasilkan wajah yang terlalu halus. Kelemahannya, Pixel butuh waktu proses sekitar satu sampai dua detik setelah menekan tombol, jadi kurang cocok untuk objek bergerak.\n\nIntinya: Pixel untuk suasana, iPhone untuk detail. Dua-duanya sudah jauh di atas ponsel kelas menengah.",
                    'sources' => []],
            ]],
            ['ref' => 'macbook-kuliah', 'object' => 'MacBook Air M4', 'type' => 'experience', 'by' => 'intanpermata', 'revisions' => [
                ['days_ago' => 8, 'status' => 'published', 'language' => 'id', 'title' => 'MacBook Air M4 untuk Skripsi: Yang Saya Suka dan Tidak',
                    'body' => "Saya pakai MacBook Air M4 13 inci selama semester terakhir untuk menulis skripsi, olah data di SPSS, dan sesekali edit video presentasi.\n\nYang saya suka: baterai benar-benar tahan dari pagi sampai sore di perpustakaan tanpa charger, laptopnya senyap karena tanpa kipas, dan ringan dibawa naik KRL.\n\nYang kurang: hanya dua port USB-C, jadi saya tetap bawa dongle untuk proyektor kampus. SPSS versi kampus sempat bermasalah dan harus minta versi terbaru ke bagian IT.\n\nKalau budget cukup, saya sarankan ambil RAM 16 GB yang sekarang jadi standar dan penyimpanan minimal 512 GB.",
                    'sources' => []],
            ]],
            ['ref' => 'raja-ampat-panduan', 'object' => 'Raja Ampat Papua', 'type' => 'informative', 'by' => 'salsa.nur', 'revisions' => [
                ['days_ago' => 21, 'status' => 'published', 'language' => 'id', 'title' => 'Panduan Pertama ke Raja Ampat: Rute, Biaya, dan Waktu Terbaik',
                    'body' => "Raja Ampat terasa jauh, tapi rutenya sebenarnya sederhana.\n\n## Rute\n\nTerbang ke Bandara Domine Eduard Osok di Sorong, lalu naik kapal cepat ke Waisai di Pulau Waigeo, sekitar dua jam.\n\n## Waktu terbaik\n\nOktober hingga April umumnya laut lebih tenang. Juli dan Agustus ombak bisa tinggi, terutama untuk perjalanan ke Misool.\n\n## Biaya yang perlu disiapkan\n\n- Pin Konservasi untuk wisatawan domestik\n- Kapal cepat Sorong ke Waisai\n- Homestay di Pulau Kri atau Arborek, biasanya sudah termasuk makan\n\nSebaiknya bergabung dengan open trip jika berangkat sendiri, karena sewa speedboat ke Wayag dan Piaynemo cukup mahal bila ditanggung sendiri.",
                    'sources' => ['https://www.indonesia.travel/id/id/destinasi/papua/raja-ampat.html']],
                ['days_ago' => 6, 'status' => 'published', 'language' => 'id', 'title' => 'Panduan Pertama ke Raja Ampat: Rute, Biaya, dan Waktu Terbaik',
                    'body' => "Raja Ampat terasa jauh, tapi rutenya sebenarnya sederhana.\n\n## Rute\n\nTerbang ke Bandara Domine Eduard Osok di Sorong, lalu naik kapal cepat ke Waisai di Pulau Waigeo, sekitar dua jam. Jadwal kapal biasanya pukul 09.00 dan 14.00, jadi pilih penerbangan yang tiba sebelum siang.\n\n## Waktu terbaik\n\nOktober hingga April umumnya laut lebih tenang. Juli dan Agustus ombak bisa tinggi, terutama untuk perjalanan ke Misool.\n\n## Biaya yang perlu disiapkan\n\n- Pin Konservasi untuk wisatawan domestik, dibayar di Waisai\n- Kapal cepat Sorong ke Waisai\n- Homestay di Pulau Kri atau Arborek, biasanya sudah termasuk tiga kali makan\n\nSebaiknya bergabung dengan open trip jika berangkat sendiri, karena sewa speedboat ke Wayag dan Piaynemo cukup mahal bila ditanggung sendiri.\n\n## Tambahan dari pembaca\n\nSinyal hanya stabil di Waisai. Unduh peta offline dan bawa uang tunai secukupnya, karena ATM sangat terbatas di pulau-pulau kecil.",
                    'sources' => ['https://www.indonesia.travel/id/id/destinasi/papua/raja-ampat.html', 'https://www.kemenparekraf.go.id/']],
            ]],
            ['ref' => 'bromo-sunrise', 'object' => 'Gunung Bromo Tengger', 'type' => 'experience', 'by' => 'bayusetiawan', 'revisions' => [
                ['days_ago' => 13, 'status' => 'published', 'language' => 'id', 'title' => 'Mengejar Sunrise Bromo Naik Motor dari Malang',
                    'body' => "Saya berangkat dari Kota Malang jam 1 dini hari lewat jalur Tumpang. Jalannya menanjak dan berkelok, jadi pastikan rem dan lampu motor dalam kondisi baik.\n\nSampai di Penanjakan sekitar jam 4 pagi, sudah ramai sekali. Kalau mau dapat tempat di depan, datang sebelum jam setengah empat.\n\nSuhu di atas bisa di bawah 10 derajat. Saya pakai jaket tebal, sarung tangan, dan kupluk, masih kedinginan juga.\n\nSetelah matahari terbit, turun ke lautan pasir dan naik ke bibir kawah lewat tangga. Masker wajib karena debu pasirnya banyak.",
                    'sources' => []],
            ]],
            ['ref' => 'rendang-rumahan', 'object' => 'Rendang Daging Padang', 'type' => 'informative', 'by' => 'rinakartika', 'revisions' => [
                ['days_ago' => 16, 'status' => 'published', 'language' => 'id', 'title' => 'Beda Rendang, Kalio, dan Gulai yang Sering Tertukar',
                    'body' => "Ketiganya sama-sama dimasak dengan santan dan bumbu rempah, bedanya ada di lama memasak dan sisa kuahnya.\n\n- **Gulai** masih berkuah banyak dan berwarna kuning karena kunyit.\n- **Kalio** adalah tahap tengah: kuah sudah menyusut dan mengental, warnanya cokelat kemerahan.\n- **Rendang** dimasak terus sampai santan habis dan bumbunya kering menghitam, bisa 6 jam atau lebih.\n\nBanyak rumah makan Padang di luar Sumatera Barat menyajikan kalio dengan nama rendang karena lebih cepat dibuat dan dagingnya lebih lembut. Rendang kering asli justru lebih tahan lama.",
                    'sources' => ['https://warisanbudaya.kemdikbud.go.id/?newdetail&detailCatat=1340']],
            ]],
            ['ref' => 'bakso-malang-tempat', 'object' => 'Bakso Malang Komplit', 'type' => 'experience', 'by' => 'ekosusilo', 'revisions' => [
                ['days_ago' => 5, 'status' => 'pending', 'language' => 'id', 'title' => 'Bakso Malang Terenak Menurut Saya di Kota Malang',
                    'body' => 'Sebagai orang Malang asli, ini tiga tempat bakso yang selalu saya ajak kunjungi kalau ada teman dari luar kota. Kuahnya bening dan gurih, porsinya komplit dengan siomay basah dan goreng. Harga masih wajar dan parkir cukup luas.',
                    'sources' => []],
            ]],
            ['ref' => 'ioniq5-mudik', 'object' => 'Hyundai Ioniq 5', 'type' => 'experience', 'by' => 'fajarrmd', 'revisions' => [
                ['days_ago' => 13, 'status' => 'published', 'language' => 'id', 'title' => 'Mudik Jakarta–Semarang Pakai Ioniq 5: Catatan Pengisian Daya',
                    'body' => "Perjalanan sekitar 450 km dengan Ioniq 5 Long Range, berempat plus bagasi penuh.\n\n## Rencana pengisian\n\nSaya berangkat dengan baterai 100% dan mengisi sekali di SPKLU rest area KM 207 Tol Cipali. Dari 25% ke 80% butuh sekitar 25 menit di charger 100 kW.\n\n## Yang perlu diperhatikan\n\n- Saat musim mudik antrean SPKLU bisa lebih dari satu jam. Berangkat di luar jam puncak.\n- Pakai aplikasi PLN Mobile untuk cek charger yang sedang dipakai.\n- Kecepatan di atas 100 km/jam membuat konsumsi energi naik cukup banyak.\n\nTotal biaya listrik pulang pergi masih jauh lebih murah dibanding bensin dan tol pun sama saja.",
                    'sources' => []],
            ]],
            ['ref' => 'zenix-irit', 'object' => 'Toyota Innova Zenix Hybrid', 'type' => 'informative', 'by' => 'rizkyhdyt', 'revisions' => [
                ['days_ago' => 3, 'status' => 'needs_revision', 'language' => 'id', 'title' => 'Seberapa Irit Innova Zenix Hybrid di Kemacetan?',
                    'reason' => 'Artikel informatif perlu sumber untuk angka konsumsi BBM yang disebutkan. Silakan tambahkan rujukan atau ubah ke tipe pengalaman.',
                    'body' => 'Di kemacetan Jakarta, Innova Zenix Hybrid bisa mencapai 18 km per liter karena mobil lebih banyak berjalan dengan motor listrik di kecepatan rendah. Di tol, angkanya turun ke kisaran 14 sampai 15 km per liter.',
                    'sources' => ['https://www.toyota.astra.co.id/product/innova-zenix']],
            ]],
            ['ref' => 'gadis-kretek-ulasan', 'object' => 'Gadis Kretek', 'type' => 'experience', 'by' => 'citradewi', 'revisions' => [
                ['days_ago' => 10, 'status' => 'published', 'language' => 'id', 'title' => 'Kenapa Gadis Kretek Terasa Berbeda dari Serial Indonesia Lain',
                    'body' => "Saya menonton Gadis Kretek dalam satu malam, dan yang paling membekas adalah cara serial ini membangun suasana.\n\nPalet warnanya hangat dan kusam seperti foto lama, kamera sering diam lama di wajah pemain, dan dialognya tidak terburu-buru. Dian Sastrowardoyo sebagai Dasiyah terasa kuat tanpa banyak kata.\n\nCerita dua garis waktunya kadang membingungkan di episode awal, tapi semuanya terbayar di episode terakhir. Bagi yang suka drama sejarah dan kisah cinta yang pelan, ini wajib ditonton.",
                    'sources' => []],
            ]],
            ['ref' => 'dicoding-bangkit', 'object' => 'Dicoding', 'type' => 'experience', 'by' => 'demouser', 'revisions' => [
                ['days_ago' => 4, 'status' => 'published', 'language' => 'id', 'title' => 'Belajar Android dari Nol di Dicoding Sampai Lulus Kelas Expert',
                    'body' => "Saya mulai belajar Android di Dicoding tanpa latar belakang informatika. Jalurnya dimulai dari kelas dasar Kotlin, lalu Android Pemula, Fundamental, dan Expert.\n\nYang membuat saya bertahan adalah sistem submission: setiap kelas ditutup dengan proyek yang direview manual oleh reviewer, lengkap dengan catatan perbaikan. Beberapa kali submission saya ditolak, tapi dari situ belajarnya paling banyak.\n\nTotal saya butuh sekitar lima bulan sambil kerja. Forum diskusinya aktif, jadi kalau buntu biasanya ada yang membantu dalam sehari.",
                    'sources' => []],
            ]],
            ['ref' => 'xm6-commute', 'object' => 'Sony WH-1000XM6', 'type' => 'experience', 'by' => 'noahbennett', 'revisions' => [
                ['days_ago' => 8, 'status' => 'published', 'language' => 'en', 'title' => 'Three Weeks of Subway Commutes with the Sony WH-1000XM6',
                    'body' => "I ride the subway about an hour each way, and the XM6 has been the best travel companion I've owned.\n\nNoise cancelling handles the low rumble of the train almost completely. Announcements still come through faintly, which I actually like. Voices nearby are reduced but not erased.\n\nThe new folding hinge means it fits in my backpack's side pocket again, which was my biggest complaint about the XM5.\n\nThe one downside: the ear cushions get warm after about two hours. For a commute that's fine, for a long flight you'll want breaks.",
                    'sources' => []],
            ]],
            ['ref' => 'severance-guide', 'object' => 'Severance', 'type' => 'informative', 'by' => 'emmacarter', 'revisions' => [
                ['days_ago' => 5, 'status' => 'published', 'language' => 'en', 'title' => 'Severance: What to Know Before Starting Season One',
                    'body' => "Severance rewards patience. The first two episodes are slow by design, building the strange rules of Lumon's severed floor.\n\n- **Innies** are the work selves, who know nothing of the outside world.\n- **Outies** are the same people outside, with no memory of their work.\n- The show was filmed largely in New Jersey and New York, with Bell Labs' Holmdel complex standing in for the Lumon exterior.\n\nSeason two arrived nearly three years after the first, so many viewers rewatch season one first. Both seasons are on Apple TV+.",
                    'sources' => ['https://tv.apple.com/show/severance/umc.cmc.1srk2goyh2q2zdxcx605w8vtx']],
            ]],
        ];
    }

    /**
     * Discussions on object pages and articles. `on` names an object, or `article` the `ref`
     * of an article above. Comment `at` values are minutes after the discussion opened.
     *
     * @return list<array<string, mixed>>
     */
    public static function discussions(): array
    {
        return [
            ['on' => 'iPhone 16 Pro Max', 'comments' => [
                ['by' => 'galihpratama', 'at' => 120, 'body' => 'Ada yang sudah coba tombol Camera Control lama? Saya masih sering kepencet pas ambil HP dari saku.', 'replies' => [
                    ['by' => 'putrimhrn', 'at' => 200, 'body' => 'Sama, Mas. Saya akhirnya ubah setelannya jadi harus double press supaya nggak kebuka tiba-tiba.'],
                    ['by' => 'demouser', 'at' => 260, 'body' => 'Makasih tipsnya, baru tahu bisa diubah gitu.'],
                ]],
                ['by' => 'andisaputra', 'at' => 900, 'body' => 'Pengisian dayanya masih terasa lambat dibanding Android flagship. Dari 0 ke 100 hampir 1,5 jam pakai charger 20 W.', 'replies' => [
                    ['by' => 'bagaswicak', 'at' => 980, 'body' => 'Coba charger 30 W ke atas, bisa sedikit lebih cepat. Tapi memang bukan keunggulan iPhone.'],
                ]],
            ]],
            ['on' => 'Samsung Galaxy S25 Ultra', 'comments' => [
                ['by' => 'yogaaditya', 'at' => 300, 'body' => 'Buat yang tanya soal S Pen tanpa Bluetooth: fitur Air Command tetap ada, cuma gerakan jarak jauh untuk shutter kamera yang hilang.'],
                ['by' => 'wulansari', 'at' => 1500, 'body' => 'Bodinya titanium tapi tetap licin. Wajib pakai case kalau nggak mau jantungan tiap hari.', 'replies' => [
                    ['by' => 'dimaspras', 'at' => 1600, 'body' => 'Setuju banget, sudut kotaknya juga lebih tajam dari S24 Ultra kalau dipegang lama.'],
                ]],
            ]],
            ['on' => 'Poco F7', 'comments' => [
                ['by' => 'bayusetiawan', 'at' => 200, 'body' => 'Baterai 6.500 mAh itu nyata. Saya main ML tiga jam masih sisa 50%.', 'replies' => [
                    ['by' => 'fajarrmd', 'at' => 260, 'body' => 'Iya, dan charge 90 W-nya bikin nggak perlu nunggu lama.'],
                    ['by' => 'fikrimaulana', 'at' => 400, 'body' => 'Wah, jadi makin yakin ambil F7 daripada X7 Pro.'],
                ]],
            ]],
            ['on' => 'Raja Ampat Papua', 'comments' => [
                ['by' => 'citradewi', 'at' => 600, 'body' => 'Ringkasannya lengkap. Mungkin bisa ditambah info soal musim ubur-ubur di Danau Misool?', 'replies' => [
                    ['by' => 'salsa.nur', 'at' => 700, 'body' => 'Ide bagus, nanti saya cari sumber resminya dulu sebelum ditambahkan ke ringkasan.'],
                ]],
                ['by' => 'tiaraamelia', 'at' => 2000, 'body' => 'Tahun lalu ke Piaynemo, tangganya sudah dibangun bagus. Pemandangan dari atas bikin lupa capek naiknya.'],
                ['by' => 'jackreynolds', 'at' => 4000, 'body' => 'Dove at Cape Kri last year. Counted more fish species in one dive than anywhere else I have been.'],
            ]],
            ['on' => 'Labuan Bajo Komodo', 'comments' => [
                ['by' => 'hendragun', 'at' => 400, 'body' => 'Tiket masuk Taman Nasional Komodo sempat berubah-ubah. Sebaiknya cek pengumuman resmi Balai TN Komodo sebelum berangkat.', 'replies' => [
                    ['by' => 'salsa.nur', 'at' => 520, 'body' => 'Betul, Pak. Saya sengaja tidak menulis angka harga di ringkasan karena sering berubah.'],
                ]],
            ]],
            ['on' => 'Rendang Daging Padang', 'comments' => [
                ['by' => 'safirandini', 'at' => 300, 'body' => 'Penjelasan soal rendang sebagai bekal perantau itu menarik. Nenek saya juga selalu bawain rendang kering kalau saya balik ke Jakarta.', 'replies' => [
                    ['by' => 'rinakartika', 'at' => 360, 'body' => 'Rendang buatan nenek memang beda, Kak. Nggak ada yang bisa ngalahin 🥹'],
                ]],
                ['by' => 'sofiamartinez', 'at' => 1800, 'body' => 'Tried rendang for the first time in Jakarta and now I understand the hype. Is it supposed to be this dry?', 'replies' => [
                    ['by' => 'rinakartika', 'at' => 1900, 'body' => 'Yes! Real rendang is cooked until the coconut milk is completely absorbed. The wetter version is called kalio.'],
                ]],
            ]],
            ['on' => 'Bubur Ayam', 'comments' => [
                ['by' => 'radityakrn', 'at' => 150, 'body' => 'Tim bubur diaduk, nggak bisa diganggu gugat 😂', 'replies' => [
                    ['by' => 'nadiasaf', 'at' => 210, 'body' => 'Tim tidak diaduk hadir. Kerupuknya harus tetap renyah!'],
                    ['by' => 'mayaanggr', 'at' => 300, 'body' => 'Saya diaduk setengah, jalan tengah paling damai.'],
                ]],
            ]],
            ['on' => 'Hyundai Ioniq 5', 'comments' => [
                ['by' => 'andisaputra', 'at' => 500, 'body' => 'Garansi baterainya berapa tahun ya di Indonesia? Itu yang paling bikin ragu beli mobil listrik.', 'replies' => [
                    ['by' => 'fajarrmd', 'at' => 560, 'body' => 'Untuk baterai 8 tahun atau 160 ribu km, mana yang tercapai lebih dulu. Cek lagi di dealer karena bisa beda per program.'],
                ]],
            ]],
            ['on' => 'Gadis Kretek', 'comments' => [
                ['by' => 'anisarahma', 'at' => 800, 'body' => 'Soundtrack-nya juga juara. Lagu-lagu lamanya pas banget sama suasana tahun 60-an.'],
            ]],
            ['on' => 'Sony WH-1000XM6', 'comments' => [
                ['by' => 'avamitchell', 'at' => 300, 'body' => 'Does the multipoint connection work well between a laptop and phone? My XM4 was flaky.', 'replies' => [
                    ['by' => 'emmacarter', 'at' => 360, 'body' => 'Much better on the XM6 in my experience. It switches to whichever device starts playing audio.'],
                ]],
            ]],
            ['article' => 's25-sebulan', 'comments' => [
                ['by' => 'bagaswicak', 'at' => 180, 'body' => 'Catatan soal S Pen tanpa Bluetooth ini penting, banyak yang belum tahu sebelum beli.', 'replies' => [
                    ['by' => 'dimaspras', 'at' => 240, 'body' => 'Iya Mas, saya juga baru sadar setelah unboxing 😅'],
                ]],
                ['by' => 'hendragun', 'at' => 1300, 'body' => 'Kalau dari S22 Ultra kira-kira worth it nggak upgrade?', 'replies' => [
                    ['by' => 'dimaspras', 'at' => 1380, 'body' => 'Dari S22 menurut saya worth it, Pak. Baterai dan performanya jauh lebih stabil.'],
                ]],
            ]],
            ['article' => 'pixel-vs-iphone-malam', 'comments' => [
                ['by' => 'rizkyhdyt', 'at' => 240, 'body' => '"Pixel untuk suasana, iPhone untuk detail" — rangkuman paling pas yang pernah saya baca.'],
                ['by' => 'putrimhrn', 'at' => 900, 'body' => 'Coba tambah perbandingan video malam juga, Kak. Di situ iPhone biasanya unggul.', 'replies' => [
                    ['by' => 'tiaraamelia', 'at' => 1000, 'body' => 'Siap, lagi saya kumpulkan footage-nya. Nanti saya buat artikel lanjutan.'],
                ]],
            ]],
            ['article' => 'raja-ampat-panduan', 'comments' => [
                ['by' => 'wulansari', 'at' => 600, 'body' => 'Tambahan: bawa obat anti mabuk laut. Perjalanan ke Wayag bisa 3-4 jam dan ombaknya lumayan.', 'replies' => [
                    ['by' => 'salsa.nur', 'at' => 700, 'body' => 'Makasih, Kak! Nanti saya masukkan ke bagian tips di revisi berikutnya.'],
                ]],
                ['by' => 'liamwalker', 'at' => 3000, 'body' => 'Super helpful. Is a week enough to see both Wayag and Misool?', 'replies' => [
                    ['by' => 'salsa.nur', 'at' => 3100, 'body' => 'A week is tight but doable if the weather is kind. Misool alone deserves three days.'],
                ]],
            ]],
            ['article' => 'rendang-rumahan', 'comments' => [
                ['by' => 'citradewi', 'at' => 400, 'body' => 'Akhirnya ada yang jelasin beda kalio dan rendang dengan sederhana. Selama ini saya kira sama saja.'],
                ['by' => 'safirandini', 'at' => 1100, 'body' => 'Di rumah kami rendang dimasak pakai kayu bakar, katanya biar aromanya lebih keluar. Ada yang sama?'],
            ]],
            ['article' => 'ioniq5-mudik', 'comments' => [
                ['by' => 'rizkyhdyt', 'at' => 300, 'body' => 'Info antrean SPKLU pas mudik ini yang paling penting. Banyak yang nggak siap mental 😂', 'replies' => [
                    ['by' => 'fajarrmd', 'at' => 360, 'body' => 'Iya, saya sempat lihat ada yang nunggu dua jam di Cipali.'],
                ]],
            ]],
            ['article' => 'dicoding-bangkit', 'comments' => [
                ['by' => 'lestarin', 'at' => 200, 'body' => 'Keren, Kak! Lima bulan sambil kerja itu konsisten banget.', 'replies' => [
                    ['by' => 'demouser', 'at' => 260, 'body' => 'Makasih, Kak. Kuncinya dicicil tiap malam 1 jam aja.'],
                ]],
                ['by' => 'fikrimaulana', 'at' => 700, 'body' => 'Submission yang paling susah kelas mana, Kak?', 'replies' => [
                    ['by' => 'demouser', 'at' => 760, 'body' => 'Menurut saya Android Expert, karena harus menerapkan modularisasi dan clean architecture.'],
                ]],
            ]],
            ['article' => 'xm6-commute', 'comments' => [
                ['by' => 'miasullivan', 'at' => 300, 'body' => 'Glad to hear the folding hinge is back. That alone might make me upgrade.'],
            ]],
            ['article' => 'severance-guide', 'comments' => [
                ['by' => 'oliviahughes', 'at' => 240, 'body' => 'The Bell Labs detail is great. I visited Holmdel and it looks exactly like the show.', 'replies' => [
                    ['by' => 'emmacarter', 'at' => 300, 'body' => 'Lucky you! It is open to the public now as a mixed-use building.'],
                ]],
            ]],
        ];
    }
}
