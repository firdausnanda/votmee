import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@headlessui/react';
import { useEffect, useState, useMemo, useRef } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import SelectBox from '../../Components/SelectBox';
import { ALLOWED_TYPES, MAX_BYTES, optimizeImage } from './ObjectImageField';
import { api, categoryHashtag, dateLabel, dictionary, Layout, NavIcon, numberLabel, PageHead, PaginationLinks, path, rankingPath } from './Shared';

function formatMemberSince(isoDate, locale) {
    if (!isoDate) return '—';
    const date = new Date(isoDate);
    if (Number.isNaN(date.getTime())) return '—';
    return new Intl.DateTimeFormat(locale === 'id' ? 'id-ID' : 'en-US', {
        month: 'long',
        year: 'numeric',
    }).format(date);
}

const prefersReducedMotion = () => typeof window !== 'undefined'
    && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/**
 * Animate a number from its previous value to the target with an ease-out curve.
 */
function useCountUp(target, duration = 1100) {
    const [value, setValue] = useState(0);
    const valueRef = useRef(value);

    useEffect(() => {
        if (prefersReducedMotion()) {
            valueRef.current = target;
            setValue(target);
            return undefined;
        }

        const from = valueRef.current;
        const start = performance.now();
        let frame;
        const tick = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            const next = Math.round(from + (target - from) * (1 - Math.pow(1 - progress, 3)));
            valueRef.current = next;
            setValue(next);
            if (progress < 1) frame = requestAnimationFrame(tick);
        };
        frame = requestAnimationFrame(tick);

        return () => cancelAnimationFrame(frame);
    }, [target, duration]);

    return value;
}

/**
 * Reveal `[data-reveal]` children with a staggered transition as they scroll into view.
 */
