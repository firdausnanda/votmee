import { Link, router } from '@inertiajs/react';
import { dictionary, EmptyState, Layout, PageHead, PaginationLinks, RankingCard, path, useCommunityAccess } from './Shared';

export default function Feed({ locale, categories, leaders, feed, filters }) {
    const t = dictionary(locale);
    const access = useCommunityAccess();
    const activeSort = filters.sort || 'for_you';
    const visit = (sort) => router.get(path(locale), { sort });

    return <Layout locale={locale} categories={categories} leaders={leaders} searchPosts={feed.data} active="home">
        <PageHead title={t.home} locale={locale} description={t.feedDescription} />
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
        <div className="vt-feed-toolbar vm-feed-toolbar">
            <div className="vt-tabs vm-tabs" role="group" aria-label={t.rankings}>
                {['for_you', 'popular', 'latest'].map(sort => <button key={sort} className={activeSort === sort ? 'active' : ''} onClick={() => visit(sort)}>{sort === 'for_you' ? t.forYou : t[sort]}</button>)}
                <Link className="vt-global-tab" href={path(locale, '/explore')}>{locale === 'id' ? 'Peringkat Global' : 'Global Rankings'}</Link>
            </div>
        </div>
        <div className="vt-feed-list vm-feed-list">{feed.data.length ? feed.data.map(post => <RankingCard key={post.id} post={post} locale={locale} category={categories.find(item => item.id === post.category_id)} />) : <EmptyState locale={locale} />}</div>
        <PaginationLinks pagination={feed} locale={locale} />
    </Layout>;
}
