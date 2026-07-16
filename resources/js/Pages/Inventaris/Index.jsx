import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ stats, items = [], filters = {} }) {
    const [values, setValues] = useState({
        search: filters.search || '',
        jenis_dokumen: filters.jenis_dokumen || '',
        tanggal_mulai: filters.tanggal_mulai || '',
        tanggal_selesai: filters.tanggal_selesai || '',
    });

    const applyFilter = (e) => {
        e.preventDefault();
        router.get(route('inventaris.index'), values, {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilter = () => {
        setValues({ search: '', jenis_dokumen: '', tanggal_mulai: '', tanggal_selesai: '' });
        router.get(route('inventaris.index'), {}, { preserveState: true, replace: true });
    };

    const queryString = new URLSearchParams(Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value)
    )).toString();
    const pdfUrl = `${route('inventaris.cetak-pdf')}${queryString ? `?${queryString}` : ''}`;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Modul Daftar Inventaris & Laporan Rekapitulasi
                </h2>
            }
        >
            <Head title="Inventaris & Laporan" />

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div className="bg-gray-50 rounded-lg p-6 flex items-center border-l-4 border-primary shadow-sm hover:shadow-md transition">
                            <div className="rounded-full bg-primary bg-opacity-10 p-3">
                                <svg className="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                            </div>
                            <div className="ml-4">
                                <div className="text-sm text-gray-500">Rilis Berita</div>
                                <div className="text-2xl font-bold text-gray-900">{stats.rilis_berita || 0}</div>
                            </div>
                        </div>
                        
                        <div className="bg-gray-50 rounded-lg p-6 flex items-center border-l-4 border-primary shadow-sm hover:shadow-md transition">
                            <div className="rounded-full bg-primary bg-opacity-10 p-3">
                                <svg className="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            </div>
                            <div className="ml-4">
                                <div className="text-sm text-gray-500">Galeri Dokumentasi</div>
                                <div className="text-2xl font-bold text-gray-900">{stats.dokumentasi || 0}</div>
                            </div>
                        </div>
                        
                        <div className="bg-gray-50 rounded-lg p-6 flex items-center border-l-4 border-primary shadow-sm hover:shadow-md transition">
                            <div className="rounded-full bg-primary bg-opacity-10 p-3">
                                <svg className="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div className="ml-4">
                                <div className="text-sm text-gray-500">Arsip Statis</div>
                                <div className="text-2xl font-bold text-gray-900">{stats.arsip_statis || 0}</div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow-lg sm:rounded-xl border-t-4 border-primary">
                        <div className="p-6 text-gray-900">
                            <div className="mb-6">
                                <h3 className="text-2xl font-bold text-primary">Daftar Inventaris Dokumentasi</h3>
                            </div>

                            <form onSubmit={applyFilter} className="mb-6 grid grid-cols-1 gap-4 rounded-lg border bg-gray-50 p-4 md:grid-cols-5">
                                <input
                                    type="text"
                                    value={values.search}
                                    onChange={(e) => setValues({ ...values, search: e.target.value })}
                                    placeholder="Cari judul dokumen"
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary md:col-span-2"
                                />
                                <select
                                    value={values.jenis_dokumen}
                                    onChange={(e) => setValues({ ...values, jenis_dokumen: e.target.value })}
                                    className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                >
                                    <option value="">Semua Dokumen</option>
                                    <option value="rilis_berita">Rilis Berita</option>
                                    <option value="dokumentasi">Dokumentasi</option>
                                    <option value="arsip_statis">Arsip Statis</option>
                                    <option value="kliping">Kliping</option>
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

                            <div className="overflow-x-auto rounded-lg border">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-primary text-white">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Jenis</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Judul</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Tanggal</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Sumber/Pimpinan</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {items.length === 0 ? (
                                            <tr>
                                                <td colSpan="5" className="px-6 py-12 text-center text-gray-500 italic">
                                                    Tidak ada data inventaris sesuai filter.
                                                </td>
                                            </tr>
                                        ) : (
                                            items.map((item, index) => (
                                                <tr key={`${item.jenis}-${item.judul}-${index}`} className="hover:bg-gray-50">
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-semibold text-primary">{item.jenis}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{item.judul}</td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{item.tanggal || '-'}</td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{item.status || '-'}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-500">{item.sumber || '-'}</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="mt-10 border-t pt-8">
                                <h4 className="text-lg font-semibold text-primary mb-4">Cetak Laporan</h4>
                                <p className="text-gray-600 mb-6">Anda dapat mencetak laporan rekapitulasi data sesuai filter aktif ke dalam format dokumen PDF.</p>
                                
                                <a href={pdfUrl} target="_blank" rel="noopener noreferrer" className="inline-flex items-center px-6 py-3 bg-primary hover:bg-primary-light text-white font-semibold rounded-lg shadow-md transition">
                                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    Cetak PDF Laporan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
