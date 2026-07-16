import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children, logoSrc }) {
    return (
        <div className="relative min-h-screen overflow-hidden bg-[#06142c]">
            <img
                src="/images/login-ampera.png"
                alt="Jembatan Ampera pada malam hari"
                className="absolute inset-0 h-full w-full object-cover object-center opacity-45 lg:hidden"
            />
            <div className="absolute inset-0 bg-gradient-to-b from-[#06142c]/75 via-[#06142c]/90 to-[#06142c] lg:hidden" />

            <div className="relative grid min-h-screen lg:grid-cols-[minmax(0,1.35fr)_minmax(430px,0.65fr)]">
                <section className="relative hidden min-h-screen overflow-hidden lg:block">
                    <img
                        src="/images/login-ampera.png"
                        alt="Jembatan Ampera pada malam hari"
                        className="absolute inset-0 h-full w-full object-cover object-center"
                    />
                    <div className="absolute inset-0 bg-gradient-to-r from-[#06142c]/25 via-transparent to-[#06142c]/55" />
                    <div className="absolute inset-x-0 bottom-0 h-3/5 bg-gradient-to-t from-[#06142c]/95 via-[#06142c]/35 to-transparent" />

                    <div className="relative flex h-full min-h-screen flex-col justify-between p-10 xl:p-14">
                        <div className="flex items-center gap-4 text-white">
                            <span className="h-9 w-1 rounded-full bg-gold" />
                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-[0.32em] text-gold-light">Portal Internal</p>
                                <p className="mt-1 text-sm font-semibold">Pemerintah Provinsi Sumatera Selatan</p>
                            </div>
                        </div>

                        <div className="max-w-2xl pb-2 text-white">
                            <p className="mb-4 flex items-center gap-3 text-xs font-bold uppercase tracking-[0.3em] text-gold-light">
                                <span className="h-px w-10 bg-gold" />
                                Satu pusat informasi
                            </p>
                            <h1 className="font-serif text-5xl font-semibold leading-[1.08] tracking-[-0.025em] drop-shadow-lg xl:text-6xl">
                                Informasi publik,<br />bergerak lebih sigap.
                            </h1>
                            <p className="mt-6 max-w-xl text-sm leading-7 text-white/75 xl:text-base">
                                Kelola rilis, kliping media, dan dokumentasi resmi dalam satu ruang kerja yang terintegrasi.
                            </p>
                        </div>
                    </div>
                </section>

                <section className="relative flex min-h-screen items-center justify-center px-5 py-8 sm:px-10 lg:bg-[#f6f3ed] lg:px-12">
                    <div className="absolute left-0 top-0 hidden h-full w-px bg-gold/50 lg:block" />
                    <div className="absolute right-0 top-0 hidden h-44 w-44 rounded-bl-full border-b border-l border-gold/15 lg:block" />

                    <div className="relative w-full max-w-md">
                        <Link href="/" className="mx-auto block w-fit lg:mx-0">
                            <span className="inline-flex rounded-2xl bg-white px-4 py-3 shadow-lg shadow-slate-900/10 ring-1 ring-slate-900/5">
                                <ApplicationLogo logoSrc={logoSrc} className="block h-auto w-[220px] object-contain sm:w-[250px]" />
                            </span>
                        </Link>

                        <div className="mt-6 overflow-hidden rounded-[1.5rem] border border-white/70 bg-white p-6 shadow-2xl shadow-black/20 sm:p-8 lg:border-slate-200/80 lg:shadow-xl lg:shadow-slate-900/10">
                            {children}
                        </div>

                        <div className="mt-6 flex items-center justify-center gap-3 text-center text-[11px] font-medium uppercase tracking-[0.14em] text-white/60 lg:text-slate-400">
                            <span className="h-px w-8 bg-current opacity-40" />
                            <span>Biro Humas dan Protokol</span>
                            <span className="h-px w-8 bg-current opacity-40" />
                        </div>
                    </div>
                </section>
            </div>
        </div>
    );
}
