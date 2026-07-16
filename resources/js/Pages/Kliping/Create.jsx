import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import axios from 'axios';
import { useRef, useState } from 'react';

export default function Create() {
    const [detecting, setDetecting] = useState(false);
    const [detectError, setDetectError] = useState('');
    const detectingRef = useRef(false);

    const { data, setData, post, processing, errors } = useForm({
        judul: '',
        media: '',
        tanggal: '',
        url: '',
        gambar_url: '',
        isi_berita: '',
        sentimen: 'netral',
        status: 'draft',
        sentimen_confidence: '',
        sentimen_otomatis: false,
        sentimen_metode: '',
        sentimen_model: '',
        terkait_pimpinan: false,
        persentase_keterkaitan: '',
        tingkat_keterkaitan: '',
        kata_kunci_keterkaitan: '',
        file: null,
    });
    const hasRelevanceScore = data.persentase_keterkaitan !== '' && data.persentase_keterkaitan !== null && data.persentase_keterkaitan !== undefined;
    const relevanceScore = Number(data.persentase_keterkaitan || 0);
    const isIrrelevantDetected = data.sentimen_otomatis && data.isi_berita && (!data.terkait_pimpinan || (hasRelevanceScore && relevanceScore < 50));

    const submit = (e) => {
        e.preventDefault();

        if (isIrrelevantDetected) {
            return;
        }

        post(route('kliping.store'));
    };

    const detectFromUrl = async () => {
        if (detectingRef.current || !data.url) {
            return;
        }

        detectingRef.current = true;
        setDetectError('');
        setDetecting(true);

        try {
            const response = await axios.post(route('kliping.detect-url'), { url: data.url });
            const result = response.data;

            setData({
                ...data,
                judul: result.title || data.judul,
                media: result.media || data.media,
                tanggal: result.published_at || data.tanggal,
                gambar_url: result.image_url || '',
                isi_berita: result.content || data.isi_berita,
                sentimen: result.sentimen || data.sentimen,
                sentimen_confidence: result.confidence || '',
                sentimen_otomatis: true,
                sentimen_metode: result.sentimen_metode || '',
                sentimen_model: result.sentimen_model || '',
                terkait_pimpinan: Boolean(result.terkait_pimpinan),
                persentase_keterkaitan: result.persentase_keterkaitan ?? '',
                tingkat_keterkaitan: result.tingkat_keterkaitan || '',
                kata_kunci_keterkaitan: result.kata_kunci_keterkaitan || '',
            });
        } catch (error) {
            setDetectError(error.response?.data?.message || 'URL berita gagal dibaca. Isi data secara manual.');
        } finally {
            detectingRef.current = false;
            setDetecting(false);
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-white">Tambah Kliping Media</h2>}
        >
            <Head title="Tambah Kliping" />

            <div className="min-h-screen bg-gray-50 py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-lg sm:rounded-xl border-t-4 border-gold">
                        <div className="p-6 text-gray-900">
                            <form onSubmit={submit} className="space-y-6" encType="multipart/form-data">
                                <div className="rounded-lg border border-blue-100 bg-blue-50 p-4">
                                    <label className="block text-sm font-medium text-gray-700">URL Berita Online</label>
                                    <div className="mt-1 flex flex-col gap-3 sm:flex-row">
                                        <input type="url" value={data.url} onChange={(e) => setData('url', e.target.value)} placeholder="https://nama-media.com/berita..." className="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        <button type="button" onClick={detectFromUrl} disabled={detecting || !data.url} className="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white shadow-md transition-all hover:bg-primary-dark disabled:opacity-60">
                                            {detecting ? 'Mendeteksi...' : 'Deteksi dari URL'}
                                        </button>
                                    </div>
                                    {errors.url && <div className="mt-1 text-sm text-red-600">{errors.url}</div>}
                                    {detectError && <div className="mt-2 text-sm text-red-600">{detectError}</div>}
                                    {isIrrelevantDetected && (
                                        <div className="mt-2 rounded-md border border-red-200 bg-red-100 px-3 py-2 text-sm font-semibold text-red-800">
                                            Peringatan: keterkaitan berita hanya {relevanceScore}% ({data.tingkat_keterkaitan || 'rendah'}). Berita tidak cukup terkait Gubernur, Wakil Gubernur, Sekda/Sekretaris Daerah, Pemprov Sumsel, atau kegiatan pimpinan lainnya. Proses simpan dinonaktifkan.
                                        </div>
                                    )}
                                    {data.terkait_pimpinan && data.kata_kunci_keterkaitan && (
                                        <div className="mt-2 rounded-md bg-green-100 px-3 py-2 text-sm text-green-800">
                                            Relevan {relevanceScore}% ({data.tingkat_keterkaitan || 'cukup'}) dengan kata kunci: {data.kata_kunci_keterkaitan}.
                                        </div>
                                    )}
                                    {data.gambar_url && (
                                        <div className="mt-3 overflow-hidden rounded-lg border border-gray-200 bg-white">
                                            <img src={data.gambar_url} alt="Gambar utama berita" className="max-h-64 w-full object-cover" />
                                            <div className="px-3 py-2 text-xs text-gray-600">Gambar utama akan disimpan otomatis sebagai file kliping.</div>
                                        </div>
                                    )}
                                    <p className="mt-2 text-xs text-gray-600">Jika situs tidak bisa dibaca otomatis, isi judul, media, tanggal, dan isi berita secara manual.</p>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Judul Kliping</label>
                                    <input type="text" value={data.judul} onChange={(e) => setData('judul', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                    {errors.judul && <div className="mt-1 text-sm text-red-600">{errors.judul}</div>}
                                </div>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Nama Media</label>
                                        <input type="text" value={data.media} onChange={(e) => setData('media', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        {errors.media && <div className="mt-1 text-sm text-red-600">{errors.media}</div>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Tanggal Terbit</label>
                                        <input type="date" value={data.tanggal} onChange={(e) => setData('tanggal', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        {errors.tanggal && <div className="mt-1 text-sm text-red-600">{errors.tanggal}</div>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Sentimen</label>
                                        <select value={data.sentimen} onChange={(e) => setData('sentimen', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                            <option value="positif">Positif</option>
                                            <option value="netral">Netral</option>
                                            <option value="negatif">Negatif</option>
                                        </select>
                                        {errors.sentimen && <div className="mt-1 text-sm text-red-600">{errors.sentimen}</div>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Status Publikasi</label>
                                        <select value={data.status} onChange={(e) => setData('status', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                            <option value="draft">Draft</option>
                                            <option value="terpublikasi">Terpublikasi</option>
                                            <option value="diarsipkan">Diarsipkan</option>
                                        </select>
                                        {errors.status && <div className="mt-1 text-sm text-red-600">{errors.status}</div>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Upload File Kliping (PDF/JPG/PNG)</label>
                                        <input type="file" onChange={(e) => setData('file', e.target.files[0])} className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary-dark" />
                                        <p className="mt-1 text-xs text-gray-500">Opsional jika gambar utama dari URL berhasil terbaca.</p>
                                        {errors.file && <div className="mt-1 text-sm text-red-600">{errors.file}</div>}
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Isi/Ringkasan Berita</label>
                                    <textarea value={data.isi_berita} onChange={(e) => setData('isi_berita', e.target.value)} rows="7" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                    {errors.isi_berita && <div className="mt-1 text-sm text-red-600">{errors.isi_berita}</div>}
                                    {data.sentimen_confidence && (
                                        <p className="mt-2 text-sm text-gray-600">
                                            Saran otomatis: <strong>{data.sentimen}</strong> dengan keyakinan {data.sentimen_confidence}%
                                            {data.sentimen_metode && ` (${data.sentimen_metode === 'ai' ? 'AI' : 'Rule-based'})`}.
                                            {data.sentimen_model && ` Model: ${data.sentimen_model}.`}
                                        </p>
                                    )}
                                </div>

                                <div className="flex items-center justify-end">
                                    <Link href={route('kliping.index')} className="mr-4 text-gray-600 hover:text-gray-900">Batal</Link>
                                    <button disabled={processing || isIrrelevantDetected} type="submit" className="rounded-lg bg-primary px-4 py-2 font-bold text-white shadow-md transition-all hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-60">
                                        Simpan Kliping
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
