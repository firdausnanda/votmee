import { InfiniteScroll, Link } from '@inertiajs/react';
import { dictionary, EmptyState, Layout, Leaderboard, MobileQuickDuel, PageHead, RankingCard, path, useCommunityAccess } from './Shared';

const periodLabels = {
    id: { today: 'Hari ini', week: 'Minggu ini', all: 'Sepanjang masa' },
    en: { today: 'Today', week: 'This week', all: 'All time' },
};

export default function Feed({ locale, categories, leaders, feed, filters, loginModal = false, registerModal = false }) {
    const t = dictionary(locale);
    const access = useCommunityAccess();
    const activeSort = filters.sort || 'for_you';
    const activePeriod = filters.period || 'week';
    const sortUrl = (sort) => sort === 'for_you' ? path(locale) : `${path(locale)}?sort=${sort}`;

    return <Layout locale={locale} categories={categories} leaders={leaders} searchPosts={feed.data} active="home" loginModal={loginModal} registerModal={registerModal}>
        <PageHead title={registerModal ? (locale === 'id' ? 'Daftar' : 'Sign up') : loginModal ? t.login : t.home} locale={locale} description={t.feedDescription} />
        <section className="vm-hero" aria-labelledby="vm-hero-title">
            <div className="vm-hero-copy">
                <h1 id="vm-hero-title">Where Favorites Rise.</h1>
                <p>{t.feedDescription}</p>
                {access.createRanking && <Link className="vt-hero-create" href={path(locale, '/create')}>{t.create} →</Link>}
            </div>
            <div className="vm-hero-illustration" aria-hidden="true">
                <div className="vm-phone"><span className="vm-phone-speaker" /><strong>Ranking #1</strong><span className="vm-phone-line" /><span className="vm-phone-line short" /><small>▲ 98.4%</small></div>
                <span className="vm-hero-trophy"><svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M7 3h10v8a5 5 0 0 1-10 0V3ZM7 5H4v3a4 4 0 0 0 4 4M17 5h3v3a4 4 0 0 1-4 4M12 16v4m-4 1h8" /></svg></span><span className="vm-hero-trend"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="m3 17 6-6 4 4 7-8M14 7h6v6" /></svg></span>
            </div>
        </section>
        <div className="vm-mobile-leaderboard"><Leaderboard locale={locale} initialLeaders={leaders} /></div>
        <MobileQuickDuel locale={locale} />
        <div className="vt-feed-toolbar vm-feed-toolbar">
            <div className="vt-tabs vm-tabs" role="group" aria-label={t.rankings}>
                {['for_you', 'popular', 'latest'].map(sort => <a key={sort} href={sortUrl(sort)} className={`vt-sort-tab${activeSort === sort ? ' active' : ''}`} aria-current={activeSort === sort ? 'page' : undefined}>{sort === 'for_you' ? t.forYou : t[sort]}</a>)}            </div>
            {activeSort === 'popular' && <div className="vt-tabs vm-tabs vm-period-tabs" role="group" aria-label={locale === 'id' ? 'Periode populer' : 'Popular period'}>
                {Object.entries(periodLabels[locale] || periodLabels.en).map(([period, label]) => <a key={period} href={`${path(locale)}?sort=popular&period=${period}`} className={`vt-sort-tab${activePeriod === period ? ' active' : ''}`} aria-current={activePeriod === period ? 'page' : undefined}>{label}</a>)}
            </div>}
        </div>
        {feed.data.length ? <InfiniteScroll
            data="feed"
            buffer={360}
            className="vt-feed-list vm-feed-list"
            loading={() => <p className="vt-feed-scroll-status" role="status">{locale === 'id' ? 'Memuat ranking...' : 'Loading rankings...'}</p>}
            next={({ loading, fetch, hasMore }) => <div className="vt-feed-scroll-footer">
                {loading ? <span role="status">{locale === 'id' ? 'Memuat ranking...' : 'Loading rankings...'}</span>
                    : hasMore ? <button type="button" onClick={fetch}>{t.loadMore}</button>
                        : <span role="status">{locale === 'id' ? 'Semua ranking sudah ditampilkan.' : 'You have seen all rankings.'}</span>}
            </div>}
        >
            {feed.data.map(post => <RankingCard key={post.id} post={post} locale={locale} category={categories.find(item => item.id === post.category_id)} />)}
        </InfiniteScroll> : <EmptyState locale={locale} />}
    </Layout>;
}
