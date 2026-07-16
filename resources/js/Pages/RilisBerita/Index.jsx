import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { useRef, useState } from 'react';

export default function Index({ rilisBerita, filters = {} }) {
    const [updatingStatus, setUpdatingStatus] = useState(null);
    const [syncing, setSyncing] = useState(false);
    const [syncProgress, setSyncProgress] = useState(null);
    const [conflict, setConflict] = useState(null);
    const [applyToAll, setApplyToAll] = useState(false);
    const conflictResolver = useRef(null);
    const actionForAll = useRef(null);
    const releases = Array.isArray(rilisBerita) ? rilisBerita : (rilisBerita.data || []);
    const currentPage = rilisBerita.current_page || 1;
    const lastPage = rilisBerita.last_page || 1;
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
        status: filters.status || '',
        tanggal_mulai: filters.tanggal_mulai || '',
        tanggal_selesai: filters.tanggal_selesai || '',
    });

    const applyFilter = (e) => {
        e.preventDefault();
        router.get(route('rilis-berita.index'), values, {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilter = () => {
        setValues({ search: '', status: '', tanggal_mulai: '', tanggal_selesai: '' });
        router.get(route('rilis-berita.index'), {}, { preserveState: true, replace: true });
    };

    const statusClass = {
        draft: 'border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-300 hover:bg-amber-100',
        terpublikasi: 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100',
        diarsipkan: 'border-slate-200 bg-slate-100 text-slate-600 hover:border-slate-300 hover:bg-slate-200',
    };

    const toggleStatus = (item) => {
        if (updatingStatus) return;

        setUpdatingStatus(item.id);
        router.post(route('rilis-berita.toggle-status', item.id), {}, {
            preserveScroll: true,
            onFinish: () => setUpdatingStatus(null),
        });
    };

    const formatDate = (date) => {
        if (!date) return '-';

        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(new Date(`${date}T00:00:00`));
    };

    const syncSumselprov = async () => {
        if (syncing) return;

        const totals = { created: 0, updated: 0, skipped: 0, failed: 0, processed: 0 };
        let page = 1;
        let maxPages = 1;

        actionForAll.current = null;
        setConflict(null);
        setApplyToAll(false);
        setSyncing(true);
        setSyncProgress({
            ...totals,
            page: 1,
            maxPages: 1,
            percent: 0,
            status: 'Menghubungkan ke API Sumselprov...',
            completed: false,
            error: '',
        });

        try {
            do {
                setSyncProgress((current) => ({
                    ...current,
                    page,
                    maxPages,
                    status: `Memproses halaman ${page}${maxPages > 1 ? ` dari ${maxPages}` : ''}...`,
                }));

                const preview = await axios.post(route('rilis-berita.sync-sumselprov'), { mode: 'preview', page });
                maxPages = preview.data.max_pages || 1;
                const items = preview.data.items || [];

                for (let index = 0; index < items.length; index += 1) {
                    const item = items[index];
                    let action = 'import';

                    if (item.duplicate) {
                        action = actionForAll.current || await new Promise((resolve) => {
                            conflictResolver.current = resolve;
                            setConflict({ item, index: totals.processed + 1 });
                            setSyncProgress((current) => ({
                                ...current,
                                page,
                                maxPages,
                                status: 'Menunggu keputusan untuk berita yang sama...',
                            }));
                        });
                    }

                    const response = await axios.post(route('rilis-berita.sync-sumselprov'), {
                        mode: 'resolve',
                        item,
                        action,
                    });
                    totals.created += response.data.created || 0;
                    totals.updated += response.data.updated || 0;
                    totals.skipped += response.data.skipped || 0;
                    totals.failed += response.data.failed || 0;
                    totals.processed += 1;

                    const pageFraction = items.length > 0 ? (index + 1) / items.length : 1;
                    setSyncProgress({
                        ...totals,
                        page,
                        maxPages,
                        percent: Math.round((((page - 1) + pageFraction) / maxPages) * 100),
                        status: `Memproses ${item.judul}`,
                        completed: false,
                        error: '',
                    });
                }

                page += 1;
            } while (page <= maxPages);

            setSyncProgress((current) => ({
                ...current,
                percent: 100,
                status: 'Sinkronisasi Sumselprov selesai.',
                completed: true,
            }));
            router.reload({ only: ['rilisBerita'], preserveScroll: true });
        } catch (error) {
            setSyncProgress((current) => ({
                ...current,
                status: 'Sinkronisasi terhenti.',
                completed: true,
                error: error.response?.data?.message || 'API Sumselprov tidak dapat diakses. Silakan coba kembali.',
            }));
        } finally {
            setSyncing(false);
        }
    };

    const chooseConflictAction = (action) => {
        if (applyToAll) {
            actionForAll.current = action;
        }

        setConflict(null);
        conflictResolver.current?.(action);
        conflictResolver.current = null;
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Modul Rilis Berita
                </h2>
            }
        >
            <Head title="Rilis Berita" />

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-lg sm:rounded-xl border-t-4 border-primary">
                        <div className="p-6 text-gray-900">
                            <div className="flex flex-col justify-between gap-3 mb-6 sm:flex-row sm:items-center">
                                <div>
                                    <h3 className="text-2xl font-bold text-primary">Daftar Rilis Berita</h3>
                                    <p className="mt-1 text-sm text-gray-500">Kelola berita, publikasi, dan sinkronisasi media resmi.</p>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onClick={syncSumselprov}
                                        disabled={syncing}
                                        className="rounded-lg border border-primary px-4 py-2 font-bold text-primary transition-colors hover:bg-primary hover:text-white disabled:cursor-wait disabled:opacity-60"
                                    >
                                        {syncing ? 'Menyinkronkan...' : 'Sinkronkan Sumselprov'}
                                    </button>
                                    <Link href={route('rilis-berita.create')} className="bg-gold hover:bg-gold-light text-primary-dark font-bold py-2 px-4 rounded-lg shadow-md transition-all duration-300">
                                        + Tambah Berita
                                    </Link>
                                </div>
                            </div>

                            <form onSubmit={applyFilter} className="mb-6 grid grid-cols-1 gap-4 rounded-lg border bg-gray-50 p-4 md:grid-cols-5">
                                <input
                                    type="text"
                                    value={values.search}
                                    onChange={(e) => setValues({ ...values, search: e.target.value })}
                                    placeholder="Cari judul, isi, penulis, media"
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary md:col-span-2"
                                />
                                <select
                                    value={values.status}
                                    onChange={(e) => setValues({ ...values, status: e.target.value })}
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="terpublikasi">Terpublikasi</option>
                                    <option value="diarsipkan">Diarsipkan</option>
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
                             
                            <div className="mb-3 flex flex-col gap-1 text-sm text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                                <p>
                                    Menampilkan <strong className="font-semibold text-gray-900">{rilisBerita.from || 0}-{rilisBerita.to || 0}</strong> dari <strong className="font-semibold text-gray-900">{rilisBerita.total ?? releases.length}</strong> rilis
                                </p>
                                <p className="text-xs font-semibold uppercase tracking-wider text-gray-400">Klik status untuk mengubah</p>
                            </div>

                            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Informasi Rilis</th>
                                            <th className="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal</th>
                                            <th className="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                                            <th className="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 bg-white">
                                        {releases.length === 0 ? (
                                            <tr>
                                                <td colSpan="4" className="px-6 py-16 text-center">
                                                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-400">-</div>
                                                    <p className="font-semibold text-gray-700">Belum ada rilis berita</p>
                                                    <p className="mt-1 text-sm text-gray-400">Tambahkan berita baru, sinkronkan Sumselprov, atau sesuaikan filter.</p>
                                                </td>
                                            </tr>
                                        ) : (
                                            releases.map((item) => (
                                                <tr key={item.id} className="group transition-colors hover:bg-slate-50/70">
                                                    <td className="max-w-xl px-5 py-4">
                                                        <p className="line-clamp-2 text-sm font-semibold leading-5 text-slate-900">{item.judul}</p>
                                                        <div className="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                            <span className="font-medium">{item.media_publikasi || 'Media tidak dicantumkan'}</span>
                                                            {item.penulis && (
                                                                <>
                                                                    <span className="h-1 w-1 rounded-full bg-slate-300" />
                                                                    <span>{item.penulis}</span>
                                                                </>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-600">{formatDate(item.tanggal_rilis)}</td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-sm">
                                                        <button
                                                            type="button"
                                                            onClick={() => toggleStatus(item)}
                                                            disabled={updatingStatus === item.id}
                                                            title="Klik untuk mengubah status"
                                                            className={`inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold capitalize transition-all disabled:cursor-wait disabled:opacity-60 ${statusClass[item.status] || statusClass.draft}`}
                                                        >
                                                            <span className="h-1.5 w-1.5 rounded-full bg-current" />
                                                            {item.status}
                                                        </button>
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-right text-sm font-medium">
                                                        <div className="inline-flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                                                        <Link href={route('rilis-berita.edit', item.id)} className="border-r border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50 hover:text-primary">Edit</Link>
                                                        <Link
                                                            as="button"
                                                            method="delete"
                                                            href={route('rilis-berita.destroy', item.id)}
                                                            onClick={(e) => {
                                                                if (!confirm('Yakin ingin menghapus rilis berita ini?')) e.preventDefault();
                                                            }}
                                                            className="px-3 py-1.5 text-xs font-semibold text-slate-500 transition-colors hover:bg-rose-50 hover:text-rose-700"
                                                        >
                                                            Hapus
                                                        </Link>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                                </div>
                            </div>

                            {lastPage > 1 && (
                                <div className="mt-5 flex flex-col items-center justify-between gap-3 border-t border-gray-100 pt-5 sm:flex-row">
                                    <p className="text-sm text-gray-500">Halaman {currentPage} dari {lastPage}</p>
                                    <nav className="flex flex-wrap items-center justify-center gap-1" aria-label="Pagination rilis berita">
                                        <Link
                                            href={rilisBerita.prev_page_url || '#'}
                                            preserveScroll
                                            className={`rounded-lg border px-3 py-2 text-sm font-semibold ${rilisBerita.prev_page_url ? 'border-gray-300 text-gray-700 hover:border-primary hover:text-primary' : 'pointer-events-none border-gray-200 text-gray-300'}`}
                                        >
                                            Sebelumnya
                                        </Link>
                                        {pageNumbers.map((page, index) => (
                                            <span key={page} className="contents">
                                                {index > 0 && pageNumbers[index - 1] !== page - 1 && <span className="px-1 text-gray-400">...</span>}
                                                <Link
                                                    href={`${rilisBerita.path}?${new URLSearchParams({ ...filters, page }).toString()}`}
                                                    preserveScroll
                                                    className={`min-w-10 rounded-lg px-3 py-2 text-center text-sm font-bold ${page === currentPage ? 'bg-primary text-white shadow-sm' : 'border border-gray-300 text-gray-700 hover:border-primary hover:text-primary'}`}
                                                >
                                                    {page}
                                                </Link>
                                            </span>
                                        ))}
                                        <Link
                                            href={rilisBerita.next_page_url || '#'}
                                            preserveScroll
                                            className={`rounded-lg border px-3 py-2 text-sm font-semibold ${rilisBerita.next_page_url ? 'border-gray-300 text-gray-700 hover:border-primary hover:text-primary' : 'pointer-events-none border-gray-200 text-gray-300'}`}
                                        >
                                            Berikutnya
                                        </Link>
                                    </nav>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {syncProgress && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
                    <div className="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                        <div className="bg-primary px-6 py-5 text-white">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">Sinkronisasi API</p>
                                    <h3 className="mt-1 text-xl font-bold">Berita Sumselprov</h3>
                                </div>
                                {!syncing && (
                                    <button type="button" onClick={() => setSyncProgress(null)} className="rounded-full bg-white/10 px-3 py-1.5 text-sm font-semibold hover:bg-white/20">
                                        Tutup
                                    </button>
                                )}
                            </div>
                        </div>

                        <div className="space-y-5 p-6">
                            <div>
                                <div className="mb-2 flex justify-between text-sm font-semibold text-gray-700">
                                    <span>{syncProgress.status}</span>
                                    <span>{syncProgress.percent}%</span>
                                </div>
                                <div className="h-3 overflow-hidden rounded-full bg-gray-200">
                                    <div className={`h-full rounded-full transition-all duration-500 ${syncProgress.error ? 'bg-red-500' : 'bg-gold'}`} style={{ width: `${syncProgress.percent}%` }} />
                                </div>
                                <p className="mt-2 text-xs text-gray-500">
                                    Halaman {syncProgress.page} dari {syncProgress.maxPages} · {syncProgress.processed} data diperiksa
                                </p>
                            </div>

                            {conflict && (
                                <div className="rounded-xl border-2 border-amber-300 bg-amber-50 p-4">
                                    <p className="text-xs font-bold uppercase tracking-wider text-amber-700">Rilis yang sama ditemukan</p>
                                    <h4 className="mt-1 font-bold leading-snug text-gray-900">{conflict.item.judul}</h4>
                                    <p className="mt-1 break-all text-xs text-gray-500">Slug: {conflict.item.slug}</p>

                                    <label className="mt-4 flex items-start gap-2 rounded-lg bg-white p-3 text-sm text-gray-700 shadow-sm">
                                        <input
                                            type="checkbox"
                                            checked={applyToAll}
                                            onChange={(e) => setApplyToAll(e.target.checked)}
                                            className="mt-0.5 rounded border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <span>Terapkan pilihan ini untuk semua rilis yang sama berikutnya.</span>
                                    </label>

                                    <div className="mt-4 grid gap-2 sm:grid-cols-3">
                                        <button type="button" onClick={() => chooseConflictAction('delete_reimport')} className="rounded-lg bg-red-600 px-3 py-2 text-sm font-bold text-white hover:bg-red-700">
                                            Hapus & Impor Ulang
                                        </button>
                                        <button type="button" onClick={() => chooseConflictAction('overwrite')} className="rounded-lg bg-blue-600 px-3 py-2 text-sm font-bold text-white hover:bg-blue-700">
                                            Timpa
                                        </button>
                                        <button type="button" onClick={() => chooseConflictAction('skip')} className="rounded-lg bg-gray-600 px-3 py-2 text-sm font-bold text-white hover:bg-gray-700">
                                            Lewati
                                        </button>
                                    </div>
                                    <div className="mt-3 space-y-1 text-xs text-gray-600">
                                        <p><strong>Hapus & Impor Ulang:</strong> hapus rilis lama beserta seluruh gambarnya, lalu buat data baru.</p>
                                        <p><strong>Timpa:</strong> pertahankan ID dan gambar pendukung, tetapi perbarui isi serta gambar utama dari Sumselprov.</p>
                                        <p><strong>Lewati:</strong> pertahankan data SIGAP tanpa perubahan.</p>
                                    </div>
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div className="rounded-xl bg-green-50 p-3 text-center"><strong className="block text-xl text-green-700">{syncProgress.created}</strong><span className="text-xs text-green-800">Baru</span></div>
                                <div className="rounded-xl bg-blue-50 p-3 text-center"><strong className="block text-xl text-blue-700">{syncProgress.updated}</strong><span className="text-xs text-blue-800">Diperbarui</span></div>
                                <div className="rounded-xl bg-gray-100 p-3 text-center"><strong className="block text-xl text-gray-700">{syncProgress.skipped}</strong><span className="text-xs text-gray-700">Dilewati</span></div>
                                <div className="rounded-xl bg-red-50 p-3 text-center"><strong className="block text-xl text-red-700">{syncProgress.failed}</strong><span className="text-xs text-red-800">Gagal</span></div>
                            </div>

                            {syncProgress.error && <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{syncProgress.error}</div>}
                            {syncing && <p className="text-center text-xs text-gray-500">Jangan tutup atau muat ulang halaman selama proses berlangsung.</p>}
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
