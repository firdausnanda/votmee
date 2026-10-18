import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ConfirmPassword() {
    const isId = (localStorage.getItem('votmee.locale') ?? 'id') === 'id';
    const { data, setData, post, processing, errors, reset } = useForm({ password: '' });
    const submit = (event) => { event.preventDefault(); post(route('password.confirm'), { onFinish: () => reset('password') }); };
    return <GuestLayout>
        <Head title={isId ? 'Konfirmasi kata sandi' : 'Confirm password'} />
        <p className="vt-eyebrow">Votmee</p><h2>{isId ? 'Konfirmasi kata sandi' : 'Confirm password'}</h2>
        <p className="vt-auth-caption">{isId ? 'Masukkan kata sandi untuk melanjutkan tindakan ini.' : 'Enter your password to continue this action.'}</p>
        <form className="vt-auth-form" onSubmit={submit}><label>{isId ? 'Kata sandi' : 'Password'}<input type="password" autoFocus required value={data.password} onChange={event => setData('password', event.target.value)} />{errors.password && <small role="alert">{errors.password}</small>}</label><button disabled={processing}>{isId ? 'Konfirmasi' : 'Confirm'} →</button></form>
    </GuestLayout>;
}
