<?php

namespace Database\Seeders\Content;

/**
 * Hand-written demo content shared by the seeders, so that objects, rankings, comments,
 * votes and reports all describe the same community.
 */
class CommunityContent
{
    /**
     * Community members: [email, name, username, locale, region].
     *
     * @return list<array{email: string, name: string, username: string, locale: string, region: string}>
     */
    public static function members(): array
    {
        $indonesian = [
            ['Rina Kartika', 'rinakartika'], ['Dimas Prasetyo', 'dimaspras'], ['Salsabila Nur', 'salsa.nur'],
            ['Fajar Ramadhan', 'fajarrmd'], ['Putri Maharani', 'putrimhrn'], ['Yoga Aditya', 'yogaaditya'],
            ['Nadia Safitri', 'nadiasaf'], ['Bagas Wicaksono', 'bagaswicak'], ['Intan Permata', 'intanpermata'],
            ['Rizky Hidayat', 'rizkyhdyt'], ['Maya Anggraini', 'mayaanggr'], ['Andi Saputra', 'andisaputra'],
            ['Lestari Ningsih', 'lestarin'], ['Hendra Gunawan', 'hendragun'], ['Citra Dewi', 'citradewi'],
            ['Eko Susilo', 'ekosusilo'], ['Wulan Sari', 'wulansari'], ['Galih Pratama', 'galihpratama'],
            ['Safira Andini', 'safirandini'], ['Raditya Kurniawan', 'radityakrn'], ['Tiara Amelia', 'tiaraamelia'],
            ['Bayu Setiawan', 'bayusetiawan'], ['Dewi Anggraeni', 'dewianggraeni'], ['Fikri Maulana', 'fikrimaulana'],
            ['Anisa Rahma', 'anisarahma'],
        ];
        $international = [
            ['Emma Carter', 'emmacarter'], ['Liam Walker', 'liamwalker'], ['Sofia Martinez', 'sofiamartinez'],
            ['Noah Bennett', 'noahbennett'], ['Olivia Hughes', 'oliviahughes'], ['Ethan Brooks', 'ethanbrooks'],
            ['Ava Mitchell', 'avamitchell'], ['Lucas Foster', 'lucasfoster'], ['Mia Sullivan', 'miasullivan'],
            ['Jack Reynolds', 'jackreynolds'],
        ];
        $members = [];

        foreach ($indonesian as $index => [$name, $username]) {
            $members[] = ['email' => 'user'.($index + 1).'@community.votmee.com', 'name' => $name, 'username' => $username, 'locale' => 'id', 'region' => 'ID'];
        }

        foreach ($international as $index => [$name, $username]) {
            $members[] = ['email' => 'global'.($index + 26).'@community.votmee.com', 'name' => $name, 'username' => $username, 'locale' => 'en', 'region' => 'GLOBAL'];
        }

        return $members;
    }