function useRevealOnScroll(dependency) {
    const containerRef = useRef(null);

    useEffect(() => {
        const container = containerRef.current;
        if (!container || typeof IntersectionObserver === 'undefined' || prefersReducedMotion()) return undefined;

        container.classList.add('is-reveal-ready');
        const observer = new IntersectionObserver((entries) => {
            entries.filter(entry => entry.isIntersecting).forEach((entry, index) => {
                entry.target.style.setProperty('--vpr-reveal-delay', `${Math.min(index, 5) * 70}ms`);
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -40px 0px', threshold: 0.12 });

        container.querySelectorAll('[data-reveal]:not(.is-revealed)').forEach(item => observer.observe(item));

        return () => observer.disconnect();
    }, [dependency]);

    return containerRef;
}

function AnimatedNumber({ value, locale }) {
    const animated = useCountUp(Number(value || 0));

    return <>
        <span aria-hidden="true">{numberLabel(animated, locale)}</span>
        <span className="vm-sr-only">{numberLabel(value, locale)}</span>
    </>;
}

const PROFILE_IMAGE_EDGES = { avatar: 1000, cover: 2400 };

const withSize = (url, size) => `${url}&size=${size}`;

/**
 * Upload, replace or remove the signed-in member's avatar or cover image.
 */
function ProfileImageEditor({ type, hasImage, locale, buttonClassName, label, showText = false, onError }) {
    const isId = locale === 'id';
    const inputRef = useRef(null);
    const wrapRef = useRef(null);
    const [menuOpen, setMenuOpen] = useState(false);
    const [busy, setBusy] = useState(false);
    const [confirmingRemoval, setConfirmingRemoval] = useState(false);

    useEffect(() => {
        if (!menuOpen) return undefined;

        const close = (event) => {
            if (event.type === 'keydown' ? event.key === 'Escape' : !wrapRef.current?.contains(event.target)) setMenuOpen(false);
        };
        document.addEventListener('mousedown', close);
        document.addEventListener('keydown', close);

        return () => {
            document.removeEventListener('mousedown', close);
            document.removeEventListener('keydown', close);
        };
    }, [menuOpen]);

    const run = async (request) => {
        setMenuOpen(false);
        setBusy(true);
        onError('');
        try {
            await request();
            router.reload({ only: ['profile', 'auth'], preserveScroll: true });
        } catch (error) {
            onError(error.message);
        } finally {
            setBusy(false);
        }
    };

    const upload = (event) => {
        const selected = event.target.files?.[0];
        event.target.value = '';
        if (!selected) return;

        if (!ALLOWED_TYPES.includes(selected.type)) {
            onError(isId ? 'Format harus JPG, PNG, atau WebP.' : 'The image must be a JPG, PNG, or WebP file.');
            return;
        }

        run(async () => {
            const optimized = await optimizeImage(selected, PROFILE_IMAGE_EDGES[type]).catch(() => selected);
            if (optimized.size > MAX_BYTES) {
                throw new Error(isId ? 'Ukuran gambar masih di atas 4 MB. Pilih gambar yang lebih kecil.' : 'The image is still larger than 4 MB. Choose a smaller image.');
            }
            const payload = new FormData();
            payload.append('image', optimized);
            await api(`/backend/profile/${type}`, 'POST', payload);
        });
    };

    const removeImage = async () => {
        await run(() => api(`/backend/profile/${type}`, 'DELETE'));
        setConfirmingRemoval(false);
    };

    const removalCopy = type === 'avatar'
        ? {
            title: isId ? 'Hapus foto profil?' : 'Remove profile photo?',
            body: isId ? 'Foto profil Anda akan dihapus permanen dan diganti dengan inisial nama.' : 'Your profile photo will be permanently removed and replaced with your initial.',
        }
        : {
            title: isId ? 'Hapus sampul?' : 'Remove cover image?',
            body: isId ? 'Gambar sampul Anda akan dihapus permanen dan diganti dengan sampul bawaan.' : 'Your cover image will be permanently removed and replaced with the default cover.',
        };

    return <div className={`vpr-image-editor vpr-image-editor-${type}`} ref={wrapRef}>
        <input type="file" ref={inputRef} accept={ALLOWED_TYPES.join(',')} hidden onChange={upload} />
        <button
            type="button"
            className={buttonClassName}
            onClick={() => (hasImage ? setMenuOpen(open => !open) : inputRef.current?.click())}
            disabled={busy}
            aria-haspopup={hasImage ? 'menu' : undefined}
            aria-expanded={hasImage ? menuOpen : undefined}
            aria-label={label}
            title={label}
        >
            <NavIcon name="camera" size={showText ? 9 : 14} />
            {showText && <span>{busy ? (confirmingRemoval ? (isId ? 'Menghapus…' : 'Removing…') : (isId ? 'Mengunggah…' : 'Uploading…')) : label}</span>}
        </button>
        {menuOpen && <div className="vpr-image-menu" role="menu">
            <button type="button" role="menuitem" onClick={() => { setMenuOpen(false); inputRef.current?.click(); }}>
                <NavIcon name="camera" size={13} />
                {isId ? 'Ganti gambar' : 'Change image'}
            </button>
            <button type="button" role="menuitem" className="is-danger" onClick={() => { setMenuOpen(false); setConfirmingRemoval(true); }}>
                <NavIcon name="trash" size={13} />
                {isId ? 'Hapus gambar' : 'Remove image'}
            </button>
        </div>}
        <Dialog open={confirmingRemoval} onClose={() => { if (!busy) setConfirmingRemoval(false); }} className="vt-report-overlay">
            <DialogBackdrop transition className="vt-report-backdrop vpr-confirm-backdrop" />
            <div className="vt-report-positioner">
                <DialogPanel transition className="vt-report-dialog vpr-confirm-dialog">
                    <div className="vt-report-header">
                        <div>
                            <DialogTitle className="vt-report-title">{removalCopy.title}</DialogTitle>
                            <p className="vt-report-intro">{removalCopy.body}</p>
                        </div>
                    </div>
                    <div className="vt-report-footer">
                        <div className="vt-report-actions">
                            <button type="button" className="vt-report-cancel" onClick={() => setConfirmingRemoval(false)} disabled={busy}>
                                {isId ? 'Batal' : 'Cancel'}
                            </button>
                            <button type="button" className="vt-report-submit" onClick={removeImage} disabled={busy}>
                                {busy ? (isId ? 'Menghapus…' : 'Removing…') : (isId ? 'Hapus' : 'Remove')}
                            </button>
                        </div>
                    </div>
                </DialogPanel>
            </div>
        </Dialog>
    </div>;
}

export default function Profile({ locale, categories = [], leaders = [], profile, rankings }) {
    const t = dictionary(locale);
    const currentUser = usePage().props.auth?.user;
    const isOwnProfile = currentUser && currentUser.id === profile.id;

    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('');
    const [imageError, setImageError] = useState('');

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
    const gridRef = useRevealOnScroll(filteredRankings);

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
                    <div className={`vpr-cover ${profile.cover_url ? 'has-image' : ''}`}>
                        {profile.cover_url && (
                            <img
                                className="vpr-cover-img"
                                src={profile.cover_url}
                                srcSet={`${withSize(profile.cover_url, 'small')} 800w, ${profile.cover_url} 1600w`}
                                sizes="(max-width: 1000px) 100vw, 960px"
                                alt=""
                            />
                        )}
                        {isOwnProfile && (
                            <ProfileImageEditor
                                type="cover"
                                hasImage={Boolean(profile.cover_url)}
                                locale={locale}
                                buttonClassName="vpr-cover-btn"
                                label={locale === 'id' ? 'Edit Sampul' : 'Edit Cover'}
                                showText
                                onError={setImageError}
                            />
                        )}
                    </div>

                    <div className="vpr-card">
                        <div className="vpr-card-main">
                            <div className="vpr-avatar-wrap">
                                <div className="vpr-avatar" aria-hidden="true">
                                    {profile.avatar_url ? (
                                        <img
                                            src={withSize(profile.avatar_url, 'small')}
                                            srcSet={`${withSize(profile.avatar_url, 'small')} 96w, ${profile.avatar_url} 400w`}
                                            sizes="84px"
                                            alt=""
                                        />
                                    ) : (profile.name ? profile.name.slice(0, 1).toUpperCase() : 'U')}
                                </div>
                                {isOwnProfile && (
                                    <ProfileImageEditor
                                        type="avatar"
                                        hasImage={Boolean(profile.avatar_url)}
                                        locale={locale}
                                        buttonClassName="vpr-avatar-edit-btn"
                                        label={locale === 'id' ? 'Edit foto profil' : 'Edit profile photo'}
                                        onError={setImageError}
                                    />
                                )}
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
                                {imageError && <p className="vpr-image-error" role="alert">{imageError}</p>}
                            </div>
                            <div className="vpr-inline-stats" aria-label={locale === 'id' ? 'Statistik Poin' : 'Point Statistics'}>
                                <div className="vpr-inline-stat">
                                    <NavIcon name="heart" size={14} />
                                    <strong><AnimatedNumber value={supportPointsReceived} locale={locale} /></strong>
                                    <span>{locale === 'id' ? 'Total Poin Diterima' : 'Total Points Received'}</span>
                                </div>
                                <div className="vpr-inline-stat">
                                    <NavIcon name="wallet" size={14} />
                                    <strong><AnimatedNumber value={walletBalance} locale={locale} /></strong>
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
                        <div className="vpr-grid" ref={gridRef}>
                            {filteredRankings.map(post => {
                                const contenders = post.entries || [];
                                return (
                                    <article className="vpr-ranking-card" key={post.id} data-reveal>
                                        <div className="vpr-card-header">
                                            <span className={`vm-category-tag vm-tag-${post.category?.slug ?? 'default'}`}>
                                                {categoryHashtag(post.category) || (locale === 'id' ? 'Umum' : 'General')}
                                            </span>
                                            <span className="vpr-date">{dateLabel(post.published_at, locale)}</span>
                                        </div>

                                        <h3 className="vpr-ranking-title">
                                            <Link href={rankingPath(locale, post)}>
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
