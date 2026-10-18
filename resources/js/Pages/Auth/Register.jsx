import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Register() {
    const locale = window.location.pathname.startsWith('/en') ? 'en' : 'id';
    const isId = locale === 'id';
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', email: '', password: '', password_confirmation: '' });
    const submit = (event) => {
        event.preventDefault();
        post(route('register'), { onFinish: () => reset('password', 'password_confirmation') });
    };
    return <GuestLayout>
        <Head title={isId ? 'Daftar' : 'Sign up'} />
        <p className="vt-eyebrow">Votmee</p><h2>{isId ? 'Bergabung dengan komunitas' : 'Join the community'}</h2>
        <p className="vt-auth-caption">{isId ? 'Buat ranking dan dukung kandidat pilihanmu.' : 'Create rankings and support your favorite candidates.'}</p>
        <form className="vt-auth-form" onSubmit={submit}>
            <label>{isId ? 'Nama' : 'Name'}<input value={data.name} autoComplete="name" autoFocus required onChange={event => setData('name', event.target.value)} />{errors.name && <small role="alert">{errors.name}</small>}</label>
            <label>Email<input type="email" value={data.email} autoComplete="username" required onChange={event => setData('email', event.target.value)} />{errors.email && <small role="alert">{errors.email}</small>}</label>
            <label>{isId ? 'Kata sandi' : 'Password'}<input type="password" value={data.password} autoComplete="new-password" required onChange={event => setData('password', event.target.value)} />{errors.password && <small role="alert">{errors.password}</small>}</label>
            <label>{isId ? 'Konfirmasi kata sandi' : 'Confirm password'}<input type="password" value={data.password_confirmation} autoComplete="new-password" required onChange={event => setData('password_confirmation', event.target.value)} />{errors.password_confirmation && <small role="alert">{errors.password_confirmation}</small>}</label>
            <button disabled={processing}>{isId ? 'Daftar' : 'Sign up'} →</button>
        </form>
        <p className="vt-auth-switch">{isId ? 'Sudah punya akun?' : 'Already have an account?'} <Link href={`/${locale}/login`}>{isId ? 'Masuk' : 'Log in'}</Link></p>
    </GuestLayout>;
}
