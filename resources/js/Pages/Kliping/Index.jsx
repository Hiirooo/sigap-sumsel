import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ kliping, filters = {} }) {
    const [updatingStatus, setUpdatingStatus] = useState(null);
    const [values, setValues] = useState({
        search: filters.search || '',
        sentimen: filters.sentimen || '',
        status: filters.status || '',
        tanggal_mulai: filters.tanggal_mulai || '',
        tanggal_selesai: filters.tanggal_selesai || '',
    });

    const applyFilter = (e) => {
        e.preventDefault();
        router.get(route('kliping.index'), values, {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilter = () => {
        setValues({ search: '', sentimen: '', status: '', tanggal_mulai: '', tanggal_selesai: '' });
        router.get(route('kliping.index'), {}, { preserveState: true, replace: true });
    };

    const sentimenClass = {
        positif: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        netral: 'border-amber-200 bg-amber-50 text-amber-700',
        negatif: 'border-rose-200 bg-rose-50 text-rose-700',
    };
    const statusClass = {
        draft: 'border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-300 hover:bg-amber-100',
        terpublikasi: 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100',
        diarsipkan: 'border-slate-200 bg-slate-100 text-slate-600 hover:border-slate-300 hover:bg-slate-200',
    };

    const toggleStatus = (item) => {
        if (updatingStatus) return;

        setUpdatingStatus(item.id);
        router.patch(route('kliping.toggle-status', item.id), {}, {
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
                                <Link href={route('kliping.create')} className="rounded-lg bg-gold px-4 py-2 font-bold text-primary-dark shadow-md transition-all hover:bg-gold-light">
                                    + Tambah Kliping
                                </Link>
                            </div>

                            <form onSubmit={applyFilter} className="mb-6 grid grid-cols-1 gap-4 rounded-lg border bg-gray-50 p-4 md:grid-cols-6">
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
                                <div className="flex gap-2 md:col-span-6">
                                    <button type="submit" className="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">
                                        Terapkan Filter
                                    </button>
                                    <button type="button" onClick={resetFilter} className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-white">
                                        Reset
                                    </button>
                                </div>
                            </form>

                            <div className="mb-3 flex items-center justify-between">
                                <p className="text-sm text-gray-500">
                                    Menampilkan <strong className="font-semibold text-gray-900">{kliping.length}</strong> kliping
                                </p>
                                <p className="hidden text-xs font-semibold uppercase tracking-wider text-gray-400 sm:block">Klik status untuk mengubah</p>
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
                                        {kliping.length === 0 ? (
                                            <tr>
                                                <td colSpan="7" className="px-6 py-16 text-center">
                                                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-400">-</div>
                                                    <p className="font-semibold text-gray-700">Belum ada kliping media</p>
                                                    <p className="mt-1 text-sm text-gray-400">Tambahkan data baru atau sesuaikan filter pencarian.</p>
                                                </td>
                                            </tr>
                                        ) : (
                                            kliping.map((item) => (
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
                                                        {item.file_url ? (
                                                            <a href={item.file_url} target="_blank" rel="noopener noreferrer" className="inline-flex rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-primary transition-colors hover:border-primary hover:bg-primary/5">
                                                                Buka
                                                            </a>
                                                        ) : <span className="text-xs text-slate-400">Tidak ada</span>}
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-right text-sm font-medium">
                                                        <div className="inline-flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                                                        <Link href={route('kliping.edit', item.id)} className="border-r border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50 hover:text-primary">Edit</Link>
                                                        <Link
                                                            as="button"
                                                            method="delete"
                                                            href={route('kliping.destroy', item.id)}
                                                            onClick={(e) => {
                                                                if (!confirm('Yakin ingin menghapus kliping ini?')) e.preventDefault();
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
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
