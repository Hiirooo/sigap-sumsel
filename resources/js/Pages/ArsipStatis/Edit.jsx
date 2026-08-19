import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link, usePage } from '@inertiajs/react';

export default function Edit({ arsip, jenisOptions }) {
    const flash = usePage().props.flash || {};
    const initialAnggota = Array.isArray(arsip.anggota) && arsip.anggota.length
        ? arsip.anggota.map((item) => ({ nama: item.nama || '', nip: item.nip || '' }))
        : [{ nama: arsip.nama || '', nip: arsip.nip || '' }];

    const { data, setData, post, processing, errors } = useForm({
        _method: 'put',
        jenis_asli: arsip.jenis_asli || 'cuti',
        kode_klasifikasi_surat: arsip.kode_klasifikasi_surat || '',
        nomor_nota_dinas: arsip.nomor_nota_dinas || '',
        tanggal_asli: arsip.tanggal_asli || '',
        kolektif: Boolean(arsip.kolektif),
        nama: arsip.nama || '',
        nip: arsip.nip || '',
        anggota: initialAnggota,
        perihal: arsip.perihal || '',
        tujuan: arsip.tujuan || '',
        no_surat_cuti: arsip.no_surat_cuti || '',
        file_digital: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('arsip-statis.update', arsip.id), {
            onError: (err) => console.error('VALIDATION ERRORS:', err),
        });
    };

    const toggleKolektif = (checked) => {
        setData('kolektif', checked);
        if (checked && data.anggota.length < 2) {
            setData('anggota', [
                ...data.anggota,
                ...Array.from({ length: 2 - data.anggota.length }, () => ({ nama: '', nip: '' })),
            ]);
        }
    };

    const updateAnggota = (index, field, value) => {
        const next = data.anggota.map((item, i) => (i === index ? { ...item, [field]: value } : item));
        setData('anggota', next);
    };

    const addAnggota = () => {
        setData('anggota', [...data.anggota, { nama: '', nip: '' }]);
    };

    const removeAnggota = (index) => {
        setData('anggota', data.anggota.filter((_, i) => i !== index));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Edit Arsip Kepegawaian
                </h2>
            }
        >
            <Head title="Edit Arsip Kepegawaian" />

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-lg sm:rounded-xl border-t-4 border-primary">
                        <div className="p-6 text-gray-900">
                            {flash.error && (
                                <div className="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">
                                    {flash.error}
                                </div>
                            )}
                            {Object.keys(errors).length > 0 && (
                                <div className="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">
                                    Data gagal diperbarui. Periksa kembali isian yang ditandai merah.
                                </div>
                            )}

                            <form onSubmit={submit} className="space-y-6" encType="multipart/form-data">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Jenis Arsip</label>
                                    <select value={data.jenis_asli} onChange={e => setData('jenis_asli', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                        {Object.entries(jenisOptions).map(([value, label]) => (
                                            <option key={value} value={value}>{label}</option>
                                        ))}
                                    </select>
                                    {errors.jenis_asli && <div className="text-red-600 text-sm mt-1">{errors.jenis_asli}</div>}
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Kode Klasifikasi Surat</label>
                                        <input type="text" value={data.kode_klasifikasi_surat} onChange={e => setData('kode_klasifikasi_surat', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        {errors.kode_klasifikasi_surat && <div className="text-red-600 text-sm mt-1">{errors.kode_klasifikasi_surat}</div>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Nomor Nota Dinas</label>
                                        <input type="text" value={data.nomor_nota_dinas} onChange={e => setData('nomor_nota_dinas', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        {errors.nomor_nota_dinas && <div className="text-red-600 text-sm mt-1">{errors.nomor_nota_dinas}</div>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Tanggal</label>
                                        <input type="date" value={data.tanggal_asli} onChange={e => setData('tanggal_asli', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        {errors.tanggal_asli && <div className="text-red-600 text-sm mt-1">{errors.tanggal_asli}</div>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Perihal</label>
                                        <input type="text" value={data.perihal} onChange={e => setData('perihal', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        {errors.perihal && <div className="text-red-600 text-sm mt-1">{errors.perihal}</div>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Tujuan</label>
                                        <input type="text" value={data.tujuan} onChange={e => setData('tujuan', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        {errors.tujuan && <div className="text-red-600 text-sm mt-1">{errors.tujuan}</div>}
                                    </div>
                                    {data.jenis_asli === 'cuti' && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">No. Surat Cuti</label>
                                            <input type="text" value={data.no_surat_cuti} onChange={e => setData('no_surat_cuti', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                            {errors.no_surat_cuti && <div className="text-red-600 text-sm mt-1">{errors.no_surat_cuti}</div>}
                                        </div>
                                    )}
                                </div>

                                <div className="rounded-lg border border-gray-200 p-4">
                                    <label className="flex items-center gap-3 text-sm font-medium text-gray-700">
                                        <input
                                            type="checkbox"
                                            checked={data.kolektif}
                                            onChange={e => toggleKolektif(e.target.checked)}
                                            className="rounded border-gray-300 text-primary focus:ring-primary"
                                        />
                                        Kolektif (satu nota dinas untuk banyak orang)
                                    </label>
                                    {errors.kolektif && <div className="text-red-600 text-sm mt-1">{errors.kolektif}</div>}

                                    {!data.kolektif ? (
                                        <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">Nama</label>
                                                <input type="text" value={data.nama} onChange={e => setData('nama', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                                {errors.nama && <div className="text-red-600 text-sm mt-1">{errors.nama}</div>}
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">NIP</label>
                                                <input type="text" value={data.nip} onChange={e => setData('nip', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                                {errors.nip && <div className="text-red-600 text-sm mt-1">{errors.nip}</div>}
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="mt-4 space-y-4">
                                            {errors.anggota && <div className="text-red-600 text-sm">{errors.anggota}</div>}
                                            {data.anggota.map((anggota, index) => (
                                                <div key={index} className="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                                    <div className="mb-3 flex items-center justify-between">
                                                        <span className="text-sm font-semibold text-gray-700">Pegawai {index + 1}</span>
                                                        <button
                                                            type="button"
                                                            onClick={() => removeAnggota(index)}
                                                            disabled={data.kolektif ? data.anggota.length <= 2 : data.anggota.length <= 1}
                                                            className="text-sm font-medium text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:text-gray-400"
                                                        >
                                                            Hapus
                                                        </button>
                                                    </div>
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700">Nama</label>
                                                            <input
                                                                type="text"
                                                                value={anggota.nama}
                                                                onChange={e => updateAnggota(index, 'nama', e.target.value)}
                                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                                            />
                                                            {errors[`anggota.${index}.nama`] && <div className="text-red-600 text-sm mt-1">{errors[`anggota.${index}.nama`]}</div>}
                                                        </div>
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700">NIP</label>
                                                            <input
                                                                type="text"
                                                                value={anggota.nip}
                                                                onChange={e => updateAnggota(index, 'nip', e.target.value)}
                                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                                            />
                                                            {errors[`anggota.${index}.nip`] && <div className="text-red-600 text-sm mt-1">{errors[`anggota.${index}.nip`]}</div>}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                            <button
                                                type="button"
                                                onClick={addAnggota}
                                                className="rounded-md border border-primary px-3 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white"
                                            >
                                                + Tambah Pegawai
                                            </button>
                                        </div>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Ganti File (Kosongkan jika tidak diganti)</label>
                                    <input type="file" onChange={e => setData('file_digital', e.target.files[0])} className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-red-700" />
                                    {errors.file_digital && <div className="text-red-600 text-sm mt-1">{errors.file_digital}</div>}
                                </div>

                                <div className="flex items-center justify-end mt-4">
                                    <Link href={route('arsip-statis.index')} className="text-gray-600 hover:text-gray-900 mr-4">
                                        Batal
                                    </Link>
                                    <button disabled={processing} type="submit" className="bg-primary hover:bg-red-800 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-all duration-300">
                                        {processing ? 'Menyimpan...' : 'Perbarui Arsip'}
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