import { Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { api, dateLabel, NavIcon, openLoginModal, path } from './Shared';
import { CommentImageAttachButton, CommentImageGallery, CommentImagePreviews, commentPayload, useCommentImages } from './CommentImages';
import { CommentBadges } from './Achievements';
import { MentionInput, MentionText } from './Mentions';
import ReportDialog from './ReportDialog';
import ContentBody from './ContentBody';
import ArticleSources from './ArticleSources';

export function CommunityDiscussion({ object, article, locale }) {
    const isId = locale === 'id';
    const isArticle = Boolean(article);
    const commentsEndpoint = isArticle
        ? `/backend/articles/${article.id}/comments`
        : `/backend/objects/${object.slug}/comments`;
    const user = usePage().props.auth?.user;
    const [comments, setComments] = useState([]);
    const [total, setTotal] = useState(0);
    const [hasMore, setHasMore] = useState(false);
    const [sort, setSort] = useState('latest');
    const [body, setBody] = useState('');
    const attachments = useCommentImages(locale);
    const [replyTo, setReplyTo] = useState(null);
    const [replyBody, setReplyBody] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [reporting, setReporting] = useState(null);

    const load = async (offset = 0, order = sort) => {
        const data = await api(`${commentsEndpoint}?offset=${offset}&limit=15&sort=${order}`);
        setComments((current) => (offset ? [...current, ...data.comments] : data.comments));
        setTotal(data.total);
        setHasMore(data.has_more);
    };

    useEffect(() => {
        load(0, sort).catch((failure) => setError(failure.message));
    }, [commentsEndpoint, sort]);

    const submit = async (event, parentId = null, content = body) => {
        if (event) event.preventDefault();
        const text = (content || '').trim();
        if (!text || text.length < 2 || attachments.processing) return;
        if (!user) return openLoginModal();

        setBusy(true);
        setError('');
        try {
            await api(commentsEndpoint, 'POST', parentId
                ? { body: text, parent_id: parentId }
                : commentPayload({ body: text }, attachments.images));
            if (parentId) {
                setReplyTo(null);
                setReplyBody('');
            } else {
                setBody('');
                attachments.clear();
            }
            await load(0, sort);
        } catch (failure) {
            setError(failure.message);
        } finally {
            setBusy(false);
        }
    };

    const like = async (comment) => {
        if (!user) return openLoginModal();
        setComments((prev) =>
            prev.map((c) =>
                c.id === comment.id
                    ? {
                          ...c,
                          liked: !c.liked,
                          likes_count: c.liked
                              ? Math.max(0, Number(c.likes_count) - 1)
                              : Number(c.likes_count) + 1,
                      }
                    : c
            )
        );
        try {
            await api(`/backend/comments/${comment.id}/like`, 'POST');
        } catch (failure) {
            setComments((prev) =>
                prev.map((c) => (c.id === comment.id ? comment : c))
            );
            setError(failure.message);
        }
    };

    const commentMap = useMemo(() => {
        const map = {};
        comments.forEach((c) => {
            map[c.id] = c;
        });
        return map;
    }, [comments]);

    const { rootComments, repliesByParent, orphanReplies } = useMemo(() => {
        const roots = [];
        const replies = {};
        const rootIdSet = new Set();
        comments.forEach((c) => {
            if (!c.parent_id) {
                roots.push(c);
                rootIdSet.add(c.id);
            } else {
                if (!replies[c.parent_id]) replies[c.parent_id] = [];
                replies[c.parent_id].push(c);
            }
        });
        const orphans = comments.filter((c) => c.parent_id && !rootIdSet.has(c.parent_id));
        return { rootComments: roots, repliesByParent: replies, orphanReplies: orphans };
    }, [comments]);

    const mentionParticipants = [article?.author, ...comments];

    const renderComment = (item, isReply = false) => {
        const parentComment = item.parent_id ? commentMap[item.parent_id] : null;
        const isReplyingThis = replyTo?.id === item.id;

        return (
            <div key={item.id} className={`vr-comment-item ${isReply ? 'is-reply' : ''}`}>
                <div className="vr-avatar-small">
                    {item.name ? item.name.slice(0, 1).toUpperCase() : 'U'}
                </div>
                <div className="vr-comment-content">
                    <div className="vr-comment-header-row">
                        <div className="vr-comment-user-info">
                            <Link href={path(locale, `/profile/${item.username}`)} className="vr-comment-user-name">
                                {item.name}
                            </Link>
                            <CommentBadges keys={item.badges} locale={locale} />
                            <span className="vr-meta-time">
                                {dateLabel(item.created_at, locale)}
                            </span>
                        </div>
                        <button
                            type="button"
                            className="vr-report-icon-btn"
                            onClick={() => (user ? setReporting(item) : openLoginModal())}
                            title={isArticle ? (isId ? 'Laporkan komentar ini' : 'Report comment') : (isId ? 'Laporkan pesan ini' : 'Report message')}
                            aria-label={isArticle ? (isId ? 'Laporkan komentar' : 'Report comment') : (isId ? 'Laporkan pesan' : 'Report message')}
                        >
                            <NavIcon name="shield" size={13} />
                        </button>
                    </div>

                    {item.parent_id && (
                        <div className="vr-reply-badge">
                            <NavIcon name="cornerDownRight" size={12} />
                            <span>
                                {isId ? 'Membalas' : 'Replying to'}{' '}
                                <strong>@{parentComment?.name || (isId ? 'pengguna' : 'user')}</strong>
                            </span>
                        </div>
                    )}

                    <p className="vr-comment-text"><MentionText body={item.body} mentions={item.mentions} locale={locale} /></p>
                    <CommentImageGallery images={item.images} locale={locale} authorName={item.name} />

                    <div className="vr-comment-actions">
                        <button
                            type="button"
                            className={`vr-comment-action-btn vr-btn-like ${item.liked ? 'liked' : ''}`}
                            onClick={() => like(item)}
                            title={item.liked ? (isId ? 'Batal suka' : 'Unlike') : (isId ? 'Suka' : 'Like')}
                        >
                            <NavIcon name="heart" size={13} />
                            <span className="vr-like-count">
                                {item.likes_count > 0 ? item.likes_count : (isId ? 'Suka' : 'Like')}
                            </span>
                        </button>

                        <button
                            type="button"
                            className={`vr-comment-action-btn vr-btn-reply ${isReplyingThis ? 'active' : ''}`}
                            onClick={() => {
                                if (!user) return openLoginModal();
                                if (isReplyingThis) {
                                    setReplyTo(null);
                                    setReplyBody('');
                                } else {
                                    setReplyTo({ id: item.id, name: item.name });
                                    setReplyBody('');
                                }
                            }}
                            title={isArticle ? (isId ? 'Balas komentar ini' : 'Reply to comment') : (isId ? 'Balas pesan ini' : 'Reply')}
                        >
                            <NavIcon name="messageSquare" size={13} />
                            <span>{isId ? 'Balas' : 'Reply'}</span>
                        </button>
                    </div>

                    {isReplyingThis && (
                        <form
                            className="vr-chat-reply-box"
                            onSubmit={(e) => submit(e, replyTo.id, replyBody)}
                        >
                            <div className="vr-chat-reply-header">
                                <span>
                                    {isId ? 'Membalas' : 'Replying to'} <strong>@{replyTo.name}</strong>
                                </span>
                                <button
                                    type="button"
                                    className="vr-chat-close-btn"
                                    onClick={() => {
                                        setReplyTo(null);
                                        setReplyBody('');
                                    }}
                                    title={isId ? 'Batal' : 'Cancel'}
                                >
                                    <NavIcon name="x" size={13} />
                                </button>
                            </div>
                            <div className="vr-chat-bar">
                                <MentionInput
                                    type="text"
                                    className="vr-chat-input"
                                    placeholder={isId ? `Balas @${replyTo.name}...` : `Reply to @${replyTo.name}...`}
                                    value={replyBody}
                                    onValueChange={setReplyBody}
                                    participants={mentionParticipants}
                                    locale={locale}
                                    autoFocus
                                    autoComplete="off"
                                    required
                                />
                                {replyBody.length > 0 && (
                                    <button
                                        type="button"
                                        className="vr-chat-clear-btn"
                                        onClick={() => setReplyBody('')}
                                        title={isId ? 'Hapus' : 'Clear'}
                                    >
                                        <NavIcon name="x" size={13} />
                                    </button>
                                )}
                                <button
                                    type="submit"
                                    className="vr-chat-send-btn"
                                    disabled={busy || replyBody.trim().length < 2}
                                    title={isId ? 'Kirim Balasan' : 'Send reply'}
                                >
                                    <NavIcon name="send" size={14} />
                                </button>
                            </div>
                        </form>
                    )}
                </div>
            </div>
        );
    };

    return (
        <section className="vob-knowledge-panel" id={isArticle ? 'article-comments' : 'object-discussion'}>
            <div className="vob-knowledge-heading">
                <div className="vob-knowledge-heading-main">
                    <div className="vob-heading-badge-wrap">
                        <h2>{isArticle ? (isId ? 'Komentar Artikel' : 'Article comments') : (isId ? 'Diskusi Objek' : 'Object discussion')}</h2>
                    </div>
                    <p>{isArticle
                        ? (isId ? 'Tanggapi tulisan ini dan berdiskusi dengan pembaca lain' : 'Respond to this article and discuss it with other readers')
                        : (isId ? 'Kirim pesan, opini, dan diskusikan informasi objek ini bersama komunitas' : 'Send messages, share thoughts, and discuss this object with the community')}</p>
                </div>
                <div className="vob-sort-control" role="group" aria-label={isId ? 'Urutkan diskusi' : 'Sort discussion'}>
                    <button
                        type="button"
                        className={sort === 'latest' ? 'active' : ''}
                        aria-pressed={sort === 'latest'}
                        onClick={() => setSort('latest')}
                    >
                        {isId ? 'Terbaru' : 'Latest'}
                    </button>
                    <button
                        type="button"
                        className={sort === 'popular' ? 'active' : ''}
                        aria-pressed={sort === 'popular'}
                        onClick={() => setSort('popular')}
                    >
                        {isId ? 'Populer' : 'Popular'}
                    </button>
                </div>
            </div>

            {error && <p role="alert" className="vob-form-error">{error}</p>}

            {/* Simple Chat Composer */}
            {user ? (
                <form className="vr-chat-composer" onSubmit={(e) => submit(e, null, body)}>
                    <div className="vr-chat-bar">
                        <MentionInput
                            type="text"
                            className="vr-chat-input"
                            id={isArticle ? 'article-comment-input' : 'object-discussion-input'}
                            placeholder={isArticle
                                ? (isId ? 'Tulis komentar tentang artikel ini...' : 'Write a comment about this article...')
                                : (isId ? 'Ketik pesan diskusi tentang objek ini...' : 'Type a discussion message about this object...')}
                            value={body}
                            maxLength={3000}
                            onValueChange={setBody}
                            participants={mentionParticipants}
                            locale={locale}
                            autoComplete="off"
                            required
                        />
                        {body.length > 0 && (
                            <button
                                type="button"
                                className="vr-chat-clear-btn"
                                onClick={() => setBody('')}
                                title={isId ? 'Hapus' : 'Clear'}
                            >
                                <NavIcon name="x" size={13} />
                            </button>
                        )}
                        <CommentImageAttachButton attachments={attachments} locale={locale} disabled={busy} />
                        <button
                            type="submit"
                            className="vr-chat-send-btn"
                            disabled={busy || attachments.processing || body.trim().length < 2}
                            title={isArticle ? (isId ? 'Kirim Komentar' : 'Send comment') : (isId ? 'Kirim Pesan' : 'Send message')}
                        >
                            <NavIcon name="send" size={15} />
                        </button>
                    </div>
                    <CommentImagePreviews attachments={attachments} locale={locale} />
                </form>
            ) : (
                <div className="vr-guest-inline" style={{ marginBottom: 20 }}>
                    <span>{isArticle
                        ? (isId ? 'Masuk untuk menulis komentar dan membalas pembaca lain.' : 'Sign in to comment and reply to other readers.')
                        : (isId ? 'Ingin bergabung dan mengirim pesan dalam diskusi ini?' : 'Want to join and send messages in this discussion?')}</span>
                    <button type="button" onClick={openLoginModal} className="vr-link-bold">
                        {isId ? 'Masuk' : 'Log in'} →
                    </button>
                </div>
            )}

            {/* Chat Messages Stream */}
            <div className="vr-comment-items">
                {rootComments.length || orphanReplies.length ? (
                    <>
                        {rootComments.map((root) => {
                            const replies = repliesByParent[root.id] || [];
                            return (
                                <div key={root.id} className="vr-comment-thread">
                                    {renderComment(root, false)}
                                    {replies.length > 0 && (
                                        <div className="vr-comment-replies">
                                            {replies.map((reply) => renderComment(reply, true))}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                        {orphanReplies.map((orphan) => (
                            <div key={orphan.id} className="vr-comment-thread">
                                {renderComment(orphan, true)}
                            </div>
                        ))}
                    </>
                ) : (
                    <div className="vob-empty-box" style={{ padding: '32px 16px' }}>
                        <div className="vob-empty-icon-wrap" aria-hidden="true">
                            <NavIcon name="comment" size={22} />
                        </div>
                        <h3>{isArticle ? (isId ? 'Belum Ada Komentar' : 'No comments yet') : (isId ? 'Belum Ada Pesan Diskusi' : 'No discussion messages yet')}</h3>
                        <p>
                            {isArticle
                                ? (isId ? 'Jadilah yang pertama menanggapi tulisan ini.' : 'Be the first to respond to this article.')
                                : (isId ? 'Jadilah yang pertama mengirim pesan dan memulai percakapan tentang objek ini!' : 'Be the first to send a message and start the conversation about this object!')}
                        </p>
                    </div>
                )}
            </div>

            {hasMore && (
                <div className="vr-load-more-container" style={{ marginTop: 20 }}>
                    <button
                        type="button"
                        className="vob-btn-action"
                        onClick={() => load(comments.length).catch((failure) => setError(failure.message))}
                    >
                        <NavIcon name="refresh" size={13} />
                        <span>{isArticle ? (isId ? 'Muat Komentar Lainnya' : 'Load more comments') : (isId ? 'Muat Pesan Lainnya' : 'Load more messages')}</span>
                    </button>
                </div>
            )}

            {reporting && (
                <ReportDialog
                    locale={locale}
                    targetType="comment"
                    onClose={() => setReporting(null)}
                    onSubmit={async (reason) => {
                        await api('/backend/reports', 'POST', { target_type: 'comment', target_id: String(reporting.id), reason });
                        setReporting(null);
                    }}
                />
            )}
        </section>
    );
}

function revisionStatusLabel(status, isId) {
    const labels = {
        pending: 'Pending',
        needs_revision: isId ? 'Perlu revisi' : 'Needs revision',
        rejected: isId ? 'Ditolak' : 'Rejected',
    };

    return labels[status] ?? status;
}

export default function ObjectKnowledge({ object, summary, articles, myRevisions = [], reviewFeedback, locale }) {
    const isId = locale === 'id';
    const user = usePage().props.auth?.user;
    const feedback = reviewFeedback ? (typeof reviewFeedback === 'string' ? JSON.parse(reviewFeedback) : reviewFeedback) : null;
    const canResubmit = user?.id === object.submitted_by && ['needs_revision', 'rejected'].includes(object.status);
    const latestOwn = myRevisions.filter((revision, index, all) => all.findIndex((item) => item.article_id === revision.article_id) === index);
    const ownSummaryRevision = object.status === 'verified'
        ? latestOwn.find((revision) => !revision.article_id && revision.status !== 'published')
        : null;
    const ownArticleRevisions = latestOwn.filter((revision) => revision.article_id && revision.status !== 'published');

    return (
        <div className="vob-knowledge-stack">
            {/* Section 1: Tentang Objek */}
            <section className="vob-knowledge-panel" aria-label={isId ? 'Tentang objek' : 'About this object'}>
                <div className="vob-knowledge-heading">
                    <div className="vob-knowledge-heading-main">
                        <div className="vob-heading-badge-wrap">
                            <h2>{isId ? 'Tentang Objek' : 'About this object'}</h2>
                        </div>
                        <p>{isId ? 'Ringkasan ensiklopedia dan informasi faktual yang disusun bersama komunitas' : 'Encyclopedia summary and factual information verified by the community'}</p>
                    </div>

                    {object.status === 'verified' && (
                        user ? (
                            <Link
                                className="vob-btn-action vob-knowledge-action"
                                href={path(locale, `/object/${object.slug}/summary/edit`)}
                                title={ownSummaryRevision
                                    ? (isId ? 'Lihat atau perbaiki usulan ringkasan Anda' : 'View or revise your summary proposal')
                                    : (isId ? 'Usulkan perbaikan atau informasi baru untuk ringkasan objek ini' : 'Suggest an edit or updated information for this object')}
                            >
                                <NavIcon name="edit" size={13} />
                                <span>{ownSummaryRevision
                                    ? (isId ? 'Lihat Usulan' : 'View proposal')
                                    : (isId ? 'Usulkan Revisi' : 'Suggest an edit')}</span>
                            </Link>
                        ) : (
                            <button
                                type="button"
                                className="vob-btn-action vob-knowledge-action"
                                onClick={openLoginModal}
                                title={isId ? 'Masuk untuk mengusulkan revisi' : 'Sign in to suggest an edit'}
                            >
                                <NavIcon name="edit" size={13} />
                                <span>{isId ? 'Usulkan Revisi' : 'Suggest an edit'}</span>
                            </button>
                        )
                    )}
                </div>

                {summary ? (
                    <>
                        <ContentBody revision={summary} />
                        <div className="vob-content-meta">
                            <span className="vob-content-meta-badge">{summary.language_code?.toUpperCase()}</span>
                            <span>·</span>
                            <span>{isId ? 'Kontributor' : 'Contributor'}: <strong>{summary.author?.name}</strong></span>
                            <span>·</span>
                            <span>{dateLabel(summary.published_at, locale)}</span>
                        </div>
                        <ArticleSources sources={summary.sources} locale={locale} />
                    </>
                ) : (
                    <div className="vob-empty-box" style={{ padding: '32px 16px' }}>
                        <h3>{isId ? 'Ringkasan Belum Tersedia' : 'No summary available yet'}</h3>
                        <p>
                            {ownSummaryRevision
                                ? (isId ? 'Ringkasan yang terbit akan tampil di sini setelah disetujui moderator.' : 'The published summary will appear here after moderator approval.')
                                : (isId ? 'Belum ada ringkasan ensiklopedia untuk objek ini. Bantu komunitas dengan mengusulkan ringkasan pertama!' : 'No encyclopedia summary is available for this object yet. Help the community by proposing the first summary!')}
                        </p>
                        {object.status === 'verified' && !ownSummaryRevision && (
                            <div style={{ marginTop: 16 }}>
                                {user ? (
                                    <Link
                                        className="vob-btn-action"
                                        href={path(locale, `/object/${object.slug}/summary/edit`)}
                                    >
                                        <NavIcon name="plus" size={13} />
                                        <span>{isId ? 'Tulis Ringkasan Objek' : 'Write summary'}</span>
                                    </Link>
                                ) : (
                                    <button
                                        type="button"
                                        className="vob-btn-action"
                                        onClick={openLoginModal}
                                    >
                                        <NavIcon name="plus" size={13} />
                                        <span>{isId ? 'Tulis Ringkasan Objek' : 'Write summary'}</span>
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                )}

                {ownSummaryRevision && (
                    <div className="vob-summary-submission" role="status">
                        <div className="vob-summary-submission-copy">
                            <span className="vob-submission-private">{isId ? 'Hanya Anda' : 'Only you'}</span>
                            <strong>{isId ? 'Usulan ringkasan objek' : 'Object summary proposal'}</strong>
                            <span className={`vob-status-pill vob-status-${ownSummaryRevision.status}`}>
                                {revisionStatusLabel(ownSummaryRevision.status, isId)}
                            </span>
                            {ownSummaryRevision.excerpt && <p>{ownSummaryRevision.excerpt}</p>}
                            {ownSummaryRevision.review_reason && (
                                <p className="vob-submission-reason">
                                    <strong>{isId ? 'Catatan moderator:' : 'Moderator note:'}</strong> {ownSummaryRevision.review_reason}
                                </p>
                            )}
                        </div>
                        <Link className="vob-submission-link" href={path(locale, `/object/${object.slug}/summary/edit`)}>
                            {ownSummaryRevision.status === 'pending'
                                ? (isId ? 'Lihat usulan' : 'View proposal')
                                : (isId ? 'Perbaiki usulan' : 'Revise proposal')} <span aria-hidden="true">→</span>
                        </Link>
                    </div>
                )}

                {feedback && canResubmit && (
                    <div className="vob-moderator-feedback-box" role="status">
                        <strong>{isId ? 'Catatan Moderator' : 'Moderator feedback'}:</strong>
                        <p>{feedback.reason}</p>
                    </div>
                )}

                {canResubmit && (
                    <div className="vob-resubmit-bar">
                        <Link
                            className="vob-btn-action"
                            href={path(locale, `/object/${object.slug}/submission/edit`)}
                        >
                            <NavIcon name="edit" size={13} />
                            <span>{isId ? 'Perbaiki Pengajuan Objek' : 'Revise object submission'}</span>
                        </Link>
                    </div>
                )}
            </section>

            {/* Section 2: Tulisan Komunitas */}
            {object.status === 'verified' && (
                <>
                    <section className="vob-knowledge-panel vob-article-section" aria-label={isId ? 'Tulisan komunitas' : 'Community articles'}>
                        <div className="vob-knowledge-heading">
                            <div className="vob-knowledge-heading-main">
                                <div className="vob-heading-badge-wrap">
                                    <h2>{isId ? 'Tulisan Komunitas' : 'Community articles'}</h2>
                                    <span className="vob-badge-pill" aria-live="polite">
                                        {articles?.total ?? 0} {isId ? 'Artikel' : 'Articles'}
                                    </span>
                                    {ownArticleRevisions.length > 0 && (
                                        <span className="vob-submission-count">
                                            {ownArticleRevisions.length} {isId ? 'Usulan Anda' : 'Your proposals'}
                                        </span>
                                    )}
                                </div>
                                <p>{isId ? 'Ulasan dan pengalaman dari anggota komunitas' : 'Reviews and experiences from the community'}</p>
                            </div>

                            {user && (
                                <Link className="vob-knowledge-action" href={path(locale, `/object/${object.slug}/write`)}>
                                    {isId ? 'Tulis artikel' : 'Write an article'}
                                </Link>
                            )}
                        </div>

                        {ownArticleRevisions.length > 0 || articles?.data?.length ? (
                            <div className="vob-articles-list">
                                {ownArticleRevisions.map((revision) => (
                                    <article key={`submission-${revision.id}`} className="vob-article-row vob-article-row-submission">
                                        <Link className="vob-article-row-link" href={path(locale, `/article/${revision.article_id}/edit`)}>
                                            <div className="vob-article-row-labels">
                                                <span className="vob-article-row-type">
                                                    {revision.article?.type === 'experience'
                                                        ? (isId ? 'Pengalaman' : 'Experience')
                                                        : (isId ? 'Informatif' : 'Informative')}
                                                </span>
                                                <span className="vob-article-row-language">{revision.language_code?.toUpperCase()}</span>
                                                <span className="vob-article-row-private">{isId ? 'Hanya Anda' : 'Only you'}</span>
                                            </div>
                                            <div className="vob-article-row-title-line">
                                                <h3>{revision.title}</h3>
                                                <span className={`vob-status-pill vob-status-${revision.status}`}>
                                                    {revisionStatusLabel(revision.status, isId)}
                                                </span>
                                            </div>
                                            {revision.excerpt && <p className="vob-article-row-excerpt">{revision.excerpt}</p>}
                                            {revision.review_reason && (
                                                <p className="vob-submission-reason">
                                                    <strong>{isId ? 'Catatan moderator:' : 'Moderator note:'}</strong> {revision.review_reason}
                                                </p>
                                            )}
                                            <div className="vob-article-row-byline">
                                                <span>{revision.article?.published_revision_id
                                                    ? (isId ? 'Usulan revisi artikel' : 'Article revision proposal')
                                                    : (isId ? 'Usulan tulisan baru' : 'New article proposal')}</span>
                                                <span aria-hidden="true">·</span>
                                                <time dateTime={revision.created_at}>{dateLabel(revision.created_at, locale)}</time>
                                                <span className="vob-submission-link">
                                                    {revision.status === 'pending'
                                                        ? (isId ? 'Lihat usulan' : 'View proposal')
                                                        : (isId ? 'Perbaiki usulan' : 'Revise proposal')} <span aria-hidden="true">→</span>
                                                </span>
                                            </div>
                                        </Link>
                                    </article>
                                ))}
                                {articles?.data?.map((article) => (
                                    <article key={article.id} className="vob-article-row">
                                        <Link className="vob-article-row-link" href={path(article.published_revision?.language_code ?? locale, `/article/${article.id}`)}>
                                            <div className="vob-article-row-labels">
                                                <span className="vob-article-row-type">
                                                    {article.type === 'experience'
                                                        ? (isId ? 'Pengalaman' : 'Experience')
                                                        : (isId ? 'Informatif' : 'Informative')}
                                                </span>
                                                <span className="vob-article-row-language">
                                                    {article.published_revision?.language_code?.toUpperCase()}
                                                </span>
                                            </div>
                                            <h3>{article.published_revision?.title}</h3>
                                            {article.published_revision?.excerpt && (
                                                <p className="vob-article-row-excerpt">{article.published_revision.excerpt}</p>
                                            )}
                                            <div className="vob-article-row-byline">
                                                <span className="vob-article-row-avatar" aria-hidden="true">
                                                    {article.author?.name?.trim().charAt(0).toLocaleUpperCase() || 'V'}
                                                </span>
                                                <span>{article.author?.name ?? (isId ? 'Anggota Votmee' : 'Votmee member')}</span>
                                                <span aria-hidden="true">·</span>
                                                <time dateTime={article.published_revision?.published_at}>
                                                    {dateLabel(article.published_revision?.published_at, locale)}
                                                </time>
                                            </div>
                                        </Link>
                                    </article>
                                ))}
                            </div>
                        ) : (
                            <div className="vob-empty-box" style={{ padding: '32px 16px' }}>
                                <h3>{isId ? 'Belum Ada Artikel Terbit' : 'No published articles yet'}</h3>
                                <p>
                                    {isId
                                        ? 'Jadilah yang pertama menuliskan ulasan, opini, atau pengalaman pribadi Anda tentang objek ini.'
                                        : 'Be the first to share your thoughts, informative review, or experience regarding this object.'}
                                </p>
                            </div>
                        )}

                        {articles?.last_page > 1 && (
                            <nav aria-label={isId ? 'Halaman artikel' : 'Article pages'} className="vob-article-pages">
                                {articles.prev_page_url && (
                                    <Link href={articles.prev_page_url} preserveScroll className="vob-page-nav-btn">
                                        ← {isId ? 'Sebelumnya' : 'Previous'}
                                    </Link>
                                )}
                                <span className="vob-page-nav-info">{articles.current_page} / {articles.last_page}</span>
                                {articles.next_page_url && (
                                    <Link href={articles.next_page_url} preserveScroll className="vob-page-nav-btn">
                                        {isId ? 'Berikutnya' : 'Next'} →
                                    </Link>
                                )}
                            </nav>
                        )}
                    </section>

                    {/* Section 3: Diskusi Objek */}
                    <CommunityDiscussion object={object} locale={locale} />
                </>
            )}

        </div>
    );
}
