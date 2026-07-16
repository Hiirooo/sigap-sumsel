import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const statusStyle = {
    sesuai: 'bg-green-100 text-green-800 ring-green-200',
    perlu_perhatian: 'bg-yellow-100 text-yellow-800 ring-yellow-200',
    kritis: 'bg-red-100 text-red-800 ring-red-200',
};

const priorityStyle = {
    rendah: 'bg-slate-100 text-slate-700',
    sedang: 'bg-blue-100 text-blue-800',
    tinggi: 'bg-red-100 text-red-800',
};

function Kpi({ label, value, helper }) {
    return (
        <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div className="text-xs font-bold uppercase tracking-[0.2em] text-gray-500">{label}</div>
            <div className="mt-3 text-4xl font-black text-gray-950">{value}</div>
            <div className="mt-2 text-sm text-gray-500">{helper}</div>
        </div>
    );
}

function Donut({ title, data }) {
    const total = data.reduce((sum, item) => sum + item.value, 0);
    let offset = 25;
    const radius = 36;
    const circumference = 2 * Math.PI * radius;

    return (
        <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <h3 className="font-black text-gray-950">{title}</h3>
            <div className="mt-4 flex items-center gap-5">
                <div className="relative h-28 w-28 shrink-0">
                    <svg viewBox="0 0 100 100" className="h-full w-full -rotate-90">
                        <circle cx="50" cy="50" r={radius} fill="none" stroke="#eef2f7" strokeWidth="12" />
                        {data.map((item) => {
                            const length = total > 0 ? (item.value / total) * circumference : 0;
                            const segment = <circle key={item.label} cx="50" cy="50" r={radius} fill="none" stroke={item.color} strokeWidth="12" strokeDasharray={`${length} ${circumference - length}`} strokeDashoffset={-offset} strokeLinecap="round" />;
                            offset += length;
                            return segment;
                        })}
                    </svg>
                    <div className="absolute inset-0 flex items-center justify-center text-2xl font-black text-gray-950">{total}</div>
                </div>
                <div className="flex-1 space-y-2 text-sm">
                    {data.map((item) => (
                        <div key={item.label} className="flex items-center justify-between gap-2">
                            <span className="flex items-center gap-2 text-gray-600"><span className="h-3 w-3 rounded-full" style={{ backgroundColor: item.color }} />{item.label}</span>
                            <span className="font-bold text-gray-950">{item.value}</span>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

export default function Index({ items, summary, filters = {} }) {
    const user = usePage().props.auth.user;
    const canManage = ['admin', 'operator'].includes(user.role);
    const [values, setValues] = useState({
        search: filters.search || '',
        status: filters.status || '',
        prioritas: filters.prioritas || '',
        tanggal_mulai: filters.tanggal_mulai || '',
        tanggal_selesai: filters.tanggal_selesai || '',
    });

    const applyFilter = (e) => {
        e.preventDefault();
        router.get(route('monev.index'), values, { preserveState: true, replace: true });
    };

    const resetFilter = () => {
        const empty = { search: '', status: '', prioritas: '', tanggal_mulai: '', tanggal_selesai: '' };
        setValues(empty);
        router.get(route('monev.index'), {}, { preserveState: true, replace: true });
    };

    const queryString = new URLSearchParams(Object.fromEntries(Object.entries(filters).filter(([, value]) => value))).toString();
    const pdfUrl = `${route('monev.cetak-pdf')}${queryString ? `?${queryString}` : ''}`;

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-white">Monitoring & Evaluasi SIGAP SUMSEL</h2>}>
            <Head title="Monev" />

            <div className="min-h-screen bg-slate-100 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <section className="rounded-3xl bg-gradient-to-br from-primary via-primary-dark to-slate-950 p-8 text-white shadow-xl">
                        <div className="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                            <div>
                                <p className="text-xs font-bold uppercase tracking-[0.28em] text-gold">Monev Command Board</p>
                                <h1 className="mt-3 max-w-3xl text-4xl font-black tracking-tight">Checklist kinerja, risiko, dan tindak lanjut dalam satu panel.</h1>
                                <p className="mt-3 max-w-2xl text-white/75">Pantau indikator SOP, capaian implementasi, rekomendasi perbaikan, dan status penyelesaian tindak lanjut secara terukur.</p>
                            </div>
                            <div className="flex flex-wrap gap-3">
                                <a href={pdfUrl} target="_blank" rel="noopener noreferrer" className="rounded-full bg-white px-5 py-3 text-sm font-black text-primary shadow hover:bg-slate-100">Cetak PDF</a>
                                {canManage && <Link href={route('monev.create')} className="rounded-full bg-gold px-5 py-3 text-sm font-black text-primary-dark shadow hover:bg-yellow-300">+ Checklist Baru</Link>}
                            </div>
                        </div>
                    </section>

                    <section className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                        <Kpi label="Total Checklist" value={summary.total} helper="Item monev sesuai filter" />
                        <Kpi label="Rata-rata Skor" value={`${summary.average_score}%`} helper="Kualitas implementasi" />
                        <Kpi label="Tindak Lanjut" value={`${summary.completion_rate}%`} helper="Persentase selesai" />
                        <Kpi label="Risiko Kritis" value={summary.critical_count} helper={`${summary.high_priority_count} prioritas tinggi`} />
                    </section>

                    <section className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <Donut title="Distribusi Status Monev" data={summary.status} />
                        <Donut title="Progress Tindak Lanjut" data={summary.follow_up} />
                    </section>

                    <section className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <form onSubmit={applyFilter} className="grid grid-cols-1 gap-4 md:grid-cols-6">
                            <input type="text" value={values.search} onChange={(e) => setValues({ ...values, search: e.target.value })} placeholder="Cari aspek, indikator, PIC" className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary md:col-span-2" />
                            <select value={values.status} onChange={(e) => setValues({ ...values, status: e.target.value })} className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                <option value="">Semua Status</option>
                                <option value="sesuai">Sesuai</option>
                                <option value="perlu_perhatian">Perlu Perhatian</option>
                                <option value="kritis">Kritis</option>
                            </select>
                            <select value={values.prioritas} onChange={(e) => setValues({ ...values, prioritas: e.target.value })} className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                <option value="">Semua Prioritas</option>
                                <option value="rendah">Rendah</option>
                                <option value="sedang">Sedang</option>
                                <option value="tinggi">Tinggi</option>
                            </select>
                            <input type="date" value={values.tanggal_mulai} onChange={(e) => setValues({ ...values, tanggal_mulai: e.target.value })} className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                            <input type="date" value={values.tanggal_selesai} onChange={(e) => setValues({ ...values, tanggal_selesai: e.target.value })} className="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                            <div className="flex gap-2 md:col-span-6">
                                <button type="submit" className="rounded-md bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-dark">Terapkan Filter</button>
                                <button type="button" onClick={resetFilter} className="rounded-md border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Reset</button>
                            </div>
                        </form>
                    </section>

                    <section className="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div className="border-b border-gray-100 p-6">
                            <h3 className="text-xl font-black text-gray-950">Register Checklist Monev</h3>
                            <p className="mt-1 text-sm text-gray-500">Daftar indikator, skor capaian, risiko, dan rekomendasi tindak lanjut.</p>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-100">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Indikator</th>
                                        <th className="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Skor</th>
                                        <th className="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Prioritas</th>
                                        <th className="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">PIC / Tenggat</th>
                                        <th className="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Tindak Lanjut</th>
                                        {canManage && <th className="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Aksi</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 bg-white">
                                    {items.length === 0 ? (
                                        <tr><td colSpan={canManage ? 7 : 6} className="px-6 py-12 text-center text-sm italic text-gray-500">Belum ada data monev.</td></tr>
                                    ) : items.map((item) => (
                                        <tr key={item.id} className="hover:bg-slate-50">
                                            <td className="px-6 py-4">
                                                <div className="text-sm font-black text-gray-950">{item.aspek}</div>
                                                <div className="mt-1 text-sm text-gray-600">{item.indikator}</div>
                                                <div className="mt-2 text-xs text-gray-500">Target: {item.target || '-'} · Realisasi: {item.realisasi || '-'}</div>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <div className="text-2xl font-black text-primary">{item.skor}%</div>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4"><span className={`rounded-full px-3 py-1 text-xs font-black ring-1 ${statusStyle[item.status]}`}>{item.status.replace('_', ' ')}</span></td>
                                            <td className="whitespace-nowrap px-6 py-4"><span className={`rounded-full px-3 py-1 text-xs font-black ${priorityStyle[item.prioritas]}`}>{item.prioritas}</span></td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                <div className="font-semibold text-gray-900">{item.penanggung_jawab || '-'}</div>
                                                <div>{item.tenggat_tindak_lanjut || 'Tanpa tenggat'}</div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                <div className="font-bold text-gray-900">{item.status_tindak_lanjut}</div>
                                                <div className="mt-1 max-w-xs truncate">{item.rekomendasi || '-'}</div>
                                            </td>
                                            {canManage && (
                                                <td className="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                                    <Link href={route('monev.edit', item.id)} className="mr-3 text-primary-light hover:underline">Edit</Link>
                                                    <Link as="button" method="delete" href={route('monev.destroy', item.id)} onClick={(e) => { if (!confirm('Yakin ingin menghapus checklist ini?')) e.preventDefault(); }} className="text-primary hover:text-red-900">Hapus</Link>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
