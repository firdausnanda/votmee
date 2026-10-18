import { Link, usePage } from '@inertiajs/react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const locale = user.locale === 'en' ? 'en' : 'id';
    const home = `/${locale}`;
    const profile = `${home}/profile/${user.username}`;

    return (
        <div className="vt-app vm-page vt-settings">
            <a className="vm-skip" href="#main-content">
                {locale === 'id' ? 'Langsung ke konten' : 'Skip to content'}
            </a>
            <header className="vm-header">
                <div className="vm-shell vt-settings-header">
                    <Link href={home} className="vt-wordmark vm-brand" aria-label="Votmee home">
                        votmee<span>.</span>
                    </Link>
                    <nav aria-label={locale === 'id' ? 'Navigasi akun' : 'Account navigation'}>
                        <Link href={home}>{locale === 'id' ? 'Beranda' : 'Home'}</Link>
                        <Link href={profile}>{locale === 'id' ? 'Profil' : 'Profile'}</Link>
                        <Link className="active" href="/profile">{locale === 'id' ? 'Pengaturan' : 'Settings'}</Link>
                        <Link href="/logout" method="post" as="button">{locale === 'id' ? 'Keluar' : 'Log out'}</Link>
                    </nav>
                </div>
            </header>
            <div className="vm-shell vt-settings-shell vt-settings-2col">
                <aside className="vt-settings-sidebar" aria-label={locale === 'id' ? 'Menu akun' : 'Account menu'}>
                    <nav className="vm-side-nav">
                        <Link href={home}>{locale === 'id' ? 'Beranda' : 'Home'}</Link>
                        <Link href={profile}>{locale === 'id' ? 'Profil publik' : 'Public profile'}</Link>
                        <Link className="active" href="/profile">{locale === 'id' ? 'Pengaturan akun' : 'Account settings'}</Link>
                    </nav>
                    <div className="vt-settings-user-card vm-panel" style={{ marginTop: '20px', padding: '14px' }}>
                        <p className="vt-eyebrow" style={{ margin: '0 0 4px', fontSize: '10px', color: '#94a3b8', fontWeight: 800 }}>{locale === 'id' ? 'AKUN' : 'ACCOUNT'}</p>
                        <strong style={{ display: 'block', fontSize: '13px', color: '#0f172a' }}>{user.name}</strong>
                        <span style={{ display: 'block', fontSize: '11px', color: '#64748b', wordBreak: 'break-all' }}>{user.email}</span>
                    </div>
                </aside>
                <main className="vt-settings-main" id="main-content">
                    {header && <div className="vt-settings-heading">{header}</div>}
                    {children}
                </main>
            </div>
        </div>
    );
}
