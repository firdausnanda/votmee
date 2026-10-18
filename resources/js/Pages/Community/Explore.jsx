import { Link } from '@inertiajs/react';
import { categoryHashtag, dictionary, EmptyState, Layout, numberLabel, PageHead, PaginationLinks, RankingCard, path } from './Shared';

export default function Explore({ locale, categories, leaders, feed, filters, objects }) {
    const t = dictionary(locale);
    const isId = locale === 'id';
    const activeCategory = categories.find((category) => String(category.id) === String(filters.category_id));
    const hasActiveFilters = Boolean(filters.q || filters.category_id);
    const featuredPost = !filters.q && feed.current_page === 1 ? feed.data[0] : null;
    const regularPosts = featuredPost ? feed.data.slice(1) : feed.data;
    const exploreUrl = ({ q = filters.q, category_id = filters.category_id, sort = filters.sort } = {}) => {
        const params = new URLSearchParams();
        if (q) params.set('q', q);
        if (category_id) params.set('category_id', category_id);
        if (sort && sort !== 'for_you') params.set('sort', sort);
        const search = params.toString();
        return `${path(locale, '/explore')}${search ? `?${search}` : ''}`;
    };

    return <Layout locale={locale} categories={categories} leaders={leaders} searchPosts={feed.data} active="explore">
        <PageHead
            title={t.explore}
            locale={locale}
            description={isId ? 'Cari ranking dan objek komunitas berdasarkan kata kunci dan kategori.' : 'Find community rankings and objects by keyword and category.'}
        />
        <div className="vx-explore">
            <h1 className="vm-sr-only">{t.explore}</h1>
            <nav className="vx-category-rail" aria-label={isId ? 'Jelajahi berdasarkan kategori' : 'Browse by category'}>
                <Link href={exploreUrl({ category_id: null })} preserveScroll className={!activeCategory ? 'is-active' : ''} aria-current={!activeCategory ? 'page' : undefined}>{isId ? 'Semua topik' : 'All topics'}</Link>
                {categories.map((category) => <Link key={category.id} href={exploreUrl({ category_id: category.id })} preserveScroll className={activeCategory?.id === category.id ? 'is-active' : ''} aria-current={activeCategory?.id === category.id ? 'page' : undefined}>
                    {categoryHashtag(category)}
                </Link>)}
            </nav>

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
                        <span className="vx-object-avatar" aria-hidden="true">{object.canonical_name.slice(0, 1).toUpperCase()}</span>
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

            {featuredPost && <section className="vx-featured" aria-labelledby="explore-featured-heading">
                <div className="vx-section-heading">
                    <div>
                        <span className="vx-section-kicker">{isId ? 'MULAI DARI SINI' : 'START HERE'}</span>
                        <h2 id="explore-featured-heading">{isId ? 'Sorotan komunitas' : 'Community spotlight'}</h2>
                    </div>
                    <span className="vx-section-side-note">{isId ? 'Pilihan untuk dijelajahi' : 'A ranking to explore'}</span>
                </div>
                <RankingCard post={featuredPost} locale={locale} category={categories.find((category) => category.id === featuredPost.category_id)} />
            </section>}

            <section className="vx-results-section vx-ranking-section" aria-labelledby="explore-rankings-heading">
                <div className="vx-section-heading">
                    <div>
                        <span className="vx-section-kicker">{isId ? 'TEMUKAN LEBIH BANYAK' : 'DISCOVER MORE'}</span>
                        <h2 id="explore-rankings-heading">{t.rankings}</h2>
                        <p>{hasActiveFilters
                            ? (isId ? 'Ranking sesuai filter Anda' : 'Rankings matching your filters')
                            : (isId ? 'Ide dan pilihan dari komunitas' : 'Ideas and picks from the community')}
                        </p>
                    </div>
                </div>
                <nav className="vx-sort-tabs" aria-label={isId ? 'Urutkan ranking' : 'Sort rankings'}>
                    {[['for_you', isId ? 'Untukmu' : 'For you'], ['popular', isId ? 'Populer' : 'Popular'], ['latest', isId ? 'Terbaru' : 'Latest']].map(([value, label]) =>
                        <Link key={value} href={exploreUrl({ sort: value })} preserveScroll className={filters.sort === value ? 'is-active' : ''} aria-current={filters.sort === value ? 'page' : undefined}>{label}</Link>)}
                </nav>
                <div className="vt-feed-list vm-feed-list vx-explore-feed">
                    {regularPosts.length
                        ? regularPosts.map((post) => <RankingCard key={post.id} post={post} locale={locale} category={categories.find((category) => category.id === post.category_id)} />)
                        : featuredPost ? null
                          : hasActiveFilters
                          ? <div className="vx-empty-result" role="status">
                                <strong>{t.noResults}</strong>
                                <p>{isId ? 'Coba kata kunci atau kategori lain.' : 'Try another keyword or category.'}</p>
                            </div>
                          : <EmptyState locale={locale} />}
                </div>
                <PaginationLinks pagination={feed} locale={locale} />
            </section>
        </div>
    </Layout>;
}
