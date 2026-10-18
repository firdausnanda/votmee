import { Link } from '@inertiajs/react';
import { dateLabel, dictionary, Layout, PageHead, PaginationLinks, path } from './Shared';

export default function Profile({ locale, categories, leaders, profile, rankings }) {
    const t = dictionary(locale);

    return <Layout locale={locale} categories={categories} leaders={leaders}>
        <PageHead title={profile.name} locale={locale} />
        <div className="vt-profile-header"><div className="vt-profile-avatar">{profile.name.slice(0, 1).toUpperCase()}</div><div><p className="vt-eyebrow">{t.profile}</p><h1>{profile.name}</h1><p>@{profile.username}</p></div></div>
        <div className="vt-profile-meta"><span>{profile.region_code}</span><span>{profile.locale.toUpperCase()}</span><span>{rankings.total} {t.rankings}</span></div>
        <section className="vt-results-section"><div className="vt-section-heading"><h2>{t.rankings}</h2><span>{rankings.total}</span></div>
            {rankings.data.length ? <div className="vt-simple-list">{rankings.data.map(post => <article key={post.id}><div><Link href={path(locale, `/ranking/${post.slug}`)}>{post.title}</Link><p>{post.category?.name} · {dateLabel(post.published_at, locale)}</p></div><span>→</span></article>)}</div> : <p className="vt-muted">{t.noRankings}</p>}
            <PaginationLinks pagination={rankings} locale={locale} />
        </section>
    </Layout>;
}
