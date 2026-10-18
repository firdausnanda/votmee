import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import SearchMenu from '../../Components/SearchMenu';
import LanguageSelect from '../../Components/LanguageSelect';
import ProfileDropdown from '../../Components/ProfileDropdown';
import id from '../../i18n/id.json';
import en from '../../i18n/en.json';

export const dictionary = (locale) => locale === 'en' ? en : id;
export const path = (locale, suffix = '') => `/${locale}${suffix}`;
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

function NavIcon({ name, size = 18 }) {
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
        globe: <><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c3 2.5 4.5 5.5 4.5 9S15 18.5 12 21M12 3C9 5.5 7.5 8.5 7.5 12S9 18.5 12 21" /></>,
        trophy: <><path d="M7 3h10v8a5 5 0 0 1-10 0V3ZM7 5H4v3a4 4 0 0 0 4 4M17 5h3v3a4 4 0 0 1-4 4M12 16v4m-4 1h8" /></>,
        trend: <path d="m3 17 6-6 4 4 7-8M14 7h6v6" />,
        shield: <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />,
    };
    return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{paths[name]}</svg>;
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

export function Layout({ locale, categories = [], leaders = [], children, active = '', searchPosts = [] }) {
    const t = dictionary(locale);
    const user = usePage().props.auth?.user;
    const access = useCommunityAccess();
    const [query, setQuery] = useState('');
    const [menuOpen, setMenuOpen] = useState(false);
    const [searchOpen, setSearchOpen] = useState(false);
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
            <div className="vt-header-inner vm-shell vm-header-inner vm-header-2col">
                <Link href={path(locale)} className="vt-wordmark vm-brand" aria-label="Votmee home">votmee</Link>
                <SearchMenu {...searchMenuProps} />
                <div className="vt-header-actions vm-header-right">
                    <LanguageSelect language={locale} onChange={(next) => router.visit(switchLocale(next))} />
                    {user ? <ProfileDropdown user={user} locale={locale} canModerate={access.moderate} />
                        : <><Link className="vt-login" href={path(locale, '/login')}>{t.login}</Link><Link className="vt-register" href={path(locale, '/register')}>{locale === 'id' ? 'Daftar' : 'Sign up'}</Link></>}
                </div>
                <div className="vm-mobile-header-actions">
                    <button type="button" aria-label={t.searchAction} aria-expanded={searchOpen} onClick={() => setSearchOpen(!searchOpen)}><NavIcon name="search" size={21} /></button>
                    <button type="button" aria-label={t.menu} aria-expanded={menuOpen} onClick={() => setMenuOpen(!menuOpen)}><NavIcon name="menu" size={23} /></button>
                </div>
            </div>
            {searchOpen && <div className="vm-mobile-search-wrapper"><SearchMenu {...searchMenuProps} isMobile autoFocus onClose={() => setSearchOpen(false)} /></div>}
        </header>
        <div className="vt-shell vm-shell vm-layout vm-layout-2col">
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
                    {!user && <><Link href={path(locale, '/login')}>{t.login}</Link><Link href={path(locale, '/register')}>{locale === 'id' ? 'Daftar' : 'Sign up'}</Link></>}
                    <LanguageSelect language={locale} onChange={(next) => router.visit(switchLocale(next))} mobile />
                </div>
            </aside>
            <main className="vt-main vm-main" id="main-content">{children}</main>
        </div>
        <nav className={`vt-bottom-nav vm-bottom-nav ${user ? '' : 'vm-bottom-nav-guest'}`} aria-label={t.menu}>
            <Link className={active === 'home' ? 'active' : ''} href={path(locale)}><NavIcon name="home" size={21} /><span>{t.home}</span></Link>
            <Link className={active === 'explore' ? 'active' : ''} href={path(locale, '/explore')}><NavIcon name="search" size={21} /><span>{t.explore}</span></Link>
            {access.createRanking && <Link className="vm-bottom-create" href={path(locale, '/create')} aria-label={t.create}><NavIcon name="plus" size={26} /></Link>}
            {access.viewWallet && <Link href={path(locale, '/wallet')}><NavIcon name="wallet" size={21} /><span>{t.wallet}</span></Link>}
            {user ? <Link href={path(locale, `/profile/${user.username}`)}><NavIcon name="user" size={21} /><span>{t.profile}</span></Link> : <Link href={path(locale, '/login')}><NavIcon name="user" size={21} /><span>{t.login}</span></Link>}
        </nav>
    </div>;
}

