import { Link, usePage } from '@inertiajs/react';
import { PolicyDocument } from './InfoPage';
import { path } from './Shared';

function content(locale, supportEmail) {
    const mail = <a href={`mailto:${supportEmail}`}>{supportEmail}</a>;

    if (locale === 'en') {
        return {
            title: 'User Agreement',
            intro: 'These terms govern your use of Votmee. By creating an account or using the platform, you agree to follow them. Please read them carefully.',
            summary: [
                'Be honest: one person, one account, and no vote manipulation.',
                'You own what you post, and you give Votmee permission to display it.',
                'Points are virtual rewards with no cash value.',
                'Rankings reflect community opinion, not professional advice.',
            ],
            sections: [
                { id: 'acceptance', title: 'Accepting these terms', content: <p>By accessing or using Votmee, you agree to this User Agreement and our <Link href={path('en', '/privacy')}>Privacy Policy</Link>. If you do not agree, please do not use the platform.</p> },
                { id: 'accounts', title: 'Your account', content: <ul>
                    <li>You must be at least 13 years old to create an account.</li>
                    <li>Provide accurate information and keep your password secure. You are responsible for all activity on your account.</li>
                    <li>Each person may have only one account. Usernames must not impersonate other people, brands, or Votmee itself.</li>
                    <li>Tell us right away at {mail} if you suspect unauthorized access to your account.</li>
                </ul> },
                { id: 'your-content', title: 'Your content', content: <>
                    <p>You keep ownership of the rankings, comments, articles, and images you post. By posting, you give Votmee a worldwide, non-exclusive, royalty-free license to host, display, adapt for display, and distribute that content on the platform and in our promotional materials.</p>
                    <p>You are responsible for your content and confirm that you have the right to post it, including any images.</p>
                    <p>Wiki articles and object summaries are collaborative: other members may edit them, and approved revisions remain part of the article.</p>
                </> },
                { id: 'community-guidelines', title: 'Community guidelines', content: <>
                    <p>Keep Votmee a fair and respectful place. You may not:</p>
                    <ul>
                        <li>Manipulate votes, support, or points, including with multiple accounts, bots, or coordinated vote exchanges.</li>
                        <li>Post spam, misleading content, or undisclosed paid promotion.</li>
                        <li>Harass, threaten, or spread hate toward individuals or groups.</li>
                        <li>Share other people's personal information without their consent.</li>
                        <li>Post illegal, sexually explicit, or violent content, or content that infringes copyright or trademarks.</li>
                        <li>Interfere with the security or operation of the platform.</li>
                    </ul>
                </> },
                { id: 'points', title: 'Rankings, support, and points', content: <ul>
                    <li>Points are virtual rewards for taking part in the community. They have no cash value and cannot be sold, transferred outside the platform, or exchanged for money.</li>
                    <li>Daily bonuses, missions, and achievements follow the rules shown in your point wallet. Unclaimed mission rewards may expire.</li>
                    <li>We may adjust or remove points, support, or achievements gained through errors or violations of these terms.</li>
                    <li>Ranking order is determined by community activity. Votmee does not sell ranking positions.</li>
                </ul> },
                { id: 'moderation', title: 'Moderation and enforcement', content: <>
                    <p>Anyone can report content using the report button. Our moderators review reports and may remove content, reject object proposals, or restrict, suspend, or permanently close accounts that break these terms.</p>
                    <p>If you believe a decision was a mistake, contact us at {mail} and we will review it.</p>
                </> },
                { id: 'intellectual-property', title: 'Votmee intellectual property', content: <p>The Votmee name, logo, design, and software belong to Votmee. You may not copy, modify, or use them without our written permission, except as allowed by law.</p> },
                { id: 'disclaimer', title: 'Disclaimer', content: <p>Rankings, reviews, and articles on Votmee are the opinions of community members. They are not professional, financial, medical, or legal advice. Votmee is provided "as is" and we do not guarantee that it will always be available or error-free.</p> },
                { id: 'liability', title: 'Limitation of liability', content: <p>To the extent permitted by law, Votmee is not liable for indirect or consequential losses arising from your use of the platform, from content posted by other members, or from decisions you make based on rankings.</p> },
                { id: 'termination', title: 'Closing your account', content: <p>You can stop using Votmee and delete your account at any time from <a href="/profile">account settings</a>. We may suspend or close accounts that seriously or repeatedly break these terms.</p> },
                { id: 'changes', title: 'Changes to these terms', content: <p>We may update these terms from time to time. For significant changes, we will notify you on the platform before they take effect. Continuing to use Votmee after that means you accept the updated terms.</p> },
                { id: 'governing-law', title: 'Governing law', content: <p>These terms are governed by the laws of the Republic of Indonesia. Any dispute will first be resolved amicably, and if that fails, through the competent courts in Indonesia.</p> },
                { id: 'contact', title: 'Contact', content: <p>Questions about these terms? Email {mail} or visit the <Link href={path('en', '/support')}>Help Center</Link>.</p> },
            ],
        };
    }

    return {
        title: 'Ketentuan Pengguna',
        intro: 'Ketentuan ini mengatur penggunaan Votmee. Dengan membuat akun atau menggunakan platform, Anda setuju untuk mematuhinya. Mohon baca dengan saksama.',
        summary: [
            'Jujur: satu orang satu akun, dan tanpa manipulasi suara.',
            'Konten Anda tetap milik Anda, dan Anda mengizinkan Votmee menampilkannya.',
            'Poin adalah hadiah virtual tanpa nilai uang.',
            'Ranking mencerminkan pendapat komunitas, bukan saran profesional.',
        ],
        sections: [
            { id: 'acceptance', title: 'Penerimaan ketentuan', content: <p>Dengan mengakses atau menggunakan Votmee, Anda menyetujui Ketentuan Pengguna ini dan <Link href={path('id', '/privacy')}>Kebijakan Privasi</Link> kami. Jika tidak setuju, mohon untuk tidak menggunakan platform.</p> },
            { id: 'accounts', title: 'Akun Anda', content: <ul>
                <li>Anda harus berusia minimal 13 tahun untuk membuat akun.</li>
                <li>Berikan informasi yang benar dan jaga kerahasiaan kata sandi. Anda bertanggung jawab atas semua aktivitas di akun Anda.</li>
                <li>Setiap orang hanya boleh memiliki satu akun. Username tidak boleh meniru orang lain, merek, atau Votmee.</li>
                <li>Segera hubungi kami di {mail} jika Anda mencurigai akses tanpa izin ke akun Anda.</li>
            </ul> },
            { id: 'your-content', title: 'Konten Anda', content: <>
                <p>Anda tetap memiliki ranking, komentar, artikel, dan gambar yang Anda unggah. Dengan mengunggah, Anda memberi Votmee lisensi non-eksklusif, berlaku di seluruh dunia, dan bebas royalti untuk menyimpan, menampilkan, menyesuaikan tampilan, dan mendistribusikan konten tersebut di platform dan materi promosi kami.</p>
                <p>Anda bertanggung jawab atas konten Anda dan menjamin bahwa Anda berhak mengunggahnya, termasuk gambar.</p>
                <p>Artikel wiki dan ringkasan objek bersifat kolaboratif: anggota lain dapat menyuntingnya, dan revisi yang disetujui tetap menjadi bagian artikel.</p>
            </> },
            { id: 'community-guidelines', title: 'Pedoman komunitas', content: <>
                <p>Jaga Votmee tetap adil dan saling menghormati. Anda dilarang:</p>
                <ul>
                    <li>Memanipulasi suara, dukungan, atau poin, termasuk dengan akun ganda, bot, atau tukar suara yang terkoordinasi.</li>
                    <li>Mengunggah spam, konten menyesatkan, atau promosi berbayar tanpa keterangan.</li>
                    <li>Melecehkan, mengancam, atau menyebarkan kebencian terhadap individu atau kelompok.</li>
                    <li>Membagikan informasi pribadi orang lain tanpa izin.</li>
                    <li>Mengunggah konten ilegal, pornografi, kekerasan, atau yang melanggar hak cipta maupun merek dagang.</li>
                    <li>Mengganggu keamanan atau operasional platform.</li>
                </ul>
            </> },
            { id: 'points', title: 'Ranking, dukungan, dan poin', content: <ul>
                <li>Poin adalah hadiah virtual atas partisipasi di komunitas. Poin tidak bernilai uang dan tidak dapat dijual, dipindahkan ke luar platform, atau ditukar dengan uang.</li>
                <li>Bonus harian, misi, dan pencapaian mengikuti aturan yang ditampilkan di dompet poin. Hadiah misi yang tidak diklaim dapat kedaluwarsa.</li>
                <li>Kami dapat menyesuaikan atau menghapus poin, dukungan, atau pencapaian yang diperoleh karena kesalahan sistem atau pelanggaran ketentuan.</li>
                <li>Urutan ranking ditentukan oleh aktivitas komunitas. Votmee tidak menjual posisi ranking.</li>
            </ul> },
            { id: 'moderation', title: 'Moderasi dan penegakan', content: <>
                <p>Siapa pun dapat melaporkan konten melalui tombol laporkan. Moderator kami meninjau laporan dan dapat menghapus konten, menolak usulan objek, atau membatasi, menangguhkan, hingga menutup permanen akun yang melanggar ketentuan.</p>
                <p>Jika Anda merasa keputusan tersebut keliru, hubungi kami di {mail} dan kami akan meninjaunya kembali.</p>
            </> },
            { id: 'intellectual-property', title: 'Hak kekayaan intelektual Votmee', content: <p>Nama, logo, desain, dan perangkat lunak Votmee adalah milik Votmee. Anda tidak boleh menyalin, mengubah, atau menggunakannya tanpa izin tertulis dari kami, kecuali diizinkan oleh hukum.</p> },
            { id: 'disclaimer', title: 'Penafian', content: <p>Ranking, ulasan, dan artikel di Votmee adalah pendapat anggota komunitas, bukan saran profesional, keuangan, medis, atau hukum. Votmee disediakan "sebagaimana adanya" dan kami tidak menjamin platform selalu tersedia atau bebas dari kesalahan.</p> },
            { id: 'liability', title: 'Batasan tanggung jawab', content: <p>Sejauh diizinkan hukum, Votmee tidak bertanggung jawab atas kerugian tidak langsung yang timbul dari penggunaan platform, dari konten yang diunggah anggota lain, atau dari keputusan yang Anda ambil berdasarkan ranking.</p> },
            { id: 'termination', title: 'Menutup akun', content: <p>Anda dapat berhenti menggunakan Votmee dan menghapus akun kapan saja melalui <a href="/profile">pengaturan akun</a>. Kami dapat menangguhkan atau menutup akun yang melanggar ketentuan secara serius atau berulang.</p> },
            { id: 'changes', title: 'Perubahan ketentuan', content: <p>Kami dapat memperbarui ketentuan ini dari waktu ke waktu. Untuk perubahan penting, kami akan memberi tahu Anda di platform sebelum berlaku. Dengan tetap menggunakan Votmee setelahnya, Anda dianggap menyetujui ketentuan yang diperbarui.</p> },
            { id: 'governing-law', title: 'Hukum yang berlaku', content: <p>Ketentuan ini tunduk pada hukum Republik Indonesia. Setiap perselisihan akan diselesaikan terlebih dahulu secara musyawarah, dan jika tidak tercapai, melalui pengadilan yang berwenang di Indonesia.</p> },
            { id: 'contact', title: 'Kontak', content: <p>Ada pertanyaan tentang ketentuan ini? Kirim email ke {mail} atau kunjungi <Link href={path('id', '/support')}>Pusat Bantuan</Link>.</p> },
        ],
    };
}

export default function Terms() {
    const { locale, supportEmail } = usePage().props;
    const copy = content(locale, supportEmail);

    return <PolicyDocument
        active="terms"
        eyebrow={locale === 'id' ? 'Aturan penggunaan' : 'Terms of use'}
        {...copy}
    />;
}
