import { useState, useMemo, useRef } from 'react';
import { Link, usePage } from '@inertiajs/react';
import SelectBox from '../../Components/SelectBox';
import { categoryHashtag, dateLabel, dictionary, Layout, NavIcon, numberLabel, PageHead, PaginationLinks, path } from './Shared';

function formatMemberSince(isoDate, locale) {
    if (!isoDate) return '—';
    const date = new Date(isoDate);
    if (Number.isNaN(date.getTime())) return '—';
    return new Intl.DateTimeFormat(locale === 'id' ? 'id-ID' : 'en-US', {
        month: 'long',
        year: 'numeric',
    }).format(date);
}

export default function Profile({ locale, categories = [], leaders = [], profile, rankings }) {
    const t = dictionary(locale);
    const currentUser = usePage().props.auth?.user;
    const isOwnProfile = currentUser && currentUser.id === profile.id;

    const fileInputRef = useRef(null);
    const [coverPreview, setCoverPreview] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('');

    const handleCoverChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            const url = URL.createObjectURL(file);
            setCoverPreview(url);
        }
    };

    const roleName = profile.role === 'admin'
        ? 'Administrator'
        : (profile.role === 'moderator'
            ? 'Moderator'
            : (locale === 'id' ? 'Kontributor' : 'Contributor'));

    const roleClass = profile.role === 'admin'
        ? 'vpr-badge-admin'
        : (profile.role === 'moderator'
            ? 'vpr-badge-mod'
            : 'vpr-badge-user');

    // Extract available categories from profile's current rankings
    const rankingCategories = useMemo(() => {
        const map = new Map();
        (rankings.data || []).forEach(post => {
            if (post.category && !map.has(post.category.slug)) {
                map.set(post.category.slug, post.category.name);
            }
        });
        return Array.from(map.entries()).map(([slug, name]) => ({ slug, name }));
    }, [rankings.data]);

    // Client-side quick filter
    const filteredRankings = useMemo(() => {
        return (rankings.data || []).filter(post => {
            const matchesCategory = !selectedCategory || post.category?.slug === selectedCategory;
            const query = searchQuery.trim().toLowerCase();
            const matchesQuery = !query ||
                post.title?.toLowerCase().includes(query) ||
                post.criteria?.toLowerCase().includes(query) ||
                post.category?.name?.toLowerCase().includes(query);
            return matchesCategory && matchesQuery;
        });
    }, [rankings.data, selectedCategory, searchQuery]);

    const stats = profile.stats || {};
    const supportPointsReceived = stats.support_points_received ?? 0;
    const walletBalance = stats.wallet_balance ?? 0;

    return (
        <Layout locale={locale} categories={categories} leaders={leaders} active="profile">
            <PageHead
                title={`${profile.name} (@${profile.username})`}
                locale={locale}
                description={locale === 'id'
                    ? `Profil kreator ${profile.name} di Votmee. Temukan ranking, kontribusi, dan dukungan komunitas.`
                    : `Creator profile of ${profile.name} on Votmee. Explore rankings, contributions, and community support.`
                }
            />

            <div className="vpr-page">
                {/* 1. Hero Cover & Profile Identity Card */}
                <header className="vpr-hero">
                    <div
                        className="vpr-cover"
                        style={coverPreview ? { backgroundImage: `url(${coverPreview})`, backgroundSize: 'cover', backgroundPosition: 'center' } : undefined}
                    >
                        <input
                            type="file"
                            ref={fileInputRef}
                            accept="image/*"
                            style={{ display: 'none' }}
                            onChange={handleCoverChange}
                        />
                        {(isOwnProfile || !currentUser) && (
                            <button
                                type="button"
                                className="vpr-cover-btn"
                                onClick={() => fileInputRef.current?.click()}
                                aria-label={locale === 'id' ? 'Edit foto sampul' : 'Edit cover photo'}
                            >
                                <NavIcon name="camera" size={9} />
                                <span>{locale === 'id' ? 'Edit Sampul' : 'Edit Cover'}</span>
                            </button>
                        )}
                    </div>

                    <div className="vpr-card">
                        <div className="vpr-card-main">
                            <div className="vpr-avatar-wrap">
                                <div className="vpr-avatar" aria-hidden="true">
                                    {profile.name ? profile.name.slice(0, 1).toUpperCase() : 'U'}
                                </div>
                                <span className={`vpr-avatar-role-pill ${roleClass}`}>
                                    {roleName}
                                </span>
                            </div>

                            <div className="vpr-identity">
                                <div className="vpr-identity-header">
                                    <div className="vpr-name-group">
                                        <h1 className="vpr-name">{profile.name}</h1>
                                        {isOwnProfile && (
                                            <Link
                                                href="/profile"
                                                className="vpr-action-icon"
                                                aria-label={locale === 'id' ? 'Pengaturan akun' : 'Account settings'}
                                                title={locale === 'id' ? 'Pengaturan akun' : 'Account settings'}
                                            >
                                                <NavIcon name="edit" size={13} />
                                            </Link>
                                        )}
                                    </div>
                                </div>
                                <p className="vpr-handle">@{profile.username}</p>

                                <div className="vpr-meta-chips">
                                    <span className="vpr-chip">
                                        <NavIcon name="calendar" size={13} />
                                        {locale === 'id' ? 'Bergabung' : 'Joined'} {formatMemberSince(profile.created_at, locale)}
                                    </span>
                                    <span className="vpr-chip">
                                        <NavIcon name="globe" size={13} />
                                        {locale === 'id' ? 'Wilayah' : 'Region'} {profile.region_code || 'ID'}
                                    </span>
                                    <span className="vpr-chip">
                                        {profile.locale?.toUpperCase() || 'ID'}
                                    </span>
                                </div>
                            </div>
                            <div className="vpr-inline-stats" aria-label={locale === 'id' ? 'Statistik Poin' : 'Point Statistics'}>
                                <div className="vpr-inline-stat">
                                    <NavIcon name="heart" size={14} />
                                    <strong>{numberLabel(supportPointsReceived, locale)}</strong>
                                    <span>{locale === 'id' ? 'Total Poin Diterima' : 'Total Points Received'}</span>
                                </div>
                                <div className="vpr-inline-stat">
                                    <NavIcon name="wallet" size={14} />
                                    <strong>{numberLabel(walletBalance, locale)}</strong>
                                    <span>{locale === 'id' ? 'Poin Dimiliki' : 'Points Owned'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                {/* 3. Published Rankings Showcase & Filter */}
                <main className="vpr-content" aria-labelledby="vpr-rankings-heading">
                    <div className="vpr-content-header">
                        <div className="vpr-content-title-wrap">
                            <h2 id="vpr-rankings-heading" className="vpr-content-title">
                                {locale === 'id' ? 'Daftar Ranking' : 'Rankings List'}
                            </h2>
                            <span className="vpr-count-pill">
                                {numberLabel(filteredRankings.length, locale)}
                            </span>
                        </div>

                        {(rankings.data?.length > 0 || searchQuery || selectedCategory) && (
                            <div className="vpr-filter-bar">
                                <div className="vpr-search-box">
                                    <NavIcon name="search" size={14} />
                                    <input
                                        type="text"
                                        value={searchQuery}
                                        onChange={e => setSearchQuery(e.target.value)}
                                        placeholder={locale === 'id' ? 'Cari ranking...' : 'Search rankings...'}
                                        aria-label={locale === 'id' ? 'Cari ranking' : 'Search rankings'}
                                    />
                                    {searchQuery && (
                                        <button
                                            type="button"
                                            className="vpr-clear-search"
                                            onClick={() => setSearchQuery('')}
                                            aria-label={locale === 'id' ? 'Hapus pencarian' : 'Clear search'}
                                        >
                                            ×
                                        </button>
                                    )}
                                </div>

                                {rankingCategories.length > 1 && (
                                    <SelectBox
                                        value={selectedCategory}
                                        onChange={setSelectedCategory}
                                        options={[
                                            { value: '', label: locale === 'id' ? 'Semua Kategori' : 'All Categories' },
                                            ...rankingCategories.map(cat => ({ value: cat.slug, label: cat.name })),
                                        ]}
                                        placeholder={locale === 'id' ? 'Filter kategori' : 'Filter category'}
                                        searchable={true}
                                        searchPlaceholder={locale === 'id' ? 'Cari kategori...' : 'Search categories...'}
                                        searchLabels={locale === 'id' ? {
                                            all: 'Kategori tersedia',
                                            results: 'Hasil pencarian',
                                            of: 'dari',
                                            empty: 'Tidak ada kategori yang cocok',
                                            hint: 'Coba kata kunci lain atau hapus pencarian.',
                                            clear: 'Hapus pencarian',
                                        } : {
                                            all: 'Available categories',
                                            results: 'Search results',
                                            of: 'of',
                                            empty: 'No matching categories',
                                            hint: 'Try another keyword or clear the search.',
                                            clear: 'Clear search',
                                        }}
                                        clearable={true}
                                        align="right"
                                        className="vpr-category-filter"
                                    />
                                )}
                            </div>
                        )}
                    </div>

                    {/* Rankings Grid or Empty State */}
                    {filteredRankings.length > 0 ? (
                        <div className="vpr-grid">
                            {filteredRankings.map(post => {
                                const contenders = post.entries || [];
                                return (
                                    <article className="vpr-ranking-card" key={post.id}>
                                        <div className="vpr-card-header">
                                            <span className={`vm-category-tag vm-tag-${post.category?.slug ?? 'default'}`}>
                                                {categoryHashtag(post.category) || (locale === 'id' ? 'Umum' : 'General')}
                                            </span>
                                            <span className="vpr-date">{dateLabel(post.published_at, locale)}</span>
                                        </div>

                                        <h3 className="vpr-ranking-title">
                                            <Link href={path(locale, `/ranking/${post.slug}`)}>
                                                {post.title}
                                            </Link>
                                        </h3>

                                        {post.criteria && (
                                            <p className="vpr-ranking-desc">{post.criteria}</p>
                                        )}

                                        {contenders.length > 0 && (
                                            <div className="vpr-contenders-preview" aria-label={locale === 'id' ? 'Objek teratas' : 'Top contenders'}>
                                                {contenders.slice(0, 3).map((entry, idx) => (
                                                    <span className="vpr-contender-tag" key={entry.id ?? idx}>
                                                        <span className="vpr-contender-rank">{idx + 1}</span>
                                                        <span className="vpr-contender-name">
                                                            {entry.object?.canonical_name ?? entry.canonical_name ?? `Objek #${idx + 1}`}
                                                        </span>
                                                    </span>
                                                ))}
                                                {contenders.length > 3 && (
                                                    <span className="vpr-contender-more">
                                                        +{contenders.length - 3} {locale === 'id' ? 'lainnya' : 'more'}
                                                    </span>
                                                )}
                                            </div>
                                        )}

                                        <div className="vpr-card-footer">
                                            <div className="vpr-stats-group">
                                                <span className="vpr-stat-item" title={locale === 'id' ? 'Total poin dukungan' : 'Total support points'}>
                                                    <NavIcon name="heart" size={13} />
                                                    <strong>{numberLabel(post.support_points ?? 0, locale)}</strong>
                                                </span>
                                                <span className="vpr-stat-item" title={locale === 'id' ? 'Total komentar' : 'Total comments'}>
                                                    <NavIcon name="comment" size={13} />
                                                    <strong>{numberLabel(post.comments_count ?? 0, locale)}</strong>
                                                </span>
                                            </div>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                    ) : (
                        rankings.data?.length > 0 ? (
                            /* Query/filter produced no results */
                            <div className="vpr-empty">
                                <div className="vpr-empty-icon">
                                    <NavIcon name="search" size={26} />
                                </div>
                                <h3>{locale === 'id' ? 'Tidak Ada Ranking yang Cocok' : 'No Matching Rankings'}</h3>
                                <p>
                                    {locale === 'id'
                                        ? 'Coba gunakan kata kunci pencarian lain atau pilih filter kategori yang berbeda.'
                                        : 'Try adjusting your search terms or selecting a different category filter.'
                                    }
                                </p>
                                <button
                                    type="button"
                                    className="vpr-btn vpr-btn-ghost"
                                    onClick={() => { setSearchQuery(''); setSelectedCategory(''); }}
                                >
                                    {locale === 'id' ? 'Reset Filter' : 'Reset Filters'}
                                </button>
                            </div>
                        ) : (
                            /* User has never published any rankings */
                            <div className="vpr-empty">
                                <div className="vpr-empty-icon">
                                    <NavIcon name="trophy" size={26} />
                                </div>
                                <h3>{locale === 'id' ? 'Belum Ada Ranking' : 'No Rankings Yet'}</h3>
                                <p>
                                    {isOwnProfile
                                        ? (locale === 'id'
                                            ? 'Anda belum mempublikasikan ranking apapun. Mulai buat ranking pertama Anda untuk mengumpulkan dukungan dari komunitas!'
                                            : "You haven't published any rankings yet. Create your first ranking to gather community support!"
                                        )
                                        : (locale === 'id'
                                            ? 'Kreator ini belum mempublikasikan ranking apapun ke publik.'
                                            : "This creator hasn't published any public rankings yet."
                                        )
                                    }
                                </p>
                            </div>
                        )
                    )}

                    {/* Pagination */}
                    <PaginationLinks pagination={rankings} locale={locale} />
                </main>
            </div>

        </Layout>
    );
}
