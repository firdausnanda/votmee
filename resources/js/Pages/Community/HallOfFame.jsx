import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AchievementBadge, achievementName, monthLabel } from './Achievements';
import { NameFlair, UserAvatar } from './AvatarFrame';
import { Layout, numberLabel, PageHead, path } from './Shared';

const CATEGORY_COPY = {
    id: {
        influencer: { title: 'Pengaruh', short: 'Pengaruh', unit: 'pendukung unik', badge: (month) => `Bintang Pengaruh ${month}` },
        ranker: { title: 'Pembuat ranking', short: 'Ranking', unit: 'ranking didukung', badge: (month) => `Perancang ${month}` },
        curator: { title: 'Kurator', short: 'Kurator', unit: 'objek terverifikasi', badge: (month) => `Kurator ${month}` },
        writer: { title: 'Penulis wiki', short: 'Penulis', unit: 'revisi disetujui', badge: (month) => `Penulis ${month}` },
        voice: { title: 'Bintang komentar', short: 'Komentar', unit: 'like diterima', badge: (month) => `Bintang Komentar ${month}` },
    },
    en: {
        influencer: { title: 'Influence', short: 'Influence', unit: 'unique supporters', badge: (month) => `${month} Influencer` },
        ranker: { title: 'Ranking makers', short: 'Rankings', unit: 'supported rankings', badge: (month) => `${month} Ranker` },
        curator: { title: 'Curators', short: 'Curators', unit: 'objects verified', badge: (month) => `${month} Curator` },
        writer: { title: 'Wiki writers', short: 'Writers', unit: 'revisions approved', badge: (month) => `${month} Writer` },
        voice: { title: 'Comment stars', short: 'Comments', unit: 'likes received', badge: (month) => `${month} Comment Star` },
    },
};

/** Boards shown large in the top row on wide screens. */
const FEATURED_BOARDS = ['influencer', 'ranker'];

const monthHref = (locale, month) => path(locale, `/hall-of-fame?month=${month}`);

function TrophyArt() {
    return <svg className="vhf-trophy" viewBox="0 0 120 120" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="vhf-trophy-gold" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stopColor="#fde68a" />
                <stop offset="0.55" stopColor="#f59e0b" />
                <stop offset="1" stopColor="#b45309" />
            </linearGradient>
        </defs>
        <path d="M34 22h52v18c0 18-11.6 31-26 31S34 58 34 40V22Z" fill="url(#vhf-trophy-gold)" />
        <path d="M34 30H20c0 14 7 22 18 24M86 30h14c0 14-7 22-18 24" fill="none" stroke="#f59e0b" strokeWidth="6" strokeLinecap="round" />
        <path d="M52 70h16v14H52z" fill="#d97706" />
        <path d="M38 84h44l4 14H34l4-14Z" fill="url(#vhf-trophy-gold)" />
        <path d="m60 32 4.4 9 9.6 1.4-7 6.8 1.7 9.6L60 54.3l-8.7 4.5 1.7-9.6-7-6.8 9.6-1.4L60 32Z" fill="#fffbeb" opacity="0.9" />
    </svg>;
}

function CrownIcon() {
    return <svg className="vhf-crown" viewBox="0 0 24 16" aria-hidden="true" focusable="false">
        <path d="M2 14 1 3l6 4.5L12 1l5 6.5L23 3l-1 11H2Z" fill="#fbbf24" stroke="#b45309" strokeWidth="1.2" strokeLinejoin="round" />
    </svg>;
}

function MonthNav({ locale, month, previousMonth, nextMonth }) {
    const isId = locale === 'id';

    return <nav className="vhf-month-nav" aria-label={isId ? 'Pilih musim' : 'Choose season'}>
        {previousMonth
            ? <Link href={monthHref(locale, previousMonth)} preserveScroll aria-label={isId ? `Musim sebelumnya, ${monthLabel(previousMonth, locale)}` : `Previous season, ${monthLabel(previousMonth, locale)}`}>‹</Link>
            : <span aria-hidden="true">‹</span>}
        <strong aria-live="polite">{monthLabel(month, locale)}</strong>
        {nextMonth
            ? <Link href={monthHref(locale, nextMonth)} preserveScroll aria-label={isId ? `Musim berikutnya, ${monthLabel(nextMonth, locale)}` : `Next season, ${monthLabel(nextMonth, locale)}`}>›</Link>
            : <span aria-hidden="true">›</span>}
    </nav>;
}

function Avatar({ user, size, locale }) {
    return <UserAvatar user={user} locale={locale} pixels={size} size={size >= 44 ? 'md' : 'sm'} className="vhf-avatar" />;
}

function ChampionMark({ isChampion, championKey, locale }) {
    if (!isChampion) return null;

    return <span className="vhf-champion" title={achievementName(championKey, locale)} role="img" aria-label={achievementName(championKey, locale)}>
        <AchievementBadge achievementKey={championKey} size={18} />
    </span>;
}

