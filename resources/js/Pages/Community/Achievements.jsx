const TIER_COLORS = {
    bronze: { stroke: '#b4693e', fill: '#f8e7da', icon: '#8a4a25' },
    silver: { stroke: '#7b8796', fill: '#eef1f5', icon: '#4b5563' },
    gold: { stroke: '#c98a05', fill: '#fff3c4', icon: '#8a5a00' },
    season: { stroke: '#7c3aed', fill: '#f3e8ff', icon: '#5b21b6' },
    hidden: { stroke: '#0f766e', fill: '#ccfbf1', icon: '#115e59' },
    champion: { stroke: '#be123c', fill: '#ffe4e6', icon: '#9f1239' },
};
const LOCKED_COLORS = { stroke: '#cbd5e1', fill: '#f1f5f9', icon: '#94a3b8' };

const SHAPES = {
    tier: (colors) => <polygon points="24 3 42 13.5 42 34.5 24 45 6 34.5 6 13.5" fill={colors.fill} stroke={colors.stroke} strokeWidth="2.5" strokeLinejoin="round" />,
    season: (colors) => <circle cx="24" cy="24" r="20" fill={colors.fill} stroke={colors.stroke} strokeWidth="2.5" strokeDasharray="3 2.2" />,
    champion: (colors) => <path d="M24 3 42 9.5V23c0 10.5-7.5 18.4-18 22C13.5 41.4 6 33.5 6 23V9.5L24 3Z" fill={colors.fill} stroke={colors.stroke} strokeWidth="2.5" strokeLinejoin="round" />,
    hidden: (colors) => <rect x="9" y="9" width="30" height="30" rx="5" transform="rotate(45 24 24)" fill={colors.fill} stroke={colors.stroke} strokeWidth="2.5" />,
};

const ICONS = {
    streak: <path d="M12 3c1 3 4 4.6 4 8.6a4 4 0 0 1-8 0c0-1.9.9-3.1 2-4 0 1.8.8 2.9 2 3 0-2.8-1.1-4.9 0-7.6Z" />,
    curator: <><circle cx="11" cy="11" r="6" /><path d="m20 20-4.5-4.5" /></>,
    writer: <><path d="M4 20h4L19 9l-4-4L4 16v4Z" /><path d="m13.5 6.5 4 4" /></>,
    voice: <path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z" />,
    guardian: <><path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6l-7-3Z" /><path d="m9 12 2 2 4-4" /></>,
    ranker: <path d="M3 20h18M5 20v-6h4v6M10 20V8h4v12M15 20v-9h4v9" />,
    voter: <><rect x="4" y="12" width="16" height="8" rx="1" /><path d="M8 12V5h8v7" /><path d="m10 8.5 1.5 1.5 3-3" /></>,
    influencer: <><circle cx="9" cy="8" r="3" /><path d="M3 20a6 6 0 0 1 12 0" /><path d="M16 5a3 3 0 0 1 0 6M21 20a6 6 0 0 0-4-5.6" /></>,
    viral_comment: <><path d="M4 10v4h3l6 4V6l-6 4H4Z" /><path d="M16.5 8.5a5 5 0 0 1 0 7M19 6a8.5 8.5 0 0 1 0 12" /></>,
    perfect_day: <><circle cx="12" cy="12" r="8" /><path d="m8.5 12 2.5 2.5 4.5-5" /></>,
    night_owl: <path d="M19 14.5A7.5 7.5 0 1 1 9.5 5a6 6 0 0 0 9.5 9.5Z" />,
    locked: <><circle cx="12" cy="12" r="1" /><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17.5h.01" /></>,
};

