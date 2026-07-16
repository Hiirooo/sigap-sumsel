import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { createVideoThumbnail } from '@/Utils/videoThumbnail';
import { useState } from 'react';

export default function Create() {
    const [previews, setPreviews] = useState([]);
    const [generatingThumbnails, setGeneratingThumbnails] = useState(false);
    const [thumbnailErrors, setThumbnailErrors] = useState({});
    const { data, setData, post, processing, errors } = useForm({
        judul: '',
        tanggal: '',
        pimpinan_terkait: '',
        status_verifikasi: 'draft',
        status_digitalisasi: 'belum_didigitalisasi',
        files: [],
        thumbnails: {},
    });

    const handleFiles = async (selectedFiles) => {
        const files = Array.from(selectedFiles).slice(0, 20);
        const thumbnails = {};
        const nextPreviews = files.map((file) => ({
            name: file.name,
            type: file.type.startsWith('video/') ? 'video' : 'foto',
            url: URL.createObjectURL(file),
            thumbnailUrl: '',
        }));

        setGeneratingThumbnails(true);
        setThumbnailErrors({});
        for (let index = 0; index < files.length; index += 1) {
            if (!files[index].type.startsWith('video/')) continue;
            try {
                const thumbnail = await createVideoThumbnail(files[index]);
                thumbnails[index] = thumbnail;
                nextPreviews[index].thumbnailUrl = URL.createObjectURL(thumbnail);
            } catch (error) {
                setThumbnailErrors((current) => ({ ...current, [index]: error.message }));
            }
        }
        setData('files', files);
        setData('thumbnails', thumbnails);
        setPreviews(nextPreviews);
        setGeneratingThumbnails(false);
    };

    const setManualThumbnail = (index, file) => {
        setData('thumbnails', { ...data.thumbnails, [index]: file });
        setPreviews((current) => current.map((preview, itemIndex) => itemIndex === index
            ? { ...preview, thumbnailUrl: file ? URL.createObjectURL(file) : '' }
            : preview));
        setThumbnailErrors((current) => ({ ...current, [index]: '' }));
    };

    const submit = (event) => {
        event.preventDefault();
        post(route('dokumentasi.store'), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-white">Tambah Galeri Kegiatan</h2>}>
            <Head title="Tambah Dokumentasi" />
            <div className="min-h-screen bg-gray-50 py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden border-t-4 border-gold bg-white shadow-lg sm:rounded-xl">
                        <form onSubmit={submit} className="space-y-6 p-6 text-gray-900" encType="multipart/form-data">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Judul Kegiatan</label>
                                <input type="text" value={data.judul} onChange={(e) => setData('judul', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                                {errors.judul && <p className="mt-1 text-sm text-red-600">{errors.judul}</p>}
                            </div>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Tanggal Kegiatan</label>
                                    <input type="date" value={data.tanggal} onChange={(e) => setData('tanggal', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                                    {errors.tanggal && <p className="mt-1 text-sm text-red-600">{errors.tanggal}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Pimpinan Terkait</label>
                                    <input type="text" value={data.pimpinan_terkait} onChange={(e) => setData('pimpinan_terkait', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                                </div>
                            </div>

                            <div className="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-5">
                                <label className="block text-sm font-bold text-slate-800">Foto dan Video Kegiatan</label>
                                <p className="mt-1 text-xs text-slate-500">Pilih maksimal 20 file sekaligus. Maksimal 50 MB per file; thumbnail video dibuat otomatis.</p>
                                <input type="file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime" onChange={(e) => handleFiles(e.target.files)} className="mt-4 block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-primary-dark" />
                                {(errors.files || errors['files.0']) && <p className="mt-2 text-sm text-red-600">{errors.files || errors['files.0']}</p>}
                            </div>

                            {generatingThumbnails && <p className="text-sm font-semibold text-primary">Membuat thumbnail video...</p>}
                            {previews.length > 0 && (
                                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {previews.map((preview, index) => (
                                        <div key={`${preview.name}-${index}`} className="overflow-hidden rounded-xl border bg-white shadow-sm">
                                            <div className="h-40 bg-slate-900">
                                                {preview.type === 'video' ? (
                                                    preview.thumbnailUrl ? <img src={preview.thumbnailUrl} alt={preview.name} className="h-full w-full object-cover" /> : <video src={preview.url} className="h-full w-full object-cover" muted />
                                                ) : <img src={preview.url} alt={preview.name} className="h-full w-full object-cover" />}
                                            </div>
                                            <div className="p-3">
                                                <div className="flex items-center justify-between gap-2">
                                                    <p className="truncate text-sm font-semibold text-slate-800">{preview.name}</p>
                                                    <span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold capitalize text-slate-600">{preview.type}</span>
                                                </div>
                                                {preview.type === 'video' && (
                                                    <div className="mt-3">
                                                        <label className="text-xs font-semibold text-slate-600">Ganti thumbnail</label>
                                                        <input type="file" accept="image/jpeg,image/png,image/webp" onChange={(e) => setManualThumbnail(index, e.target.files[0])} className="mt-1 block w-full text-xs text-slate-500" />
                                                        {(thumbnailErrors[index] || errors[`thumbnails.${index}`]) && <p className="mt-1 text-xs text-red-600">{thumbnailErrors[index] || errors[`thumbnails.${index}`]}</p>}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Status Verifikasi</label>
                                    <select value={data.status_verifikasi} onChange={(e) => setData('status_verifikasi', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                        <option value="draft">Draft</option><option value="terverifikasi">Terverifikasi</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Status Digitalisasi</label>
                                    <select value={data.status_digitalisasi} onChange={(e) => setData('status_digitalisasi', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                        <option value="belum_didigitalisasi">Belum Didigitalisasi</option><option value="sudah_didigitalisasi">Sudah Didigitalisasi</option><option value="sudah_diarsipkan">Sudah Diarsipkan</option>
                                    </select>
                                </div>
                            </div>
                            <div className="flex justify-end gap-4">
                                <Link href={route('dokumentasi.index')} className="px-4 py-2 font-semibold text-gray-600 hover:text-gray-900">Batal</Link>
                                <button disabled={processing || generatingThumbnails} className="rounded-lg bg-primary px-5 py-2 font-bold text-white shadow-md hover:bg-primary-dark disabled:opacity-60">Simpan Galeri</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
