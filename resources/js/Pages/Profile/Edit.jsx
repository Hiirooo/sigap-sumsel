import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Profil Saya
                </h2>
            }
        >
            <Head title="Profil Saya" />

            <div className="min-h-screen bg-slate-100 py-8">
                <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-3xl bg-gradient-to-r from-emerald-950 to-primary p-6 text-white shadow-xl">
                        <p className="text-sm font-black uppercase tracking-[0.22em] text-gold">Account Center</p>
                        <h1 className="mt-3 text-3xl font-black">Kelola Profil dan Keamanan Akun</h1>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75">
                            Perbarui identitas akun, email, dan password yang digunakan untuk mengakses SIGAP Sumsel.
                        </p>
                    </div>

                    <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-2xl"
                        />
                    </div>

                    <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                        <UpdatePasswordForm className="max-w-2xl" />
                    </div>

                    <div className="rounded-2xl border border-red-100 bg-white p-6 shadow-sm sm:p-8">
                        <DeleteUserForm className="max-w-2xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
