import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import SearchMenu from '../../Components/SearchMenu';
import LanguageSelect from '../../Components/LanguageSelect';
import ProfileDropdown from '../../Components/ProfileDropdown';
import AuthModal from '../../Components/AuthModal';
import id from '../../i18n/id.json';
import en from '../../i18n/en.json';

export const dictionary = (locale) => locale === 'en' ? en : id;
export const path = (locale, suffix = '') => `/${locale}${suffix}`;
export const openLoginModal = () => window.dispatchEvent(new Event('votmee:open-login'));
export const categoryHashtag = (category) => {
    const label = category?.slug ?? category?.name;
    return label ? `#${String(label).toLowerCase().replace(/[-\s]+/g, '')}` : '';
};

export function useCommunityAccess() {
    const access = usePage().props.auth?.can;

    return {
        createRanking: access?.createRanking === true,
        viewWallet: access?.viewWallet === true,
        moderate: access?.moderate === true,
    };
}

export const dateLabel = (value, locale) =>
    new Intl.DateTimeFormat(locale === 'id' ? 'id-ID' : 'en-US', { dateStyle: 'medium' }).format(new Date(value));

export const numberLabel = (value, locale) =>
    new Intl.NumberFormat(locale === 'id' ? 'id-ID' : 'en-US').format(Number(value || 0));

export function NavIcon({ name, size = 18, className = '' }) {
    const paths = {
        home: <><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V10Z" /><path d="M9 21v-7h6v7" /></>,
        search: <><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" /></>,
        popular: <><path d="m3 17 6-6 4 4 8-8" /><path d="M15 7h6v6" /></>,
        latest: <><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>,
        wallet: <><rect x="3" y="5" width="18" height="15" rx="2" /><path d="M3 9h18M15 15h3" /></>,
        user: <><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></>,
        plus: <path d="M12 4v16M4 12h16" />,
        menu: <path d="M4 7h16M4 12h16M4 17h16" />,
        plusCircle: <><circle cx="12" cy="12" r="9" /><path d="M12 8v8M8 12h8" /></>,
        bookmark: <path d="M5 5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16l-7-3.5L5 21V5Z" />,
        comment: <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.4-4 8-9 8-1.5 0-3-.3-4.3-.9L3 20l1.4-3.7A7.2 7.2 0 0 1 3 12c0-4.4 4-8 9-8s9 3.6 9 8Z" />,
        chevronUp: <path d="m5 15 7-7 7 7" />,
        chevronDown: <path d="m5 9 7 7 7-7" />,
        chevronRight: <path d="m9 18 6-6-6-6" />,
        globe: <><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c3 2.5 4.5 5.5 4.5 9S15 18.5 12 21M12 3C9 5.5 7.5 8.5 7.5 12S9 18.5 12 21" /></>,
        trophy: <><path d="M7 3h10v8a5 5 0 0 1-10 0V3ZM7 5H4v3a4 4 0 0 0 4 4M17 5h3v3a4 4 0 0 1-4 4M12 16v4m-4 1h8" /></>,
        trend: <path d="m3 17 6-6 4 4 7-8M14 7h6v6" />,
        shield: <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />,
        refresh: <><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" /><path d="M3 3v5h5" /><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" /><path d="M16 21h5v-5" /></>,
        flame: <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z" />,
        zap: <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />,
        check: <path d="M20 6 9 17l-5-5" />,
        users: <><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></>,
        sparkles: <path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z" />,
        calendar: <><rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></>,
        share: <><circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18" cy="19" r="3" /><line x1="8.59" y1="13.51" x2="15.42" y2="17.49" /><line x1="15.41" y1="6.51" x2="8.59" y2="10.49" /></>,
        edit: <><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" /></>,
        heart: <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />,
        award: <><circle cx="12" cy="8" r="7" /><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88" /></>,
        arrowRight: <path d="M5 12h14M12 5l7 7-7 7" />,
        arrowLeft: <path d="M19 12H5M12 19l-7-7 7-7" />,
        camera: <><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" /><circle cx="12" cy="13" r="4" /></>,
        settings: <><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" /></>,
        lock: <><rect x="3" y="11" width="18" height="11" rx="2" ry="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></>,
        trash: <><polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /></>,
        alertTriangle: <><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" /></>,
        mail: <><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" /></>,
    };
    return <svg className={className} width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{paths[name]}</svg>;
}

export function SocialIcon({ name, size = 26 }) {
    switch (name) {
        case 'whatsapp':
            return (
                <svg width={size} height={size} viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                        fill="#25D366"
                        d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.23 8.23 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24M8.53 7.33c-.16 0-.35.06-.53.25-.19.19-.71.7-.71 1.7 0 1 .73 1.97.83 2.11.1.13 1.44 2.2 3.49 3.08.49.21.87.34 1.17.43.49.16.94.13 1.29.08.4-.06 1.22-.5 1.39-.98.17-.48.17-.9.12-.98-.05-.09-.19-.14-.4-.25s-1.22-.6-1.41-.67c-.19-.07-.33-.1-.47.1-.14.21-.54.67-.66.81-.12.14-.24.16-.45.05-.21-.1-.88-.32-1.67-1.03-.62-.55-1.04-1.23-1.16-1.44-.12-.21-.01-.32.09-.43.09-.1.21-.24.32-.36.1-.12.14-.21.21-.35.07-.14.03-.26-.02-.36-.05-.1-.47-1.13-.64-1.55-.17-.41-.34-.35-.47-.36z"
                    />
                </svg>
            );
        case 'x':
        case 'twitter':
            return (
                <svg width={size} height={size} viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                        fill="#0f172a"
                        d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"
                    />
                </svg>
            );
        case 'telegram':
            return (
                <svg width={size} height={size} viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                        fill="#24A1DE"
                        d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"
                    />
                </svg>
            );
        case 'facebook':
            return (
                <svg width={size} height={size} viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                        fill="#1877F2"
                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                    />
                </svg>
            );
        default:
            return null;
    }
}

