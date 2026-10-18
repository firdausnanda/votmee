import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const isId = (new URLSearchParams(window.location.search).get('locale') ?? localStorage.getItem('votmee.locale') ?? 'id') === 'id';
    const { data, setData, post, processing, errors } = useForm({ email: '' });
    const submit = (event) => { event.preventDefault(); post(route('password.email')); };
    return <GuestLayout>
        <Head title={isId ? 'Lupa kata sandi' : 'Forgot password'} />
        <p className="vt-eyebrow">Votmee</p><h2>{isId ? 'Atur ulang kata sandi' : 'Reset your password'}</h2>
        <p className="vt-auth-caption">{isId ? 'Masukkan email akun. Kami akan mengirim tautan untuk memilih kata sandi baru.' : 'Enter your account email. We will send a link to choose a new password.'}</p>
        {status && <p className="vt-form-message" role="status">{status}</p>}
        <form className="vt-auth-form" onSubmit={submit}><label>Email<input type="email" autoFocus required value={data.email} onChange={event => setData('email', event.target.value)} />{errors.email && <small role="alert">{errors.email}</small>}</label><button disabled={processing}>{isId ? 'Kirim tautan' : 'Send reset link'} →</button></form>
    </GuestLayout>;
}
