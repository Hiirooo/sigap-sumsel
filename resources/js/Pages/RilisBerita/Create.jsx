import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RichTextEditor from '@/Components/RichTextEditor';
import { Head, useForm, Link } from '@inertiajs/react';
import { useState } from 'react';

export default function Create() {
    const [sourceUrl, setSourceUrl] = useState('');
    const [fetchingUrl, setFetchingUrl] = useState(false);
    const [urlMessage, setUrlMessage] = useState({ type: '', text: '' });
    const { data, setData, post, processing, errors } = useForm({
        judul: '',
        isi: '',
        tanggal_rilis: '',
        penulis: '',
        media_publikasi: '',
        status: 'draft',
        gambar_utama: null,
        gambar_pendukung: [],
        sumber_url: '',
        imported_image_urls: [],
    });

    const fetchFromUrl = async () => {
        if (!sourceUrl.trim() || fetchingUrl) return;

        setFetchingUrl(true);
        setUrlMessage({ type: '', text: '' });

        try {
            const response = await axios.post(route('rilis-berita.preview-url'), { url: sourceUrl.trim() });
            const imported = response.data;

            setData((current) => ({
                ...current,
                judul: imported.judul || '',
                isi: imported.isi || '',
                tanggal_rilis: imported.tanggal_rilis || '',
                penulis: imported.penulis || '',
                media_publikasi: imported.media_publikasi || 'sumselprov.go.id',
                sumber_url: imported.sumber_url || sourceUrl.trim(),
                imported_image_urls: imported.image_urls || [],
            }));
            setUrlMessage({
                type: 'success',
                text: `Data berhasil diambil, termasuk ${(imported.image_urls || []).length} gambar. Silakan periksa sebelum menyimpan.`,
            });
        } catch (error) {
            setUrlMessage({
                type: 'error',
                text: error.response?.data?.message || 'Data berita tidak dapat diambil dari tautan tersebut.',
            });
        } finally {
            setFetchingUrl(false);
        }
    };

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
                                <div className="rounded-xl border border-primary/15 bg-primary/5 p-5">
                                    <div className="mb-3">
                                        <h3 className="font-semibold text-primary-dark">Isi otomatis dari tautan berita</h3>
                                        <p className="mt-1 text-sm text-gray-600">Masukkan tautan berita resmi Sumselprov untuk mengambil judul, isi, tanggal, penulis, dan galeri gambar.</p>
                                    </div>
                                    <div className="flex flex-col gap-3 sm:flex-row">
                                        <input
                                            type="url"
                                            value={sourceUrl}
                                            onChange={(event) => setSourceUrl(event.target.value)}
                                            onKeyDown={(event) => {
                                                if (event.key === 'Enter') {
                                                    event.preventDefault();
                                                    fetchFromUrl();
                                                }
                                            }}
                                            placeholder="https://sumselprov.go.id/page/berita/..."
                                            className="block min-w-0 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        />
                                        <button
                                            type="button"
                                            onClick={fetchFromUrl}
                                            disabled={fetchingUrl || !sourceUrl.trim()}
                                            className="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {fetchingUrl ? 'Mengambil data...' : 'Ambil Data'}
                                        </button>
                                    </div>
                                    {urlMessage.text && (
                                        <p className={`mt-3 text-sm ${urlMessage.type === 'success' ? 'text-green-700' : 'text-red-600'}`}>
                                            {urlMessage.text}
                                        </p>
                                    )}
                                </div>

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
                                        />
                                        <p className="mt-1 text-xs text-gray-500">Wajib jika gambar belum diambil dari tautan. Maksimal 20 MB.</p>
                                        {data.imported_image_urls.length > 0 && (
                                            <div className="mt-3 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-3">
                                                <img src={data.imported_image_urls[0]} alt="Pratinjau gambar utama" className="h-16 w-24 rounded-md object-cover" />
                                                <div>
                                                    <p className="text-sm font-medium text-green-800">Gambar utama dari tautan siap digunakan</p>
                                                    <p className="text-xs text-green-700">{data.imported_image_urls.length - 1} gambar pendukung tersedia.</p>
                                                </div>
                                            </div>
                                        )}
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
