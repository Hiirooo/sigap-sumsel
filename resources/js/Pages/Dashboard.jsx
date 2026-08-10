import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value || 0);

function KpiCard({ title, value, subtitle, accent = 'bg-primary', href, hrefLabel }) {
    return (
        <div className="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
            <div className={`absolute right-0 top-0 h-24 w-24 translate-x-8 -translate-y-8 rounded-full ${accent} opacity-10`} />
            <div className="relative">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">{title}</p>
                <div className="mt-3 text-4xl font-black tracking-tight text-gray-950">{formatNumber(value)}</div>
                <p className="mt-2 text-sm text-gray-500">{subtitle}</p>
                {href && (
                    <Link href={href} className="mt-4 inline-flex text-sm font-semibold text-primary-light hover:underline">
                        {hrefLabel || 'Lihat detail'}
                    </Link>
                )}
            </div>
        </div>
    );
}

function LineChart({ data }) {
    const width = 760;
    const height = 270;
    const padding = 34;
    const maxValue = Math.max(1, ...data.map((item) => item.total));
    const stepX = data.length > 1 ? (width - padding * 2) / (data.length - 1) : 0;
    const points = data.map((item, index) => {
        const x = padding + index * stepX;
        const y = height - padding - (item.total / maxValue) * (height - padding * 2);
        return { ...item, x, y };
    });
    const path = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
    const area = `${path} L ${points[points.length - 1]?.x || padding} ${height - padding} L ${padding} ${height - padding} Z`;

    return (
        <div className="overflow-x-auto">
            <svg viewBox={`0 0 ${width} ${height}`} className="min-w-[620px] w-full">
                <defs>
                    <linearGradient id="trendFill" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stopColor="#0c2d5e" stopOpacity="0.24" />
                        <stop offset="100%" stopColor="#0c2d5e" stopOpacity="0" />
                    </linearGradient>
                </defs>
                {[0, 0.25, 0.5, 0.75, 1].map((ratio) => {
                    const y = padding + ratio * (height - padding * 2);
                    return <line key={ratio} x1={padding} x2={width - padding} y1={y} y2={y} stroke="#e5e7eb" strokeDasharray="4 6" />;
                })}
                {points.length > 0 && <path d={area} fill="url(#trendFill)" />}
                {points.length > 0 && <path d={path} fill="none" stroke="#0c2d5e" strokeWidth="4" strokeLinecap="round" strokeLinejoin="round" />}
                {points.map((point) => (
                    <g key={point.label}>
                        <circle cx={point.x} cy={point.y} r="6" fill="#d4af37" stroke="#0c2d5e" strokeWidth="3" />
                        <text x={point.x} y={height - 8} textAnchor="middle" className="fill-gray-500 text-[11px] font-semibold">
                            {point.label}
                        </text>
                        <text x={point.x} y={point.y - 14} textAnchor="middle" className="fill-gray-800 text-[12px] font-bold">
                            {point.total}
                        </text>
                    </g>
                ))}
            </svg>
        </div>
    );
}

