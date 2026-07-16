import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link, usePage } from '@inertiajs/react';

const messages = {
    400: {
        title: 'Permintaan Tidak Valid',
        description: 'Permintaan yang dikirim belum dapat diproses oleh sistem.',
    },
    401: {
        title: 'Belum Masuk',
        description: 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.',
    },
    403: {
        title: 'Akses Ditolak',
        description: 'Akun Anda tidak memiliki hak akses untuk membuka halaman ini.',
    },
    404: {
        title: 'Halaman Tidak Ditemukan',
        description: 'Alamat yang dibuka tidak tersedia atau sudah dipindahkan.',
    },
    419: {
        title: 'Sesi Kedaluwarsa',
        description: 'Sesi halaman sudah berakhir. Silakan muat ulang dan coba lagi.',
    },
    429: {
        title: 'Terlalu Banyak Permintaan',
        description: 'Sistem menerima terlalu banyak permintaan. Silakan coba beberapa saat lagi.',
    },
    500: {
        title: 'Gangguan Server',
        description: 'Terjadi gangguan pada sistem. Tim teknis dapat memeriksa log aplikasi.',
    },
    503: {
        title: 'Layanan Sementara Tidak Tersedia',
        description: 'Sistem sedang sibuk atau dalam pemeliharaan. Silakan coba kembali nanti.',
    },
};

export default function Error({ status }) {
    const user = usePage().props.auth?.user;
    const message = messages[status] || {
        title: 'Terjadi Kendala',
        description: 'Sistem belum dapat memproses halaman yang diminta.',
    };
    const homeUrl = user ? route('dashboard') : route('login');

    return (
        <main className="min-h-screen overflow-hidden bg-slate-950 text-white">
            <Head title={`${status} - ${message.title}`} />

            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(212,175,55,0.22),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.20),_transparent_36%)]" />
            <div className="relative mx-auto flex min-h-screen max-w-6xl items-center px-6 py-12">
                <section className="w-full overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] shadow-2xl shadow-black/30 backdrop-blur">
                    <div className="grid gap-0 lg:grid-cols-[0.9fr_1.1fr]">
                        <div className="border-b border-white/10 bg-white/[0.04] p-8 sm:p-10 lg:border-b-0 lg:border-r">
                            <Link href="/" className="inline-flex items-center gap-3 rounded-2xl border border-white/10 bg-white/10 px-4 py-3 transition hover:bg-white/15">
                                <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-white p-1.5">
                                    <ApplicationLogo className="h-full w-full object-contain" />
                                </span>
                                <span>
                                    <span className="block text-xs font-black uppercase tracking-[0.28em] text-gold">SIGAP Sumsel</span>
                                    <span className="block text-sm font-semibold text-white/75">Biro Humas dan Protokol</span>
                                </span>
                            </Link>

                            <div className="mt-12 inline-flex rounded-full border border-gold/40 bg-gold/10 px-4 py-2 text-sm font-bold text-gold">
                                Kode Error {status}
                            </div>
                            <h1 className="mt-6 text-5xl font-black tracking-tight sm:text-6xl lg:text-7xl">
                                {message.title}
                            </h1>
                            <p className="mt-5 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">
                                {message.description}
                            </p>
                        </div>

                        <div className="flex flex-col justify-between p-8 sm:p-10">
                            <div className="rounded-3xl border border-white/10 bg-slate-900/80 p-6 shadow-inner">
                                <div className="flex items-center justify-between gap-4 border-b border-white/10 pb-5">
                                    <div>
                                        <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-400">Status Sistem</p>
                                        <p className="mt-1 text-2xl font-black text-white">{status}</p>
                                    </div>
                                    <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-gold text-2xl font-black text-primary-dark">
                                        !
                                    </div>
                                </div>
                                <div className="mt-5 space-y-3 text-sm leading-6 text-slate-300">
                                    <p>Periksa kembali alamat halaman, hak akses akun, atau coba muat ulang halaman.</p>
                                    <p>Jika kendala berulang, catat kode error ini untuk pemeriksaan administrator.</p>
                                </div>
                            </div>

                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                <Link href={homeUrl} className="inline-flex justify-center rounded-xl bg-gold px-5 py-3 text-sm font-black text-primary-dark shadow-lg shadow-gold/20 transition hover:bg-gold-light">
                                    {user ? 'Kembali ke Dashboard' : 'Masuk ke Sistem'}
                                </Link>
                                <button type="button" onClick={() => window.history.back()} className="inline-flex justify-center rounded-xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/15">
                                    Kembali ke Halaman Sebelumnya
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    );
}
