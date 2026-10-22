import { InfiniteScroll, Link, usePoll } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import {
    clearReadNotifications,
    dismissToast,
    markAllRead,
    markRead,
    mergeNotifications,
    removeNotification,
    useNotificationFeed,
    useUnreadCount,
} from '../../Components/notificationFeed';
import { achievementName, AchievementBadge } from './Achievements';
import { api, commentTimeAgo, Layout, NavIcon, numberLabel, PageHead, path } from './Shared';

const reviewLabels = {
    id: { published: 'diterbitkan', verified: 'diverifikasi', needs_revision: 'perlu direvisi', rejected: 'ditolak', resolved: 'ditindaklanjuti', dismissed: 'ditutup tanpa tindakan' },
    en: { published: 'published', verified: 'verified', needs_revision: 'sent back for revision', rejected: 'rejected', resolved: 'resolved', dismissed: 'dismissed' },
};

/**
 * Sentence for a stored notification in the viewer's locale. The server stores only
 * structured data so old notifications follow the current language.
 */
export function notificationText(notification, locale) {
    const isId = locale === 'id';
    const { data } = notification;
    const actor = data.actor?.name ?? (isId ? 'Seseorang' : 'Someone');
    const status = reviewLabels[isId ? 'id' : 'en'][data.status] ?? data.status;
    const title = data.title ? ` “${data.title}”` : '';

    switch (notification.type) {
        case 'comment_reply':
            return isId ? `${actor} membalas komentar Anda` : `${actor} replied to your comment`;
        case 'ranking_comment':
            return isId ? `${actor} mengomentari ranking Anda${title}` : `${actor} commented on your ranking${title}`;
        case 'comment_liked': {
            const others = Math.max(0, (data.count ?? 1) - 1);
            if (others === 0) return isId ? `${actor} menyukai komentar Anda` : `${actor} liked your comment`;
            return isId ? `${actor} dan ${numberLabel(others, locale)} lainnya menyukai komentar Anda` : `${actor} and ${numberLabel(others, locale)} others liked your comment`;
        }
        case 'achievement_unlocked':
            return isId ? `Lencana baru terbuka: ${achievementName(data.badge_key, locale)}` : `New badge unlocked: ${achievementName(data.badge_key, locale)}`;
        case 'content_reviewed':
            return isId ? `Kontribusi Anda${title} ${status}` : `Your contribution${title} was ${status}`;
        case 'object_reviewed':
            return isId ? `Objek${title} ${status}` : `Your object${title} was ${status}`;
        case 'report_resolved':
            return isId ? `Laporan Anda ${status}` : `Your report was ${status}`;
        case 'streak_reminder':
            return isId ? `Streak ${numberLabel(data.days, locale)} harianmu hampir putus. Klaim bonus hari ini!` : `Your ${numberLabel(data.days, locale)}-day streak is about to break. Claim today's bonus!`;
        case 'message_request':
            return isId ? `${actor} ingin mengirim pesan kepada Anda` : `${actor} wants to message you`;
        case 'trust_granted':
            return isId ? 'Anda kini menjadi kontributor tepercaya' : 'You are now a trusted contributor';
        default:
            return isId ? 'Pemberitahuan baru' : 'New notification';
    }
}

function NotificationLead({ notification, size = 36 }) {
    const { data } = notification;

    return <span className="vm-notification-icon">
        {notification.type === 'achievement_unlocked' && data.badge_key
            ? <AchievementBadge achievementKey={data.badge_key} size={size} />
            : data.actor?.avatar_url
                ? <img src={data.actor.avatar_url} alt="" />
                : <NavIcon name={{ comment_liked: 'heart', streak_reminder: 'flame' }[notification.type] ?? 'messageSquare'} size={18} />}
    </span>;
}

