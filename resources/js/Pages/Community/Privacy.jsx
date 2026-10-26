import { Link, usePage } from '@inertiajs/react';
import { PolicyDocument } from './InfoPage';
import { path } from './Shared';

function content(locale, supportEmail) {
    const mail = <a href={`mailto:${supportEmail}`}>{supportEmail}</a>;

    if (locale === 'en') {
        return {
            title: 'Privacy Policy',
            intro: 'This policy explains what data Votmee collects when you use the platform, why we need it, who it is shared with, and the control you have over it.',
            summary: [
                'We only collect what we need to run your account, rankings, and community features.',
                'We never sell your personal data.',
                'Your private messages are visible only to the people in the conversation.',
                'You can edit your profile, change notifications, or delete your account at any time.',
            ],
            sections: [
                { id: 'data-we-collect', title: 'Data we collect', content: <>
                    <p>Depending on how you use Votmee, we collect the following:</p>
                    <ul>
                        <li><strong>Account data:</strong> name, email address, username, and a securely hashed password. If you sign in with Google, we receive your Google account ID, name, email, and profile photo.</li>
                        <li><strong>Profile data:</strong> profile photo, cover image, region, and language preference.</li>
                        <li><strong>Content you create:</strong> rankings, object proposals, wiki articles, comments, mentions, and images you upload.</li>
                        <li><strong>Activity:</strong> support votes, quick votes, saved rankings, points, missions, streaks, and achievements.</li>
                        <li><strong>Private messages:</strong> messages you send and receive, plus the people you block.</li>
                        <li><strong>Technical data:</strong> IP address, browser type, device information, and server logs needed for security and troubleshooting.</li>
                    </ul>
                </> },
                { id: 'how-we-use', title: 'How we use your data', content: <>
                    <ul>
                        <li>To create and secure your account and keep you signed in.</li>
                        <li>To show rankings, leaderboards, Hall of Fame, and recommendations that fit your language and region.</li>
                        <li>To run the point wallet, missions, and achievements, and to detect abuse such as vote manipulation or duplicate accounts.</li>
                        <li>To send notifications you have chosen to receive, and important service emails such as email verification or password resets.</li>
                        <li>To moderate content, handle reports, and enforce the <Link href={path('en', '/terms')}>User Agreement</Link>.</li>
                        <li>To understand and improve how Votmee performs.</li>
                    </ul>
                </> },
                { id: 'public-information', title: 'What is public', content: <>
                    <p>Votmee is a community platform, so some information is visible to everyone: your name, username, profile photo, cover image, avatar frame, achievements, and the rankings, comments, and articles you publish.</p>
                    <p>Your email address, password, private messages, point transaction history, and notification settings are never shown publicly.</p>
                </> },
                { id: 'sharing', title: 'Sharing with third parties', content: <>
                    <p>We do not sell or rent your personal data. We only share data with:</p>
                    <ul>
                        <li><strong>Service providers</strong> that host our servers, deliver email, and run real-time notifications, under obligations to protect it.</li>
                        <li><strong>Google</strong>, only if you choose to sign in with Google.</li>
                        <li><strong>Authorities</strong>, when required by law or to protect the safety of our users.</li>
                    </ul>
                </> },
                { id: 'cookies', title: 'Cookies and local storage', content: <>
                    <p>We use essential cookies to keep you signed in and to protect forms against forgery (CSRF). We also store a few preferences in your browser, such as your language and rankings you saved before signing in.</p>
                    <p>We do not use third-party advertising cookies. Blocking essential cookies will prevent you from signing in.</p>
                </> },
                { id: 'retention', title: 'Storage and security', content: <>
                    <p>We keep your data while your account is active. Passwords are stored as one-way hashes, and connections to Votmee are encrypted.</p>
                    <p>When you delete your account, it is deactivated immediately and can no longer be accessed. Any remaining data is kept only as long as needed for legal, security, or abuse-prevention reasons.</p>
                </> },
                { id: 'your-rights', title: 'Your rights', content: <>
                    <ul>
                        <li><strong>Access and correction:</strong> view and update your data in <a href="/profile">account settings</a>.</li>
                        <li><strong>Notification control:</strong> choose which notifications you receive in account settings.</li>
                        <li><strong>Deletion:</strong> delete your account from account settings at any time.</li>
                        <li><strong>Other requests:</strong> for a copy of your data or other questions, email {mail}.</li>
                    </ul>
                </> },
                { id: 'children', title: 'Children', content: <p>Votmee is not intended for children under 13. If you believe a child has created an account, please contact us so we can remove it.</p> },
                { id: 'changes', title: 'Changes to this policy', content: <p>We may update this policy as Votmee evolves. When we make significant changes, we will notify you on the platform before they take effect. The date at the top of this page always shows the latest version.</p> },
                { id: 'contact', title: 'Contact', content: <p>For questions about this policy or your data, email {mail} or visit the <Link href={path('en', '/support')}>Help Center</Link>.</p> },
            ],
        };
    }

    return {
        title: 'Kebijakan Privasi',
        intro: 'Kebijakan ini menjelaskan data apa yang dikumpulkan Votmee saat Anda menggunakan platform, alasan kami membutuhkannya, dengan siapa data dibagikan, dan kendali yang Anda miliki.',
        summary: [
            'Kami hanya mengumpulkan data yang dibutuhkan untuk menjalankan akun, ranking, dan fitur komunitas.',
            'Kami tidak pernah menjual data pribadi Anda.',
            'Pesan pribadi hanya dapat dilihat oleh peserta percakapan.',
            'Anda dapat mengubah profil, mengatur notifikasi, atau menghapus akun kapan saja.',
        ],
        sections: [
            { id: 'data-we-collect', title: 'Data yang kami kumpulkan', content: <>
                <p>Tergantung cara Anda menggunakan Votmee, kami mengumpulkan:</p>
                <ul>
                    <li><strong>Data akun:</strong> nama, alamat email, username, dan kata sandi yang disimpan dalam bentuk hash. Jika masuk dengan Google, kami menerima ID akun Google, nama, email, dan foto profil Anda.</li>
                    <li><strong>Data profil:</strong> foto profil, gambar sampul, wilayah, dan preferensi bahasa.</li>
                    <li><strong>Konten buatan Anda:</strong> ranking, usulan objek, artikel wiki, komentar, mention, dan gambar yang Anda unggah.</li>
                    <li><strong>Aktivitas:</strong> dukungan, quick vote, ranking tersimpan, poin, misi, streak, dan pencapaian.</li>
                    <li><strong>Pesan pribadi:</strong> pesan yang Anda kirim dan terima, serta pengguna yang Anda blokir.</li>
                    <li><strong>Data teknis:</strong> alamat IP, jenis browser, informasi perangkat, dan log server untuk keamanan serta penanganan masalah.</li>
                </ul>
            </> },
            { id: 'how-we-use', title: 'Cara kami menggunakan data', content: <>
                <ul>
                    <li>Membuat dan mengamankan akun Anda serta menjaga sesi login.</li>
                    <li>Menampilkan ranking, leaderboard, Hall of Fame, dan rekomendasi sesuai bahasa dan wilayah Anda.</li>
                    <li>Menjalankan dompet poin, misi, dan pencapaian, serta mendeteksi penyalahgunaan seperti manipulasi suara atau akun ganda.</li>
                    <li>Mengirim notifikasi yang Anda pilih, serta email penting seperti verifikasi email atau atur ulang kata sandi.</li>
                    <li>Memoderasi konten, menangani laporan, dan menegakkan <Link href={path('id', '/terms')}>Ketentuan Pengguna</Link>.</li>
                    <li>Memahami dan meningkatkan kinerja Votmee.</li>
                </ul>
            </> },
            { id: 'public-information', title: 'Informasi yang bersifat publik', content: <>
                <p>Votmee adalah platform komunitas, sehingga beberapa informasi dapat dilihat semua orang: nama, username, foto profil, gambar sampul, bingkai avatar, pencapaian, serta ranking, komentar, dan artikel yang Anda terbitkan.</p>
                <p>Alamat email, kata sandi, pesan pribadi, riwayat transaksi poin, dan pengaturan notifikasi tidak pernah ditampilkan secara publik.</p>
            </> },
            { id: 'sharing', title: 'Berbagi dengan pihak ketiga', content: <>
                <p>Kami tidak menjual atau menyewakan data pribadi Anda. Kami hanya membagikan data kepada:</p>
                <ul>
                    <li><strong>Penyedia layanan</strong> yang menjalankan server, pengiriman email, dan notifikasi real-time kami, dengan kewajiban untuk melindunginya.</li>
                    <li><strong>Google</strong>, hanya jika Anda memilih masuk dengan Google.</li>
                    <li><strong>Pihak berwenang</strong>, jika diwajibkan hukum atau untuk melindungi keselamatan pengguna.</li>
                </ul>
            </> },
            { id: 'cookies', title: 'Cookie dan penyimpanan lokal', content: <>
                <p>Kami menggunakan cookie esensial untuk menjaga Anda tetap masuk dan melindungi formulir dari pemalsuan (CSRF). Kami juga menyimpan beberapa preferensi di browser Anda, seperti bahasa dan ranking yang Anda simpan sebelum masuk.</p>
                <p>Kami tidak menggunakan cookie iklan pihak ketiga. Memblokir cookie esensial akan membuat Anda tidak dapat masuk.</p>
            </> },
            { id: 'retention', title: 'Penyimpanan dan keamanan', content: <>
                <p>Kami menyimpan data Anda selama akun aktif. Kata sandi disimpan sebagai hash satu arah, dan koneksi ke Votmee dienkripsi.</p>
                <p>Saat Anda menghapus akun, akun langsung dinonaktifkan dan tidak dapat diakses lagi. Data yang tersisa hanya disimpan selama diperlukan untuk alasan hukum, keamanan, atau pencegahan penyalahgunaan.</p>
            </> },
            { id: 'your-rights', title: 'Hak Anda', content: <>
                <ul>
                    <li><strong>Akses dan koreksi:</strong> lihat dan perbarui data Anda di <a href="/profile">pengaturan akun</a>.</li>
                    <li><strong>Kendali notifikasi:</strong> pilih notifikasi yang ingin Anda terima di pengaturan akun.</li>
                    <li><strong>Penghapusan:</strong> hapus akun Anda dari pengaturan akun kapan saja.</li>
                    <li><strong>Permintaan lain:</strong> untuk salinan data atau pertanyaan lain, kirim email ke {mail}.</li>
                </ul>
            </> },
            { id: 'children', title: 'Anak-anak', content: <p>Votmee tidak ditujukan untuk anak di bawah 13 tahun. Jika Anda mengetahui ada anak yang membuat akun, hubungi kami agar akun tersebut dapat dihapus.</p> },
            { id: 'changes', title: 'Perubahan kebijakan', content: <p>Kami dapat memperbarui kebijakan ini seiring perkembangan Votmee. Untuk perubahan penting, kami akan memberi tahu Anda di platform sebelum berlaku. Tanggal di bagian atas halaman ini selalu menunjukkan versi terbaru.</p> },
            { id: 'contact', title: 'Kontak', content: <p>Untuk pertanyaan tentang kebijakan ini atau data Anda, kirim email ke {mail} atau kunjungi <Link href={path('id', '/support')}>Pusat Bantuan</Link>.</p> },
        ],
    };
}

export default function Privacy() {
    const { locale, supportEmail } = usePage().props;
    const copy = content(locale, supportEmail);

    return <PolicyDocument
        active="privacy"
        eyebrow={locale === 'id' ? 'Privasi & data' : 'Privacy & data'}
        {...copy}
    />;
}
