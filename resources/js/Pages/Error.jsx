import { Head, Link } from '@inertiajs/react';

const messages = {
    403: {
        reason: 'Forbidden',
        id: ['Akses ditolak', 'Anda tidak memiliki izin untuk membuka halaman ini. Masuk dengan akun lain atau kembali ke beranda.'],
        en: ['Access denied', 'You do not have permission to open this page. Sign in with another account or head back home.'],
    },
    404: {
        reason: 'Not Found',
        id: ['Halaman tidak ditemukan', 'Halaman yang Anda cari mungkin sudah dipindahkan, dihapus, atau alamatnya salah ketik.'],
        en: ['Page not found', 'The page you are looking for may have been moved, removed, or the address was mistyped.'],
    },
    419: {
        reason: 'Page Expired',
        id: ['Sesi berakhir', 'Demi keamanan, sesi Anda sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.'],
        en: ['Session expired', 'For your security, your session has expired. Reload the page and try again.'],
    },
    429: {
        reason: 'Too Many Requests',
        id: ['Terlalu banyak permintaan', 'Anda mengirim terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar sebelum mencoba lagi.'],
        en: ['Too many requests', 'You sent too many requests in a short time. Please wait a moment before trying again.'],
    },
    500: {
        reason: 'Server Error',
        id: ['Terjadi kesalahan', 'Ada masalah di server kami dan tim kami sudah diberi tahu. Silakan coba lagi dalam beberapa saat.'],
        en: ['Something went wrong', 'Something broke on our server and our team has been notified. Please try again in a moment.'],
    },
    503: {
        reason: 'Service Unavailable',
        id: ['Sedang dalam pemeliharaan', 'Votmee sedang diperbarui agar lebih cepat dan andal. Kami akan segera kembali.'],
        en: ['Under maintenance', 'Votmee is being upgraded to be faster and more reliable. We will be back shortly.'],
    },
};

const retryableStatuses = [419, 429, 500, 503];

function StatusIcon({ status }) {
    const paths = {
        403: <><rect x="4" y="11" width="16" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" /></>,
        404: <><circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3M8.5 11h5" /></>,
        419: <><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>,
        429: <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" />,
        503: <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />,
    };

    return (
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            {paths[status] ?? <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0zM12 9v4M12 17h.01" />}
        </svg>
    );
}

function ArrowIcon({ direction = 'right' }) {
    return (
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.25" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d={direction === 'left' ? 'M19 12H5M12 19l-7-7 7-7' : 'M5 12h14M12 5l7 7-7 7'} />
        </svg>
    );
}

export default function Error({ status = 500, locale = 'id' }) {
    const isId = locale !== 'en';
    const content = messages[status] ?? messages[500];
    const [title, description] = content[isId ? 'id' : 'en'];
    const canRetry = retryableStatuses.includes(status);
    const isMaintenance = status === 503;

    const currentPath = typeof window === 'undefined' ? `/${locale}` : window.location.pathname;
    const otherLocale = isId ? 'en' : 'id';
    const switchLocaleHref = /^\/(id|en)(\/|$)/.test(currentPath)
        ? currentPath.replace(/^\/(id|en)/, `/${otherLocale}`)
        : `/${otherLocale}`;

    const usefulLinks = [
        { href: `/${locale}`, label: isId ? 'Beranda' : 'Home', hint: isId ? 'Ranking terbaru dari komunitas' : 'Latest rankings from the community' },
        { href: `/${locale}/explore`, label: isId ? 'Jelajahi' : 'Explore', hint: isId ? 'Cari topik dan objek favorit' : 'Find topics and favorite objects' },
        { href: `/${locale}/hall-of-fame`, label: 'Hall of Fame', hint: isId ? 'Kontributor terbaik Votmee' : 'Top Votmee contributors' },
    ];

    const goBack = () => {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = `/${locale}`;
        }
    };

    return (
        <div className="vt-error-page" data-status={status}>
            <Head title={`${status} · ${title}`}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <div className="vt-error-backdrop" aria-hidden="true" />

            <header className="vt-error-header">
                <a href={`/${locale}`} className="vt-error-brand" aria-label={`Votmee ${isId ? 'Beranda' : 'Home'}`}>
                    votmee<span>.</span>
                </a>
                <a href={switchLocaleHref} className="vt-error-locale" hrefLang={otherLocale}>
                    <span className={isId ? 'active' : ''}>ID</span>
                    <span className={isId ? '' : 'active'}>EN</span>
                </a>
            </header>

            <main className="vt-error-main">
                <section className="vt-error-content" aria-labelledby="vt-error-title">
                    <h1 id="vt-error-title">{title}</h1>
                    <p className="vt-error-description">{description}</p>

                    <div className="vt-error-actions">
                        {canRetry ? (
                            <button type="button" className="vt-error-button vt-error-button-primary" onClick={() => window.location.reload()}>
                                {isId ? 'Muat ulang halaman' : 'Reload page'}
                            </button>
                        ) : (
                            <Link href={`/${locale}`} className="vt-error-button vt-error-button-primary">
                                {isId ? 'Kembali ke Beranda' : 'Back to Home'} <ArrowIcon />
                            </Link>
                        )}
                        {!isMaintenance && (canRetry ? (
                            <a href={`/${locale}`} className="vt-error-button vt-error-button-ghost">
                                {isId ? 'Kembali ke Beranda' : 'Back to Home'}
                            </a>
                        ) : (
                            <button type="button" className="vt-error-button vt-error-button-ghost" onClick={goBack}>
                                <ArrowIcon direction="left" /> {isId ? 'Halaman sebelumnya' : 'Previous page'}
                            </button>
                        ))}
                    </div>

                    {!isMaintenance && (
                        <nav className="vt-error-links" aria-label={isId ? 'Tautan berguna' : 'Helpful links'}>
                            <p className="vt-error-links-label">{isId ? 'Mungkin Anda mencari' : 'You might be looking for'}</p>
                            <ul>
                                {usefulLinks.map((link) => (
                                    <li key={link.href}>
                                        <a href={link.href}>
                                            <span>
                                                <strong>{link.label}</strong>
                                                <small>{link.hint}</small>
                                            </span>
                                            <ArrowIcon />
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    )}
                </section>

                <div className="vt-error-visual" aria-hidden="true">
                    <span className="vt-error-numeral">{status}</span>
                    <div className="vt-error-panel">
                        <div className="vt-error-panel-head">
                            <span className="vt-error-panel-icon"><StatusIcon status={status} /></span>
                            <span className="vt-error-panel-title">
                                <strong>{status} {content.reason}</strong>
                                <small>{isId ? 'Status halaman' : 'Page status'}</small>
                            </span>
                        </div>
                        {isMaintenance ? (
                            <div className="vt-error-progress">
                                <div className="vt-error-progress-track"><span /></div>
                                <small>{isId ? 'Pembaruan sedang berlangsung…' : 'Upgrade in progress…'}</small>
                            </div>
                        ) : (
                            <ol className="vt-error-skeleton">
                                <li><b>#1</b><span /><i /></li>
                                <li><b>#2</b><span /><i /></li>
                                <li><b>#?</b><span className="missing" /><i /></li>
                            </ol>
                        )}
                    </div>
                </div>
            </main>

            <footer className="vt-error-footer">
                <span>© {new Date().getFullYear()} Votmee</span>
                <span>{isId ? 'Platform ranking komunitas' : 'Community ranking platform'}</span>
            </footer>
        </div>
    );
}