function PlayerCard({ locale, standing, entries, threshold, unit, isCurrentMonth }) {
    const isId = locale === 'id';

    if (!standing) return null;

    const count = standing.count;
    const progress = Math.min(100, Math.round((count / Math.max(1, threshold)) * 100));
    const hasBadge = count >= threshold;
    const above = standing.rank !== null && standing.rank > 1 ? entries[standing.rank - 2] : null;
    const gapToNext = above ? above.count - count + 1 : null;

    let quest;
    if (!isCurrentMonth) {
        quest = isId ? 'Musim selesai' : 'Season over';
    } else if (standing.rank === 1) {
        quest = isId ? 'Pertahankan takhta sampai akhir bulan!' : 'Hold the throne until the month ends!';
    } else if (gapToNext !== null) {
        quest = isId ? `${numberLabel(gapToNext, locale)} lagi untuk naik ke #${standing.rank - 1}` : `${numberLabel(gapToNext, locale)} more to climb to #${standing.rank - 1}`;
    } else if (standing.rank === null) {
        quest = isId ? 'Mulai berkontribusi untuk masuk papan' : 'Contribute to join the board';
    } else {
        quest = isId ? 'Terus kumpulkan untuk masuk 10 besar' : 'Keep going to reach the top 10';
    }

    return <div className={'vhf-player' + (hasBadge ? ' has-badge' : '')}>
        <div className="vhf-player-rank">
            <span>{isId ? 'Posisi Anda' : 'Your rank'}</span>
            <strong>{standing.rank === null ? '—' : `#${numberLabel(standing.rank, locale)}`}</strong>
        </div>
        <div className="vhf-player-body">
            <div className="vhf-xp" role="progressbar" aria-valuemin={0} aria-valuemax={threshold} aria-valuenow={Math.min(count, threshold)} aria-label={isId ? 'Progres badge musiman' : 'Seasonal badge progress'}>
                <span style={{ width: `${progress}%` }} />
            </div>
            <div className="vhf-player-meta">
                <span>{hasBadge
                    ? (isId ? '✓ Badge musiman didapat' : '✓ Seasonal badge earned')
                    : `${numberLabel(count, locale)}/${numberLabel(threshold, locale)} ${unit}`}</span>
                <span className="vhf-quest">{quest}</span>
            </div>
        </div>
    </div>;
}

function Podium({ locale, entries, unit, viewerId, championIds, championKey }) {
    const isId = locale === 'id';

    return <ol className="vhf-podium">
        {[0, 1, 2].map((index) => {
            const entry = entries[index];
            const place = index + 1;

            if (!entry) {
                return <li key={`empty-${place}`} className={`vhf-seat is-place-${place} is-empty`} aria-label={isId ? `Peringkat ${place} masih kosong` : `Rank ${place} is still open`}>
                    <span className="vhf-seat-avatar"><span className="vhf-avatar is-empty" aria-hidden="true">?</span></span>
                    <span className="vhf-seat-name">{isId ? 'Kursi kosong' : 'Open seat'}</span>
                    <span className="vhf-plinth" aria-hidden="true">{place}</span>
                </li>;
            }

            return <li key={entry.user.id} className={`vhf-seat is-place-${place}` + (entry.user.id === viewerId ? ' is-viewer' : '')}>
                <span className="vhf-seat-avatar">
                    {place === 1 && <CrownIcon />}
                    <Avatar user={entry.user} size={place === 1 ? 56 : 44} locale={locale} />
                </span>
                <span className="vhf-seat-name">
                    <NameFlair flair={entry.user.name_flair} locale={locale}><Link href={path(locale, `/profile/${entry.user.username}`)}>{entry.user.name}</Link></NameFlair>
                    <ChampionMark isChampion={championIds.includes(entry.user.id)} championKey={championKey} locale={locale} />
                </span>
                <span className="vhf-seat-score" aria-label={`${numberLabel(entry.count, locale)} ${unit}`}>{numberLabel(entry.count, locale)}</span>
                <span className="vhf-plinth" aria-hidden="true">{place}</span>
            </li>;
        })}
    </ol>;
}