const COPY = {
    id: {
        categories: { streak: 'Konsistensi', curator: 'Kurator', writer: 'Penulis wiki', voice: 'Komunitas', guardian: 'Penjaga', ranker: 'Pembuat ranking', voter: 'Pemilih', influencer: 'Pengaruh' },
        tiers: { bronze: 'Perunggu', silver: 'Perak', gold: 'Emas', season: 'Musiman', hidden: 'Tersembunyi', champion: 'Juara' },
        names: {
            streak: ['Rajin Mampir', 'Pelanggan Setia', 'Penghuni Tetap'],
            curator: ['Pemburu Objek', 'Kurator Jeli', 'Kurator Legendaris'],
            writer: ['Juru Tulis', 'Penulis Andal', 'Pujangga Votmee'],
            voice: ['Suara Didengar', 'Pendapat Berbobot', 'Suara Rakyat'],
            guardian: ['Mata Elang', 'Penjaga Komunitas', 'Garda Votmee'],
            ranker: ['Perintis Ranking', 'Penyusun Andal', 'Arsitek Ranking'],
            voter: ['Pemilih Aktif', 'Suara Konsisten', 'Penentu Peringkat'],
            influencer: ['Mulai Dilirik', 'Panutan', 'Trendsetter'],
        },
        describe: {
            streak: (n) => `Klaim bonus harian ${n} hari berturut-turut.`,
            curator: (n) => `${n} objek usulan Anda terverifikasi.`,
            writer: (n) => `${n} revisi konten Anda disetujui.`,
            voice: (n) => `Komentar Anda mendapat ${n} like dari pengguna lain.`,
            guardian: (n) => `${n} laporan Anda terbukti valid.`,
            ranker: (n) => `${n} ranking Anda dipublikasikan.`,
            voter: (n) => `Memberikan ${n} vote cepat atau dukungan poin.`,
            influencer: (n) => `${n} pengguna lain mendukung ranking Anda dengan poin.`,
        },
        seasonNames: { curator: (month) => `Kurator ${month}`, writer: (month) => `Penulis ${month}`, voice: (month) => `Bintang Komentar ${month}`, influencer: (month) => `Bintang Pengaruh ${month}`, ranker: (month) => `Perancang ${month}` },
        seasonDescribe: {
            curator: (n, month) => `${n} objek usulan Anda terverifikasi selama ${month}.`,
            writer: (n, month) => `${n} revisi konten Anda disetujui selama ${month}.`,
            voice: (n, month) => `Komentar Anda mendapat ${n} like dari pengguna lain selama ${month}.`,
            influencer: (n, month) => `${n} pengguna lain mendukung ranking Anda dengan poin selama ${month}.`,
            ranker: (n, month) => `${n} ranking yang Anda publikasikan selama ${month} didukung pengguna lain.`,
        },
        championNames: { curator: (month) => `Juara Kurator ${month}`, writer: (month) => `Juara Penulis ${month}`, voice: (month) => `Juara Komentar ${month}`, influencer: (month) => `Juara Pengaruh ${month}`, ranker: (month) => `Juara Ranking ${month}` },
        championDescribe: {
            curator: (month) => `Kurator teratas di Hall of Fame ${month}.`,
            writer: (month) => `Penulis wiki teratas di Hall of Fame ${month}.`,
            voice: (month) => `Bintang komentar teratas di Hall of Fame ${month}.`,
            influencer: (month) => `Kreator paling berpengaruh di Hall of Fame ${month}.`,
            ranker: (month) => `Pembuat ranking teratas di Hall of Fame ${month}.`,
        },
        hiddenNames: { viral_comment: 'Komentar Viral', perfect_day: 'Hari Sempurna', night_owl: 'Burung Hantu' },
        hiddenDescribe: {
            viral_comment: 'Satu komentar Anda disukai banyak pengguna lain.',
            perfect_day: 'Menyelesaikan semua misi harian dalam satu hari.',
            night_owl: 'Mengklaim bonus harian sebelum pukul 01.00 WIB.',
        },
    },
    en: {
        categories: { streak: 'Consistency', curator: 'Curator', writer: 'Wiki writer', voice: 'Community', guardian: 'Guardian', ranker: 'Ranking maker', voter: 'Voter', influencer: 'Influence' },
        tiers: { bronze: 'Bronze', silver: 'Silver', gold: 'Gold', season: 'Seasonal', hidden: 'Hidden', champion: 'Champion' },
        names: {
            streak: ['Regular Visitor', 'Loyal Regular', 'Permanent Resident'],
            curator: ['Object Hunter', 'Sharp Curator', 'Legendary Curator'],
            writer: ['Scribe', 'Skilled Writer', 'Votmee Bard'],
            voice: ['Heard Voice', 'Weighty Opinion', 'Voice of the People'],
            guardian: ['Eagle Eye', 'Community Keeper', 'Votmee Guard'],
            ranker: ['Ranking Pioneer', 'Seasoned Ranker', 'Ranking Architect'],
            voter: ['Active Voter', 'Steady Voter', 'Kingmaker'],
            influencer: ['Getting Noticed', 'Role Model', 'Trendsetter'],
        },
        describe: {
            streak: (n) => `Claim the daily bonus ${n} days in a row.`,
            curator: (n) => `${n} of your submitted objects verified.`,
            writer: (n) => `${n} of your content revisions approved.`,
            voice: (n) => `Your comments received ${n} likes from other members.`,
            guardian: (n) => `${n} of your reports confirmed valid.`,
            ranker: (n) => `${n} of your rankings published.`,
            voter: (n) => `Cast ${n} quick votes or point supports.`,
            influencer: (n) => `${n} other members supported your rankings with points.`,
        },
        seasonNames: { curator: (month) => `${month} Curator`, writer: (month) => `${month} Writer`, voice: (month) => `${month} Comment Star`, influencer: (month) => `${month} Influencer`, ranker: (month) => `${month} Ranker` },
        seasonDescribe: {
            curator: (n, month) => `${n} of your submitted objects verified during ${month}.`,
            writer: (n, month) => `${n} of your content revisions approved during ${month}.`,
            voice: (n, month) => `Your comments received ${n} likes from other members during ${month}.`,
            influencer: (n, month) => `${n} other members supported your rankings with points during ${month}.`,
            ranker: (n, month) => `${n} rankings you published during ${month} were supported by other members.`,
        },
        championNames: { curator: (month) => `${month} Champion Curator`, writer: (month) => `${month} Champion Writer`, voice: (month) => `${month} Comment Champion`, influencer: (month) => `${month} Influence Champion`, ranker: (month) => `${month} Ranking Champion` },
        championDescribe: {
            curator: (month) => `Top curator in the ${month} Hall of Fame.`,
            writer: (month) => `Top wiki writer in the ${month} Hall of Fame.`,
            voice: (month) => `Top comment star in the ${month} Hall of Fame.`,
            influencer: (month) => `Most influential creator in the ${month} Hall of Fame.`,
            ranker: (month) => `Top ranking maker in the ${month} Hall of Fame.`,
        },
        hiddenNames: { viral_comment: 'Viral Comment', perfect_day: 'Perfect Day', night_owl: 'Night Owl' },
        hiddenDescribe: {
            viral_comment: 'One of your comments was liked by many other members.',
            perfect_day: 'Completed every daily mission in a single day.',
            night_owl: 'Claimed the daily bonus before 01:00 Jakarta time.',
        },
    },
};

