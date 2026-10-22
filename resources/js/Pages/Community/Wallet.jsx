import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { api, dictionary, Layout, numberLabel, PageHead, PaginationLinks, path } from './Shared';

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
    const [alert, setAlert] = useState(null);
    const [displayedBalance, setDisplayedBalance] = useState(balance);
    const [isClaimed, setIsClaimed] = useState(claimedToday);
    const [gain, setGain] = useState(0);
    const [openEntry, setOpenEntry] = useState(null);
    const [displayedStreak, setDisplayedStreak] = useState(streak);

    useEffect(() => setDisplayedBalance(balance), [balance]);
    useEffect(() => setIsClaimed(claimedToday), [claimedToday]);
    useEffect(() => setDisplayedStreak(streak), [streak]);
    useEffect(() => setOpenEntry(null), [flow]);
    useEffect(() => {
        if (!gain) return undefined;

        const timeout = window.setTimeout(() => setGain(0), 1600);
        return () => window.clearTimeout(timeout);
    }, [gain]);

    const claim = async () => {
        setBusy(true);
        setAlert(null);

        try {
            const result = await api('/backend/wallet/claim', 'POST');
            setDisplayedBalance(result.balance);
            setIsClaimed(true);
            setDisplayedStreak(result.streak);
            setGain(result.claimed ? 5 + result.streak_bonus : 0);
            setAlert({
                type: 'success',
                text: !result.claimed
                    ? (isId ? 'Bonus hari ini sudah Anda klaim.' : 'You have already claimed today’s bonus.')
                    : result.streak_bonus > 0
                        ? (isId ? `5 poin plus bonus streak ${result.streak_bonus} poin sudah masuk ke dompet Anda.` : `5 points plus a ${result.streak_bonus}-point streak bonus were added to your wallet.`)
                        : (isId ? '5 poin sudah masuk ke dompet Anda.' : '5 points were added to your wallet.'),
            });
            router.reload({ only: ['balance', 'claimedToday', 'ledger', 'streak', 'missions'] });
        } catch (error) {
            setAlert({
                type: 'error',
                text: error.message || (isId ? 'Poin belum dapat diklaim. Coba lagi.' : 'Points could not be claimed. Please try again.'),
            });
        } finally {
            setBusy(false);
        }
    };

    const transactionLabel = (type) => ({
        daily_bonus: isId ? 'Bonus harian' : 'Daily bonus',
        support: isId ? 'Dukungan ranking' : 'Ranking support',
        reversal: isId ? 'Pengembalian poin' : 'Points returned',
        streak_bonus: isId ? 'Bonus streak' : 'Streak bonus',
        mission_reward: isId ? 'Hadiah misi harian' : 'Daily mission reward',
    })[type] ?? type.replace(/_/g, ' ');
    const missionCopy = {
        quick_vote: isId
            ? { title: 'Ikut Quick Vote', text: 'Pilih salah satu kandidat di duel hari ini.' }
            : { title: 'Join the Quick Vote', text: 'Pick a candidate in today’s duel.' },
        support: isId
            ? { title: 'Dukung sebuah ranking', text: 'Beri poin dukungan ke kandidat mana pun.' }
            : { title: 'Support a ranking', text: 'Give support points to any candidate.' },
        comment_liked: isId
            ? { title: 'Komentar disukai', text: 'Dapatkan like dari pengguna lain pada komentar Anda.' }
            : { title: 'Get a comment liked', text: 'Receive a like from another member on your comment.' },
    };
    const missionsEarned = missions.reduce((total, mission) => total + (mission.earned ?? 0), 0);
    const missionsTotal = missions.reduce((total, mission) => total + mission.points, 0);
    const claimsToBonus = displayedStreak.cycle_days - displayedStreak.cycle_progress;
    const transactionDateLabel = (value) => new Intl.DateTimeFormat(isId ? 'id-ID' : 'en-US', {
        dateStyle: 'medium',
        timeZone: 'Asia/Jakarta',
    }).format(new Date(value));

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
                    <p>{isId ? 'Poin untuk ikut mendukung pilihan komunitas.' : 'Points for supporting your community’s choices.'}</p>
                </header>

                <section className="vwl-balance-card" aria-label={t.balance}>
                    <div className="vwl-balance-main">
                        <div className="vwl-balance-topline">
                            <span className="vwl-balance-label">{t.balance}</span>
                            <span className="vwl-balance-symbol" aria-hidden="true">P</span>
                        </div>
                        <div className="vwl-balance-amount">
                            <strong key={displayedBalance}>{numberLabel(displayedBalance, locale)}</strong>
                            <span>{t.points}</span>
                            {gain > 0 && <span className="vwl-gain" aria-hidden="true">+{gain}</span>}
                        </div>
                        <div className="vwl-balance-bottom">
                            <p>{isId ? 'Beri dukungan pada kandidat di ranking komunitas.' : 'Support candidates in community rankings.'}</p>
                            <Link className="vwl-explore-link" href={path(locale, '/explore')}>{isId ? 'Lihat ranking' : 'See rankings'} <span aria-hidden="true">↗</span></Link>
                        </div>
                    </div>
                    <div className={'vwl-claim' + (isClaimed ? ' is-claimed' : '')}>
                        <div className="vwl-claim-heading">
                            <span className="vwl-claim-label">{isId ? 'Bonus harian' : 'Daily bonus'}</span>
                            <span className="vwl-claim-status">{isClaimed ? (isId ? 'Sudah diklaim' : 'Claimed today') : (isId ? 'Tersedia' : 'Available')}</span>
                        </div>
                        <div className="vwl-claim-value"><strong>+5</strong><span>{t.points}</span></div>
                        <p>{isClaimed
                            ? (isId ? 'Bonus berikutnya tersedia besok, mengikuti waktu WIB.' : 'Your next bonus is available tomorrow, Jakarta time.')
                            : (isId ? 'Klaim sekali setiap hari. Hari baru dimulai pukul 00.00 WIB.' : 'Claim once each day. A new day starts at 00:00 Jakarta time.')}</p>
                        <button type="button" disabled={busy || isClaimed} onClick={claim}>
                            {busy ? (isId ? 'Memproses...' : 'Processing...') : isClaimed ? (isId ? 'Diklaim hari ini' : 'Claimed today') : t.claimDaily}
                        </button>
                    </div>
                </section>

                {alert && <div className={'vwl-alert is-' + alert.type} role={alert.type === 'error' ? 'alert' : 'status'}>
                    <p>{alert.text}</p>
                    <button type="button" onClick={() => setAlert(null)} aria-label={isId ? 'Tutup pesan' : 'Dismiss message'}>×</button>
                </div>}

                {returnUrl && <div className="vwl-return" role="status">
                    <p>{displayedBalance >= requestedAmount
                        ? (isId ? 'Poin Anda cukup untuk melanjutkan dukungan.' : 'You have enough points to continue your support.')
                        : isClaimed
                            ? (isId ? 'Poin masih kurang. Bonus berikutnya tersedia besok, pukul 00.00 WIB.' : 'You still need more points. The next bonus is available tomorrow at 00:00 Jakarta time.')
                            : (isId ? 'Klaim bonus harian di atas, lalu kembali ke kandidat pilihan Anda.' : 'Claim the daily bonus above, then return to your chosen candidate.')}</p>
                    <Link href={returnUrl}>{isId ? 'Kembali ke kandidat' : 'Return to candidate'}</Link>
                </div>}

                <div className="vwl-progress">
                    <section className="vwl-streak" aria-labelledby="vwl-streak-heading">
                        <header className="vwl-card-heading">
                            <h2 id="vwl-streak-heading">{isId ? 'Streak harian' : 'Daily streak'}</h2>
                            <span>{isId ? `Terpanjang ${numberLabel(displayedStreak.longest, locale)} hari` : `Best ${numberLabel(displayedStreak.longest, locale)} days`}</span>
                        </header>
                        <div className="vwl-streak-count">
                            <strong>{numberLabel(displayedStreak.days, locale)}</strong>
                            <span>{isId ? 'hari berturut-turut' : displayedStreak.days === 1 ? 'day in a row' : 'days in a row'}</span>
                        </div>
                        <ol className="vwl-streak-track" aria-label={isId ? `${displayedStreak.cycle_progress} dari ${displayedStreak.cycle_days} hari menuju bonus` : `${displayedStreak.cycle_progress} of ${displayedStreak.cycle_days} days toward the bonus`}>
                            {Array.from({ length: displayedStreak.cycle_days }, (_, index) => <li
                                key={index}
                                className={(index < displayedStreak.cycle_progress ? 'is-done' : '') + (index === displayedStreak.cycle_days - 1 ? ' is-reward' : '')}
                            >{index === displayedStreak.cycle_days - 1 ? `+${displayedStreak.bonus}` : index + 1}</li>)}
                        </ol>
                        <p>{claimsToBonus === 0
                            ? (isId ? `Bonus streak +${displayedStreak.bonus} poin sudah masuk hari ini. Siklus baru dimulai besok.` : `Your +${displayedStreak.bonus} streak bonus landed today. A new cycle starts tomorrow.`)
                            : (isId ? `Klaim ${claimsToBonus} hari lagi berturut-turut untuk bonus +${displayedStreak.bonus} poin. Satu hari terlewat, streak dimulai ulang.` : `Claim ${claimsToBonus} more days in a row for a +${displayedStreak.bonus} bonus. Miss a day and the streak restarts.`)}</p>
                    </section>

                    <section className="vwl-missions" aria-labelledby="vwl-missions-heading">
                        <header className="vwl-card-heading">
                            <h2 id="vwl-missions-heading">{isId ? 'Misi harian' : 'Daily missions'}</h2>
                            <span>{numberLabel(missionsEarned, locale)}/{numberLabel(missionsTotal, locale)} {t.points}</span>
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
                                    </span>
                                    <span className="vwl-mission-points" aria-label={isDone ? (isId ? `Selesai, +${mission.earned} poin` : `Completed, +${mission.earned} points`) : (isId ? `Hadiah +${mission.points} poin` : `Reward +${mission.points} points`)}>
                                        +{numberLabel(isDone ? mission.earned : mission.points, locale)}
                                    </span>
                                </li>;
                            })}
                        </ul>
                        <p>{isId ? 'Misi diatur ulang setiap pukul 00.00 WIB.' : 'Missions reset daily at 00:00 Jakarta time.'}</p>
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

                    {ledger.data.length > 0 ? <ul className="vwl-activity-list">
                        {ledger.data.map((item) => {
                            const isIncoming = item.amount >= 0;
                            const isOpen = openEntry === item.id;
                            const detailId = 'vwl-entry-' + item.id;

                            return <li key={item.id} className={'vwl-activity' + (isOpen ? ' is-open' : '')}>
                                <button type="button" className="vwl-activity-trigger" aria-expanded={isOpen} aria-controls={detailId} onClick={() => setOpenEntry(isOpen ? null : item.id)}>
                                    <span className={'vwl-activity-mark ' + (isIncoming ? 'is-incoming' : 'is-outgoing')} aria-hidden="true">{isIncoming ? '+' : '−'}</span>
                                    <span className="vwl-activity-info">
                                        <strong>{transactionLabel(item.type)}</strong>
                                        <time dateTime={item.created_at}>{transactionDateLabel(item.created_at)}</time>
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
                    </ul> : <div className="vwl-empty">
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
