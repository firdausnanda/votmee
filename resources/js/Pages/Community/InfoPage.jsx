import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Layout, NavIcon, PageHead, path } from './Shared';

/** Date the privacy policy and user agreement were last revised. */
export const POLICY_UPDATED_AT = '2026-10-25';

export function formatPolicyDate(locale) {
    return new Intl.DateTimeFormat(locale === 'id' ? 'id-ID' : 'en-US', { day: 'numeric', month: 'long', year: 'numeric' })
        .format(new Date(`${POLICY_UPDATED_AT}T00:00:00`));
}

/** Links between the three information pages, shown as tabs under each page title. */
function InfoTabs({ locale, active }) {
    const isId = locale === 'id';
    const tabs = [
        ['privacy', '/privacy', isId ? 'Kebijakan Privasi' : 'Privacy Policy', 'lock'],
        ['terms', '/terms', isId ? 'Ketentuan Pengguna' : 'User Agreement', 'shield'],
        ['support', '/support', isId ? 'Pusat Bantuan' : 'Help Center', 'messageSquare'],
    ];

    return <nav className="vif-tabs" aria-label={isId ? 'Informasi Votmee' : 'Votmee information'}>
        {tabs.map(([key, suffix, label, icon]) => <Link
            key={key}
            href={path(locale, suffix)}
            className={active === key ? 'active' : ''}
            aria-current={active === key ? 'page' : undefined}
        >
            <NavIcon name={icon} size={15} />{label}
        </Link>)}
    </nav>;
}

/** Shared shell of the information pages: app layout, hero with tabs, and a help call-to-action. */
export function InfoShell({ active, eyebrow, title, intro, meta = null, children }) {
    const { locale, categories, leaders = [], supportEmail } = usePage().props;
    const isId = locale === 'id';

    return <Layout locale={locale} categories={categories} leaders={leaders} active={active}>
        <PageHead title={title} description={intro} />
        <div className="vif-page">
            <header className="vif-hero">
                <div className="vif-hero-glow" aria-hidden="true" />
                <p className="vif-eyebrow">{eyebrow}</p>
                <h1>{title}</h1>
                <p className="vif-intro">{intro}</p>
                {meta && <div className="vif-meta">{meta}</div>}
                <InfoTabs locale={locale} active={active} />
            </header>

            {children}

            {active !== 'support' && <aside className="vif-cta">
                <span className="vif-cta-icon" aria-hidden="true"><NavIcon name="messageSquare" size={22} /></span>
                <div>
                    <h2>{isId ? 'Masih ada pertanyaan?' : 'Still have questions?'}</h2>
                    <p>{isId ? 'Tim kami siap membantu menjelaskan bagian mana pun dari dokumen ini.' : 'Our team is happy to explain any part of this document.'}</p>
                </div>
                <div className="vif-cta-actions">
                    <Link href={path(locale, '/support')} className="vif-button vif-button-primary">{isId ? 'Pusat Bantuan' : 'Help Center'}</Link>
                    <a href={`mailto:${supportEmail}`} className="vif-button">{supportEmail}</a>
                </div>
            </aside>}
        </div>
    </Layout>;
}

/** Tracks which section heading is currently in view, for the table of contents. */
function useActiveSection(ids) {
    const [activeId, setActiveId] = useState(ids[0]);

    useEffect(() => {
        if (typeof IntersectionObserver === 'undefined') return undefined;
        const observer = new IntersectionObserver((entries) => {
            const visible = entries.filter(entry => entry.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
            if (visible) setActiveId(visible.target.id);
        }, { rootMargin: '-90px 0px -65% 0px' });
        ids.forEach(id => {
            const element = document.getElementById(id);
            if (element) observer.observe(element);
        });

        return () => observer.disconnect();
    }, [ids.join('|')]);

    return activeId;
}

/**
 * A long-form policy document with a sticky, numbered table of contents.
 *
 * @param {{ sections: Array<{ id: string, title: string, content: import('react').ReactNode }> }} props
 */
export function PolicyDocument({ active, eyebrow, title, intro, summary, sections }) {
    const { locale } = usePage().props;
    const isId = locale === 'id';
    const activeId = useActiveSection(sections.map(section => section.id));

    return <InfoShell
        active={active}
        eyebrow={eyebrow}
        title={title}
        intro={intro}
        meta={<>
            <span><NavIcon name="calendar" size={14} />{isId ? 'Berlaku sejak' : 'Effective'} {formatPolicyDate(locale)}</span>
            <span><NavIcon name="eye" size={14} />{isId ? `${sections.length} bagian` : `${sections.length} sections`}</span>
        </>}
    >
        <div className="vif-doc">
            <nav className="vif-toc" aria-label={isId ? 'Daftar isi' : 'Table of contents'}>
                <p>{isId ? 'Daftar isi' : 'On this page'}</p>
                <ol>
                    {sections.map((section, index) => <li key={section.id}>
                        <a href={`#${section.id}`} className={activeId === section.id ? 'active' : ''}>
                            <span>{String(index + 1).padStart(2, '0')}</span>{section.title}
                        </a>
                    </li>)}
                </ol>
            </nav>
            <article className="vif-article">
                {summary && <div className="vif-summary">
                    <p className="vif-summary-label"><NavIcon name="sparkles" size={15} />{isId ? 'Ringkasan singkat' : 'The short version'}</p>
                    <ul>{summary.map(item => <li key={item}><NavIcon name="check" size={15} />{item}</li>)}</ul>
                </div>}
                {sections.map((section, index) => <section key={section.id} id={section.id} className="vif-section">
                    <h2><span>{String(index + 1).padStart(2, '0')}</span>{section.title}</h2>
                    {section.content}
                </section>)}
            </article>
        </div>
    </InfoShell>;
}