const categoryOrder = ['teknologi', 'wisata', 'kuliner', 'otomotif', 'hiburan', 'pendidikan', 'gadget', 'finansial'];

function categoryPosition(category) {
    const position = categoryOrder.indexOf(category.slug);
    return position < 0 ? categoryOrder.length : position;
}

function relativeDate(value, locale) {
    const publishedAt = new Date(value);
    if (Number.isNaN(publishedAt.getTime())) return '';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    publishedAt.setHours(0, 0, 0, 0);
    const days = Math.max(0, Math.round((today.getTime() - publishedAt.getTime()) / 86400000));
    if (days === 0) return locale === 'id' ? 'hari ini' : 'today';
    return new Intl.RelativeTimeFormat(locale === 'id' ? 'id-ID' : 'en-US', { numeric: 'auto' }).format(-days, 'day');
}

function commentTimeAgo(value, locale) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const diffMs = Date.now() - date.getTime();
    const diffSec = Math.max(0, Math.floor(diffMs / 1000));
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    const isId = locale === 'id';
    if (diffMin < 1) return isId ? 'baru saja' : 'just now';
    if (diffMin < 60) return `${diffMin}${isId ? 'm lalu' : 'm ago'}`;
    if (diffHour < 24) return `${diffHour}${isId ? 'j lalu' : 'h ago'}`;
    if (diffDay < 7) return `${diffDay}${isId ? 'h lalu' : 'd ago'}`;
    return relativeDate(value, locale);
}

export async function api(url, method = 'GET', data) {
    const xsrfCookie = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/)?.[1];
    const csrfHeaders = xsrfCookie
        ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) }
        : { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' };
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...csrfHeaders,
        },
        body: data ? JSON.stringify(data) : undefined,
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        const firstError = Object.values(result.errors ?? {}).flat()[0];
        throw new Error(String(firstError ?? result.message ?? 'Request failed'));
    }
    return result;
}

export function PageHead({ title, locale, description }) {
    const pathname = window.location.pathname.replace(/^\/(id|en)(?=\/|$)/, '') || '';
    const canonical = `${window.location.origin}/${locale}${pathname}`;
    return <Head title={title}>
        {description && <meta name="description" content={description} />}
        <link rel="canonical" href={canonical} />
        <link rel="alternate" hrefLang="id" href={`${window.location.origin}/id${pathname}`} />
        <link rel="alternate" hrefLang="en" href={`${window.location.origin}/en${pathname}`} />
    </Head>;
}

