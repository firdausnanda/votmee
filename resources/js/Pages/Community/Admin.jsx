import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import ContentBody from './ContentBody';
import { api, dictionary, PaginationLinks } from './Shared';

const CATEGORY_FILTER_THRESHOLD = 12;

export default function Admin({
    locale,
    categories = [],
    leaders = [],
    objects,
    contentQueue,
    reports,
    mergedObjects = [],
    isAdmin = false,
    stats = {},
}) {
    const t = dictionary(locale);
    const isId = locale === 'id';

    const [activeTab, setActiveTab] = useState('overview');
    const [reason, setReason] = useState('');
    const [categoryName, setCategoryName] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('');
    const [brandName, setBrandName] = useState('');
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState('');
    const [messageType, setMessageType] = useState('info');

    const [sourceQuery, setSourceQuery] = useState('');
    const [targetQuery, setTargetQuery] = useState('');
    const [sourceOptions, setSourceOptions] = useState([]);
    const [targetOptions, setTargetOptions] = useState([]);
    const [source, setSource] = useState(null);
    const [target, setTarget] = useState(null);
    const [alias, setAlias] = useState('');
    const [translationName, setTranslationName] = useState('');
    const [translationLocale, setTranslationLocale] = useState('en');
    const [audits, setAudits] = useState([]);

    const normalizedCategoryFilter = categoryFilter.trim().toLowerCase();
    const filteredCategories = normalizedCategoryFilter
        ? categories.filter((category) => category.name.toLowerCase().includes(normalizedCategoryFilter) || category.slug.includes(normalizedCategoryFilter))
        : categories;

    const computedStats = {
        pendingObjects: stats.pendingObjects ?? objects?.total ?? 0,
        pendingContent: contentQueue?.total ?? 0,
        openReports: stats.openReports ?? reports?.total ?? 0,
        verifiedObjects: stats.verifiedObjects ?? 0,
        totalCategories: stats.totalCategories ?? categories.length,
    };

    const sections = {
        overview: {
            title: isId ? 'Ringkasan' : 'Overview',
            description: isId ? 'Pekerjaan moderasi dan kondisi data saat ini.' : 'Moderation work and current data at a glance.',
        },
        objects: {
            title: isId ? 'Antrean objek' : 'Object queue',
            description: isId ? 'Tinjau objek yang diajukan pengguna.' : 'Review objects submitted by users.',
        },
        content: {
            title: isId ? 'Antrean tulisan' : 'Content queue',
            description: isId ? 'Tinjau ringkasan dan artikel dari pengguna.' : 'Review user summaries and articles.',
        },
        reports: {
            title: isId ? 'Laporan konten' : 'Content reports',
            description: isId ? 'Periksa dan selesaikan laporan komunitas.' : 'Review and resolve community reports.',
        },
        master: {
            title: isId ? 'Master objek' : 'Master objects',
            description: isId ? 'Kelola alias, terjemahan, dan objek duplikat.' : 'Manage aliases, translations, and duplicate objects.',
        },
        categories: {
            title: isId ? 'Kategori & merek' : 'Categories & brands',
            description: isId ? 'Kelola pengelompokan objek.' : 'Manage object organization.',
        },
        audit: {
            title: isId ? 'Log audit' : 'Audit log',
            description: isId ? 'Lihat riwayat tindakan administratif.' : 'Review administrative actions.',
        },
    };

    useEffect(() => {
        if (!isAdmin) return;
        api('/admin/backend/audit-logs')
            .then((result) => setAudits(result.data.slice(0, 15)))
            .catch(() => setAudits([]));
    }, [isAdmin]);

    useEffect(() => {
        if (sourceQuery.trim().length < 2) {
            setSourceOptions([]);
            return;
        }
        const timer = window.setTimeout(
            () =>
                api(`/backend/objects?q=${encodeURIComponent(sourceQuery.trim())}`)
                    .then(setSourceOptions)
                    .catch(() => setSourceOptions([])),
            250
        );
        return () => window.clearTimeout(timer);
    }, [sourceQuery]);

    useEffect(() => {
        if (!isAdmin) return;
        if (targetQuery.trim().length < 2) {
            setTargetOptions([]);
            return;
        }
        const timer = window.setTimeout(
            () =>
                api(`/backend/objects?q=${encodeURIComponent(targetQuery.trim())}`)
                    .then(setTargetOptions)
                    .catch(() => setTargetOptions([])),
            250
        );
        return () => window.clearTimeout(timer);
    }, [targetQuery, isAdmin]);

    const runMasterAction = async (url, method, payload) => {
        if (reason.trim().length < 5) {
            setMessage(isId ? 'Alasan tindakan minimal 5 karakter.' : 'Reason must be at least 5 characters.');
            setMessageType('error');
            return;
        }
        setBusy(true);
        setMessage('');
        try {
            await api(url, method, { ...payload, reason });
            if (url.endsWith('/merge') && source) setSource({ ...source, status: 'merged' });
            if (url.endsWith('/undo-merge')) setSource(null);
            setMessage(isId ? 'Perubahan master objek berhasil disimpan.' : 'Master object changes saved.');
            setMessageType('success');
            setAlias('');
            setTranslationName('');
            setReason('');
            router.reload();
        } catch (error) {
            setMessage(error.message);
            setMessageType('error');
        } finally {
            setBusy(false);
        }
    };

    const act = async (url, status) => {
        if (reason.trim().length < 5) {
            setMessage(isId ? 'Alasan tindakan minimal 5 karakter.' : 'Reason must be at least 5 characters.');
            setMessageType('error');
            return;
        }
        setBusy(true);
        setMessage('');
        try {
            await api(url, 'PATCH', { status, reason });
            setMessage(isId ? 'Status objek berhasil diperbarui.' : 'Object status updated successfully.');
            setMessageType('success');
            setReason('');
            router.reload();
        } catch (error) {
            setMessage(error.message);
            setMessageType('error');
        } finally {
            setBusy(false);
        }
    };

    const reviewObjectImage = async (object, status) => {
        if (reason.trim().length < 5) {
            setMessage(isId ? 'Alasan tindakan minimal 5 karakter.' : 'Reason must be at least 5 characters.');
            setMessageType('error');
            return;
        }
        setBusy(true);
        setMessage('');
        try {
            await api(`/admin/backend/objects/${object.id}/image`, 'PATCH', { status, reason });
            setReason('');
            setMessage(isId ? 'Gambar objek berhasil ditinjau.' : 'Object image reviewed.');
            setMessageType('success');
            router.reload();
        } catch (error) {
            setMessage(error.message);
            setMessageType('error');
        } finally {
            setBusy(false);
        }
    };

    const reviewContent = async (revision, status) => {
        if (reason.trim().length < 5) {
            setMessage(isId ? 'Alasan tindakan minimal 5 karakter.' : 'Reason must be at least 5 characters.');
            setMessageType('error');
            return;
        }
        setBusy(true);
        try {
            await api(`/admin/backend/content-revisions/${revision.id}`, 'PATCH', { status, reason });
            setReason('');
            setMessage(isId ? 'Tulisan berhasil ditinjau.' : 'Content reviewed.');
            setMessageType('success');
            router.reload();
        } catch (error) {
            setMessage(error.message);
            setMessageType('error');
        } finally {
            setBusy(false);
        }
    };

    const hideTarget = async (report) => {
        if (reason.trim().length < 5) {
            setMessage(isId ? 'Alasan tindakan minimal 5 karakter.' : 'Reason must be at least 5 characters.');
            setMessageType('error');
            return;
        }
        const endpoint =
            report.target_type === 'ranking_post'
                ? `/admin/backend/rankings/${report.target_id}/hide`
                : report.target_type === 'object_article'
                ? `/admin/backend/articles/${report.target_id}/hide`
                : report.target_type === 'comment'
                ? `/admin/backend/comments/${report.target_id}/hide`
                : report.target_type === 'message'
                ? `/admin/backend/messages/${report.target_id}/hide`
                : `/admin/backend/objects/${report.target_id}`;

        setBusy(true);
        setMessage('');
        try {
            await api(endpoint, 'PATCH', report.target_type === 'object' ? { status: 'rejected', reason } : { reason });
            await api(`/admin/backend/reports/${report.id}`, 'PATCH', { status: 'resolved', reason });
            setMessage(isId ? 'Target konten disembunyikan dan laporan diselesaikan.' : 'Target content hidden and report resolved.');
            setMessageType('success');
            setReason('');
            router.reload();
        } catch (error) {
            setMessage(error.message);
            setMessageType('error');
        } finally {
            setBusy(false);
        }
    };

    const resolveReport = async (report, status) => {
        if (reason.trim().length < 5) {
            setMessage(isId ? 'Alasan tindakan minimal 5 karakter.' : 'Reason must be at least 5 characters.');
            setMessageType('error');
            return;
        }
        setBusy(true);
        setMessage('');
        try {
            await api(`/admin/backend/reports/${report.id}`, 'PATCH', { status, reason });
            setMessage(isId ? `Laporan berhasil di-${status === 'resolved' ? 'selesaikan' : 'abaikan'}.` : `Report marked as ${status}.`);
            setMessageType('success');
            setReason('');
            router.reload();
        } catch (error) {
            setMessage(error.message);
            setMessageType('error');
        } finally {
            setBusy(false);
        }
    };

    const createCategory = async (event) => {
        event.preventDefault();
        setBusy(true);
        setMessage('');
        try {
            await api('/admin/backend/categories', 'POST', { name: categoryName });
            setCategoryName('');
            setMessage(isId ? 'Kategori baru berhasil ditambahkan.' : 'Category added successfully.');
            setMessageType('success');
            router.reload();
        } catch (error) {
            setMessage(error.message);
            setMessageType('error');
        } finally {
            setBusy(false);
        }
    };

    const createBrand = async (event) => {
        event.preventDefault();
        setBusy(true);
        setMessage('');
        try {
            await api('/admin/backend/brands', 'POST', { name: brandName });
            setBrandName('');
            setMessage(isId ? 'Merek baru berhasil ditambahkan.' : 'Brand added successfully.');
            setMessageType('success');
        } catch (error) {
            setMessage(error.message);
            setMessageType('error');
        } finally {
            setBusy(false);
        }
    };

    const reasonTemplates = isId
        ? [
              'Memenuhi kriteria verifikasi master objek.',
              'Informasi kurang lengkap / perlu perbaikan nama.',
              'Data duplikat dengan master objek yang ada.',
              'Melanggar pedoman konten komunitas Votmee.',
          ]
        : [
              'Meets master object verification criteria.',
              'Incomplete info / needs name clarification.',
              'Duplicate with existing verified master object.',
              'Violates Votmee community content guidelines.',
          ];

    return (
        <AdminLayout
            locale={locale}
            title={isId ? 'Panel Admin' : 'Admin Panel'}
            activeTab={activeTab}
            onTabChange={setActiveTab}
            stats={computedStats}
            isAdmin={isAdmin}
        >
            <div className="vap-container">
                <div className="vap-page-header">
                    <div>
                        <h1 className="vap-page-title">{sections[activeTab].title}</h1>
                        <p className="vap-page-subtitle">{sections[activeTab].description}</p>
                    </div>
                </div>

                {activeTab === 'overview' && <div className="vap-stats-grid">
                    <button type="button" className="vap-stat-card" onClick={() => setActiveTab('objects')}>
                        <div className="vap-stat-content">
                            <span className="vap-stat-number">{computedStats.pendingObjects}</span>
                            <span className="vap-stat-title">{isId ? 'Objek perlu ditinjau' : 'Objects to review'}</span>
                        </div>
                    </button>

                    <button type="button" className="vap-stat-card" onClick={() => setActiveTab('reports')}>
                        <div className="vap-stat-content">
                            <span className="vap-stat-number">{computedStats.openReports}</span>
                            <span className="vap-stat-title">{isId ? 'Laporan Terbuka' : 'Open Reports'}</span>
                        </div>
                    </button>

                    <div className="vap-stat-card">
                        <div className="vap-stat-content">
                            <span className="vap-stat-number">{computedStats.verifiedObjects}</span>
                            <span className="vap-stat-title">{isId ? 'Objek terverifikasi' : 'Verified objects'}</span>
                        </div>
                    </div>

                    <div className="vap-stat-card">
                        <div className="vap-stat-content">
                            <span className="vap-stat-number">{computedStats.totalCategories}</span>
                            <span className="vap-stat-title">{isId ? 'Kategori aktif' : 'Active categories'}</span>
                        </div>
                    </div>
                </div>}

                {['objects', 'content', 'reports', 'master'].includes(activeTab) && <div className="vap-reason-card">
                    <div className="vap-reason-header">
                        <div className="vap-reason-title-row">
                            <span className="vap-reason-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 20h9" /><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
                            </span>
                            <label htmlFor="moderation-reason">{isId ? 'Alasan tindakan' : 'Action reason'}</label>
                            <span className="vap-reason-hint">
                                ({isId ? 'Wajib minimal 5 karakter sebelum klik aksi' : 'Min 5 characters required for actions'})
                            </span>
                        </div>
                        <span className={`vap-char-count ${reason.trim().length >= 5 ? 'valid' : ''}`}>
                            {reason.trim().length} / 2000
                            {reason.trim().length >= 5 && ' ✓'}
                        </span>
                    </div>

                    <textarea
                        id="moderation-reason"
                        className="vap-reason-textarea"
                        minLength={5}
                        maxLength={2000}
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        placeholder={
                            isId
                                ? 'Ketik alasan verifikasi, penolakan, merge, atau resolusi laporan di sini...'
                                : 'Type decision reason for review, rejection, merge, or resolution...'
                        }
                    />

                    <details className="vap-reason-presets">
                        <summary>{isId ? 'Pilih alasan yang tersedia' : 'Choose a suggested reason'}</summary>
                        <div className="vap-reason-templates">
                            {reasonTemplates.map((template, idx) => (
                                <button
                                    key={idx}
                                    type="button"
                                    className="vap-template-btn"
                                    onClick={() => setReason(template)}
                                >
                                    {template}
                                </button>
                            ))}
                        </div>
                    </details>
                </div>}

                {/* Alert Notification */}
                {message && (
                    <div className={`vap-alert vap-alert-${messageType}`} role="alert">
                        <span className="vap-alert-icon">
                            {messageType === 'success' ? '✓' : messageType === 'error' ? '⚠' : 'ℹ'}
                        </span>
                        <span className="vap-alert-text">{message}</span>
                        <button
                            type="button"
                            className="vap-alert-close"
                            onClick={() => setMessage('')}
                            aria-label={isId ? 'Tutup pesan' : 'Dismiss message'}
                        >
                            ✕
                        </button>
                    </div>
                )}

                {/* TAB 1: OVERVIEW */}
                {activeTab === 'overview' && (
                    <div className="vap-tab-section">
                        <div className="vap-card vap-guidance-card">
                            <div className="vap-card-header">
                                <h2>{isId ? 'Panduan moderasi' : 'Moderation guide'}</h2>
                            </div>
                            <div className="vap-card-body">
                                <ul className="vap-guidelines-list">
                                    <li>
                                        <strong>{isId ? 'Verifikasi nama objek' : 'Verify object names'}</strong>
                                        <p>{isId ? 'Gunakan nama resmi yang jelas dan tidak duplikat.' : 'Use clear official names without duplicates.'}</p>
                                    </li>
                                    <li>
                                        <strong>{isId ? 'Catat alasan keputusan' : 'Record your reason'}</strong>
                                        <p>{isId ? 'Setiap tindakan moderasi memerlukan alasan yang tercatat.' : 'Every moderation action needs a recorded reason.'}</p>
                                    </li>
                                    <li>
                                        <strong>{isId ? 'Tangani objek duplikat' : 'Handle duplicates'}</strong>
                                        <p>{isId ? 'Gunakan Master Objek untuk menggabungkan data yang sama.' : 'Use Master Objects to merge matching records.'}</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                )}

                {/* TAB 2: OBJECTS QUEUE */}
                {activeTab === 'objects' && (
                    <div className="vap-tab-section">
                        <div className="vap-card">
                            <div className="vap-card-header">
                                <div className="vap-card-header-left">
                                    <h2>{isId ? 'Antrean Objek Menunggu Moderasi' : 'Pending Objects Queue'}</h2>
                                    <span className="vap-counter-tag">{objects.total}</span>
                                </div>
                                <p className="vap-card-desc">
                                    {isId
                                        ? 'Verifikasi keabsahan objek yang diajukan oleh pengguna sebelum dapat digunakan secara publik.'
                                        : 'Review user-proposed objects before they appear in public listings.'}
                                </p>
                            </div>

                            <div className="vap-card-body p-0">
                                {objects.data.length ? (
                                    <div className="vap-table-wrap">
                                        <table className="vap-table">
                                            <thead>
                                                <tr>
                                                    <th>{isId ? 'Nama Objek' : 'Canonical Name'}</th>
                                                    <th>ID</th>
                                                    <th>{isId ? 'Status' : 'Status'}</th>
                                                    <th className="text-right">{isId ? 'Tindakan' : 'Actions'}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {objects.data.map((object) => (
                                                    <tr key={object.id}>
                                                        <td>
                                                            <div className="vap-object-name-col">
                                                                <strong>{object.canonical_name}</strong>
                                                                {object.pending_image_path && <img className="vap-object-image-preview" src={`/backend/objects/${object.id}/image?pending=1`} alt={isId ? `Usulan gambar ${object.canonical_name}` : `Proposed image for ${object.canonical_name}`} />}
                                                                {object.content_revisions?.[0] && <details className="vap-subtext">
                                                                    <summary>{isId ? 'Baca ringkasan yang diajukan' : 'Read submitted summary'}</summary>
                                                                    <ContentBody revision={object.content_revisions[0]} />
                                                                    <ul>{object.content_revisions[0].sources?.map((url) => <li key={url}><a href={url} target="_blank" rel="noopener noreferrer">{url}</a></li>)}</ul>
                                                                </details>}
                                                                {object.normalized_name && (
                                                                    <small className="vap-subtext">norm: {object.normalized_name}</small>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <code className="vap-code-id">{object.id}</code>
                                                        </td>
                                                        <td>
                                                            <span className={`vap-badge status-${object.status}`}>
                                                                {object.status}
                                                            </span>
                                                        </td>
                                                        <td className="text-right">
                                                            <div className="vap-action-btn-group">
                                                                {object.status === 'verified' ? <>
                                                                    <button type="button" className="vap-btn vap-btn-success" disabled={busy} onClick={() => reviewObjectImage(object, 'approved')}>✓ {isId ? 'Setujui gambar' : 'Approve image'}</button>
                                                                    <button type="button" className="vap-btn vap-btn-danger" disabled={busy} onClick={() => reviewObjectImage(object, 'rejected')}>✕ {isId ? 'Tolak gambar' : 'Reject image'}</button>
                                                                </> : <>
                                                                <button
                                                                    type="button"
                                                                    className="vap-btn vap-btn-success"
                                                                    disabled={busy}
                                                                    onClick={() => act(`/admin/backend/objects/${object.id}`, 'verified')}
                                                                    title={isId ? 'Setujui objek' : 'Approve object'}
                                                                >
                                                                    ✓ {t.approve}
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    className="vap-btn vap-btn-warning"
                                                                    disabled={busy}
                                                                    onClick={() => act(`/admin/backend/objects/${object.id}`, 'needs_revision')}
                                                                    title={isId ? 'Minta revisi' : 'Needs revision'}
                                                                >
                                                                    ✎ {t.needsRevision}
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    className="vap-btn vap-btn-danger"
                                                                    disabled={busy}
                                                                    onClick={() => act(`/admin/backend/objects/${object.id}`, 'rejected')}
                                                                    title={isId ? 'Tolak objek' : 'Reject object'}
                                                                >
                                                                    ✕ {t.reject}
                                                                </button>
                                                                </>}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <div className="vap-empty-state">
                                        <span className="vap-empty-icon">✓</span>
                                        <h3>{isId ? 'Semua objek telah ditinjau!' : 'No pending objects!'}</h3>
                                        <p>{t.emptyQueue}</p>
                                    </div>
                                )}
                            </div>

                            <div className="vap-card-footer">
                                <PaginationLinks pagination={objects} locale={locale} />
                            </div>
                        </div>
                    </div>
                )}

                {activeTab === 'content' && <div className="vap-tab-section">
                    <div className="vap-card">
                        <div className="vap-card-header"><h2>{isId ? 'Ringkasan & artikel menunggu tinjauan' : 'Summaries & articles awaiting review'}</h2></div>
                        <div className="vap-card-body">
                            {contentQueue.data.length ? contentQueue.data.map((revision) => <article key={revision.id} className="vap-report-item">
                                <div className="vap-report-meta">
                                    <strong>{revision.article_id ? revision.title : (isId ? 'Ringkasan objek' : 'Object summary')}</strong>
                                    <p>{revision.object?.canonical_name} · {revision.article?.type ?? 'summary'} · {revision.language_code.toUpperCase()} · {revision.author?.name}</p>
                                    <ContentBody revision={revision} />
                                    <ul>{revision.sources?.map((url) => <li key={url}><a href={url} target="_blank" rel="noopener noreferrer">{url}</a></li>)}</ul>
                                </div>
                                <div className="vap-report-actions">
                                    <button type="button" className="vap-btn vap-btn-success" disabled={busy} onClick={() => reviewContent(revision, 'published')}>✓ {t.approve}</button>
                                    <button type="button" className="vap-btn vap-btn-warning" disabled={busy} onClick={() => reviewContent(revision, 'needs_revision')}>✎ {t.needsRevision}</button>
                                    <button type="button" className="vap-btn vap-btn-danger" disabled={busy} onClick={() => reviewContent(revision, 'rejected')}>✕ {t.reject}</button>
                                </div>
                            </article>) : <p>{isId ? 'Tidak ada tulisan menunggu tinjauan.' : 'No content awaits review.'}</p>}
                        </div>
                        <div className="vap-card-footer"><PaginationLinks pagination={contentQueue} locale={locale} /></div>
                    </div>
                </div>}

                {/* TAB 3: REPORTS QUEUE */}
                {activeTab === 'reports' && (
                    <div className="vap-tab-section">
                        <div className="vap-card">
                            <div className="vap-card-header">
                                <div className="vap-card-header-left">
                                    <h2>{isId ? 'Laporan Konten Masuk' : 'Open Reports Queue'}</h2>
                                    <span className="vap-counter-tag danger">{reports.total}</span>
                                </div>
                                <p className="vap-card-desc">
                                    {isId
                                        ? 'Tinjau laporan pelanggaran konten yang dikirimkan oleh anggota komunitas.'
                                        : 'Review reported content submitted by community members.'}
                                </p>
                            </div>

                            <div className="vap-card-body p-0">
                                {reports.data.length ? (
                                    <div className="vap-reports-list">
                                        {reports.data.map((report) => (
                                            <div key={report.id} className="vap-report-item">
                                                <div className="vap-report-meta">
                                                    <div className="vap-report-type-badge">
                                                        <span className="vap-badge primary">{report.target_type}</span>
                                                        <code className="vap-code-id">#{report.target_id}</code>
                                                    </div>
                                                    <div className="vap-report-reason-box">
                                                        <span className="vap-reason-label">{isId ? 'Alasan pelapor:' : 'Report reason:'}</span>
                                                        <p className="vap-report-reason-text">{report.reason}</p>
                                                    </div>
                                                    {report.message && (
                                                        <div className="vap-report-reason-box">
                                                            <span className="vap-reason-label">
                                                                {isId ? 'Pesan dari' : 'Message from'} @{report.message.user?.username ?? '-'}
                                                                {report.message.status === 'hidden' && ` (${isId ? 'disembunyikan' : 'hidden'})`}:
                                                            </span>
                                                            <p className="vap-report-reason-text">{report.message.body}</p>
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="vap-report-actions">
                                                    <button
                                                        type="button"
                                                        className="vap-btn vap-btn-danger"
                                                        disabled={busy}
                                                        onClick={() => hideTarget(report)}
                                                    >
                                                        🚫 {isId ? 'Sembunyikan Target' : 'Hide Target'}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        className="vap-btn vap-btn-success"
                                                        disabled={busy}
                                                        onClick={() => resolveReport(report, 'resolved')}
                                                    >
                                                        ✓ {t.resolve}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        className="vap-btn vap-btn-secondary"
                                                        disabled={busy}
                                                        onClick={() => resolveReport(report, 'dismissed')}
                                                    >
                                                        ✕ {t.dismiss}
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="vap-empty-state">
                                        <span className="vap-empty-icon">✓</span>
                                        <h3>{isId ? 'Tidak ada laporan terbuka' : 'No open reports!'}</h3>
                                        <p>{t.emptyQueue}</p>
                                    </div>
                                )}
                            </div>

                            <div className="vap-card-footer">
                                <PaginationLinks pagination={reports} locale={locale} />
                            </div>
                        </div>
                    </div>
                )}

                {/* TAB 4: MASTER OBJECTS WORKBENCH */}
                {activeTab === 'master' && (
                    <div className="vap-tab-section">
                        <div className="vap-card">
                            <div className="vap-card-header">
                                <h2>{isId ? 'Workbench Master Objek & Penggabungan' : 'Master Object Workbench'}</h2>
                                <p className="vap-card-desc">
                                    {isId
                                        ? 'Kelola alias pencarian, terjemahan nama multi-bahasa, serta merge objek duplikat.'
                                        : 'Manage search aliases, multilingual translations, and duplicate merges.'}
                                </p>
                            </div>

                            <div className="vap-card-body">
                                <div className={`vap-search-grid ${isAdmin ? 'two-col' : 'single-col'}`}>
                                    {/* Source Object Search */}
                                    <div className="vap-field-box">
                                        <label className="vap-field-label">
                                            {isId ? '1. Pilih Objek Sumber (Source Object)' : '1. Select Source Object'}
                                        </label>
                                        <div className="vap-input-wrap">
                                            <input
                                                type="text"
                                                className="vap-input"
                                                value={sourceQuery}
                                                onChange={(e) => {
                                                    setSourceQuery(e.target.value);
                                                    setSource(null);
                                                }}
                                                placeholder={t.candidateSearch}
                                            />
                                        </div>

                                        {sourceOptions.length > 0 && (
                                            <div className="vap-dropdown-list">
                                                {sourceOptions.map((object) => (
                                                    <button
                                                        key={object.id}
                                                        type="button"
                                                        className="vap-dropdown-item"
                                                        onClick={() => {
                                                            setSource(object);
                                                            setSourceQuery(object.canonical_name);
                                                            setSourceOptions([]);
                                                        }}
                                                    >
                                                        <strong>{object.canonical_name}</strong>
                                                        <span className={`vap-badge status-${object.status}`}>
                                                            {object.status}
                                                        </span>
                                                    </button>
                                                ))}
                                            </div>
                                        )}

                                        {source && (
                                            <div className="vap-selected-pill">
                                                <span className="vap-selected-dot" />
                                                <strong>{source.canonical_name}</strong>
                                                <code className="vap-code-id">#{source.id}</code>
                                                <span className={`vap-badge status-${source.status}`}>{source.status}</span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Target Object Search (For Merge) */}
                                    {isAdmin && (
                                        <div className="vap-field-box">
                                            <label className="vap-field-label">
                                                {isId ? '2. Pilih Objek Tujuan Merge (Target Master)' : '2. Select Merge Target'}
                                            </label>
                                            <div className="vap-input-wrap">
                                                <input
                                                    type="text"
                                                    className="vap-input"
                                                    value={targetQuery}
                                                    onChange={(e) => {
                                                        setTargetQuery(e.target.value);
                                                        setTarget(null);
                                                    }}
                                                    placeholder={isId ? 'Cari target master objek...' : 'Search master object target...'}
                                                />
                                            </div>

                                            {targetOptions.length > 0 && (
                                                <div className="vap-dropdown-list">
                                                    {targetOptions.map((object) => (
                                                        <button
                                                            key={object.id}
                                                            type="button"
                                                            className="vap-dropdown-item"
                                                            onClick={() => {
                                                                setTarget(object);
                                                                setTargetQuery(object.canonical_name);
                                                                setTargetOptions([]);
                                                            }}
                                                        >
                                                            <strong>{object.canonical_name}</strong>
                                                            <span className={`vap-badge status-${object.status}`}>
                                                                {object.status}
                                                            </span>
                                                        </button>
                                                    ))}
                                                </div>
                                            )}

                                            {target && (
                                                <div className="vap-selected-pill">
                                                    <span className="vap-selected-dot" />
                                                    <strong>{target.canonical_name}</strong>
                                                    <code className="vap-code-id">#{target.id}</code>
                                                    <span className={`vap-badge status-${target.status}`}>{target.status}</span>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>

                                {/* Alias Tool */}
                                <div className="vap-tool-block">
                                    <h3 className="vap-tool-heading">{isId ? 'A. Tambah Alias Pencarian' : 'A. Add Search Alias'}</h3>
                                    <div className="vap-inline-form">
                                        <input
                                            type="text"
                                            className="vap-input"
                                            value={alias}
                                            onChange={(e) => setAlias(e.target.value)}
                                            placeholder={isId ? 'Contoh alias: iPhone 16 Pro, iP16' : 'e.g. iPhone 16 Pro, iP16'}
                                            aria-label={isId ? 'Alias baru' : 'New alias'}
                                        />
                                        <button
                                            type="button"
                                            className="vap-btn vap-btn-primary"
                                            disabled={busy || !source || alias.trim().length < 2}
                                            onClick={() =>
                                                runMasterAction(`/admin/backend/objects/${source?.id}/aliases`, 'POST', {
                                                    alias,
                                                })
                                            }
                                        >
                                            + {isId ? 'Tambah Alias' : 'Add Alias'}
                                        </button>
                                    </div>
                                </div>

                                {/* Translation Tool */}
                                <div className="vap-tool-block">
                                    <h3 className="vap-tool-heading">{isId ? 'B. Terjemahan Nama Multi-Bahasa' : 'B. Multilingual Translations'}</h3>
                                    <div className="vap-inline-form">
                                        <select
                                            className="vap-select"
                                            value={translationLocale}
                                            onChange={(e) => setTranslationLocale(e.target.value)}
                                            aria-label={t.language}
                                        >
                                            <option value="id">Bahasa Indonesia (ID)</option>
                                            <option value="en">English (EN)</option>
                                        </select>
                                        <input
                                            type="text"
                                            className="vap-input"
                                            value={translationName}
                                            onChange={(e) => setTranslationName(e.target.value)}
                                            placeholder={isId ? 'Nama terjemahan...' : 'Translated name...'}
                                            aria-label={isId ? 'Nama terjemahan' : 'Translated name'}
                                        />
                                        <button
                                            type="button"
                                            className="vap-btn vap-btn-primary"
                                            disabled={busy || !source || translationName.trim().length < 2}
                                            onClick={() =>
                                                runMasterAction(
                                                    `/admin/backend/objects/${source?.id}/translations`,
                                                    'PUT',
                                                    { locale: translationLocale, name: translationName }
                                                )
                                            }
                                        >
                                            💾 {isId ? 'Simpan Terjemahan' : 'Save Translation'}
                                        </button>
                                    </div>
                                </div>

                                {/* Merge & Undo Merge Tools (Admin Only) */}
                                {isAdmin && (
                                    <div className="vap-tool-block">
                                        <h3 className="vap-tool-heading">{isId ? 'C. Penggabungan Objek (Merge & Undo Merge)' : 'C. Object Merge & Undo'}</h3>
                                        <div className="vap-inline-form flex-wrap">
                                            <button
                                                type="button"
                                                className="vap-btn vap-btn-primary"
                                                disabled={busy || !source || !target || source.id === target.id}
                                                onClick={() =>
                                                    runMasterAction(`/admin/backend/objects/${source?.id}/merge`, 'POST', {
                                                        target_id: target?.id,
                                                    })
                                                }
                                            >
                                                🔗 {isId ? 'Gabungkan (Merge)' : 'Merge Objects'} →
                                            </button>

                                            <select
                                                className="vap-select max-w-sm"
                                                value={source?.status === 'merged' ? source.id : ''}
                                                onChange={(e) => {
                                                    const item = mergedObjects.find(
                                                        (object) => String(object.id) === e.target.value
                                                    );
                                                    if (item) setSource(item);
                                                }}
                                                aria-label={isId ? 'Objek tergabung' : 'Merged object'}
                                            >
                                                <option value="">
                                                    {isId ? '-- Pilih Objek Tergabung --' : '-- Select Merged Object --'}
                                                </option>
                                                {mergedObjects.map((object) => (
                                                    <option key={object.id} value={object.id}>
                                                        {object.canonical_name} (#{object.id})
                                                    </option>
                                                ))}
                                            </select>

                                            <button
                                                type="button"
                                                className="vap-btn vap-btn-secondary"
                                                disabled={busy || !source || source.status !== 'merged'}
                                                onClick={() =>
                                                    runMasterAction(
                                                        `/admin/backend/objects/${source?.id}/undo-merge`,
                                                        'POST',
                                                        {}
                                                    )
                                                }
                                            >
                                                ↩ {isId ? 'Batalkan Merge (Undo)' : 'Undo Merge'}
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* TAB 5: CATEGORIES & BRANDS (ADMIN ONLY) */}
                {isAdmin && activeTab === 'categories' && (
                    <div className="vap-tab-section">
                        <div className="vap-grid-two">
                            <div className="vap-card">
                                <div className="vap-card-header">
                                    <div className="vap-card-header-left">
                                        <h2>{isId ? 'Kategori Ranking' : 'Ranking Categories'}</h2>
                                        <span className="vap-counter-tag">{categories.length}</span>
                                    </div>
                                </div>
                                <div className="vap-card-body">
                                    <form className="vap-add-form" onSubmit={createCategory}>
                                        <input
                                            type="text"
                                            className="vap-input"
                                            minLength={2}
                                            maxLength={120}
                                            value={categoryName}
                                            onChange={(e) => setCategoryName(e.target.value)}
                                            placeholder={isId ? 'Nama kategori baru...' : 'New category name...'}
                                            required
                                        />
                                        <button type="submit" className="vap-btn vap-btn-primary" disabled={busy}>
                                            + {isId ? 'Tambah Kategori' : 'Add Category'}
                                        </button>
                                    </form>

                                    {categories.length > CATEGORY_FILTER_THRESHOLD && (
                                        <input
                                            type="search"
                                            className="vap-input vap-chips-filter"
                                            value={categoryFilter}
                                            onChange={(e) => setCategoryFilter(e.target.value)}
                                            placeholder={isId ? `Cari di ${categories.length} kategori...` : `Search ${categories.length} categories...`}
                                            aria-label={isId ? 'Cari kategori' : 'Search categories'}
                                        />
                                    )}

                                    {filteredCategories.length ? (
                                        <div className="vap-chips-cloud is-scrollable">
                                            {filteredCategories.map((category) => (
                                                <span key={category.id} className="vap-chip">
                                                    <strong>{category.name}</strong>
                                                    <small className="vap-chip-slug">/{category.slug}</small>
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="vap-chips-empty" role="status">
                                            {isId ? 'Tidak ada kategori yang cocok.' : 'No matching categories.'}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="vap-card">
                                <div className="vap-card-header">
                                    <h2>{isId ? 'Tambah Merek (Brands)' : 'Add Brands'}</h2>
                                </div>
                                <div className="vap-card-body">
                                    <form className="vap-add-form" onSubmit={createBrand}>
                                        <input
                                            type="text"
                                            className="vap-input"
                                            minLength={2}
                                            maxLength={120}
                                            value={brandName}
                                            onChange={(e) => setBrandName(e.target.value)}
                                            placeholder={isId ? 'Nama merek baru...' : 'New brand name...'}
                                            required
                                        />
                                        <button type="submit" className="vap-btn vap-btn-primary" disabled={busy}>
                                            + {isId ? 'Tambah Merek' : 'Add Brand'}
                                        </button>
                                    </form>
                                    <p className="vap-card-desc mt-3">
                                        {isId
                                            ? 'Merek yang didaftarkan akan mempermudah kategorisasi dan filter objek kandidat dalam ranking.'
                                            : 'Registered brands help categorize candidate objects in rankings.'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* TAB 6: AUDIT LOGS (ADMIN ONLY) */}
                {isAdmin && activeTab === 'audit' && (
                    <div className="vap-tab-section">
                        <div className="vap-card">
                            <div className="vap-card-header">
                                <h2>{isId ? 'Log Riwayat Audit Moderasi' : 'Recent Audit Logs'}</h2>
                                <p className="vap-card-desc">
                                    {isId
                                        ? 'Catatan rekaman setiap tindakan administratif, alasan, dan waktu eksekusi.'
                                        : 'Immutable trace of administrative actions, reasons, and timestamps.'}
                                </p>
                            </div>

                            <div className="vap-card-body p-0">
                                {audits.length ? (
                                    <div className="vap-table-wrap">
                                        <table className="vap-table">
                                            <thead>
                                                <tr>
                                                    <th>{isId ? 'Aksi' : 'Action'}</th>
                                                    <th>{isId ? 'Target' : 'Target'}</th>
                                                    <th>ID Target</th>
                                                    <th>{isId ? 'Waktu' : 'Timestamp'}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {audits.map((item) => (
                                                    <tr key={item.id}>
                                                        <td>
                                                            <strong className="vap-audit-action">{item.action}</strong>
                                                        </td>
                                                        <td>
                                                            <span className="vap-badge primary">{item.target_type}</span>
                                                        </td>
                                                        <td>
                                                            <code className="vap-code-id">#{item.target_id}</code>
                                                        </td>
                                                        <td>
                                                            <span className="vap-timestamp">
                                                                {item.created_at ? new Date(item.created_at).toLocaleString(isId ? 'id-ID' : 'en-US') : '-'}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <div className="vap-empty-state">
                                        <p>{t.noResults}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
