import { useForm, Head, Link } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'customer', // default role
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/register');
    };

    return (
        <AppLayout role="customer" activeMenu="">
            <Head title="Daftar Akun Baru - AmbulanBDG" />
            <div className="flex items-center justify-center min-h-full w-full p-6">
                <div className="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 shadow-xl rounded-2xl p-8 space-y-6">
                    <div className="text-center space-y-2">
                        <h2 className="font-extrabold text-2xl tracking-tight">DAFTAR AKUN BARU</h2>
                        <p className="text-xs text-slate-500">Silakan isi formulir di bawah ini untuk membuat akun baru.</p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Masukkan nama lengkap Anda"
                                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm bg-white dark:border-slate-800 dark:bg-slate-950 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                required
                            />
                            {errors.name && (
                                <span className="text-[10px] text-red-500 font-semibold mt-1 block">{errors.name}</span>
                            )}
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="nama@email.com"
                                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm bg-white dark:border-slate-800 dark:bg-slate-950 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                required
                            />
                            {errors.email && (
                                <span className="text-[10px] text-red-500 font-semibold mt-1 block">{errors.email}</span>
                            )}
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Pilih Peran Akun (Role)</label>
                            <select
                                value={data.role}
                                onChange={(e) => setData('role', e.target.value)}
                                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm bg-white dark:border-slate-800 dark:bg-slate-950 focus:outline-none"
                            >
                                <option value="customer">Pelanggan / Customer</option>
                                <option value="driver">Driver Ambulans</option>
                                <option value="nurse">Perawat Medis</option>
                            </select>
                            {errors.role && (
                                <span className="text-[10px] text-red-500 font-semibold mt-1 block">{errors.role}</span>
                            )}
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Kata Sandi</label>
                            <input
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Masukkan kata sandi baru"
                                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm bg-white dark:border-slate-800 dark:bg-slate-950 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                required
                            />
                            {errors.password && (
                                <span className="text-[10px] text-red-500 font-semibold mt-1 block">{errors.password}</span>
                            )}
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Konfirmasi Kata Sandi</label>
                            <input
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                placeholder="Masukkan kembali kata sandi"
                                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm bg-white dark:border-slate-800 dark:bg-slate-950 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                required
                            />
                            {errors.password_confirmation && (
                                <span className="text-[10px] text-red-500 font-semibold mt-1 block">{errors.password_confirmation}</span>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full bg-teal-600 hover:bg-teal-700 disabled:bg-teal-400 text-white rounded-lg py-2.5 text-xs font-bold shadow transition uppercase tracking-wider"
                        >
                            Daftar Akun
                        </button>
                    </form>

                    <div className="text-center text-xs text-slate-500">
                        Sudah memiliki akun?{' '}
                        <Link href="/login" className="font-bold text-teal-600 hover:underline">
                            Masuk
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
