import { Link } from '@inertiajs/react';

export default function Form({ data, setData, errors, processing, submit, submitLabel }) {
    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 className="text-lg font-black text-gray-950">Identitas Checklist</h3>
                <div className="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label className="block text-sm font-bold text-gray-700">Tanggal Monev</label>
                        <input type="date" value={data.tanggal} onChange={(e) => setData('tanggal', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                        {errors.tanggal && <div className="mt-1 text-sm text-red-600">{errors.tanggal}</div>}
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-gray-700">Periode</label>
                        <input type="text" value={data.periode} onChange={(e) => setData('periode', e.target.value)} placeholder="Contoh: Agustus 2026" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-gray-700">Aspek</label>
                        <select value={data.aspek} onChange={(e) => setData('aspek', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                            <option value="SOP Pengelolaan Dokumentasi">SOP Pengelolaan Dokumentasi</option>
                            <option value="Input Data dan Metadata">Input Data dan Metadata</option>
                            <option value="Pencarian dan Retrieval">Pencarian dan Retrieval</option>
                            <option value="Keamanan dan Backup">Keamanan dan Backup</option>
                            <option value="Kualitas Layanan Informasi">Kualitas Layanan Informasi</option>
                            <option value="Tindak Lanjut Stakeholder">Tindak Lanjut Stakeholder</option>
                        </select>
                        {errors.aspek && <div className="mt-1 text-sm text-red-600">{errors.aspek}</div>}
                    </div>
                </div>
            </div>

            <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 className="text-lg font-black text-gray-950">Indikator dan Capaian</h3>
                <div className="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <label className="block text-sm font-bold text-gray-700">Indikator</label>
                        <input type="text" value={data.indikator} onChange={(e) => setData('indikator', e.target.value)} placeholder="Contoh: Dokumen kegiatan terinput maksimal H+1" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                        {errors.indikator && <div className="mt-1 text-sm text-red-600">{errors.indikator}</div>}
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-gray-700">Target</label>
                        <input type="text" value={data.target} onChange={(e) => setData('target', e.target.value)} placeholder="Contoh: 100% dokumen terinput" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-gray-700">Realisasi</label>
                        <input type="text" value={data.realisasi} onChange={(e) => setData('realisasi', e.target.value)} placeholder="Contoh: 85% dokumen terinput" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-gray-700">Skor Capaian: {data.skor}%</label>
                        <input type="range" min="0" max="100" value={data.skor} onChange={(e) => setData('skor', e.target.value)} className="mt-3 w-full accent-primary" />
                        {errors.skor && <div className="mt-1 text-sm text-red-600">{errors.skor}</div>}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-bold text-gray-700">Status</label>
                            <select value={data.status} onChange={(e) => setData('status', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                <option value="sesuai">Sesuai</option>
                                <option value="perlu_perhatian">Perlu Perhatian</option>
                                <option value="kritis">Kritis</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-bold text-gray-700">Prioritas</label>
                            <select value={data.prioritas} onChange={(e) => setData('prioritas', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                <option value="rendah">Rendah</option>
                                <option value="sedang">Sedang</option>
                                <option value="tinggi">Tinggi</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 className="text-lg font-black text-gray-950">Analisis dan Tindak Lanjut</h3>
                <div className="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label className="block text-sm font-bold text-gray-700">Catatan Temuan</label>
                        <textarea value={data.catatan} onChange={(e) => setData('catatan', e.target.value)} rows="4" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-gray-700">Rekomendasi</label>
                        <textarea value={data.rekomendasi} onChange={(e) => setData('rekomendasi', e.target.value)} rows="4" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-gray-700">Penanggung Jawab</label>
                        <input type="text" value={data.penanggung_jawab} onChange={(e) => setData('penanggung_jawab', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-bold text-gray-700">Tenggat</label>
                            <input type="date" value={data.tenggat_tindak_lanjut} onChange={(e) => setData('tenggat_tindak_lanjut', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label className="block text-sm font-bold text-gray-700">Status Tindak Lanjut</label>
                            <select value={data.status_tindak_lanjut} onChange={(e) => setData('status_tindak_lanjut', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                <option value="belum">Belum</option>
                                <option value="proses">Proses</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div className="flex justify-end gap-3">
                <Link href={route('monev.index')} className="rounded-lg border border-gray-300 px-5 py-3 font-bold text-gray-700 hover:bg-white">Batal</Link>
                <button disabled={processing} type="submit" className="rounded-lg bg-primary px-5 py-3 font-bold text-white shadow hover:bg-primary-dark">{submitLabel}</button>
            </div>
        </form>
    );
}
