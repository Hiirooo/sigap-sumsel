import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RichTextEditor from '@/Components/RichTextEditor';
import { Head, useForm, Link } from '@inertiajs/react';

export default function Edit({ rilisBerita }) {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'put',
        judul: rilisBerita.judul || '',
        isi: rilisBerita.isi || '',
        tanggal_rilis: rilisBerita.tanggal_rilis || '',
        penulis: rilisBerita.penulis || '',
        media_publikasi: rilisBerita.media_publikasi || '',
        status: rilisBerita.status || 'draft',
        gambar_utama: null,
        gambar_pendukung: [],
        hapus_gambar_utama: false,
        hapus_gambar_pendukung: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('rilis-berita.update', rilisBerita.id), { forceFormData: true });
    };

    const toggleSupportingImage = (index) => {
        const selected = data.hapus_gambar_pendukung.includes(index);
        setData('hapus_gambar_pendukung', selected
            ? data.hapus_gambar_pendukung.filter((item) => item !== index)
            : [...data.hapus_gambar_pendukung, index]);
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Edit Rilis Berita
                </h2>
            }
        >
            <Head title="Edit Rilis Berita" />

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

                                <div className="space-y-5 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Gambar Utama</label>
                                        {rilisBerita.gambar_url && (
                                            <div className="mt-2 flex items-start gap-4">
                                                <img src={rilisBerita.gambar_url} alt="Gambar utama" className="h-28 w-44 rounded-lg object-cover" />
                                                <label className="flex items-center gap-2 text-sm text-red-700">
                                                    <input type="checkbox" checked={data.hapus_gambar_utama} onChange={(e) => setData('hapus_gambar_utama', e.target.checked)} />
                                                    Hapus gambar utama lama
                                                </label>
                                            </div>
                                        )}
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/gif,image/webp"
                                            onChange={(e) => setData('gambar_utama', e.target.files[0] || null)}
                                            className="mt-3 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:font-semibold file:text-white"
                                        />
                                        <p className="mt-1 text-xs text-gray-500">Pilih file untuk mengganti gambar utama. Maksimal 20 MB.</p>
                                        {errors.gambar_utama && <div className="text-red-600 text-sm mt-1">{errors.gambar_utama}</div>}
                                    </div>

                                    {rilisBerita.gambar_pendukung_urls?.length > 0 && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Gambar Pendukung Saat Ini</label>
                                            <div className="mt-2 grid grid-cols-2 gap-3 md:grid-cols-4">
                                                {rilisBerita.gambar_pendukung_urls.map((url, index) => (
                                                    <label key={url} className={`relative overflow-hidden rounded-lg border-2 ${data.hapus_gambar_pendukung.includes(index) ? 'border-red-500 opacity-50' : 'border-transparent'}`}>
                                                        <img src={url} alt={`Pendukung ${index + 1}`} className="h-28 w-full object-cover" />
                                                        <span className="absolute bottom-0 left-0 right-0 bg-black/65 px-2 py-1 text-xs text-white">
                                                            <input type="checkbox" checked={data.hapus_gambar_pendukung.includes(index)} onChange={() => toggleSupportingImage(index)} className="mr-1" /> Hapus
                                                        </span>
                                                    </label>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Tambah Gambar Pendukung</label>
                                        <input
                                            type="file"
                                            multiple
                                            accept="image/jpeg,image/png,image/gif,image/webp"
                                            onChange={(e) => setData('gambar_pendukung', Array.from(e.target.files).slice(0, 10))}
                                            className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:font-semibold file:text-white"
                                        />
                                        <p className="mt-1 text-xs text-gray-500">Total gambar pendukung setelah perubahan maksimal 10.</p>
                                        {data.gambar_pendukung.length > 0 && <p className="mt-1 text-sm text-primary">{data.gambar_pendukung.length} gambar baru dipilih.</p>}
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
                                        Perbarui
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
