import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { api, CoinIcon, dictionary, Layout, numberLabel, PageHead, PaginationLinks, path } from './Shared';

const JAKARTA_OFFSET_MS = 7 * 60 * 60 * 1000;
const DAY_MS = 24 * 60 * 60 * 1000;

const prefersReducedMotion = () => typeof window !== 'undefined'
    && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/** The Jakarta calendar day of a timestamp, as "YYYY-MM-DD". Jakarta has no daylight saving. */
const jakartaDay = (time) => new Date(time + JAKARTA_OFFSET_MS).toISOString().slice(0, 10);

/** Animate a number from its previous value to the target with an ease-out curve. */
function useCountUp(target, duration = 900) {
    const [value, setValue] = useState(target);
    const valueRef = useRef(target);

    useEffect(() => {
        if (prefersReducedMotion() || valueRef.current === target) {
            valueRef.current = target;
            setValue(target);
            return undefined;
        }

        const from = valueRef.current;
        const start = performance.now();
        let frame;
        const tick = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            const next = Math.round(from + (target - from) * (1 - Math.pow(1 - progress, 3)));
            valueRef.current = next;
            setValue(next);
            if (progress < 1) frame = requestAnimationFrame(tick);
        };
        frame = requestAnimationFrame(tick);

        return () => cancelAnimationFrame(frame);
    }, [target, duration]);

    return value;
}

/** Milliseconds left until the next 00:00 Jakarta time, refreshed every second. */
function useJakartaMidnightCountdown(onMidnight) {
    const remainingAt = (now) => DAY_MS - ((now + JAKARTA_OFFSET_MS) % DAY_MS);
    const [remaining, setRemaining] = useState(() => remainingAt(Date.now()));
    const callbackRef = useRef(onMidnight);
    callbackRef.current = onMidnight;

    useEffect(() => {
        const interval = window.setInterval(() => {
            setRemaining((previous) => {
                const next = remainingAt(Date.now());
                if (next > previous) callbackRef.current?.();
                return next;
            });
        }, 1000);

        return () => window.clearInterval(interval);
    }, []);

    return remaining;
}

function countdownParts(milliseconds) {
    const totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));

    return [Math.floor(totalSeconds / 3600), Math.floor((totalSeconds % 3600) / 60), totalSeconds % 60]
        .map((part) => String(part).padStart(2, '0'));
}

const LEDGER_ICONS = {
    daily_bonus: <><rect x="4" y="9" width="16" height="11" rx="1.5" /><path d="M3 9h18M12 9v11M12 9c-1.5-3-5-3.5-5-1.2C7 9 12 9 12 9Zm0 0c1.5-3 5-3.5 5-1.2C17 9 12 9 12 9Z" /></>,
    streak_bonus: <path d="M12 3c1 3 4 4.6 4 8.6a4 4 0 0 1-8 0c0-1.9.9-3.1 2-4 0 1.8.8 2.9 2 3 0-2.8-1.1-4.9 0-7.6Z" />,
    mission_reward: <><circle cx="12" cy="12" r="8" /><circle cx="12" cy="12" r="4" /><circle cx="12" cy="12" r="0.8" /></>,
    achievement: <><circle cx="12" cy="9" r="5" /><path d="m9 13.5-1.5 7 4.5-2.5 4.5 2.5-1.5-7" /></>,
    support: <path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z" />,
    reversal: <><path d="M9 7 4 12l5 5" /><path d="M4 12h11a5 5 0 0 1 0 10h-2" /></>,
};

function LedgerIcon({ type }) {
    return <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        {LEDGER_ICONS[type] ?? <path d="M12 5v14M5 12h14" />}
    </svg>;
}

const MISSION_TAB_ICONS = {
    daily: <><rect x="3.5" y="5" width="17" height="15.5" rx="2.5" /><path d="M8 3v4M16 3v4M3.5 10h17" /><path d="m9 15 2 2 4-4" /></>,
    weekly: <><rect x="3.5" y="5" width="17" height="15.5" rx="2.5" /><path d="M8 3v4M16 3v4M3.5 10h17" /><path d="M8 14h8M8 17.2h5" /></>,
    challenges: <><path d="M8 4h8v5a4 4 0 0 1-8 0V4Z" /><path d="M8 6H5.5A1.5 1.5 0 0 0 4 7.5C4 9.7 5.6 11 8 11M16 6h2.5A1.5 1.5 0 0 1 20 7.5C20 9.7 18.4 11 16 11" /><path d="M12 13v4M8.5 20h7M9.5 20l.5-3h4l.5 3" /></>,
};

function MissionTabIcon({ type }) {
    return <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        {MISSION_TAB_ICONS[type]}
    </svg>;
}

const COIN_BURST = Array.from({ length: 12 }, (_, index) => {
    const angle = (index / 12) * Math.PI * 2;
    const distance = 70 + (index % 3) * 22;

    return { x: Math.round(Math.cos(angle) * distance), y: Math.round(Math.sin(angle) * distance - 30), delay: (index % 4) * 40 };
});

function GrandChestIllustration({ state }) {
    const isReady = state === 'ready';
    const isClaimed = state === 'claimed';

    return (
        <svg viewBox="0 0 64 64" width="56" height="56" fill="none" aria-hidden="true">
            <circle cx="32" cy="32" r="28" fill={isReady ? '#fef08a' : isClaimed ? '#d1fae5' : '#f1f5f9'} opacity="0.6" />
            <rect x="12" y="26" width="40" height="26" rx="4" fill={isClaimed ? '#059669' : '#d97706'} stroke={isClaimed ? '#047857' : '#b45309'} strokeWidth="2.5" />
            <path
                d={isReady ? "M10 24c0-7 9-12 22-12s22 5 22 12l-4 4H14z" : "M12 26c0-7 8-11 20-11s20 4 20 11H12z"}
                fill={isClaimed ? '#10b981' : '#f59e0b'}
                stroke={isClaimed ? '#059669' : '#b45309'}
                strokeWidth="2.5"
            />
            <rect x="22" y="26" width="5" height="26" fill={isClaimed ? '#34d399' : '#fbbf24'} />
            <rect x="37" y="26" width="5" height="26" fill={isClaimed ? '#34d399' : '#fbbf24'} />
            <circle cx="32" cy="36" r="4.5" fill="#1e293b" />
            <path d="M32 37v4" stroke="#fbbf24" strokeWidth="2" strokeLinecap="round" />
            {isReady && (
                <>
                    <path d="M14 12l1.5 3.5L19 17l-3.5 1.5L14 22l-1.5-3.5L9 17l3.5-1.5z" fill="#f59e0b" />
                    <path d="M50 14l1 2.5L53.5 17.5 51 18.5 50 21l-1-2.5L46.5 17.5 49 16.5z" fill="#f59e0b" />
                </>
            )}
            <path d="M28 20l2 2 2-3 2 3 2-2v3h-8z" fill="#fef08a" />
        </svg>
    );
}

