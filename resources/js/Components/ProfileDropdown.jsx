import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function DropdownIcon({ name, size = 16 }) {
    const icons = {
        shield: (
            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
        ),
        bookmark: <path d="M5 5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16l-7-3.5L5 21V5Z" />,
        bell: (
            <>
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
            </>
        ),
        user: (
            <>
                <circle cx="12" cy="8" r="4" />
                <path d="M4 21a8 8 0 0 1 16 0" />
            </>
        ),
        logOut: (
            <>
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" x2="9" y1="12" y2="12" />
            </>
        ),
        settings: (
            <>
                <circle cx="12" cy="12" r="3" />
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
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

export default function ProfileDropdown({
    user,
    locale = 'id',
    canModerate = false,
}) {
    const [open, setOpen] = useState(false);
    const [imageFailed, setImageFailed] = useState(false);
    const containerRef = useRef(null);
    const triggerRef = useRef(null);

    const isId = locale === 'id';
    const profileUrl = `/${locale}/profile/${user?.username || ''}`;
    const isAdminOrMod =
        canModerate ||
        user?.role === 'admin' ||
        user?.role === 'moderator' ||
        user?.role === 'administrator';

    useEffect(() => {
        if (!open) return;

        const handleOutsideClick = (event) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target)
            ) {
                setOpen(false);
            }
        };

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
                triggerRef.current?.focus();
            }
        };

        document.addEventListener('mousedown', handleOutsideClick);
        document.addEventListener('touchstart', handleOutsideClick);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('mousedown', handleOutsideClick);
            document.removeEventListener('touchstart', handleOutsideClick);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [open]);

    const profileImageUrl = user?.avatar_url || user?.profile_photo_url || user?.photo_url || user?.picture;
    const displayName = user?.name || (isId ? 'Akun saya' : 'My account');
    const accountDetail = user?.username ? `@${user.username}` : user?.email;
    const roleLabel = user?.role === 'moderator'
        ? 'Moderator'
        : ['admin', 'administrator'].includes(user?.role)
          ? 'Administrator'
          : null;

    useEffect(() => {
        setImageFailed(false);
    }, [profileImageUrl]);

    return (
        <div ref={containerRef} className="vm-profile-dropdown-wrap">
            <button
                ref={triggerRef}
                type="button"
                className={`vm-profile-trigger ${open ? 'open' : ''}`}
                onClick={() => setOpen(!open)}
                aria-expanded={open}
                aria-controls={open ? 'profile-dropdown-menu' : undefined}
                aria-label={isId ? `Menu akun ${displayName}` : `${displayName} account menu`}
            >
                <span className="vm-profile-avatar" aria-hidden="true">
                    {profileImageUrl && !imageFailed ? (
                        <img src={profileImageUrl} alt="" onError={() => setImageFailed(true)} />
                    ) : <DropdownIcon name="user" size={19} />}
                </span>
            </button>

            {open && (
                <div
                    id="profile-dropdown-menu"
                    className="vm-profile-dropdown-menu"
                >
                    <div className="vm-dropdown-header">
                        <div className="vm-dropdown-identity">
                            <strong className="vm-dropdown-name">{displayName}</strong>
                            {accountDetail && <span className="vm-dropdown-handle">{accountDetail}</span>}
                        </div>
                        {roleLabel && (
                            <span className={`vm-dropdown-role ${user?.role === 'moderator' ? 'vm-dropdown-role-moderator' : ''}`}>
                                {roleLabel}
                            </span>
                        )}
                    </div>

                    <nav className="vm-dropdown-items" aria-label={isId ? 'Navigasi akun' : 'Account navigation'}>
                        <Link
                            href={profileUrl}
                            className="vm-dropdown-item"
                            onClick={() => setOpen(false)}
                        >
                            <span className="vm-dropdown-icon">
                                <DropdownIcon name="user" size={16} />
                            </span>
                            <span>{isId ? 'Profil' : 'Profile'}</span>
                        </Link>

                        <Link
                            href={`/${locale}/saved`}
                            className="vm-dropdown-item"
                            onClick={() => setOpen(false)}
                        >
                            <span className="vm-dropdown-icon">
                                <DropdownIcon name="bookmark" size={16} />
                            </span>
                            <span>{isId ? 'Ranking tersimpan' : 'Saved rankings'}</span>
                        </Link>

                        <Link
                            href={`/${locale}/notifications`}
                            className="vm-dropdown-item"
                            onClick={() => setOpen(false)}
                        >
                            <span className="vm-dropdown-icon">
                                <DropdownIcon name="bell" size={16} />
                            </span>
                            <span>{isId ? 'Notifikasi' : 'Notifications'}</span>
                        </Link>

                        {isAdminOrMod && (
                            <Link
                                href="/admin"
                                className="vm-dropdown-item"
                                onClick={() => setOpen(false)}
                            >
                                <span className="vm-dropdown-icon">
                                    <DropdownIcon name="shield" size={16} />
                                </span>
                                <span>{isId ? 'Panel admin' : 'Admin panel'}</span>
                            </Link>
                        )}

                        <Link
                            href="/profile"
                            className="vm-dropdown-item"
                            onClick={() => setOpen(false)}
                        >
                            <span className="vm-dropdown-icon">
                                <DropdownIcon name="settings" size={16} />
                            </span>
                            <span>{isId ? 'Pengaturan Akun' : 'Account Settings'}</span>
                        </Link>

                        <div className="vm-dropdown-divider" />

                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="vm-dropdown-item vm-dropdown-logout"
                            onClick={() => setOpen(false)}
                        >
                            <span className="vm-dropdown-icon">
                                <DropdownIcon name="logOut" size={16} />
                            </span>
                            <span>{isId ? 'Keluar' : 'Log out'}</span>
                        </Link>
                    </nav>
                </div>
            )}
        </div>
    );
}
