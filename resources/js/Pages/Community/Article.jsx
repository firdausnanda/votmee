import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ArticleSources from './ArticleSources';
import ContentBody from './ContentBody';
import { CommunityDiscussion } from './ObjectKnowledge';
import ReportDialog from './ReportDialog';
import { api, dateLabel, Layout, NavIcon, PageHead, path } from './Shared';

export default function Article({ locale, categories, article, author, revision, object }) {
    const isId = locale === 'id';
    const user = usePage().props.auth?.user;
    const [reporting, setReporting] = useState(false);
    const objectUrl = path(locale, `/object/${object.slug}`);

    return (
        <Layout locale={locale} categories={categories} active="object">
            <PageHead
                title={revision.title}
                locale={locale}
                description={isId
                    ? `Baca tulisan komunitas tentang ${object.canonical_name}: ${revision.title}.`
                    : `Read a community article about ${object.canonical_name}: ${revision.title}.`}
            />

            <div className="vob-container vob-article-page">
                <nav className="vr-simple-nav" aria-label="Breadcrumb">
                    <Link href={objectUrl} className="vr-back-link">
                        <NavIcon name="arrowLeft" size={13} />
                        <span>{isId ? 'Kembali ke Objek' : 'Back to object'}</span>
                    </Link>
                    <span className="vr-nav-separator">/</span>
                    <span className="vr-nav-current">{revision.title}</span>
                </nav>

                <article className="vob-knowledge-panel vob-article-reading">
                    <header className="vob-article-reading-header">
                        <div className="vob-article-top">
                            <span className="vob-article-type-pill">
                                {article.type === 'experience'
                                    ? (isId ? 'Pengalaman Pribadi' : 'Personal experience')
                                    : (isId ? 'Informatif' : 'Informative')}
                            </span>
                            <span className="vob-article-lang-pill">{revision.language_code?.toUpperCase()}</span>
                        </div>
                        <h1>{revision.title}</h1>
                        <div className="vob-content-meta">
                            <span>{isId ? 'Penulis' : 'Author'}: <strong>{author?.name}</strong></span>
                            {revision.published_at && (
                                <>
                                    <span>·</span>
                                    <span>{dateLabel(revision.published_at, locale)}</span>
                                </>
                            )}
                            <span>·</span>
                            <Link href={objectUrl}>{object.canonical_name}</Link>
                        </div>
                    </header>

                    <ContentBody revision={revision} />
                    <ArticleSources sources={revision.sources} locale={locale} />

                    {user && (
                        <div className="vob-article-footer">
                            {user?.id === article.author_id && (
                                <Link className="vob-btn-article-action" href={path(locale, `/article/${article.id}/edit`)}>
                                    <NavIcon name="edit" size={12} />
                                    <span>{isId ? 'Usulkan Revisi' : 'Suggest revision'}</span>
                                </Link>
                            )}
                            <button type="button" className="vob-btn-article-ghost" onClick={() => setReporting(true)}>
                                <NavIcon name="shield" size={12} />
                                <span>{isId ? 'Laporkan' : 'Report article'}</span>
                            </button>
                        </div>
                    )}
                </article>

                <CommunityDiscussion article={article} locale={locale} />
            </div>

            {reporting && (
                <ReportDialog
                    locale={locale}
                    targetType="object_article"
                    onClose={() => setReporting(false)}
                    onSubmit={async (reason) => {
                        await api('/backend/reports', 'POST', { target_type: 'object_article', target_id: article.id, reason });
                        setReporting(false);
                    }}
                />
            )}
        </Layout>
    );
}
