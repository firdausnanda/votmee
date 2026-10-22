const TIER_COLORS = {
    bronze: { stroke: '#b4693e', fill: '#f8e7da', icon: '#8a4a25' },
    silver: { stroke: '#7b8796', fill: '#eef1f5', icon: '#4b5563' },
    gold: { stroke: '#c98a05', fill: '#fff3c4', icon: '#8a5a00' },
};
const LOCKED_COLORS = { stroke: '#cbd5e1', fill: '#f1f5f9', icon: '#94a3b8' };

const CATEGORY_ICONS = {
    streak: <path d="M12 3c1 3 4 4.6 4 8.6a4 4 0 0 1-8 0c0-1.9.9-3.1 2-4 0 1.8.8 2.9 2 3 0-2.8-1.1-4.9 0-7.6Z" />,
    curator: <><circle cx="11" cy="11" r="6" /><path d="m20 20-4.5-4.5" /></>,
    writer: <><path d="M4 20h4L19 9l-4-4L4 16v4Z" /><path d="m13.5 6.5 4 4" /></>,
    voice: <path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z" />,
    guardian: <><path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6l-7-3Z" /><path d="m9 12 2 2 4-4" /></>,
};

const COPY = {
    id: {
        categories: { streak: 'Konsistensi', curator: 'Kurator', writer: 'Penulis wiki', voice: 'Komunitas', guardian: 'Penjaga' },
        tiers: { bronze: 'Perunggu', silver: 'Perak', gold: 'Emas' },
        names: {
            streak: ['Rajin Mampir', 'Pelanggan Setia', 'Penghuni Tetap'],
            curator: ['Pemburu Objek', 'Kurator Jeli', 'Kurator Legendaris'],
            writer: ['Juru Tulis', 'Penulis Andal', 'Pujangga Votmee'],
            voice: ['Suara Didengar', 'Pendapat Berbobot', 'Suara Rakyat'],
            guardian: ['Mata Elang', 'Penjaga Komunitas', 'Garda Votmee'],
        },
        describe: {
            streak: (n) => `Klaim bonus harian ${n} hari berturut-turut.`,
            curator: (n) => `${n} objek usulan Anda terverifikasi.`,
            writer: (n) => `${n} revisi konten Anda disetujui.`,
            voice: (n) => `Komentar Anda mendapat ${n} like dari pengguna lain.`,
            guardian: (n) => `${n} laporan Anda terbukti valid.`,
        },
    },
    en: {
        categories: { streak: 'Consistency', curator: 'Curator', writer: 'Wiki writer', voice: 'Community', guardian: 'Guardian' },
        tiers: { bronze: 'Bronze', silver: 'Silver', gold: 'Gold' },
        names: {
            streak: ['Regular Visitor', 'Loyal Regular', 'Permanent Resident'],
            curator: ['Object Hunter', 'Sharp Curator', 'Legendary Curator'],
            writer: ['Scribe', 'Skilled Writer', 'Votmee Bard'],
            voice: ['Heard Voice', 'Weighty Opinion', 'Voice of the People'],
            guardian: ['Eagle Eye', 'Community Keeper', 'Votmee Guard'],
        },
        describe: {
            streak: (n) => `Claim the daily bonus ${n} days in a row.`,
            curator: (n) => `${n} of your submitted objects verified.`,
            writer: (n) => `${n} of your content revisions approved.`,
            voice: (n) => `Your comments received ${n} likes from other members.`,
            guardian: (n) => `${n} of your reports confirmed valid.`,
        },
    },
};

const TIER_ORDER = ['bronze', 'silver', 'gold'];

/** Split a badge key such as "curator_gold" into its category and tier. */
export function parseAchievementKey(key) {
    const separator = key.lastIndexOf('_');

    return { category: key.slice(0, separator), tier: key.slice(separator + 1) };
}

/** The display name of a badge key, falling back to the key itself. */
export function achievementName(key, locale) {
    const { category, tier } = parseAchievementKey(key);
    const names = COPY[locale === 'id' ? 'id' : 'en'].names[category];

    return names?.[TIER_ORDER.indexOf(tier)] ?? key.replace(/_/g, ' ');
}

