import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { api, dictionary, Layout, numberLabel, PageHead, PaginationLinks, path } from './Shared';

export default function Wallet({ locale, categories, leaders, balance, claimedToday, flow, ledger }) {
    const t = dictionary(locale);
    const isId = locale === 'id';
    const [busy, setBusy] = useState(false);
    const [alert, setAlert] = useState(null);
    const [displayedBalance, setDisplayedBalance] = useState(balance);
    const [isClaimed, setIsClaimed] = useState(claimedToday);
    const [showGain, setShowGain] = useState(false);
    const [openEntry, setOpenEntry] = useState(null);

    useEffect(() => setDisplayedBalance(balance), [balance]);
    useEffect(() => setIsClaimed(claimedToday), [claimedToday]);
    useEffect(() => setOpenEntry(null), [flow]);
    useEffect(() => {
        if (!showGain) return undefined;

        const timeout = window.setTimeout(() => setShowGain(false), 1600);
        return () => window.clearTimeout(timeout);
    }, [showGain]);

    const claim = async () => {
        setBusy(true);
        setAlert(null);

        try {
            const result = await api('/backend/wallet/claim', 'POST');
            setDisplayedBalance(result.balance);
            setIsClaimed(true);
            setShowGain(result.claimed);
            setAlert({
                type: 'success',
                text: result.claimed
                    ? (isId ? '5 poin sudah masuk ke dompet Anda.' : '5 points were added to your wallet.')
                    : (isId ? 'Bonus hari ini sudah Anda klaim.' : 'You have already claimed today’s bonus.'),
            });
            router.reload({ only: ['balance', 'claimedToday', 'ledger'] });
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
    })[type] ?? type.replace(/_/g, ' ');
    const transactionDateLabel = (value) => new Intl.DateTimeFormat(isId ? 'id-ID' : 'en-US', {
        dateStyle: 'medium',
        timeZone: 'Asia/Jakarta',
    }).format(new Date(value));

    const filters = [
        { value: 'all', label: isId ? 'Semua' : 'All' },
        { value: 'incoming', label: isId ? 'Poin masuk' : 'Received' },
        { value: 'outgoing', label: isId ? 'Poin keluar' : 'Spent' },
    ];

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
                            {showGain && <span className="vwl-gain" aria-hidden="true">+5</span>}
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
                            href={path(locale, '/wallet') + (filter.value === 'all' ? '' : '?flow=' + filter.value)}
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
