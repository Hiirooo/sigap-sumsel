import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout logoSrc="/logo-bhp.png">
            <Head title="Masuk" />

            <div className="mb-7 border-b border-slate-100 pb-6">
                <p className="text-[11px] font-bold uppercase tracking-[0.24em] text-gold-dark">SIGAP Sumatera Selatan</p>
                <h2 className="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Selamat datang kembali</h2>
                <p className="mt-2 text-sm leading-6 text-slate-500">Masukkan akun resmi Anda untuk mengakses dashboard.</p>
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="Alamat Email" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        placeholder="nama@sumselprov.go.id"
                        className="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-primary focus:bg-white focus:ring-primary"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-5">
                    <InputLabel htmlFor="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        placeholder="Masukkan kata sandi"
                        className="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-primary focus:bg-white focus:ring-primary"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-5 flex items-center justify-between gap-4">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-sm text-slate-600">
                            Ingat saya
                        </span>
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-sm font-semibold text-primary hover:text-primary-light focus:outline-none"
                        >
                            Lupa kata sandi?
                        </Link>
                    )}
                </div>

                <div className="mt-7">
                    <PrimaryButton className="w-full justify-center rounded-xl bg-primary py-3.5 text-sm font-bold tracking-wide shadow-lg shadow-primary/20 hover:bg-primary-light focus:bg-primary-light focus:ring-gold" disabled={processing}>
                        {processing ? 'Memproses...' : 'Masuk ke SIGAP'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