function NotificationItem({ notification, locale, onOpen, onDelete, index = 0 }) {
    const { data } = notification;
    const isId = locale === 'id';
    const [leaving, setLeaving] = useState(false);
    const href = data.url ? path(locale, data.url) : null;
    const body = <>
        <NotificationLead notification={notification} />
        <span className="vm-notification-copy">
            <strong>{notificationText(notification, locale)}</strong>
            {data.excerpt && <span className="vm-notification-excerpt">{data.excerpt}</span>}
            {data.reason && <span className="vm-notification-excerpt">{data.reason}</span>}
            <time dateTime={notification.created_at}>{commentTimeAgo(notification.created_at, locale, new Date())}</time>
        </span>
        {!notification.read && <span className="vm-notification-dot" role="img" aria-label={isId ? 'Belum dibaca' : 'Unread'} />}
    </>;
    const remove = () => {
        setLeaving(true);
        onDelete(notification).catch(() => setLeaving(false));
    };

    return <div
        className={'vm-notification-item' + (notification.read ? '' : ' is-unread') + (leaving ? ' is-leaving' : '')}
        style={{ '--i': Math.min(index, 8) }}
    >
        {href
            ? <Link href={href} className="vm-notification-main" onClick={() => onOpen(notification)}>{body}</Link>
            : <button type="button" className="vm-notification-main" onClick={() => onOpen(notification)}>{body}</button>}
        {onDelete && <button type="button" className="vm-notification-delete" aria-label={isId ? 'Hapus notifikasi' : 'Delete notification'} onClick={remove}>
            <NavIcon name="x" size={16} />
        </button>}
    </div>;
}

/** Header bell: unread badge plus a dropdown with the latest notifications. */
export function NotificationBell({ locale, compact = false }) {
    const feed = useNotificationFeed();
    const count = useUnreadCount();
    const [open, setOpen] = useState(false);
    const [fetched, setFetched] = useState(null);
    const [ringing, setRinging] = useState(false);
    const containerRef = useRef(null);
    const previousCount = useRef(count);
    const isId = locale === 'id';
    const items = mergeNotifications(feed, fetched ?? []).slice(0, 10);

    useEffect(() => {
        if (count > previousCount.current) {
            setRinging(true);
            const timer = setTimeout(() => setRinging(false), 1200);
            previousCount.current = count;

            return () => clearTimeout(timer);
        }
        previousCount.current = count;

        return undefined;
    }, [count]);

    useEffect(() => {
        if (!open) return undefined;
        let active = true;
        api('/backend/notifications').then((result) => active && setFetched(result.notifications)).catch(() => active && setFetched([]));
        const close = (event) => {
            if (event.type === 'keydown' ? event.key === 'Escape' : !containerRef.current?.contains(event.target)) setOpen(false);
        };
        document.addEventListener('mousedown', close);
        document.addEventListener('keydown', close);

        return () => {
            active = false;
            document.removeEventListener('mousedown', close);
            document.removeEventListener('keydown', close);
        };
    }, [open]);

    const openItem = (notification) => {
        setOpen(false);
        markRead(notification);
    };

    return <div ref={containerRef} className={'vm-notification-wrap' + (compact ? ' is-compact' : '')}>
        <button
            type="button"
            className={'vm-notification-trigger' + (ringing ? ' is-ringing' : '') + (open ? ' is-open' : '')}
            aria-expanded={open}
            aria-label={isId ? `Notifikasi, ${count} belum dibaca` : `Notifications, ${count} unread`}
            onClick={() => setOpen(!open)}
        >
            <NavIcon name="bell" size={20} />
            {count > 0 && <span key={count} className="vm-notification-badge">{count > 99 ? '99+' : count}</span>}
        </button>
        {open && <div className="vm-notification-menu">
            <div className="vm-notification-menu-head">
                <strong>{isId ? 'Notifikasi' : 'Notifications'}</strong>
                {count > 0 && <button type="button" onClick={() => markAllRead(items.map((item) => item.id))}>{isId ? 'Tandai semua dibaca' : 'Mark all read'}</button>}
            </div>
            <div className="vm-notification-list">
                {fetched === null && !items.length ? <p className="vm-notification-empty" role="status">{isId ? 'Memuat...' : 'Loading...'}</p>
                    : items.length ? items.map((item, index) => <NotificationItem key={item.id} notification={item} locale={locale} onOpen={openItem} index={index} />)
                        : <p className="vm-notification-empty">{isId ? 'Belum ada notifikasi.' : 'No notifications yet.'}</p>}
            </div>
            <Link href={path(locale, '/notifications')} className="vm-notification-all" onClick={() => setOpen(false)}>{isId ? 'Lihat semua' : 'See all'}</Link>
        </div>}
    </div>;
}

