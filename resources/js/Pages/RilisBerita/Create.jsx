import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RichTextEditor from '@/Components/RichTextEditor';
import { Head, useForm, Link } from '@inertiajs/react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        judul: '',
        isi: '',
        tanggal_rilis: '',
        penulis: '',
        media_publikasi: '',
        status: 'draft',
        gambar_utama: null,
        gambar_pendukung: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('rilis-berita.store'), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Tambah Rilis Berita
                </h2>
            }
        >
            <Head title="Tambah Rilis Berita" />

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-lg sm:rounded-xl border-t-4 border-gold">
                        <div className="p-6 text-gray-900">
                            <form onSubmit={submit} className="space-y-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Judul Berita</label>
                                    <input type="text" value={data.judul} onChange={e => setData('judul', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                    {errors.judul && <div className="text-red-600 text-sm mt-1">{errors.judul}</div>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Isi Berita</label>
                                    <div className="mt-1">
                                        <RichTextEditor
                                            value={data.isi}
                                            onChange={(content) => setData('isi', content)}
                                            textareaClassName="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        />
                                    </div>
                                    {errors.isi && <div className="text-red-600 text-sm mt-1">{errors.isi}</div>}
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Tanggal Rilis</label>
                                        <input type="date" value={data.tanggal_rilis} onChange={e => setData('tanggal_rilis', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        {errors.tanggal_rilis && <div className="text-red-600 text-sm mt-1">{errors.tanggal_rilis}</div>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Penulis / Wartawan</label>
                                        <input type="text" value={data.penulis} onChange={e => setData('penulis', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        {errors.penulis && <div className="text-red-600 text-sm mt-1">{errors.penulis}</div>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Gambar Utama</label>
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/gif,image/webp"
                                            onChange={(e) => setData('gambar_utama', e.target.files[0] || null)}
                                            className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:font-semibold file:text-white"
                                            required
                                        />
                                        <p className="mt-1 text-xs text-gray-500">Wajib. Maksimal 20 MB.</p>
                                        {errors.gambar_utama && <div className="text-red-600 text-sm mt-1">{errors.gambar_utama}</div>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Gambar Pendukung</label>
                                        <input
                                            type="file"
                                            multiple
                                            accept="image/jpeg,image/png,image/gif,image/webp"
                                            onChange={(e) => setData('gambar_pendukung', Array.from(e.target.files).slice(0, 10))}
                                            className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:font-semibold file:text-white"
                                        />
                                        <p className="mt-1 text-xs text-gray-500">Opsional, maksimal 10 gambar, masing-masing 20 MB.</p>
                                        {data.gambar_pendukung.length > 0 && <p className="mt-1 text-sm text-primary">{data.gambar_pendukung.length} gambar dipilih.</p>}
                                        {errors.gambar_pendukung && <div className="text-red-600 text-sm mt-1">{errors.gambar_pendukung}</div>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Media Publikasi</label>
                                        <input type="text" value={data.media_publikasi} onChange={e => setData('media_publikasi', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Status</label>
                                        <select value={data.status} onChange={e => setData('status', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                            <option value="draft">Draft</option>
                                            <option value="terpublikasi">Terpublikasi</option>
                                            <option value="diarsipkan">Diarsipkan</option>
                                        </select>
                                    </div>
                                </div>

                                <div className="flex items-center justify-end mt-4">
                                    <Link href={route('rilis-berita.index')} className="text-gray-600 hover:text-gray-900 mr-4">
                                        Batal
                                    </Link>
                                    <button disabled={processing} type="submit" className="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg shadow-md transition-all duration-300">
                                        Simpan
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
