import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Form';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        tanggal: new Date().toISOString().slice(0, 10),
        periode: '',
        aspek: 'SOP Pengelolaan Dokumentasi',
        indikator: '',
        target: '',
        realisasi: '',
        skor: 75,
        status: 'perlu_perhatian',
        prioritas: 'sedang',
        catatan: '',
        rekomendasi: '',
        penanggung_jawab: '',
        tenggat_tindak_lanjut: '',
        status_tindak_lanjut: 'belum',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('monev.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-white">Tambah Checklist Monev</h2>}>
            <Head title="Tambah Monev" />
            <div className="min-h-screen bg-slate-100 py-8">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <Form data={data} setData={setData} errors={errors} processing={processing} submit={submit} submitLabel="Simpan Checklist" />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
