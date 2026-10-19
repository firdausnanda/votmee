import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { api, dateLabel, openLoginModal, path } from './Shared';
import ReportDialog from './ReportDialog';
import ContentBody from './ContentBody';

function Sources({ sources, locale }) {
    if (!sources?.length) return null;
    return <div className="vob-sources">
        <strong>{locale === 'id' ? 'Sumber' : 'Sources'}</strong>
        <ul>{sources.map((url) => <li key={url}><a href={url} target="_blank" rel="noopener noreferrer">{url}</a></li>)}</ul>
    </div>;
}

function ObjectDiscussion({ object, locale }) {
    const isId = locale === 'id';
    const user = usePage().props.auth?.user;
    const [comments, setComments] = useState([]);
    const [total, setTotal] = useState(0);
    const [hasMore, setHasMore] = useState(false);
    const [sort, setSort] = useState('latest');
    const [body, setBody] = useState('');
    const [replyTo, setReplyTo] = useState(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [reporting, setReporting] = useState(null);

    const load = async (offset = 0, order = sort) => {
        const data = await api(`/backend/objects/${object.slug}/comments?offset=${offset}&limit=15&sort=${order}`);
        setComments((current) => offset ? [...current, ...data.comments] : data.comments);
        setTotal(data.total);
        setHasMore(data.has_more);
    };

    useEffect(() => {
        load(0, sort).catch((failure) => setError(failure.message));
    }, [object.slug, sort]);

    const submit = async (event) => {
        event.preventDefault();
        setBusy(true);
        setError('');
        try {
            await api(`/backend/objects/${object.slug}/comments`, 'POST', { body, ...(replyTo ? { parent_id: replyTo } : {}) });
            setBody('');
            setReplyTo(null);
            await load();
        } catch (failure) {
            setError(failure.message);
        } finally {
            setBusy(false);
        }
    };

    const like = async (comment) => {
        if (!user) return openLoginModal();
        try {
            await api(`/backend/comments/${comment.id}/like`, 'POST');
            await load();
        } catch (failure) {
            setError(failure.message);
        }
    };

    return <section className="vob-knowledge-panel" id="object-discussion">
        <div className="vob-knowledge-heading">
            <div><h2>{isId ? 'Diskusi objek' : 'Object discussion'}</h2><p>{total} {isId ? 'komentar' : 'comments'}</p></div>
            <select aria-label={isId ? 'Urutkan komentar' : 'Sort comments'} value={sort} onChange={(event) => setSort(event.target.value)}>
                <option value="latest">{isId ? 'Terbaru' : 'Latest'}</option>
                <option value="popular">{isId ? 'Populer' : 'Popular'}</option>
            </select>
        </div>
        {error && <p role="alert" className="vob-form-error">{error}</p>}
        {comments.length ? <ol className="vob-comments">{comments.map((comment) => <li key={comment.id} className={comment.parent_id ? 'is-reply' : ''}>
            <div className="vob-comment-meta"><strong>{comment.name}</strong><span>@{comment.username} · {dateLabel(comment.created_at, locale)}</span></div>
            <p>{comment.body}</p>
            <div className="vob-comment-actions">
                <button type="button" onClick={() => like(comment)} aria-pressed={comment.liked}>{comment.liked ? '♥' : '♡'} {comment.likes_count}</button>
                <button type="button" onClick={() => user ? setReplyTo(comment.id) : openLoginModal()}>{isId ? 'Balas' : 'Reply'}</button>
                <button type="button" onClick={() => user ? setReporting(comment) : openLoginModal()}>{isId ? 'Laporkan' : 'Report'}</button>
            </div>
        </li>)}</ol> : <p>{isId ? 'Belum ada komentar.' : 'No comments yet.'}</p>}
        {hasMore && <button type="button" onClick={() => load(comments.length).catch((failure) => setError(failure.message))}>{isId ? 'Muat lagi' : 'Load more'}</button>}
        {user ? <form className="vob-editor" onSubmit={submit}>
            <label>{replyTo ? (isId ? 'Tulis balasan' : 'Write a reply') : (isId ? 'Tulis komentar' : 'Write a comment')}
                <textarea value={body} onChange={(event) => setBody(event.target.value)} minLength={2} maxLength={3000} rows={3} required />
            </label>
            <div className="vob-editor-actions"><button type="submit" disabled={busy}>{isId ? 'Kirim' : 'Post'}</button>{replyTo && <button type="button" onClick={() => setReplyTo(null)}>{isId ? 'Batal balas' : 'Cancel reply'}</button>}</div>
        </form> : <button type="button" onClick={openLoginModal}>{isId ? 'Masuk untuk berkomentar' : 'Sign in to comment'}</button>}
        {reporting && <ReportDialog locale={locale} targetType="comment" onClose={() => setReporting(null)} onSubmit={async (reason) => {
            await api('/backend/reports', 'POST', { target_type: 'comment', target_id: String(reporting.id), reason });
            setReporting(null);
        }} />}
    </section>;
}

export default function ObjectKnowledge({ object, summary, articles, myRevisions = [], reviewFeedback, locale }) {
    const isId = locale === 'id';
    const user = usePage().props.auth?.user;
    const [reporting, setReporting] = useState(null);
    const feedback = reviewFeedback ? (typeof reviewFeedback === 'string' ? JSON.parse(reviewFeedback) : reviewFeedback) : null;
    const canResubmit = user?.id === object.submitted_by && ['needs_revision', 'rejected'].includes(object.status);
    const latestOwn = myRevisions.filter((revision, index, all) => all.findIndex((item) => item.article_id === revision.article_id) === index);

    return <div className="vob-knowledge-stack">
        <section className="vob-knowledge-panel">
            <div className="vob-knowledge-heading"><div><h2>{isId ? 'Tentang objek' : 'About this object'}</h2><p>{isId ? 'Ringkasan ensiklopedia' : 'Encyclopedia summary'}</p></div>
                {object.status === 'verified' && user && <Link className="vob-knowledge-action" href={path(locale, `/object/${object.slug}/summary/edit`)}>{isId ? 'Usulkan revisi' : 'Suggest an edit'}</Link>}
            </div>
            {summary ? <>
                <ContentBody revision={summary} />
                <p className="vob-content-meta">{summary.language_code.toUpperCase()} · {isId ? 'Kontributor' : 'Contributor'}: {summary.author?.name} · {dateLabel(summary.published_at, locale)}</p>
                <Sources sources={summary.sources} locale={locale} />
            </> : <p>{isId ? 'Ringkasan belum tersedia.' : 'No summary is available yet.'}</p>}
            {feedback && canResubmit && <p role="status" className="vob-form-error">{isId ? 'Alasan moderator' : 'Moderator feedback'}: {feedback.reason}</p>}
            {canResubmit && <Link className="vob-knowledge-action" href={path(locale, `/object/${object.slug}/submission/edit`)}>{isId ? 'Perbaiki pengajuan objek' : 'Revise object submission'}</Link>}
        </section>

        {object.status === 'verified' && <>
            <section className="vob-knowledge-panel">
                <div className="vob-knowledge-heading"><div><h2>{isId ? 'Tulisan komunitas' : 'Community articles'}</h2><p>{isId ? 'Informasi dan pengalaman dari pengguna' : 'Information and personal experiences'}</p></div>
                    {user && <Link className="vob-knowledge-action" href={path(locale, `/object/${object.slug}/write`)}>{isId ? 'Tulis artikel' : 'Write an article'}</Link>}
                </div>
                {articles?.data?.length ? articles.data.map((article) => <article key={article.id} className="vob-article">
                    <div className="vob-article-top"><span>{article.type === 'experience' ? (isId ? 'Pengalaman pribadi' : 'Personal experience') : (isId ? 'Informatif' : 'Informative')}</span><span>{article.published_revision?.language_code?.toUpperCase()}</span></div>
                    <h3>{article.published_revision?.title}</h3>
                    <p className="vob-content-meta">{article.author?.name} · {dateLabel(article.published_revision?.published_at, locale)}</p>
                    <ContentBody revision={article.published_revision} />
                    <Sources sources={article.published_revision?.sources} locale={locale} />
                    {user?.id === article.author_id && <Link className="vob-article-edit" href={path(locale, `/article/${article.id}/edit`)}>{isId ? 'Usulkan revisi' : 'Suggest revision'}</Link>}
                    {user && <button type="button" onClick={() => setReporting(article)}>{isId ? 'Laporkan artikel' : 'Report article'}</button>}
                </article>) : <p>{isId ? 'Belum ada artikel terbit.' : 'No published articles yet.'}</p>}
                {articles?.last_page > 1 && <nav aria-label={isId ? 'Halaman artikel' : 'Article pages'} className="vob-article-pages">
                    {articles.prev_page_url && <Link href={articles.prev_page_url} preserveScroll>{isId ? 'Sebelumnya' : 'Previous'}</Link>}
                    <span>{articles.current_page} / {articles.last_page}</span>
                    {articles.next_page_url && <Link href={articles.next_page_url} preserveScroll>{isId ? 'Berikutnya' : 'Next'}</Link>}
                </nav>}
            </section>

            {latestOwn.length > 0 && <section className="vob-knowledge-panel">
                <h2>{isId ? 'Kontribusi saya' : 'My submissions'}</h2>
                <ul className="vob-own-revisions">{latestOwn.map((revision) => <li key={revision.id}>
                    <strong>{revision.article_id ? revision.title : (isId ? 'Ringkasan objek' : 'Object summary')}</strong>
                    <span> · {revision.status}</span>
                    {revision.review_reason && <p>{isId ? 'Alasan moderator' : 'Moderator feedback'}: {revision.review_reason}</p>}
                    {revision.article_id && ['needs_revision', 'rejected', 'published'].includes(revision.status) && <Link href={path(locale, `/article/${revision.article_id}/edit`)}>{isId ? 'Usulkan revisi' : 'Suggest revision'}</Link>}
                </li>)}</ul>
            </section>}

            <ObjectDiscussion object={object} locale={locale} />
        </>}
        {reporting && <ReportDialog locale={locale} targetType="object_article" onClose={() => setReporting(null)} onSubmit={async (reason) => {
            await api('/backend/reports', 'POST', { target_type: 'object_article', target_id: reporting.id, reason });
            setReporting(null);
        }} />}
    </div>;
}
