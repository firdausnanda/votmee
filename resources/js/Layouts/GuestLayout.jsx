import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import LanguageSelect from '@/Components/LanguageSelect';

export default function GuestLayout({ children, currentLocale = null, variant = '' }) {
    const [locale, setLocale] = useState(() => {
        if (currentLocale) return currentLocale;
        if (typeof window === 'undefined') return 'id';
        const pathLocale = window.location.pathname.startsWith('/en')
            ? 'en'
            : window.location.pathname.startsWith('/id')
                ? 'id'
                : null;
        if (pathLocale) return pathLocale;
        const queryLocale = new URLSearchParams(window.location.search).get('locale');
        if (queryLocale === 'en' || queryLocale === 'id') return queryLocale;
        try {
            return (window.localStorage.getItem('votmee-language') === 'en' || window.localStorage.getItem('votmee.locale') === 'en')
                ? 'en'
                : 'id';
        } catch {
            return 'id';
        }
    });

    useEffect(() => {
        try {
            window.localStorage.setItem('votmee-language', locale);
            window.localStorage.setItem('votmee.locale', locale);
        } catch {}
    }, [locale]);

    const handleLanguageChange = (newLang) => {
        setLocale(newLang);
        try {
            window.localStorage.setItem('votmee-language', newLang);
            window.localStorage.setItem('votmee.locale', newLang);
        } catch {}
        if (typeof window !== 'undefined') {
            const path = window.location.pathname;
            if (path.startsWith('/id') || path.startsWith('/en')) {
                router.visit(path.replace(/^\/(id|en)/, `/${newLang}`) + window.location.search);
            } else {
                router.visit(`/${newLang}${path.startsWith('/') ? path : `/${path}`}` + window.location.search);
            }
        }
    };

    const isId = locale === 'id';
    const isLogin = variant === 'login';
    const isMinimal = variant === 'minimal';

    return (
        <div className={`vm-auth-page vt-auth${isLogin ? ' vm-auth-login' : ''}${isMinimal ? ' vm-auth-minimal' : ''}`}>
            <header className="vm-auth-header">
                <div className="vm-auth-header-inner">
                    <Link
                        href={`/${locale}`}
                        className="vm-auth-brand-link"
                        aria-label={`Votmee ${isId ? 'Beranda' : 'Home'}`}
                    >
                        <span className="vm-auth-brand">
                            votmee{!isLogin && <span>.</span>}
                        </span>
                    </Link>
                    <div className="vm-auth-header-actions">
                        <LanguageSelect language={locale} onChange={handleLanguageChange} />
                        <Link
                            href={`/${locale}`}
                            className="vm-auth-back-link"
                            title={isId ? 'Kembali ke Beranda' : 'Back to Home'}
                        >
                            <svg
                                width="15"
                                height="15"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M19 12H5M12 19l-7-7 7-7" />
                            </svg>
                            <span>{isId ? 'Kembali ke Beranda' : 'Back to Home'}</span>
                        </Link>
                    </div>
                </div>
            </header>

            <main className="vm-auth-main">
                <div className="vm-auth-layout">
                    {/* Left Column: Welcome-Style Hero Showcase */}
                    {!isMinimal && <section
                        className="vm-auth-showcase vt-auth-intro"
                        aria-label={isId ? 'Tentang Votmee' : 'About Votmee'}
                    >
                        {!isLogin && <><div className="vm-floating-badge vm-floating-trophy" aria-hidden="true">
                            <svg
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M7 3h10v8a5 5 0 0 1-10 0V3ZM7 5H4v3a4 4 0 0 0 4 4M17 5h3v3a4 4 0 0 1-4 4M12 16v4m-4 1h8" />
                            </svg>
                        </div>
                        <div className="vm-floating-badge vm-floating-trend" aria-hidden="true">
                            <svg
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="m3 17 6-6 4 4 7-8M14 7h6v6" />
                            </svg>
                        </div></>}

                        <div>
                            {!isLogin && <span className="vm-showcase-badge">
                                ✦ {isId ? 'Platform Ranking Komunitas' : 'Community Ranking Platform'}
                            </span>}
                            <div className="vm-showcase-content">
                                {isLogin ? <h2>Where Favorites Rise.</h2> : <h1>
                                    {isId ? 'Suara Anda Menentukan Pilihan Terbaik' : 'Your Voice Shapes the Community’s Best'}
                                </h1>}
                                <p>
                                    {isLogin
                                        ? isId ? 'Bandingkan kandidat, dukung pilihanmu, dan lihat suara komunitas.' : 'Compare candidates, support your pick, and see what the community thinks.'
                                        : isId ? 'Bandingkan gadget, wisata, kuliner, dan topik lainnya. Berikan voting nyata dan temukan rekomendasi terpercaya.' : 'Compare tech, travel, food, and other topics. Cast verified votes and discover trusted recommendations.'}
                                </p>
                            </div>

                            {isLogin && <Link href={`/${locale}`} className="vm-auth-explore-link">
                                {isId ? 'Jelajahi ranking' : 'Explore rankings'} <span aria-hidden="true">→</span>
                            </Link>}

                            {!isLogin && <div className="vm-showcase-card">
                                <div className="vm-showcase-card-header">
                                    <span className="vm-showcase-category-tag">
                                        {isId ? 'Teknologi' : 'Technology'}
                                    </span>
                                    <span className="vm-showcase-live-pill">
                                        <span className="vm-pulse-dot"></span>
                                        {isId ? 'Sedang Tren' : 'Trending Now'}
                                    </span>
                                </div>
                                <div className="vm-showcase-item">
                                    <span className="vm-showcase-rank-badge">#1</span>
                                    <div className="vm-showcase-item-info">
                                        <strong>Samsung Galaxy A26</strong>
                                        <small>
                                            {isId ? '12.4K suara pendukung' : '12.4K community supporters'}
                                        </small>
                                    </div>
                                    <span className="vm-showcase-trend-pill">▲ +12%</span>
                                </div>
                            </div>}
                        </div>

                        {isLogin && <div className="vm-hero-illustration vm-auth-hero-illustration" aria-hidden="true">
                            <div className="vm-phone"><span className="vm-phone-speaker" /><strong>Ranking #1</strong><span className="vm-phone-line" /><span className="vm-phone-line short" /><small>▲ 98.4%</small></div>
                            <span className="vm-hero-trophy"><svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M7 3h10v8a5 5 0 0 1-10 0V3ZM7 5H4v3a4 4 0 0 0 4 4M17 5h3v3a4 4 0 0 1-4 4M12 16v4m-4 1h8" /></svg></span>
                            <span className="vm-hero-trend"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m3 17 6-6 4 4 7-8M14 7h6v6" /></svg></span>
                        </div>}

                        {!isLogin && <div className="vm-showcase-stats">
                            <div className="vm-showcase-stat-item">
                                <strong>50K+</strong>
                                <small>{isId ? 'Suara Masuk' : 'Votes Cast'}</small>
                            </div>
                            <div className="vm-showcase-stat-item">
                                <strong>100%</strong>
                                <small>{isId ? 'Pilihan Komunitas' : 'Community Driven'}</small>
                            </div>
                            <div className="vm-showcase-stat-item">
                                <strong>12K+</strong>
                                <small>{isId ? 'Member Aktif' : 'Active Members'}</small>
                            </div>
                        </div>}
                    </section>}

                    {/* Right Column: Auth Card */}
                    <div className="vm-auth-card vt-auth-card">{children}</div>
                </div>
            </main>
        </div>
    );
}
