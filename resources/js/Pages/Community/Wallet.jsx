import { router } from '@inertiajs/react';
import { useState } from 'react';
import { api, dateLabel, dictionary, Layout, numberLabel, PageHead, PaginationLinks } from './Shared';

export default function Wallet({ locale, categories, leaders, balance, ledger }) {
    const t = dictionary(locale);
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState('');

    const claim = async () => {
        setBusy(true); setMessage('');
        try {
            const result = await api('/backend/wallet/claim', 'POST');
            setMessage(locale === 'id' ? `Saldo sekarang ${numberLabel(result.balance, locale)} poin.` : `Balance is now ${numberLabel(result.balance, locale)} points.`);
            router.reload();
        } catch (error) { setMessage(error.message); } finally { setBusy(false); }
    };

    return <Layout locale={locale} categories={categories} leaders={leaders}>
        <PageHead title={t.wallet} locale={locale} />
        <div className="vt-page-heading"><div><p className="vt-eyebrow">{t.community}</p><h1>{t.wallet}</h1></div></div>
        <section className="vt-wallet-balance"><div><span>{t.balance}</span><strong>{numberLabel(balance, locale)}</strong><small>{t.points}</small></div><button disabled={busy} onClick={claim}>＋ {t.claimDaily}</button></section>
        {message && <p className="vt-form-message" role="status">{message}</p>}
        <section className="vt-results-section"><div className="vt-section-heading"><h2>{t.history}</h2><span>{ledger.total}</span></div>
            {ledger.data.length ? <div className="vt-ledger-list">{ledger.data.map(item => <div key={item.id}><span><b>{item.type.replace(/_/g, ' ')}</b><small>{dateLabel(item.created_at, locale)}</small></span><strong className={item.amount >= 0 ? 'positive' : ''}>{item.amount >= 0 ? '+' : ''}{numberLabel(item.amount, locale)}</strong></div>)}</div>
                : <p className="vt-muted">{t.noHistory}</p>}
            <PaginationLinks pagination={ledger} locale={locale} />
        </section>
    </Layout>;
}
