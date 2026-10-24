import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function ChooseUsername({ locale = 'id', suggestedUsername }) {
    const isId = locale === 'id';
    const { data, setData, post, processing, errors } = useForm({ username: suggestedUsername });

    const submit = (event) => {
        event.preventDefault();
        post(route('community.username.store', { locale }));
    };

    return <GuestLayout currentLocale={locale}>
        <Head title={isId ? 'Pilih username' : 'Choose a username'} />
        <h1 className="vm-auth-title">{isId ? 'Pilih username Anda' : 'Choose your username'}</h1>
        <p className="vm-auth-subtitle">
            {isId
                ? 'Kami menyiapkan saran dari nama Anda. Anda bisa mengubahnya sebelum melanjutkan.'
                : 'We suggested a username from your name. You can change it before continuing.'}
        </p>
        <form className="vm-auth-form" onSubmit={submit} noValidate>
            <div className="vm-form-group">
                <label htmlFor="choose-username" className="vm-form-label">Username</label>
                <input
                    id="choose-username"
                    className={`vm-auth-input vm-username-input${errors.username ? ' has-error' : ''}`}
                    type="text"
                    autoComplete="username"
                    autoFocus
                    required
                    minLength={3}
                    maxLength={30}
                    pattern="[a-z0-9][a-z0-9_-]*"
                    value={data.username}
                    aria-invalid={Boolean(errors.username)}
                    aria-describedby={`choose-username-help${errors.username ? ' choose-username-error' : ''}`}
                    onChange={(event) => setData('username', event.target.value.toLowerCase().replace(/^@/, ''))}
                />
                <div id="choose-username-help" className="vm-username-help">
                    {isId ? '3–30 karakter: huruf kecil, angka, - atau _.' : '3–30 characters: lowercase letters, numbers, - or _.'}
                    <span>{isId ? 'Saran kami: ' : 'Our suggestion: '}
                        {data.username === suggestedUsername
                            ? <strong>@{suggestedUsername}</strong>
                            : <button type="button" onClick={() => setData('username', suggestedUsername)}>@{suggestedUsername}</button>}
                    </span>
                </div>
                {errors.username && <div id="choose-username-error" className="vm-form-error" role="alert">{errors.username}</div>}
            </div>
            <button type="submit" className="vm-submit-button" disabled={processing}>
                {processing ? (isId ? 'Menyimpan...' : 'Saving...') : (isId ? 'Lanjutkan' : 'Continue')}
            </button>
        </form>
        <p className="vm-register-callout">
            <Link href={route('logout')} method="post" as="button">{isId ? 'Keluar' : 'Log out'}</Link>
        </p>
    </GuestLayout>;
}