const TIER_ORDER = ['bronze', 'silver', 'gold'];

const copyFor = (locale) => COPY[locale === 'id' ? 'id' : 'en'];

/** "2026-10" as "Oktober 2026" or "October 2026". */
export function monthLabel(period, locale) {
    const [year, month] = period.split('-').map(Number);

    return new Intl.DateTimeFormat(locale === 'id' ? 'id-ID' : 'en-US', { month: 'long', year: 'numeric', timeZone: 'UTC' })
        .format(new Date(Date.UTC(year, month - 1, 1)));
}

/** Split a badge key into its group, category, tier and, for seasonal and champion badges, period. */
export function parseAchievementKey(key) {
    const monthly = key.match(/^(season|champion)_(\d{4})_(\d{2})_(\w+)$/);

    if (monthly) {
        return { group: monthly[1], category: monthly[4], tier: monthly[1], period: `${monthly[2]}-${monthly[3]}` };
    }

    if (key.startsWith('hidden_')) {
        return { group: 'hidden', category: key.slice(7), tier: 'hidden', period: null };
    }

    const separator = key.lastIndexOf('_');

    return { group: 'tier', category: key.slice(0, separator), tier: key.slice(separator + 1), period: null };
}

/** The display name of a badge key, falling back to the key itself. */
export function achievementName(key, locale) {
    const copy = copyFor(locale);
    const { group, category, tier, period } = parseAchievementKey(key);

    if (group === 'season') {
        return copy.seasonNames[category]?.(monthLabel(period, locale)) ?? key;
    }

    if (group === 'champion') {
        return copy.championNames[category]?.(monthLabel(period, locale)) ?? key;
    }

    if (group === 'hidden') {
        return copy.hiddenNames[category] ?? key.replace(/_/g, ' ');
    }

    return copy.names[category]?.[TIER_ORDER.indexOf(tier)] ?? key.replace(/_/g, ' ');
}

function achievementDescription(badge, locale) {
    const copy = copyFor(locale);

    if (badge.group === 'season') {
        return copy.seasonDescribe[badge.category]?.(badge.threshold, monthLabel(badge.period, locale));
    }

    if (badge.group === 'champion') {
        return copy.championDescribe[badge.category]?.(monthLabel(badge.period, locale));
    }

    if (badge.group === 'hidden') {
        return copy.hiddenDescribe[badge.tier];
    }

    return copy.describe[badge.category]?.(badge.threshold);
}

