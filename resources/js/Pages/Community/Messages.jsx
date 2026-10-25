import { Link, router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { setMessageCount, useNotificationFeed } from '../../Components/notificationFeed';
import { AvatarFrame } from './AvatarFrame';
import ReportDialog from './ReportDialog';
import { api, commentTimeAgo, Layout, NavIcon, PageHead, path } from './Shared';

const POLL_INTERVAL = 15000;

function Avatar({ person, size = 40 }) {
    return <AvatarFrame frame={person?.avatar_frame} size={size >= 44 ? 'md' : 'sm'}>
        <span className="vdm-avatar" style={{ width: size, height: size }} aria-hidden="true">
            {person?.avatar_url ? <img src={`${person.avatar_url}&size=small`} alt="" /> : (person?.name ?? '?').slice(0, 1).toUpperCase()}
        </span>
    </AvatarFrame>;
}

/** Re-runs `callback` on an interval only while the WebSocket is down, as the notification bell does. */
function usePollWhileOffline(callback) {
    const { connected } = useNotificationFeed();

    useEffect(() => {
        if (connected) return undefined;
        const timer = setInterval(callback, POLL_INTERVAL);

        return () => clearInterval(timer);
    }, [connected, callback]);
}

function ConversationList({ locale, box, onBoxChange, activeId, onOpen }) {
    const isId = locale === 'id';
    const { lastMessage } = useNotificationFeed();
    const [conversations, setConversations] = useState(null);
    const [pending, setPending] = useState(0);
    const [error, setError] = useState('');

    const load = useCallback(() => api(`/backend/messages/conversations?box=${box}`)
        .then((result) => {
            setConversations(result.conversations.data);
            setPending(result.pending_requests);
            setMessageCount(result);
            setError('');
        })
        .catch((requestError) => setError(requestError.message)), [box]);

    useEffect(() => {
        setConversations(null);
        load();
    }, [load]);
    useEffect(() => {
        if (lastMessage) load();
    }, [lastMessage, load]);
    usePollWhileOffline(load);

    return <section className="vdm-list" aria-label={isId ? 'Daftar percakapan' : 'Conversations'}>
        <div className="vdm-tabs" role="tablist">
            {[['inbox', isId ? 'Kotak masuk' : 'Inbox'], ['requests', isId ? 'Permintaan' : 'Requests']].map(([key, label]) => <button
                key={key}
                type="button"
                role="tab"
                aria-selected={box === key}
                className={box === key ? 'is-active' : ''}
                onClick={() => onBoxChange(key)}
            >
                {label}
                {key === 'requests' && pending > 0 && <span className="vdm-tab-count">{pending}</span>}
            </button>)}
        </div>
        {error && <p className="vdm-error" role="alert">{error}</p>}
        {conversations === null ? <p className="vdm-empty" role="status">{isId ? 'Memuat...' : 'Loading...'}</p>
            : conversations.length === 0 ? <p className="vdm-empty">{box === 'requests'
                ? (isId ? 'Tidak ada permintaan pesan.' : 'No message requests.')
                : (isId ? 'Belum ada percakapan. Mulai dari tombol "Kirim pesan" di profil anggota.' : 'No conversations yet. Start one from the "Message" button on a member profile.')}</p>
                : <ul className="vdm-threads">
                    {conversations.map((conversation) => <li key={conversation.id}>
                        <button
                            type="button"
                            className={'vdm-thread' + (conversation.id === activeId ? ' is-active' : '') + (conversation.unread ? ' is-unread' : '')}
                            onClick={() => onOpen(conversation.id)}
                        >
                            <Avatar person={conversation.other} />
                            <span className="vdm-thread-copy">
                                <strong>{conversation.other?.name ?? (isId ? 'Anggota' : 'Member')}</strong>
                                <span>{conversation.last_message?.body ?? ''}</span>
                            </span>
                            <span className="vdm-thread-meta">
                                {conversation.last_message_at && <time dateTime={conversation.last_message_at}>{commentTimeAgo(conversation.last_message_at, locale, new Date())}</time>}
                                {conversation.unread && <span className="vm-notification-dot" role="img" aria-label={isId ? 'Belum dibaca' : 'Unread'} />}
                            </span>
                        </button>
                    </li>)}
                </ul>}
    </section>;
}

function Thread({ locale, conversationId, bodyMax, onBack, onClosed }) {
    const isId = locale === 'id';
    const { lastMessage } = useNotificationFeed();
    const [conversation, setConversation] = useState(null);
    const [messages, setMessages] = useState([]);
    const [nextCursor, setNextCursor] = useState(null);
    const [body, setBody] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [reporting, setReporting] = useState(null);
    const [notice, setNotice] = useState('');
    const bottomRef = useRef(null);

    /** Newest page first from the server; the thread shows oldest at the top. */
    const load = useCallback(() => api(`/backend/messages/conversations/${conversationId}`).then((result) => {
        setConversation(result.conversation);
        setMessages((current) => {
            const known = new Set(result.messages.data.map((message) => message.id));

            return [...current.filter((message) => !known.has(message.id) && message.id < (result.messages.data.at(-1)?.id ?? 0)), ...[...result.messages.data].reverse()];
        });
        setNextCursor((current) => current ?? result.messages.next_cursor);
        setMessageCount(result);
    }).catch((requestError) => setError(requestError.message)), [conversationId]);

    useEffect(() => {
        load();
    }, [load]);
    usePollWhileOffline(load);

    useEffect(() => {
        if (!lastMessage || lastMessage.conversation_id !== conversationId) return;
        setMessages((current) => current.some((message) => message.id === lastMessage.id) ? current : [...current, lastMessage]);
        if (lastMessage.user_id === conversation?.other?.id) {
            api(`/backend/messages/conversations/${conversationId}/read`, 'PATCH').then(setMessageCount).catch(() => {});
        }
    }, [lastMessage, conversationId, conversation?.other?.id]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ block: 'end' });
    }, [messages.length]);

    const loadOlder = async () => {
        const result = await api(`/backend/messages/conversations/${conversationId}?cursor=${encodeURIComponent(nextCursor)}`).catch((requestError) => {
            setError(requestError.message);

            return null;
        });
        if (!result) return;
        setMessages((current) => {
            const known = new Set(current.map((message) => message.id));

            return [...[...result.messages.data].reverse().filter((message) => !known.has(message.id)), ...current];
        });
        setNextCursor(result.messages.next_cursor);
    };

    const send = async (event) => {
        event.preventDefault();
        if (!body.trim() || busy) return;
        setBusy(true);
        setError('');

        try {
            const result = await api(`/backend/messages/conversations/${conversationId}/messages`, 'POST', { body });
            setMessages((current) => current.some((message) => message.id === result.message.id) ? current : [...current, result.message]);
            setBody('');
            if (conversation?.state === 'request') load();
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setBusy(false);
        }
    };

    const respond = async (action) => {
        setBusy(true);
        setError('');

        try {
            const result = await api(`/backend/messages/conversations/${conversationId}/${action}`, 'PATCH');
            setMessageCount(result);
            if (action === 'decline') {
                onClosed();
            } else {
                setConversation(result.conversation);
            }
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setBusy(false);
        }
    };

    const block = async () => {
        setBusy(true);

        try {
            await api(`/backend/users/${conversation.other.id}/block`, 'POST');
            await load();
            setNotice(isId ? 'Anggota ini diblokir. Kalian tidak bisa saling mengirim pesan.' : 'Member blocked. Neither of you can message the other.');
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setBusy(false);
        }
    };

    const submitReport = async (reason) => {
        await api('/backend/reports', 'POST', { target_type: 'message', target_id: String(reporting.id), reason });
        setReporting(null);
        setNotice(isId ? 'Laporan terkirim. Tim moderasi akan meninjaunya.' : 'Report sent. The moderation team will review it.');
    };

    if (!conversation) {
        return <section className="vdm-thread-pane"><p className="vdm-empty" role="status">{error || (isId ? 'Memuat percakapan...' : 'Loading conversation...')}</p></section>;
    }

    const other = conversation.other;
    const canWrite = conversation.state !== 'closed';

    return <section className="vdm-thread-pane" aria-label={isId ? 'Percakapan' : 'Conversation'}>
        <header className="vdm-thread-head">
            <button type="button" className="vdm-back" onClick={onBack} aria-label={isId ? 'Kembali ke daftar' : 'Back to list'}><NavIcon name="arrowLeft" size={18} /></button>
            <Avatar person={other} size={36} />
            <div className="vdm-thread-title">
                {other?.username ? <Link href={path(locale, `/profile/${other.username}`)}><strong>{other.name}</strong></Link> : <strong>{isId ? 'Anggota' : 'Member'}</strong>}
                {other?.username && <span>@{other.username}</span>}
            </div>
            {other && conversation.state !== 'closed' && <button type="button" className="vdm-head-action" onClick={block} disabled={busy}>{isId ? 'Blokir' : 'Block'}</button>}
        </header>

        {conversation.state === 'request' && <div className="vdm-banner">
            <p>{isId ? `${other?.name ?? 'Anggota ini'} ingin mengirim pesan kepada Anda. Mereka tidak tahu Anda sudah membacanya sampai Anda menerima atau membalas.` : `${other?.name ?? 'This member'} wants to message you. They will not know you have read it until you accept or reply.`}</p>
            <div className="vdm-banner-actions">
                <button type="button" className="vdm-primary" onClick={() => respond('accept')} disabled={busy}>{isId ? 'Terima' : 'Accept'}</button>
                <button type="button" className="vdm-secondary" onClick={() => respond('decline')} disabled={busy}>{isId ? 'Tolak' : 'Decline'}</button>
            </div>
        </div>}
        {conversation.state === 'pending' && <div className="vdm-banner is-muted"><p>{isId ? 'Menunggu permintaan pesan Anda diterima.' : 'Waiting for your message request to be accepted.'}</p></div>}
        {notice && <div className="vdm-banner is-muted" role="status"><p>{notice}</p></div>}

        <ol className="vdm-messages">
            {nextCursor && <li className="vdm-older"><button type="button" onClick={loadOlder}>{isId ? 'Muat pesan sebelumnya' : 'Load earlier messages'}</button></li>}
            {messages.map((message) => {
                const mine = message.user_id !== other?.id;

                return <li key={message.id} className={'vdm-message' + (mine ? ' is-mine' : '')}>
                    <p>{message.body}</p>
                    <span className="vdm-message-meta">
                        <time dateTime={message.created_at}>{commentTimeAgo(message.created_at, locale, new Date())}</time>
                        {!mine && <button type="button" onClick={() => setReporting(message)}>{isId ? 'Laporkan' : 'Report'}</button>}
                    </span>
                </li>;
            })}
            <li ref={bottomRef} aria-hidden="true" />
        </ol>

        {canWrite ? <form className="vdm-composer" onSubmit={send}>
            <label htmlFor="vdm-composer-input" className="sr-only">{isId ? 'Tulis pesan' : 'Write a message'}</label>
            <textarea
                id="vdm-composer-input"
                value={body}
                onChange={(event) => setBody(event.target.value)}
                onKeyDown={(event) => {
                    if (event.key === 'Enter' && !event.shiftKey && !event.nativeEvent.isComposing) send(event);
                }}
                maxLength={bodyMax}
                rows={1}
                placeholder={isId ? 'Tulis pesan…' : 'Write a message…'}
            />
            <button type="submit" className="vdm-send" disabled={busy || !body.trim()} aria-label={isId ? 'Kirim' : 'Send'}><NavIcon name="send" size={18} /></button>
        </form> : <p className="vdm-closed">{isId ? 'Anda tidak bisa membalas percakapan ini.' : 'You can no longer reply to this conversation.'}</p>}
        {error && <p className="vdm-error" role="alert">{error}</p>}
        {reporting && <ReportDialog locale={locale} targetType="message" onClose={() => setReporting(null)} onSubmit={submitReport} />}
    </section>;
}