export function Layout({ locale, categories = [], leaders = [], children, active = '', searchPosts = [], loginModal = false, registerModal = false, recentComments: propRecentComments }) {
    const t = dictionary(locale);
    const showRankingPanel = active === 'home' || active === 'explore';
    const useWideContent = ['create', 'wallet', 'profile', 'object', 'ranking', 'settings'].includes(active);
    const useFeedFrame = showRankingPanel || useWideContent;
    const user = usePage().props.auth?.user;
    const access = useCommunityAccess();
    const [query, setQuery] = useState('');
    const [menuOpen, setMenuOpen] = useState(false);
    const [searchOpen, setSearchOpen] = useState(false);
    const [authMode, setAuthMode] = useState(registerModal ? 'register' : loginModal ? 'login' : null);
    const { loginStatus, googleAuthError, recentComments: pageRecentComments = [] } = usePage().props;
    const recentComments = propRecentComments || pageRecentComments || [];
    const orderedCategories = [...categories].sort((first, second) => categoryPosition(first) - categoryPosition(second));
    const searchCategories = orderedCategories.map(category => [category.name, ({ teknologi: 'chip', wisata: 'mountain', kuliner: 'utensils', otomotif: 'car', hiburan: 'film', pendidikan: 'graduation', finansial: 'wallet', kesehatan: 'heartPulse' })[category.slug] ?? 'more']);
    const searchLeaders = leaders.map(leader => [leader.name, numberLabel(leader.score, locale), '', true]);
    const searchRankings = searchPosts.map(post => ({
        id: post.id,
        title: post.title,
        description: post.criteria,
        category: post.category?.name ?? categories.find(category => category.id === post.category_id)?.name ?? '',
        author: post.creator?.username ? `@${post.creator.username}` : post.creator?.name ?? 'Votmee',
        votes: post.supporters_count ?? 0,
        voteLabel: numberLabel(post.supporters_count ?? 0, locale),
    }));
    useEffect(() => { localStorage.setItem('votmee.locale', locale); }, [locale]);
    useEffect(() => {
        const open = () => setAuthMode('login');
        window.addEventListener('votmee:open-login', open);
        return () => window.removeEventListener('votmee:open-login', open);
    }, []);
    const closeAuth = () => {
        setAuthMode(null);
        if (loginModal || registerModal) {
            router.get(path(locale), {}, { replace: true, preserveState: true, preserveScroll: true });
        }
    };
    const switchLocale = (next) => window.location.pathname.startsWith('/admin')
        ? `/admin?locale=${next}`
        : window.location.pathname === '/'
            ? `/${next}`
            : `${window.location.pathname.replace(/^\/(id|en)(?=\/|$)/, `/${next}`)}${window.location.search}`;
    const submitSearch = (value) => router.get(path(locale, '/explore'), value.trim() ? { q: value.trim() } : {});
    const selectSearchCategory = (name) => {
        const category = categories.find(item => item.name === name);
        if (!category) return false;
        router.get(path(locale, '/explore'), { category_id: category.id });
        return true;
    };
    const searchMenuProps = {
        searchInput: query,
        setSearchInput: setQuery,
        onSearchSubmit: submitSearch,
        onSelectCategory: selectSearchCategory,
        posts: searchRankings,
        leaders: searchLeaders,
        categories: searchCategories,
        language: locale,
    };

    return <div className="vt-app vm-page">
        <a className="vm-skip" href="#main-content">{locale === 'id' ? 'Langsung ke konten' : 'Skip to content'}</a>
        <header className="vt-header vm-header">
            <div className={`vt-header-inner vm-shell vm-header-inner vm-header-2col${useFeedFrame ? ' vm-header-3col' : ''}`}>
                <Link href={path(locale)} className="vt-wordmark vm-brand" aria-label="Votmee home">votmee</Link>
                <SearchMenu {...searchMenuProps} />
                <div className="vt-header-actions vm-header-right">
                    <LanguageSelect language={locale} onChange={(next) => router.visit(switchLocale(next))} />
                    {user ? <ProfileDropdown user={user} locale={locale} canModerate={access.moderate} />
                        : <><button type="button" className="vt-login" onClick={() => setAuthMode('login')}>{t.login}</button><button type="button" className="vt-register" onClick={() => setAuthMode('register')}>{locale === 'id' ? 'Daftar' : 'Sign up'}</button></>}
                </div>
                <div className="vm-mobile-header-actions">
                    <button type="button" aria-label={t.searchAction} aria-expanded={searchOpen} onClick={() => setSearchOpen(!searchOpen)}><NavIcon name="search" size={21} /></button>
                    <button type="button" aria-label={t.menu} aria-expanded={menuOpen} onClick={() => setMenuOpen(!menuOpen)}><NavIcon name="menu" size={23} /></button>
                </div>
            </div>
            {searchOpen && <div className="vm-mobile-search-wrapper"><SearchMenu {...searchMenuProps} isMobile autoFocus onClose={() => setSearchOpen(false)} /></div>}
        </header>
        <div className={`vt-shell vm-shell vm-layout vm-layout-2col${useFeedFrame ? ' vm-layout-3col' : ''}${useWideContent ? ' vm-layout-wide-content' : ''}`}>
            <aside className={`vt-left vm-sidebar ${menuOpen ? 'vt-left-open' : ''}`} aria-label={t.menu}>
                <nav className="vt-main-nav vm-side-nav">
                    <Link className={active === 'home' ? 'active' : ''} href={path(locale)}><NavIcon name="home" />{t.home}</Link>
                    <Link className={active === 'explore' ? 'active' : ''} href={path(locale, '/explore')}><NavIcon name="search" />{t.explore}</Link>
                    {access.createRanking && <Link href={path(locale, '/create')}><NavIcon name="plusCircle" />{t.create}</Link>}
                    {access.viewWallet && <Link href={path(locale, '/wallet')}><NavIcon name="wallet" />{t.wallet}</Link>}
                    {user && <Link href={path(locale, `/profile/${user.username}`)}><NavIcon name="user" />{t.profile}</Link>}
                </nav>
                <div className="vt-nav-section vm-side-categories">
                    <p className="vt-rail-label">{locale === 'id' ? 'KATEGORI POPULER' : 'POPULAR CATEGORIES'}</p>
                    {orderedCategories.map(category => <Link key={category.id} href={path(locale, `/explore?category_id=${category.id}`)}>{categoryHashtag(category)}</Link>)}
                </div>
                <div className="vt-mobile-account-links">
                    {!user && <><button type="button" onClick={() => { setMenuOpen(false); setAuthMode('login'); }}>{t.login}</button><button type="button" onClick={() => { setMenuOpen(false); setAuthMode('register'); }}>{locale === 'id' ? 'Daftar' : 'Sign up'}</button></>}
                    <LanguageSelect language={locale} onChange={(next) => router.visit(switchLocale(next))} mobile />
                </div>
            </aside>
            <main className="vt-main vm-main" id="main-content">{children}</main>
            {showRankingPanel && <aside className="vm-rightbar vm-ranking-sidebar" aria-label={locale === 'id' ? 'Panel ranking' : 'Ranking panel'}>
                <Leaderboard locale={locale} initialLeaders={leaders} />
                <QuickDuelWidget locale={locale} leaders={leaders} />
                <DiscussionPanel locale={locale} posts={searchPosts} categories={categories} recentComments={recentComments} />
            </aside>}
        </div>
        <nav className={`vt-bottom-nav vm-bottom-nav ${user ? '' : 'vm-bottom-nav-guest'}`} aria-label={t.menu}>
            <Link className={active === 'home' ? 'active' : ''} href={path(locale)}><NavIcon name="home" size={21} /><span>{t.home}</span></Link>
            <Link className={active === 'explore' ? 'active' : ''} href={path(locale, '/explore')}><NavIcon name="search" size={21} /><span>{t.explore}</span></Link>
            {access.createRanking && <Link className="vm-bottom-create" href={path(locale, '/create')} aria-label={t.create}><NavIcon name="plus" size={26} /></Link>}
            {access.viewWallet && <Link href={path(locale, '/wallet')}><NavIcon name="wallet" size={21} /><span>{t.wallet}</span></Link>}
            {user ? <Link href={path(locale, `/profile/${user.username}`)}><NavIcon name="user" size={21} /><span>{t.profile}</span></Link> : <button type="button" onClick={() => setAuthMode('login')}><NavIcon name="user" size={21} /><span>{t.login}</span></button>}
        </nav>
        {!user && <AuthModal mode={authMode} onClose={closeAuth} onModeChange={setAuthMode} locale={locale} status={loginStatus} googleAuthError={googleAuthError} />}
    </div>;
}

