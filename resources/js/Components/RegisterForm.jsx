import { useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import GoogleAuthLink from '@/Components/GoogleAuthLink';

export default function RegisterForm({ locale, onLogin }) {
    const isId = locale === 'id';
    const returnTo = /\/(login|register)$/.test(window.location.pathname)
        ? `/${locale}`
        : `${window.location.pathname}${window.location.search}`;
    const usernameWasEdited = useRef(false);
    const [suggestedUsername, setSuggestedUsername] = useState('');
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        if (!data.name.trim()) {
            setSuggestedUsername('');
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            try {
                const response = await fetch(route('community.username.suggest', { locale, name: data.name }), {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) return;

                const { username } = await response.json();
                setSuggestedUsername(username);
                if (!usernameWasEdited.current) setData('username', username);
            } catch (error) {
                if (error.name !== 'AbortError') setSuggestedUsername('');
            }
        }, 300);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [data.name, locale]);

    const submit = (event) => {
        event.preventDefault();
        post(route('register', { locale, return_to: returnTo }), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const fields = [
        { name: 'name', label: isId ? 'Nama' : 'Name', type: 'text', autoComplete: 'name' },
        { name: 'username', label: 'Username', type: 'text', autoComplete: 'username' },
        { name: 'email', label: 'Email', type: 'email', autoComplete: 'email' },
        { name: 'password', label: isId ? 'Kata sandi' : 'Password', type: 'password', autoComplete: 'new-password' },
        { name: 'password_confirmation', label: isId ? 'Konfirmasi kata sandi' : 'Confirm password', type: 'password', autoComplete: 'new-password' },
    ];

    return <div className="vm-auth-card">
        <div className="vm-auth-card-header">
            <h1 id="vm-register-title" className="vm-auth-title">{isId ? 'Bergabung dengan komunitas' : 'Join the community'}</h1>
            <p className="vm-auth-subtitle">{isId ? 'Buat ranking dan dukung kandidat pilihanmu.' : 'Create rankings and support your favorite candidates.'}</p>
        </div>
        <form onSubmit={submit} className="vm-auth-form" noValidate>
            {fields.map((field) => <div className="vm-form-group" key={field.name}>
                <label htmlFor={`register-${field.name}`} className="vm-form-label">{field.label}</label>
                <input
                    id={`register-${field.name}`}
                    type={field.type}
                    value={data[field.name]}
                    autoComplete={field.autoComplete}
                    autoFocus={field.name === 'name'}
                    required
                    minLength={field.name === 'username' ? 3 : undefined}
                    maxLength={field.name === 'username' ? 30 : undefined}
                    pattern={field.name === 'username' ? '[a-z0-9][a-z0-9_-]*' : undefined}
                    className={`vm-auth-input${errors[field.name] ? ' has-error' : ''}`}
                    aria-invalid={Boolean(errors[field.name])}
                    aria-describedby={field.name === 'username'
                        ? `register-username-help${errors.username ? ' register-username-error' : ''}`
                        : (errors[field.name] ? `register-${field.name}-error` : undefined)}
                    onChange={(event) => {
                        if (field.name === 'username') {
                            usernameWasEdited.current = true;
                            setData('username', event.target.value.toLowerCase().replace(/^@/, ''));
                        } else {
                            setData(field.name, event.target.value);
                        }
                    }}
                />
                {field.name === 'username' && (
                    <div id="register-username-help" className="vm-username-help">
                        <span>{isId ? '3–30 karakter: huruf kecil, angka, - atau _.' : '3–30 characters: lowercase letters, numbers, - or _.'}</span>
                        {suggestedUsername && (
                            <span>
                                {isId ? 'Saran kami: ' : 'Our suggestion: '}
                                {data.username === suggestedUsername
                                    ? <strong>@{suggestedUsername}</strong>
                                    : <button type="button" onClick={() => {
                                        usernameWasEdited.current = true;
                                        setData('username', suggestedUsername);
                                    }}>@{suggestedUsername}</button>}
                            </span>
                        )}
                    </div>
                )}
                {errors[field.name] && <div id={`register-${field.name}-error`} className="vm-form-error" role="alert">{errors[field.name]}</div>}
            </div>)}
            <button type="submit" className="vm-submit-button" disabled={processing}>
                {processing ? (isId ? 'Memproses...' : 'Creating account...') : (isId ? 'Daftar' : 'Sign up')}
            </button>
        </form>
        <div className="vm-auth-divider"><span>{isId ? 'atau' : 'or'}</span></div>
        <GoogleAuthLink locale={locale} returnTo={returnTo} register />
        <p className="vm-register-callout">
            <span>{isId ? 'Sudah punya akun?' : 'Already have an account?'}</span>{' '}
            <button type="button" onClick={onLogin}>{isId ? 'Masuk' : 'Sign in'} →</button>
        </p>
    </div>;
}
