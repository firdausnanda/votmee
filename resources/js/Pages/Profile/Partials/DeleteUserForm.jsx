import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { NavIcon } from '../../Community/Shared';

export default function DeleteUserForm({ locale = 'id' }) {
    const isId = locale === 'id';
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef();

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);
        clearErrors();
        reset();
    };

    return (
        <section className="vst-card vst-card-danger">
            <header className="vst-card-header">
                <div className="vst-card-heading">
                    <h2 className="vst-card-title">
                        {isId ? 'Zona Bahaya: Hapus Akun' : 'Danger Zone: Delete Account'}
                    </h2>
                    <p className="vst-card-desc">
                        {isId
                            ? 'Penghapusan akun bersifat permanen dan tidak dapat dibatalkan.'
                            : 'Deleting your account is permanent and cannot be undone.'}
                    </p>
                </div>
            </header>

            <div className="vst-danger-banner">
                <p style={{ margin: 0 }}>
                    {isId
                        ? 'Setelah akun Anda dihapus, semua data profil, riwayat peringkat, dan dukungan yang pernah Anda berikan akan dibersihkan dari sistem. Pastikan Anda telah mempertimbangkan keputusan ini secara matang.'
                        : 'Once your account is deleted, all profile data, ranking submissions, and support history will be permanently wiped. Please ensure you are certain before proceeding.'}
                </p>
            </div>

            <div style={{ display: 'flex', justifyContent: 'flex-start' }}>
                <button
                    type="button"
                    onClick={confirmUserDeletion}
                    className="vst-btn-danger"
                >
                    <NavIcon name="trash" size={14} />
                    <span>{isId ? 'Hapus Akun Saya' : 'Delete My Account'}</span>
                </button>
            </div>

            <Dialog
                open={confirmingUserDeletion}
                onClose={closeModal}
                className="vst-modal-overlay"
            >
                <div className="vst-modal-backdrop" aria-hidden="true" />

                <DialogPanel className="vst-modal-panel">
                    <form onSubmit={deleteUser}>
                        <div className="vst-modal-header">
                            <div className="vst-modal-icon" aria-hidden="true">
                                <NavIcon name="alertTriangle" size={22} />
                            </div>
                            <div>
                                <DialogTitle className="vst-modal-title">
                                    {isId ? 'Konfirmasi Penghapusan Akun' : 'Confirm Account Deletion'}
                                </DialogTitle>
                                <p className="vst-modal-desc">
                                    {isId
                                        ? 'Apakah Anda yakin ingin menghapus akun Anda? Masukkan kata sandi akun Anda untuk memverifikasi tindakan ini.'
                                        : 'Are you sure you want to delete your account? Please enter your password to confirm this action.'}
                                </p>
                            </div>
                        </div>

                        <div className="vst-field" style={{ marginTop: 16 }}>
                            <label htmlFor="confirm_password" className="vst-label">
                                <span>{isId ? 'Kata Sandi Akun' : 'Account Password'}</span>
                            </label>
                            <input
                                id="confirm_password"
                                type="password"
                                ref={passwordInput}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className={`vst-input ${errors.password ? 'has-error' : ''}`}
                                placeholder="••••••••"
                                autoFocus
                                required
                            />
                            {errors.password && (
                                <p className="vst-error" role="alert">{errors.password}</p>
                            )}
                        </div>

                        <div className="vst-modal-footer">
                            <button
                                type="button"
                                className="vst-btn-cancel"
                                onClick={closeModal}
                                disabled={processing}
                            >
                                {isId ? 'Batal' : 'Cancel'}
                            </button>

                            <button
                                type="submit"
                                className="vst-btn-danger"
                                disabled={processing}
                            >
                                <NavIcon name="trash" size={14} />
                                <span>{processing ? (isId ? 'Menghapus...' : 'Deleting...') : (isId ? 'Ya, Hapus Akun' : 'Yes, Delete Account')}</span>
                            </button>
                        </div>
                    </form>
                </DialogPanel>
            </Dialog>
        </section>
    );
}