function MissionIcon({ name, size = 22, className = '' }) {
    const icons = {
        quick_vote: (
            <>
                <path d="M14.5 17.5 3 6V3h3l11.5 11.5" />
                <path d="m13 19 6 2 2-6-4-1" />
                <path d="M9.5 17.5 21 6V3h-3L6.5 14.5" />
                <path d="m11 19-6 2-2-6 4-1" />
            </>
        ),
        support: (
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
        ),
        write_comments: (
            <>
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                <path d="M8 11h8M8 15h5" />
            </>
        ),
        like_comments: (
            <>
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
                <path d="M12 9v4M10 11h4" opacity="0.6" />
            </>
        ),
        save_ranking: (
            <>
                <path d="M19 21l-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                <circle cx="12" cy="10" r="2.5" />
            </>
        ),
        mention_friend: (
            <>
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </>
        ),
        article_comment: (
            <>
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                <path d="M8 7h8M8 11h6" />
            </>
        ),
        perfect_day: (
            <>
                <path d="M2 4l3 12h14l3-12-5 5-5-7-5 7-5-5z" />
                <circle cx="12" cy="19" r="1.5" />
                <circle cx="6" cy="19" r="1" />
                <circle cx="18" cy="19" r="1" />
            </>
        ),
        create_ranking: (
            <>
                <path d="M6 9H3a2 2 0 0 0-2 2v1a6 6 0 0 0 6 6M18 9h3a2 2 0 0 1 2 2v1a6 6 0 0 1-6 6" />
                <path d="M4 22h16M12 18v4M7 2h10v8a5 5 0 0 1-10 0V2Z" />
            </>
        ),
        write_article: (
            <>
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" y1="13" x2="8" y2="13" />
                <line x1="16" y1="17" x2="8" y2="17" />
            </>
        ),
        improve_summary: (
            <>
                <path d="M6 3h12l4 6-10 12L2 9l4-6z" />
                <path d="M2 9h20M10 3l-2 6 4 12 4-12-2-6" />
            </>
        ),
        submit_object: (
            <>
                <circle cx="12" cy="12" r="10" />
                <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76" />
            </>
        ),
        daily_complete: (
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
        ),
        popular_ranking: (
            <>
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                <circle cx="12" cy="12" r="3" fill="currentColor" opacity="0.3" />
            </>
        ),
        discussed_ranking: (
            <>
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                <path d="M8 9h8M8 13h5" />
            </>
        ),
        read_article: (
            <>
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
            </>
        ),
        liked_comment: (
            <>
                <circle cx="12" cy="14" r="7" />
                <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88" />
                <path d="m12 11 1 2.2 2.4.3-1.8 1.6.5 2.4-2.1-1.2-2.1 1.2.5-2.4-1.8-1.6 2.4-.3L12 11z" />
            </>
        ),
    };

    return (
        <svg
            className={className}
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            {icons[name] ?? <circle cx="12" cy="12" r="8" />}
        </svg>
    );
}