export function AchievementBadge({ achievementKey, size = 48, locked = false, className = '' }) {
    const { group, category, tier } = parseAchievementKey(achievementKey);
    const colors = locked ? LOCKED_COLORS : (TIER_COLORS[tier] ?? LOCKED_COLORS);

    return <svg className={'vac-badge ' + className} width={size} height={size} viewBox="0 0 48 48" aria-hidden="true" focusable="false">
        {SHAPES[group](colors)}
        <g transform="translate(12 12)" fill="none" stroke={colors.icon} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            {ICONS[category] ?? ICONS.locked}
        </g>
    </svg>;
}

/** The featured badges shown beside a commenter's name. */
export function CommentBadges({ keys = [], locale }) {
    if (!keys?.length) return null;

    return <span className="vac-comment-badges">
        {keys.map((key) => <span key={key} title={achievementName(key, locale)} role="img" aria-label={achievementName(key, locale)}>
            <AchievementBadge achievementKey={key} size={16} />
        </span>)}
    </span>;
}

function BadgeCard({ badge, locale, isOwn, featuredCount, busy, onToggle }) {
    const isId = locale === 'id';
    const copy = copyFor(locale);
    const isUnlocked = Boolean(badge.unlocked_at);
    const progress = Math.min(100, Math.round((badge.current / Math.max(1, badge.threshold)) * 100));
    const canFeature = isUnlocked && (badge.featured || featuredCount < 3);
    const numberFormat = (value) => value.toLocaleString(isId ? 'id-ID' : 'en-US');
    const dateLabel = (value) => new Intl.DateTimeFormat(isId ? 'id-ID' : 'en-US', { dateStyle: 'medium' }).format(new Date(value));
    const holdersLabel = (percent) => percent < 1
        ? (isId ? 'Dimiliki kurang dari 1% pengguna' : 'Held by under 1% of members')
        : (isId ? `Dimiliki ${percent.toLocaleString('id-ID')}% pengguna` : `Held by ${percent}% of members`);

    return <li className={'vac-card is-' + badge.group + (isUnlocked ? ' is-unlocked' : '') + (badge.featured ? ' is-featured' : '')}>
        <AchievementBadge achievementKey={badge.key} size={52} locked={!isUnlocked} />
        <div className="vac-card-body">
            <span className="vac-card-tier">{copy.tiers[badge.group === 'tier' ? badge.tier : badge.group]} · +{badge.points} {isId ? 'poin' : 'pts'}</span>
            <strong>{achievementName(badge.key, locale)}</strong>
            <p>{achievementDescription(badge, locale)}</p>
            {isUnlocked
                ? <span className="vac-card-meta">{isId ? 'Terbuka' : 'Unlocked'} {dateLabel(badge.unlocked_at)} · {holdersLabel(badge.holders_percent)}</span>
                : <div className="vac-progress">
                    <div className="vac-progress-bar" role="progressbar" aria-valuemin={0} aria-valuemax={badge.threshold} aria-valuenow={Math.min(badge.current, badge.threshold)} aria-label={achievementName(badge.key, locale)}>
                        <span style={{ width: `${progress}%` }} />
                    </div>
                    <span>{numberFormat(Math.min(badge.current, badge.threshold))}/{numberFormat(badge.threshold)}</span>
                </div>}
            {isOwn && isUnlocked && <button
                type="button"
                className="vac-feature-toggle"
                aria-pressed={badge.featured}
                disabled={busy || !canFeature}
                title={!canFeature ? (isId ? 'Maksimal 3 badge dipajang' : 'Up to 3 badges can be shown') : undefined}
                onClick={() => onToggle(badge.key)}
            >{badge.featured ? (isId ? '★ Dipajang' : '★ Shown') : (isId ? '☆ Pajang' : '☆ Show')}</button>}
        </div>
    </li>;
}

/**
 * The locked badges closest to unlocking: the next tier of each category and this
 * month's seasonal badges, among those with some progress, most complete first.
 */
function nearestBadges(achievements, limit = 3) {
    const nextTiers = new Map();

    achievements.forEach((badge) => {
        if (badge.group === 'tier' && !badge.unlocked_at && !nextTiers.has(badge.category)) {
            nextTiers.set(badge.category, badge);
        }
    });

    return [...nextTiers.values(), ...achievements.filter((badge) => badge.group === 'season' && !badge.unlocked_at)]
        .filter((badge) => badge.current > 0)
        .sort((a, b) => (b.current / b.threshold) - (a.current / a.threshold))
        .slice(0, limit);
}

