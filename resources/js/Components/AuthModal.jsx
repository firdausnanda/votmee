import { Dialog, DialogPanel, Transition, TransitionChild } from '@headlessui/react';
import { useEffect, useState } from 'react';
import LoginForm from '@/Components/LoginForm';
import RegisterForm from '@/Components/RegisterForm';

export default function AuthModal({ mode, onClose, onModeChange, locale, status, googleAuthError }) {
    const isId = locale === 'id';
    const [lastMode, setLastMode] = useState(mode ?? 'login');
    const isRegister = (mode ?? lastMode) === 'register';

    useEffect(() => {
        if (mode) {
            setLastMode(mode);
        }
    }, [mode]);

    return (
        <Transition show={mode !== null} appear>
            <Dialog onClose={onClose} className="vm-login-modal-root" aria-labelledby={isRegister ? 'vm-register-title' : 'vm-login-title'}>
                <TransitionChild
                    enter="transition-opacity duration-300 ease-out"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="transition-opacity duration-200 ease-in"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="vm-login-modal-backdrop" aria-hidden="true" />
                </TransitionChild>
                <TransitionChild
                    enter="transition-all duration-300 ease-out"
                    enterFrom="opacity-0 translate-y-4 sm:scale-95"
                    enterTo="opacity-100 translate-y-0 sm:scale-100"
                    leave="transition-all duration-200 ease-in"
                    leaveFrom="opacity-100 translate-y-0 sm:scale-100"
                    leaveTo="opacity-0 translate-y-4 sm:scale-95"
                >
                    <DialogPanel className={`vm-auth-login vm-login-modal-panel${isRegister ? ' vm-register-modal-panel' : ''}`}>
                        <button
                            type="button"
                            className="vm-login-modal-close"
                            onClick={onClose}
                            aria-label={isRegister
                                ? isId ? 'Tutup formulir daftar' : 'Close sign up form'
                                : isId ? 'Tutup formulir masuk' : 'Close sign in form'}
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
                                <path d="M5 5l14 14M19 5 5 19" />
                            </svg>
                        </button>
                        {isRegister
                            ? <RegisterForm locale={locale} onLogin={() => onModeChange('login')} />
                            : <LoginForm locale={locale} status={status} canResetPassword googleAuthError={googleAuthError} onRegister={() => onModeChange('register')} />}
                    </DialogPanel>
                </TransitionChild>
            </Dialog>
        </Transition>
    );
}
