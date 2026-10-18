import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ResetPassword({ token, email }) {
    const isId = (localStorage.getItem('votmee.locale') ?? 'id') === 'id';
    const { data, setData, post, processing, errors, reset } = useForm({ token, email, password: '', password_confirmation: '' });
    const submit = (event) => { event.preventDefault(); post(route('password.store'), { onFinish: () => reset('password', 'password_confirmation') }); };
    return <GuestLayout>
        <Head title={isId ? 'Atur ulang kata sandi' : 'Reset password'} />
        <p className="vt-eyebrow">Votmee</p><h2>{isId ? 'Buat kata sandi baru' : 'Choose a new password'}</h2>
        <form className="vt-auth-form" onSubmit={submit}>
            <label>Email<input type="email" value={data.email} autoComplete="username" required onChange={event => setData('email', event.target.value)} />{errors.email && <small role="alert">{errors.email}</small>}</label>
            <label>{isId ? 'Kata sandi baru' : 'New password'}<input type="password" value={data.password} autoComplete="new-password" autoFocus required onChange={event => setData('password', event.target.value)} />{errors.password && <small role="alert">{errors.password}</small>}</label>
            <label>{isId ? 'Konfirmasi kata sandi' : 'Confirm password'}<input type="password" value={data.password_confirmation} autoComplete="new-password" required onChange={event => setData('password_confirmation', event.target.value)} />{errors.password_confirmation && <small role="alert">{errors.password_confirmation}</small>}</label>
            <button disabled={processing}>{isId ? 'Simpan kata sandi' : 'Save password'} →</button>
        </form>
    </GuestLayout>;
}
