import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function DropdownIcon({ name, size = 16 }) {
    const icons = {
        shield: (
            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
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