export default function Messages({ locale, categories, leaders, activeConversationId = null, pendingRequests = 0, bodyMax = 2000 }) {
    const isId = locale === 'id';
    const [box, setBox] = useState(() => (activeConversationId === null && pendingRequests > 0 ? 'requests' : 'inbox'));
    const open = (id) => router.visit(path(locale, id ? `/messages/${id}` : '/messages'), {
        only: ['activeConversationId', 'pendingRequests'],
        preserveState: true,
        preserveScroll: true,
    });

    return <Layout locale={locale} categories={categories} leaders={leaders} active="messages">
        <PageHead
            title={isId ? 'Pesan' : 'Messages'}
            locale={locale}
            description={isId ? 'Pesan pribadi dengan anggota Votmee.' : 'Private messages with Votmee members.'}
        />
        <div className={'vdm-page' + (activeConversationId ? ' has-active' : '')}>
            <div className="vx-section-heading vdm-heading">
                <div>
                    <span className="vx-section-kicker">{isId ? 'PESAN PRIBADI' : 'PRIVATE MESSAGES'}</span>
                    <h1>{isId ? 'Pesan' : 'Messages'}</h1>
                </div>
            </div>
            <div className="vdm-layout">
                <ConversationList locale={locale} box={box} onBoxChange={setBox} activeId={activeConversationId} onOpen={open} />
                {activeConversationId
                    ? <Thread key={activeConversationId} locale={locale} conversationId={activeConversationId} bodyMax={bodyMax} onBack={() => open(null)} onClosed={() => open(null)} />
                    : <section className="vdm-thread-pane vdm-placeholder">
                        <NavIcon name="mail" size={28} />
                        <p>{isId ? 'Pilih percakapan untuk mulai membaca.' : 'Choose a conversation to start reading.'}</p>
                    </section>}
            </div>
        </div>
    </Layout>;
}
