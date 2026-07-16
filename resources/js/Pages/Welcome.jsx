import { Head, Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';

export default function Welcome({ auth }) {
    const features = [
        'Rilis berita',
        'Galeri dokumentasi',
        'Kliping media',
        'Arsip statis',
        'Inventaris laporan',
        'Monev tindak lanjut',
    ];

    return (
        <div className="relative min-h-screen overflow-hidden bg-gradient-to-br from-emerald-950 via-primary to-emerald-800 text-white">
            <Head title="SIGAP SUMSEL" />
            <div className="absolute -left-24 top-16 h-72 w-72 rounded-full bg-gold/20 blur-3xl" />
            <div className="absolute -right-20 bottom-10 h-96 w-96 rounded-full bg-emerald-300/10 blur-3xl" />

            <header className="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
                <Link href="/" className="flex items-center gap-3">
                    <span className="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl border border-white/30 bg-white p-2 shadow-lg">
                        <ApplicationLogo className="h-full w-full object-contain" />
                    </span>
                    <span className="leading-tight">
                        <span className="block text-sm font-black uppercase tracking-[0.22em] text-gold">SIGAP Sumsel</span>
                        <span className="block text-xs font-semibold text-white/70">Biro Humas dan Protokol</span>
                    </span>
                </Link>

                <Link
                    href={auth.user ? route('dashboard') : route('login')}
                    className="rounded-full border border-white/25 bg-white/10 px-5 py-2 text-sm font-bold text-white shadow-sm backdrop-blur transition hover:bg-white hover:text-emerald-950"
                >
                    {auth.user ? 'Dashboard' : 'Login'}
                </Link>
            </header>

            <main className="relative z-10 mx-auto grid min-h-[calc(100vh-104px)] max-w-7xl items-center gap-12 px-6 pb-16 pt-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
                <section>
                    <div className="mb-6 inline-flex items-center rounded-full border border-gold/40 bg-gold/10 px-4 py-2 text-sm font-bold text-gold shadow-sm">
                        Sistem Dokumentasi Pemberitaan Terintegrasi
                    </div>
                    <h1 className="max-w-4xl text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Pusat kendali dokumentasi dan publikasi Pemerintah Provinsi Sumatera Selatan.
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg leading-8 text-emerald-50/80">
                        SIGAP membantu Biro Humas dan Protokol mengelola rilis berita, dokumentasi pimpinan, kliping media, arsip statis, inventaris laporan, dan monitoring tindak lanjut dalam satu sistem kerja yang tertib.
                    </p>

                    <div className="mt-9 flex flex-col gap-3 sm:flex-row">
                        <Link
                            href={auth.user ? route('dashboard') : route('login')}
                            className="inline-flex items-center justify-center rounded-2xl bg-gold px-7 py-4 text-base font-black text-emerald-950 shadow-xl shadow-emerald-950/25 transition hover:-translate-y-0.5 hover:bg-yellow-300"
                        >
                            {auth.user ? 'Masuk ke Dashboard' : 'Login Sistem'}
                        </Link>
                    </div>
                </section>

                <section id="modul" className="rounded-[2rem] border border-white/15 bg-white/95 p-6 text-emerald-950 shadow-2xl shadow-emerald-950/30 ring-1 ring-black/5">
                    <div className="rounded-3xl bg-gradient-to-br from-emerald-50 to-amber-50 p-6">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-sm font-black uppercase tracking-[0.2em] text-emerald-700">Executive Workspace</p>
                                <h2 className="mt-2 text-3xl font-black text-gray-950">SIGAP Sumsel</h2>
                            </div>
                            <span className="rounded-full bg-emerald-900 px-4 py-2 text-xs font-black uppercase tracking-widest text-gold">Internal</span>
                        </div>

                        <div className="mt-8 grid grid-cols-2 gap-3">
                            {features.map((feature) => (
                                <div key={feature} className="rounded-2xl border border-emerald-900/10 bg-white p-4 shadow-sm">
                                    <div className="mb-3 h-2 w-10 rounded-full bg-gold" />
                                    <p className="text-sm font-black text-gray-900">{feature}</p>
                                </div>
                            ))}
                        </div>

                        <div className="mt-6 rounded-2xl bg-emerald-950 p-5 text-white">
                            <p className="text-sm font-bold text-gold">Fokus Sistem</p>
                            <p className="mt-2 text-sm leading-6 text-white/80">
                                Menjaga dokumentasi kegiatan, publikasi berita, dan laporan monitoring tetap mudah ditelusuri, terukur, dan siap dilaporkan.
                            </p>
                        </div>
                    </div>
                </section>
            </main>

            <footer className="relative z-10 border-t border-white/10 px-6 py-4 text-center text-xs font-medium text-white/55">
                Biro Humas dan Protokol Setda Provinsi Sumatera Selatan
            </footer>
        </div>
    );
}
