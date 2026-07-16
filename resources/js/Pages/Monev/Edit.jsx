import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Form';

export default function Edit({ item }) {
    const { data, setData, put, processing, errors } = useForm({
        tanggal: item.tanggal || '',
        periode: item.periode || '',
        aspek: item.aspek || 'SOP Pengelolaan Dokumentasi',
        indikator: item.indikator || '',
        target: item.target || '',
        realisasi: item.realisasi || '',
        skor: item.skor || 0,
        status: item.status || 'perlu_perhatian',
        prioritas: item.prioritas || 'sedang',
        catatan: item.catatan || '',
        rekomendasi: item.rekomendasi || '',
        penanggung_jawab: item.penanggung_jawab || '',
        tenggat_tindak_lanjut: item.tenggat_tindak_lanjut || '',
        status_tindak_lanjut: item.status_tindak_lanjut || 'belum',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('monev.update', item.id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-white">Edit Checklist Monev</h2>}>
            <Head title="Edit Monev" />
            <div className="min-h-screen bg-slate-100 py-8">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <Form data={data} setData={setData} errors={errors} processing={processing} submit={submit} submitLabel="Perbarui Checklist" />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
