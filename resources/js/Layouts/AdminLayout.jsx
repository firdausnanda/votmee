import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import LanguageSelect from '../Components/LanguageSelect';

function AdminIcon({ name, size = 18 }) {
    const icons = {
        dashboard: (
            <>
                <rect width="7" height="9" x="3" y="3" rx="1" />
                <rect width="7" height="5" x="14" y="3" rx="1" />
                <rect width="7" height="9" x="14" y="12" rx="1" />
                <rect width="7" height="5" x="3" y="16" rx="1" />
            </>
        ),
        checkSquare: (
            <>
                <path d="m9 12 2 2 4-4" />
                <rect width="18" height="18" x="3" y="3" rx="2" />
            </>
        ),
        flag: (
            <>
                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
                <line x1="4" x2="4" y1="22" y2="15" />
            </>
        ),
        database: (
            <>
                <ellipse cx="12" cy="5" rx="9" ry="3" />
                <path d="M3 5V19A9 3 0 0 0 21 19V5" />
                <path d="M3 12A9 3 0 0 0 21 12" />
            </>
        ),
        tags: (
            <>
                <path d="M9 5H2v7l6.29 6.29c.94.94 2.48.94 3.42 0l3.58-3.58c.94-.94.94-2.48 0-3.42L9 5Z" />
                <path d="M6 9.01V9" />
                <path d="m15 5 6.3 6.3a2.4 2.4 0 0 1 0 3.4L17 19" />
            </>
        ),
        fileText: (
            <>
                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                <path d="M10 9H8" />
                <path d="M16 13H8" />
                <path d="M16 17H8" />
            </>
        ),
        menu: (
            <>
                <line x1="4" x2="20" y1="12" y2="12" />
                <line x1="4" x2="20" y1="6" y2="6" />
                <line x1="4" x2="20" y1="18" y2="18" />
            </>
        ),
        x: <path d="M18 6 6 18M6 6l12 12" />,
        arrowLeft: (
            <>
                <path d="m12 19-7-7 7-7" />
                <path d="M19 12H5" />
            </>
        ),
        logOut: (
            <>
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" x2="9" y1="12" y2="12" />
            </>
        ),
    };

    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            {icons[name] || null}
        </svg>
    );
}

