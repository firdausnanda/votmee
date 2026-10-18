import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status }) {
    const locale = usePage().props.auth.user.locale === 'en' ? 'en' : 'id';
    const title = locale === 'id' ? 'Pengaturan akun' : 'Account settings';

    return (
        <AuthenticatedLayout
            header={
                <h2 className="vt-settings-title">
                    {title}
                </h2>
            }
        >
            <Head title={title} />

            <div className="vt-settings-content">
                <div className="vt-settings-cards">
                    <div className="vt-settings-card">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    <div className="vt-settings-card">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    <div className="vt-settings-card">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
