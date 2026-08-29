import ApplicationLogo from '@/Components/ApplicationLogo';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-tinta px-4 py-10">
            <div
                aria-hidden="true"
                className="absolute inset-0 bg-[linear-gradient(100deg,#1E4A3E_0%,#26443F_38%,#3B3542_50%,#452B39_62%,#4E2836_100%)]"
            />
            <div
                aria-hidden="true"
                className="absolute inset-y-0 left-1/2 hidden w-px -translate-x-1/2 bg-gradient-to-b from-transparent via-ouro/50 to-transparent lg:block"
            />

            <main className="relative w-full max-w-md rounded-2xl bg-papel px-7 py-9 shadow-2xl shadow-black/30 sm:px-9">
                <ApplicationLogo className="h-10 w-16" />

                <h1 className="mt-6 font-display text-4xl font-bold leading-none tracking-tight text-tinta">
                    A Dois
                </h1>
                <p className="mt-2 text-sm text-tinta-claro">
                    As contas do casal, em um lugar só.
                </p>

                <div className="mt-8">{children}</div>
            </main>
        </div>
    );
}