export default function AdminLayout({
    locale = 'id',
    title = 'Admin Panel',
    activeTab = 'overview',
    onTabChange,
    stats = {},
    isAdmin = false,
    children,
}) {
    const user = usePage().props.auth?.user;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const isId = locale === 'id';
    const publicHome = `/${locale}`;

    useEffect(() => {
        if (!sidebarOpen) {
            return;
        }

        const closeOnEscape = (event) => {
            if (event.key === 'Escape') {
                setSidebarOpen(false);
            }
        };

        window.addEventListener('keydown', closeOnEscape);
        return () => window.removeEventListener('keydown', closeOnEscape);
    }, [sidebarOpen]);

    const switchLocale = (next) => {
        router.visit(`/admin?locale=${next}`);
    };

    const navItems = [
        {
            id: 'overview',
            label: isId ? 'Ikhtisar' : 'Overview',
            icon: 'dashboard',
            badge: null,
        },
        {
            id: 'objects',
            label: isId ? 'Antrean Objek' : 'Review Queue',
            icon: 'checkSquare',
            badge: stats.pendingObjects > 0 ? stats.pendingObjects : null,
            badgeVariant: 'warning',
        },
        {
            id: 'reports',
            label: isId ? 'Laporan Konten' : 'Reports Queue',
            icon: 'flag',
            badge: stats.openReports > 0 ? stats.openReports : null,
            badgeVariant: 'danger',
        },
        {
            id: 'master',
            label: isId ? 'Master Objek' : 'Master Objects',
            icon: 'database',
            badge: null,
        },
        ...(isAdmin
            ? [
                  {
                      id: 'categories',
                      label: isId ? 'Kategori & Merek' : 'Categories & Brands',
                      icon: 'tags',
                      badge: null,
                  },
                  {
                      id: 'audit',
                      label: isId ? 'Log Audit' : 'Audit Logs',
                      icon: 'fileText',
                      badge: null,
                  },
              ]
            : []),
    ];

    const currentTabMeta = navItems.find((item) => item.id === activeTab) || navItems[0];

    return (
        <div className="vap-app">
            <Head title={`${title} - Votmee Admin`}><meta name="robots" content="noindex,nofollow" /></Head>

            {/* Mobile Sidebar Overlay */}
            {sidebarOpen && (
                <div
                    className="vap-sidebar-backdrop"
                    onClick={() => setSidebarOpen(false)}
                    aria-hidden="true"
                />
            )}

            {/* Dedicated Admin Sidebar */}
            <aside id="admin-navigation" className={`vap-sidebar ${sidebarOpen ? 'vap-sidebar-open' : ''}`}>
                <div className="vap-sidebar-header">
                    <div className="vap-brand-row">
                        <Link href="/admin" className="vap-brand">
                            votmee<span>.</span>
                        </Link>
                        <span className="vap-admin-pill">Admin</span>
                    </div>
                    <p className="vap-sidebar-caption">
                        {isId ? 'Panel Kontrol & Moderasi' : 'Control & Moderation Panel'}
                    </p>
                </div>

                <div className="vap-sidebar-nav-wrap">
                    <nav className="vap-nav-list" aria-label={isId ? 'Navigasi admin' : 'Admin navigation'}>
                        {navItems.map((item) => {
                            const isActive = activeTab === item.id;
                            return (
                                <button
                                    key={item.id}
                                    type="button"
                                    className={`vap-nav-item ${isActive ? 'active' : ''}`}
                                    aria-pressed={isActive}
                                    onClick={() => {
                                        onTabChange?.(item.id);
                                        setSidebarOpen(false);
                                    }}
                                >
                                    <span className="vap-nav-icon">
                                        <AdminIcon name={item.icon} size={17} />
                                    </span>
                                    <span className="vap-nav-label">{item.label}</span>
                                    {item.badge !== null && (
                                        <span className={`vap-nav-badge vap-badge-${item.badgeVariant || 'default'}`}>
                                            {item.badge}
                                        </span>
                                    )}
                                </button>
                            );
                        })}
                    </nav>
                </div>

                <div className="vap-sidebar-footer">
                    <Link href={publicHome} className="vap-public-link">
                        <AdminIcon name="arrowLeft" size={15} />
                        <span>{isId ? 'Kembali ke Web Publik' : 'Back to Public Site'}</span>
                    </Link>

                    <div className="vap-user-card">
                        <div className="vap-user-avatar">
                            {user?.name ? user.name.slice(0, 1).toUpperCase() : 'A'}
                        </div>
                        <div className="vap-user-info">
                            <strong>{user?.name || 'Administrator'}</strong>
                            <div className="vap-user-role-row">
                                <span className={`vap-role-tag ${isAdmin ? 'admin' : 'mod'}`}>
                                    {isAdmin ? 'Administrator' : 'Moderator'}
                                </span>
                            </div>
                        </div>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="vap-user-logout"
                            title={isId ? 'Keluar' : 'Logout'}
                            aria-label={isId ? 'Keluar' : 'Logout'}
                        >
                            <AdminIcon name="logOut" size={16} />
                        </Link>
                    </div>
                </div>
            </aside>

            {/* Main Stage */}
            <div className="vap-main-wrap">
                {/* Admin Topbar */}
                <header className="vap-topbar">
                    <div className="vap-topbar-left">
                        <button
                            type="button"
                            className="vap-mobile-toggle"
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                            aria-label={isId ? (sidebarOpen ? 'Tutup menu' : 'Buka menu') : (sidebarOpen ? 'Close menu' : 'Open menu')}
                            aria-expanded={sidebarOpen}
                            aria-controls="admin-navigation"
                        >
                            <AdminIcon name={sidebarOpen ? 'x' : 'menu'} size={20} />
                        </button>
                        <div className="vap-breadcrumbs">
                            <span className="vap-crumb-root">Admin</span>
                            <span className="vap-crumb-sep">/</span>
                            <span className="vap-crumb-current">{currentTabMeta.label}</span>
                        </div>
                    </div>

                    <div className="vap-topbar-right">
                        <LanguageSelect
                            language={locale}
                            onChange={(next) => switchLocale(next)}
                        />
                    </div>
                </header>

                {/* Main Content Viewport */}
                <main className="vap-main" id="admin-content">
                    {children}
                </main>
            </div>
        </div>
    );
}
