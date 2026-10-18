import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { api, dateLabel, dictionary, Layout, numberLabel, PageHead, path } from './Shared';
import ReportDialog from './ReportDialog';

export default function ObjectPage({ locale, categories, leaders, object, stats, rankings }) {
    const t = dictionary(locale);
    const user = usePage().props.auth?.user;
    const [reporting, setReporting] = useState(false);
    const [message, setMessage] = useState('');
    const displayName = object.translations.find(item => item.locale === locale)?.name ?? object.canonical_name;

    const report = async (reason) => {
        await api('/backend/reports', 'POST', { target_type: 'object', target_id: object.id, reason });
        setReporting(false);
        setMessage(locale === 'id' ? 'Laporan terkirim.' : 'Report sent.');
    };

    return <Layout locale={locale} categories={categories} leaders={leaders} active="object">
        <PageHead title={object.canonical_name} locale={locale} />
        <div className="vdc-page vdc-object-page">
            <div className="vdc-object-overview">
                <header className="vdc-object-hero">
                    <div className="vdc-object-mark" aria-hidden="true">{displayName.slice(0, 1).toUpperCase()}</div>
                    <div className="vdc-object-heading">
                        <p className="vdc-eyebrow">{object.category?.name ?? t.objects}</p>
                        <h1>{displayName}</h1>
                        <span className={`vdc-status ${object.status === 'verified' ? 'is-verified' : ''}`}>
                            {object.status === 'verified' ? t.verified : t.pending}
                        </span>
                    </div>
                    {object.aliases.length > 0 && <div className="vdc-object-aliases">
                        <strong>{locale === 'id' ? 'Nama lain' : 'Also known as'}</strong>
                        <p>{object.aliases.map(item => item.alias).join(' · ')}</p>
                    </div>}
                </header>

                <div className="vdc-object-summary">
                    <dl className="vdc-object-stats">
                        <div><dt>{t.supporters}</dt><dd>{numberLabel(stats.supporters, locale)}</dd></div>
                        <div><dt>{t.points}</dt><dd>{numberLabel(stats.points, locale)}</dd></div>
                    </dl>
                    <button type="button" className="vdc-report-button" onClick={() => user ? setReporting(true) : router.visit(path(locale, '/login'))}>{t.report}</button>
                </div>
            </div>

            {message && <p className="vt-form-message" role="status">{message}</p>}
            <section className="vdc-results" aria-labelledby="vdc-object-rankings-title">
                <div className="vdc-section-heading">
                    <h2 id="vdc-object-rankings-title">{t.objectRankings}</h2>
                    <span>{numberLabel(rankings.length, locale)}</span>
                </div>
                {rankings.length ? <div className="vdc-ranking-grid">
                    {rankings.map(post => <article className="vdc-ranking-card" key={post.id}>
                        <span className="vdc-ranking-category">{object.category?.name ?? t.rankings}</span>
                        <h3><Link href={path(locale, `/ranking/${post.slug}`)}>{post.title}</Link></h3>
                        <p>{t.createdBy} <Link href={path(locale, `/profile/${post.creator?.username}`)}>{post.creator?.name}</Link> · {dateLabel(post.published_at, locale)}</p>
                    </article>)}
                </div> : <p className="vdc-empty">{t.noRankings}</p>}
            </section>
        </div>
        {reporting && <ReportDialog locale={locale} targetType="object" onClose={() => setReporting(false)} onSubmit={report} />}
    </Layout>;
}
