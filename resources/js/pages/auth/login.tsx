import { useForm, Head, Link } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <AppLayout role="customer" activeMenu="">
            <Head title="Masuk Akun - AmbulanBDG" />
            <div className="flex items-center justify-center min-h-full w-full p-6">
                <div className="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 shadow-xl rounded-2xl p-8 space-y-6">
                    <div className="text-center space-y-2">
                        <h2 className="font-extrabold text-2xl tracking-tight">MASUK AKUN</h2>
                        <p className="text-xs text-slate-500">Silakan masukkan email dan kata sandi Anda untuk melanjutkan.</p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">
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
                            <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Kata Sandi</label>
                            <input
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Masukkan kata sandi"
                                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm bg-white dark:border-slate-800 dark:bg-slate-950 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                required
                            />
                            {errors.password && (
                                <span className="text-[10px] text-red-500 font-semibold mt-1 block">{errors.password}</span>
                            )}
                        </div>

                        <div className="flex items-center justify-between text-xs">
                            <label className="flex items-center gap-2 cursor-pointer font-medium text-slate-500">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                />
                                Ingat Saya
                            </label>
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full bg-teal-600 hover:bg-teal-700 disabled:bg-teal-400 text-white rounded-lg py-2.5 text-xs font-bold shadow transition uppercase tracking-wider"
                        >
                            Masuk
                        </button>
                    </form>

                    <div className="text-center text-xs text-slate-500">
                        Belum punya akun?{' '}
                        <Link href="/register" className="font-bold text-teal-600 hover:underline">
                            Daftar Sekarang
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
