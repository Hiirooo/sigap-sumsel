import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const canManageContent = ['admin', 'operator'].includes(user.role);
    const isAdmin = user.role === 'admin';

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    return (
        <div className="min-h-screen bg-gray-50">
            <nav className="border-b border-emerald-950/30 bg-gradient-to-r from-primary via-emerald-800 to-primary-dark text-white shadow-lg shadow-emerald-950/10">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-20 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/" className="flex items-center gap-3 rounded-2xl px-1 py-2 transition hover:bg-white/10">
                                    <span className="flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl border border-white/30 bg-white p-1.5 shadow-sm">
                                        <ApplicationLogo className="h-full w-full object-contain" />
                                    </span>
                                    <span className="hidden leading-tight lg:block">
                                        <span className="block text-sm font-black uppercase tracking-[0.22em] text-gold">SIGAP Sumsel</span>
                                        <span className="block text-xs font-semibold text-white/80">Biro Humas dan Protokol</span>
                                    </span>
                                </Link>
                            </div>

                            <div className="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                                <NavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                    className="text-white hover:text-gold focus:text-gold"
                                >
                                    Dashboard
                                </NavLink>
                                {canManageContent && (
                                    <>
                                        <NavLink
                                            href={route('rilis-berita.index')}
                                            active={route().current('rilis-berita.*')}
                                            className="text-white hover:text-gold focus:text-gold"
                                        >
                                            Rilis Berita
                                        </NavLink>
                                        <NavLink
                                            href={route('dokumentasi.index')}
                                            active={route().current('dokumentasi.*')}
                                            className="text-white hover:text-gold focus:text-gold"
                                        >
                                            Galeri Dokumentasi
                                        </NavLink>
                                        <NavLink
                                            href={route('kliping.index')}
                                            active={route().current('kliping.*')}
                                            className="text-white hover:text-gold focus:text-gold"
                                        >
                                            Kliping
                                        </NavLink>
                                        <NavLink
                                            href={route('arsip-statis.index')}
                                            active={route().current('arsip-statis.*')}
                                            className="text-white hover:text-gold focus:text-gold"
                                        >
                                            Arsip Statis
                                        </NavLink>
                                    </>
                                )}
                                <NavLink
                                    href={route('inventaris.index')}
                                    active={route().current('inventaris.*')}
                                    className="text-white hover:text-gold focus:text-gold"
                                >
                                    Inventaris & Laporan
                                </NavLink>
                                <NavLink
                                    href={route('monev.index')}
                                    active={route().current('monev.*')}
                                    className="text-white hover:text-gold focus:text-gold"
                                >
                                    Monev
                                </NavLink>
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-xl border border-white/20 bg-white/95 px-4 py-2 text-sm font-bold leading-4 text-emerald-950 shadow-sm transition duration-150 ease-in-out hover:bg-white focus:outline-none focus:ring-2 focus:ring-gold/70"
                                            >
                                                {user.name}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
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
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            Profil Saya
                                        </Dropdown.Link>
                                        {isAdmin && (
                                            <Dropdown.Link
                                                href={route('settings.users.index')}
                                            >
                                                User & Role
                                            </Dropdown.Link>
                                        )}
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Keluar
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-xl border border-white/20 p-2 text-white transition duration-150 ease-in-out hover:bg-white/10 focus:bg-white/10 focus:outline-none focus:ring-2 focus:ring-gold/70"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 border-t border-white/10 bg-white pb-3 pt-2 text-emerald-950 shadow-inner">
                        <ResponsiveNavLink
                            href={route('dashboard')}
                            active={route().current('dashboard')}
                        >
                            Dashboard
                        </ResponsiveNavLink>
                        {canManageContent && (
                            <>
                                <ResponsiveNavLink
                                    href={route('rilis-berita.index')}
                                    active={route().current('rilis-berita.*')}
                                >
                                    Rilis Berita
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('dokumentasi.index')}
                                    active={route().current('dokumentasi.*')}
                                >
                                    Galeri Dokumentasi
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('kliping.index')}
                                    active={route().current('kliping.*')}
                                >
                                    Kliping
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('arsip-statis.index')}
                                    active={route().current('arsip-statis.*')}
                                >
                                    Arsip Statis
                                </ResponsiveNavLink>
                            </>
                        )}
                        <ResponsiveNavLink
                            href={route('inventaris.index')}
                            active={route().current('inventaris.*')}
                        >
                            Inventaris & Laporan
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('monev.index')}
                            active={route().current('monev.*')}
                        >
                            Monev
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('profile.edit')}
                            active={route().current('profile.*')}
                        >
                            Profil
                        </ResponsiveNavLink>
                        {isAdmin && (
                            <ResponsiveNavLink
                                href={route('settings.users.index')}
                                active={route().current('settings.users.*')}
                            >
                                User & Role
                            </ResponsiveNavLink>
                        )}
                    </div>

                    <div className="border-t border-gray-200 bg-white pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-bold text-gray-900">
                                {user.name}
                            </div>
                            <div className="text-sm font-medium text-gray-500">
                                {user.email} · {user.role}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profil Saya
                            </ResponsiveNavLink>
                            {isAdmin && (
                                <ResponsiveNavLink href={route('settings.users.index')}>
                                    Pengaturan User & Role
                                </ResponsiveNavLink>
                            )}
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Keluar
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-gradient-to-r from-primary-light to-emerald-700 shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
