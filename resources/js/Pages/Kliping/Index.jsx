import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

export default function Index({ kliping, filters = {}, statusCounts = {} }) {
    const initialPerPage = String(filters.per_page || '10');
    const presetPageSizes = ['10', '25', '50', '100', '200'];
    const [items, setItems] = useState(kliping.data);
    const [nextPageUrl, setNextPageUrl] = useState(kliping.next_page_url);
    const [total, setTotal] = useState(kliping.total);
    const [counts, setCounts] = useState(statusCounts);
    const [loadingMore, setLoadingMore] = useState(false);
    const loadMoreRef = useRef(null);
    const loadingMoreRequestRef = useRef(false);
    const [updatingStatus, setUpdatingStatus] = useState(null);
    const [showImport, setShowImport] = useState(false);
    const [importText, setImportText] = useState('');
    const [importStatus, setImportStatus] = useState('draft');
    const [importing, setImporting] = useState(false);
    const [importResult, setImportResult] = useState(null);
    const [pageSize, setPageSize] = useState(presetPageSizes.includes(initialPerPage) || initialPerPage === 'all' ? initialPerPage : 'custom');
    const [customPageSize, setCustomPageSize] = useState(presetPageSizes.includes(initialPerPage) || initialPerPage === 'all' ? '' : initialPerPage);
    const [values, setValues] = useState({
        search: filters.search || '',
        sentimen: filters.sentimen || '',
        status: filters.status || '',
        tanggal_mulai: filters.tanggal_mulai || '',
        tanggal_selesai: filters.tanggal_selesai || '',
    });
    const statusTabs = [
        { value: 'draft', label: 'Draft' },
        { value: 'terpublikasi', label: 'Terpublikasi' },
    ];
    const activeStatusLabel = statusTabs.find((tab) => tab.value === values.status)?.label || 'Draft';

    const replaceCollection = (collection) => {
        setItems(collection.data);
        setNextPageUrl(collection.next_page_url);
        setTotal(collection.total);
    };

    const activePerPage = pageSize === 'custom'
        ? String(Math.min(1000, Math.max(1, Number(customPageSize) || 10)))
        : pageSize;

    const reloadCollection = (parameters) => {
        router.get(route('kliping.index'), parameters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: (page) => {
                replaceCollection(page.props.kliping);
                if (page.props.statusCounts) setCounts(page.props.statusCounts);
            },
        });
    };

    const applyFilter = (e) => {
        e.preventDefault();
        reloadCollection({ ...values, per_page: activePerPage });
    };

    const resetFilter = () => {
        const resetValues = { search: '', sentimen: '', status: values.status, tanggal_mulai: '', tanggal_selesai: '' };
        setValues(resetValues);
        reloadCollection({ status: values.status, per_page: activePerPage });
    };

    const changeStatusTable = (status) => {
        const nextValues = { ...values, status };
        setValues(nextValues);
        reloadCollection({ ...nextValues, per_page: activePerPage });
    };

    const changePageSize = (value) => {
        setPageSize(value);
        if (value !== 'custom') {
            reloadCollection({ ...values, per_page: value });
        }
    };

    const applyCustomPageSize = () => {
        const size = String(Math.min(1000, Math.max(1, Number(customPageSize) || 10)));
        setCustomPageSize(size);
        reloadCollection({ ...values, per_page: size });
    };

    const sentimenClass = {
        positif: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        netral: 'border-amber-200 bg-amber-50 text-amber-700',
        negatif: 'border-rose-200 bg-rose-50 text-rose-700',
    };
    const statusClass = {
        draft: 'border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-300 hover:bg-amber-100',
        terpublikasi: 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100',
    };

    const toggleStatus = (item) => {
        if (updatingStatus) return;

        setUpdatingStatus(item.id);
        router.patch(route('kliping.toggle-status', item.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                const nextStatus = item.status === 'draft'
                    ? 'terpublikasi'
                    : 'draft';
                setItems((current) => current.filter((currentItem) => currentItem.id !== item.id));
                setTotal((current) => Math.max(0, current - 1));
                setCounts((current) => ({
                    ...current,
                    [item.status]: Math.max(0, (current[item.status] || 0) - 1),
                    [nextStatus]: (current[nextStatus] || 0) + 1,
                }));
            },
            onFinish: () => setUpdatingStatus(null),
        });
    };

    const deleteItem = (item) => {
        if (!confirm('Yakin ingin menghapus kliping ini?')) return;

        router.delete(route('kliping.destroy', item.id), {
            preserveScroll: true,
            onSuccess: () => {
                setItems((current) => current.filter((currentItem) => currentItem.id !== item.id));
                setTotal((current) => Math.max(0, current - 1));
                setCounts((current) => ({
                    ...current,
                    [item.status]: Math.max(0, (current[item.status] || 0) - 1),
                }));
            },
        });
    };

    const loadMore = () => {
        if (!nextPageUrl || loadingMoreRequestRef.current) return;

        loadingMoreRequestRef.current = true;
        setLoadingMore(true);
        router.get(nextPageUrl, {}, {
            only: ['kliping'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: (page) => {
                const nextCollection = page.props.kliping;
                setItems((current) => {
                    const merged = new Map(current.map((item) => [item.id, item]));
                    nextCollection.data.forEach((item) => merged.set(item.id, item));
                    return Array.from(merged.values());
                });
                setNextPageUrl(nextCollection.next_page_url);
                setTotal(nextCollection.total);
            },
            onFinish: () => {
                loadingMoreRequestRef.current = false;
                setLoadingMore(false);
            },
        });
    };

    useEffect(() => {
        const target = loadMoreRef.current;
        if (!target || !nextPageUrl) return undefined;

        const observer = new IntersectionObserver((entries) => {
            if (entries[0]?.isIntersecting) loadMore();
        }, { rootMargin: '300px 0px' });
        observer.observe(target);

        return () => observer.disconnect();
    }, [nextPageUrl, loadingMore]);

    const formatDate = (date) => {
        if (!date) return '-';

        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(new Date(`${date}T00:00:00`));
    };

    const parseImportUrls = () => Array.from(new Set(
        importText
            .split(/\s+/)
            .map((url) => url.trim())
            .filter((url) => /^https?:\/\//i.test(url))
    ));

    const importUrls = async () => {
        if (importing) return;

        const urls = parseImportUrls();
        if (urls.length === 0) {
            setImportResult({ processed: 0, total: 0, created: 0, duplicate: 0, failed: 0, errors: ['Tidak ada URL HTTP/HTTPS yang valid.'] });
            return;
        }
        if (urls.length > 200) {
            setImportResult({ processed: 0, total: urls.length, created: 0, duplicate: 0, failed: 0, errors: ['Maksimal 200 URL dalam satu proses impor.'] });
            return;
        }

        setImporting(true);
        const summary = { processed: 0, total: urls.length, created: 0, duplicate: 0, failed: 0, errors: [] };
        setImportResult({ ...summary });

        for (const url of urls) {
            try {
                const response = await axios.post(route('kliping.import-url'), { url, status: importStatus });
                if (response.data.status === 'duplicate') {
                    summary.duplicate += 1;
                } else {
                    summary.created += 1;
                }
            } catch (error) {
                summary.failed += 1;
                const message = error.response?.data?.message
                    || Object.values(error.response?.data?.errors || {})[0]?.[0]
                    || 'Gagal membaca artikel.';
                summary.errors.push(`${url}: ${message}`);
            }

            summary.processed += 1;
            setImportResult({ ...summary, errors: [...summary.errors] });
        }

        setImporting(false);
        router.get(route('kliping.index'), { ...values, per_page: activePerPage }, {
            only: ['kliping', 'statusCounts'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: (page) => {
                replaceCollection(page.props.kliping);
                if (page.props.statusCounts) setCounts(page.props.statusCounts);
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Modul Kliping Media
                </h2>
            }
        >
            <Head title="Kliping" />

            <div className="min-h-screen bg-gray-50 py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-lg sm:rounded-xl border-t-4 border-primary">
                        <div className="p-6 text-gray-900">
                            <div className="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                                <div>
                                    <h3 className="text-2xl font-bold text-primary">Daftar Kliping Media</h3>
                                    <p className="mt-1 text-sm text-gray-500">Kelola publikasi dan hasil pemantauan media.</p>
                                </div>
                                <div className="flex flex-wrap gap-3">
                                    <button type="button" onClick={() => setShowImport((current) => !current)} className="rounded-lg border border-primary px-4 py-2 font-bold text-primary shadow-sm transition hover:bg-primary hover:text-white">
                                        Import Banyak Link
                                    </button>
                                    <Link href={route('kliping.create')} className="rounded-lg bg-gold px-4 py-2 font-bold text-primary-dark shadow-md transition-all hover:bg-gold-light">
                                        + Tambah Kliping
                                    </Link>
                                </div>
                            </div>

                            {showImport && (
                                <div className="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-5">
                                    <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 className="font-bold text-primary">Import Link Berita Massal</h4>
                                            <p className="text-sm text-slate-600">Tempel maksimal 200 URL, satu link per baris. Sistem membaca metadata, menganalisis sentimen, dan melewati URL duplikat.</p>
                                        </div>
                                        <span className="text-xs font-semibold text-slate-500">{parseImportUrls().length} URL valid</span>
                                    </div>
                                    <textarea
                                        value={importText}
                                        onChange={(event) => setImportText(event.target.value)}
                                        rows="10"
                                        disabled={importing}
                                        placeholder={'https://media.example/read/123/judul-berita\nhttps://media.example/read/456/judul-lain'}
                                        className="mt-4 block w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm focus:border-primary focus:ring-primary disabled:bg-gray-100"
                                    />
                                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                        <label className="text-sm font-semibold text-gray-700">
                                            Status hasil impor
                                            <select value={importStatus} onChange={(event) => setImportStatus(event.target.value)} disabled={importing} className="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                                                <option value="draft">Draft</option>
                                                <option value="terpublikasi">Terpublikasi</option>
                                            </select>
                                        </label>
                                        <button type="button" onClick={importUrls} disabled={importing || parseImportUrls().length === 0} className="rounded-lg bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-50">
                                            {importing ? `Memproses ${importResult?.processed || 0}/${importResult?.total || 0}...` : 'Mulai Import'}
                                        </button>
                                    </div>

                                    {importResult && (
                                        <div className="mt-4 rounded-lg border border-blue-200 bg-white p-4 text-sm">
                                            <div className="flex flex-wrap gap-x-5 gap-y-2 font-semibold">
                                                <span>Progres: {importResult.processed}/{importResult.total}</span>
                                                <span className="text-emerald-700">Berhasil: {importResult.created}</span>
                                                <span className="text-amber-700">Duplikat: {importResult.duplicate}</span>
                                                <span className="text-red-700">Gagal: {importResult.failed}</span>
                                            </div>
                                            {importResult.total > 0 && (
                                                <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                                                    <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${(importResult.processed / importResult.total) * 100}%` }} />
                                                </div>
                                            )}
                                            {importResult.errors.length > 0 && (
                                                <div className="mt-3 max-h-40 overflow-y-auto rounded-md bg-red-50 p-3 text-xs text-red-700">
                                                    {importResult.errors.map((error, index) => <div key={`${error}-${index}`}>{error}</div>)}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                {statusTabs.map((tab) => {
                                    const active = values.status === tab.value;
                                    return (
                                        <button
                                            key={tab.value}
                                            type="button"
                                            onClick={() => changeStatusTable(tab.value)}
                                            className={`flex items-center justify-between rounded-xl border px-4 py-3 text-left transition ${active ? 'border-primary bg-primary text-white shadow-md' : 'border-gray-200 bg-white text-gray-700 hover:border-primary hover:bg-primary/5'}`}
                                        >
                                            <span className="font-bold">Tabel {tab.label}</span>
                                            <span className={`rounded-full px-2.5 py-1 text-xs font-black ${active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'}`}>
                                                {counts[tab.value] || 0}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>

                            <form onSubmit={applyFilter} className="mb-6 grid grid-cols-1 gap-4 rounded-lg border bg-gray-50 p-4 md:grid-cols-4">
                                <input
                                    type="text"
                                    value={values.search}
                                    onChange={(e) => setValues({ ...values, search: e.target.value })}
                                    placeholder="Cari judul atau media"
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary md:col-span-2"
                                />
                                <select
                                    value={values.sentimen}
                                    onChange={(e) => setValues({ ...values, sentimen: e.target.value })}
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                >
                                    <option value="">Semua Sentimen</option>
                                    <option value="positif">Positif</option>
                                    <option value="netral">Netral</option>
                                    <option value="negatif">Negatif</option>
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
                                <div className="flex gap-2 md:col-span-4">
                                    <button type="submit" className="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">
                                        Terapkan Filter
                                    </button>
                                    <button type="button" onClick={resetFilter} className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-white">
                                        Reset
                                    </button>
                                </div>
                            </form>

                            <div className="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-gray-500">
                                    Tabel <strong className="font-semibold text-gray-900">{activeStatusLabel}</strong>: menampilkan <strong className="font-semibold text-gray-900">{items.length}</strong> dari <strong className="font-semibold text-gray-900">{total}</strong> kliping
                                </p>
                                <div className="flex flex-wrap items-center justify-end gap-2">
                                    <label htmlFor="kliping-page-size" className="text-xs font-semibold uppercase tracking-wider text-gray-500">Tampilkan</label>
                                    <select
                                        id="kliping-page-size"
                                        value={pageSize}
                                        onChange={(event) => changePageSize(event.target.value)}
                                        className="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                    >
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="200">200</option>
                                        <option value="custom">Custom</option>
                                        <option value="all">All</option>
                                    </select>
                                    {pageSize === 'custom' && (
                                        <>
                                            <input
                                                type="number"
                                                min="1"
                                                max="1000"
                                                value={customPageSize}
                                                onChange={(event) => setCustomPageSize(event.target.value)}
                                                onKeyDown={(event) => {
                                                    if (event.key === 'Enter') applyCustomPageSize();
                                                }}
                                                placeholder="1-1000"
                                                className="w-24 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                            />
                                            <button type="button" onClick={applyCustomPageSize} className="rounded-md bg-primary px-3 py-1.5 text-sm font-bold text-white hover:bg-primary-dark">
                                                Terapkan
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>

                            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Informasi Kliping</th>
                                            <th className="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal</th>
                                            <th className="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Sentimen</th>
                                            <th className="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                                            <th className="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Keterkaitan</th>
                                            <th className="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">File</th>
                                            <th className="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 bg-white">
                                        {items.length === 0 ? (
                                            <tr>
                                                <td colSpan="7" className="px-6 py-16 text-center">
                                                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-400">-</div>
                                                    <p className="font-semibold text-gray-700">Belum ada kliping media</p>
                                                    <p className="mt-1 text-sm text-gray-400">Tambahkan data baru atau sesuaikan filter pencarian.</p>
                                                </td>
                                            </tr>
                                        ) : (
                                            items.map((item) => (
                                                <tr key={item.id} className="group transition-colors hover:bg-slate-50/70">
                                                    <td className="max-w-sm px-5 py-4">
                                                        <p className="line-clamp-2 text-sm font-semibold leading-5 text-slate-900">{item.judul}</p>
                                                        <p className="mt-1 text-xs font-medium text-slate-500">{item.media || 'Media tidak dicantumkan'}</p>
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-600">{formatDate(item.tanggal)}</td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-sm">
                                                        <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold capitalize ${sentimenClass[item.sentimen] || sentimenClass.netral}`}>
                                                            {item.sentimen}
                                                        </span>
                                                    </td>
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
                                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                                        {item.persentase_keterkaitan !== null && item.persentase_keterkaitan !== undefined ? (
                                                            <div className="min-w-24">
                                                                <div className="mb-1.5 flex items-center justify-between gap-3 text-xs">
                                                                    <span className="font-semibold capitalize text-slate-700">{item.tingkat_keterkaitan || '-'}</span>
                                                                    <span className="text-slate-400">{item.persentase_keterkaitan}%</span>
                                                                </div>
                                                                <div className="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                                                    <div className="h-full rounded-full bg-primary" style={{ width: `${Math.min(100, Math.max(0, item.persentase_keterkaitan))}%` }} />
                                                                </div>
                                                            </div>
                                                        ) : <span className="text-slate-400">-</span>}
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-center text-sm">
                                                        <div className="flex flex-col items-center gap-1.5">
                                                            {item.file_url && (
                                                                <a href={item.file_url} target="_blank" rel="noopener noreferrer" className="inline-flex rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-primary transition-colors hover:border-primary hover:bg-primary/5">
                                                                    Buka File
                                                                </a>
                                                            )}
                                                            {item.url && (
                                                                <a href={item.url} target="_blank" rel="noopener noreferrer" className="text-[11px] font-semibold text-slate-500 hover:text-primary hover:underline">
                                                                    Buka Berita
                                                                </a>
                                                            )}
                                                            {!item.file_url && !item.url && <span className="text-xs text-slate-400">Tidak ada</span>}
                                                        </div>
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-right text-sm font-medium">
                                                        <div className="inline-flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                                                        <Link href={route('kliping.edit', item.id)} className="border-r border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50 hover:text-primary">Edit</Link>
                                                        <button
                                                            type="button"
                                                            onClick={() => deleteItem(item)}
                                                            className="px-3 py-1.5 text-xs font-semibold text-slate-500 transition-colors hover:bg-rose-50 hover:text-rose-700"
                                                        >
                                                            Hapus
                                                        </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                                </div>
                            </div>

                            <div ref={loadMoreRef} className="mt-5 flex min-h-16 flex-col items-center justify-center gap-2 text-center">
                                {loadingMore ? (
                                    <div className="flex items-center gap-3 text-sm font-semibold text-primary">
                                        <span className="h-5 w-5 animate-spin rounded-full border-2 border-primary/20 border-t-primary" />
                                        Memuat kliping berikutnya...
                                    </div>
                                ) : nextPageUrl ? (
                                    <button type="button" onClick={loadMore} className="rounded-lg border border-primary px-4 py-2 text-sm font-bold text-primary transition hover:bg-primary hover:text-white">
                                        Muat berikutnya
                                    </button>
                                ) : items.length > 0 ? (
                                    <p className="text-sm text-gray-400">Semua {total} kliping sudah ditampilkan.</p>
                                ) : null}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