export default function Wallet({ locale, categories, leaders, balance, claimedToday, flow, ledger, streak, missions }) {
    const t = dictionary(locale);
    const isId = locale === 'id';
    const query = new URLSearchParams(usePage().url.split('?')[1] ?? '');
    const rankingSlug = query.get('ranking');
    const entryId = Number(query.get('entry'));
    const requestedAmount = Number(query.get('amount'));
    const hasReturnContext = /^[a-z0-9-]+$/i.test(rankingSlug ?? '')
        && Number.isSafeInteger(entryId) && entryId > 0
        && Number.isSafeInteger(requestedAmount) && requestedAmount > 0;
    const returnParams = hasReturnContext ? new URLSearchParams({
        ranking: rankingSlug,
        entry: String(entryId),
        amount: String(requestedAmount),
    }) : null;
    const returnUrl = hasReturnContext
        ? `${path(locale, `/ranking/${encodeURIComponent(rankingSlug)}`)}?${new URLSearchParams({ support: String(entryId), amount: String(requestedAmount) })}`
        : null;
    const [busy, setBusy] = useState(false);
    const [busyMission, setBusyMission] = useState(null);
    const [alert, setAlert] = useState(null);
    const [displayedBalance, setDisplayedBalance] = useState(balance);
    const [isClaimed, setIsClaimed] = useState(claimedToday);
    const [gain, setGain] = useState(0);
    const [burstKey, setBurstKey] = useState(0);
    const [openEntry, setOpenEntry] = useState(null);
    const [displayedStreak, setDisplayedStreak] = useState(streak);
    const [missionTab, setMissionTab] = useState('daily');
    const animatedBalance = useCountUp(displayedBalance);
    const untilMidnight = useJakartaMidnightCountdown(() => router.reload({ only: ['claimedToday', 'streak', 'missions'] }));
    const [hours, minutes, seconds] = countdownParts(untilMidnight);

    useEffect(() => setDisplayedBalance(balance), [balance]);
    useEffect(() => setIsClaimed(claimedToday), [claimedToday]);
    useEffect(() => setDisplayedStreak(streak), [streak]);
    useEffect(() => setOpenEntry(null), [flow]);
    useEffect(() => {
        if (!gain) return undefined;

        const timeout = window.setTimeout(() => setGain(0), 1600);
        return () => window.clearTimeout(timeout);
    }, [gain]);
    useEffect(() => {
        if (!burstKey) return undefined;

        const timeout = window.setTimeout(() => setBurstKey(0), 1100);
        return () => window.clearTimeout(timeout);
    }, [burstKey]);

    const claim = async () => {
        setBusy(true);
        setAlert(null);

        try {
            const result = await api('/backend/wallet/claim', 'POST');
            setDisplayedBalance(result.balance);
            setIsClaimed(true);
            setDisplayedStreak(result.streak);
            setGain(result.claimed ? 5 + result.streak_bonus : 0);
            if (result.claimed) setBurstKey(Date.now());
            setAlert({
                type: 'success',
                text: !result.claimed
                    ? (isId ? 'Bonus hari ini sudah Anda klaim.' : 'You have already claimed today’s bonus.')
                    : result.streak_bonus > 0
                        ? (isId ? `5 poin plus bonus streak ${result.streak_bonus} poin sudah masuk ke dompet Anda.` : `5 points plus a ${result.streak_bonus}-point streak bonus were added to your wallet.`)
                        : (isId ? '5 poin sudah masuk ke dompet Anda.' : '5 points were added to your wallet.'),
            });
            router.reload({ only: ['balance', 'claimedToday', 'ledger', 'streak', 'missions', 'walletBalance'] });
        } catch (error) {
            setAlert({
                type: 'error',
                text: error.message || (isId ? 'Poin belum dapat diklaim. Coba lagi.' : 'Points could not be claimed. Please try again.'),
            });
        } finally {
            setBusy(false);
        }
    };

    const claimMission = async (completionId) => {
        setBusyMission(completionId);
        setAlert(null);

        try {
            const result = await api(`/backend/wallet/missions/${completionId}/claim`, 'POST');
            setDisplayedBalance(result.balance);
            setGain(result.amount);
            if (result.claimed) setBurstKey(Date.now());
            setAlert({
                type: 'success',
                text: result.claimed
                    ? (isId ? `🎉 ${result.amount} poin hadiah misi masuk ke dompet Anda!` : `🎉 ${result.amount} mission points were added to your wallet!`)
                    : (isId ? 'Hadiah misi ini sudah diklaim.' : 'This mission reward was already claimed.'),
            });
            router.reload({ only: ['balance', 'ledger', 'missions', 'summary', 'walletBalance'] });
        } catch (error) {
            setAlert({
                type: 'error',
                text: error.message || (isId ? 'Hadiah misi belum dapat diklaim. Coba lagi.' : 'Mission reward could not be claimed. Please try again.'),
            });
            router.reload({ only: ['missions'] });
        } finally {
            setBusyMission(null);
        }
    };

    const transactionLabel = (type) => ({
        daily_bonus: isId ? 'Bonus harian' : 'Daily bonus',
        support: isId ? 'Dukungan ranking' : 'Ranking support',
        reversal: isId ? 'Pengembalian poin' : 'Points returned',
        streak_bonus: isId ? 'Bonus streak' : 'Streak bonus',
        mission_reward: isId ? 'Hadiah misi' : 'Mission reward',
        achievement: isId ? 'Hadiah badge' : 'Badge reward',
    })[type] ?? type.replace(/_/g, ' ');
    const explore = path(locale, '/explore');
    const create = path(locale, '/create');
    const missionCopy = isId ? {
        quick_vote: { tag: 'DUEL CEPAT', theme: 'crimson', title: 'Ikut Quick Vote', text: 'Pilih salah satu kandidat di duel hari ini.', href: path(locale), action: 'Ikut duel' },
        support: { tag: 'DUKUNGAN', theme: 'amber', title: 'Dukung sebuah ranking', text: 'Beri poin dukungan ke kandidat mana pun.', href: explore, action: 'Cari ranking' },
        write_comments: { tag: 'DISKUSI', theme: 'blue', title: 'Ikut berdiskusi', text: 'Tulis komentar di ranking, objek, atau artikel.', href: explore, action: 'Cari diskusi' },
        like_comments: { tag: 'APRESIASI', theme: 'rose', title: 'Apresiasi komentar', text: 'Sukai komentar dari pengguna lain.', href: explore, action: 'Baca komentar' },
        save_ranking: { tag: 'KOLEKSI', theme: 'purple', title: 'Simpan ranking', text: 'Simpan ranking buatan pengguna lain yang menarik.', href: explore, action: 'Cari ranking' },
        mention_friend: { tag: 'TEMAN', theme: 'teal', title: 'Ajak teman', text: 'Mention pengguna lain dengan @username di komentar.', href: explore, action: 'Tulis komentar' },
        article_comment: { tag: 'ARTIKEL', theme: 'emerald', title: 'Komentari artikel', text: 'Beri tanggapan pada artikel sebuah objek.', href: explore, action: 'Cari artikel' },
        perfect_day: { tag: 'PETI UTAMA', theme: 'gold', title: 'Hari sempurna', text: 'Selesaikan semua misi hari ini untuk bonus tambahan.' },
        create_ranking: { tag: 'KREATOR', theme: 'purple', title: 'Buat ranking baru', text: 'Terbitkan ranking buatan Anda sendiri minggu ini.', href: create, action: 'Buat ranking' },
        write_article: { tag: 'PENULIS', theme: 'blue', title: 'Tulis artikel objek', text: 'Tulis artikel baru minggu ini dan lolos moderasi.', href: explore, action: 'Pilih objek' },
        improve_summary: { tag: 'KURASI', theme: 'emerald', title: 'Perbaiki ringkasan', text: 'Ringkasan objek yang Anda tulis minggu ini terbit.', href: explore, action: 'Pilih objek' },
        submit_object: { tag: 'EKSPLORASI', theme: 'amber', title: 'Ajukan objek baru', text: 'Objek yang Anda ajukan minggu ini terverifikasi.', href: create, action: 'Ajukan objek' },
        daily_complete: { tag: 'KONSISTENSI', theme: 'orange', title: 'Rajin setiap hari', text: 'Raih hari sempurna di beberapa hari minggu ini.' },
        popular_ranking: { tag: 'POPULER', theme: 'purple', title: 'Ranking populer', text: 'Ranking baru Anda didukung pengguna berbeda dalam 7 hari pertamanya.', href: create, action: 'Buat ranking' },
        discussed_ranking: { tag: 'DISKUSI RAMAI', theme: 'rose', title: 'Ranking ramai diskusi', text: 'Ranking baru Anda dikomentari minimal 5 pengguna berbeda dalam 7 hari pertamanya.', href: create, action: 'Buat ranking' },
        read_article: { tag: 'BANYAK DIBACA', theme: 'blue', title: 'Artikel dibaca komunitas', text: 'Artikel baru Anda dikomentari pengguna berbeda dalam 7 hari pertamanya.', href: explore, action: 'Pilih objek' },
        liked_comment: { tag: 'KOMENTAR TERBAIK', theme: 'amber', title: 'Komentar berbobot', text: 'Satu komentar Anda minggu ini disukai pengguna berbeda dalam 7 hari.', href: explore, action: 'Ikut diskusi' },
    } : {
        quick_vote: { tag: 'QUICK DUEL', theme: 'crimson', title: 'Join the Quick Vote', text: 'Pick a candidate in today’s duel.', href: path(locale), action: 'Join duel' },
        support: { tag: 'SUPPORT', theme: 'amber', title: 'Support a ranking', text: 'Give support points to any candidate.', href: explore, action: 'Find rankings' },
        write_comments: { tag: 'DISCUSSION', theme: 'blue', title: 'Join the discussion', text: 'Comment on rankings, objects, or articles.', href: explore, action: 'Find discussions' },
        like_comments: { tag: 'APPRECIATION', theme: 'rose', title: 'Appreciate comments', text: 'Like comments from other members.', href: explore, action: 'Read comments' },
        save_ranking: { tag: 'COLLECTION', theme: 'purple', title: 'Save a ranking', text: 'Save an interesting ranking made by another member.', href: explore, action: 'Find rankings' },
        mention_friend: { tag: 'PARTY', theme: 'teal', title: 'Bring a friend', text: 'Mention another member with @username in a comment.', href: explore, action: 'Write a comment' },
        article_comment: { tag: 'ARTICLE', theme: 'emerald', title: 'Comment on an article', text: 'Respond to an object’s article.', href: explore, action: 'Find articles' },
        perfect_day: { tag: 'GRAND BOUNTY', theme: 'gold', title: 'Perfect day', text: 'Finish every mission today for an extra bonus.' },
        create_ranking: { tag: 'CREATOR', theme: 'purple', title: 'Create a ranking', text: 'Publish a ranking of your own this week.', href: create, action: 'Create ranking' },
        write_article: { tag: 'WRITER', theme: 'blue', title: 'Write an object article', text: 'Write a new article this week that passes review.', href: explore, action: 'Pick an object' },
        improve_summary: { tag: 'CURATION', theme: 'emerald', title: 'Improve a summary', text: 'An object summary you write this week gets published.', href: explore, action: 'Pick an object' },
        submit_object: { tag: 'DISCOVERY', theme: 'amber', title: 'Submit a new object', text: 'An object you submit this week gets verified.', href: create, action: 'Submit object' },
        daily_complete: { tag: 'STREAK', theme: 'orange', title: 'Every day counts', text: 'Reach a perfect day on several days this week.' },
        popular_ranking: { tag: 'VIRAL', theme: 'purple', title: 'Popular ranking', text: 'Your new ranking is supported by different members in its first 7 days.', href: create, action: 'Create ranking' },
        discussed_ranking: { tag: 'LIVELY', theme: 'rose', title: 'Lively ranking', text: 'Your new ranking draws comments from at least 5 members in its first 7 days.', href: create, action: 'Create ranking' },
        read_article: { tag: 'READS', theme: 'blue', title: 'Article people read', text: 'Your new article draws comments from different members in its first 7 days.', href: explore, action: 'Pick an object' },
        liked_comment: { tag: 'TOP COMMENT', theme: 'amber', title: 'Valued comment', text: 'One of your comments this week is liked by different members within 7 days.', href: explore, action: 'Join a discussion' },
    };

    const dailyClaimable = missions.daily.filter((m) => m.earned !== null && !m.claimed).length
        + (missions.perfect_day && missions.perfect_day.earned !== null && !missions.perfect_day.claimed ? 1 : 0);
    const weeklyClaimable = missions.weekly.filter((m) => m.earned !== null && !m.claimed).length;
    const challengesClaimable = missions.challenges.filter((m) => m.earned !== null && !m.claimed).length;

    const missionTabs = [
        { value: 'daily', label: isId ? 'Misi Harian' : 'Daily', icon: 'daily', items: missions.daily, claimableCount: dailyClaimable },
        { value: 'weekly', label: isId ? 'Mingguan' : 'Weekly', icon: 'weekly', items: missions.weekly, claimableCount: weeklyClaimable },
        { value: 'challenges', label: isId ? 'Tantangan' : 'Challenges', icon: 'challenges', items: missions.challenges, claimableCount: challengesClaimable },
    ];
    const activeMissions = missionTabs.find((tab) => tab.value === missionTab).items;
    const daysToWeekEnd = Math.max(1, Math.ceil((new Date(missions.week_ends_at).getTime() - Date.now()) / DAY_MS));
    const claimsToBonus = displayedStreak.cycle_days - displayedStreak.cycle_progress;
    const timeLabel = (value) => new Intl.DateTimeFormat(isId ? 'id-ID' : 'en-US', { timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(value));
    const today = jakartaDay(Date.now());
    const yesterday = jakartaDay(Date.now() - DAY_MS);
    const dayLabel = (day) => day === today
        ? (isId ? 'Hari ini' : 'Today')
        : day === yesterday
            ? (isId ? 'Kemarin' : 'Yesterday')
            : new Intl.DateTimeFormat(isId ? 'id-ID' : 'en-US', { dateStyle: 'full', timeZone: 'UTC' }).format(new Date(`${day}T00:00:00Z`));
    const ledgerDays = ledger.data.reduce((days, item) => {
        const day = jakartaDay(new Date(item.created_at).getTime());
        const group = days.at(-1)?.day === day ? days.at(-1) : (days.push({ day, items: [], net: 0 }), days.at(-1));
        group.items.push(item);
        group.net += item.amount;
        return days;
    }, []);

    const filters = [
        { value: 'all', label: isId ? 'Semua' : 'All' },
        { value: 'incoming', label: isId ? 'Poin masuk' : 'Received' },
        { value: 'outgoing', label: isId ? 'Poin keluar' : 'Spent' },
    ];
    const walletFilterUrl = (filterValue) => {
        const params = new URLSearchParams(returnParams ?? undefined);
        if (filterValue !== 'all') params.set('flow', filterValue);
        const search = params.toString();

        return path(locale, '/wallet') + (search ? `?${search}` : '');
    };
    return (
        <Layout locale={locale} categories={categories} leaders={leaders} active="wallet">
            <PageHead title={t.wallet} locale={locale} />
            <div className="vwl-page">
                <header className="vwl-header">
                    <p className="vwl-kicker">{t.community} / {t.wallet}</p>
                    <h1>{t.wallet}</h1>
                    <p>{isId ? 'Jaga streak harian, selesaikan misi, dan kumpulkan poin untuk mendukung pilihan Anda.' : 'Keep your daily streak, complete missions, and earn points to support your choices.'}</p>
                    <section className="vwl-balance-card" aria-label={t.balance}>
                        <div className="vwl-balance-main">
                            <span className="vwl-balance-symbol" aria-hidden="true"><CoinIcon /></span>
                            <div>
                                <span className="vwl-balance-label">{t.balance}</span>
                                <div className="vwl-balance-amount">
                                    <strong aria-live="polite">{numberLabel(animatedBalance, locale)}</strong>
                                    <span>{t.points}</span>
                                    {gain > 0 && <span className="vwl-gain" aria-hidden="true">+{gain}</span>}
                                </div>
                            </div>
                        </div>
                        <Link className="vwl-explore-link" href={path(locale, '/explore')}>{isId ? 'Lihat ranking' : 'See rankings'} <span aria-hidden="true">↗</span></Link>
                    </section>
                </header>

                {alert && <div className={'vwl-alert is-' + alert.type} role={alert.type === 'error' ? 'alert' : 'status'}>
                    <p>{alert.text}</p>
                    <button type="button" onClick={() => setAlert(null)} aria-label={isId ? 'Tutup pesan' : 'Dismiss message'}>×</button>
                </div>}

                <div className="vwl-progress">
                    <section className="vwl-streak" aria-labelledby="vwl-streak-heading">
                        <header className="vwl-card-heading">
                            <h2 id="vwl-streak-heading">{isId ? 'Streak harian' : 'Daily streak'}</h2>
                            <span>{isId ? `Terpanjang ${numberLabel(displayedStreak.longest, locale)} hari` : `Best ${numberLabel(displayedStreak.longest, locale)} days`}</span>
                        </header>
                        <div className={'vwl-streak-count' + (displayedStreak.days > 0 ? ' is-active' : '')}>
                            <svg className="vwl-streak-flame" viewBox="0 0 24 24" width="34" height="34" aria-hidden="true">
                                <path d="M12 2.5c1.2 3.4 5 5.2 5 9.8a5 5 0 0 1-10 0c0-2.3 1.1-3.7 2.4-4.8 0 2.1.9 3.4 2.4 3.5 0-3.3-1.3-5.7.2-8.5Z" />
                            </svg>
                            <strong key={displayedStreak.days}>{numberLabel(displayedStreak.days, locale)}</strong>
                            <span>{isId ? 'hari berturut-turut' : displayedStreak.days === 1 ? 'day in a row' : 'days in a row'}</span>
                        </div>
                        <ol className="vwl-streak-track" aria-label={isId ? `${displayedStreak.cycle_progress} dari ${displayedStreak.cycle_days} hari menuju bonus` : `${displayedStreak.cycle_progress} of ${displayedStreak.cycle_days} days toward the bonus`}>
                            {Array.from({ length: displayedStreak.cycle_days }, (_, index) => {
                                const isReward = index === displayedStreak.cycle_days - 1;
                                const isNext = !isClaimed && index === displayedStreak.cycle_progress;

                                return <li
                                    key={index}
                                    style={{ '--i': index }}
                                    className={(index < displayedStreak.cycle_progress ? 'is-done' : '') + (isReward ? ' is-reward' : '') + (isNext ? ' is-next' : '')}
                                    title={isNext ? (isId ? 'Klaim hari ini' : 'Claim today') : undefined}
                                >{isReward ? `+${displayedStreak.bonus}` : index + 1}</li>;
                            })}
                        </ol>
                        <p className="vwl-streak-note">{claimsToBonus === 0
                            ? (isId ? `Bonus streak +${displayedStreak.bonus} poin sudah masuk hari ini. Siklus baru dimulai besok.` : `Your +${displayedStreak.bonus} streak bonus landed today. A new cycle starts tomorrow.`)
                            : (isId ? `Klaim ${claimsToBonus} hari lagi berturut-turut untuk bonus +${displayedStreak.bonus} poin. Satu hari terlewat, streak dimulai ulang.` : `Claim ${claimsToBonus} more days in a row for a +${displayedStreak.bonus} bonus. Miss a day and the streak restarts.`)}</p>
                        <div className={'vwl-claim' + (isClaimed ? ' is-claimed' : ' is-available')}>
                            <div className="vwl-claim-copy">
                                <span className="vwl-claim-label">{isId ? 'Bonus harian' : 'Daily bonus'}</span>
                                <strong>+5 <small>{t.points}</small></strong>
                                <span className="vwl-claim-status">{isClaimed ? (isId ? 'Sudah diklaim hari ini' : 'Claimed today') : (isId ? 'Tersedia sekarang' : 'Available now')}</span>
                            </div>
                            <div className="vwl-claim-control">
                                {isClaimed && <span className="vwl-claim-reset" role="timer" aria-label={isId ? `Bonus berikutnya dalam ${hours} jam ${minutes} menit` : `Next bonus in ${hours} hours ${minutes} minutes`}>
                                    {isId ? 'Berikutnya' : 'Next'} {hours}:{minutes}:{seconds}
                                </span>}
                                <div className="vwl-claim-action">
                                    <button type="button" disabled={busy || isClaimed} onClick={claim}>
                                        {busy ? (isId ? 'Memproses...' : 'Processing...') : isClaimed ? (isId ? 'Diklaim hari ini ✓' : 'Claimed today ✓') : t.claimDaily}
                                    </button>
                                    {burstKey > 0 && <span key={burstKey} className="vwl-coin-burst" aria-hidden="true">
                                        {COIN_BURST.map((coin, index) => <span
                                            key={index}
                                            style={{ '--x': `${coin.x}px`, '--y': `${coin.y}px`, animationDelay: `${coin.delay}ms` }}
                                        ><CoinIcon /></span>)}
                                    </span>}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="vwl-missions" aria-labelledby="vwl-missions-heading">
                        <header className="vwl-quest-hub-header">
                            <div className="vwl-quest-hub-main">
                                <div className="vwl-quest-hub-crest" aria-hidden="true">
                                    <svg viewBox="0 0 40 40" width="38" height="38" fill="none">
                                        <circle cx="20" cy="20" r="19" fill="#eef4ff" stroke="#3b82f6" strokeWidth="2" />
                                        <path d="M12 28l6-6M28 12l-6 6M12 12l16 16M12 12l4 1-1 4M28 28l-4-1 1-4" stroke="#1d4ed8" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" />
                                        <polygon points="20 7 22 13 28 13 23 17 25 23 20 19 15 23 17 17 12 13 18 13" fill="#f59e0b" />
                                    </svg>
                                </div>
                                <div className="vwl-quest-hub-text">
                                    <h2 id="vwl-missions-heading">{isId ? 'Papan Misi' : 'Missions'}</h2>
                                    <p>{isId ? 'Selesaikan misi harian & mingguan untuk mengumpulkan poin dan membuka peti bonus.' : 'Complete daily and weekly quests to earn points and unlock bonus chests.'}</p>
                                </div>
                            </div>
                        </header>

                        <div className="vwl-quest-tabs" role="tablist" aria-label={isId ? 'Jenis misi' : 'Mission type'}>
                            {missionTabs.map((tab) => (
                                <button
                                    key={tab.value}
                                    type="button"
                                    role="tab"
                                    id={`vwl-mission-tab-${tab.value}`}
                                    aria-selected={missionTab === tab.value}
                                    aria-controls="vwl-mission-panel"
                                    className={`vwl-quest-tab ${missionTab === tab.value ? 'is-active' : ''}`}
                                    onClick={() => setMissionTab(tab.value)}
                                >
                                    <span className="vwl-quest-tab-icon" aria-hidden="true"><MissionTabIcon type={tab.icon} /></span>
                                    <span className="vwl-quest-tab-label">{tab.label}</span>
                                    {tab.claimableCount > 0 && (
                                        <span className="vwl-quest-tab-loot-badge" aria-label={isId ? `${tab.claimableCount} hadiah siap diklaim` : `${tab.claimableCount} rewards ready`}>
                                            {tab.claimableCount}<span className="vwl-quest-tab-loot-text"> {isId ? 'Siap' : 'Ready'}</span>
                                        </span>
                                    )}
                                </button>
                            ))}
                        </div>

                        {missionTab === 'daily' && missions.perfect_day && (() => {
                            const p = missions.perfect_day;
                            const isDone = p.earned !== null;
                            const isClaimed = p.claimed;
                            const isReady = isDone && !isClaimed;
                            const state = isClaimed ? 'claimed' : isReady ? 'ready' : 'locked';
                            const remainingToUnlock = Math.max(0, p.target - p.progress);

                            return (
                                <div className={`vwl-grand-chest ${isReady ? 'is-ready' : isDone ? 'is-done' : 'is-progress'}`} aria-label={isId ? 'Misi Utama: Hari Sempurna' : 'Daily Grand Bounty: Perfect Day'}>
                                    <div className="vwl-grand-chest-badge-row">
                                        {isReady && <span className="vwl-grand-chest-ready-pill">✨ {isId ? 'PETI TERBUKA!' : 'CHEST UNLOCKED!'}</span>}
                                        {isClaimed && <span className="vwl-grand-chest-done-pill">✓ {isId ? 'SUDAH DIKLAIM' : 'CLAIMED'}</span>}
                                    </div>

                                    <div className="vwl-grand-chest-content">
                                        <div className="vwl-grand-chest-visual">
                                            <GrandChestIllustration state={state} />
                                        </div>

                                        <div className="vwl-grand-chest-details">
                                            <div className="vwl-grand-chest-header-line">
                                                <h3 className="vwl-grand-chest-title">{isId ? 'Hari Sempurna' : 'Perfect Day'}</h3>
                                                <div className="vwl-grand-chest-reward-badge" aria-label={isId ? `Bonus +${p.points} poin` : `Bonus +${p.points} points`}>
                                                    <span className="vwl-grand-chest-coin"><CoinIcon /></span>
                                                    <strong>+{numberLabel(p.points, locale)} {t.points}</strong>
                                                </div>
                                            </div>
                                            <p className="vwl-grand-chest-desc">
                                                {isId
                                                    ? 'Selesaikan semua misi harian hari ini untuk membuka peti emas dan mendapatkan bonus tambahan!'
                                                    : 'Complete all daily quests today to unlock this golden chest and claim extra bonus points!'}
                                            </p>

                                            <div className="vwl-grand-chest-tracker">
                                                <div className="vwl-grand-chest-steps">
                                                    {missions.daily.map((m, idx) => {
                                                        const copy = missionCopy[m.key] ?? { title: m.key };
                                                        const stepDone = m.earned !== null;
                                                        return (
                                                            <div key={m.key} className={`vwl-grand-step ${stepDone ? 'is-done' : ''}`} title={`${copy.title}: ${stepDone ? (isId ? 'Selesai' : 'Completed') : (isId ? 'Belum selesai' : 'Pending')}`}>
                                                                <span className="vwl-grand-step-icon">{stepDone ? '✓' : idx + 1}</span>
                                                                <span className="vwl-grand-step-text">{copy.title}</span>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                                <div className="vwl-grand-chest-count-badge">
                                                    {p.progress} / {p.target} {isId ? 'Misi Selesai' : 'Quests Done'}
                                                </div>
                                                <div className="vwl-grand-chest-action">
                                                    {isReady && (
                                                        <button
                                                            type="button"
                                                            className="vwl-grand-chest-claim-btn"
                                                            disabled={busyMission !== null}
                                                            onClick={() => claimMission(p.completion_id)}
                                                        >
                                                            <span className="vwl-btn-shine" aria-hidden="true" />
                                                            <span>{busyMission === p.completion_id ? (isId ? 'Membuka...' : 'Opening...') : (isId ? '🎁 Buka Peti & Ambil Poin!' : '🎁 Open Chest & Claim!')}</span>
                                                        </button>
                                                    )}
                                                    {isDone && isClaimed && (
                                                        <div className="vwl-grand-chest-claimed-msg">
                                                            <span aria-hidden="true">✓</span> {isId ? 'Peti Telah Dibuka' : 'Chest Claimed'}
                                                        </div>
                                                    )}
                                                    {!isDone && (
                                                        <div className="vwl-grand-chest-locked-msg">
                                                            <span aria-hidden="true">🔒</span>
                                                            <span>{isId ? `Sisa ${remainingToUnlock} misi lagi` : `${remainingToUnlock} more quests to unlock`}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })()}

                        <div className="vwl-quest-grid" id="vwl-mission-panel" role="tabpanel" aria-labelledby={`vwl-mission-tab-${missionTab}`}>
                            {activeMissions.map((mission) => {
                                const copy = missionCopy[mission.key] ?? {
                                    tag: isId ? 'MISI' : 'QUEST',
                                    theme: 'blue',
                                    title: mission.key.replace(/_/g, ' '),
                                    text: '',
                                    action: isId ? 'Mulai' : 'Start',
                                };
                                const isDone = mission.earned !== null;
                                const isClaimed = mission.claimed;
                                const isReady = isDone && !isClaimed;
                                const rewardPoints = isDone ? mission.earned : mission.points;
                                const progressPct = mission.target > 0 ? Math.min(100, Math.round((mission.progress / mission.target) * 100)) : 0;

                                return (
                                    <div
                                        key={mission.key}
                                        className={`vwl-qcard ${isReady ? 'vwl-qcard--ready' : isDone ? 'vwl-qcard--done' : 'vwl-qcard--active'} theme-${copy.theme ?? 'blue'}`}
                                    >
                                        <div className="vwl-qcard-top">
                                            <div className="vwl-qcard-header">
                                                <div className={`vwl-qcard-emblem theme-${copy.theme ?? 'blue'}`}>
                                                    <MissionIcon name={mission.key} size={22} />
                                                </div>
                                                <div className="vwl-qcard-meta">
                                                    <span className="vwl-qcard-tag">{copy.tag}</span>
                                                    <strong className="vwl-qcard-title">{copy.title}</strong>
                                                </div>
                                                <div className="vwl-qcard-reward" aria-label={isId ? `Hadiah ${rewardPoints} poin` : `Reward ${rewardPoints} points`}>
                                                    <span className="vwl-qcard-coin"><CoinIcon /></span>
                                                    <strong>+{numberLabel(rewardPoints, locale)}</strong>
                                                </div>
                                            </div>

                                            <p className="vwl-qcard-text">{copy.text}</p>
                                        </div>

                                        <div className="vwl-qcard-bottom">
                                            <div className="vwl-qcard-progress-wrap">
                                                <div
                                                    className="vwl-qcard-progress-track"
                                                    role="progressbar"
                                                    aria-valuemin={0}
                                                    aria-valuemax={mission.target}
                                                    aria-valuenow={mission.progress}
                                                    aria-label={copy.title}
                                                >
                                                    <div
                                                        className={`vwl-qcard-progress-fill ${isDone ? 'is-complete' : ''}`}
                                                        style={{ width: `${progressPct}%` }}
                                                    />
                                                </div>
                                                <div className="vwl-qcard-progress-labels">
                                                    <span className="vwl-qcard-progress-status">
                                                        {isDone
                                                            ? (isClaimed ? (isId ? 'Selesai ✓' : 'Completed ✓') : (isId ? 'Siap diklaim!' : 'Ready to claim!'))
                                                            : (isId ? 'Progres' : 'Progress')}
                                                    </span>
                                                    <strong className="vwl-qcard-progress-nums">
                                                        {numberLabel(mission.progress, locale)} / {numberLabel(mission.target, locale)}
                                                    </strong>
                                                </div>
                                            </div>

                                            <div className="vwl-qcard-footer">
                                                {isReady && (
                                                    <button
                                                        type="button"
                                                        className="vwl-qcard-claim-btn"
                                                        disabled={busyMission !== null}
                                                        onClick={() => claimMission(mission.completion_id)}
                                                    >
                                                        <span className="vwl-btn-shine" aria-hidden="true" />
                                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                            <path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zm0 0h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" />
                                                        </svg>
                                                        <span>{busyMission === mission.completion_id ? (isId ? 'Mengklaim...' : 'Claiming...') : (isId ? 'Klaim Hadiah!' : 'Claim Reward!')}</span>
                                                    </button>
                                                )}
                                                {!isDone && copy.href && (
                                                    <Link href={copy.href} className="vwl-qcard-action-link">
                                                        <span>{copy.action}</span>
                                                        <span aria-hidden="true">→</span>
                                                    </Link>
                                                )}
                                                {!isDone && !copy.href && (
                                                    <div className="vwl-qcard-hint">
                                                        <span>{isId ? 'Selesaikan misi harian' : 'Complete daily missions'}</span>
                                                    </div>
                                                )}
                                                {isDone && isClaimed && (
                                                    <div className="vwl-qcard-claimed-stamp">
                                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                            <polyline points="20 6 9 17 4 12" />
                                                        </svg>
                                                        <span>{isId ? 'Poin Diklaim' : 'Points Claimed'}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {missions.pending.length > 0 && (
                            <div className="vwl-loot-stash">
                                <div className="vwl-loot-stash-header">
                                    <div className="vwl-loot-stash-title">
                                        <span className="vwl-loot-stash-icon" aria-hidden="true">🎁</span>
                                        <div>
                                            <h3>{isId ? 'Peti Simpanan: Hadiah Misi Tersisa' : 'Loot Stash: Earlier Unclaimed Rewards'}</h3>
                                            <p>{isId ? 'Hadiah dari periode sebelumnya yang belum Anda klaim. Klaim sekarang sebelum masa tenggang berakhir!' : 'Unclaimed rewards from earlier periods. Claim them before they expire!'}</p>
                                        </div>
                                    </div>
                                    <span className="vwl-loot-stash-count">{missions.pending.length} {isId ? 'Hadiah' : 'Rewards'}</span>
                                </div>
                                <ul className="vwl-loot-stash-list">
                                    {missions.pending.map((mission) => {
                                        const copy = missionCopy[mission.key] ?? { title: mission.key.replace(/_/g, ' ') };
                                        return (
                                            <li key={mission.id} className="vwl-loot-stash-item">
                                                <div className="vwl-loot-stash-item-info">
                                                    <strong>{copy.title}</strong>
                                                    <span>{mission.period === 'daily' ? dayLabel(mission.completed_on) : (isId ? 'Minggu lalu' : 'Earlier week')}</span>
                                                </div>
                                                <div className="vwl-loot-stash-item-action">
                                                    <span className="vwl-loot-stash-points">+{numberLabel(mission.amount, locale)} {t.points}</span>
                                                    <button
                                                        type="button"
                                                        className="vwl-loot-stash-claim-btn"
                                                        disabled={busyMission !== null}
                                                        onClick={() => claimMission(mission.id)}
                                                    >
                                                        {busyMission === mission.id ? (isId ? 'Memproses...' : 'Processing...') : (isId ? 'Klaim' : 'Claim')}
                                                    </button>
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        )}

                        <div className="vwl-quest-tip">
                            <span className="vwl-quest-tip-icon" aria-hidden="true">💡</span>
                            <p>
                                {missionTab === 'daily'
                                    ? (isId ? `Tips Petualang: Misi diatur ulang setiap hari pukul 00:00 WIB (sisa ${hours}:${minutes}:${seconds}). Selesaikan semua 4 misi harian untuk membuka Peti Hari Sempurna!` : `Adventurer’s Tip: Missions reset daily at 00:00 Jakarta time (${hours}:${minutes}:${seconds} left). Complete all 4 daily quests to unlock the Perfect Day chest!`)
                                    : missionTab === 'weekly'
                                        ? (isId ? `Tips Petualang: Misi mingguan berakhir dalam ${daysToWeekEnd} hari. Hanya konten yang dibuat minggu ini yang dihitung ke dalam progres.` : `Adventurer’s Tip: Weekly missions end in ${daysToWeekEnd} ${daysToWeekEnd === 1 ? 'day' : 'days'}. Only content created this week counts toward progress.`)
                                        : (isId ? 'Tips Petualang: Tantangan kreator dihitung dari performa konten yang Anda buat minggu ini berdasarkan respon pengguna lain dalam 7 hari sejak terbit.' : 'Adventurer’s Tip: Creator challenges count content you create this week, based on other members’ activity within 7 days of publishing.')}
                            </p>
                        </div>
                    </section>
                </div>

                <section className="vwl-history" aria-labelledby="vwl-history-heading">
                    <header className="vwl-history-header">
                        <div>
                            <h2 id="vwl-history-heading">{t.history}</h2>
                            <p>{isId ? 'Catatan poin masuk, keluar, dan pengembalian.' : 'A record of points received, spent, and returned.'}</p>
                        </div>
                        <span className="vwl-history-count">{numberLabel(ledger.total, locale)} {isId ? 'catatan' : 'entries'}</span>
                    </header>

                    <nav className="vwl-filters" aria-label={isId ? 'Filter riwayat poin' : 'Filter point history'}>
                        {filters.map((filter) => <Link
                            key={filter.value}
                            className={flow === filter.value ? 'is-active' : ''}
                            href={walletFilterUrl(filter.value)}
                            preserveScroll
                            aria-current={flow === filter.value ? 'page' : undefined}
                        >{filter.label}</Link>)}
                    </nav>

                    {ledgerDays.length > 0 ? ledgerDays.map((group) => <div key={group.day} className="vwl-day">
                        <h3 className="vwl-day-heading">
                            <span>{dayLabel(group.day)}</span>
                            <span className={group.net >= 0 ? 'is-incoming' : 'is-outgoing'}>{group.net >= 0 ? '+' : '−'}{numberLabel(Math.abs(group.net), locale)} {t.points}</span>
                        </h3>
                        <ul className="vwl-activity-list">
                            {group.items.map((item, index) => {
                                const isIncoming = item.amount >= 0;
                                const isOpen = openEntry === item.id;
                                const detailId = 'vwl-entry-' + item.id;

                                return <li key={item.id} className={'vwl-activity' + (isOpen ? ' is-open' : '')} style={{ '--i': index }}>
                                    <button type="button" className="vwl-activity-trigger" aria-expanded={isOpen} aria-controls={detailId} onClick={() => setOpenEntry(isOpen ? null : item.id)}>
                                        <span className={'vwl-activity-mark is-' + item.type + ' ' + (isIncoming ? 'is-incoming' : 'is-outgoing')}><LedgerIcon type={item.type} /></span>
                                        <span className="vwl-activity-info">
                                            <strong>{transactionLabel(item.type)}</strong>
                                            <time dateTime={item.created_at}>{timeLabel(item.created_at)} {isId ? 'WIB' : ''}</time>
                                        </span>
                                        <strong className={'vwl-activity-amount ' + (isIncoming ? 'is-incoming' : 'is-outgoing')}>
                                            {isIncoming ? '+' : ''}{numberLabel(item.amount, locale)} <span>{t.points}</span>
                                        </strong>
                                        <span className="vwl-activity-chevron" aria-hidden="true">⌄</span>
                                    </button>
                                    <div className="vwl-activity-detail" id={detailId} hidden={!isOpen}>
                                        <span>{isId ? 'Waktu transaksi' : 'Transaction time'}</span>
                                        <time dateTime={item.created_at}>{new Intl.DateTimeFormat(isId ? 'id-ID' : 'en-US', { dateStyle: 'long', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(item.created_at))} {isId ? 'WIB' : '(Jakarta time)'}</time>
                                        <span>{isId ? 'Nomor transaksi' : 'Transaction ID'}</span>
                                        <span>#{item.id}</span>
                                    </div>
                                </li>;
                            })}
                        </ul>
                    </div>) : <div className="vwl-empty">
                        <strong>{flow === 'all' ? t.noHistory : (isId ? 'Belum ada transaksi di sini.' : 'No transactions here yet.')}</strong>
                        <p>{flow === 'all'
                            ? (isId ? 'Klaim bonus harian untuk mulai mengisi dompet poin.' : 'Claim your daily bonus to start adding points to your wallet.')
                            : (isId ? 'Pilih filter lain untuk melihat aktivitas poin Anda.' : 'Try another filter to see your point activity.')}</p>
                    </div>}

                    {ledger.last_page > 1 && <div className="vwl-pagination"><PaginationLinks pagination={ledger} locale={locale} /></div>}
                </section>
            </div>
        </Layout>
    );
}
