import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    Layout,
    NavIcon,
    PageHead,
    path,
} from '../Community/Shared';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    locale = 'id',
    categories = [],
    leaders = [],
    mustVerifyEmail = false,
    status = null,
}) {
    const user = usePage().props.auth.user;
    const isId = locale === 'id';
    const [activeTab, setActiveTab] = useState('profile');

    const title = isId ? 'Pengaturan Akun' : 'Account Settings';
    const pageDescription = isId
        ? 'Kelola informasi profil, keamanan kata sandi, dan privasi akun Anda di Votmee.'
        : 'Manage your profile details, password security, and account preferences on Votmee.';

    const roleName = user?.role === 'admin' || user?.role === 'administrator'
        ? 'Administrator'
        : (user?.role === 'moderator' ? 'Moderator' : null);

    const roleClass = user?.role === 'admin' || user?.role === 'administrator'
        ? 'vst-role-admin'
        : 'vst-role-mod';

    return (
        <Layout locale={locale} categories={categories} leaders={leaders} active="settings">
            <PageHead
                title={`${title} - Votmee`}
                locale={locale}
                description={pageDescription}
            />

            <div className="vst-container">
                <nav className="vr-simple-nav" aria-label="Breadcrumb">
                    <Link href={path(locale)} className="vr-back-link">
                        <NavIcon name="arrowLeft" size={13} />
                        <span>{isId ? 'Kembali ke Feed' : 'Back to Feed'}</span>
                    </Link>
                    <span className="vr-nav-separator">/</span>
                    <span className="vr-nav-current">{title}</span>
                </nav>

                <header className="vst-hero">
                    <h1 className="vst-hero-title">{title}</h1>
                    <p className="vst-hero-sub">{pageDescription}</p>
                </header>

                <div className="vst-grid">
                    <aside className="vst-sidebar" aria-label={isId ? 'Navigasi Pengaturan' : 'Settings Navigation'}>
                        <div className="vst-sidebar-panel">
                            <div className="vst-user-card">
                                <div className="vst-avatar" aria-hidden="true">
                                    {user.name ? user.name.slice(0, 1).toUpperCase() : 'U'}
                                </div>

                                <div className="vst-user-details">
                                    <strong className="vst-user-name" title={user.name}>{user.name}</strong>
                                    {user.username && (
                                        <span className="vst-user-handle">@{user.username}</span>
                                    )}
                                    {roleName && (
                                        <span className={`vst-role-pill ${roleClass}`}>
                                            {roleName}
                                        </span>
                                    )}
                                </div>
                            </div>

                            <nav className="vst-nav-card" aria-label={isId ? 'Bagian pengaturan' : 'Settings sections'}>
                                <button
                                    type="button"
                                    className={`vst-nav-btn ${activeTab === 'profile' ? 'is-active' : ''}`}
                                    onClick={() => setActiveTab('profile')}
                                    aria-pressed={activeTab === 'profile'}
                                >
                                    <NavIcon name="user" size={17} />
                                    <span>{isId ? 'Informasi Profil' : 'Profile Information'}</span>
                                </button>

                                <button
                                    type="button"
                                    className={`vst-nav-btn ${activeTab === 'security' ? 'is-active' : ''}`}
                                    onClick={() => setActiveTab('security')}
                                    aria-pressed={activeTab === 'security'}
                                >
                                    <NavIcon name="lock" size={17} />
                                    <span>{isId ? 'Kata Sandi & Keamanan' : 'Password & Security'}</span>
                                </button>

                                <button
                                    type="button"
                                    className={`vst-nav-btn is-danger ${activeTab === 'danger' ? 'is-active' : ''}`}
                                    onClick={() => setActiveTab('danger')}
                                    aria-pressed={activeTab === 'danger'}
                                >
                                    <NavIcon name="trash" size={17} />
                                    <span>{isId ? 'Hapus Akun' : 'Delete Account'}</span>
                                </button>
                            </nav>
                        </div>
                    </aside>

                    <main className="vst-content">
                        {activeTab === 'profile' && (
                            <UpdateProfileInformationForm
                                mustVerifyEmail={mustVerifyEmail}
                                status={status}
                                locale={locale}
                            />
                        )}

                        {activeTab === 'security' && (
                            <UpdatePasswordForm locale={locale} />
                        )}

                        {activeTab === 'danger' && (
                            <DeleteUserForm locale={locale} />
                        )}
                    </main>
                </div>
            </div>
        </Layout>
    );
}