function DonutChart({ data, title, totalLabel }) {
    const total = data.reduce((sum, item) => sum + (item.value || 0), 0);
    let offset = 25;
    const radius = 38;
    const circumference = 2 * Math.PI * radius;

    return (
        <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-lg font-bold text-gray-950">{title}</h3>
                    <p className="mt-1 text-sm text-gray-500">{totalLabel}</p>
                </div>
                <div className="relative h-32 w-32 shrink-0">
                    <svg viewBox="0 0 100 100" className="h-full w-full -rotate-90">
                        <circle cx="50" cy="50" r={radius} fill="none" stroke="#eef2f7" strokeWidth="12" />
                        {data.map((item) => {
                            const length = total > 0 ? (item.value / total) * circumference : 0;
                            const segment = (
                                <circle
                                    key={item.label}
                                    cx="50"
                                    cy="50"
                                    r={radius}
                                    fill="none"
                                    stroke={item.color}
                                    strokeWidth="12"
                                    strokeDasharray={`${length} ${circumference - length}`}
                                    strokeDashoffset={-offset}
                                    strokeLinecap="round"
                                />
                            );
                            offset += length;
                            return segment;
                        })}
                    </svg>
                    <div className="absolute inset-0 flex flex-col items-center justify-center">
                        <div className="text-2xl font-black text-gray-950">{formatNumber(total)}</div>
                        <div className="text-[10px] uppercase tracking-widest text-gray-500">Total</div>
                    </div>
                </div>
            </div>
            <div className="mt-5 space-y-3">
                {data.map((item) => (
                    <div key={item.label} className="flex items-center justify-between gap-3 text-sm">
                        <div className="flex items-center gap-2">
                            <span className="h-3 w-3 rounded-full" style={{ backgroundColor: item.color }} />
                            <span className="font-medium text-gray-700">{item.label}</span>
                        </div>
                        <span className="font-bold text-gray-950">{formatNumber(item.value)}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function ProgressList({ title, items }) {
    const maxValue = Math.max(1, ...items.map((item) => item.value || 0));

    return (
        <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 className="text-lg font-bold text-gray-950">{title}</h3>
            <div className="mt-5 space-y-4">
                {items.length === 0 ? (
                    <p className="text-sm italic text-gray-500">Belum ada data.</p>
                ) : (
                    items.map((item) => (
                        <div key={item.label}>
                            <div className="mb-1 flex items-center justify-between text-sm">
                                <span className="font-semibold text-gray-700">{item.label}</span>
                                <span className="text-gray-500">{formatNumber(item.value)}</span>
                            </div>
                            <div className="h-2 rounded-full bg-gray-100">
                                <div className="h-2 rounded-full bg-primary" style={{ width: `${Math.max(6, (item.value / maxValue) * 100)}%` }} />
                            </div>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
}

export default function Dashboard({ stats }) {
    const totalDocuments = stats?.total_documents || 0;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold leading-tight text-white">Dashboard Analitik SIGAP SUMSEL</h2>
                    <p className="text-sm text-white/80">Command center dokumentasi, pemberitaan, arsip, dan kliping media.</p>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="min-h-screen bg-slate-100 py-8">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section className="overflow-hidden rounded-3xl bg-gradient-to-br from-primary via-primary-dark to-slate-950 p-8 text-white shadow-xl">
                        <div className="grid gap-8 lg:grid-cols-[1.5fr_1fr] lg:items-end">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.28em] text-gold">Executive Overview</p>
                                <h1 className="mt-4 max-w-3xl text-4xl font-black tracking-tight md:text-5xl">Pusat kendali dokumentasi pemberitaan terintegrasi.</h1>
                                <p className="mt-4 max-w-2xl text-base leading-7 text-white/75">Memantau volume dokumen, status verifikasi, sentimen media, dan aktivitas operator untuk mendukung keputusan pimpinan berbasis data.</p>
                            </div>
                            <div className="grid grid-cols-2 gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                                <div className="rounded-xl bg-white/10 p-4">
                                    <div className="text-3xl font-black">{formatNumber(totalDocuments)}</div>
                                    <div className="mt-1 text-xs uppercase tracking-wider text-white/70">Total Dokumen</div>
                                </div>
                                <div className="rounded-xl bg-white/10 p-4">
                                    <div className="text-3xl font-black">{stats?.this_month_uploads || 0}</div>
                                    <div className="mt-1 text-xs uppercase tracking-wider text-white/70">Upload Bulan Ini</div>
                                </div>
                                <div className="rounded-xl bg-white/10 p-4">
                                    <div className="text-3xl font-black">{stats?.verification_rate || 0}%</div>
                                    <div className="mt-1 text-xs uppercase tracking-wider text-white/70">Verifikasi Foto/Video</div>
                                </div>
                                <div className="rounded-xl bg-white/10 p-4">
                                    <div className="text-3xl font-black">{stats?.publication_rate || 0}%</div>
                                    <div className="mt-1 text-xs uppercase tracking-wider text-white/70">Publikasi Rilis</div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                        <KpiCard title="Rilis Berita" value={stats?.rilis_berita} subtitle={`${formatNumber(stats?.published_rilis)} sudah terpublikasi`} accent="bg-primary" href={route('rilis-berita.index')} hrefLabel="Kelola rilis" />
                        <KpiCard title="Dokumentasi" value={stats?.dokumentasi} subtitle={`${formatNumber(stats?.verified_docs)} berkas terverifikasi`} accent="bg-blue-600" href={route('dokumentasi.index')} hrefLabel="Buka galeri" />
                        <KpiCard title="Kliping Media" value={stats?.kliping} subtitle="Basis monitoring pemberitaan" accent="bg-gold" href={route('kliping.index')} hrefLabel="Kelola kliping" />
                        <KpiCard title="Arsip Kepegawaian" value={stats?.arsip_statis} subtitle="Dokumen kepegawaian" accent="bg-slate-600" href={route('arsip-statis.index')} hrefLabel="Kelola arsip" />
                    </section>

                    <section className="grid grid-cols-1 gap-6 xl:grid-cols-[1.55fr_1fr]">
                        <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                            <div className="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                                <div>
                                    <h3 className="text-xl font-black text-gray-950">Tren Produksi Dokumentasi</h3>
                                    <p className="text-sm text-gray-500">Akumulasi rilis, dokumentasi, dan kliping selama 6 bulan terakhir.</p>
                                </div>
                                <Link href={route('inventaris.index')} className="rounded-full bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-dark">Lihat Inventaris</Link>
                            </div>
                            <LineChart data={stats?.monthly_trend || []} />
                            <div className="mt-5 grid grid-cols-3 gap-3 text-center text-sm">
                                <div className="rounded-xl bg-slate-50 p-3"><span className="font-bold text-primary">Rilis</span><div className="text-gray-500">Berita resmi</div></div>
                                <div className="rounded-xl bg-slate-50 p-3"><span className="font-bold text-blue-700">Dokumentasi</span><div className="text-gray-500">Foto/video</div></div>
                                <div className="rounded-xl bg-slate-50 p-3"><span className="font-bold text-gold-dark">Kliping</span><div className="text-gray-500">Media massa</div></div>
                            </div>
                        </div>

                        <DonutChart data={stats?.composition || []} title="Komposisi Database" totalLabel="Proporsi seluruh aset digital" />
                    </section>

                    <section className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <DonutChart data={stats?.sentiment || []} title="Sentimen Kliping" totalLabel="Monitoring persepsi media" />
                        <DonutChart data={stats?.verification || []} title="Status Verifikasi" totalLabel="Kesiapan dokumentasi publik" />
                        <ProgressList title="Dokumentasi per Pimpinan" items={stats?.leaders || []} />
                    </section>

                    <section className="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_1.35fr]">
                        <DonutChart data={stats?.digitalization || []} title="Status Digitalisasi" totalLabel="Tahapan pengarsipan dokumentasi" />

                        <div className="rounded-2xl border border-gray-100 bg-white shadow-sm">
                            <div className="border-b border-gray-100 p-6">
                                <h3 className="text-xl font-black text-gray-950">Audit Trail Terbaru</h3>
                                <p className="mt-1 text-sm text-gray-500">Aktivitas pengguna yang tercatat otomatis oleh sistem.</p>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-100">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Waktu</th>
                                            <th className="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Pengguna</th>
                                            <th className="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Aksi</th>
                                            <th className="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Modul</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 bg-white">
                                        {(stats?.logs || []).length === 0 ? (
                                            <tr><td colSpan="4" className="px-6 py-10 text-center text-sm italic text-gray-500">Belum ada aktivitas tercatat.</td></tr>
                                        ) : (
                                            stats.logs.map((log) => (
                                                <tr key={log.id} className="hover:bg-slate-50">
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{new Date(log.created_at).toLocaleString('id-ID')}</td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900">{log.user?.name || 'Sistem'}</td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${log.aksi === 'CREATE' ? 'bg-green-100 text-green-800' : log.aksi === 'UPDATE' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'}`}>{log.aksi}</span>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{log.model}</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
