import { router, usePage } from '@inertiajs/react';
import { useEffect, useSyncExternalStore } from 'react';
import { api } from '../Pages/Community/Shared';

/**
 * Notification state that outlives page navigation: every page renders its own Layout,
 * so the WebSocket subscription, live items and toasts live here instead of in React state.
 */
const TOAST_LIMIT = 3;
const LIVE_LIMIT = 50;
const EMPTY = { userId: null, count: null, live: [], removed: [], read: [], toasts: [], connected: false, toastsEnabled: true };

let state = EMPTY;
let channelName = null;
let stopConnectionWatch = null;
let session = 0;
const listeners = new Set();

const update = (patch) => {
    state = { ...state, ...(typeof patch === 'function' ? patch(state) : patch) };
    listeners.forEach((listener) => listener());
};
const subscribe = (listener) => {
    listeners.add(listener);

    return () => listeners.delete(listener);
};
const upsert = (list, item) => [item, ...list.filter((entry) => entry.id !== item.id)];

export const useNotificationFeed = () => useSyncExternalStore(subscribe, () => state, () => EMPTY);

/** Unread count: the live value once an event or action set it, otherwise what the server shared. */
export function useUnreadCount() {
    const shared = usePage().props.unreadNotifications ?? 0;

    return useNotificationFeed().count ?? shared;
}

/** Live and server notifications as one list: newest first, without duplicates or deleted items. */
export function mergeNotifications(feed, base) {
    const removed = new Set(feed.removed);
    const read = new Set(feed.read);
    const seen = new Set();

    return [...feed.live, ...base]
        .filter((notification) => {
            if (removed.has(notification.id) || seen.has(notification.id)) return false;
            seen.add(notification.id);

            return true;
        })
        .map((notification) => (read.has(notification.id) && !notification.read ? { ...notification, read: true } : notification));
}

function receive({ notification, unread_count: unreadCount }, created) {
    update((current) => ({
        live: upsert(current.live, notification).slice(0, LIVE_LIMIT),
        removed: current.removed.filter((id) => id !== notification.id),
        count: unreadCount,
        toasts: created && current.toastsEnabled
            ? upsert(current.toasts, notification).slice(0, TOAST_LIMIT)
            : current.toasts.map((toast) => (toast.id === notification.id ? notification : toast)),
    }));
}

export async function startNotificationFeed(userId) {
    if (typeof window === 'undefined' || state.userId === userId) return;

    const { echo, echoIsConfigured } = await import('@laravel/echo-react');
    if (!echoIsConfigured()) return;

    stopNotificationFeed();
    const current = ++session;
    const connection = echo();
    const name = `App.Models.User.${userId}`;
    let wasDown = false;

    connection.private(name)
        .notification((payload) => receive(payload, true))
        .listen('.notification.refreshed', (payload) => receive(payload, false));
    channelName = name;
    const { connector } = connection;
    update({ userId, connected: connector.connectionStatus() === 'connected' });
    stopConnectionWatch = connector.onConnectionChange((status) => {
        if (current !== session) return;
        update({ connected: status === 'connected' });
        wasDown = wasDown || status !== 'connected';
        if (status === 'connected' && wasDown) {
            wasDown = false;
            router.reload({ only: ['unreadNotifications'] });
        }
    });
}

export function stopNotificationFeed() {
    session += 1;
    stopConnectionWatch?.();
    stopConnectionWatch = null;

    if (channelName) {
        import('@laravel/echo-react').then(({ echo, echoIsConfigured }) => echoIsConfigured() && echo().leave(channelName));
        channelName = null;
    }
    if (state !== EMPTY) update(EMPTY);
}

export const setToastsEnabled = (enabled) => update((current) => (current.toastsEnabled === enabled ? {} : { toastsEnabled: enabled, toasts: enabled ? current.toasts : [] }));
export const resetUnreadCount = () => state.count !== null && update({ count: null });
export const dismissToast = (id) => update((current) => ({ toasts: current.toasts.filter((toast) => toast.id !== id) }));

export function markRead(notification) {
    if (notification.read || state.read.includes(notification.id)) return Promise.resolve();
    update((current) => ({ read: [...current.read, notification.id], toasts: current.toasts.filter((toast) => toast.id !== notification.id) }));

    return api(`/backend/notifications/${notification.id}/read`, 'PATCH').then((result) => update({ count: result.unread_count })).catch(() => {});
}

export const markAllRead = (ids) => api('/backend/notifications/read-all', 'POST')
    .then(() => update((current) => ({ read: [...current.read, ...ids], toasts: [], count: 0 })))
    .catch(() => {});

/** Rejects when the server refuses, so the caller can restore the item. */
export const removeNotification = (notification) => api(`/backend/notifications/${notification.id}`, 'DELETE').then((result) => update((current) => ({
    removed: [...current.removed, notification.id],
    live: current.live.filter((entry) => entry.id !== notification.id),
    toasts: current.toasts.filter((toast) => toast.id !== notification.id),
    count: result.unread_count,
})));

export const clearReadNotifications = (ids) => api('/backend/notifications?scope=read', 'DELETE').then((result) => update((current) => ({
    removed: [...current.removed, ...ids],
    count: result.unread_count,
})));

/** Wires the feed to the signed in member: subscribes to their channel and keeps toasts and the server count in step. */
export function useNotificationFeedLifecycle(userId, toastsEnabled) {
    const shared = usePage().props.unreadNotifications;

    useEffect(() => {
        if (userId) {
            startNotificationFeed(userId);
        } else {
            stopNotificationFeed();
        }
    }, [userId]);
    useEffect(() => {
        setToastsEnabled(toastsEnabled);
    }, [toastsEnabled]);
    useEffect(() => {
        resetUnreadCount();
    }, [shared]);
}
