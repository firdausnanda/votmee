import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { api, dateLabel, dictionary, Layout, numberLabel, PageHead, path } from './Shared';

export default function ObjectPage({ locale, categories, leaders, object, stats, rankings }) {
    const t = dictionary(locale);
    const user = usePage().props.auth?.user;
    const [reporting, setReporting] = useState(false);
    const [reason, setReason] = useState('');
    const [message, setMessage] = useState('');
    const [busy, setBusy] = useState(false);

    const report = async (event) => {
        event.preventDefault(); setBusy(true); setMessage('');
        try {
            await api('/backend/reports', 'POST', { target_type: 'object', target_id: object.id, reason });
            setReporting(false); setReason(''); setMessage(locale === 'id' ? 'Laporan terkirim.' : 'Report sent.');
        } catch (error) { setMessage(error.message); } finally { setBusy(false); }
    };

    return <Layout locale={locale} categories={categories} leaders={leaders}>
        <PageHead title={object.canonical_name} locale={locale} />
        <div className="vt-object-header"><p className="vt-eyebrow">{object.category?.name ?? t.objects}</p><h1>{object.translations.find(item => item.locale === locale)?.name ?? object.canonical_name}</h1><span className="vt-status">{object.status === 'verified' ? t.verified : t.pending}</span></div>
        <div className="vt-object-stat-grid"><div><strong>{numberLabel(stats.supporters, locale)}</strong><span>{t.supporters}</span></div><div><strong>{numberLabel(stats.points, locale)}</strong><span>{t.points}</span></div></div>
        {object.aliases.length > 0 && <div className="vt-object-aliases"><strong>{locale === 'id' ? 'Nama lain' : 'Also known as'}</strong><p>{object.aliases.map(item => item.alias).join(' · ')}</p></div>}
        <div className="vt-object-actions"><button onClick={() => user ? setReporting(true) : router.visit(path(locale, '/login'))}>{t.report}</button></div>
        {message && <p className="vt-form-message" role="status">{message}</p>}
        <section className="vt-results-section"><div className="vt-section-heading"><h2>{t.objectRankings}</h2><span>{rankings.length}</span></div>
            {rankings.length ? <div className="vt-simple-list">{rankings.map(post => <article key={post.id}><div><Link href={path(locale, `/ranking/${post.slug}`)}>{post.title}</Link><p>{t.createdBy} <Link href={path(locale, `/profile/${post.creator?.username}`)}>{post.creator?.name}</Link> · {dateLabel(post.published_at, locale)}</p></div><span>→</span></article>)}</div>
                : <p className="vt-muted">{t.noRankings}</p>}
        </section>
        {reporting && <div className="vt-modal-backdrop" role="presentation" onMouseDown={event => { if (event.target === event.currentTarget) setReporting(false); }}><form className="vt-modal" role="dialog" aria-modal="true" aria-labelledby="object-report-title" onSubmit={report}>
            <h2 id="object-report-title">{t.report}</h2><label>{t.reportReason}<textarea minLength={10} maxLength={2000} value={reason} onChange={e => setReason(e.target.value)} required /></label>
            {message && <p className="vt-form-message" role="alert">{message}</p>}
            <div className="vt-modal-actions"><button type="button" onClick={() => setReporting(false)}>{t.cancel}</button><button disabled={busy}>{t.sendReport}</button></div>
        </form></div>}
    </Layout>;
}
