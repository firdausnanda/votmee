import { InfiniteScroll, Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { categoryHashtag, dictionary, EmptyState, Layout, Leaderboard, MobileQuickDuel, NavIcon, numberLabel, objectImageSrcSet, objectImageUrl, PageHead, RankingCard, path, sortCategoriesByPopularity } from './Shared';

const CATEGORY_CARD_LIMIT = 12;

export default function Explore({ locale, categories, leaders, feed, filters, objects, spotlight = null, rising = [] }) {
    const t = dictionary(locale);
    const isId = locale === 'id';
    const activeCategory = categories.find((category) => String(category.id) === String(filters.category_id));
    const hasActiveFilters = Boolean(filters.q || filters.category_id);
    const risingRef = useRef(null);
    const [risingColumns, setRisingColumns] = useState(null);
    const visibleRising = risingColumns ? rising.slice(0, risingColumns) : rising;
    const categoryRailRef = useRef(null);
    const [categoryQuery, setCategoryQuery] = useState('');
    const [showAllCategories, setShowAllCategories] = useState(false);
    const popularCategories = sortCategoriesByPopularity(categories);
    const normalizedCategoryQuery = categoryQuery.trim().toLowerCase();
    const filteredCategories = normalizedCategoryQuery
        ? popularCategories.filter((category) => category.name.toLowerCase().includes(normalizedCategoryQuery) || category.slug.includes(normalizedCategoryQuery))
        : popularCategories;
    const visibleCategories = showAllCategories || normalizedCategoryQuery ? filteredCategories : filteredCategories.slice(0, CATEGORY_CARD_LIMIT);
    const hiddenCategoryCount = filteredCategories.length - visibleCategories.length;

    useEffect(() => {
        categoryRailRef.current?.querySelector('.is-active')?.scrollIntoView({ block: 'nearest', inline: 'center' });
    }, [activeCategory?.id]);

    useEffect(() => {
        const list = risingRef.current;
        if (!list) return undefined;

        const fitToOneRow = () => {
            if (window.matchMedia('(max-width: 767px)').matches) {
                setRisingColumns(null);
                return;
            }
            const style = getComputedStyle(list);
            const gap = parseFloat(style.columnGap) || 0;
            const minimum = 128;
            setRisingColumns(Math.max(1, Math.floor((list.clientWidth + gap) / (minimum + gap))));
        };

        fitToOneRow();
        const observer = new ResizeObserver(fitToOneRow);
        observer.observe(list);

        return () => observer.disconnect();
    }, [rising.length]);

    const regularPosts = spotlight ? feed.data.filter((post) => post.id !== spotlight.id) : feed.data;
    const categoryOf = (post) => categories.find((category) => category.id === post.category_id);
    const exploreUrl = ({ q = filters.q, category = activeCategory } = {}) => {
        const params = new URLSearchParams();
        if (q) params.set('q', q);
        if (category) params.set('category', category.slug);
        const search = params.toString();
        return `${path(locale, '/explore')}${search ? `?${search}` : ''}`;
    };
    const pageTitle = filters.q
        ? `${isId ? 'Hasil untuk' : 'Results for'} "${filters.q}"`
        : activeCategory ? `${categoryHashtag(activeCategory)} · ${t.explore}` : t.explore;

    return <Layout locale={locale} categories={categories} leaders={leaders} searchPosts={feed.data} active="explore">
        <PageHead
            title={pageTitle}
            locale={locale}
            description={isId ? 'Cari ranking dan objek komunitas berdasarkan kata kunci dan kategori.' : 'Find community rankings and objects by keyword and category.'}
        />
        <div className="vx-explore">
            <h1 className="vm-sr-only">{t.explore}</h1>

            {hasActiveFilters && <nav ref={categoryRailRef} className="vx-category-rail" aria-label={isId ? 'Jelajahi berdasarkan kategori' : 'Browse by category'}>
                <Link href={exploreUrl({ category: null })} preserveScroll className={!activeCategory ? 'is-active' : ''} aria-current={!activeCategory ? 'page' : undefined}>{isId ? 'Semua topik' : 'All topics'}</Link>
                {categories.map((category) => <Link key={category.id} href={exploreUrl({ category })} preserveScroll className={activeCategory?.id === category.id ? 'is-active' : ''} aria-current={activeCategory?.id === category.id ? 'page' : undefined}>
                    {categoryHashtag(category)}
                </Link>)}
            </nav>}

            <div className="vm-mobile-leaderboard"><Leaderboard locale={locale} initialLeaders={leaders} /></div>
            <MobileQuickDuel locale={locale} />

            {hasActiveFilters && <div className="vx-active-filters" aria-label={isId ? 'Filter aktif' : 'Active filters'}>
                <span>{isId ? 'Menampilkan hasil untuk' : 'Showing results for'}</span>
                {filters.q && <strong>{filters.q}</strong>}
                {activeCategory && <strong>{categoryHashtag(activeCategory)}</strong>}
                <Link href={path(locale, '/explore')} preserveScroll>{isId ? 'Hapus filter' : 'Clear filters'}</Link>
            </div>}

            {filters.q && <section className="vx-results-section" aria-labelledby="explore-objects-heading">
                <div className="vx-section-heading">
                    <div>
                        <span className="vx-section-kicker">{isId ? 'HASIL PENCARIAN' : 'SEARCH RESULTS'}</span>
                        <h2 id="explore-objects-heading">{t.objects}<span>{numberLabel(objects.length, locale)}</span></h2>
                        <p>{isId ? 'Objek yang cocok dengan pencarian Anda' : 'Objects matching your search'}</p>
                    </div>
                </div>
                {objects.length ? <div className="vx-object-results">
                    {objects.map((object) => <Link key={object.id} href={path(locale, `/object/${object.slug}`)}>
                        <span className="vx-object-avatar" aria-hidden="true">
                            {object.status === 'verified' && object.image_path
                                ? <img src={objectImageUrl(object, 'small')} srcSet={objectImageSrcSet(object)} sizes="40px" alt="" loading="lazy" />
                                : object.canonical_name.slice(0, 1).toUpperCase()}
                        </span>
                        <span className="vx-object-name">{object.canonical_name}</span>
                        <small className={object.status === 'verified' ? 'is-verified' : ''}>
                            {object.status === 'verified' ? t.verified : t.pending}
                        </small>
                        <span className="vx-object-chevron" aria-hidden="true">↗</span>
                    </Link>)}
                </div> : <div className="vx-empty-result" role="status">
                    <strong>{t.noResults}</strong>
                    <p>{isId ? 'Coba kata kunci atau kategori lain.' : 'Try another keyword or category.'}</p>
                </div>}
            </section>}

            {rising.length > 0 && <section className="vx-rising" aria-labelledby="explore-rising-heading">
                <div className="vx-section-heading">
                    <div>
                        <span className="vx-section-kicker">{isId ? 'MINGGU INI' : 'THIS WEEK'}</span>
                        <h2 id="explore-rising-heading">{isId ? 'Objek sedang naik' : 'Rising objects'}</h2>
                        <p>{isId ? 'Kenaikan poin terbesar dibanding minggu lalu' : 'Biggest point gains compared with last week'}</p>
                    </div>
                </div>
                <ol className="vx-rising-list" ref={risingRef}>
                    {visibleRising.map((object, index) => <li key={object.object_id}>
                        <Link href={path(locale, `/object/${object.slug}`)} title={object.name}>
                            <span className="vx-rising-photo" aria-hidden="true">
                                {object.image_path
                                    ? <img src={objectImageUrl(object, 'small')} srcSet={objectImageSrcSet(object)} sizes="68px" alt="" loading="lazy" decoding="async" />
                                    : object.name.slice(0, 1).toUpperCase()}
                                <b>{index + 1}</b>
                            </span>
                            <strong className="vx-rising-name">{object.name}</strong>
                            <span className="vx-rising-gain is-up">▲ {numberLabel(object.delta, locale)} {t.points}</span>
                        </Link>
                    </li>)}
                </ol>
            </section>}

            {!hasActiveFilters && <section className="vx-categories" aria-labelledby="explore-categories-heading">
                <div className="vx-section-heading">
                    <div>
                        <span className="vx-section-kicker">{isId ? 'TOPIK' : 'TOPICS'}</span>
                        <h2 id="explore-categories-heading">{isId ? 'Jelajahi kategori' : 'Browse categories'}</h2>
                    </div>
                    {categories.length > CATEGORY_CARD_LIMIT && <label className="vx-category-search">
                        <span className="vm-sr-only">{isId ? 'Cari kategori' : 'Search categories'}</span>
                        <NavIcon name="search" size={15} />
                        <input type="search" value={categoryQuery} onChange={(event) => setCategoryQuery(event.target.value)} placeholder={isId ? 'Cari kategori...' : 'Search categories...'} />
                    </label>}
                </div>
                {visibleCategories.length ? <div className="vx-category-cards">
                    {visibleCategories.map((category) => <Link key={category.id} href={exploreUrl({ category })}>
                        <span className={`vx-cat-icon vm-cover-ph-${category.slug}`} aria-hidden="true"><NavIcon name="trophy" size={18} /></span>
                        <strong>{category.name}</strong>
                        <small>{numberLabel(category.ranking_posts_count ?? 0, locale)} {t.rankings}</small>
                    </Link>)}
                </div> : <p className="vx-category-empty" role="status">{isId ? `Tidak ada kategori yang cocok dengan "${categoryQuery}".` : `No categories match "${categoryQuery}".`}</p>}
                {!normalizedCategoryQuery && filteredCategories.length > CATEGORY_CARD_LIMIT && <button type="button" className="vx-category-toggle" aria-expanded={showAllCategories} onClick={() => setShowAllCategories((value) => !value)}>
                    {showAllCategories
                        ? (isId ? 'Tampilkan lebih sedikit' : 'Show less')
                        : (isId ? `Tampilkan semua kategori (+${hiddenCategoryCount})` : `Show all categories (+${hiddenCategoryCount})`)}
                </button>}
            </section>}

            {spotlight && <section className="vx-featured" aria-labelledby="explore-featured-heading">
                <div className="vx-section-heading">
                    <div>
                        <span className="vx-section-kicker">{isId ? 'MULAI DARI SINI' : 'START HERE'}</span>
                        <h2 id="explore-featured-heading">{isId ? 'Sorotan komunitas' : 'Community spotlight'}</h2>
                    </div>
                    <span className="vx-section-side-note">{isId ? 'Paling ramai minggu ini' : 'Busiest this week'}</span>
                </div>
                <RankingCard post={spotlight} locale={locale} category={categoryOf(spotlight)} />
            </section>}

            <section className="vx-results-section vx-ranking-section" aria-labelledby="explore-rankings-heading">
                <div className="vx-section-heading">
                    <div>
                        <span className="vx-section-kicker">{isId ? 'TEMUKAN LEBIH BANYAK' : 'DISCOVER MORE'}</span>
                        <h2 id="explore-rankings-heading">{hasActiveFilters ? t.rankings : (isId ? 'Ranking terbaru' : 'Latest rankings')}</h2>
                        <p>{hasActiveFilters
                            ? (isId ? 'Ranking sesuai filter Anda' : 'Rankings matching your filters')
                            : (isId ? 'Baru dipublikasikan oleh komunitas' : 'Freshly published by the community')}
                        </p>
                    </div>
                </div>
                {regularPosts.length ? <InfiniteScroll
                    data="feed"
                    buffer={360}
                    className="vt-feed-list vm-feed-list vx-explore-feed"
                    loading={() => <p className="vt-feed-scroll-status" role="status">{isId ? 'Memuat ranking...' : 'Loading rankings...'}</p>}
                    next={({ loading, fetch, hasMore }) => <div className="vt-feed-scroll-footer">
                        {loading ? <span role="status">{isId ? 'Memuat ranking...' : 'Loading rankings...'}</span>
                            : hasMore ? <a href={feed.next_page_url} onClick={(event) => { event.preventDefault(); fetch(); }}>{t.loadMore}</a>
                                : <span role="status">{isId ? 'Semua ranking sudah ditampilkan.' : 'You have seen all rankings.'}</span>}
                    </div>}
                >
                    {regularPosts.map((post) => <RankingCard key={post.id} post={post} locale={locale} category={categoryOf(post)} />)}
                </InfiniteScroll> : spotlight ? null
                    : hasActiveFilters
                        ? <div className="vx-empty-result" role="status">
                            <strong>{t.noResults}</strong>
                            <p>{isId ? 'Coba kata kunci lain, atau jelajahi kategori berikut.' : 'Try another keyword, or browse these categories.'}</p>
                            <div className="vx-empty-links">
                                {categories.slice(0, 5).map((category) => <Link key={category.id} href={exploreUrl({ q: null, category })}>{categoryHashtag(category)}</Link>)}
                            </div>
                        </div>
                        : <EmptyState locale={locale} />}
            </section>
        </div>
    </Layout>;
}
