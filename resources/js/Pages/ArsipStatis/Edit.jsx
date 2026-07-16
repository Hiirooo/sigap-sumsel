import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';

export default function Edit({ arsip }) {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'put',
        judul: arsip.judul || '',
        deskripsi: arsip.deskripsi || '',
        asal_dokumen: arsip.asal_dokumen || '',
        tanggal_asli: arsip.tanggal_asli || '',
        jenis_asli: arsip.jenis_asli || 'fisik',
        file_digital: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('arsip-statis.update', arsip.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Edit Arsip Statis
                </h2>
            }
        >
            <Head title="Edit Arsip Statis" />

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-lg sm:rounded-xl border-t-4 border-primary">
                        <div className="p-6 text-gray-900">
                            <form onSubmit={submit} className="space-y-6" encType="multipart/form-data">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Judul Arsip</label>
                                    <input type="text" value={data.judul} onChange={e => setData('judul', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                    {errors.judul && <div className="text-red-600 text-sm mt-1">{errors.judul}</div>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Deskripsi</label>
                                    <textarea value={data.deskripsi} onChange={e => setData('deskripsi', e.target.value)} rows="3" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                    {errors.deskripsi && <div className="text-red-600 text-sm mt-1">{errors.deskripsi}</div>}
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Asal Dokumen</label>
                                        <input type="text" value={data.asal_dokumen} onChange={e => setData('asal_dokumen', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        {errors.asal_dokumen && <div className="text-red-600 text-sm mt-1">{errors.asal_dokumen}</div>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Tanggal Asli</label>
                                        <input type="date" value={data.tanggal_asli} onChange={e => setData('tanggal_asli', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        {errors.tanggal_asli && <div className="text-red-600 text-sm mt-1">{errors.tanggal_asli}</div>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Jenis Asli</label>
                                        <select value={data.jenis_asli} onChange={e => setData('jenis_asli', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                            <option value="fisik">Fisik</option>
                                            <option value="cetak">Cetak</option>
                                            <option value="cd">CD</option>
                                            <option value="lainnya">Lainnya</option>
                                        </select>
                                        {errors.jenis_asli && <div className="text-red-600 text-sm mt-1">{errors.jenis_asli}</div>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Ganti File (Kosongkan jika tidak diganti)</label>
                                        <input type="file" onChange={e => setData('file_digital', e.target.files[0])} className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-red-700" />
                                        {errors.file_digital && <div className="text-red-600 text-sm mt-1">{errors.file_digital}</div>}
                                    </div>
                                </div>

                                <div className="flex items-center justify-end mt-4">
                                    <Link href={route('arsip-statis.index')} className="text-gray-600 hover:text-gray-900 mr-4">
                                        Batal
                                    </Link>
                                    <button disabled={processing} type="submit" className="bg-primary hover:bg-red-800 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-all duration-300">
                                        Perbarui Arsip
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