function NotificationToast({ notification, locale }) {
    const isId = locale === 'id';
    const [paused, setPaused] = useState(false);
    const href = notification.data.url ? path(locale, notification.data.url) : null;
    const open = () => {
        markRead(notification);
        dismissToast(notification.id);
    };
    const body = <>
        <NotificationLead notification={notification} size={32} />
        <span className="vm-notification-copy">
            <strong>{notificationText(notification, locale)}</strong>
            {notification.data.excerpt && <span className="vm-notification-excerpt">{notification.data.excerpt}</span>}
        </span>
    </>;

    useEffect(() => {
        if (paused) return undefined;
        const timer = setTimeout(() => dismissToast(notification.id), 6000);

        return () => clearTimeout(timer);
    }, [paused, notification.id]);

    return <div
        className="vm-toast"
        onMouseEnter={() => setPaused(true)}
        onMouseLeave={() => setPaused(false)}
        onFocus={() => setPaused(true)}
        onBlur={() => setPaused(false)}
    >
        {href
            ? <Link href={href} className="vm-toast-main" onClick={open}>{body}</Link>
            : <button type="button" className="vm-toast-main" onClick={open}>{body}</button>}
        <button type="button" className="vm-toast-close" aria-label={isId ? 'Tutup' : 'Dismiss'} onClick={() => dismissToast(notification.id)}>
            <NavIcon name="x" size={14} />
        </button>
    </div>;
}

/** Newly arrived notifications as small stacked toasts. */
export function NotificationToasts({ locale }) {
    const { toasts } = useNotificationFeed();

    return <div className="vm-toast-stack" role="status" aria-live="polite">
        {toasts.map((toast) => <NotificationToast key={toast.id} notification={toast} locale={locale} />)}
    </div>;
}

/** Cadangan saat WebSocket tidak tersambung: ambil jumlah belum dibaca dari server secara berkala. */
export function NotificationPoll({ interval }) {
    usePoll(interval, { only: ['unreadNotifications', 'unreadMessages'] });

    return null;
}

export default function Notifications({ locale, categories, leaders, notifications }) {
    const isId = locale === 'id';
    const feed = useNotificationFeed();
    const unread = useUnreadCount();
    const [confirmClear, setConfirmClear] = useState(false);
    const items = mergeNotifications(feed, notifications.data);
    const readIds = items.filter((item) => item.read).map((item) => item.id);

    useEffect(() => {
        if (!confirmClear) return undefined;
        const timer = setTimeout(() => setConfirmClear(false), 4000);

        return () => clearTimeout(timer);
    }, [confirmClear]);

    const clearRead = () => {
        if (!confirmClear) return setConfirmClear(true);
        setConfirmClear(false);

        return clearReadNotifications(readIds).catch(() => {});
    };

    return <Layout locale={locale} categories={categories} leaders={leaders} active="notifications">
        <PageHead
            title={isId ? 'Notifikasi' : 'Notifications'}
            locale={locale}
            description={isId ? 'Balasan, suka, lencana, dan hasil review untuk akun Anda.' : 'Replies, likes, badges and review results for your account.'}
        />
        <div className="vm-notifications-page">
            <div className="vx-section-heading">
                <div>
                    <span className="vx-section-kicker">{isId ? 'AKTIVITAS ANDA' : 'YOUR ACTIVITY'}</span>
                    <h1>{isId ? 'Notifikasi' : 'Notifications'}</h1>
                </div>
                <div className="vm-notification-actions">
                    {unread > 0 && <button type="button" className="vm-notification-readall" onClick={() => markAllRead(items.map((item) => item.id))}>{isId ? 'Tandai semua dibaca' : 'Mark all read'}</button>}
                    {readIds.length > 0 && <button type="button" className={'vm-notification-readall is-danger' + (confirmClear ? ' is-confirming' : '')} onClick={clearRead}>
                        {confirmClear ? (isId ? `Yakin? Hapus ${numberLabel(readIds.length, locale)}` : `Sure? Delete ${numberLabel(readIds.length, locale)}`) : (isId ? 'Hapus yang sudah dibaca' : 'Delete read')}
                    </button>}
                </div>
            </div>

            {items.length ? <InfiniteScroll
                data="notifications"
                buffer={360}
                className="vm-notification-page-list"
                loading={() => <p className="vt-feed-scroll-status" role="status">{isId ? 'Memuat notifikasi...' : 'Loading notifications...'}</p>}
            >
                {items.map((item, index) => <NotificationItem key={item.id} notification={item} locale={locale} onOpen={markRead} onDelete={removeNotification} index={index} />)}
            </InfiniteScroll> : <div className="vt-empty">
                <div className="vt-empty-mark"><NavIcon name="bell" size={22} /></div>
                <h2>{isId ? 'Belum ada notifikasi' : 'No notifications yet'}</h2>
                <p>{isId ? 'Balasan, suka, dan lencana baru akan muncul di sini.' : 'Replies, likes and new badges will show up here.'}</p>
            </div>}
        </div>
    </Layout>;
}
