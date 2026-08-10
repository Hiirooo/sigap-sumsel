import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ arsip, filters = {} }) {
    const [values, setValues] = useState({
        search: filters.search || '',
        jenis_asli: filters.jenis_asli || '',
        tanggal_mulai: filters.tanggal_mulai || '',
        tanggal_selesai: filters.tanggal_selesai || '',
    });

    const applyFilter = (e) => {
        e.preventDefault();
        router.get(route('arsip-statis.index'), values, {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilter = () => {
        setValues({ search: '', jenis_asli: '', tanggal_mulai: '', tanggal_selesai: '' });
        router.get(route('arsip-statis.index'), {}, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Modul Arsip Kepegawaian
                </h2>
            }
        >
            <Head title="Arsip Kepegawaian" />

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-lg sm:rounded-xl border-t-4 border-primary">
                        <div className="p-6 text-gray-900">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-2xl font-bold text-primary">Daftar Arsip Kepegawaian</h3>
                                <Link href={route('arsip-statis.create')} className="bg-primary hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-all duration-300">
                                    + Tambah Arsip Baru
                                </Link>
                            </div>

                            <form onSubmit={applyFilter} className="mb-6 grid grid-cols-1 gap-4 rounded-lg border bg-gray-50 p-4 md:grid-cols-5">
                                <input
                                    type="text"
                                    value={values.search}
                                    onChange={(e) => setValues({ ...values, search: e.target.value })}
                                    placeholder="Cari judul, deskripsi, asal dokumen"
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary md:col-span-2"
                                />
                                <select
                                    value={values.jenis_asli}
                                    onChange={(e) => setValues({ ...values, jenis_asli: e.target.value })}
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                >
                                    <option value="">Semua Jenis</option>
                                    <option value="fisik">Fisik</option>
                                    <option value="cetak">Cetak</option>
                                    <option value="cd">CD</option>
                                    <option value="lainnya">Lainnya</option>
                                    <option value="rilis">Rilis Berita</option>
                                    <option value="kliping">Kliping</option>
                                    <option value="dokumentasi">Dokumentasi</option>
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
                             
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-primary text-white">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Judul / Deskripsi</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Tanggal Asli</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Asal Dokumen</th>
                                             <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Jenis / Modul</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {arsip.length === 0 ? (
                                            <tr>
                                                <td colSpan="5" className="px-6 py-12 text-center text-gray-500 italic">
                                                    Belum ada data arsip kepegawaian terdaftar.
                                                </td>
                                            </tr>
                                        ) : (
                                            arsip.map((item) => (
                                                <tr key={item.key} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                                        <div>{item.judul}</div>
                                                        <div className="mt-1 text-xs font-normal text-gray-500">{item.deskripsi || '-'}</div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{item.tanggal_asli || '-'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{item.asal_dokumen || '-'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                            {item.jenis_asli?.toUpperCase() || '-'}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        {item.file_url && <a href={item.file_url} target="_blank" rel="noopener noreferrer" className="mr-3 text-primary-light hover:text-blue-900">Buka</a>}
                                                        <Link href={item.edit_url} className="text-primary-light hover:text-blue-900 mr-3">Edit</Link>
                                                        <Link
                                                            as="button"
                                                            method="delete"
                                                            href={item.delete_url}
                                                            onClick={(e) => {
                                                                if(!confirm('Yakin ingin menghapus arsip ini?')) e.preventDefault();
                                                            }}
                                                            className="text-primary hover:text-red-900"
                                                        >
                                                            Hapus
                                                        </Link>
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
        </AuthenticatedLayout>
    );
}
