import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';

const navigation = [
    { label: 'Rilis Berita', href: route('public.rilis.index'), segment: '/publik/rilis' },
    { label: 'Galeri', href: route('public.galeri.index'), segment: '/publik/galeri' },
    { label: 'Kliping', href: route('public.kliping.index'), segment: '/publik/kliping' },
];

export default function PublicLayout({ children }) {
    const { auth } = usePage().props;
    const currentPath = usePage().url.split('?')[0];
    const destination = auth.user ? route('dashboard') : route('login');

    return (
        <div className="min-h-screen bg-[#f3f5f7] text-slate-900">
            <header className="relative z-30 border-b border-white/10 bg-[#06142c] text-white shadow-xl shadow-slate-950/10">
                <div className="mx-auto flex max-w-[1440px] items-center justify-between gap-5 px-5 py-4 sm:px-8 lg:px-12">
                    <Link href="/" className="flex min-w-0 items-center gap-3.5">
                        <span className="flex shrink-0 rounded-lg bg-white px-2.5 py-2 shadow-lg ring-1 ring-white/30">
                            <ApplicationLogo className="h-8 w-auto object-contain sm:h-10" />
                        </span>
                        <span className="hidden leading-tight sm:block">
                            <span className="block text-base font-extrabold tracking-[-0.02em]">SIGAP <span className="font-medium text-gold-light">SUMSEL</span></span>
                            <span className="mt-1 block text-[8px] font-bold uppercase tracking-[0.22em] text-white/50">Portal Informasi Publik</span>
                        </span>
                    </Link>

                    <nav className="hidden items-center gap-1 lg:flex" aria-label="Navigasi publik">
                        {navigation.map((item) => {
                            const active = currentPath.startsWith(item.segment);

                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={`rounded-full px-4 py-2 text-xs font-bold transition ${active ? 'bg-white/10 text-gold-light' : 'text-white/65 hover:bg-white/[0.06] hover:text-white'}`}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>

                    <Link
                        href={destination}
                        className="inline-flex shrink-0 items-center gap-2 rounded-full border border-gold/40 px-4 py-2 text-[10px] font-bold uppercase tracking-[0.14em] text-gold-light transition hover:bg-gold hover:text-primary-dark sm:px-5"
                    >
                        {auth.user ? 'Dashboard' : 'Masuk'}
                        <span aria-hidden="true">→</span>
                    </Link>
                </div>

                <nav className="flex gap-1 overflow-x-auto border-t border-white/10 px-4 py-2 lg:hidden" aria-label="Navigasi publik seluler">
                    {navigation.map((item) => {
                        const active = currentPath.startsWith(item.segment);

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`whitespace-nowrap rounded-full px-3.5 py-2 text-[11px] font-bold ${active ? 'bg-gold text-primary-dark' : 'text-white/65'}`}
                            >
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>
            </header>

            <main>{children}</main>

            <footer className="border-t border-slate-200 bg-white">
                <div className="mx-auto grid max-w-[1440px] gap-7 px-5 py-9 sm:px-8 md:grid-cols-2 md:items-end lg:px-12">
                    <div>
                        <p className="text-sm font-extrabold tracking-[-0.015em] text-primary">SIGAP SUMSEL</p>
                        <p className="mt-2 max-w-lg text-xs leading-6 text-slate-500">Sistem Informasi Gerak Cepat Biro Humas dan Protokol Pemerintah Provinsi Sumatera Selatan.</p>
                    </div>
                    <div className="text-xs leading-6 text-slate-400 md:text-right">
                        <p>Akurat · Responsif · Mantap</p>
                        <p>© {new Date().getFullYear()} Pemerintah Provinsi Sumatera Selatan</p>
                    </div>
                </div>
            </footer>
        </div>
    );
}
