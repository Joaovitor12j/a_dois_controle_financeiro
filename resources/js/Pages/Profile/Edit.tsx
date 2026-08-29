import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit() {
    return (
        <AuthenticatedLayout
            header={
                <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                    Meu perfil
                </h1>
            }
        >
            <Head title="Meu perfil" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
                <div className="rounded-xl border border-tinta/10 bg-white p-6 sm:p-8">
                    <UpdateProfileInformationForm className="max-w-xl" />
                </div>

                <div className="rounded-xl border border-tinta/10 bg-white p-6 sm:p-8">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
