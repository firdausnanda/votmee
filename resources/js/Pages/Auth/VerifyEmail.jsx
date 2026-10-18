import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const isId = (localStorage.getItem('votmee.locale') ?? 'id') === 'id';
    const { post, processing } = useForm({});
    const submit = (event) => { event.preventDefault(); post(route('verification.send')); };
    return <GuestLayout>
        <Head title={isId ? 'Verifikasi email' : 'Verify email'} />
        <p className="vt-eyebrow">Votmee</p><h2>{isId ? 'Verifikasi email Anda' : 'Verify your email'}</h2>
        <p className="vt-auth-caption">{isId ? 'Buka tautan verifikasi yang kami kirim ke email Anda sebelum melanjutkan.' : 'Open the verification link we sent to your email before continuing.'}</p>
        {status === 'verification-link-sent' && <p className="vt-form-message" role="status">{isId ? 'Tautan baru telah dikirim.' : 'A new link has been sent.'}</p>}
        <form className="vt-auth-form" onSubmit={submit}><button disabled={processing}>{isId ? 'Kirim ulang email verifikasi' : 'Resend verification email'}</button></form>
        <p className="vt-auth-switch"><Link href={route('logout')} method="post" as="button">{isId ? 'Keluar' : 'Log out'}</Link></p>
    </GuestLayout>;
}