export default function HallOfFame({ locale, categories, leaders, month, isCurrentMonth, previousMonth, nextMonth, board, champions = {}, standing = null, thresholds }) {
    const isId = locale === 'id';
    const copy = CATEGORY_COPY[isId ? 'id' : 'en'];
    const selectedMonthLabel = monthLabel(month, locale);
    const viewerId = usePage().props.auth?.user?.id;
    const [activeBoard, setActiveBoard] = useState(FEATURED_BOARDS[0]);

    return <Layout locale={locale} categories={categories} leaders={leaders} active="hall-of-fame">
        <PageHead
            title="Hall of Fame"
            locale={locale}
            description={isId
                ? `Kontributor teratas Votmee pada ${selectedMonthLabel}.`
                : `Top Votmee contributors for ${selectedMonthLabel}.`}
        />
        <div className="vhf-page">
            <header className="vhf-hero">
                <div className="vhf-hero-copy">
                    <p className="vhf-kicker">{isId ? 'Komunitas' : 'Community'} / Hall of Fame</p>
                    <h1>Hall of Fame</h1>
                    <p>{isCurrentMonth
                        ? (isId
                            ? `Musim ${selectedMonthLabel} sedang berlangsung. Saat musim berakhir, peringkat #1 tiap papan dinobatkan sebagai juara.`
                            : `The ${selectedMonthLabel} season is live. When it ends, each board's #1 is crowned champion.`)
                        : (isId
                            ? `Musim ${selectedMonthLabel} sudah selesai. Inilah para pemenangnya.`
                            : `The ${selectedMonthLabel} season is over. Meet its winners.`)}</p>
                    <MonthNav locale={locale} month={month} previousMonth={previousMonth} nextMonth={nextMonth} />
                </div>
                <TrophyArt />
            </header>

            <nav className="vhf-switcher" aria-label={isId ? 'Pilih papan' : 'Choose board'}>
                {Object.entries(copy).map(([category, categoryCopy]) => <button
                    key={category}
                    type="button"
                    className={`is-${category}`}
                    aria-pressed={activeBoard === category}
                    aria-controls={`vhf-board-${category}`}
                    onClick={() => setActiveBoard(category)}
                >
                    <AchievementBadge achievementKey={`season_${month.replace('-', '_')}_${category}`} size={26} />
                    <span>{categoryCopy.short}</span>
                    {standing?.[category]?.rank != null && <em>#{numberLabel(standing[category].rank, locale)}</em>}
                </button>)}
            </nav>

            <div className="vhf-boards">
                {Object.entries(copy).map(([category, categoryCopy]) => {
                    const entries = board[category] ?? [];
                    const championIds = champions[category] ?? [];
                    const championKey = `champion_${month.replace('-', '_')}_${category}`;
                    const rest = entries.slice(3);

                    const boardClass = ['vhf-board', `is-${category}`, FEATURED_BOARDS.includes(category) ? 'is-featured' : '', activeBoard === category ? 'is-active' : ''].filter(Boolean).join(' ');

                    return <section key={category} id={`vhf-board-${category}`} className={boardClass} aria-labelledby={`vhf-${category}`}>
                        <header>
                            <AchievementBadge achievementKey={`season_${month.replace('-', '_')}_${category}`} size={44} />
                            <div>
                                <h2 id={`vhf-${category}`}>{categoryCopy.title}</h2>
                                <p>{isId
                                    ? `Target: ${thresholds[category]} ${categoryCopy.unit} → badge ${categoryCopy.badge(selectedMonthLabel)}`
                                    : `Goal: ${thresholds[category]} ${categoryCopy.unit} → ${categoryCopy.badge(selectedMonthLabel)} badge`}</p>
                            </div>
                        </header>

                        <PlayerCard locale={locale} standing={standing?.[category]} entries={entries} threshold={thresholds[category]} unit={categoryCopy.unit} isCurrentMonth={isCurrentMonth} />

                        {entries.length > 0
                            ? <Podium locale={locale} entries={entries} unit={categoryCopy.unit} viewerId={viewerId} championIds={championIds} championKey={championKey} />
                            : <p className="vhf-empty">{isCurrentMonth
                                ? (isId ? 'Belum ada yang naik podium. Jadilah yang pertama!' : 'Nobody is on the podium yet. Be the first!')
                                : (isId ? 'Tidak ada kontribusi di musim ini.' : 'No contributions that season.')}</p>}

                        {rest.length > 0 && <ol className="vhf-list" start={4}>
                            {rest.map((entry, index) => <li key={entry.user.id} className={entry.user.id === viewerId ? 'is-viewer' : ''}>
                                <span className="vhf-rank">{index + 4}</span>
                                <Avatar user={entry.user} size={30} locale={locale} />
                                <span className="vhf-name">
                                    <NameFlair flair={entry.user.name_flair} locale={locale}><Link href={path(locale, `/profile/${entry.user.username}`)}>{entry.user.name}</Link></NameFlair>
                                    <ChampionMark isChampion={championIds.includes(entry.user.id)} championKey={championKey} locale={locale} />
                                </span>
                                <span className="vhf-count" aria-label={`${numberLabel(entry.count, locale)} ${categoryCopy.unit}`}>{numberLabel(entry.count, locale)}</span>
                            </li>)}
                        </ol>}
                    </section>;
                })}
            </div>
        </div>
    </Layout>;
}
