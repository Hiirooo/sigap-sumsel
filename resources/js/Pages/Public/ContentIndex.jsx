import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';

const labels = {
    rilis: 'Informasi Resmi',
    galeri: 'Dokumentasi Pemerintah',
    kliping: 'Pantauan Media',
};

function formatDate(value) {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${value.slice(0, 10)}T00:00:00`));
}

function paginationLabel(label) {
    return label
        .replace('&laquo; Previous', '← Sebelumnya')
        .replace('Next &raquo;', 'Berikutnya →');
}

function ContentCard({ item, type }) {
    return (
        <Link
            href={item.href}
            className="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-gold/50 hover:shadow-xl hover:shadow-slate-900/10"
        >
            <div className="relative aspect-[16/9] overflow-hidden bg-[#0a2348]">
                {item.image_url ? (
                    <img src={item.image_url} alt="" className="h-full w-full object-cover transition duration-700 group-hover:scale-[1.04]" />
                ) : (
                    <div className="absolute inset-0 flex items-end bg-[linear-gradient(135deg,#0c2d5e_0%,#06142c_70%)] p-6">
                        <div className="absolute inset-0 opacity-20 [background-image:repeating-linear-gradient(135deg,transparent_0,transparent_15px,#c9a84c_16px,transparent_17px)]" />
                        <p className="relative font-serif text-2xl font-semibold text-white/90">SIGAP <span className="text-gold-light">SUMSEL</span></p>
                    </div>
                )}
                <span className="absolute left-4 top-4 rounded-full border border-white/20 bg-[#06142c]/80 px-3 py-1.5 text-[9px] font-bold uppercase tracking-[0.16em] text-gold-light backdrop-blur-md">
                    {type === 'galeri' ? `${item.media_count} media` : labels[type]}
                </span>
            </div>

            <div className="flex flex-1 flex-col p-5 sm:p-6">
                <div className="flex flex-wrap items-center gap-2 text-[10px] font-bold uppercase tracking-[0.13em] text-slate-400">
                    <span>{formatDate(item.date)}</span>
                    {(item.author || item.source || item.leader) && <span className="h-1 w-1 rounded-full bg-gold" />}
                    <span className="truncate">{item.author || item.source || item.leader}</span>
                </div>
                <h2 className="mt-3 text-lg font-extrabold leading-snug tracking-[-0.025em] text-slate-900 transition group-hover:text-primary-light">
                    {item.title}
                </h2>
                <p className="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">
                    {item.excerpt || (type === 'galeri' ? 'Dokumentasi resmi kegiatan Pemerintah Provinsi Sumatera Selatan.' : 'Informasi selengkapnya tersedia pada halaman detail.')}
                </p>
                <div className="mt-auto flex items-center gap-2 pt-5 text-xs font-extrabold text-primary">
                    Lihat selengkapnya
                    <span className="transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                </div>
            </div>
        </Link>
    );
}

export default function ContentIndex({ type, title, description, items, search }) {
    return (
        <PublicLayout>
            <Head title={title} />

            <section className="relative overflow-hidden bg-[#081a35] text-white">
                <div className="absolute inset-0 opacity-20 [background-image:linear-gradient(120deg,transparent_35%,#c9a84c_100%)]" />
                <div className="absolute -right-20 -top-32 h-96 w-96 rounded-full border border-gold/20" />
                <div className="relative mx-auto max-w-[1440px] px-5 py-14 sm:px-8 sm:py-20 lg:px-12">
                    <div className="flex items-center gap-3 text-[10px] font-bold uppercase tracking-[0.28em] text-gold-light">
                        <span className="h-px w-10 bg-gold" />
                        {labels[type]}
                    </div>
                    <div className="mt-5 grid gap-6 lg:grid-cols-[1fr_0.7fr] lg:items-end">
                        <h1 className="font-serif text-4xl font-semibold tracking-[-0.035em] sm:text-5xl lg:text-6xl">{title}</h1>
                        <p className="max-w-xl text-sm leading-7 text-white/65 lg:justify-self-end lg:text-right">{description}</p>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-[1440px] px-5 py-10 sm:px-8 sm:py-14 lg:px-12">
                <div className="mb-8 flex flex-col gap-4 border-b border-slate-200 pb-7 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-[10px] font-bold uppercase tracking-[0.22em] text-gold-dark">Katalog Publik</p>
                        <p className="mt-2 text-sm text-slate-500">Menampilkan {items.total} konten yang telah dipublikasikan.</p>
                    </div>
                    <form action={route(`public.${type}.index`)} method="GET" className="flex w-full max-w-md gap-2">
                        <label htmlFor="search" className="sr-only">Cari {title}</label>
                        <input
                            id="search"
                            type="search"
                            name="search"
                            defaultValue={search}
                            placeholder={`Cari ${title.toLowerCase()}...`}
                            className="min-w-0 flex-1 rounded-xl border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-primary focus:ring-primary"
                        />
                        <button type="submit" className="rounded-xl bg-primary px-5 py-3 text-xs font-bold text-white transition hover:bg-primary-light">Cari</button>
                    </form>
                </div>

                {items.data.length > 0 ? (
                    <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        {items.data.map((item) => <ContentCard key={item.id} item={item} type={type} />)}
                    </div>
                ) : (
                    <div className="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-20 text-center">
                        <p className="font-serif text-2xl font-semibold text-slate-700">Konten belum tersedia</p>
                        <p className="mt-2 text-sm text-slate-500">Coba gunakan kata pencarian lain atau kembali lagi nanti.</p>
                    </div>
                )}

                {items.links.length > 3 && (
                    <nav className="mt-10 flex flex-wrap justify-center gap-2" aria-label="Navigasi halaman">
                        {items.links.map((link, index) => (
                            link.url ? (
                                <Link
                                    key={`${link.label}-${index}`}
                                    href={link.url}
                                    preserveScroll
                                    className={`min-w-10 rounded-lg border px-3 py-2 text-center text-xs font-bold transition ${link.active ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-500 hover:border-gold hover:text-primary'}`}
                                >
                                    {paginationLabel(link.label)}
                                </Link>
                            ) : (
                                <span key={`${link.label}-${index}`} className="min-w-10 rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-center text-xs text-slate-300">
                                    {paginationLabel(link.label)}
                                </span>
                            )
                        ))}
                    </nav>
                )}
            </section>
        </PublicLayout>
    );
}
