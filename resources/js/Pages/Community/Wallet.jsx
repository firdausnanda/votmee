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

const COIN_BURST = Array.from({ length: 12 }, (_, index) => {
    const angle = (index / 12) * Math.PI * 2;
    const distance = 70 + (index % 3) * 22;

    return { x: Math.round(Math.cos(angle) * distance), y: Math.round(Math.sin(angle) * distance - 30), delay: (index % 4) * 40 };
});

export default function Wallet({ locale, categories, leaders, balance, claimedToday, flow, ledger, streak, missions, pendingMissions = [] }) {
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
    const animatedBalance = useCountUp(displayedBalance);
    const untilMidnight = useJakartaMidnightCountdown(() => router.reload({ only: ['claimedToday', 'streak', 'missions', 'pendingMissions'] }));
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
            setAlert({
                type: 'success',
                text: result.claimed
                    ? (isId ? `${result.amount} poin hadiah misi masuk ke dompet Anda.` : `${result.amount} mission points were added to your wallet.`)
                    : (isId ? 'Hadiah misi ini sudah diklaim.' : 'This mission reward was already claimed.'),
            });
            router.reload({ only: ['balance', 'ledger', 'missions', 'pendingMissions', 'summary', 'walletBalance'] });
        } catch (error) {
            setAlert({
                type: 'error',
                text: error.message || (isId ? 'Hadiah misi belum dapat diklaim. Coba lagi.' : 'Mission reward could not be claimed. Please try again.'),
            });
        } finally {
            setBusyMission(null);
        }
    };

    const transactionLabel = (type) => ({
        daily_bonus: isId ? 'Bonus harian' : 'Daily bonus',
        support: isId ? 'Dukungan ranking' : 'Ranking support',
        reversal: isId ? 'Pengembalian poin' : 'Points returned',
        streak_bonus: isId ? 'Bonus streak' : 'Streak bonus',
        mission_reward: isId ? 'Hadiah misi harian' : 'Daily mission reward',
        achievement: isId ? 'Hadiah badge' : 'Badge reward',
    })[type] ?? type.replace(/_/g, ' ');
    const missionCopy = {
        quick_vote: isId
            ? { title: 'Ikut Quick Vote', text: 'Pilih salah satu kandidat di duel hari ini.', href: path(locale), action: 'Ikut duel' }
            : { title: 'Join the Quick Vote', text: 'Pick a candidate in today’s duel.', href: path(locale), action: 'Join duel' },
        support: isId
            ? { title: 'Dukung sebuah ranking', text: 'Beri poin dukungan ke kandidat mana pun.', href: path(locale, '/explore'), action: 'Cari ranking' }
            : { title: 'Support a ranking', text: 'Give support points to any candidate.', href: path(locale, '/explore'), action: 'Find rankings' },
        comment_liked: isId
            ? { title: 'Komentar disukai', text: 'Dapatkan like dari pengguna lain pada komentar Anda.', href: path(locale, '/explore'), action: 'Ikut diskusi' }
            : { title: 'Get a comment liked', text: 'Receive a like from another member on your comment.', href: path(locale, '/explore'), action: 'Join a discussion' },
    };
    const missionsEarned = missions.reduce((total, mission) => total + (mission.earned ?? 0), 0);
    const missionsTotal = missions.reduce((total, mission) => total + mission.points, 0);
    const missionsDone = missions.filter((mission) => mission.earned !== null).length;
    const missionRatio = missionsTotal > 0 ? Math.min(1, missionsEarned / missionsTotal) : 0;
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
                        <header className="vwl-card-heading">
                            <div>
                                <h2 id="vwl-missions-heading">{isId ? 'Misi harian' : 'Daily missions'}</h2>
                                <span>{isId ? `${missionsDone} dari ${missions.length} selesai` : `${missionsDone} of ${missions.length} done`}</span>
                            </div>
                            <div className="vwl-mission-ring" role="img" aria-label={isId ? `${missionsEarned}/${missionsTotal} poin misi selesai` : `${missionsEarned}/${missionsTotal} mission points completed`}>
                                <svg viewBox="0 0 44 44" aria-hidden="true">
                                    <circle cx="22" cy="22" r="18" />
                                    <circle cx="22" cy="22" r="18" style={{ strokeDashoffset: 113.1 * (1 - missionRatio) }} />
                                </svg>
                                <span>{numberLabel(missionsEarned, locale)}<small>/{numberLabel(missionsTotal, locale)}</small></span>
                            </div>
                        </header>
                        <ul className="vwl-mission-list">
                            {missions.map((mission) => {
                                const copy = missionCopy[mission.key] ?? { title: mission.key.replace(/_/g, ' '), text: '' };
                                const isDone = mission.earned !== null;

                                return <li key={mission.key} className={isDone ? 'is-done' : ''}>
                                    <span className="vwl-mission-check" aria-hidden="true">{isDone ? '✓' : ''}</span>
                                    <span className="vwl-mission-info">
                                        <strong>{copy.title}</strong>
                                        <span>{copy.text}</span>
                                        {!isDone && copy.href && <Link href={copy.href} className="vwl-mission-link">{copy.action} <span aria-hidden="true">→</span></Link>}
                                        {isDone && !mission.claimed && <button type="button" className="vwl-mission-claim" disabled={busyMission !== null} onClick={() => claimMission(mission.completion_id)}>
                                            {busyMission === mission.completion_id ? (isId ? 'Memproses...' : 'Processing...') : (isId ? 'Klaim poin' : 'Claim points')}
                                        </button>}
                                        {isDone && mission.claimed && <span className="vwl-mission-claimed">{isId ? 'Poin diklaim' : 'Points claimed'}</span>}
                                    </span>
                                    <span className="vwl-mission-points" aria-label={isDone ? (isId ? `Selesai, +${mission.earned} poin` : `Completed, +${mission.earned} points`) : (isId ? `Hadiah +${mission.points} poin` : `Reward +${mission.points} points`)}>
                                        +{numberLabel(isDone ? mission.earned : mission.points, locale)}
                                    </span>
                                </li>;
                            })}
                        </ul>
                        {pendingMissions.length > 0 && <div className="vwl-pending-missions">
                            <h3>{isId ? 'Hadiah misi sebelumnya' : 'Earlier mission rewards'}</h3>
                            <ul>
                                {pendingMissions.map((mission) => <li key={mission.id}>
                                    <span>{missionCopy[mission.key]?.title ?? mission.key.replace(/_/g, ' ')} · {dayLabel(mission.completed_on)} · +{numberLabel(mission.amount, locale)} {t.points}</span>
                                    <button type="button" className="vwl-mission-claim" disabled={busyMission !== null} onClick={() => claimMission(mission.id)}>
                                        {busyMission === mission.id ? (isId ? 'Memproses...' : 'Processing...') : (isId ? 'Klaim poin' : 'Claim points')}
                                    </button>
                                </li>)}
                            </ul>
                        </div>}
                        <p>{isId ? `Misi diatur ulang dalam ${hours}:${minutes}:${seconds}.` : `Missions reset in ${hours}:${minutes}:${seconds}.`}</p>
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