    /**
     * Rankings with their candidates, vote profile and discussion.
     *
     * `weight` is the relative share of supporters a candidate attracts (list order is only the
     * initial position), `popularity` scales how many members voted, and comment `at` values are
     * minutes after publication. A comment may carry `replies` and a moderation `flag`.
     *
     * @return list<array<string, mixed>>
     */
    public static function posts(): array
    {
        return [
            [
                'title' => 'Smartphone Flagship Terbaik 2026',
                'criteria' => 'Yang saya nilai: performa sehari-hari dan saat dipakai gaming, kamera terutama saat minim cahaya, daya tahan baterai seharian, dan berapa lama update software dijanjikan. Harga tidak dihitung, murni soal kualitas.',
                'category' => 'teknologi', 'language' => 'id', 'region' => 'ID',
                'creator' => 'bagaswicak', 'days_ago' => 21, 'popularity' => 1.6,
                'candidates' => [
                    ['name' => 'iPhone 16 Pro Max', 'weight' => 9],
                    ['name' => 'Samsung Galaxy S25 Ultra', 'weight' => 12],
                    ['name' => 'Google Pixel 9 Pro', 'weight' => 6],
                    ['name' => 'Xiaomi 15 Ultra', 'weight' => 7],
                ],
                'comments' => [
                    ['by' => 'dimaspras', 'at' => 190, 'body' => 'Buat saya S25 Ultra masih paling seimbang. Zoom-nya enak, baterai kuat sampai malam, dan S Pen kepake banget buat catat-catat pas meeting.', 'replies' => [
                        ['by' => 'bagaswicak', 'at' => 230, 'body' => 'Zoom 5x-nya konsisten sih. Cuma harganya yang bikin mikir dua kali 😅'],
                        ['by' => 'yogaaditya', 'at' => 285, 'body' => 'S Pen ini underrated. Banyak yang beli Ultra tapi penanya nggak pernah dikeluarin wkwk'],
                    ]],
                    ['by' => 'rizkyhdyt', 'at' => 420, 'body' => 'Pixel 9 Pro hasil fotonya memang juara, warnanya enak dan nggak lebay. Tapi baterai sama sinyal di tempat saya kadang naik turun, makanya saya taruh di bawah.', 'replies' => [
                        ['by' => 'galihpratama', 'at' => 480, 'body' => 'Sinyal Pixel di Bekasi sempat bermasalah juga di unit saya, membaik setelah update bulan Maret.'],
                    ]],
                    ['by' => 'nadiasaf', 'at' => 780, 'body' => 'Kok Xiaomi 15 Ultra bisa di bawah iPhone ya? Kamera Leica-nya menurut saya paling natural, malam hari pun detailnya masih dapet.', 'replies' => [
                        ['by' => 'bagaswicak', 'at' => 840, 'body' => 'Kalau kamera malam saya setuju Xiaomi kuat. Tapi di kriteria saya update software ikut dihitung, dan janji update Samsung dan Pixel sampai 7 tahun, Xiaomi lebih pendek.'],
                    ]],
                    ['by' => 'putrimhrn', 'at' => 1500, 'body' => 'Sudah pakai iPhone 16 Pro Max sejak launching. Baterai kuat seharian walau layar sering nyala, tapi yang bikin betah itu hasil videonya. Susah pindah ke lain.'],
                    ['by' => 'hendragun', 'at' => 2100, 'body' => 'Kalau Oppo Find X8 Pro dimasukkan gimana ya? Kameranya setahu saya nggak kalah.', 'replies' => [
                        ['by' => 'bagaswicak', 'at' => 2200, 'body' => 'Bisa banget, Pak. Silakan buat ranking baru dengan kandidat yang Bapak mau, nanti kita bandingkan juga.'],
                    ]],
                    ['by' => 'ekosusilo', 'at' => 2600, 'flag' => 'spam', 'body' => 'JUAL iPhone 16 Pro Max garansi resmi 1 tahun harga miring! Chat wa.me/62812xxxxxxx sekarang juga'],
                    ['by' => 'tiaraamelia', 'at' => 4000, 'body' => 'Aku sih tim Pixel buat urusan foto. Hasilnya nggak over-saturated kayak beberapa HP lain. Tapi namanya selera, beda-beda.'],
                    ['by' => 'fikrimaulana', 'at' => 5200, 'flag' => 'heated', 'body' => 'Yang pilih iPhone itu cuma ngikutin gengsi. Dasar budak merek, nggak paham spek.'],
                ],
            ],
            [
                'title' => 'HP Gaming Paling Worth It Tahun Ini',
                'criteria' => 'Dinilai dari performa saat main game berat (Genshin, Mobile Legends, PUBG), suhu setelah main satu jam, daya tahan baterai, dan seberapa sepadan dengan harganya. ROG Phone 9 saya masukkan sebagai pembanding kelas atas.',
                'category' => 'teknologi', 'language' => 'id', 'region' => 'ID',
                'creator' => 'yogaaditya', 'days_ago' => 14, 'popularity' => 1.1,
                'candidates' => [
                    ['name' => 'Poco X7 Pro', 'weight' => 12],
                    ['name' => 'Poco F7', 'weight' => 11],
                    ['name' => 'Infinix GT 30 Pro', 'weight' => 7],
                    ['name' => 'Asus ROG Phone 9', 'weight' => 6],
                ],
                'comments' => [
                    ['by' => 'fajarrmd', 'at' => 240, 'body' => 'Poco X7 Pro menurut saya paling masuk akal. Main Genshin dan ML lancar di setelan tinggi, panasnya masih bisa ditoleransi.', 'replies' => [
                        ['by' => 'yogaaditya', 'at' => 300, 'body' => 'Setuju, tapi kalau mau main lama banget baterai F7 jauh lebih besar.'],
                    ]],
                    ['by' => 'anisarahma', 'at' => 600, 'body' => 'ROG Phone 9 ini sebenarnya di luar jangkauan wajar buat kebanyakan orang. Layak ditaruh di daftar? Bandingannya jadi nggak adil.', 'replies' => [
                        ['by' => 'yogaaditya', 'at' => 680, 'body' => 'Sengaja saya masukkan sebagai pembanding. Kalau ternyata harganya nggak sepadan, kelihatan dari poinnya nanti.'],
                    ]],
                    ['by' => 'bayusetiawan', 'at' => 1300, 'body' => 'Infinix GT 30 Pro bikin kaget, lampu RGB di belakangnya lucu dan performanya lumayan di harga segitu. Tapi service center Infinix di kota saya masih jarang.', 'replies' => [
                        ['by' => 'galihpratama', 'at' => 1400, 'body' => 'Itu yang bikin ragu juga. Di Medan service center-nya cuma ada satu.'],
                    ]],
                    ['by' => 'fikrimaulana', 'at' => 2400, 'body' => 'Ada yang tahu suhu Poco F7 pas dipakai main lebih dari satu jam? Kemarin lihat review katanya agak panas.', 'replies' => [
                        ['by' => 'fajarrmd', 'at' => 2600, 'body' => 'Saya pakai F7 sekitar 3 bulan. Main 1-2 jam hangat tapi nggak sampai throttle parah. Pakai case tipis aja.'],
                    ]],
                    ['by' => 'lestarin', 'at' => 3500, 'body' => 'Saya bukan gamer tapi ikut vote Infinix. Buat yang baru mulai main game, ini sudah cukup banget.'],
                ],
            ],
            [
                'title' => 'Laptop Terbaik untuk Mahasiswa & Kerja Remote',
                'criteria' => 'Untuk mahasiswa dan yang kerja remote: bobot ringan, baterai tahan seharian tanpa colokan, keyboard nyaman diketik lama, layar enak di mata, dan servis mudah dijangkau. Harga dipertimbangkan secara wajar.',
                'category' => 'teknologi', 'language' => 'id', 'region' => 'ID',
                'creator' => 'intanpermata', 'days_ago' => 10, 'popularity' => 1.0,
                'candidates' => [
                    ['name' => 'MacBook Air M4', 'weight' => 13],
                    ['name' => 'Asus Zenbook 14 OLED', 'weight' => 9],
                    ['name' => 'Lenovo ThinkPad E14', 'weight' => 8],
                    ['name' => 'Acer Swift Go 14', 'weight' => 5],
                ],
                'comments' => [
                    ['by' => 'bayusetiawan', 'at' => 300, 'body' => 'MacBook Air memang enak buat mahasiswa: ringan, baterainya awet, dan tanpa kipas jadi senyap di perpustakaan. Tapi kalau jurusannya butuh software khusus Windows, perlu dipikir lagi.', 'replies' => [
                        ['by' => 'intanpermata', 'at' => 360, 'body' => 'Betul, makanya servis dan kompatibilitas saya masukkan ke kriteria. Anak teknik sipil di kampus saya masih banyak yang pakai AutoCAD di Windows.'],
                    ]],
                    ['by' => 'citradewi', 'at' => 900, 'body' => 'Zenbook 14 OLED layarnya cantik banget buat nonton dan desain. Bobotnya juga ringan. Sayangnya baterai agak cepat habis kalau layar terang.'],
                    ['by' => 'ekosusilo', 'at' => 1700, 'body' => 'ThinkPad E14 itu kelihatannya biasa tapi kokoh. Keyboard-nya juara, saya ngetik skripsi tiga bulan nyaman banget. Kekurangannya cuma bobot dan layar yang standar.', 'replies' => [
                        ['by' => 'wulansari', 'at' => 1800, 'body' => 'Setuju soal keyboard ThinkPad. Servisnya juga gampang, banyak tempat resmi.'],
                    ]],
                    ['by' => 'tiaraamelia', 'at' => 2500, 'body' => 'Acer Swift Go 14 cocok buat budget terbatas, tapi pengalaman teman saya kipasnya lumayan berisik pas dipakai berat.'],
                    ['by' => 'hendragun', 'at' => 3600, 'body' => 'Anak saya mau masuk kuliah tahun depan. Dari komentar di sini kayaknya MacBook Air atau Zenbook ya. Terima kasih referensinya!', 'replies' => [
                        ['by' => 'intanpermata', 'at' => 3700, 'body' => 'Semoga cocok, Pak! Kalau ragu, coba langsung di toko dulu buat tes keyboard dan layarnya.'],
                    ]],
                ],
            ],
            [
                'title' => 'Destinasi Wisata Alam Terindah di Indonesia',
                'criteria' => 'Saya nilai dari keindahan lanskap, keunikan, seberapa terjaga alamnya, dan kemudahan akses serta fasilitas untuk wisatawan. Cerita dari yang pernah ke sana sangat membantu.',
                'category' => 'wisata', 'language' => 'id', 'region' => 'ID',
                'creator' => 'salsa.nur', 'days_ago' => 25, 'popularity' => 1.5,
                'candidates' => [
                    ['name' => 'Raja Ampat Papua', 'weight' => 14],
                    ['name' => 'Labuan Bajo Komodo', 'weight' => 10],
                    ['name' => 'Gunung Bromo Tengger', 'weight' => 9],
                    ['name' => 'Danau Toba Samosir', 'weight' => 7],
                    ['name' => 'Ubud Bali', 'weight' => 6],
                ],
                'comments' => [
                    ['by' => 'andisaputra', 'at' => 200, 'body' => 'Raja Ampat memang juara, snorkeling di Piaynemo dan Arborek airnya jernih banget. Tapi siap-siap biaya transportasi dan penginapan yang lumayan, jadi harus nabung dulu.', 'replies' => [
                        ['by' => 'salsa.nur', 'at' => 260, 'body' => 'Iya Mas, saya dulu nabung hampir setahun. Worth it sih, tapi memang bukan buat yang dadakan.'],
                    ]],
                    ['by' => 'galihpratama', 'at' => 900, 'body' => 'Bromo sunrise-nya cakep, tapi pas libur panjang jip dan wisatawannya bikin macet. Saran saya datang hari kerja dan berangkat dari Cemoro Lawang jam 3 pagi.'],
                    ['by' => 'safirandini', 'at' => 2000, 'body' => 'Danau Toba menurut aku underrated. Suasananya tenang, makanan khas Batak enak, dan Samosir masih terasa asri. Nggak sepadat Bali.', 'replies' => [
                        ['by' => 'radityakrn', 'at' => 2100, 'body' => 'Setuju! Sayang aksesnya masih cukup jauh dari Kualanamu, bisa 4-5 jam.'],
                    ]],
                    ['by' => 'lestarin', 'at' => 4300, 'body' => 'Labuan Bajo bagus, tapi harga paket kapal pas high season lumayan naik. Sempat kecewa karena lihat komodonya cuma sebentar.'],
                    ['by' => 'wulansari', 'at' => 7000, 'body' => 'Ubud masuk daftar wisata alam? Menurutku lebih ke budaya dan sawah terasering, tapi sawah Tegallalang tetap cantik sih.', 'replies' => [
                        ['by' => 'salsa.nur', 'at' => 7100, 'body' => 'Betul Kak, saya masukkan karena banyak yang cari alam santai selain gunung dan laut. Silakan kalau mau vote 😊'],
                    ]],
                ],
            ],
            [
                'title' => 'Liburan Akhir Pekan dari Jakarta (Maksimal 4 Jam Perjalanan)',
                'criteria' => 'Tujuan akhir pekan dari Jakarta dengan perjalanan maksimal sekitar 4 jam, cocok untuk menginap satu malam. Saya nilai dari pemandangan, kemudahan akses, dan seberapa nyaman buat keluarga atau pasangan.',
                'category' => 'wisata', 'language' => 'id', 'region' => 'ID',
                'creator' => 'radityakrn', 'days_ago' => 6, 'popularity' => 0.9,
                'candidates' => [
                    ['name' => 'Lembang Bandung', 'weight' => 12],
                    ['name' => 'Puncak Bogor', 'weight' => 6],
                    ['name' => 'Kepulauan Seribu', 'weight' => 9],
                    ['name' => 'Kawah Putih Ciwidey', 'weight' => 7],
                    ['name' => 'Pantai Anyer', 'weight' => 5],
                ],
                'comments' => [
                    ['by' => 'citradewi', 'at' => 150, 'body' => 'Lembang selalu jadi pilihan aman. Banyak kafe dan tempat foto, udaranya adem, dan cukup lewat tol Cipularang. Cuma macet pas Sabtu pagi.', 'replies' => [
                        ['by' => 'radityakrn', 'at' => 200, 'body' => 'Betul, saya biasa berangkat Jumat malam supaya Sabtu sudah santai.'],
                    ]],
                    ['by' => 'fajarrmd', 'at' => 700, 'body' => 'Puncak sekarang saya hindari. Hampir tiap weekend macet parah, sistem one-way bikin waktu tempuh bisa dua kali lipat.'],
                    ['by' => 'nadiasaf', 'at' => 1400, 'body' => 'Kepulauan Seribu cocok kalau mau rame-rame. Pantainya jernih, snorkeling gampang. Tapi harus pesan penginapan jauh-jauh hari kalau mau dapat harga bagus.', 'replies' => [
                        ['by' => 'andisaputra', 'at' => 1500, 'body' => 'Betul, kalau berangkat dari Marina Ancol jam 7 pagi kita masih sempat main sebelum siang.'],
                    ]],
                    ['by' => 'bayusetiawan', 'at' => 3000, 'body' => 'Kawah Putih bagus buat foto, tapi bau belerangnya cukup menyengat. Yang punya asma atau bawa anak kecil sebaiknya pakai masker.'],
                    ['by' => 'hendragun', 'at' => 5000, 'body' => 'Anyer pantainya oke tapi kurang terawat di beberapa titik. Lebih enak cari yang di dalam area resort.'],
                ],
            ],
            [
                'title' => 'Kuliner Nusantara Paling Lezat & Otentik',
                'criteria' => 'Saya nilai dari kekayaan rasa dan rempah, konsistensi di berbagai kota, keotentikan resep, dan seberapa mudah ditemukan. Pilih berdasarkan versi terbaik yang pernah kamu makan, bukan sekadar nama besar.',
                'category' => 'kuliner', 'language' => 'id', 'region' => 'ID',
                'creator' => 'rinakartika', 'days_ago' => 18, 'popularity' => 1.4,
                'candidates' => [
                    ['name' => 'Rendang Daging Padang', 'weight' => 15],
                    ['name' => 'Sate Ayam Madura', 'weight' => 10],
                    ['name' => 'Nasi Goreng Spesial', 'weight' => 11],
                    ['name' => 'Bakso Malang Komplit', 'weight' => 8],
                ],
                'comments' => [
                    ['by' => 'hendragun', 'at' => 240, 'body' => 'Rendang yang asli Padang itu kering, gelap, dan bumbunya meresap sampai ke dalam. Yang di restoran Jawa sering terlalu basah dan manis. Beda jauh!', 'replies' => [
                        ['by' => 'rinakartika', 'at' => 300, 'body' => 'Betul Pak. Makanya di kriteria saya tulis "versi terbaik yang pernah kamu makan", soalnya variasinya banyak banget.'],
                    ]],
                    ['by' => 'radityakrn', 'at' => 1000, 'body' => 'Sate Madura itu bumbu kacangnya, dagingnya dibakar pas, ditambah lontong dan sambal. Selesai sudah. Nasi goreng enak sih, tapi variasinya terlalu banyak buat disatukan.'],
                    ['by' => 'tiaraamelia', 'at' => 2300, 'body' => 'Aku kasih ke bakso malang soalnya ada siomay, tahu goreng, pangsit. Satu mangkuk isinya komplet 😋', 'replies' => [
                        ['by' => 'wulansari', 'at' => 2400, 'body' => 'Setuju, tapi kuahnya jangan kebanyakan MSG ya hehe.'],
                    ]],
                    ['by' => 'fikrimaulana', 'at' => 4800, 'body' => 'Nasi goreng itu makanan paling gampang dibikin sendiri di rumah. Kalau dibandingkan, rendang dan sate menurutku lebih punya identitas.', 'replies' => [
                        ['by' => 'nadiasaf', 'at' => 4900, 'body' => 'Hmm, nasi goreng pinggir jalan jam 11 malam beda rasanya, Mas. Sensasinya nggak bisa ditiru di rumah 😄'],
                    ]],
                    ['by' => 'lestarin', 'at' => 8000, 'body' => 'Rendang sudah diakui dunia sebagai salah satu makanan terenak. Wajar kalau nomor satu.'],
                ],
            ],
            [
                'title' => 'Menu Sarapan Favorit Orang Indonesia',
                'criteria' => 'Menu sarapan yang paling bikin semangat memulai hari: rasa, bikin kenyang, dan gampang ditemukan di dekat rumah atau kantor. Boleh vote berdasarkan pengalaman pribadi.',
                'category' => 'kuliner', 'language' => 'id', 'region' => 'ID',
                'creator' => 'nadiasaf', 'days_ago' => 9, 'popularity' => 1.0,
                'candidates' => [
                    ['name' => 'Bubur Ayam', 'weight' => 13],
                    ['name' => 'Nasi Uduk Betawi', 'weight' => 10],
                    ['name' => 'Lontong Sayur', 'weight' => 7],
                    ['name' => 'Nasi Kuning', 'weight' => 6],
                    ['name' => 'Soto Betawi Kuah Susu', 'weight' => 5],
                ],
                'comments' => [
                    ['by' => 'bayusetiawan', 'at' => 120, 'body' => 'Bubur ayam itu diaduk atau nggak diaduk? Saya tim diaduk, dari lahir 😆', 'replies' => [
                        ['by' => 'rinakartika', 'at' => 170, 'body' => 'Tim nggak diaduk di sini! Biar kelihatan cantik dulu.'],
                        ['by' => 'galihpratama', 'at' => 230, 'body' => 'Perdebatan abadi bangsa Indonesia wkwk'],
                    ]],
                    ['by' => 'ekosusilo', 'at' => 900, 'body' => 'Nasi uduk dengan bawang goreng, semur jengkol, dan telur balado. Berat, tapi pas buat yang kerja lapangan.'],
                    ['by' => 'anisarahma', 'at' => 1800, 'body' => 'Lontong sayur buatan ibu saya masih juara, nomor satu di hati. Tapi kalau beli di luar, kuahnya sering kurang gurih.'],
                    ['by' => 'dimaspras', 'at' => 3000, 'body' => 'Soto Betawi buat sarapan agak berat ya, tapi pas hujan dingin enak banget. Saya vote karena kuah susunya khas.'],
                    ['by' => 'mayaanggr', 'at' => 4500, 'body' => 'Nasi kuning langganan saya dekat kantor, sambalnya mantap dan murah. Sayang cuma buka sampai jam 9 pagi.'],
                ],
            ],
            [
                'title' => 'Mobil Listrik Paling Layak Dibeli Keluarga',
                'criteria' => 'Mobil listrik untuk keluarga, dinilai dari jarak tempuh nyata (bukan angka brosur), kenyamanan kabin dan suspensi, kemudahan mengisi daya di rumah atau SPKLU, serta biaya kepemilikan seperti servis dan nilai jual kembali.',
                'category' => 'otomotif', 'language' => 'id', 'region' => 'ID',
                'creator' => 'fajarrmd', 'days_ago' => 16, 'popularity' => 1.2,
                'candidates' => [
                    ['name' => 'Hyundai Ioniq 5', 'weight' => 12],
                    ['name' => 'BYD Seal Performance', 'weight' => 9],
                    ['name' => 'BYD Atto 3', 'weight' => 8],
                    ['name' => 'Wuling Binguo EV', 'weight' => 7],
                ],
                'comments' => [
                    ['by' => 'rizkyhdyt', 'at' => 300, 'body' => 'Ioniq 5 kabinnya lega banget, lantai rata, dan charging 800V cepat. Cuma harga jual kembali EV di Indonesia masih belum jelas, itu yang bikin saya ragu.', 'replies' => [
                        ['by' => 'fajarrmd', 'at' => 360, 'body' => 'Poin resale memang masih tanda tanya buat semua EV. Makanya saya masukkan ke kriteria.'],
                    ]],
                    ['by' => 'ekosusilo', 'at' => 1100, 'body' => 'Binguo cocok buat dalam kota. Untuk harian ke kantor 25 km pulang-pergi sudah cukup, charging di rumah semalam penuh. Tapi buat luar kota ya mikir dulu.', 'replies' => [
                        ['by' => 'dimaspras', 'at' => 1200, 'body' => 'Setuju. Jarak tempuh nyatanya kata orang-orang sekitar 250 km, cukup buat harian.'],
                    ]],
                    ['by' => 'citradewi', 'at' => 2200, 'body' => 'BYD Seal akselerasinya gila, tapi sebagai mobil keluarga posisi duduk belakangnya agak rendah buat orang tua saya.'],
                    ['by' => 'galihpratama', 'at' => 3600, 'body' => 'Pengalaman pakai Atto 3 di Bekasi: charge di rumah pakai wallbox, tagihan listriknya jauh lebih murah dibanding bensin. Tapi pas banjir kemarin sempat deg-degan.', 'replies' => [
                        ['by' => 'fajarrmd', 'at' => 3700, 'body' => 'Tenang, baterainya tahan air, tapi tetap hindari banjir yang dalam kalau bisa 😅'],
                    ]],
                    ['by' => 'nadiasaf', 'at' => 5600, 'body' => 'SPKLU di jalur tol Trans Jawa sudah lumayan banyak, tapi antreannya pas libur panjang bikin capek.'],
                ],
            ],
            [
                'title' => 'Mobil Hybrid Keluarga: Mana yang Paling Irit & Nyaman?',
                'criteria' => 'Mobil hybrid untuk keluarga: seberapa irit BBM di pemakaian sehari-hari, kenyamanan kabin, ruang bagasi dan kursi, serta jaringan servis dan biaya perawatannya.',
                'category' => 'otomotif', 'language' => 'id', 'region' => 'ID',
                'creator' => 'rizkyhdyt', 'days_ago' => 4, 'popularity' => 0.8,
                'candidates' => [
                    ['name' => 'Toyota Innova Zenix Hybrid', 'weight' => 12],
                    ['name' => 'Honda CR-V Hybrid', 'weight' => 8],
                    ['name' => 'Toyota Corolla Cross Hybrid', 'weight' => 6],
                    ['name' => 'Toyota Yaris Cross Hybrid', 'weight' => 5],
                ],
                'comments' => [
                    ['by' => 'hendragun', 'at' => 180, 'body' => 'Innova Zenix Hybrid paling masuk akal buat keluarga besar: tiga baris, kabin luas, dan irit. Keluarga saya berenam dan masih nyaman.', 'replies' => [
                        ['by' => 'rizkyhdyt', 'at' => 260, 'body' => 'Betul Pak, makanya banyak yang inden. Cuma tunggunya lumayan lama.'],
                    ]],
                    ['by' => 'ekosusilo', 'at' => 700, 'body' => 'Konsumsi BBM Zenix Hybrid saya rata-rata 17-18 km/l di dalam kota Jakarta, jauh lebih irit dari Innova bensin yang lama.'],
                    ['by' => 'tiaraamelia', 'at' => 1500, 'body' => 'CR-V Hybrid kenyamanan berkendara dan handling-nya paling enak menurut saya. Tapi harganya sudah masuk segmen SUV premium.'],
                    ['by' => 'bayusetiawan', 'at' => 2400, 'body' => 'Yaris Cross Hybrid ukurannya pas buat parkir di mal dan masuk gang. Kabin cukup untuk empat orang dewasa, bagasi lumayan.'],
                    ['by' => 'safirandini', 'at' => 3300, 'body' => 'Ibu saya nanya, hybrid itu harus dicolok listrik kayak mobil listrik nggak? Katanya ada yang bilang nggak perlu.', 'replies' => [
                        ['by' => 'rizkyhdyt', 'at' => 3400, 'body' => 'Nggak perlu dicolok, Bu. Baterainya terisi dari mesin dan pengereman. Jadi pakainya sama seperti mobil bensin biasa.'],
                    ]],
                ],
            ],
            [
                'title' => 'Film & Serial Indonesia yang Paling Berkesan',
                'criteria' => 'Film atau serial Indonesia yang masih terbayang setelah ditonton: cerita, akting, sinematografi, dan dampaknya buat kamu. Bukan sekadar yang paling laris.',
                'category' => 'hiburan', 'language' => 'id', 'region' => 'ID',
                'creator' => 'putrimhrn', 'days_ago' => 12, 'popularity' => 1.1,
                'candidates' => [
                    ['name' => 'Gadis Kretek', 'weight' => 11],
                    ['name' => 'Pengabdi Setan', 'weight' => 10],
                    ['name' => 'Laskar Pelangi', 'weight' => 9],
                    ['name' => 'Ngeri-Ngeri Sedap', 'weight' => 8],
                    ['name' => 'Petualangan Sherina 2', 'weight' => 4],
                ],
                'comments' => [
                    ['by' => 'safirandini', 'at' => 260, 'body' => 'Gadis Kretek visualnya cantik banget, kostum dan latar tahun 60-an terasa hidup. Akting Dian Sastro dan Ario Bayu juga kuat.', 'replies' => [
                        ['by' => 'putrimhrn', 'at' => 330, 'body' => 'Betul, saya sampai nonton ulang dua kali cuma buat lihat detail kostumnya.'],
                    ]],
                    ['by' => 'dimaspras', 'at' => 1100, 'body' => 'Pengabdi Setan itu horor Indonesia yang akhirnya bikin saya betah nonton sampai habis. Atmosfernya dapet tanpa banyak jumpscare murahan.'],
                    ['by' => 'bagaswicak', 'at' => 2400, 'body' => 'Ngeri-Ngeri Sedap bikin saya nangis di bagian ending. Cerita keluarga Batak yang relatable banget, padahal saya bukan orang Batak.', 'replies' => [
                        ['by' => 'radityakrn', 'at' => 2500, 'body' => 'Sama! Habis nonton langsung telepon orang tua 😭'],
                    ]],
                    ['by' => 'lestarin', 'at' => 4000, 'body' => 'Laskar Pelangi ini film masa kecil saya. Walau sudah lama, pesan tentang pendidikannya masih relevan sampai sekarang.'],
                    ['by' => 'fikrimaulana', 'at' => 6500, 'body' => 'Petualangan Sherina 2 menurut saya kurang kuat dibanding yang pertama. Nostalgianya dapat, tapi ceritanya biasa saja.', 'replies' => [
                        ['by' => 'putrimhrn', 'at' => 6600, 'body' => 'Wajar sih, ekspektasinya sudah terlalu tinggi dari film pertama.'],
                    ]],
                ],
            ],
            [
                'title' => 'Dompet Digital Paling Praktis untuk Sehari-hari',
                'criteria' => 'Dompet digital untuk keperluan sehari-hari: seberapa banyak merchant yang menerima, kemudahan top-up dan transfer, biaya admin, promo yang masuk akal, dan keamanan akun.',
                'category' => 'finansial', 'language' => 'id', 'region' => 'ID',
                'creator' => 'mayaanggr', 'days_ago' => 8, 'popularity' => 0.9,
                'candidates' => [
                    ['name' => 'GoPay', 'weight' => 11],
                    ['name' => 'DANA', 'weight' => 10],
                    ['name' => 'OVO', 'weight' => 7],
                    ['name' => 'ShopeePay', 'weight' => 7],
                ],
                'comments' => [
                    ['by' => 'galihpratama', 'at' => 200, 'body' => 'GoPay paling sering saya pakai karena langsung nyambung dengan Gojek dan banyak merchant. Cashback-nya juga lumayan.'],
                    ['by' => 'anisarahma', 'at' => 800, 'body' => 'DANA buat saya paling ringan dan jarang error. Tampilannya simpel, top-up gampang, dan transfer ke bank jarang bermasalah.', 'replies' => [
                        ['by' => 'mayaanggr', 'at' => 900, 'body' => 'Setuju, aplikasinya ringan di HP lama juga.'],
                    ]],
                    ['by' => 'hendragun', 'at' => 2000, 'body' => 'ShopeePay karena sering belanja di Shopee, ada potongan harga dan gratis ongkir kalau bayar pakai itu. Di luar Shopee jarang saya pakai.'],
                    ['by' => 'bayusetiawan', 'at' => 3200, 'body' => 'OVO sekarang makin jarang saya buka. Dulu sering untuk Grab, tapi sekarang tempat yang saya datangi kebanyakan terima QRIS dari aplikasi apa saja.', 'replies' => [
                        ['by' => 'mayaanggr', 'at' => 3300, 'body' => 'Betul, QRIS bikin pilihan dompet digital jadi lebih bebas.'],
                    ]],
                    ['by' => 'wulansari', 'at' => 5000, 'body' => 'Apa pun dompetnya, aktifkan PIN dan jangan pernah kasih kode OTP ke siapa pun, termasuk yang ngaku CS.'],
                ],
            ],
            [
                'title' => 'Platform Belajar Online Terbaik untuk Skill Baru',
                'criteria' => 'Untuk belajar skill kerja secara mandiri: kualitas materi, kejelasan jalur belajar, interaksi dengan mentor atau komunitas, sertifikat yang diakui, dan harga yang masuk akal.',
                'category' => 'pendidikan', 'language' => 'id', 'region' => 'ID',
                'creator' => 'lestarin', 'days_ago' => 5, 'popularity' => 0.8,
                'candidates' => [
                    ['name' => 'Dicoding', 'weight' => 11],
                    ['name' => 'Coursera', 'weight' => 9],
                    ['name' => 'Udemy', 'weight' => 7],
                    ['name' => 'Skill Academy', 'weight' => 6],
                ],
                'comments' => [
                    ['by' => 'ekosusilo', 'at' => 240, 'body' => 'Dicoding jalur belajarnya terstruktur, ada submission yang direview. Pas buat yang baru mulai di dunia programming.'],
                    ['by' => 'intanpermata', 'at' => 900, 'body' => 'Coursera bagus karena materinya dari universitas luar negeri dan ada opsi bantuan biaya. Tapi sebagian besar berbahasa Inggris.', 'replies' => [
                        ['by' => 'lestarin', 'at' => 980, 'body' => 'Betul Kak, makanya saya masukkan kriteria interaksi dan komunitas. Bahasa Inggris memang jadi tantangan.'],
                    ]],
                    ['by' => 'radityakrn', 'at' => 1800, 'body' => 'Udemy kalau lagi diskon harganya murah banget dan materinya banyak. Cuma kualitas tiap kursus beda-beda, cek rating dulu.'],
                    ['by' => 'citradewi', 'at' => 3000, 'body' => 'Skill Academy saya coba untuk kelas desain grafis. Cukup membantu, tapi saya perlu latihan tambahan di luar videonya.'],
                ],
            ],
            [
                'title' => 'Best Noise-Cancelling Headphones for Commuting',
                'criteria' => 'For daily commuting: noise cancellation on trains and buses, comfort over a long ride, battery life, call quality, and whether the price feels fair. I care more about real-world use than spec sheets.',
                'category' => 'gadget', 'language' => 'en', 'region' => 'GLOBAL',
                'creator' => 'emmacarter', 'days_ago' => 11, 'popularity' => 0.9,
                'candidates' => [
                    ['name' => 'Sony WH-1000XM6', 'weight' => 12],
                    ['name' => 'Bose QuietComfort Ultra Headphones', 'weight' => 11],
                    ['name' => 'Apple AirPods Max', 'weight' => 7],
                    ['name' => 'Sennheiser Momentum 4 Wireless', 'weight' => 6],
                ],
                'comments' => [
                    ['by' => 'liamwalker', 'at' => 220, 'body' => 'Bose still has the most comfortable fit for me. I can wear the QuietComfort Ultra for a three hour train ride without my ears getting hot.', 'replies' => [
                        ['by' => 'emmacarter', 'at' => 280, 'body' => "Same, the clamp pressure is just right. Sony's a bit tighter on my head."],
                    ]],
                    ['by' => 'sofiamartinez', 'at' => 900, 'body' => 'I switched from the XM5 to the XM6 and the noise cancelling on the subway is noticeably better. Call quality too.'],
                    ['by' => 'noahbennett', 'at' => 1700, 'body' => "AirPods Max feel premium, but they're heavy and the case is... weird. Great if you're deep in the Apple ecosystem, otherwise I'd skip.", 'replies' => [
                        ['by' => 'oliviahughes', 'at' => 1800, 'body' => 'The case alone put me off. Who designed that?'],
                    ]],
                    ['by' => 'ethanbrooks', 'at' => 3000, 'body' => 'Momentum 4 has the best battery life by far. Nearly two weeks of commuting for me. ANC is a step behind the other two though.'],
                    ['by' => 'avamitchell', 'at' => 4200, 'body' => "Do any of these work well with Android? I don't want to lose features outside the Apple ecosystem.", 'replies' => [
                        ['by' => 'emmacarter', 'at' => 4300, 'body' => "Sony and Bose both have solid Android apps with EQ and multipoint. Haven't had any issues."],
                    ]],
                ],
            ],
            [
                'title' => 'TV Series Worth Binging, Ranked',
                'criteria' => "Shows you'd happily lose a weekend to: writing, performances, pacing, and whether the ending earns its payoff. No guilty-pleasure excuses needed.",
                'category' => 'hiburan', 'language' => 'en', 'region' => 'GLOBAL',
                'creator' => 'liamwalker', 'days_ago' => 7, 'popularity' => 0.9,
                'candidates' => [
                    ['name' => 'Severance', 'weight' => 12],
                    ['name' => 'Succession', 'weight' => 11],
                    ['name' => 'Shōgun', 'weight' => 9],
                    ['name' => 'The Bear', 'weight' => 8],
                ],
                'comments' => [
                    ['by' => 'lucasfoster', 'at' => 180, 'body' => 'Severance has the best premise of the decade and the set design alone is worth the watch.'],
                    ['by' => 'miasullivan', 'at' => 700, 'body' => "Succession's writing is untouchable. Every insult is a small work of art, and the finale stuck the landing.", 'replies' => [
                        ['by' => 'jackreynolds', 'at' => 790, 'body' => 'The last season had some incredible scenes. Brian Cox deserved every award.'],
                    ]],
                    ['by' => 'jackreynolds', 'at' => 1500, 'body' => 'Shōgun is gorgeous and the Japanese dialogue is subtitled with real care. Slow burn, but it pays off.'],
                    ['by' => 'oliviahughes', 'at' => 2300, 'body' => 'The Bear season one is anxiety in TV form. Loved it, but I needed breaks between episodes.', 'replies' => [
                        ['by' => 'liamwalker', 'at' => 2400, 'body' => 'Same! The single-take episode almost gave me a panic attack lol.'],
                    ]],
                    ['by' => 'ethanbrooks', 'at' => 3200, 'flag' => 'overrated', 'body' => "Honestly The Bear is overrated. It's stressful for the sake of being stressful."],
                ],
            ],
        ];
    }

