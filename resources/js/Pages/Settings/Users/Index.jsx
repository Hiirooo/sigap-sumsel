import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const roleLabels = {
    admin: 'Administrator',
    operator: 'Operator Konten',
    viewer: 'Viewer / Pimpinan',
};

const roleDescriptions = {
    admin: 'Akses penuh termasuk user, role, kategori, dan seluruh modul.',
    operator: 'Mengelola konten, dokumentasi, kliping, arsip, dan monev.',
    viewer: 'Akses baca dashboard, inventaris, laporan, dan monev.',
};

function RoleBadge({ role }) {
    const classes = {
        admin: 'bg-emerald-900 text-gold',
        operator: 'bg-blue-50 text-blue-700',
        viewer: 'bg-slate-100 text-slate-700',
    };

    return (
        <span className={`inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide ${classes[role] || classes.viewer}`}>
            {roleLabels[role] || role}
        </span>
    );
}

function UserRow({ user, roles, currentUserId }) {
    const [editing, setEditing] = useState(false);
    const { data, setData, put, processing, errors, reset } = useForm({
        name: user.name,
        email: user.email,
        role: user.role,
        password: '',
    });

    const save = (e) => {
        e.preventDefault();
        put(route('settings.users.update', user.id), {
            preserveScroll: true,
            onSuccess: () => {
                setEditing(false);
                reset('password');
            },
        });
    };

    const destroy = () => {
        if (confirm(`Hapus user ${user.name}?`)) {
            router.delete(route('settings.users.destroy', user.id), { preserveScroll: true });
        }
    };

    if (editing) {
        return (
            <tr className="bg-amber-50/50 align-top">
                <td className="px-5 py-4" colSpan="5">
                    <form onSubmit={save} className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_1fr_180px_1fr_auto]">
                        <div>
                            <label className="text-xs font-bold uppercase text-gray-500">Nama</label>
                            <input value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                            {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                        </div>
                        <div>
                            <label className="text-xs font-bold uppercase text-gray-500">Email</label>
                            <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                            {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email}</p>}
                        </div>
                        <div>
                            <label className="text-xs font-bold uppercase text-gray-500">Role</label>
                            <select value={data.role} onChange={(e) => setData('role', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                                {roles.map((role) => <option key={role} value={role}>{roleLabels[role]}</option>)}
                            </select>
                            {errors.role && <p className="mt-1 text-xs text-red-600">{errors.role}</p>}
                        </div>
                        <div>
                            <label className="text-xs font-bold uppercase text-gray-500">Password Baru</label>
                            <input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} placeholder="Kosongkan jika tetap" className="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                            {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password}</p>}
                        </div>
                        <div className="flex items-end gap-2">
                            <button disabled={processing} type="submit" className="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-dark">Simpan</button>
                            <button type="button" onClick={() => setEditing(false)} className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-white">Batal</button>
                        </div>
                    </form>
                </td>
            </tr>
        );
    }

    return (
        <tr className="border-t border-gray-100 hover:bg-gray-50/80">
            <td className="px-5 py-4">
                <div className="font-black text-gray-950">{user.name}</div>
                <div className="text-sm text-gray-500">{user.email}</div>
            </td>
            <td className="px-5 py-4"><RoleBadge role={user.role} /></td>
            <td className="px-5 py-4 text-sm text-gray-500">{new Date(user.created_at).toLocaleDateString('id-ID')}</td>
            <td className="px-5 py-4 text-sm text-gray-600">{roleDescriptions[user.role]}</td>
            <td className="px-5 py-4 text-right">
                <div className="flex justify-end gap-2">
                    <button onClick={() => setEditing(true)} className="rounded-lg border border-emerald-200 px-3 py-2 text-sm font-bold text-emerald-800 hover:bg-emerald-50">Edit</button>
                    <button disabled={currentUserId === user.id} onClick={destroy} className="rounded-lg border border-red-200 px-3 py-2 text-sm font-bold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40">Hapus</button>
                </div>
            </td>
        </tr>
    );
}

export default function Index({ users, roles }) {
    const currentUser = usePage().props.auth.user;
    const flash = usePage().props.flash || {};
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        role: 'operator',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('settings.users.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const counts = roles.map((role) => ({
        role,
        total: users.filter((user) => user.role === role).length,
    }));

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-white">Pengaturan User dan Role</h2>}>
            <Head title="Pengaturan User" />
            <div className="min-h-screen bg-slate-100 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-3xl bg-gradient-to-r from-emerald-950 to-primary p-6 text-white shadow-xl">
                        <p className="text-sm font-black uppercase tracking-[0.22em] text-gold">Access Control</p>
                        <div className="mt-3 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                            <div>
                                <h1 className="text-3xl font-black">Kelola Pengguna Sistem</h1>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75">Atur akun internal, pembagian role, dan akses modul SIGAP Sumsel secara terpusat.</p>
                            </div>
                            <div className="grid grid-cols-3 gap-3">
                                {counts.map((item) => (
                                    <div key={item.role} className="rounded-2xl border border-white/15 bg-white/10 p-4 text-center backdrop-blur">
                                        <div className="text-2xl font-black text-gold">{item.total}</div>
                                        <div className="text-xs font-bold uppercase text-white/70">{item.role}</div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {(flash.success || Object.keys(errors).length > 0) && (
                        <div className={`rounded-2xl border px-5 py-4 text-sm font-bold ${flash.success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-700'}`}>
                            {flash.success || 'Periksa kembali isian user dan role.'}
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-[420px_1fr]">
                        <form onSubmit={submit} className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-black text-gray-950">Tambah User</h2>
                            <div className="mt-5 space-y-4">
                                <div>
                                    <label className="block text-sm font-bold text-gray-700">Nama</label>
                                    <input value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                                    {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-bold text-gray-700">Email</label>
                                    <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                                    {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-bold text-gray-700">Password</label>
                                    <input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary" />
                                    {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-bold text-gray-700">Role</label>
                                    <select value={data.role} onChange={(e) => setData('role', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                        {roles.map((role) => <option key={role} value={role}>{roleLabels[role]}</option>)}
                                    </select>
                                </div>
                                <button disabled={processing} className="w-full rounded-xl bg-primary px-5 py-3 font-black text-white shadow hover:bg-primary-dark">Simpan User</button>
                            </div>
                        </form>

                        <div className="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                            <div className="border-b border-gray-100 px-6 py-5">
                                <h2 className="text-lg font-black text-gray-950">Daftar User dan Role</h2>
                                <p className="mt-1 text-sm text-gray-500">Edit data user langsung dari tabel. Password hanya berubah jika kolom password baru diisi.</p>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-100">
                                    <thead className="bg-gray-50 text-left text-xs font-black uppercase tracking-wider text-gray-500">
                                        <tr>
                                            <th className="px-5 py-3">User</th>
                                            <th className="px-5 py-3">Role</th>
                                            <th className="px-5 py-3">Dibuat</th>
                                            <th className="px-5 py-3">Akses</th>
                                            <th className="px-5 py-3 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 bg-white">
                                        {users.map((user) => <UserRow key={user.id} user={user} roles={roles} currentUserId={currentUser.id} />)}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
