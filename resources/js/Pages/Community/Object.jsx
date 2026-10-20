import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    api,
    dateLabel,
    dictionary,
    Layout,
    NavIcon,
    numberLabel,
    objectImageSrcSet,
    objectImageUrl,
    openLoginModal,
    PageHead,
    path,
    useCommunityAccess,
} from './Shared';
import ReportDialog from './ReportDialog';
import ShareModal from './ShareModal';
import ObjectKnowledge from './ObjectKnowledge';
import ObjectImageField from './ObjectImageField';

export default function ObjectPage({ locale, categories, leaders, object, stats, rankings, rankingTotal = 0, rankingFilters = {}, summary, articles, myRevisions = [], reviewFeedback }) {
    const t = dictionary(locale);
    const user = usePage().props.auth?.user;
    const { createRanking } = useCommunityAccess();
    const [reporting, setReporting] = useState(false);
    const [message, setMessage] = useState('');
    const [shareOpen, setShareOpen] = useState(false);
    const [imageFormOpen, setImageFormOpen] = useState(false);
    const [imageFile, setImageFile] = useState(null);
    const [imageBusy, setImageBusy] = useState(false);
    const [imageError, setImageError] = useState('');
    const [searchQuery, setSearchQuery] = useState(rankingFilters.search ?? '');
    const sortOrder = rankingFilters.sort ?? 'latest';
    const visibleRankings = rankings?.data ?? [];

    useEffect(() => setSearchQuery(rankingFilters.search ?? ''), [rankingFilters.search]);

    const displayName = object.translations?.find(item => item.locale === locale)?.name ?? object.canonical_name;
    const hasPublishedImage = object.status === 'verified' && Boolean(object.image_path);
    const imageUrl = hasPublishedImage
        ? objectImageUrl(object, 'thumb')
        : object.pending_image_path && object.pending_image_by === user?.id
            ? `/backend/objects/${object.id}/image?pending=1`
            : null;
    const objectUrl = typeof window !== 'undefined' ? window.location.href : '';
    const shareText = locale === 'id'
        ? `Lihat objek ${displayName} di Votmee!`
        : `Check out ${displayName} on Votmee!`;

    const navigateRankings = (search, sort) => {
        const url = new URL(window.location.href);
        const trimmedSearch = search.trim();

        if (trimmedSearch) url.searchParams.set('ranking_q', trimmedSearch);
        else url.searchParams.delete('ranking_q');

        if (sort === 'oldest') url.searchParams.set('ranking_sort', 'oldest');
        else url.searchParams.delete('ranking_sort');

        url.searchParams.delete('rankings_page');
        router.get(url.pathname + url.search, {}, { preserveScroll: true });
    };

    const report = async (reason) => {
        await api('/backend/reports', 'POST', { target_type: 'object', target_id: object.id, reason });
        setReporting(false);
        setMessage(locale === 'id' ? 'Laporan berhasil terkirim.' : 'Report successfully submitted.');
        setTimeout(() => setMessage(''), 4000);
    };

    const proposeImage = async (event) => {
        event.preventDefault();
        if (!imageFile) return;
        setImageBusy(true);
        setImageError('');
        try {
            const payload = new FormData();
            payload.append('image', imageFile);
            await api(`/backend/objects/${object.id}/image`, 'POST', payload);
            setImageFormOpen(false);
            setImageFile(null);
            router.reload({ preserveScroll: true });
        } catch (error) {
            setImageError(error.message);
        } finally {
            setImageBusy(false);
        }
    };

    return (
        <Layout locale={locale} categories={categories} leaders={leaders} active="object">
            <PageHead
                title={`${displayName} - Votmee`}
                locale={locale}
                description={locale === 'id'
                    ? `Lihat detail, statistik dukungan, dan daftar ranking untuk ${displayName} di Votmee.`
                    : `Explore details, support statistics, and community rankings for ${displayName} on Votmee.`
                }
            />

            <div className="vob-container">
                {/* 1. Breadcrumbs Navigation */}
                <nav className="vr-simple-nav" aria-label="Breadcrumb">
                    <Link href={path(locale)} className="vr-back-link">
                        <NavIcon name="arrowLeft" size={13} />
                        <span>{locale === 'id' ? 'Kembali ke Feed' : 'Back to Feed'}</span>
                    </Link>
                    <span className="vr-nav-separator">/</span>
                    <span className="vr-nav-current">{displayName}</span>
                </nav>

                {/* Toast feedback if report sent */}
                {message && (
                    <div className="vr-toast vr-toast-success" role="status" style={{ marginBottom: 20 }}>
                        <span>{message}</span>
                    </div>
                )}

                {/* 2. Spotlight Hero Stage */}
                <header className="vob-spotlight" aria-label={locale === 'id' ? 'Sorotan Objek' : 'Object Spotlight'}>
                    <div className="vob-spotlight-top">
                        <div className="vob-badge-group">
                            <span
                                className={`vob-seal ${object.status === 'verified' ? 'vob-seal-verified' : 'vob-seal-pending'}`}
                                title={object.status === 'verified' ? (locale === 'id' ? 'Objek Terverifikasi' : 'Verified Object') : undefined}
                            >
                                {object.status === 'verified' ? (
                                    <>
                                        <NavIcon name="check" size={10} />
                                        <span>{t.verified}</span>
                                    </>
                                ) : (
                                    <span>{t.pending}</span>
                                )}
                            </span>
                        </div>

                        <div className="vob-hero-actions">
                            <button
                                type="button"
                                onClick={() => setShareOpen(true)}
                                className="vob-btn-share"
                                aria-label={locale === 'id' ? 'Bagikan objek' : 'Share object'}
                            >
                                <NavIcon name="share" size={15} />
                            </button>

                            <button
                                type="button"
                                className="vob-btn-flag-ghost"
                                onClick={() => user ? setReporting(true) : openLoginModal()}
                                aria-label={t.report}
                            >
                                <NavIcon name="shield" size={15} />
                            </button>
                        </div>
                    </div>

                    <div className="vob-spotlight-inner">
                        <div className="vob-spotlight-main">
                            <div className="vob-object-identity">
                                <div className="vob-object-image">
                                    {imageUrl ? <img src={imageUrl} srcSet={hasPublishedImage ? objectImageSrcSet(object, true) : undefined} sizes="112px" alt={displayName} /> : <span aria-hidden="true">{displayName.slice(0, 1).toUpperCase()}</span>}
                                </div>
                                <div className="vob-spotlight-details">
                                <h1 className="vob-title">{displayName}</h1>

                                {object.canonical_name && object.canonical_name !== displayName && (
                                    <p className="vob-canonical-sub">
                                        <span className="vob-label-dim">{locale === 'id' ? 'Nama Resmi: ' : 'Canonical: '}</span>
                                        <strong>{object.canonical_name}</strong>
                                    </p>
                                )}

                                {object.aliases && object.aliases.length > 0 && (
                                    <div className="vob-aliases-bar">
                                        <span className="vob-aliases-lead">{locale === 'id' ? 'Alias / Nama Lain:' : 'Also Known As:'}</span>
                                        <div className="vob-aliases-tags">
                                            {object.aliases.map((item) => (
                                                <span key={item.id ?? item.alias} className="vob-alias-pill">
                                                    {item.alias}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                )}
                                </div>
                            </div>
                        </div>

                        <div className="vob-spotlight-side">
                            <dl className="vob-pulse-metrics">
                                <div className="vob-metric-item">
                                    <dt className="vob-metric-lbl">{t.supporters}</dt>
                                    <dd className="vob-metric-num">{numberLabel(stats?.supporters, locale)}</dd>
                                </div>
                                <div className="vob-metric-item">
                                    <dt className="vob-metric-lbl">{t.points}</dt>
                                    <dd className="vob-metric-num">{numberLabel(stats?.points, locale)}</dd>
                                </div>
                            </dl>

                        </div>
                    </div>
                    {object.status === 'verified' && (
                        <div className="vob-image-contribution">
                            {object.pending_image_path ? <span>{locale === 'id' ? 'Usulan gambar sedang ditinjau' : 'An image suggestion is under review'}</span> : (
                                <button type="button" onClick={() => user ? setImageFormOpen(!imageFormOpen) : openLoginModal()}>
                                    <NavIcon name="camera" size={14} />
                                    {locale === 'id' ? 'Usulkan gambar objek' : 'Suggest an object image'}
                                </button>
                            )}
                        </div>
                    )}
                    {imageFormOpen && !object.pending_image_path && (
                        <form className="vob-image-proposal" onSubmit={proposeImage}>
                            <ObjectImageField file={imageFile} onChange={setImageFile} locale={locale} currentUrl={imageUrl} compact />
                            {imageError && <p role="alert" className="vob-form-error">{imageError}</p>}
                            <button type="submit" disabled={imageBusy || !imageFile}>{imageBusy ? (locale === 'id' ? 'Mengirim...' : 'Submitting...') : (locale === 'id' ? 'Kirim untuk ditinjau' : 'Submit for review')}</button>
                        </form>
                    )}
                </header>

                <ObjectKnowledge object={object} summary={summary} articles={articles} myRevisions={myRevisions} reviewFeedback={reviewFeedback} locale={locale} />

                {/* 3. Two-Column Dashboard Layout */}
                <div className="vob-layout-grid">
                    {/* Left Main Column: Stream of Community Rankings */}
                    <div className="vob-panel">
                        <div className="vob-panel-header">
                            <div>
                                <div className="vob-panel-title-row">
                                    <h2 className="vob-panel-title">{t.objectRankings}</h2>
                                </div>
                                <p className="vob-panel-subtitle">
                                    {locale === 'id'
                                        ? `Daftar ranking komunitas yang menyertakan ${displayName}`
                                        : `Community ranking lists featuring ${displayName}`}
                                </p>
                            </div>

                            {(rankingTotal > 0 || rankingFilters.search) && (
                                <div className="vob-panel-tools">
                                    <div className="vob-sort-control" role="group" aria-label={locale === 'id' ? 'Urutan ranking' : 'Ranking order'}>
                                        <button
                                            type="button"
                                            className={sortOrder === 'latest' ? 'active' : ''}
                                            aria-pressed={sortOrder === 'latest'}
                                            onClick={() => navigateRankings(searchQuery, 'latest')}
                                        >
                                            {locale === 'id' ? 'Terbaru' : 'Newest'}
                                        </button>
                                        <button
                                            type="button"
                                            className={sortOrder === 'oldest' ? 'active' : ''}
                                            aria-pressed={sortOrder === 'oldest'}
                                            onClick={() => navigateRankings(searchQuery, 'oldest')}
                                        >
                                            {locale === 'id' ? 'Terlama' : 'Oldest'}
                                        </button>
                                    </div>
                                    <form className="vob-search-filter" role="search" onSubmit={(event) => { event.preventDefault(); navigateRankings(searchQuery, sortOrder); }}>
                                        <span className="vob-filter-leading" aria-hidden="true"><NavIcon name="search" size={15} /></span>
                                        <input
                                            type="text"
                                            value={searchQuery}
                                            onChange={(event) => setSearchQuery(event.target.value)}
                                            maxLength={100}
                                            placeholder={locale === 'id' ? 'Cari ranking...' : 'Search rankings...'}
                                            aria-label={locale === 'id' ? 'Cari ranking dalam objek ini' : 'Search rankings for this object'}
                                        />
                                        {searchQuery && (
                                            <button
                                                type="button"
                                                onClick={() => { setSearchQuery(''); navigateRankings('', sortOrder); }}
                                                className="vob-filter-clear"
                                                aria-label={locale === 'id' ? 'Hapus pencarian ranking' : 'Clear ranking search'}
                                            >
                                                <span aria-hidden="true">×</span>
                                            </button>
                                        )}
                                        <button type="submit" className="vob-filter-submit">
                                            {locale === 'id' ? 'Cari' : 'Search'}
                                        </button>
                                    </form>
                                </div>
                            )}
                        </div>

                        {visibleRankings.length > 0 ? (
                            <div className="vob-stream-list">
                                {visibleRankings.map(post => (
                                    <article className="vob-stream-card" key={post.id}>
                                        <div className="vob-stream-top">
                                            <div className="vob-stream-cat-wrap">
                                                <span className="vob-stream-category">
                                                    {post.category?.name ?? t.rankings}
                                                </span>
                                            </div>
                                            <span className="vob-stream-date">
                                                <NavIcon name="calendar" size={11} />
                                                <span>{dateLabel(post.published_at, locale)}</span>
                                            </span>
                                        </div>

                                        <h3 className="vob-stream-title">
                                            <Link href={path(locale, `/ranking/${post.slug}`)}>
                                                {post.title}
                                            </Link>
                                        </h3>

                                        <div className="vob-stream-footer">
                                            <div className="vob-creator-pill">
                                                <div className="vob-creator-icon" aria-hidden="true">
                                                    {post.creator?.name ? post.creator.name.slice(0, 1).toUpperCase() : 'U'}
                                                </div>
                                                <span className="vob-creator-by">{t.createdBy}</span>
                                                <Link
                                                    href={path(locale, `/profile/${post.creator?.username}`)}
                                                    className="vob-creator-link"
                                                >
                                                    {post.creator?.name}
                                                </Link>
                                            </div>

                                            <Link
                                                href={path(locale, `/ranking/${post.slug}`)}
                                                className="vob-stream-cta"
                                                aria-label={`${object.status === 'verified'
                                                    ? (locale === 'id' ? 'Buka ranking dan beri dukungan' : 'Open ranking and support')
                                                    : (locale === 'id' ? 'Buka ranking' : 'Open ranking')}: ${post.title}`}
                                            >
                                                <span>{object.status === 'verified'
                                                    ? (locale === 'id' ? 'Buka & Beri Dukungan' : 'View & Support')
                                                    : (locale === 'id' ? 'Buka Ranking' : 'View Ranking')}</span>
                                                <NavIcon name="arrowRight" size={12} />
                                            </Link>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        ) : (
                            <div className="vob-empty-box">
                                <div className="vob-empty-icon-wrap" aria-hidden="true">
                                    <NavIcon name="trophy" size={26} />
                                </div>
                                <h3>{t.noRankings}</h3>
                                <p>
                                    {rankingFilters.search
                                        ? (locale === 'id' ? 'Tidak ada ranking yang cocok dengan kata kunci pencarian.' : 'No rankings matched your search term.')
                                        : (locale === 'id' ? 'Objek ini belum dimasukkan ke dalam daftar ranking manapun.' : 'This object has not been featured in any ranking list yet.')}
                                </p>
                                {createRanking && !rankingFilters.search && (
                                    <div style={{ marginTop: 14 }}>
                                        <Link href={path(locale, '/create')} className="vpr-btn vpr-btn-primary" style={{ display: 'inline-flex' }}>
                                            <NavIcon name="plus" size={14} />
                                            <span>{locale === 'id' ? 'Buat Ranking Baru' : 'Create New Ranking'}</span>
                                        </Link>
                                    </div>
                                )}
                            </div>
                        )}

                        {rankings?.last_page > 1 && (
                            <nav aria-label={locale === 'id' ? 'Halaman ranking objek' : 'Object ranking pages'} className="vob-article-pages">
                                {rankings.prev_page_url && (
                                    <Link href={rankings.prev_page_url} preserveScroll className="vob-page-nav-btn">
                                        ← {locale === 'id' ? 'Sebelumnya' : 'Previous'}
                                    </Link>
                                )}
                                <span className="vob-page-nav-info">{rankings.current_page} / {rankings.last_page}</span>
                                {rankings.next_page_url && (
                                    <Link href={rankings.next_page_url} preserveScroll className="vob-page-nav-btn">
                                        {locale === 'id' ? 'Berikutnya' : 'Next'} →
                                    </Link>
                                )}
                            </nav>
                        )}
                    </div>

                    {/* Right Sidebar: Entity Information & Interactive Widgets */}
                    <aside className="vob-side-col">
                        {/* Widget 1: Entity Spec Matrix */}
                        <div className="vob-widget-card">
                            <div className="vob-widget-head">
                                <h4>{locale === 'id' ? 'Informasi Objek' : 'Object Details'}</h4>
                            </div>

                            <div className="vob-widget-rows">
                                <div className="vob-spec-row">
                                    <span className="vob-spec-key">{locale === 'id' ? 'Status' : 'Status'}</span>
                                    <span className={`vob-spec-badge ${object.status === 'verified' ? 'is-verified' : 'is-pending'}`}>
                                        {object.status === 'verified'
                                            ? (locale === 'id' ? '✓ Terverifikasi' : '✓ Verified')
                                            : (locale === 'id' ? 'Menunggu' : 'Pending')}
                                    </span>
                                </div>

                                <div className="vob-spec-row">
                                    <span className="vob-spec-key">{locale === 'id' ? 'Tampil di Ranking' : 'Appears in'}</span>
                                    <span className="vob-spec-val">
                                        {numberLabel(rankingTotal, locale)} {locale === 'id' ? 'Daftar' : 'Lists'}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Widget 2: How Support Voting Works */}
                        <div className="vob-widget-card vob-guide-widget">
                            <div className="vob-widget-head">
                                <h4>{locale === 'id' ? 'Cara Mendukung Objek' : 'How to Support'}</h4>
                            </div>

                            <div className="vob-guide-steps">
                                <div className="vob-guide-step">
                                    <span className="vob-step-num">1</span>
                                    <p>{locale === 'id' ? 'Pilih salah satu daftar ranking di samping yang memuat objek ini.' : 'Select any ranking list from the left that features this object.'}</p>
                                </div>
                                <div className="vob-guide-step">
                                    <span className="vob-step-num">2</span>
                                    <p>{locale === 'id' ? 'Buka ranking dan temukan objek ini di dalam kandidat.' : 'Open the ranking and locate this candidate in the list.'}</p>
                                </div>
                                <div className="vob-guide-step">
                                    <span className="vob-step-num">3</span>
                                    <p>{locale === 'id' ? 'Gunakan poin atau vote Anda untuk menaikkan peringkat objek ini ke posisi teratas!' : 'Cast your votes or points to push this object up to the top rank!'}</p>
                                </div>
                            </div>
                        </div>

                        {/* Widget 3: Create Ranking CTA Banner */}
                        {createRanking && (
                            <div className="vob-widget-card vob-cta-widget">
                                <div className="vob-cta-content">
                                    <h4>{locale === 'id' ? 'Punya Ide Ranking Baru?' : 'Have a Ranking Idea?'}</h4>
                                    <p>
                                        {locale === 'id'
                                            ? `Buat daftar ranking baru dan sertakan ${displayName} bersama kandidat menarik lainnya!`
                                            : `Create a new community ranking list and feature ${displayName} along with other candidates!`}
                                    </p>
                                    <Link href={path(locale, '/create')} className="vob-cta-btn">
                                        <NavIcon name="plus" size={13} />
                                        <span>{locale === 'id' ? 'Buat Ranking' : 'Create Ranking'}</span>
                                    </Link>
                                </div>
                            </div>
                        )}
                    </aside>
                </div>
            </div>

            {/* Reusable Share Modal */}
            <ShareModal
                isOpen={shareOpen}
                onClose={() => setShareOpen(false)}
                locale={locale}
                title={locale === 'id' ? 'Bagikan Objek' : 'Share Object'}
                description={locale === 'id' ? 'Bagikan objek ini ke teman atau media sosial' : 'Share this object with friends or social media'}
                shareUrl={objectUrl}
                shareText={shareText}
                copyLabel={locale === 'id' ? 'Salin Tautan Objek' : 'Copy Object Link'}
                preview={{
                    avatar: displayName.slice(0, 1).toUpperCase(),
                    title: displayName,
                    subtitle: `${t.objects} · ${numberLabel(stats?.supporters, locale)} ${t.supporters}`,
                }}
            />

            {/* Reporting Dialog */}
            {reporting && (
                <ReportDialog
                    locale={locale}
                    targetType="object"
                    onClose={() => setReporting(false)}
                    onSubmit={report}
                />
            )}
        </Layout>
    );
}
