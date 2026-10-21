import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import GoogleAuthLink from '@/Components/GoogleAuthLink';

export default function LoginForm({ locale, status, canResetPassword, googleAuthError, onRegister }) {
    const isId = locale === 'id';
    const returnTo = /\/(login|register)$/.test(window.location.pathname)
        ? `/${locale}`
        : `${window.location.pathname}${window.location.search}`;

    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();
        post(route('login', { return_to: returnTo }), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="vm-auth-card">
            <div className="vm-auth-card-header">
                <h1 id="vm-login-title" className="vm-auth-title">{isId ? 'Selamat datang kembali' : 'Welcome back'}</h1>
                <p className="vm-auth-subtitle">
                    {isId
                        ? 'Masuk untuk melanjutkan dukungan dan ranking Anda.'
                        : 'Sign in to continue with your rankings and support.'}
                </p>
            </div>

            {status && (
                <div className="vm-auth-alert" role="status">
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                    <span>{status}</span>
                </div>
            )}

            {googleAuthError && (
                <div className="vm-auth-alert vm-auth-alert-error" role="alert">
                    <span>{googleAuthError}</span>
                </div>
            )}

            <form onSubmit={submit} className="vm-auth-form" noValidate>
                {/* Email Field */}
                <div className="vm-form-group">
                    <label htmlFor="email" className="vm-form-label">
                        {isId ? 'Alamat Email' : 'Email Address'}
                    </label>
                    <div className={`vm-input-box ${errors.email ? 'has-error' : ''}`}>
                        <span className="vm-input-lead-icon" aria-hidden="true">
                            <svg
                                width="17"
                                height="17"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <rect width="20" height="16" x="2" y="4" rx="2" />
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                            </svg>
                        </span>
                        <input
                            id="email"
                            type="email"
                            value={data.email}
                            autoComplete="username"
                            autoFocus
                            required
                            placeholder={isId ? 'nama@email.com' : 'name@email.com'}
                            className={`vm-auth-input ${errors.email ? 'has-error' : ''}`}
                            onChange={(event) => setData('email', event.target.value)}
                        />
                    </div>
                    {errors.email && (
                        <div className="vm-form-error" role="alert">
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                aria-hidden="true"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" x2="12" y1="8" y2="12" />
                                <line x1="12" x2="12.01" y1="16" y2="16" />
                            </svg>
                            <span>{errors.email}</span>
                        </div>
                    )}
                </div>

                {/* Password Field */}
                <div className="vm-form-group">
                    <label htmlFor="password" className="vm-form-label">
                        {isId ? 'Kata Sandi' : 'Password'}
                    </label>
                    <div className={`vm-input-box ${errors.password ? 'has-error' : ''}`}>
                        <span className="vm-input-lead-icon" aria-hidden="true">
                            <svg
                                width="17"
                                height="17"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                        </span>
                        <input
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            value={data.password}
                            autoComplete="current-password"
                            required
                            placeholder="••••••••"
                            className={`vm-auth-input vm-password-input ${errors.password ? 'has-error' : ''}`}
                            onChange={(event) => setData('password', event.target.value)}
                        />
                        <button
                            type="button"
                            className="vm-password-toggle"
                            onClick={() => setShowPassword(!showPassword)}
                            aria-label={
                                showPassword
                                    ? isId
                                        ? 'Sembunyikan kata sandi'
                                        : 'Hide password'
                                    : isId
                                      ? 'Tampilkan kata sandi'
                                      : 'Show password'
                            }
                        >
                            {showPassword ? (
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                                    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                                    <line x1="2" x2="22" y1="2" y2="22" />
                                </svg>
                            ) : (
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            )}
                        </button>
                    </div>
                    {errors.password && (
                        <div className="vm-form-error" role="alert">
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                aria-hidden="true"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" x2="12" y1="8" y2="12" />
                                <line x1="12" x2="12.01" y1="16" y2="16" />
                            </svg>
                            <span>{errors.password}</span>
                        </div>
                    )}
                </div>

                {/* Remember Me & Forgot Password Row */}
                <div className="vm-auth-actions-row">
                    <label className="vm-checkbox-label">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            className="vm-checkbox"
                            onChange={(event) => setData('remember', event.target.checked)}
                        />
                        <span>{isId ? 'Ingat saya' : 'Remember me'}</span>
                    </label>

                    {canResetPassword && (
                        <Link
                            href={`${route('password.request')}?locale=${locale}`}
                            className="vm-forgot-link"
                        >
                            {isId ? 'Lupa kata sandi?' : 'Forgot password?'}
                        </Link>
                    )}
                </div>

                {/* Submit Button */}
                <button
                    type="submit"
                    className="vm-submit-button"
                    disabled={processing}
                >
                    {processing ? (
                        <>
                            <svg
                                className="vm-spinner"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <circle
                                    className="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    strokeWidth="4"
                                />
                                <path
                                    className="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                />
                            </svg>
                            <span>{isId ? 'Memproses...' : 'Signing in...'}</span>
                        </>
                    ) : (
                        <>
                            <span>{isId ? 'Masuk' : 'Sign in'}</span>
                            <svg
                                width="15"
                                height="15"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M5 12h14m-6-6 6 6-6 6" />
                            </svg>
                        </>
                    )}
                </button>
            </form>

            <div className="vm-auth-divider"><span>{isId ? 'atau' : 'or'}</span></div>

            <GoogleAuthLink locale={locale} returnTo={returnTo} />

            <p className="vm-register-callout">
                <span>{isId ? 'Belum punya akun?' : 'New to Votmee?'}</span>{' '}
                <button type="button" onClick={onRegister}>
                    {isId ? 'Daftar' : 'Create an account'} →
                </button>
            </p>
        </div>
    );
}
