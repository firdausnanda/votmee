import { Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { InfoShell } from './InfoPage';
import { NavIcon, path } from './Shared';

function helpTopics(locale) {
    if (locale === 'en') {
        return [
            { id: 'account', icon: 'user', title: 'Account & sign in', description: 'Sign up, passwords, and profile settings.', questions: [
                ['How do I create an account?', 'Select "Sign up" at the top of the page, then register with your email or continue with Google. After registering, confirm your email address using the link we send you.'],
                ['I forgot my password. What should I do?', 'Open the sign-in window and choose "Forgot password". We will email you a link to set a new password. Accounts created with Google can always sign in with Google.'],
                ['Can I change my username?', 'No. Your username is your public handle and profile URL, so it cannot be changed after it is set. You can still change your display name, photo, and cover in account settings.'],
                ['How do I delete my account?', 'Open account settings, scroll to "Danger Zone: Delete Account", and confirm with your password. Deletion takes effect immediately.'],
            ] },
            { id: 'rankings', icon: 'trophy', title: 'Rankings & support', description: 'Creating rankings and backing candidates.', questions: [
                ['How do I create a ranking?', 'Once signed in, select "Create ranking". Choose a topic and category, add candidates from existing objects or propose new ones, then review and publish.'],
                ['How is the ranking order decided?', 'Order is based on support from the community. Votmee never sells ranking positions.'],
                ['Why is my proposed object not showing yet?', 'New object proposals are reviewed by moderators to avoid duplicates and keep data accurate. They appear once verified.'],
                ['What is Quick Vote?', 'Quick Vote shows two candidates side by side. Pick the one you prefer to help the community and complete your daily mission.'],
            ] },
            { id: 'points', icon: 'wallet', title: 'Points & missions', description: 'Wallet, daily bonus, streaks, and rewards.', questions: [
                ['How do I earn points?', 'Claim the daily bonus in your point wallet, complete daily and weekly missions, and keep your streak going for extra bonuses.'],
                ['What can I do with points?', 'Use points to support candidates in rankings. Points are virtual rewards with no cash value and cannot be exchanged for money.'],
                ['Why did my mission reward disappear?', 'Completed missions must be claimed in your wallet. Unclaimed rewards expire some days after their period ends.'],
                ['How do I get into the Hall of Fame?', 'The Hall of Fame celebrates the top contributors each month, from influential ranking makers to curators and wiki writers. Stay active and helpful to climb the boards.'],
            ] },
            { id: 'safety', icon: 'shield', title: 'Safety & reports', description: 'Reporting content and protecting yourself.', questions: [
                ['How do I report content or a user?', 'Use the "Report" option on the ranking, comment, or profile, then choose a reason. Moderators review every report.'],
                ['How do I stop someone from messaging me?', 'Open the conversation in Messages and choose "Block". Blocked users can no longer send you messages.'],
                ['My content was removed. Can I appeal?', 'Yes. Email us with a link to the content and a short explanation, and we will review the decision.'],
                ['How do I manage notifications?', 'Open account settings to choose which notifications you receive.'],
            ] },
        ];
    }

    return [
        { id: 'account', icon: 'user', title: 'Akun & masuk', description: 'Pendaftaran, kata sandi, dan pengaturan profil.', questions: [
            ['Bagaimana cara membuat akun?', 'Pilih "Daftar" di bagian atas halaman, lalu daftar dengan email atau lanjutkan dengan Google. Setelah mendaftar, konfirmasi alamat email Anda melalui tautan yang kami kirim.'],
            ['Saya lupa kata sandi. Apa yang harus dilakukan?', 'Buka jendela masuk dan pilih "Lupa kata sandi". Kami akan mengirim tautan untuk membuat kata sandi baru. Akun yang dibuat dengan Google selalu dapat masuk dengan Google.'],
            ['Apakah username bisa diubah?', 'Tidak. Username adalah identitas publik dan URL profil Anda, sehingga tidak dapat diubah setelah ditetapkan. Nama tampilan, foto, dan sampul tetap bisa diubah di pengaturan akun.'],
            ['Bagaimana cara menghapus akun?', 'Buka pengaturan akun, gulir ke bagian "Zona Bahaya: Hapus Akun", lalu konfirmasi dengan kata sandi Anda. Penghapusan langsung berlaku.'],
        ] },
        { id: 'rankings', icon: 'trophy', title: 'Ranking & dukungan', description: 'Membuat ranking dan mendukung kandidat.', questions: [
            ['Bagaimana cara membuat ranking?', 'Setelah masuk, pilih "Buat ranking". Tentukan topik dan kategori, tambahkan kandidat dari objek yang ada atau ajukan objek baru, lalu tinjau dan terbitkan.'],
            ['Bagaimana urutan ranking ditentukan?', 'Urutan ditentukan oleh dukungan dari komunitas. Votmee tidak pernah menjual posisi ranking.'],
            ['Kenapa objek yang saya ajukan belum muncul?', 'Usulan objek baru ditinjau moderator untuk menghindari duplikat dan menjaga keakuratan data. Objek akan muncul setelah terverifikasi.'],
            ['Apa itu Quick Vote?', 'Quick Vote menampilkan dua kandidat berdampingan. Pilih yang Anda sukai untuk membantu komunitas sekaligus menyelesaikan misi harian.'],
        ] },
        { id: 'points', icon: 'wallet', title: 'Poin & misi', description: 'Dompet, bonus harian, streak, dan hadiah.', questions: [
            ['Bagaimana cara mendapatkan poin?', 'Klaim bonus harian di dompet poin, selesaikan misi harian dan mingguan, dan pertahankan streak untuk bonus tambahan.'],
            ['Poin bisa digunakan untuk apa?', 'Poin digunakan untuk mendukung kandidat di ranking. Poin adalah hadiah virtual tanpa nilai uang dan tidak dapat ditukar dengan uang.'],
            ['Kenapa hadiah misi saya hilang?', 'Misi yang selesai harus diklaim di dompet poin. Hadiah yang tidak diklaim akan kedaluwarsa beberapa hari setelah periodenya berakhir.'],
            ['Bagaimana cara masuk Hall of Fame?', 'Hall of Fame merayakan kontributor terbaik setiap bulan, mulai dari pembuat ranking berpengaruh hingga kurator dan penulis wiki. Tetap aktif dan bermanfaat untuk naik peringkat.'],
        ] },
        { id: 'safety', icon: 'shield', title: 'Keamanan & laporan', description: 'Melaporkan konten dan melindungi diri.', questions: [
            ['Bagaimana cara melaporkan konten atau pengguna?', 'Gunakan opsi "Laporkan" pada ranking, komentar, atau profil, lalu pilih alasannya. Moderator meninjau setiap laporan.'],
            ['Bagaimana agar seseorang berhenti mengirim pesan?', 'Buka percakapan di Pesan lalu pilih "Blokir". Pengguna yang diblokir tidak dapat mengirim pesan kepada Anda lagi.'],
            ['Konten saya dihapus. Bisakah saya mengajukan banding?', 'Bisa. Kirim email berisi tautan konten dan penjelasan singkat, dan kami akan meninjau kembali keputusan tersebut.'],
            ['Bagaimana cara mengatur notifikasi?', 'Buka pengaturan akun untuk memilih notifikasi yang ingin Anda terima.'],
        ] },
    ];
}

export default function Support() {
    const { locale, supportEmail } = usePage().props;
    const isId = locale === 'id';
    const topics = useMemo(() => helpTopics(locale), [locale]);
    const [query, setQuery] = useState('');
    const normalizedQuery = query.trim().toLowerCase();
    const visibleTopics = topics
        .map(topic => ({
            ...topic,
            questions: normalizedQuery
                ? topic.questions.filter(([question, answer]) => `${question} ${answer}`.toLowerCase().includes(normalizedQuery))
                : topic.questions,
        }))
        .filter(topic => topic.questions.length > 0);
    const resultCount = visibleTopics.reduce((total, topic) => total + topic.questions.length, 0);

    return <InfoShell
        active="support"
        eyebrow={isId ? 'Bantuan' : 'Support'}
        title={isId ? 'Ada yang bisa kami bantu?' : 'How can we help?'}
        intro={isId
            ? 'Temukan jawaban seputar akun, ranking, poin, dan keamanan. Tidak menemukan yang dicari? Tim kami siap membantu.'
            : 'Find answers about accounts, rankings, points, and safety. Can’t find what you need? Our team is here to help.'}
    >
        <div className="vif-support">
            <form className="vif-search" role="search" onSubmit={event => event.preventDefault()}>
                <NavIcon name="search" size={19} />
                <label htmlFor="vif-help-search" className="vt-sr">{isId ? 'Cari bantuan' : 'Search help'}</label>
                <input
                    id="vif-help-search"
                    type="search"
                    value={query}
                    onChange={event => setQuery(event.target.value)}
                    placeholder={isId ? 'Cari pertanyaan, mis. "poin" atau "kata sandi"' : 'Search questions, e.g. "points" or "password"'}
                    autoComplete="off"
                />
                {query && <button type="button" onClick={() => setQuery('')} aria-label={isId ? 'Hapus pencarian' : 'Clear search'}><NavIcon name="x" size={16} /></button>}
            </form>
            {normalizedQuery && <p className="vif-search-status" role="status">
                {resultCount > 0
                    ? (isId ? `${resultCount} jawaban ditemukan` : `${resultCount} answers found`)
                    : (isId ? 'Tidak ada jawaban yang cocok. Coba kata lain atau hubungi kami.' : 'No matching answers. Try another word or contact us.')}
            </p>}

            {!normalizedQuery && <div className="vif-topics">
                {topics.map(topic => <a key={topic.id} href={`#${topic.id}`} className="vif-topic">
                    <span className="vif-topic-icon" aria-hidden="true"><NavIcon name={topic.icon} size={20} /></span>
                    <strong>{topic.title}</strong>
                    <small>{topic.description}</small>
                    <span className="vif-topic-count">{isId ? `${topic.questions.length} artikel` : `${topic.questions.length} articles`}</span>
                </a>)}
            </div>}

            <div className="vif-faq">
                {visibleTopics.map(topic => <section key={topic.id} id={topic.id} className="vif-faq-group" aria-labelledby={`${topic.id}-title`}>
                    <h2 id={`${topic.id}-title`}><NavIcon name={topic.icon} size={18} />{topic.title}</h2>
                    {topic.questions.map(([question, answer]) => <details key={question} className="vif-faq-item" open={Boolean(normalizedQuery)}>
                        <summary>{question}<NavIcon name="chevronDown" size={18} /></summary>
                        <p>{answer}</p>
                    </details>)}
                </section>)}
            </div>

            <section className="vif-contact" aria-labelledby="vif-contact-title">
                <div className="vif-contact-main">
                    <p className="vif-eyebrow">{isId ? 'Hubungi kami' : 'Contact us'}</p>
                    <h2 id="vif-contact-title">{isId ? 'Masih butuh bantuan?' : 'Still need help?'}</h2>
                    <p>{isId
                        ? 'Kirim email ke tim dukungan kami. Kami biasanya membalas dalam 1–2 hari kerja.'
                        : 'Email our support team. We usually reply within 1–2 business days.'}</p>
                    <a href={`mailto:${supportEmail}`} className="vif-button vif-button-primary"><NavIcon name="mail" size={17} />{supportEmail}</a>
                </div>
                <div className="vif-contact-tips">
                    <p>{isId ? 'Agar lebih cepat ditangani, sertakan:' : 'To help us respond faster, include:'}</p>
                    <ul>
                        <li><NavIcon name="check" size={15} />{isId ? 'Username Votmee Anda' : 'Your Votmee username'}</li>
                        <li><NavIcon name="check" size={15} />{isId ? 'Tautan ke ranking, objek, atau komentar terkait' : 'A link to the related ranking, object, or comment'}</li>
                        <li><NavIcon name="check" size={15} />{isId ? 'Tangkapan layar jika ada pesan error' : 'A screenshot if you saw an error'}</li>
                    </ul>
                    <div className="vif-contact-links">
                        <Link href={path(locale, '/privacy')}>{isId ? 'Kebijakan Privasi' : 'Privacy Policy'}</Link>
                        <Link href={path(locale, '/terms')}>{isId ? 'Ketentuan Pengguna' : 'User Agreement'}</Link>
                    </div>
                </div>
            </section>
        </div>
    </InfoShell>;
}