    /**
     * Reports raised by members, tied to comments, rankings and objects above.
     *
     * @return list<array<string, mixed>>
     */
    public static function reports(): array
    {
        return [
            ['type' => 'comment', 'post' => 'Smartphone Flagship Terbaik 2026', 'match' => 'JUAL iPhone 16 Pro Max', 'reporter' => 'dimaspras', 'status' => 'resolved', 'hours_after' => 5,
                'reason' => 'Komentar ini iklan jual iPhone lengkap dengan nomor WhatsApp, tidak ada hubungannya dengan ranking.',
                'resolution' => 'Komentar berisi iklan dan dihapus dari tampilan.'],
            ['type' => 'comment', 'post' => 'Smartphone Flagship Terbaik 2026', 'match' => 'budak merek', 'reporter' => 'putrimhrn', 'status' => 'open', 'hours_after' => 9,
                'reason' => 'Menyerang orang yang memilih iPhone dengan kata-kata kasar, bukan membahas kandidatnya.',
                'resolution' => null],
            ['type' => 'comment', 'post' => 'TV Series Worth Binging, Ranked', 'match' => 'overrated', 'reporter' => 'noahbennett', 'status' => 'dismissed', 'hours_after' => 4,
                'reason' => 'This comment is just trolling the show.',
                'resolution' => 'Opini keras tapi masih membahas kandidat dan tidak menyerang pengguna lain.'],
            ['type' => 'ranking_post', 'post' => 'HP Gaming Paling Worth It Tahun Ini', 'reporter' => 'anisarahma', 'status' => 'dismissed', 'hours_after' => 30,
                'reason' => 'ROG Phone 9 beda kelas harga dengan kandidat lain, jadi perbandingannya kurang adil.',
                'resolution' => 'Pembuat ranking sudah menjelaskan ROG Phone 9 sebagai pembanding kelas atas di kriteria.'],
            ['type' => 'object', 'object' => 'Nasi Goreng Spesial', 'reporter' => 'fikrimaulana', 'status' => 'open', 'hours_after' => 48,
                'reason' => 'Nama "Spesial" terlalu umum, tiap penjual punya isian berbeda. Sebaiknya objek dijelaskan lebih spesifik.',
                'resolution' => null],
        ];
    }
}