export function AchievementBadge({ achievementKey, size = 48, locked = false, className = '' }) {
    const { category, tier } = parseAchievementKey(achievementKey);
    const colors = locked ? LOCKED_COLORS : (TIER_COLORS[tier] ?? LOCKED_COLORS);

    return <svg className={'vac-badge ' + className} width={size} height={size} viewBox="0 0 48 48" aria-hidden="true" focusable="false">
        <polygon points="24 3 42 13.5 42 34.5 24 45 6 34.5 6 13.5" fill={colors.fill} stroke={colors.stroke} strokeWidth="2.5" strokeLinejoin="round" />
        <g transform="translate(12 12)" fill="none" stroke={colors.icon} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            {CATEGORY_ICONS[category]}
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

export function AchievementGallery({ achievements, locale, isOwn, onFeature, busy = false, error = '' }) {
    const isId = locale === 'id';
    const copy = COPY[isId ? 'id' : 'en'];
    const unlockedCount = achievements.filter((badge) => badge.unlocked_at).length;
    const featuredKeys = achievements.filter((badge) => badge.featured).map((badge) => badge.key);
    const categories = [...new Set(achievements.map((badge) => badge.category))];
    const dateLabel = (value) => new Intl.DateTimeFormat(isId ? 'id-ID' : 'en-US', { dateStyle: 'medium' }).format(new Date(value));
    const holdersLabel = (percent) => percent < 1
        ? (isId ? 'Dimiliki kurang dari 1% pengguna' : 'Held by under 1% of members')
        : (isId ? `Dimiliki ${percent.toLocaleString('id-ID')}% pengguna` : `Held by ${percent}% of members`);

    const toggleFeatured = (key) => {
        const next = featuredKeys.includes(key)
            ? featuredKeys.filter((featuredKey) => featuredKey !== key)
            : [...featuredKeys, key];
        onFeature(next);
    };

    return <section className="vac-gallery" id="badges" aria-labelledby="vac-gallery-heading">
        <header className="vac-gallery-header">
            <div>
                <h2 id="vac-gallery-heading">{isId ? 'Badge pencapaian' : 'Achievement badges'}</h2>
                <p>{isOwn
                    ? (isId ? 'Pajang hingga 3 badge di samping nama Anda.' : 'Show up to 3 badges next to your name.')
                    : (isId ? 'Pencapaian dari kontribusi di komunitas.' : 'Earned through community contributions.')}</p>
            </div>
            <span className="vac-gallery-count">{unlockedCount}/{achievements.length} {isId ? 'terbuka' : 'unlocked'}</span>
        </header>

        {error && <p className="vac-gallery-error" role="alert">{error}</p>}

        {categories.map((category) => <div key={category} className="vac-category">
            <h3>{copy.categories[category] ?? category}</h3>
            <ul>
                {achievements.filter((badge) => badge.category === category).map((badge) => {
                    const isUnlocked = Boolean(badge.unlocked_at);
                    const progress = Math.min(100, Math.round((badge.current / badge.threshold) * 100));
                    const canFeature = isOwn && isUnlocked && (badge.featured || featuredKeys.length < 3);

                    return <li key={badge.key} className={'vac-card' + (isUnlocked ? ' is-unlocked' : '') + (badge.featured ? ' is-featured' : '')}>
                        <AchievementBadge achievementKey={badge.key} size={52} locked={!isUnlocked} />
                        <div className="vac-card-body">
                            <span className="vac-card-tier">{copy.tiers[badge.tier] ?? badge.tier} · +{badge.points} {isId ? 'poin' : 'pts'}</span>
                            <strong>{achievementName(badge.key, locale)}</strong>
                            <p>{copy.describe[category]?.(badge.threshold)}</p>
                            {isUnlocked
                                ? <span className="vac-card-meta">{isId ? 'Terbuka' : 'Unlocked'} {dateLabel(badge.unlocked_at)} · {holdersLabel(badge.holders_percent)}</span>
                                : <div className="vac-progress">
                                    <div className="vac-progress-bar" role="progressbar" aria-valuemin={0} aria-valuemax={badge.threshold} aria-valuenow={Math.min(badge.current, badge.threshold)} aria-label={achievementName(badge.key, locale)}>
                                        <span style={{ width: `${progress}%` }} />
                                    </div>
                                    <span>{Math.min(badge.current, badge.threshold).toLocaleString(isId ? 'id-ID' : 'en-US')}/{badge.threshold.toLocaleString(isId ? 'id-ID' : 'en-US')}</span>
                                </div>}
                            {isOwn && isUnlocked && <button
                            type="button"
                            className="vac-feature-toggle"
                            aria-pressed={badge.featured}
                            disabled={busy || !canFeature}
                            title={!canFeature ? (isId ? 'Maksimal 3 badge dipajang' : 'Up to 3 badges can be shown') : undefined}
                            onClick={() => toggleFeatured(badge.key)}
                        >{badge.featured ? (isId ? '★ Dipajang' : '★ Shown') : (isId ? '☆ Pajang' : '☆ Show')}</button>}
                        </div>
                    </li>;
                })}
            </ul>
        </div>)}
    </section>;
}