export function AchievementGallery({ achievements, hiddenLocked = 0, locale, isOwn, onFeature, busy = false, error = '' }) {
    const isId = locale === 'id';
    const copy = copyFor(locale);
    const unlockedCount = achievements.filter((badge) => badge.unlocked_at).length;
    const featuredKeys = achievements.filter((badge) => badge.featured).map((badge) => badge.key);
    const tierBadges = achievements.filter((badge) => badge.group === 'tier');
    const categories = [...new Set(tierBadges.map((badge) => badge.category))];
    const seasonBadges = achievements.filter((badge) => badge.group === 'season');
    const championBadges = achievements.filter((badge) => badge.group === 'champion');
    const hiddenBadges = achievements.filter((badge) => badge.group === 'hidden');
    const nearlyThere = isOwn ? nearestBadges(achievements) : [];

    const toggleFeatured = (key) => {
        onFeature(featuredKeys.includes(key)
            ? featuredKeys.filter((featuredKey) => featuredKey !== key)
            : [...featuredKeys, key]);
    };
    const cardProps = { locale, isOwn, featuredCount: featuredKeys.length, busy, onToggle: toggleFeatured };

    return <section className="vac-gallery" id="badges" aria-labelledby="vac-gallery-heading">
        <header className="vac-gallery-header">
            <div>
                <h2 id="vac-gallery-heading">{isId ? 'Badge pencapaian' : 'Achievement badges'}</h2>
                <p>{isOwn
                    ? (isId ? 'Pajang hingga 3 badge di samping nama Anda.' : 'Show up to 3 badges next to your name.')
                    : (isId ? 'Pencapaian dari kontribusi di komunitas.' : 'Earned through community contributions.')}</p>
            </div>
            <span className="vac-gallery-count">{unlockedCount}/{achievements.length + hiddenLocked} {isId ? 'terbuka' : 'unlocked'}</span>
        </header>

        {error && <p className="vac-gallery-error" role="alert">{error}</p>}

        {nearlyThere.length > 0 && <div className="vac-category vac-nearly">
            <h3>{isId ? 'Hampir tercapai' : 'Almost there'}</h3>
            <p className="vac-category-note">{isId ? 'Badge terdekat yang bisa Anda buka berikutnya.' : 'The badges you are closest to unlocking next.'}</p>
            <ul>{nearlyThere.map((badge) => <BadgeCard key={badge.key} badge={badge} {...cardProps} />)}</ul>
        </div>}

        {categories.map((category) => <div key={category} className="vac-category">
            <h3>{copy.categories[category] ?? category}</h3>
            <ul>{tierBadges.filter((badge) => badge.category === category).map((badge) => <BadgeCard key={badge.key} badge={badge} {...cardProps} />)}</ul>
        </div>)}

        {championBadges.length > 0 && <div className="vac-category">
            <h3>{isId ? 'Juara bulanan' : 'Monthly champions'}</h3>
            <ul>{championBadges.map((badge) => <BadgeCard key={badge.key} badge={badge} {...cardProps} />)}</ul>
        </div>}

        {seasonBadges.length > 0 && <div className="vac-category">
            <h3>{isId ? 'Musiman' : 'Seasonal'}</h3>
            <p className="vac-category-note">{isId
                ? 'Hanya bisa didapat di bulan berjalan. Badge bulan lalu tidak akan muncul lagi.'
                : 'Only available during the current month. Past months never return.'}</p>
            <ul>{seasonBadges.map((badge) => <BadgeCard key={badge.key} badge={badge} {...cardProps} />)}</ul>
        </div>}

        {(hiddenBadges.length > 0 || hiddenLocked > 0) && <div className="vac-category">
            <h3>{isId ? 'Tersembunyi' : 'Hidden'}</h3>
            <ul>
                {hiddenBadges.map((badge) => <BadgeCard key={badge.key} badge={badge} {...cardProps} />)}
                {Array.from({ length: hiddenLocked }, (_, index) => <li key={`hidden-${index}`} className="vac-card is-hidden is-mystery">
                    <AchievementBadge achievementKey="hidden_mystery" size={52} locked />
                    <div className="vac-card-body">
                        <span className="vac-card-tier">{copy.tiers.hidden}</span>
                        <strong>???</strong>
                        <p>{isId ? 'Terus berkontribusi untuk menemukan badge ini.' : 'Keep contributing to discover this badge.'}</p>
                    </div>
                </li>)}
            </ul>
        </div>}
    </section>;
}