export function Leaderboard({ locale, initialLeaders = [] }) {
    const t = dictionary(locale);
    const [board, setBoard] = useState('objects');
    const [period, setPeriod] = useState('weekly');
    const [leaders, setLeaders] = useState(initialLeaders);
    const [loading, setLoading] = useState(false);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const requestId = useRef(0);

    const changeBoard = (targetBoard) => {
        if (targetBoard === board) return;
        setLoading(true);
        setBoard(targetBoard);
    };

    const changePeriod = (targetPeriod) => {
        if (targetPeriod === period) return;
        setLoading(true);
        setPeriod(targetPeriod);
    };

    const refreshData = (targetPeriod = period, targetBoard = board) => {
        const currentRequestId = ++requestId.current;
        setLoading(true);
        setIsRefreshing(true);
        api(`/backend/leaderboard?board=${targetBoard}&period=${targetPeriod}&rank_type=support`)
            .then(result => {
                if (requestId.current === currentRequestId) setLeaders(result.leaders || []);
            })
            .catch(() => {
                if (requestId.current === currentRequestId) setLeaders([]);
            })
            .finally(() => {
                if (requestId.current === currentRequestId) {
                    setLoading(false);
                    setTimeout(() => setIsRefreshing(false), 500);
                }
            });
    };

    useEffect(() => {
        if (board === 'objects' && period === 'weekly') {
            requestId.current += 1;
            setLeaders(initialLeaders);
            setLoading(false);
            return;
        }
        refreshData(period, board);
        return () => { requestId.current += 1; };
    }, [board, period, initialLeaders]);

    const maxScore = useMemo(() => {
        return Math.max(...leaders.map(l => Number(l.score || 0)), 1);
    }, [leaders]);

    const displayedLeaders = leaders.slice(0, 5);

    return (
        <section className="vt-leaderboard vm-panel" aria-labelledby="vm-rankings-title">
            <div className="vm-panel-header-interactive">
                <h2 id="vm-rankings-title">
                    <NavIcon name="trophy" size={17} />
                    {board === 'objects'
                        ? (locale === 'id' ? 'Peringkat Objek' : 'Object Rankings')
                        : (locale === 'id' ? 'Top Kontributor' : 'Top Contributors')}
                </h2>
                <button
                    type="button"
                    className={`vm-panel-refresh-btn ${isRefreshing ? 'spinning' : ''}`}
                    aria-label={locale === 'id' ? 'Muat ulang ranking' : 'Refresh rankings'}
                    title={locale === 'id' ? 'Perbarui data ranking' : 'Refresh ranking data'}
                    onClick={() => refreshData(period)}
                >
                    <NavIcon name="refresh" size={14} />
                </button>
            </div>

            <div className="vm-leader-boards" role="group" aria-label={locale === 'id' ? 'Jenis peringkat' : 'Ranking type'}>
                <button type="button" className={board === 'objects' ? 'active' : ''} aria-pressed={board === 'objects'} onClick={() => changeBoard('objects')}>
                    {locale === 'id' ? 'Objek' : 'Objects'}
                </button>
                <button type="button" className={board === 'creators' ? 'active' : ''} aria-pressed={board === 'creators'} onClick={() => changeBoard('creators')}>
                    {locale === 'id' ? 'Kontributor' : 'Creators'}
                </button>
            </div>

            <div className="vt-periods vm-periods" role="group" aria-label={locale === 'id' ? 'Periode ranking' : 'Ranking period'}>
                {['daily', 'weekly', 'all_time'].map(value => (
                    <button
                        key={value}
                        type="button"
                        aria-pressed={period === value}
                        className={period === value ? 'active' : ''}
                        onClick={() => changePeriod(value)}
                    >
                        {value === 'all_time' ? t.allTime : t[value]}
                    </button>
                ))}
            </div>

            {loading ? (
                <div className="vm-leader-skeleton" role="status" aria-label={locale === 'id' ? 'Memuat data ranking...' : 'Loading rankings...'}>
                    {[1, 2, 3, 4, 5].map(n => (
                        <div key={n} className="vm-skeleton-row">
                            <div className="vm-skeleton-badge" />
                            <div className="vm-skeleton-avatar" />
                            <div className="vm-skeleton-info">
                                <div className="vm-skeleton-line" />
                                <div className="vm-skeleton-line short" />
                            </div>
                        </div>
                    ))}
                </div>
            ) : displayedLeaders.length ? (
                <ol className="vt-leader-list vm-leader-list">
                    {displayedLeaders.map((leader, index) => {
                        const rankNumber = index + 1;
                        const rankClass = rankNumber === 1
                            ? 'vm-leader-rank-badge vm-leader-rank-1'
                            : rankNumber === 2
                                ? 'vm-leader-rank-badge vm-leader-rank-2'
                                : rankNumber === 3
                                    ? 'vm-leader-rank-badge vm-leader-rank-3'
                                    : 'vm-leader-rank-badge vm-leader-rank-other';
                        const scoreRatio = Math.max(16, Math.min(100, Math.round(((leader.score || 0) / maxScore) * 100)));

                        return (
                            <li key={board === 'objects' ? leader.object_id : leader.user_id} className="vm-leader-interactive-item">
                                <span className={rankClass} aria-label={`Rank ${rankNumber}`}>
                                    {rankNumber}
                                </span>
                                <span className="vm-rank-avatar" aria-hidden="true">
                                    {leader.name.slice(0, 1).toUpperCase()}
                                </span>
                                <span className="vm-leader-name">
                                    <strong>
                                        <Link href={path(locale, board === 'objects' ? `/object/${leader.slug}` : `/profile/${leader.username}`)}>
                                            {leader.name}
                                        </Link>
                                    </strong>
                                    {board === 'objects' ? <small>{numberLabel(leader.score, locale)} {t.points}</small> : <>
                                        <small>{locale === 'id' ? 'Skor' : 'Score'} {numberLabel(leader.score, locale)}/100</small>
                                        <small className="vm-creator-metrics">
                                            {numberLabel(leader.supporters, locale)} {locale === 'id' ? 'pendukung' : 'supporters'} · {numberLabel(leader.support_points, locale)} {t.points}
                                        </small>
                                    </>}
                                    <div className="vm-leader-progress-wrap" aria-hidden="true">
                                        <div className="vm-leader-progress-bar" style={{ width: `${scoreRatio}%` }} />
                                    </div>
                                </span>
                                {board === 'objects' && rankNumber === 1 ? (
                                    <span className="vm-trend-pill vm-trend-hot" title="Peringkat teratas saat ini">
                                        <NavIcon name="flame" size={11} /> Hot
                                    </span>
                                ) : board === 'objects' && rankNumber <= 3 ? (
                                    <span className="vm-trend-pill vm-trend-up">
                                        ▲ Naik
                                    </span>
                                ) : null}
                            </li>
                        );
                    })}
                </ol>
            ) : (
                <p className="vt-muted vt-side-empty">{t.noResults}</p>
            )}

            <Link className="vm-panel-link" href={path(locale, '/explore')}>
                {locale === 'id' ? 'Jelajahi ranking' : 'Explore rankings'} →
            </Link>
        </section>
    );
}

