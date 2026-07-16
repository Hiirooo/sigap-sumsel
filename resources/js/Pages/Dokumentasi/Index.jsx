import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ dokumentasi, filters = {} }) {
    const [updatingStatus, setUpdatingStatus] = useState(null);
    const items = Array.isArray(dokumentasi) ? dokumentasi : (dokumentasi.data || []);
    const currentPage = dokumentasi.current_page || 1;
    const lastPage = dokumentasi.last_page || 1;
    const pageNumbers = Array.from(new Set([
        1,
        lastPage,
        currentPage - 2,
        currentPage - 1,
        currentPage,
        currentPage + 1,
        currentPage + 2,
    ]))
        .filter((page) => page >= 1 && page <= lastPage)
        .sort((a, b) => a - b);
    const [values, setValues] = useState({
        search: filters.search || '',
        jenis_media: filters.jenis_media || '',
        status_verifikasi: filters.status_verifikasi || '',
        tanggal_mulai: filters.tanggal_mulai || '',
        tanggal_selesai: filters.tanggal_selesai || '',
    });

    const applyFilter = (e) => {
        e.preventDefault();
        router.get(route('dokumentasi.index'), values, {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilter = () => {
        setValues({ search: '', jenis_media: '', status_verifikasi: '', tanggal_mulai: '', tanggal_selesai: '' });
        router.get(route('dokumentasi.index'), {}, { preserveState: true, replace: true });
    };

    const goToPage = (page) => {
        router.get(route('dokumentasi.index'), { ...filters, page }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const statusClass = {
        draft: 'border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-300 hover:bg-amber-100',
        terverifikasi: 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100',
    };

    const toggleStatus = (item) => {
        if (updatingStatus) return;

        setUpdatingStatus(item.id);
        router.post(route('dokumentasi.toggle-status', item.id), {}, {
            preserveScroll: true,
            onFinish: () => setUpdatingStatus(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Modul Galeri Dokumentasi (Foto & Video)
                </h2>
            }
        >
            <Head title="Dokumentasi" />

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-lg sm:rounded-xl border-t-4 border-primary">
                        <div className="p-6 text-gray-900">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-2xl font-bold text-primary">Inventaris Dokumentasi</h3>
                                <Link href={route('dokumentasi.create')} className="bg-gold hover:bg-gold-light text-primary-dark font-bold py-2 px-4 rounded-lg shadow-md transition-all duration-300">
                                    + Upload Media
                                </Link>
                            </div>

                            <form onSubmit={applyFilter} className="mb-6 grid grid-cols-1 gap-4 rounded-lg border bg-gray-50 p-4 md:grid-cols-5">
                                <input
                                    type="text"
                                    value={values.search}
                                    onChange={(e) => setValues({ ...values, search: e.target.value })}
                                    placeholder="Cari judul atau pimpinan"
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary md:col-span-2"
                                />
                                <select
                                    value={values.jenis_media}
                                    onChange={(e) => setValues({ ...values, jenis_media: e.target.value })}
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                >
                                    <option value="">Semua Media</option>
                                    <option value="foto">Foto</option>
                                    <option value="video">Video</option>
                                </select>
                                <select
                                    value={values.status_verifikasi}
                                    onChange={(e) => setValues({ ...values, status_verifikasi: e.target.value })}
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="terverifikasi">Terverifikasi</option>
                                </select>
                                <input
                                    type="date"
                                    value={values.tanggal_mulai}
                                    onChange={(e) => setValues({ ...values, tanggal_mulai: e.target.value })}
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                />
                                <input
                                    type="date"
                                    value={values.tanggal_selesai}
                                    onChange={(e) => setValues({ ...values, tanggal_selesai: e.target.value })}
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                />
                                <div className="flex gap-2 md:col-span-5">
                                    <button type="submit" className="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">
                                        Terapkan Filter
                                    </button>
                                    <button type="button" onClick={resetFilter} className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-white">
                                        Reset
                                    </button>
                                </div>
                            </form>
                             
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {items.length === 0 ? (
                                    <div className="col-span-3 text-center py-12 text-gray-500 italic">
                                        Belum ada dokumentasi terunggah.
                                    </div>
                                ) : (
                                    items.map((item) => (
                                        <div key={item.id} className="bg-white border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                            <div className="h-48 bg-gray-900 flex items-center justify-center overflow-hidden">
                                            {item.media_items?.[0]?.jenis_media === 'foto' ? (
                                                <img src={item.media_items[0].file_url} alt={item.judul} className="h-full w-full object-cover" />
                                            ) : item.media_items?.[0]?.jenis_media === 'video' && item.media_items[0].thumbnail_url ? (
                                                <div className="relative h-full w-full">
                                                    <img src={item.media_items[0].thumbnail_url} alt={`Thumbnail ${item.judul}`} className="h-full w-full object-cover" />
                                                    <span className="absolute inset-0 flex items-center justify-center bg-black/20 text-4xl text-white">▶</span>
                                                </div>
                                            ) : (
                                                <span className="text-gray-400 text-sm">File tidak tersedia</span>
                                            )}
                                        </div>
                                            <div className="p-4">
                                                <div className="flex justify-between items-start">
                                                    <h4 className="font-semibold text-lg mb-2">{item.judul}</h4>
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleStatus(item)}
                                                        disabled={updatingStatus === item.id}
                                                        title="Klik untuk mengubah status"
                                                        className={`inline-flex shrink-0 items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold transition-all disabled:cursor-wait disabled:opacity-60 ${statusClass[item.status_verifikasi] || statusClass.draft}`}
                                                    >
                                                        <span className="h-1.5 w-1.5 rounded-full bg-current" />
                                                        {item.status_verifikasi}
                                                    </button>
                                                </div>
                                                <div className="text-sm text-gray-500 mb-2">📅 {item.tanggal}</div>
                                                <div className="text-sm text-gray-600">Pimpinan: {item.pimpinan_terkait || '-'}</div>
                                                <div className="mt-4 flex justify-between items-center">
                                                    <div>
                                                        <Link href={route('dokumentasi.edit', item.id)} className="text-primary-light hover:underline text-sm font-semibold mr-3">Edit</Link>
                                                        <Link 
                                                            as="button" 
                                                            method="delete" 
                                                            href={route('dokumentasi.destroy', item.id)} 
                                                            onClick={(e) => {
                                                                if(!confirm('Apakah Anda yakin ingin menghapus dokumentasi ini beserta filenya?')) e.preventDefault();
                                                            }}
                                                            className="text-primary hover:underline text-sm font-semibold"
                                                        >
                                                            Hapus
                                                        </Link>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="rounded bg-primary/10 px-2 py-1 text-xs font-bold text-primary">{item.media_count} media</span>
                                                        <span className="text-xs text-gray-400 border px-2 rounded bg-gray-50">{item.status_digitalisasi.replaceAll('_', ' ')}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            {dokumentasi.total > 0 && (
                                <div className="mt-8 flex flex-col items-center justify-between gap-4 border-t border-gray-200 pt-6 sm:flex-row">
                                    <p className="text-sm text-gray-500">
                                        Menampilkan <span className="font-semibold text-gray-700">{dokumentasi.from}-{dokumentasi.to}</span> dari <span className="font-semibold text-gray-700">{dokumentasi.total}</span> dokumentasi
                                    </p>

                                    {lastPage > 1 && (
                                        <nav className="flex items-center gap-1" aria-label="Pagination dokumentasi">
                                            <button
                                                type="button"
                                                onClick={() => goToPage(currentPage - 1)}
                                                disabled={currentPage === 1}
                                                className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                Sebelumnya
                                            </button>

                                            {pageNumbers.map((page, index) => (
                                                <div key={page} className="flex items-center gap-1">
                                                    {index > 0 && page - pageNumbers[index - 1] > 1 && (
                                                        <span className="px-1 text-gray-400">...</span>
                                                    )}
                                                    <button
                                                        type="button"
                                                        onClick={() => goToPage(page)}
                                                        aria-current={page === currentPage ? 'page' : undefined}
                                                        className={`h-10 min-w-10 rounded-lg px-3 text-sm font-bold transition ${page === currentPage ? 'bg-primary text-white shadow-sm' : 'border border-gray-300 text-gray-600 hover:border-primary hover:text-primary'}`}
                                                    >
                                                        {page}
                                                    </button>
                                                </div>
                                            ))}

                                            <button
                                                type="button"
                                                onClick={() => goToPage(currentPage + 1)}
                                                disabled={currentPage === lastPage}
                                                className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                Berikutnya
                                            </button>
                                        </nav>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
