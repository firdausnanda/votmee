import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { api, categoryHashtag, CoinIcon, dateLabel, dictionary, Layout, numberLabel, objectImageSrcSet, objectImageUrl, OfficialBadge, openLoginModal, PageHead, path, useSavedRanking } from './Shared';
import { CommentImageAttachButton, CommentImageGallery, CommentImagePreviews, commentPayload, useCommentImages } from './CommentImages';
import { CommentBadges } from './Achievements';
import { AvatarFrame, NameFlair } from './AvatarFrame';
import { MentionInput, MentionText } from './Mentions';
import ReportDialog from './ReportDialog';
import ShareModal from './ShareModal';

function RankingIcon({ name, size = 16, className = '', fill = 'none' }) {
    const icons = {
        trophy: (
            <path d="M7 3h10v8a5 5 0 0 1-10 0V3ZM7 5H4v3a4 4 0 0 0 4 4M17 5h3v3a4 4 0 0 1-4 4M12 16v4m-4 1h8" />
        ),
        share: (
            <>
                <circle cx="18" cy="5" r="3" />
                <circle cx="6" cy="12" r="3" />
                <circle cx="18" cy="19" r="3" />
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
            </>
        ),
        bookmark: (
            <path d="M5 5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16l-7-3.5L5 21V5Z" />
        ),
        flag: (
            <>
                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
                <line x1="4" y1="22" x2="4" y2="15" />
            </>
        ),
        shieldAlert: (
            <>
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </>
        ),
        report: (
            <>
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </>
        ),
        filter: (
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
        ),
        check: <polyline points="20 6 9 17 4 12" />,
        arrowLeft: (
            <>
                <line x1="19" y1="12" x2="5" y2="12" />
                <polyline points="12 19 5 12 12 5" />
            </>
        ),
        plus: (
            <>
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </>
        ),
        minus: <line x1="5" y1="12" x2="19" y2="12" />,
        x: (
            <>
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </>
        ),
        wallet: (
            <>
                <rect x="3" y="5" width="18" height="15" rx="2" />
                <path d="M3 9h18M15 15h3" />
            </>
        ),
        heart: (
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
        ),
        messageSquare: (
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
        ),
        cornerDownRight: (
            <>
                <polyline points="15 10 20 15 15 20" />
                <path d="M4 4v7a4 4 0 0 0 4 4h12" />
            </>
        ),
        sparkle: (
            <path d="M12 2l2.4 7.2L21.6 12l-7.2 2.4L12 21.6l-2.4-7.2L2.4 12l7.2-2.4L12 2z" />
        ),
        clock: (
            <>
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
            </>
        ),
        flame: (
            <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z" />
        ),
        chevronDown: <polyline points="6 9 12 15 18 9" />,
        chevronUp: <polyline points="18 15 12 9 6 15" />,
        send: (
            <>
                <line x1="22" y1="2" x2="11" y2="13" />
                <polygon points="22 2 15 22 11 13 2 9 22 2" />
            </>
        ),
    };

    return (
        <svg
            className={className}
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill={fill}
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            {icons[name] || null}
        </svg>
    );
}