export function Leaderboard({ locale, initialLeaders = [] }) {
    const t = dictionary(locale);
    const [period, setPeriod] = useState('weekly');
    const [rankType, setRankType] = useState('community');
    const [leaders, setLeaders] = useState(initialLeaders);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (period === 'weekly' && rankType === 'community') { setLeaders(initialLeaders); return; }
        let active = true;
        setLoading(true);
        api(`/backend/leaderboard?period=${period}&rank_type=${rankType}`).then(result => {
            if (active) setLeaders(result.leaders);
        }).catch(() => { if (active) setLeaders([]); }).finally(() => { if (active) setLoading(false); });
        return () => { active = false; };
    }, [period, rankType, initialLeaders]);

    return <section className="vt-leaderboard vm-panel">
        <h2>{locale === 'id' ? 'Peringkat Teratas' : 'Top Rankings'}</h2>
        <div className="vt-periods vm-periods" role="group" aria-label={t.community}>
            {['daily', 'weekly', 'all_time'].map(value =>
                <button key={value} className={period === value ? 'active' : ''} onClick={() => setPeriod(value)}>{value === 'all_time' ? t.allTime : t[value]}</button>)}
        </div>
        <select className="vt-rank-type" value={rankType} aria-label={t.community} onChange={e => setRankType(e.target.value)}>
            <option value="community">{t.communityRank}</option><option value="support">{t.supportRank}</option>
        </select>
        {loading ? <p className="vt-muted" role="status">…</p> : leaders.length ? <ol className="vt-leader-list vm-leader-list">{leaders.slice(0, 5).map((leader, index) =>
            <li key={leader.object_id}><span className={`vm-leader-position vm-position-${index + 1}`}>{index + 1}</span><img className="vt-leader-image" src={`/images/stitch/object-${index + 1}.jpg`} alt="" loading="lazy" /><span className="vm-leader-name"><strong><Link href={path(locale, `/object/${leader.slug}`)}>{leader.name}</Link></strong><small>{numberLabel(leader.score, locale)} {locale === 'id' ? 'poin' : 'points'}</small></span><span className="vt-leader-score">{numberLabel(leader.score, locale)}</span></li>)}</ol>
            : <p className="vt-muted vt-side-empty">{t.noResults}</p>}
        <Link className="vm-panel-link" href={path(locale, '/explore')}>{locale === 'id' ? 'Lihat semua ranking' : 'View all rankings'} →</Link>
    </section>;
}

export function RankingCard({ post, locale, category }) {
    const t = dictionary(locale);
    const categorySlug = post.category?.slug ?? category?.slug ?? '';
    const imageNumber = categorySlug === 'wisata' ? 2 : categorySlug === 'kuliner' ? 3 : categorySlug === 'otomotif' ? 4 : categorySlug === 'hiburan' ? 5 : 1;
    const rankingUrl = path(locale, `/ranking/${post.slug}`);
    const author = post.creator?.username ? `@${post.creator.username}` : post.creator?.name ?? 'Votmee';
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
            <div className="vm-feed-meta"><span className="vm-avatar">{author.replace('@', '').slice(0, 1).toUpperCase()}</span><span>{author} <span className="vm-dot">·</span> {relativeDate(post.published_at, locale)}</span><span className="vm-desktop-comments"><span className="vm-dot">·</span><NavIcon name="comment" size={15} /> {numberLabel(post.comments_count ?? 0, locale)} {t.comments}</span></div>
        </div>
        <div className="vm-mobile-footer">
            <span className="vm-mobile-author"><span className="vm-avatar">{author.replace('@', '').slice(0, 1).toUpperCase()}</span><span>{author} · {relativeDate(post.published_at, locale)}</span></span>
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
