import { Head, Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';

export default function Welcome({ auth }) {
    const modules = [
        { name: 'Rilis Berita', description: 'Publikasi resmi dan distribusi informasi', href: route('public.rilis.index') },
        { name: 'Galeri Dokumentasi', description: 'Foto dan video kegiatan pemerintahan', href: route('public.galeri.index') },
        { name: 'Kliping Media', description: 'Pemantauan pemberitaan dan sentimen', href: route('public.kliping.index') },
        { name: 'Arsip Kepegawaian', description: 'Dokumen pegawai yang tertata dan mudah ditelusuri' },
        { name: 'Inventaris Laporan', description: 'Rekapitulasi data pelaporan internal' },
        { name: 'Monev Tindak Lanjut', description: 'Monitoring progres secara terukur' },
    ];
    const destination = auth.user ? route('dashboard') : route('login');

    return (
        <div className="relative flex min-h-screen flex-col overflow-hidden bg-[#06142c] text-white">
            <Head title="SIGAP Sumatera Selatan" />

            <div className="absolute inset-0">
                <img
                    src="/images/login-kantor.png"
                    alt="Kantor Gubernur Sumatera Selatan"
                    className="h-full w-full object-cover object-center opacity-60"
                />
                <div className="absolute inset-0 bg-gradient-to-r from-[#06142c] via-[#06142c]/90 to-[#06142c]/25" />
                <div className="absolute inset-0 bg-gradient-to-t from-[#06142c] via-[#06142c]/20 to-[#06142c]/75" />
            </div>

            <div className="pointer-events-none absolute -left-24 top-1/3 h-80 w-80 rounded-full bg-primary-light/20 blur-[110px]" />
            <div className="pointer-events-none absolute right-0 top-0 h-64 w-64 opacity-20 [background-image:repeating-linear-gradient(135deg,transparent_0,transparent_13px,#c9a84c_14px,transparent_15px)]" />

            <header className="relative z-20 border-b border-white/10">
                <div className="mx-auto flex max-w-[1440px] items-center justify-between px-5 py-5 sm:px-8 lg:px-12">
                    <Link href="/" className="flex min-w-0 items-center gap-4">
                        <span className="flex shrink-0 items-center rounded-xl bg-white px-3 py-2 shadow-xl shadow-black/20 ring-1 ring-white/40">
                            <ApplicationLogo className="h-9 w-auto object-contain sm:h-11" />
                        </span>
                        <span className="hidden leading-tight sm:block">
                            <span className="block text-lg font-extrabold tracking-[-0.015em] text-white">SIGAP <span className="font-medium text-gold-light">SUMSEL</span></span>
                            <span className="mt-1 block text-[9px] font-bold uppercase tracking-[0.22em] text-white/60">Akurat · Responsif · Mantap</span>
                        </span>
                    </Link>

                    <div className="flex items-center gap-4">
                        <span className="hidden text-[10px] font-bold uppercase tracking-[0.24em] text-white/45 md:block">Portal Internal Pemerintah</span>
                        <Link
                            href={destination}
                            className="group inline-flex items-center gap-3 rounded-full border border-gold/50 bg-[#06142c]/50 px-5 py-2.5 text-xs font-bold uppercase tracking-[0.16em] text-gold-light shadow-lg backdrop-blur-md transition hover:border-gold-light hover:bg-gold hover:text-primary-dark"
                        >
                            {auth.user ? 'Dashboard' : 'Masuk Sistem'}
                            <span className="transition-transform group-hover:translate-x-1">→</span>
                        </Link>
                    </div>
                </div>
            </header>

            <main className="relative z-10 mx-auto grid w-full max-w-[1440px] flex-1 items-center gap-12 px-5 py-12 sm:px-8 lg:grid-cols-[minmax(0,1.08fr)_minmax(430px,0.72fr)] lg:px-12 lg:py-16 xl:gap-20">
                <section className="max-w-4xl">
                    <div className="mb-7 flex items-center gap-4">
                        <span className="h-px w-12 bg-gold" />
                        <p className="text-[10px] font-bold uppercase tracking-[0.34em] text-gold-light sm:text-xs">
                            SIGAP SUMSEL <span className="px-2 text-white/40">|</span> Akurat Responsif Mantap
                        </p>
                    </div>

                    <h1 className="font-serif text-[2.8rem] font-semibold leading-[1.05] tracking-[-0.035em] text-white drop-shadow-xl sm:text-6xl lg:text-7xl xl:text-[5.4rem]">
                        Satu informasi.
                        <span className="mt-1 block italic text-gold-light">Gerak cepat untuk Sumsel.</span>
                    </h1>

                    <p className="mt-7 max-w-2xl border-l border-gold/60 pl-5 text-sm leading-7 text-white/70 sm:text-base sm:leading-8">
                        Platform kerja terpadu Biro Humas dan Protokol untuk menghadirkan informasi yang akurat, respons yang lebih cepat, serta tata kelola komunikasi publik yang mantap.
                    </p>

                    <div className="mt-9 flex flex-col gap-4 sm:flex-row sm:items-center">
                        <Link
                            href={destination}
                            className="group inline-flex items-center justify-center gap-4 rounded-xl bg-gold px-7 py-4 text-sm font-black uppercase tracking-[0.13em] text-primary-dark shadow-2xl shadow-black/25 transition hover:-translate-y-0.5 hover:bg-gold-light"
                        >
                            {auth.user ? 'Buka Dashboard' : 'Akses SIGAP'}
                            <span className="text-lg transition-transform group-hover:translate-x-1">→</span>
                        </Link>
                        <p className="text-xs font-medium leading-5 text-white/50">
                            Akses terbatas untuk<br className="hidden sm:block" /> pengguna terotorisasi
                        </p>
                    </div>

                    <div className="mt-12 grid max-w-2xl grid-cols-3 divide-x divide-white/15 border-y border-white/10 py-5">
                        <div className="pr-4">
                            <p className="font-serif text-xl font-semibold text-gold-light sm:text-2xl">Akurat</p>
                            <p className="mt-1 text-[8px] font-bold uppercase tracking-[0.16em] text-white/45 sm:text-[9px] sm:tracking-[0.2em]">Data Terverifikasi</p>
                        </div>
                        <div className="px-4 sm:px-6">
                            <p className="font-serif text-xl font-semibold text-gold-light sm:text-2xl">Responsif</p>
                            <p className="mt-1 text-[8px] font-bold uppercase tracking-[0.16em] text-white/45 sm:text-[9px] sm:tracking-[0.2em]">Gerak Lebih Cepat</p>
                        </div>
                        <div className="pl-4 sm:pl-6">
                            <p className="font-serif text-xl font-semibold text-gold-light sm:text-2xl">Mantap</p>
                            <p className="mt-1 text-[8px] font-bold uppercase tracking-[0.16em] text-white/45 sm:text-[9px] sm:tracking-[0.2em]">Tata Kelola Andal</p>
                        </div>
                    </div>
                </section>

                <section className="relative lg:justify-self-end">
                    <div className="absolute -inset-3 rounded-[2rem] border border-gold/10" />
                    <div className="relative overflow-hidden rounded-[1.75rem] border border-white/15 bg-[#081a35]/80 shadow-2xl shadow-black/40 backdrop-blur-xl">
                        <div className="flex items-center justify-between border-b border-white/10 px-6 py-5 sm:px-7">
                            <div>
                                <p className="text-[9px] font-bold uppercase tracking-[0.3em] text-gold-light">Layanan Terintegrasi</p>
                                <h2 className="mt-1.5 font-serif text-2xl font-semibold text-white">Ekosistem SIGAP</h2>
                            </div>
                            <span className="flex h-10 w-10 items-center justify-center rounded-full border border-gold/30 bg-gold/10 font-serif text-sm text-gold-light">SS</span>
                        </div>

                        <div className="grid sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            {modules.map((module, index) => {
                                const content = (
                                    <>
                                        <span className="font-serif text-sm italic text-gold/70">{String(index + 1).padStart(2, '0')}</span>
                                        <div className="min-w-0 flex-1">
                                            <h3 className="text-sm font-bold text-white transition group-hover:text-gold-light">{module.name}</h3>
                                            <p className="mt-1 text-[11px] leading-5 text-white/45">{module.description}</p>
                                        </div>
                                        {module.href && <span className="self-center text-gold-light/60 transition group-hover:translate-x-1 group-hover:text-gold-light" aria-hidden="true">→</span>}
                                    </>
                                );
                                const className = "group flex gap-4 border-b border-white/10 px-6 py-5 transition hover:bg-white/[0.06] sm:border-r sm:even:border-r-0 lg:border-r-0 xl:border-r xl:even:border-r-0";

                                return module.href ? (
                                    <Link key={module.name} href={module.href} className={className}>{content}</Link>
                                ) : (
                                    <div key={module.name} className={className}>{content}</div>
                                );
                            })}
                        </div>

                        <div className="flex items-center gap-3 bg-white/[0.04] px-6 py-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/50">
                            <span className="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.8)]" />
                            Portal operasional dan siap digunakan
                        </div>
                    </div>
                </section>
            </main>

            <footer className="relative z-10 border-t border-white/10">
                <div className="mx-auto flex max-w-[1440px] flex-col gap-2 px-5 py-4 text-center text-[10px] font-medium uppercase tracking-[0.16em] text-white/35 sm:flex-row sm:items-center sm:justify-between sm:px-8 sm:text-left lg:px-12">
                    <span>Pemerintah Provinsi Sumatera Selatan</span>
                    <span>© {new Date().getFullYear()} Biro Humas dan Protokol</span>
                </div>
            </footer>
        </div>
    );
}
