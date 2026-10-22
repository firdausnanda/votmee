import { Link, router } from '@inertiajs/react';
import { AchievementBadge, monthLabel } from './Achievements';
import { Layout, numberLabel, PageHead, path } from './Shared';

const CATEGORY_COPY = {
    id: {
        curator: { title: 'Kurator', unit: 'objek terverifikasi', badge: (month) => `Kurator ${month}` },
        writer: { title: 'Penulis wiki', unit: 'revisi disetujui', badge: (month) => `Penulis ${month}` },
        voice: { title: 'Bintang komentar', unit: 'like diterima', badge: (month) => `Bintang Komentar ${month}` },
    },
    en: {
        curator: { title: 'Curators', unit: 'objects verified', badge: (month) => `${month} Curator` },
        writer: { title: 'Wiki writers', unit: 'revisions approved', badge: (month) => `${month} Writer` },
        voice: { title: 'Comment stars', unit: 'likes received', badge: (month) => `${month} Comment Star` },
    },
};

export default function HallOfFame({ locale, categories, leaders, month, months, board, thresholds }) {
    const isId = locale === 'id';
    const copy = CATEGORY_COPY[isId ? 'id' : 'en'];
    const selectedMonthLabel = monthLabel(month, locale);
    const isCurrentMonth = month === months[0];

    return <Layout locale={locale} categories={categories} leaders={leaders} active="hall-of-fame">
        <PageHead
            title="Hall of Fame"
            locale={locale}
            description={isId
                ? `Kontributor teratas Votmee pada ${selectedMonthLabel}.`
                : `Top Votmee contributors for ${selectedMonthLabel}.`}
        />
        <div className="vhf-page">
            <header className="vhf-header">
                <div>
                    <p className="vhf-kicker">{isId ? 'Komunitas' : 'Community'} / Hall of Fame</p>
                    <h1>Hall of Fame</h1>
                    <p>{isCurrentMonth
                        ? (isId ? `Kontributor paling aktif sepanjang ${selectedMonthLabel}. Papan ini terus berubah sampai akhir bulan.` : `The most active contributors of ${selectedMonthLabel}. This board keeps moving until the month ends.`)
                        : (isId ? `Kontributor paling aktif sepanjang ${selectedMonthLabel}.` : `The most active contributors of ${selectedMonthLabel}.`)}</p>
                </div>
                <label className="vhf-month">
                    <span>{isId ? 'Bulan' : 'Month'}</span>
                    <select
                        value={month}
                        onChange={(event) => router.get(path(locale, '/hall-of-fame'), { month: event.target.value }, { preserveScroll: true })}
                    >
                        {months.map((option) => <option key={option} value={option}>{monthLabel(option, locale)}</option>)}
                    </select>
                </label>
            </header>

            <div className="vhf-boards">
                {Object.entries(copy).map(([category, categoryCopy]) => {
                    const entries = board[category] ?? [];

                    return <section key={category} className="vhf-board" aria-labelledby={`vhf-${category}`}>
                        <header>
                            <AchievementBadge achievementKey={`season_${month.replace('-', '_')}_${category}`} size={40} />
                            <div>
                                <h2 id={`vhf-${category}`}>{categoryCopy.title}</h2>
                                <p>{isId
                                    ? `Capai ${thresholds[category]} ${categoryCopy.unit} untuk badge ${categoryCopy.badge(selectedMonthLabel)}.`
                                    : `Reach ${thresholds[category]} ${categoryCopy.unit} for the ${categoryCopy.badge(selectedMonthLabel)} badge.`}</p>
                            </div>
                        </header>
                        {entries.length > 0 ? <ol>
                            {entries.map((entry, index) => <li key={entry.user.id} className={index < 3 ? `is-top is-top-${index + 1}` : ''}>
                                <span className="vhf-rank">{index + 1}</span>
                                <span className="vhf-avatar" aria-hidden="true">
                                    {entry.user.avatar_url ? <img src={entry.user.avatar_url} alt="" /> : entry.user.name.slice(0, 1).toUpperCase()}
                                </span>
                                <Link href={path(locale, `/profile/${entry.user.username}`)}>{entry.user.name}</Link>
                                <span className="vhf-count" aria-label={`${numberLabel(entry.count, locale)} ${categoryCopy.unit}`}>{numberLabel(entry.count, locale)}</span>
                            </li>)}
                        </ol> : <p className="vhf-empty">{isId ? 'Belum ada kontribusi di bulan ini.' : 'No contributions this month yet.'}</p>}
                    </section>;
                })}
            </div>
        </div>
    </Layout>;
}