export default function Ranking({ locale, categories, leaders, post, isSaved = false, scores, comments, total_comments_count }) {
    const t = dictionary(locale);
    const user = usePage().props.auth?.user;
    const isId = locale === 'id';
    const totalComments = total_comments_count ?? comments.length;

    const [selected, setSelected] = useState(null);
    const [isSupportClosing, setIsSupportClosing] = useState(false);
    const [amount, setAmount] = useState(1);
    const [balance, setBalance] = useState(null);
    const returnSupportHandled = useRef(false);
    const [supportKey, setSupportKey] = useState(crypto.randomUUID());
    const [localScores, setLocalScores] = useState(scores);
    const [boostedEntryId, setBoostedEntryId] = useState(null);
    const [boostedAmount, setBoostedAmount] = useState(0);
    const [boostSeq, setBoostSeq] = useState(0);

    useEffect(() => {
        setLocalScores(scores);
    }, [scores]);
    const [comment, setComment] = useState('');
    const commentAttachments = useCommentImages(locale);
    const [reporting, setReporting] = useState(null);
    const [busy, setBusy] = useState(false);
    const [toastMessage, setToastMessage] = useState('');
    const [toastType, setToastType] = useState('info');
    const { saved, toggle: toggleSaved } = useSavedRanking(post, isSaved);
    const [copied, setCopied] = useState(false);
    const [shareOpen, setShareOpen] = useState(false);

    // Comments interactive states: sort, likes, active reply, collapsed threads, and pagination
    const [commentSort, setCommentSort] = useState('latest');
    const [sortDropdownOpen, setSortDropdownOpen] = useState(false);
    const sortDropdownRef = useRef(null);
    const [collapsedThreads, setCollapsedThreads] = useState({});
    const [recentlyLiked, setRecentlyLiked] = useState({});
    const [closingReplyId, setClosingReplyId] = useState(null);
    const [commentClearing, setCommentClearing] = useState(false);
    const [visibleRootCount, setVisibleRootCount] = useState(10);
    const [expandedReplyThreads, setExpandedReplyThreads] = useState({});

    const commentsSectionRef = useRef(null);
    const [highlightComments, setHighlightComments] = useState(false);
    const candidatesSectionRef = useRef(null);
    const [highlightCandidates, setHighlightCandidates] = useState(false);

    const scrollToCandidates = () => {
        if (candidatesSectionRef.current) {
            candidatesSectionRef.current.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setHighlightCandidates(true);
            setTimeout(() => setHighlightCandidates(false), 1600);
        }
    };

    const scrollToComments = () => {
        if (commentsSectionRef.current) {
            commentsSectionRef.current.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setHighlightComments(true);
            setTimeout(() => setHighlightComments(false), 1600);
            const input = commentsSectionRef.current.querySelector('#comment');
            if (input) {
                setTimeout(() => {
                    input.focus();
                }, 600);
            }
        }
    };

    const handleSortChange = (newSort) => {
        setCommentSort(newSort);
        setVisibleRootCount(10);
        setSortDropdownOpen(false);
    };

    useEffect(() => {
        if (!sortDropdownOpen) return;

        const handleOutsideClick = (event) => {
            if (sortDropdownRef.current && !sortDropdownRef.current.contains(event.target)) {
                setSortDropdownOpen(false);
            }
        };

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                setSortDropdownOpen(false);
            }
        };

        document.addEventListener('mousedown', handleOutsideClick);
        document.addEventListener('touchstart', handleOutsideClick);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('mousedown', handleOutsideClick);
            document.removeEventListener('touchstart', handleOutsideClick);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [sortDropdownOpen]);

    const [likesState, setLikesState] = useState(() => {
        const initial = {};
        comments.forEach((c) => {
            initial[c.id] = {
                liked: Boolean(c.liked),
                count: Number(c.likes_count ?? 0),
            };
        });
        return initial;
    });

    const [replyingTo, setReplyingTo] = useState(null);
    const [replyBody, setReplyBody] = useState('');

    const showToast = (msg, type = 'info') => {
        setToastMessage(msg);
        setToastType(type);
        window.setTimeout(() => setToastMessage(''), 3000);
    };

    const toggleSave = async () => {
        try {
            const isNowSaved = await toggleSaved();
            showToast(
                isNowSaved
                    ? isId
                        ? 'Ranking disimpan ke koleksi'
                        : 'Saved to collection'
                    : isId
                    ? 'Ranking dihapus dari simpanan'
                    : 'Removed from saved',
                'success'
            );
        } catch (error) {
            showToast(error.message, 'error');
        }
    };

    const toggleLike = async (commentId) => {
        if (!user) {
            openLoginModal();
            return;
        }

        const current = likesState[commentId] || { liked: false, count: 0 };
        const nextLiked = !current.liked;
        const nextCount = nextLiked ? current.count + 1 : Math.max(0, current.count - 1);

        // Micro-interaction bounce animation
        if (nextLiked) {
            setRecentlyLiked((prev) => ({ ...prev, [commentId]: true }));
            window.setTimeout(() => {
                setRecentlyLiked((prev) => ({ ...prev, [commentId]: false }));
            }, 600);
        }

        // Optimistic UI update
        setLikesState((prev) => ({
            ...prev,
            [commentId]: { liked: nextLiked, count: nextCount },
        }));

        try {
            const res = await api(`/backend/comments/${commentId}/like`, 'POST');
            setLikesState((prev) => ({
                ...prev,
                [commentId]: { liked: res.liked, count: res.likes_count },
            }));
        } catch (error) {
            setLikesState((prev) => ({
                ...prev,
                [commentId]: current,
            }));
            showToast(error.message, 'error');
        }
    };

    const toggleCollapse = (rootId) => {
        setCollapsedThreads((prev) => ({
            ...prev,
            [rootId]: !prev[rootId],
        }));
    };

    const handleCancelReply = () => {
        if (!replyingTo) return;
        const targetId = replyingTo.id;
        setClosingReplyId(targetId);
        window.setTimeout(() => {
            setReplyingTo(null);
            setClosingReplyId(null);
            setReplyBody('');
        }, 220);
    };

    const handleCancelComment = () => {
        if (!comment) return;
        setCommentClearing(true);
        window.setTimeout(() => {
            setComment('');
            setCommentClearing(false);
        }, 180);
    };

    const beginSupport = async (entry, startingAmount = 1) => {
        if (!['verified', 'merged'].includes(entry.object?.status)) return;
        if (!user) {
            openLoginModal();
            return;
        }
        setIsSupportClosing(false);
        setSelected(entry);
        setAmount(startingAmount);
        try {
            const wallet = await api('/backend/wallet');
            setBalance(wallet.balance);
        } catch {
            setBalance(null);
        }
    };

    useEffect(() => {
        if (returnSupportHandled.current || !user) return;
        returnSupportHandled.current = true;

        const params = new URLSearchParams(window.location.search);
        const entryId = Number(params.get('support'));
        const entry = post.entries.find((candidate) => candidate.id === entryId);
        if (!entry || !Number.isSafeInteger(entryId)) return;

        const requestedAmount = Number(params.get('amount'));
        beginSupport(entry, Number.isSafeInteger(requestedAmount) && requestedAmount > 0 ? requestedAmount : 1);
    }, [post.entries, user]);

    const walletUrl = selected
        ? `${path(locale, '/wallet')}?${new URLSearchParams({ ranking: post.slug, entry: String(selected.id), amount: String(amount) })}`
        : path(locale, '/wallet');
    const needsPoints = balance !== null && balance < amount;

    const closeSupport = () => {
        if (!selected || isSupportClosing) return;

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            setSelected(null);
            return;
        }

        setIsSupportClosing(true);
    };

    const submitSupport = async (event) => {
        event.preventDefault();
        if (!selected || busy || isSupportClosing) return;
        const entryId = selected.id;
        const pts = amount;
        setBusy(true);
        try {
            await api('/backend/support', 'POST', {
                ranking_entry_id: entryId,
                amount: pts,
                idempotency_key: supportKey,
            });

            // Optimistic update so the percentage bar surges instantly
            setLocalScores((prev) => {
                const current = prev[entryId] || { points: 0, supporters: 0 };
                return {
                    ...prev,
                    [entryId]: {
                        points: Number(current.points ?? 0) + pts,
                        supporters: Number(current.supporters ?? 0) + 1,
                    },
                };
            });

            setBoostedEntryId(entryId);
            setBoostedAmount(pts);
            setBoostSeq((current) => current + 1);

            window.setTimeout(() => {
                setBoostedEntryId((current) => (current === entryId ? null : current));
            }, 2200);

            closeSupport();
            setSupportKey(crypto.randomUUID());
            showToast(
                isId
                    ? `Dukungan ${pts} poin berhasil diberikan!`
                    : `Supported with ${pts} points!`,
                'success'
            );

            router.reload({
                only: ['scores', 'walletBalance'],
                preserveScroll: true,
            });
        } catch (error) {
            showToast(error.message, 'error');
            try {
                const wallet = await api('/backend/wallet');
                setBalance(wallet.balance);
            } catch {
                setBalance(null);
            }
        } finally {
            setBusy(false);
        }
    };

    const submitComment = async (event) => {
        event.preventDefault();
        if (busy || commentAttachments.processing || comment.trim().length < 2) return;
        setBusy(true);
        try {
            await api(`/backend/rankings/${post.slug}/comments`, 'POST', commentPayload({ body: comment }, commentAttachments.images));
            setComment('');
            commentAttachments.clear();
            showToast(isId ? 'Komentar terkirim!' : 'Comment posted!', 'success');
            router.reload();
        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            setBusy(false);
        }
    };

    const submitReply = async (event) => {
        event.preventDefault();
        if (!replyingTo || busy || replyBody.trim().length < 2) return;
        setBusy(true);
        try {
            await api(`/backend/rankings/${post.slug}/comments`, 'POST', {
                body: replyBody,
                parent_id: replyingTo.id,
            });
            setReplyBody('');
            setReplyingTo(null);
            setClosingReplyId(null);
            showToast(isId ? 'Balasan berhasil dikirim!' : 'Reply posted!', 'success');
            router.reload();
        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            setBusy(false);
        }
    };

    const submitReport = async (reason) => {
        await api('/backend/reports', 'POST', { ...reporting, reason });
        setReporting(null);
        showToast(isId ? 'Laporan berhasil dikirim.' : 'Report submitted.', 'success');
    };

    const rankingUrl = typeof window !== 'undefined' ? window.location.href : '';
    const shareText = isId
        ? `Lihat ranking "${post.title}" di Votmee!`
        : `Check out the ranking "${post.title}" on Votmee!`;

    const totalPoints = post.entries.reduce(
        (sum, entry) => sum + Number(localScores[entry.id]?.points ?? 0),
        0
    );

    // Sorted candidates by points (descending)
    const sortedEntries = [...post.entries].sort((a, b) => {
        const pointsA = Number(localScores[a.id]?.points ?? 0);
        const pointsB = Number(localScores[b.id]?.points ?? 0);
        if (pointsB !== pointsA) return pointsB - pointsA;
        return a.position - b.position;
    });

    // Build comment map and hierarchy for threaded replies
    const commentMap = Object.fromEntries(comments.map((c) => [c.id, c]));
    const mentionParticipants = [post.creator, ...comments];

    // Root comments: comments with no parent_id or whose parent is outside the current window
    const rootComments = comments.filter((c) => !c.parent_id || !commentMap[c.parent_id]);

    // Group child replies by parent_id
    const repliesByParent = {};
    comments.forEach((c) => {
        if (c.parent_id && commentMap[c.parent_id]) {
            if (!repliesByParent[c.parent_id]) {
                repliesByParent[c.parent_id] = [];
            }
            repliesByParent[c.parent_id].push(c);
        }
    });

    // Dynamic sort for root comments (latest vs top popular)
    const sortedRootComments = [...rootComments].sort((a, b) => {
        if (commentSort === 'popular') {
            const likesA = likesState[a.id]?.count ?? Number(a.likes_count ?? 0);
            const likesB = likesState[b.id]?.count ?? Number(b.likes_count ?? 0);
            if (likesB !== likesA) return likesB - likesA;
        }
        return new Date(b.created_at) - new Date(a.created_at);
    });

    const renderCommentCard = (item, isReply = false) => {
        const isAuthor = item.username === post.creator?.username;
        const parentComment = item.parent_id ? commentMap[item.parent_id] : null;
        const likeInfo = likesState[item.id] || {
            liked: false,
            count: Number(item.likes_count ?? 0),
        };
        const isPopping = recentlyLiked[item.id];
        const isReplying = replyingTo?.id === item.id || closingReplyId === item.id;
        const isClosingReply = closingReplyId === item.id;

        return (
            <div
                key={item.id}
                className={`vr-comment-item ${isReply ? 'is-reply' : ''} ${isAuthor ? 'is-thread-creator' : ''}`}
            >
                <AvatarFrame frame={item.avatar_frame} locale={locale} size="sm" className="vr-comment-avatar-frame">
                    <div className={`vr-avatar-small ${isAuthor ? 'avatar-creator' : ''}`}>
                        {item.avatar_url
                            ? <img src={`${item.avatar_url}&size=small`} alt="" loading="lazy" decoding="async" />
                            : (item.name ? item.name.slice(0, 1).toUpperCase() : 'U')}
                    </div>
                </AvatarFrame>
                <div className="vr-comment-content">
                    <div className="vr-comment-header-row">
                        <div className="vr-comment-user-info">
                            <NameFlair flair={item.name_flair} locale={locale}>
                                <Link
                                    href={path(locale, `/profile/${item.username}`)}
                                    className="vr-comment-user-name"
                                >
                                    {item.name}
                                </Link>
                            </NameFlair>
                            <CommentBadges keys={item.badges} locale={locale} />
                            {isAuthor && (
                                <span
                                    className="vr-creator-badge"
                                    title={isId ? 'Pembuat ranking ini' : 'Author of this ranking'}
                                >
                                    <span>{isId ? 'Pembuat Utas' : 'Author'}</span>
                                </span>
                            )}
                            <span className="vr-meta-time">
                                {dateLabel(item.created_at, locale)}
                            </span>
                        </div>

                        <button
                            type="button"
                            className="vr-report-icon-btn"
                            onClick={() =>
                                user
                                    ? setReporting({
                                          target_type: 'comment',
                                          target_id: String(item.id),
                                      })
                                    : openLoginModal()
                            }
                            title={t.report}
                            aria-label="Report comment"
                        >
                            <RankingIcon name="shieldAlert" size={13} />
                        </button>
                    </div>

                    {/* Reply Reference Indicator */}
                    {parentComment && (
                        <div className="vr-reply-badge">
                            <RankingIcon name="cornerDownRight" size={12} />
                            <span>
                                {isId ? 'Membalas' : 'Replying to'}{' '}
                                <strong>@{parentComment.name}</strong>
                            </span>
                        </div>
                    )}

                    <p className="vr-comment-text"><MentionText body={item.body} mentions={item.mentions} locale={locale} /></p>
                    <CommentImageGallery images={item.images} locale={locale} authorName={item.name} />

                    {/* Like & Reply Action Row with Micro-Interactions */}
                    <div className="vr-comment-actions">
                        <button
                            type="button"
                            className={`vr-comment-action-btn vr-btn-like ${likeInfo.liked ? 'liked' : ''} ${
                                isPopping ? 'like-popping' : ''
                            }`}
                            onClick={() => toggleLike(item.id)}
                            title={likeInfo.liked ? (isId ? 'Batal suka' : 'Unlike') : (isId ? 'Suka' : 'Like')}
                        >
                            <RankingIcon
                                name="heart"
                                size={14}
                                fill={likeInfo.liked ? 'currentColor' : 'none'}
                                className={isPopping ? 'heart-icon-pop' : ''}
                            />
                            <span className="vr-like-count">
                                {likeInfo.count > 0 ? likeInfo.count : (isId ? 'Suka' : 'Like')}
                            </span>
                        </button>

                        <button
                            type="button"
                            className={`vr-comment-action-btn vr-btn-reply ${
                                replyingTo?.id === item.id ? 'active' : ''
                            }`}
                            onClick={() => {
                                if (!user) {
                                    openLoginModal();
                                    return;
                                }
                                if (replyingTo?.id === item.id) {
                                    handleCancelReply();
                                } else {
                                    setClosingReplyId(null);
                                    setReplyingTo(item);
                                    setReplyBody('');
                                }
                            }}
                            title={isId ? 'Balas komentar' : 'Reply to comment'}
                        >
                            <RankingIcon name="messageSquare" size={14} />
                            <span>{isId ? 'Balas' : 'Reply'}</span>
                        </button>
                    </div>

                    {/* Simple Message-Style Inline Reply Box */}
                    {isReplying && (
                        <form
                            className={`vr-chat-reply-box ${isClosingReply ? 'is-closing' : ''}`}
                            onSubmit={submitReply}
                        >
                            <div className="vr-chat-reply-header">
                                <span>
                                    {isId ? 'Membalas' : 'Replying to'} <strong>@{item.name}</strong>
                                </span>
                                <button
                                    type="button"
                                    className="vr-chat-close-btn"
                                    onClick={handleCancelReply}
                                    aria-label="Cancel reply"
                                    title={isId ? 'Batal' : 'Cancel'}
                                >
                                    <RankingIcon name="x" size={13} />
                                </button>
                            </div>
                            <div className="vr-chat-bar">
                                <MentionInput
                                    type="text"
                                    className="vr-chat-input"
                                    placeholder={
                                        isId
                                            ? `Balas @${item.name}...`
                                            : `Reply to @${item.name}...`
                                    }
                                    value={replyBody}
                                    onValueChange={setReplyBody}
                                    participants={mentionParticipants}
                                    locale={locale}
                                    autoFocus
                                    autoComplete="off"
                                    required
                                />
                                <button
                                    type="submit"
                                    className="vr-chat-send-btn"
                                    disabled={busy || replyBody.trim().length < 2 || isClosingReply}
                                    title={isId ? 'Kirim Balasan' : 'Send Reply'}
                                >
                                    <RankingIcon name="send" size={15} />
                                </button>
                            </div>
                        </form>
                    )}
                </div>
            </div>
        );
    };

    return (
        <Layout locale={locale} categories={categories} leaders={leaders} active="ranking">
            <PageHead
                title={`${post.title} - Ranking Votmee`}
                locale={locale}
                description={post.criteria.slice(0, 155)}
            />

            <div className="vr-detail-page">

            {/* Simple Top Navigation */}
            <div className="vr-simple-nav">
                <Link href={path(locale)} className="vr-back-link">
                    <RankingIcon name="arrowLeft" size={13} />
                    <span>{isId ? 'Kembali ke Feed' : 'Back to Feed'}</span>
                </Link>
                <span className="vr-nav-separator">/</span>
                <Link
                    href={path(locale, `/explore?category=${post.category?.slug}`)}
                    className="vr-nav-category"
                >
                    {post.category?.name || (isId ? 'Kategori' : 'Category')}
                </Link>
            </div>

            {/* Clean Ranking Header Card */}
            <article className="vr-card vr-header-card">
                <div className="vr-header-meta">
                    <div className="vr-meta-left">
                        <Link
                            href={path(locale, `/explore?category=${post.category?.slug}`)}
                            className="vr-badge-category"
                        >
                            {categoryHashtag(post.category)}
                        </Link>
                        <span className="vr-meta-dot">·</span>
                        <span className="vr-meta-time">{dateLabel(post.published_at, locale)}</span>
                    </div>

                    <div className="vr-meta-actions">
                        <button
                            type="button"
                            className={`vr-action-btn ${saved ? 'active' : ''}`}
                            onClick={toggleSave}
                            title={saved ? (isId ? 'Tersimpan di koleksi' : 'Saved in collection') : (isId ? 'Simpan ranking' : 'Save ranking')}
                            aria-label={saved ? 'Tersimpan' : 'Simpan'}
                        >
                            <RankingIcon name="bookmark" size={14} fill={saved ? 'currentColor' : 'none'} />
                        </button>

                        <button
                            type="button"
                            className={`vr-action-btn vr-btn-share-trigger ${shareOpen ? 'active' : ''}`}
                            onClick={() => setShareOpen(true)}
                            title={isId ? 'Bagikan ranking' : 'Share ranking'}
                            aria-label={isId ? 'Bagikan ranking' : 'Share ranking'}
                        >
                            <RankingIcon name="share" size={14} />
                        </button>

                        <button
                            type="button"
                            className="vr-action-btn vr-btn-flag"
                            onClick={() =>
                                user
                                    ? setReporting({ target_type: 'ranking_post', target_id: post.id })
                                    : openLoginModal()
                            }
                            title={t.report}
                            aria-label="Report"
                        >
                            <RankingIcon name="shieldAlert" size={13} />
                        </button>
                    </div>
                </div>

                <h1 className="vr-title">{post.title}</h1>

                {post.criteria && (
                    <p className="vr-criteria">{post.criteria}</p>
                )}

                <div className="vr-byline">
                    <div className="vr-author">
                        <AvatarFrame frame={post.creator?.avatar_frame} locale={locale} size="sm">
                            <div className="vr-author-avatar">
                                {post.creator?.avatar_url
                                    ? <img src={`${post.creator.avatar_url}&size=small`} alt="" decoding="async" />
                                    : (post.creator?.name ? post.creator.name.slice(0, 1).toUpperCase() : 'V')}
                            </div>
                        </AvatarFrame>
                        <Link
                            href={path(locale, `/profile/${post.creator?.username}`)}
                            className="vr-author-name"
                        >
                            {post.creator?.name}
                        </Link>
                        <OfficialBadge post={post} locale={locale} />
                    </div>

                    <div className="vr-stats-inline">
                        <button
                            type="button"
                            className="vr-stat-btn vr-stat-points-btn"
                            onClick={scrollToCandidates}
                            title={isId ? 'Lihat peringkat kandidat' : 'View candidate rankings'}
                            aria-label={isId ? `Lihat peringkat dengan ${numberLabel(totalPoints, locale)} ${t.points}` : `View rankings with ${numberLabel(totalPoints, locale)} ${t.points}`}
                        >
                            <RankingIcon name="trophy" size={13} className="vr-stat-icon" />
                            <span><strong>{numberLabel(totalPoints, locale)}</strong> {t.points}</span>
                        </button>
                        <span className="vr-meta-dot">·</span>
                        <button
                            type="button"
                            className="vr-stat-btn vr-stat-comments-btn"
                            onClick={scrollToComments}
                            title={isId ? 'Lihat komentar & diskusi' : 'View comments & discussion'}
                            aria-label={isId ? `Lihat ${totalComments} komentar` : `View ${totalComments} comments`}
                        >
                            <RankingIcon name="messageSquare" size={13} className="vr-stat-icon" />
                            <span><strong>{totalComments}</strong> {t.comments}</span>
                        </button>
                    </div>
                </div>
            </article>

            <div className="vr-detail-columns">
            <section
                ref={candidatesSectionRef}
                id="candidates-section"
                className={`vr-card vr-candidates-card ${highlightCandidates ? 'vr-highlight-pulse' : ''}`}
                aria-labelledby="vr-candidates-title"
            >
                <div className="vr-section-head">
                    <div className="vr-candidate-heading">
                        <h2 id="vr-candidates-title" className="vr-section-title">
                            {isId ? 'Peringkat Kandidat' : 'Candidate Rankings'}
                        </h2>
                        <p>{isId ? 'Urut berdasarkan dukungan komunitas' : 'Sorted by community support'}</p>
                    </div>
                    <span className="vr-count-tag">{post.entries.length} {isId ? 'kandidat' : 'candidates'}</span>
                </div>

                <div className="vr-candidate-list">
                    {sortedEntries.map((entry, index) => {
                        const score = localScores[entry.id];
                        const points = Number(score?.points ?? 0);
                        const percent = totalPoints ? Math.round((points / totalPoints) * 100) : 0;
                        const rank = index + 1;
                        const isBoosted = boostedEntryId === entry.id;
                        const canReceiveSupport = ['verified', 'merged'].includes(entry.object?.status);
                        const unavailableLabel = entry.object?.status === 'rejected'
                            ? (isId ? 'Tidak tersedia' : 'Unavailable')
                            : (isId ? 'Menunggu verifikasi' : 'Awaiting verification');

                        return (
                            <div
                                key={entry.id}
                                className={`vr-candidate-row ${isBoosted ? 'is-boosted' : ''}`}
                            >
                                <div className="vr-candidate-main">
                                    <span className={`vr-rank-badge ${rank <= 3 ? `rank-${rank}` : ''}`}>
                                        {rank}
                                    </span>

                                    <Link
                                        href={path(locale, `/object/${entry.object.slug}`)}
                                        className="vr-thumb-link"
                                        aria-label={entry.object.canonical_name}
                                    >
                                        {entry.object.image_path
                                            ? <img src={objectImageUrl(entry.object, 'small')} srcSet={objectImageSrcSet(entry.object)} sizes="44px" alt="" className="vr-thumb-img" loading="lazy" />
                                            : <span className="vr-thumb-placeholder" aria-hidden="true">{entry.object.canonical_name.slice(0, 1).toUpperCase()}</span>}
                                    </Link>

                                    <div className="vr-info-col">
                                        <div className="vr-name-row">
                                            <Link
                                                href={path(locale, `/object/${entry.object.slug}`)}
                                                className="vr-candidate-name"
                                            >
                                                {entry.object.canonical_name}
                                            </Link>
                                            {entry.object.status === 'verified' && (
                                                <span
                                                    className="vr-verified-check"
                                                    title={isId ? 'Terverifikasi' : 'Verified'}
                                                >
                                                    <RankingIcon name="check" size={11} />
                                                </span>
                                            )}
                                        </div>

                                        <div className="vr-progress-row">
                                            <div className={`vr-track ${isBoosted ? 'track-boosted' : ''}`} role="progressbar" aria-label={`${entry.object.canonical_name}: ${percent}%`} aria-valuenow={percent} aria-valuemin={0} aria-valuemax={100}>
                                                <div
                                                    key={`bar-${entry.id}-${isBoosted ? boostSeq : 'rest'}`}
                                                    className={`vr-bar ${rank === 1 ? 'bar-lead' : ''} ${isBoosted ? 'bar-boosted' : ''}`}
                                                    style={{ width: `${percent}%` }}
                                                >
                                                    {isBoosted && <span className="vr-bar-flare" />}
                                                </div>
                                            </div>
                                            <span
                                                key={`pct-${entry.id}-${isBoosted ? boostSeq : 'rest'}`}
                                                className={`vr-percent ${isBoosted ? 'is-popping' : ''}`}
                                            >
                                                {percent}%
                                            </span>
                                            {isBoosted && (
                                                <span
                                                    key={`pts-${entry.id}-${boostSeq}`}
                                                    className="vr-float-points"
                                                >
                                                    +{numberLabel(boostedAmount, locale)}
                                                </span>
                                            )}
                                        </div>

                                        <div className="vr-sub-stats">
                                            <span className={isBoosted ? 'stat-popping' : ''}>
                                                <strong>{numberLabel(points, locale)}</strong> {t.points}
                                            </span>
                                            {Number(score?.quick_points ?? 0) > 0 && (
                                                <span className="vr-candidate-status">
                                                    {isId ? `termasuk ${numberLabel(score.quick_points, locale)} dari vote cepat` : `includes ${numberLabel(score.quick_points, locale)} from quick votes`}
                                                </span>
                                            )}
                                            {!canReceiveSupport && <span className="vr-candidate-status">{unavailableLabel}</span>}
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    className="vr-btn-support vr-btn-support-pill"
                                    onClick={() => beginSupport(entry)}
                                    disabled={!canReceiveSupport}
                                    title={canReceiveSupport ? `${t.support} ${entry.object.canonical_name}` : unavailableLabel}
                                >
                                    <span className="vr-support-coin" aria-hidden="true"><CoinIcon /></span>
                                    <span className="vr-pill-label">{canReceiveSupport ? t.support : unavailableLabel}</span>
                                </button>
                            </div>
                        );
                    })}
                </div>
            </section>

            {/* Community Discussion Section with Animations & Interactive Sorting */}
            <section
                ref={commentsSectionRef}
                id="comments-section"
                className={`vr-card vr-comments-card ${highlightComments ? 'vr-highlight-pulse' : ''}`}
            >
                <div className="vr-section-head">
                    <div className="vr-title-with-count">
                        <h2 className="vr-section-title">{t.comments}</h2>
                        <span className="vr-badge-count">{totalComments}</span>
                    </div>

                    {/* Interactive Filter / Sort Dropdown */}
                    {comments.length > 1 && (
                        <div className="vr-sort-wrapper" ref={sortDropdownRef}>
                            <button
                                type="button"
                                className={`vr-sort-trigger-btn ${sortDropdownOpen ? 'is-open' : ''} ${commentSort !== 'latest' ? 'is-filtered' : ''}`}
                                onClick={() => setSortDropdownOpen((prev) => !prev)}
                                title={isId ? 'Urutkan komentar' : 'Sort comments'}
                                aria-label={isId ? 'Urutkan komentar' : 'Sort comments'}
                                aria-expanded={sortDropdownOpen}
                                aria-haspopup="true"
                            >
                                <RankingIcon name="filter" size={14} />
                            </button>

                            {sortDropdownOpen && (
                                <div className="vr-sort-dropdown" role="menu">
                                    <div className="vr-sort-dropdown-header">
                                        {isId ? 'Urutkan menurut' : 'Sort by'}
                                    </div>
                                    <button
                                        type="button"
                                        role="menuitem"
                                        className={`vr-sort-option ${commentSort === 'popular' ? 'is-active' : ''}`}
                                        onClick={() => handleSortChange('popular')}
                                    >
                                        <div className="vr-sort-option-left">
                                            <RankingIcon name="flame" size={14} className="vr-sort-option-icon" />
                                            <span>{isId ? 'Terpopuler' : 'Top Comments'}</span>
                                        </div>
                                        {commentSort === 'popular' && (
                                            <RankingIcon name="check" size={13} className="vr-sort-check" />
                                        )}
                                    </button>
                                    <button
                                        type="button"
                                        role="menuitem"
                                        className={`vr-sort-option ${commentSort === 'latest' ? 'is-active' : ''}`}
                                        onClick={() => handleSortChange('latest')}
                                    >
                                        <div className="vr-sort-option-left">
                                            <RankingIcon name="clock" size={14} className="vr-sort-option-icon" />
                                            <span>{isId ? 'Terbaru' : 'Newest'}</span>
                                        </div>
                                        {commentSort === 'latest' && (
                                            <RankingIcon name="check" size={13} className="vr-sort-check" />
                                        )}
                                    </button>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* Simple Chat-Style Comment Composer */}
                {user ? (
                    <form className="vr-chat-composer" onSubmit={submitComment}>
                        <div className="vr-chat-bar">
                            <MentionInput
                                type="text"
                                className="vr-chat-input"
                                id="comment"
                                placeholder={isId ? 'Tulis komentar...' : 'Write a comment...'}
                                value={comment}
                                maxLength={3000}
                                onValueChange={setComment}
                                participants={mentionParticipants}
                                locale={locale}
                                autoComplete="off"
                                required
                            />
                            {comment.length > 0 && (
                                <button
                                    type="button"
                                    className="vr-chat-clear-btn"
                                    onClick={() => setComment('')}
                                    title={isId ? 'Hapus' : 'Clear'}
                                >
                                    <RankingIcon name="x" size={14} />
                                </button>
                            )}
                            <CommentImageAttachButton attachments={commentAttachments} locale={locale} disabled={busy} />
                            <button
                                type="submit"
                                className="vr-chat-send-btn"
                                disabled={busy || commentAttachments.processing || comment.trim().length < 2}
                                title={t.sendComment}
                            >
                                <RankingIcon name="send" size={15} />
                            </button>
                        </div>
                        <CommentImagePreviews attachments={commentAttachments} locale={locale} />
                    </form>
                ) : (
                    <div className="vr-guest-inline">
                        <span>{isId ? 'Ingin bergabung dalam diskusi?' : 'Want to join the conversation?'}</span>
                        <button type="button" onClick={openLoginModal} className="vr-link-bold">
                            {t.login} →
                        </button>
                    </div>
                )}

                {/* Animated Threaded Comments Stream */}
                <div className="vr-comment-items">
                    {sortedRootComments.length ? (
                        sortedRootComments.slice(0, visibleRootCount).map((root) => {
                            const replies = repliesByParent[root.id] || [];
                            const isCollapsed = Boolean(collapsedThreads[root.id]);
                            const isRepliesExpanded = Boolean(expandedReplyThreads[root.id]);
                            const visibleReplies = isRepliesExpanded ? replies : replies.slice(0, 3);

                            return (
                                <div key={root.id} className="vr-comment-thread">
                                    {/* Main / Root Comment */}
                                    {renderCommentCard(root, false)}

                                    {/* Collapsible Nested Replies with Smooth Accordion & Progressive Limiting */}
                                    {replies.length > 0 && (
                                        <div className="vr-replies-wrapper">
                                            <button
                                                type="button"
                                                className={`vr-replies-toggle-btn ${
                                                    isCollapsed ? 'is-collapsed' : 'is-expanded'
                                                }`}
                                                onClick={() => toggleCollapse(root.id)}
                                                aria-expanded={!isCollapsed}
                                            >
                                                <RankingIcon
                                                    name="chevronDown"
                                                    size={13}
                                                    className="vr-chevron-icon"
                                                />
                                                <span>
                                                    {isCollapsed
                                                        ? isId
                                                            ? `Lihat ${replies.length} balasan`
                                                            : `Show ${replies.length} replies`
                                                        : isId
                                                        ? `Sembunyikan balasan (${replies.length})`
                                                        : `Hide replies (${replies.length})`}
                                                </span>
                                            </button>

                                            <div
                                                className={`vr-replies-collapse-container ${
                                                    isCollapsed ? 'is-collapsed' : 'is-expanded'
                                                }`}
                                            >
                                                <div className="vr-replies-collapse-inner">
                                                    <div className="vr-comment-replies">
                                                        {visibleReplies.map((reply) => renderCommentCard(reply, true))}
                                                    </div>

                                                    {replies.length > 3 && !isRepliesExpanded && (
                                                        <button
                                                            type="button"
                                                            className="vr-btn-more-replies"
                                                            onClick={() =>
                                                                setExpandedReplyThreads((prev) => ({
                                                                    ...prev,
                                                                    [root.id]: true,
                                                                }))
                                                            }
                                                        >
                                                            <RankingIcon name="cornerDownRight" size={12} />
                                                            <span>
                                                                {isId
                                                                    ? `Lihat ${replies.length - 3} balasan lainnya`
                                                                    : `Show ${replies.length - 3} more replies`}
                                                            </span>
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })
                    ) : (
                        <p className="vr-empty-text">
                            {isId
                                ? 'Belum ada komentar. Jadilah yang pertama berkomentar.'
                                : 'No comments yet. Be the first to share your thoughts.'}
                        </p>
                    )}

                    {/* Progressive Pagination for Root Comments */}
                    {sortedRootComments.length > visibleRootCount && (
                        <div className="vr-load-more-container">
                            <button
                                type="button"
                                className="vr-btn-load-more"
                                onClick={() => setVisibleRootCount((prev) => prev + 10)}
                            >
                                <RankingIcon name="chevronDown" size={14} />
                                <span>
                                    {isId
                                        ? `Tampilkan lebih banyak komentar (${sortedRootComments.length - visibleRootCount} tersisa)`
                                        : `Show more comments (${sortedRootComments.length - visibleRootCount} remaining)`}
                                </span>
                            </button>
                        </div>
                    )}
                </div>
            </section>

            </div>
            </div>

            {/* Support Modal Dialog */}
            {selected && (
                <div
                    className={`vr-modal-backdrop vr-support-backdrop ${isSupportClosing ? 'is-closing' : ''}`}
                    role="presentation"
                    onMouseDown={(event) => {
                        if (event.target === event.currentTarget) closeSupport();
                    }}
                    onAnimationEnd={(event) => {
                        if (isSupportClosing && event.target === event.currentTarget) {
                            setSelected(null);
                            setIsSupportClosing(false);
                        }
                    }}
                >
                    <form
                        className="vr-modal-dialog vr-support-dialog"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="support-dialog-title"
                        aria-describedby="support-dialog-description"
                        onSubmit={submitSupport}
                    >
                        <div className="vr-modal-header">
                            <div>
                                <h2 id="support-dialog-title" className="vr-modal-title">
                                    {t.supportCandidate}
                                </h2>
                                <p id="support-dialog-description" className="vr-modal-subtitle">
                                    {isId ? 'Pilih jumlah poin untuk kandidat ini.' : 'Choose how many points to give this candidate.'}
                                </p>
                            </div>
                            <button
                                type="button"
                                className="vr-modal-close"
                                onClick={closeSupport}
                                aria-label={isId ? 'Tutup dialog' : 'Close dialog'}
                            >
                                <RankingIcon name="x" size={16} />
                            </button>
                        </div>

                        <div className="vr-modal-body vr-support-body">
                            <div className="vr-support-summary">
                                <div className="vr-support-target">
                                    {selected.object.image_path
                                        ? <img src={objectImageUrl(selected.object, 'small')} srcSet={objectImageSrcSet(selected.object)} sizes="48px" alt="" className="vr-support-target-image" />
                                        : <span className="vr-support-target-image vr-thumb-placeholder" aria-hidden="true">{selected.object.canonical_name.slice(0, 1).toUpperCase()}</span>}
                                    <div className="vr-support-target-copy">
                                        <span>{isId ? 'Kandidat' : 'Candidate'}</span>
                                        <strong>{selected.object.canonical_name}</strong>
                                    </div>
                                </div>
                                <div className="vr-support-balance">
                                    <span>{isId ? 'Saldo poin Anda' : 'Your point balance'}</span>
                                    <div className="vr-support-balance-amount">
                                        <span className="vr-support-coin" aria-hidden="true"><CoinIcon /></span>
                                        <strong>{balance === null ? '...' : numberLabel(balance, locale)} {t.points}</strong>
                                    </div>
                                </div>
                            </div>

                            {needsPoints && (
                                <div className="vr-support-shortfall" role="status">
                                    <p>{balance === 0
                                        ? (isId ? 'Poin Anda habis. Klaim bonus harian di dompet jika tersedia.' : 'You have no points left. Claim your daily bonus in the wallet if available.')
                                        : (isId ? 'Poin Anda belum cukup untuk dukungan ini. Klaim bonus harian di dompet jika tersedia.' : 'You need more points for this support. Claim your daily bonus in the wallet if available.')}</p>
                                    <div className="vr-support-shortfall-actions">
                                        <Link href={walletUrl} className="vr-claim-link">
                                            {isId ? 'Buka dompet poin' : 'Open points wallet'}
                                        </Link>
                                        {balance > 0 && <button type="button" onClick={() => setAmount(balance)}>
                                            {isId ? `Gunakan ${numberLabel(balance, locale)} poin` : `Use ${numberLabel(balance, locale)} points`}
                                        </button>}
                                    </div>
                                </div>
                            )}

                            {!needsPoints && <div className="vr-field-wrap vr-support-amount">
                                <label className="vr-label" htmlFor="support-amount">{t.supportAmount}</label>
                                <div className="vr-stepper-wrap">
                                    <button
                                        type="button"
                                        className="vr-stepper-btn"
                                        disabled={amount <= 1}
                                        onClick={() => setAmount(Math.max(1, amount - 1))}
                                        aria-label={isId ? 'Kurangi satu poin' : 'Remove one point'}
                                    >
                                        <RankingIcon name="minus" size={14} />
                                    </button>
                                    <input
                                        id="support-amount"
                                        type="number"
                                        className="vr-stepper-input"
                                        min="1"
                                        max={balance !== null ? Math.max(1, balance) : 100000}
                                        value={amount}
                                        onChange={(e) => setAmount(Math.max(1, Number(e.target.value)))}
                                        required
                                    />
                                    <button
                                        type="button"
                                        className="vr-stepper-btn"
                                        onClick={() => setAmount(amount + 1)}
                                        aria-label={isId ? 'Tambah satu poin' : 'Add one point'}
                                    >
                                        <RankingIcon name="plus" size={14} />
                                    </button>
                                </div>

                                <span className="vr-support-quick-label">{isId ? 'Pilihan cepat' : 'Quick amounts'}</span>
                                <div className="vr-preset-chips">
                                    {[1, 5, 10, 25].map((val) => (
                                        <button
                                            key={val}
                                            type="button"
                                            className={`vr-chip ${amount === val ? 'active' : ''}`}
                                            onClick={() => setAmount(val)}
                                        >
                                            {numberLabel(val, locale)}
                                        </button>
                                    ))}
                                    {balance && balance > 0 && (
                                        <button
                                            type="button"
                                            className={`vr-chip ${amount === balance ? 'active' : ''}`}
                                            onClick={() => setAmount(balance)}
                                        >
                                            {isId ? 'Semua' : 'Max'}
                                        </button>
                                    )}
                                </div>
                            </div>}
                        </div>

                        <div className="vr-modal-footer">
                            <button
                                type="button"
                                className="vr-btn-secondary"
                                onClick={closeSupport}
                            >
                                {t.cancel}
                            </button>
                            {!needsPoints && <button
                                type="submit"
                                className="vr-btn-primary vr-modal-support-btn"
                                disabled={busy || amount < 1 || (balance !== null && balance < amount)}
                            >
                                <span className="vr-support-coin" aria-hidden="true"><CoinIcon /></span>
                                <span>
                                    {busy ? (isId ? 'Memproses...' : 'Processing...') : (
                                        <>
                                            {t.support}{' '}
                                            <span key={amount} className="vr-support-amount-motion">
                                                {numberLabel(amount, locale)}
                                            </span>{' '}
                                            {t.points}
                                        </>
                                    )}
                                </span>
                            </button>}
                        </div>
                    </form>
                </div>
            )}

            {reporting && (
                <ReportDialog
                    locale={locale}
                    targetType={reporting.target_type}
                    onClose={() => setReporting(null)}
                    onSubmit={submitReport}
                />
            )}

            {/* Ranking Share Modal */}
            <ShareModal
                isOpen={shareOpen}
                onClose={() => setShareOpen(false)}
                locale={locale}
                title={isId ? 'Bagikan Ranking' : 'Share Ranking'}
                description={isId ? 'Bagikan ranking ini ke teman atau media sosial' : 'Share this ranking with friends or social media'}
                shareUrl={rankingUrl}
                shareText={shareText}
                copyLabel={isId ? 'Salin Tautan Ranking' : 'Copy Ranking Link'}
                preview={{
                    avatar: <RankingIcon name="trophy" size={20} />,
                    title: post.title,
                    subtitle: `${post.category?.name || (isId ? 'Ranking' : 'Category')} · ${numberLabel(totalPoints, locale)} ${t.points} · ${post.entries?.length || 0} ${isId ? 'kandidat' : 'candidates'}`,
                }}
            />

            {/* Subtle Toast Notification */}
            {toastMessage && (
                <div className={`vr-toast vr-toast-${toastType}`} role="status">
                    <span>{toastMessage}</span>
                </div>
            )}
        </Layout>
    );
}
