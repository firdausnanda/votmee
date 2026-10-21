export default function GoogleAuthLink({ locale, returnTo, register = false }) {
    const isId = locale === 'id';
    const label = register
        ? (isId ? 'Daftar dengan Google' : 'Sign up with Google')
        : (isId ? 'Masuk dengan Google' : 'Continue with Google');

    return <a href={route('community.google.redirect', { locale, return_to: returnTo })} className="vm-google-button">
        <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#4285F4" d="M43.6 24.5c0-1.4-.1-2.7-.4-4H24v7.6h11a9.4 9.4 0 0 1-4.1 6.2v5.1h6.6c3.9-3.6 6.1-8.8 6.1-14.9Z" />
            <path fill="#34A853" d="M24 44c5.4 0 10-1.8 13.4-4.8l-6.6-5.1c-1.8 1.2-4.1 1.9-6.8 1.9-5.2 0-9.7-3.5-11.3-8.2H5.9v5.2A20 20 0 0 0 24 44Z" />
            <path fill="#FBBC05" d="M12.7 27.8a12 12 0 0 1 0-7.6V15H5.9a20 20 0 0 0 0 18l6.8-5.2Z" />
            <path fill="#EA4335" d="M24 12c2.9 0 5.5 1 7.6 3l5.7-5.7A19.3 19.3 0 0 0 24 4 20 20 0 0 0 5.9 15l6.8 5.2C14.3 15.5 18.8 12 24 12Z" />
        </svg>
        <span>{label}</span>
    </a>;
}
