import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function ChooseUsername({ locale = 'id', suggestedUsername }) {
    const isId = locale === 'id';
    const { data, setData, post, processing, errors } = useForm({ username: suggestedUsername });
    const isSuggestionChanged = data.username !== suggestedUsername;

    const submit = (event) => {
        event.preventDefault();
        post(route('community.username.store', { locale }));
    };

    return <GuestLayout currentLocale={locale} variant="minimal">
        <Head title={isId ? 'Pilih username' : 'Choose a username'} />
        <h1 className="vm-auth-title">{isId ? 'Pilih username' : 'Choose a username'}</h1>
        <p className="vm-auth-subtitle">
            {isId ? 'Ini akan menjadi nama publik Anda di Votmee.' : 'This will be your public name on Votmee.'}
        </p>
        <form className="vm-auth-form" onSubmit={submit} noValidate>
            <div className="vm-form-group">
                <label htmlFor="choose-username" className="vm-form-label">Username</label>
                <div className={`vm-input-box${errors.username ? ' has-error' : ''}`}>
                    <span className="vm-input-lead-icon vm-username-prefix" aria-hidden="true">@</span>
                    <input
                        id="choose-username"
                        className={`vm-auth-input${errors.username ? ' has-error' : ''}`}
                        type="text"
                        autoComplete="username"
                        autoFocus
                        required
                        minLength={3}
                        maxLength={30}
                        pattern="[a-z0-9][a-z0-9_-]*"
                        value={data.username}
                        aria-invalid={Boolean(errors.username)}
                        aria-describedby={errors.username ? 'choose-username-error' : 'choose-username-help'}
                        onChange={(event) => setData('username', event.target.value.toLowerCase().replace(/^@/, ''))}
                    />
                </div>
                {errors.username
                    ? <div id="choose-username-error" className="vm-form-error" role="alert">{errors.username}</div>
                    : <div id="choose-username-help" className="vm-username-help">
                        {isId ? '3–30 karakter: huruf kecil, angka, - atau _.' : '3–30 characters: lowercase letters, numbers, - or _.'}
                        {isSuggestionChanged && <button type="button" onClick={() => setData('username', suggestedUsername)}>
                            {isId ? 'Pakai saran' : 'Use suggestion'}
                        </button>}
                    </div>}
            </div>
            <button type="submit" className="vm-submit-button" disabled={processing}>
                {processing ? (isId ? 'Menyimpan...' : 'Saving...') : (isId ? 'Lanjutkan' : 'Continue')}
            </button>
        </form>
        <p className="vm-register-callout vm-username-logout">
            <Link href={route('logout')} method="post" as="button">{isId ? 'Bukan akun Anda? Keluar' : 'Not your account? Log out'}</Link>
        </p>
    </GuestLayout>;
}
