import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const usuario = usePage().props.auth.usuario!;

    const [menuAberto, setMenuAberto] = useState(false);

    return (
        <div className="min-h-screen bg-papel">
            <nav className="border-b border-tinta/10 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center gap-2.5">
                                <Link href={route('dashboard')}>
                                    <ApplicationLogo className="block h-7 w-11" />
                                </Link>
                                <span className="font-display text-lg font-bold leading-none text-tinta">
                                    A Dois
                                </span>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                >
                                    Visão geral
                                </NavLink>

                                <NavLink
                                    href={route('contas.index')}
                                    active={route().current('contas.*')}
                                >
                                    Contas
                                </NavLink>

                                <NavLink
                                    href={route('rendas.index')}
                                    active={route().current('rendas.*')}
                                >
                                    Rendas
                                </NavLink>
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button
                                        type="button"
                                        className="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-tinta-claro transition duration-150 ease-in-out hover:bg-papel focus:outline-none focus:ring-2 focus:ring-ouro"
                                    >
                                        <span
                                            aria-hidden="true"
                                            className="h-2.5 w-2.5 rounded-full"
                                            style={{
                                                backgroundColor: usuario.cor,
                                            }}
                                        />
                                        {usuario.nome}

                                        <svg
                                            className="h-4 w-4"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fillRule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </Dropdown.Trigger>

                                <Dropdown.Content>
                                    <Dropdown.Link href={route('profile.edit')}>
                                        Meu perfil
                                    </Dropdown.Link>
                                    <Dropdown.Link
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                    >
                                        Sair
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() => setMenuAberto((aberto) => !aberto)}
                                aria-label="Abrir menu"
                                aria-expanded={menuAberto}
                                className="inline-flex items-center justify-center rounded-lg p-2 text-tinta-claro transition duration-150 ease-in-out hover:bg-papel focus:outline-none focus:ring-2 focus:ring-ouro"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d={
                                            menuAberto
                                                ? 'M6 18L18 6M6 6l12 12'
                                                : 'M4 6h16M4 12h16M4 18h16'
                                        }
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div className={(menuAberto ? 'block' : 'hidden') + ' sm:hidden'}>
                    <div className="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            href={route('dashboard')}
                            active={route().current('dashboard')}
                        >
                            Visão geral
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            href={route('contas.index')}
                            active={route().current('contas.*')}
                        >
                            Contas
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            href={route('rendas.index')}
                            active={route().current('rendas.*')}
                        >
                            Rendas
                        </ResponsiveNavLink>
                    </div>

                    <div className="border-t border-tinta/10 pb-1 pt-4">
                        <div className="flex items-center gap-2 px-4">
                            <span
                                aria-hidden="true"
                                className="h-2.5 w-2.5 rounded-full"
                                style={{ backgroundColor: usuario.cor }}
                            />
                            <div>
                                <div className="text-base font-medium text-tinta">
                                    {usuario.nome}
                                </div>
                                <div className="text-sm text-tinta-claro">
                                    {usuario.email}
                                </div>
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Meu perfil
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Sair
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="border-b border-tinta/10 bg-white">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
