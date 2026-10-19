import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { useRef } from 'react';
import { NavIcon } from '../../Community/Shared';

export default function UpdatePasswordForm({ locale = 'id' }) {
    const isId = locale === 'id';
    const passwordInput = useRef();
    const currentPasswordInput = useRef();

    const {
        data,
        setData,
        errors,
        put,
        reset,
        processing,
        recentlySuccessful,
    } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errs) => {
                if (errs.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errs.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <section className="vst-card">
            <header className="vst-card-header">
                <div className="vst-card-heading">
                    <h2 className="vst-card-title">
                        {isId ? 'Perbarui Kata Sandi' : 'Update Password'}
                    </h2>
                    <p className="vst-card-desc">
                        {isId
                            ? 'Pastikan akun Anda menggunakan kata sandi yang kuat dan tidak digunakan di layanan lain.'
                            : 'Ensure your account is using a long, secure password to stay protected.'}
                    </p>
                </div>
            </header>

            <form onSubmit={updatePassword} className="vst-form">
                <div className="vst-field">
                    <label htmlFor="current_password" className="vst-label">
                        <span>{isId ? 'Kata Sandi Saat Ini' : 'Current Password'} <span className="vst-label-req">*</span></span>
                    </label>
                    <input
                        id="current_password"
                        ref={currentPasswordInput}
                        value={data.current_password}
                        onChange={(e) => setData('current_password', e.target.value)}
                        type="password"
                        className={`vst-input ${errors.current_password ? 'has-error' : ''}`}
                        autoComplete="current-password"
                        placeholder="••••••••"
                        required
                    />
                    {errors.current_password && (
                        <p className="vst-error" role="alert">{errors.current_password}</p>
                    )}
                </div>

                <div className="vst-field">
                    <label htmlFor="password" className="vst-label">
                        <span>{isId ? 'Kata Sandi Baru' : 'New Password'} <span className="vst-label-req">*</span></span>
                    </label>
                    <input
                        id="password"
                        ref={passwordInput}
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        type="password"
                        className={`vst-input ${errors.password ? 'has-error' : ''}`}
                        autoComplete="new-password"
                        placeholder="••••••••"
                        required
                    />
                    {errors.password && (
                        <p className="vst-error" role="alert">{errors.password}</p>
                    )}
                    <p className="vst-hint">
                        {isId
                            ? 'Gunakan minimal 8 karakter dengan kombinasi huruf, angka, atau simbol.'
                            : 'Use at least 8 characters with a mix of letters, numbers, or symbols.'}
                    </p>
                </div>

                <div className="vst-field">
                    <label htmlFor="password_confirmation" className="vst-label">
                        <span>{isId ? 'Konfirmasi Kata Sandi Baru' : 'Confirm New Password'} <span className="vst-label-req">*</span></span>
                    </label>
                    <input
                        id="password_confirmation"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        type="password"
                        className={`vst-input ${errors.password_confirmation ? 'has-error' : ''}`}
                        autoComplete="new-password"
                        placeholder="••••••••"
                        required
                    />
                    {errors.password_confirmation && (
                        <p className="vst-error" role="alert">{errors.password_confirmation}</p>
                    )}
                </div>

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
                            <span>{isId ? 'Kata sandi diperbarui' : 'Password updated'}</span>
                        </span>
                    </Transition>

                    <button
                        type="submit"
                        className="vst-btn-save"
                        disabled={processing}
                    >
                        <NavIcon name="lock" size={14} />
                        <span>{processing ? (isId ? 'Memperbarui...' : 'Updating...') : (isId ? 'Perbarui Kata Sandi' : 'Update Password')}</span>
                    </button>
                </div>
            </form>
        </section>
    );
}
