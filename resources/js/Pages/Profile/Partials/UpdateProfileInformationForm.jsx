import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { NavIcon } from '../../Community/Shared';

export default function UpdateProfileInformationForm({
    mustVerifyEmail,
    status,
    locale = 'id',
}) {
    const user = usePage().props.auth.user;
    const isId = locale === 'id';

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name || '',
            email: user.email || '',
        });

    const submit = (e) => {
        e.preventDefault();
        patch(route('profile.update'));
    };

    const isEmailVerified = Boolean(user.email_verified_at);

    return (
        <section className="vst-card">
            <header className="vst-card-header">
                <div className="vst-card-heading">
                    <h2 className="vst-card-title">
                        {isId ? 'Informasi Profil' : 'Profile Information'}
                    </h2>
                    <p className="vst-card-desc">
                        {isId
                            ? 'Perbarui nama tampilan dan alamat email utama akun Anda.'
                            : "Update your account's display name and primary email address."}
                    </p>
                </div>
            </header>

            <form onSubmit={submit} className="vst-form">
                <div className="vst-field">
                    <label htmlFor="name" className="vst-label">
                        <span>{isId ? 'Nama Lengkap' : 'Full Name'} <span className="vst-label-req">*</span></span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        className={`vst-input ${errors.name ? 'has-error' : ''}`}
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        autoComplete="name"
                        placeholder={isId ? 'Masukkan nama lengkap' : 'Enter full name'}
                    />
                    {errors.name && <p className="vst-error" role="alert">{errors.name}</p>}
                </div>

                {user.username && (
                    <div className="vst-field">
                        <label htmlFor="username" className="vst-label">
                            <span>{isId ? 'Username' : 'Username'} </span>
                            <span className="vst-hint">{isId ? 'Tidak dapat diubah' : 'Immutable'}</span>
                        </label>
                        <input
                            id="username"
                            type="text"
                            className="vst-input"
                            value={`@${user.username}`}
                            disabled
                            readOnly
                        />
                        <p className="vst-hint">
                            {isId
                                ? 'Username digunakan sebagai identitas publik dan URL profil Anda.'
                                : 'Username serves as your public handle and profile URL.'}
                        </p>
                    </div>
                )}

                <div className="vst-field">
                    <div className="vst-label-row">
                        <label htmlFor="email" className="vst-label">
                            <span>{isId ? 'Alamat Email' : 'Email Address'} <span className="vst-label-req">*</span></span>
                        </label>
                        <span
                            className={`vst-email-status ${isEmailVerified ? 'is-verified' : 'is-unverified'}`}
                        >
                            {isEmailVerified
                                ? (isId ? '✓ Terverifikasi' : '✓ Verified')
                                : (isId ? 'Belum Terverifikasi' : 'Unverified')}
                        </span>
                    </div>

                    <input
                        id="email"
                        type="email"
                        className={`vst-input ${errors.email ? 'has-error' : ''}`}
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="email"
                        placeholder="nama@email.com"
                    />
                    {errors.email && <p className="vst-error" role="alert">{errors.email}</p>}
                </div>

                {mustVerifyEmail && !isEmailVerified && (
                    <div className="vst-verify-box" role="status">
                        <NavIcon name="alertTriangle" size={16} style={{ flexShrink: 0, marginTop: 1 }} />
                        <div>
                            <span>
                                {isId
                                    ? 'Alamat email Anda belum terverifikasi.'
                                    : 'Your email address is unverified.'}
                            </span>
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                            >
                                {isId ? 'Kirim ulang link verifikasi' : 'Resend verification link'}
                            </Link>
                        </div>
                    </div>
                )}

                {status === 'verification-link-sent' && (
                    <div className="vst-verify-sent" role="status">
                        {isId
                            ? 'Link verifikasi baru telah dikirimkan ke alamat email Anda.'
                            : 'A new verification link has been sent to your email address.'}
                    </div>
                )}

                <div className="vst-card-footer">
                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out duration-300"
                        enterFrom="opacity-0 translate-y-1"
                        enterTo="opacity-100 translate-y-0"
                        leave="transition ease-in-out duration-200"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                    >
                        <span className="vst-feedback-success" role="status">
                            <NavIcon name="check" size={14} />
                            <span>{isId ? 'Perubahan tersimpan' : 'Changes saved'}</span>
                        </span>
                    </Transition>

                    <button
                        type="submit"
                        className="vst-btn-save"
                        disabled={processing}
                    >
                        <NavIcon name="check" size={14} />
                        <span>{processing ? (isId ? 'Menyimpan...' : 'Saving...') : (isId ? 'Simpan Perubahan' : 'Save Changes')}</span>
                    </button>
                </div>
            </form>
        </section>
    );
}