function QuickDuelWidget({ locale, leaders = [] }) {
    const isId = locale === 'id';
    const topTwo = leaders.length >= 2 ? leaders.slice(0, 2) : [
        { object_id: 'default-1', name: 'iPhone 16 Pro Max', slug: 'iphone-16-pro-max', score: 1420 },
        { object_id: 'default-2', name: 'Samsung Galaxy S25', slug: 'samsung-galaxy-s25', score: 1280 }
    ];
    const [voteChoice, setVoteChoice] = useState(null);
    const [votesA, setVotesA] = useState(58);
    const [votesB, setVotesB] = useState(42);

    useEffect(() => {
        try {
            const savedChoice = localStorage.getItem('votmee.sidebarDuelVote');
            if (savedChoice === 'a' || savedChoice === 'b') {
                setVoteChoice(savedChoice);
            }
        } catch {}
    }, []);

    const castVote = (choice) => {
        setVoteChoice(choice);
        try {
            localStorage.setItem('votmee.sidebarDuelVote', choice);
        } catch {}
        if (choice === 'a') {
            setVotesA(64);
            setVotesB(36);
        } else {
            setVotesB(64);
            setVotesA(36);
        }
    };

    const resetVote = (e) => {
        e.stopPropagation();
        setVoteChoice(null);
        try {
            localStorage.removeItem('votmee.sidebarDuelVote');
        } catch {}
    };

    return (
        <section className="vm-panel vm-duel-panel" aria-labelledby="vm-duel-title">
            <div className="vm-duel-header">
                <span className="vm-duel-badge-label">
                    <NavIcon name="zap" size={12} /> {isId ? 'VOTE CEPAT' : 'QUICK VOTE'}
                </span>
                <h3 id="vm-duel-title" className="vm-duel-title">
                    {isId ? 'Duel Pilihan Komunitas' : 'Community Faceoff'}
                </h3>
                <p className="vm-duel-subtitle">
                    {isId ? 'Mana jagoan favoritmu minggu ini?' : 'Which contender gets your support?'}
                </p>
            </div>
            <div className="vm-duel-arena">
                <div className={`vm-duel-card ${voteChoice === 'a' ? 'voted' : ''}`}>
                    <Link
                        href={path(locale, `/object/${topTwo[0].slug}`)}
                        className="vm-duel-avatar-link"
                        title={`${isId ? 'Lihat detail' : 'View details'} ${topTwo[0].name}`}
                    >
                        <span className="vm-duel-avatar" aria-hidden="true">
                            {topTwo[0].name.slice(0, 1).toUpperCase()}
                        </span>
                    </Link>
                    <strong className="vm-duel-name">
                        <Link
                            href={path(locale, `/object/${topTwo[0].slug}`)}
                            title={`${isId ? 'Lihat detail' : 'View details'} ${topTwo[0].name}`}
                        >
                            {topTwo[0].name}
                        </Link>
                    </strong>
                    <button
                        type="button"
                        className="vm-duel-btn"
                        onClick={() => castVote('a')}
                    >
                        {voteChoice === 'a' ? (
                            <><NavIcon name="check" size={12} /> {isId ? 'Didukung' : 'Supported'}</>
                        ) : (
                            <>{isId ? 'Dukung' : 'Support'}</>
                        )}
                    </button>
                </div>
                <div className="vm-duel-vs" aria-hidden="true">VS</div>
                <div className={`vm-duel-card ${voteChoice === 'b' ? 'voted' : ''}`}>
                    <Link
                        href={path(locale, `/object/${topTwo[1].slug}`)}
                        className="vm-duel-avatar-link"
                        title={`${isId ? 'Lihat detail' : 'View details'} ${topTwo[1].name}`}
                    >
                        <span className="vm-duel-avatar" aria-hidden="true" style={{ background: '#fff7ed', color: '#c2410c' }}>
                            {topTwo[1].name.slice(0, 1).toUpperCase()}
                        </span>
                    </Link>
                    <strong className="vm-duel-name">
                        <Link
                            href={path(locale, `/object/${topTwo[1].slug}`)}
                            title={`${isId ? 'Lihat detail' : 'View details'} ${topTwo[1].name}`}
                        >
                            {topTwo[1].name}
                        </Link>
                    </strong>
                    <button
                        type="button"
                        className="vm-duel-btn"
                        onClick={() => castVote('b')}
                    >
                        {voteChoice === 'b' ? (
                            <><NavIcon name="check" size={12} /> {isId ? 'Didukung' : 'Supported'}</>
                        ) : (
                            <>{isId ? 'Dukung' : 'Support'}</>
                        )}
                    </button>
                </div>
            </div>
            {voteChoice ? (
                <div className="vm-duel-result-wrap">
                    <div className="vm-duel-bar-track">
                        <div className="vm-duel-bar-a" style={{ width: `${votesA}%` }} />
                        <div className="vm-duel-bar-b" style={{ width: `${votesB}%` }} />
                    </div>
                    <div className="vm-duel-stats">
                        <span style={{ color: '#1044bb' }}>{votesA}% suara</span>
                        <button type="button" onClick={resetVote} style={{ border: 0, background: 'transparent', color: '#64748b', fontSize: '10px', cursor: 'pointer', textDecoration: 'none' }}>
                            {isId ? 'Ganti pilihan' : 'Change vote'}
                        </button>
                        <span style={{ color: '#d97706' }}>{votesB}% suara</span>
                    </div>
                </div>
            ) : (
                <p style={{ margin: '4px 0 0', textAlign: 'center', fontSize: '10.5px', color: '#94a3b8' }}>
                    {isId ? 'Klik tombol dukung untuk melihat hasil langsung!' : 'Click support to see live results!'}
                </p>
            )}
        </section>
    );
}

