import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children, logoSrc }) {
    return (
        <div className="relative min-h-screen overflow-hidden bg-[#07182f]">
            <img
                src="/images/login-kantor.png"
                alt="Kantor Gubernur Sumatera Selatan"
                className="absolute inset-0 h-full w-full object-cover object-center opacity-50 lg:hidden"
            />
            <div className="absolute inset-0 bg-gradient-to-b from-[#07182f]/70 via-[#07182f]/90 to-[#07182f] lg:hidden" />

            <div className="relative grid min-h-screen lg:grid-cols-[minmax(0,1.45fr)_minmax(430px,0.75fr)]">
                <section className="relative hidden min-h-screen overflow-hidden lg:block">
                    <img
                        src="/images/login-kantor.png"
                        alt="Kantor Gubernur Sumatera Selatan"
                        className="absolute inset-0 h-full w-full object-cover object-center"
                    />
                    <div className="absolute inset-0 bg-gradient-to-r from-[#07182f]/35 via-transparent to-[#07182f]/45" />
                    <div className="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-[#061326]/95 via-[#061326]/30 to-transparent" />

                    <div className="relative flex h-full min-h-screen flex-col justify-between p-10 xl:p-14">
                        <div className="flex items-start gap-4 text-white">
                            <span className="mt-1 h-14 w-1 rounded-full bg-gradient-to-b from-gold-light to-gold" />
                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-[0.38em] text-gold-light">Pemerintah Provinsi Sumatera Selatan</p>
                                <p className="mt-1.5 text-2xl font-extrabold tracking-[-0.025em] text-white drop-shadow-md xl:text-3xl">
                                    SIGAP <span className="font-medium text-gold-light">SUMSEL</span>
                                </p>
                            </div>
                        </div>

                        <div className="max-w-2xl pb-3 text-white">
                            <p className="mb-5 flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.28em] text-gold-light">
                                <span className="h-px w-10 bg-gold" />
                                Portal Informasi Terpadu
                            </p>
                            <h1 className="font-serif text-5xl font-semibold leading-[1.06] tracking-[-0.03em] drop-shadow-lg xl:text-6xl">
                                Akurat. Responsif.<br />Mantap.
                            </h1>
                            <p className="mt-6 max-w-xl text-sm leading-7 text-white/75 xl:text-[15px]">
                                Kelola rilis, kliping media, dan dokumentasi resmi dalam satu ruang kerja yang terintegrasi.
                            </p>
                            <p className="mt-7 text-[11px] font-bold uppercase tracking-[0.24em] text-white/90">
                                SIGAP SUMSEL <span className="px-2 text-gold-light">|</span> AKURAT RESPONSIF MANTAP
                            </p>
                        </div>
                    </div>
                </section>

                <section className="relative flex min-h-screen items-center justify-center px-5 py-8 sm:px-10 lg:bg-[#f7f5f0] lg:px-12">
                    <div className="absolute left-0 top-0 hidden h-full w-px bg-gold/50 lg:block" />
                    <div className="absolute right-0 top-0 hidden h-40 w-40 rounded-bl-full border-b border-l border-gold/20 lg:block" />

                    <div className="relative w-full max-w-md">
                        <Link href="/" className="mx-auto block w-fit lg:mx-0">
                            <span className="inline-flex rounded-2xl bg-white px-4 py-3 shadow-lg shadow-slate-900/10 ring-1 ring-slate-900/5">
                                <ApplicationLogo logoSrc={logoSrc} className="block h-auto w-[220px] object-contain sm:w-[250px]" />
                            </span>
                        </Link>

                        <div className="mt-6 overflow-hidden rounded-[1.5rem] border border-white/80 bg-white p-6 shadow-2xl shadow-black/20 sm:p-8 lg:border-slate-200/80 lg:shadow-xl lg:shadow-slate-900/10">
                            {children}
                        </div>

                        <div className="mt-6 flex items-center justify-center gap-3 text-center text-[11px] font-medium uppercase tracking-[0.14em] text-white/60 lg:text-slate-400">
                            <span className="h-px w-8 bg-current opacity-40" />
                            <span>Biro Humas dan Protokol Sumsel</span>
                            <span className="h-px w-8 bg-current opacity-40" />
                        </div>
                    </div>
                </section>
            </div>
        </div>
    );
}
