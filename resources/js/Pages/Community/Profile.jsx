import { Link } from '@inertiajs/react';
import { dateLabel, dictionary, Layout, numberLabel, PageHead, PaginationLinks, path } from './Shared';

export default function Profile({ locale, categories, leaders, profile, rankings }) {
    const t = dictionary(locale);

    return <Layout locale={locale} categories={categories} leaders={leaders} active="profile">
        <PageHead title={profile.name} locale={locale} />
        <div className="vdc-page vdc-profile-page">
            <header className="vdc-profile-hero">
                <div className="vdc-profile-identity">
                    <div className="vdc-profile-avatar" aria-hidden="true">{profile.name.slice(0, 1).toUpperCase()}</div>
                    <div className="vdc-profile-name">
                        <p className="vdc-eyebrow">{t.profile}</p>
                        <h1>{profile.name}</h1>
                        <p>@{profile.username}</p>
                    </div>
                </div>
                <dl className="vdc-profile-facts">
                    <div><dt>{t.rankings}</dt><dd>{numberLabel(rankings.total, locale)}</dd></div>
                    <div><dt>{t.region}</dt><dd>{profile.region_code || '—'}</dd></div>
                    <div><dt>{t.language}</dt><dd>{profile.locale?.toUpperCase() || '—'}</dd></div>
                </dl>
            </header>

            <section className="vdc-results" aria-labelledby="vdc-profile-rankings-title">
                <div className="vdc-section-heading">
                    <h2 id="vdc-profile-rankings-title">{t.rankings}</h2>
                    <span>{numberLabel(rankings.total, locale)}</span>
                </div>
                {rankings.data.length ? <div className="vdc-ranking-grid">
                    {rankings.data.map(post => <article className="vdc-ranking-card" key={post.id}>
                        <span className="vdc-ranking-category">{post.category?.name ?? t.rankings}</span>
                        <h3><Link href={path(locale, `/ranking/${post.slug}`)}>{post.title}</Link></h3>
                        <p>{dateLabel(post.published_at, locale)}</p>
                    </article>)}
                </div> : <p className="vdc-empty">{t.noRankings}</p>}
                <PaginationLinks pagination={rankings} locale={locale} />
            </section>
        </div>
    </Layout>;
}