function DiscussionPanel({ locale, posts = [], categories = [], recentComments = [] }) {
    const isId = locale === 'id';
    const [activeTab, setActiveTab] = useState('comments');

    const discussions = [...posts]
        .sort((first, second) => (second.comments_count ?? 0) - (first.comments_count ?? 0))
        .slice(0, 4);

    const fallbackComments = [
        {
            id: 'c1',
            user: { name: 'Galih Pratama', username: 'galihpratama' },
            body: isId
                ? 'Baterai dan suhunya stabil banget buat gaming seharian, worth it!'
                : 'Battery and temps are super stable for all-day gaming, worth it!',
            created_at: new Date(Date.now() - 1000 * 60 * 35).toISOString(),
            ranking_post: {
                title: isId ? 'Smartphone Flagship Terbaik 2026' : 'Best Gaming Smartphones 2026',
                slug: posts[0]?.slug ?? 'smartphone-flagship-terbaik-2026',
            },
        },
        {
            id: 'c2',
            user: { name: 'Safira Andini', username: 'safirandini' },
            body: isId
                ? 'Pemandangan sunrise di Bromo beneran magis, wajib masuk bucket list!'
                : 'The sunrise at Bromo is truly magical, a must on your bucket list!',
            created_at: new Date(Date.now() - 1000 * 60 * 60 * 3).toISOString(),
            ranking_post: {
                title: isId ? 'Destinasi Wisata Alam Terbaik' : 'Best Nature Spots Indonesia',
                slug: posts[1]?.slug ?? 'destinasi-wisata-alam-terbaik',
            },
        },
        {
            id: 'c3',
            user: { name: 'Raditya Kurniawan', username: 'radityakrn' },
            body: isId
                ? 'Kuah gulai di Blok M emang paling juara kalau malam-malam.'
                : 'The curry broth in Blok M is unmatched late at night.',
            created_at: new Date(Date.now() - 1000 * 60 * 60 * 7).toISOString(),
            ranking_post: {
                title: isId ? 'Kuliner Malam Terbaik Jakarta' : 'Best Late-Night Food Jakarta',
                slug: posts[2]?.slug ?? 'kuliner-malam-terbaik-jakarta',
            },
        },
    ];

    const displayComments = (recentComments && recentComments.length > 0)
        ? recentComments.slice(0, 3)
        : fallbackComments;

    return (
        <section className="vm-panel vm-comm-panel" aria-labelledby="vm-discussions-title">
            <div className="vm-comm-header">
                <h2 id="vm-discussions-title">
                    <NavIcon name="users" size={16} />
                    {isId ? 'Komunitas Votmee' : 'Votmee Community'}
                </h2>
            </div>

            <div className="vm-comm-tabs">
                <button
                    type="button"
                    className={activeTab === 'comments' ? 'active' : ''}
                    onClick={() => setActiveTab('comments')}
                >
                    <NavIcon name="comment" size={13} />
                    {isId ? 'Komentar Terbaru' : 'Recent Comments'}
                </button>
                <button
                    type="button"
                    className={activeTab === 'discussions' ? 'active' : ''}
                    onClick={() => setActiveTab('discussions')}
                >
                    <NavIcon name="flame" size={13} />
                    {isId ? 'Rangking' : 'Rangking'}
                </button>
            </div>

            {activeTab === 'comments' ? (
                <ul className="vm-comm-list vm-comm-comments-list">
                    {displayComments.map((comment) => {
                        const authorName = comment.user?.name ?? 'User';
                        const authorUsername = comment.user?.username ?? (comment.user?.name ? comment.user.name.toLowerCase().replace(/\s+/g, '') : 'user');
                        const initial = (authorName || authorUsername).slice(0, 1).toUpperCase();
                        const postTitle = comment.ranking_post?.title ?? comment.rankingPost?.title ?? '';
                        const postSlug = comment.ranking_post?.slug ?? comment.rankingPost?.slug ?? '';
                        const linkUrl = postSlug ? path(locale, `/ranking/${postSlug}`) : '#';

                        return (
                            <li key={comment.id} className="vm-comm-comment-item">
                                <Link href={linkUrl} className="vm-comm-comment-link">
                                    <div className="vm-comm-comment-top">
                                        <span className="vm-comm-comment-avatar" aria-hidden="true">
                                            {initial}
                                        </span>
                                        <div className="vm-comm-comment-user">
                                            <strong className="vm-comm-comment-name">{authorName}</strong>
                                            <small className="vm-comm-comment-username">@{authorUsername}</small>
                                        </div>
                                        <span className="vm-comm-comment-time">
                                            {commentTimeAgo(comment.created_at, locale)}
                                        </span>
                                    </div>
                                    <p className="vm-comm-comment-text">"{comment.body}"</p>
                                    {postTitle && (
                                        <div className="vm-comm-comment-target">
                                            <span className="vm-comm-comment-target-badge" title={postTitle}>
                                                ↳ {postTitle}
                                            </span>
                                        </div>
                                    )}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            ) : (
                discussions.length ? (
                    <div className="vm-comm-list">
                        {discussions.map((post) => {
                            const category = categories.find((item) => item.id === post.category_id);
                            return (
                                <Link
                                    key={post.id}
                                    href={path(locale, `/ranking/${post.slug}`)}
                                    className="vm-comm-disc-item"
                                >
                                    <span className="vm-comm-disc-avatar" aria-hidden="true">
                                        {post.title.slice(0, 1).toUpperCase()}
                                    </span>
                                    <span className="vm-comm-info">
                                        <strong className="vm-comm-name">{post.title}</strong>
                                        <small className="vm-comm-meta">{category?.name ?? 'Ranking'}</small>
                                    </span>
                                    <span className="vm-comm-comment-count">
                                        <NavIcon name="comment" size={11} />
                                        {numberLabel(post.comments_count ?? 0, locale)}
                                    </span>
                                </Link>
                            );
                        })}
                    </div>
                ) : (
                    <p className="vt-muted vt-side-empty">
                        {isId ? 'Belum ada ranking pada halaman ini.' : 'No rankings on this page yet.'}
                    </p>
                )
            )}

            <div className="vm-comm-footer">
                <Link href={path(locale, '/explore')}>
                    {isId ? 'Lihat semua aktivitas' : 'View all activity'} →
                </Link>
            </div>
        </section>
    );
}

export function RankingCard({ post, locale, category }) {
    const t = dictionary(locale);
    const categorySlug = post.category?.slug ?? category?.slug ?? '';
    const imageNumber = categorySlug === 'wisata' ? 2 : categorySlug === 'kuliner' ? 3 : categorySlug === 'otomotif' ? 4 : categorySlug === 'hiburan' ? 5 : 1;
    const rankingUrl = path(locale, `/ranking/${post.slug}`);
    const author = post.creator?.username ? `@${post.creator.username}` : post.creator?.name ?? 'Votmee';
    const authorUsername = post.creator?.username ?? (typeof post.author === 'string' && post.author.startsWith('@') ? post.author.slice(1) : null);
    const profileUrl = authorUsername ? path(locale, `/profile/${authorUsername}`) : null;
    const [saved, setSaved] = useState(false);

    useEffect(() => {
        try {
            const stored = JSON.parse(localStorage.getItem('votmee.savedRankings') || '[]');
            setSaved(Array.isArray(stored) && stored.includes(post.id));
        } catch {
            setSaved(false);
        }
    }, [post.id]);

    const toggleSaved = () => {
        let savedRankings = [];
        try {
            const stored = JSON.parse(localStorage.getItem('votmee.savedRankings') || '[]');
            savedRankings = Array.isArray(stored) ? stored : [];
        } catch {
            savedRankings = [];
        }
        const updated = savedRankings.includes(post.id) ? savedRankings.filter(id => id !== post.id) : [...savedRankings, post.id];
        localStorage.setItem('votmee.savedRankings', JSON.stringify(updated));
        setSaved(updated.includes(post.id));
    };

    const avatarElement = profileUrl ? (
        <Link href={profileUrl} className="vm-author-avatar-link" aria-hidden="true" tabIndex={-1}>
            <span className="vm-avatar">{author.replace('@', '').slice(0, 1).toUpperCase()}</span>
        </Link>
    ) : (
        <span className="vm-avatar">{author.replace('@', '').slice(0, 1).toUpperCase()}</span>
    );

    const authorLink = profileUrl ? (
        <Link href={profileUrl} className="vm-author-link">
            {author}
        </Link>
    ) : (
        author
    );

    return <article className="vm-feed-card vt-legacy-card">
        <Link className="vm-desktop-vote vt-legacy-vote" href={rankingUrl} aria-label={`${t.viewRanking}: ${post.title}`}>
            <NavIcon name="chevronUp" size={18} />
            <strong>{numberLabel(post.supporters_count ?? 0, locale)}</strong>
            <NavIcon name="chevronDown" size={18} />
        </Link>
        <Link className="vm-feed-image" href={rankingUrl} aria-label={`${t.viewRanking}: ${post.title}`}>
            <picture><source media="(max-width: 767px)" srcSet={`/images/stitch/feed-mobile-${imageNumber}.jpg`} /><img src={`/images/stitch/feed-desktop-${imageNumber}.jpg`} alt="" loading="lazy" /></picture>
        </Link>
        <div className="vm-feed-content">
            <div className="vm-feed-title-row">
                <span className={`vm-category-tag vm-tag-${categorySlug}`}>{categoryHashtag(post.category ?? category) || t.category}</span>
                <button className={`vm-save ${saved ? 'selected' : ''}`} type="button" onClick={toggleSaved} aria-label={`${saved ? (locale === 'id' ? 'Hapus simpanan' : 'Remove saved') : (locale === 'id' ? 'Simpan' : 'Save')} ${post.title}`} aria-pressed={saved}><NavIcon name="bookmark" size={17} /></button>
            </div>
            <h2><Link href={rankingUrl}>{post.title}</Link></h2>
            <p className="vm-feed-description">{post.criteria}</p>
            <div className="vm-feed-meta">{avatarElement}<span>{authorLink} <span className="vm-dot">·</span> {relativeDate(post.published_at, locale)}</span><span className="vm-desktop-comments"><span className="vm-dot">·</span><NavIcon name="comment" size={15} /> {numberLabel(post.comments_count ?? 0, locale)} {t.comments}</span></div>
        </div>
        <div className="vm-mobile-footer">
            <span className="vm-mobile-author">{avatarElement}<span>{authorLink} · {relativeDate(post.published_at, locale)}</span></span>
            <div className="vm-mobile-actions"><Link href={rankingUrl}><NavIcon name="chevronUp" size={15} /> {numberLabel(post.supporters_count ?? 0, locale)} <NavIcon name="chevronDown" size={13} /></Link><Link href={rankingUrl} aria-label={`${numberLabel(post.comments_count ?? 0, locale)} ${t.comments}, ${post.title}`}><NavIcon name="comment" size={15} /> {numberLabel(post.comments_count ?? 0, locale)}</Link></div>
        </div>
    </article>;
}

export function PaginationLinks({ pagination, locale }) {
    const t = dictionary(locale);
    if (!pagination.prev_page_url && !pagination.next_page_url) return null;
    return <nav className="vt-pagination" aria-label="Pagination">
        {pagination.prev_page_url ? <Link href={pagination.prev_page_url} preserveScroll>← {t.back}</Link> : <span />}
        {pagination.next_page_url && <Link href={pagination.next_page_url} preserveScroll>{t.loadMore} →</Link>}
    </nav>;
}

export function EmptyState({ locale, message }) {
    const t = dictionary(locale);
    const access = useCommunityAccess();
    return <div className="vt-empty"><div className="vt-empty-mark">◎</div><h2>{message ?? t.noRankings}</h2><p>{t.noRankingsHelp}</p>{access.createRanking && <Link href={path(locale, '/create')}>{t.create} →</Link>}</div>;
}
