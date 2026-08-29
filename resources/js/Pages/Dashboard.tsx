import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const usuario = usePage().props.auth.usuario!;

    return (
        <AuthenticatedLayout
            header={
                <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                    Visão geral
                </h1>
            }
        >
            <Head title="Visão geral" />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="rounded-xl border border-tinta/10 bg-white p-6">
                    <p className="text-tinta">
                        Olá, {usuario.nome.split(' ')[0]}. Por enquanto só o
                        acesso está pronto — as contas entram nas próximas
                        etapas.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
