import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';

const config = {
    rilis: { label: 'Rilis Berita', indexRoute: 'public.rilis.index' },
    galeri: { label: 'Galeri Dokumentasi', indexRoute: 'public.galeri.index' },
    kliping: { label: 'Kliping Media', indexRoute: 'public.kliping.index' },
};

function formatDate(value) {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${value.slice(0, 10)}T00:00:00`));
}

function MediaGallery({ media }) {
    if (!media?.length) return null;

    return (
        <div className={`grid gap-4 ${media.length > 1 ? 'md:grid-cols-2' : ''}`}>
            {media.map((item, index) => (
                <figure key={item.id ?? index} className="overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 shadow-sm">
                    {item.type === 'video' ? (
                        <video controls preload="metadata" poster={item.thumbnail_url || undefined} className="aspect-video h-full w-full bg-black object-contain">
                            <source src={item.file_url} />
                            Browser Anda tidak mendukung pemutaran video.
                        </video>
                    ) : (
                        <img src={item.file_url} alt={`${item.name || 'Dokumentasi'} ${index + 1}`} className="aspect-[4/3] h-full w-full object-cover" />
                    )}
                </figure>
            ))}
        </div>
    );
}

export default function ContentShow({ type, item, related }) {
    const page = config[type];
    const supportingImages = type === 'rilis' ? item.gallery : [];

    return (
        <PublicLayout>
            <Head title={item.title} />

            <article>
                <header className="relative overflow-hidden bg-[#081a35] text-white">
                    <div className="absolute inset-0 opacity-15 [background-image:repeating-linear-gradient(135deg,transparent_0,transparent_20px,#c9a84c_21px,transparent_22px)]" />
                    <div className="relative mx-auto max-w-5xl px-5 py-12 sm:px-8 sm:py-16 lg:px-12 lg:py-20">
                        <Link href={route(page.indexRoute)} className="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] text-gold-light transition hover:text-white">
                            <span aria-hidden="true">←</span> Kembali ke {page.label}
                        </Link>
                        <div className="mt-8 flex flex-wrap items-center gap-3 text-[10px] font-bold uppercase tracking-[0.15em] text-white/50">
                            <span className="rounded-full border border-gold/30 bg-gold/10 px-3 py-1.5 text-gold-light">{page.label}</span>
                            <span>{formatDate(item.date)}</span>
                            {(item.author || item.source || item.leader) && <span className="h-1 w-1 rounded-full bg-gold" />}
                            <span>{item.author || item.source || item.leader}</span>
                        </div>
                        <h1 className="mt-5 max-w-4xl font-serif text-4xl font-semibold leading-[1.12] tracking-[-0.035em] sm:text-5xl lg:text-6xl">{item.title}</h1>
                    </div>
                </header>

                <div className="mx-auto grid max-w-6xl gap-10 px-5 py-10 sm:px-8 sm:py-14 lg:grid-cols-[minmax(0,1fr)_260px] lg:px-12">
                    <div className="min-w-0">
                        {type === 'rilis' && item.image_url && (
                            <img src={item.image_url} alt={item.title} className="mb-9 aspect-[16/9] w-full rounded-2xl object-cover shadow-xl shadow-slate-900/10" />
                        )}

                        {type === 'galeri' && <MediaGallery media={item.media} />}

                        {item.content ? (
                            <div className={`${type === 'galeri' ? 'mt-9' : ''} whitespace-pre-line text-[15px] leading-8 text-slate-600 sm:text-base`}>
                                {item.content}
                            </div>
                        ) : type !== 'galeri' && (
                            <p className="rounded-xl border border-slate-200 bg-white p-6 text-sm leading-7 text-slate-500">Ringkasan teks tidak tersedia. Gunakan tautan sumber atau dokumen untuk membaca informasi selengkapnya.</p>
                        )}

                        {supportingImages?.length > 0 && (
                            <section className="mt-12 border-t border-slate-200 pt-9">
                                <p className="text-[10px] font-bold uppercase tracking-[0.22em] text-gold-dark">Dokumentasi Pendukung</p>
                                <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                    {supportingImages.map((url, index) => (
                                        <img key={url} src={url} alt={`Dokumentasi pendukung ${index + 1}`} className="aspect-[4/3] w-full rounded-xl object-cover" />
                                    ))}
                                </div>
                            </section>
                        )}

                        {type === 'kliping' && (item.file_url || item.external_url) && (
                            <div className="mt-10 flex flex-wrap gap-3 border-t border-slate-200 pt-8">
                                {item.file_url && <a href={item.file_url} target="_blank" rel="noopener noreferrer" className="rounded-xl bg-primary px-5 py-3 text-xs font-bold text-white transition hover:bg-primary-light">Buka Dokumen Kliping</a>}
                                {item.external_url && <a href={item.external_url} target="_blank" rel="noopener noreferrer" className="rounded-xl border border-primary px-5 py-3 text-xs font-bold text-primary transition hover:bg-primary hover:text-white">Kunjungi Sumber Berita</a>}
                            </div>
                        )}
                    </div>

                    <aside className="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-6">
                        <p className="text-[9px] font-bold uppercase tracking-[0.2em] text-gold-dark">Informasi Konten</p>
                        <dl className="mt-5 space-y-5 text-sm">
                            <div>
                                <dt className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tanggal</dt>
                                <dd className="mt-1.5 font-semibold text-slate-700">{formatDate(item.date)}</dd>
                            </div>
                            {(item.author || item.source || item.leader) && (
                                <div>
                                    <dt className="text-[10px] font-bold uppercase tracking-wider text-slate-400">{type === 'rilis' ? 'Penulis' : type === 'kliping' ? 'Media' : 'Pimpinan Terkait'}</dt>
                                    <dd className="mt-1.5 font-semibold text-slate-700">{item.author || item.source || item.leader}</dd>
                                </div>
                            )}
                            {type === 'galeri' && (
                                <div>
                                    <dt className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Koleksi</dt>
                                    <dd className="mt-1.5 font-semibold capitalize text-slate-700">{item.media_count} media · {item.media_type}</dd>
                                </div>
                            )}
                            {type === 'kliping' && item.sentiment && (
                                <div>
                                    <dt className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Sentimen</dt>
                                    <dd className="mt-1.5 font-semibold capitalize text-slate-700">{item.sentiment}</dd>
                                </div>
                            )}
                        </dl>
                    </aside>
                </div>
            </article>

            {related.length > 0 && (
                <section className="border-t border-slate-200 bg-white">
                    <div className="mx-auto max-w-6xl px-5 py-12 sm:px-8 lg:px-12">
                        <div className="flex items-end justify-between gap-4">
                            <div>
                                <p className="text-[9px] font-bold uppercase tracking-[0.24em] text-gold-dark">Informasi Lainnya</p>
                                <h2 className="mt-2 font-serif text-3xl font-semibold text-slate-900">Konten terkait</h2>
                            </div>
                            <Link href={route(page.indexRoute)} className="text-xs font-bold text-primary hover:text-primary-light">Lihat semua →</Link>
                        </div>
                        <div className="mt-7 grid gap-4 md:grid-cols-3">
                            {related.map((entry) => (
                                <Link key={entry.id} href={entry.href} className="group rounded-xl border border-slate-200 p-5 transition hover:border-gold hover:shadow-lg">
                                    <p className="text-[9px] font-bold uppercase tracking-wider text-slate-400">{formatDate(entry.date)}</p>
                                    <h3 className="mt-3 text-sm font-extrabold leading-6 text-slate-800 group-hover:text-primary">{entry.title}</h3>
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </PublicLayout>
    );
}
