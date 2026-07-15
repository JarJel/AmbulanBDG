import { Head } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    user: any;
}

export default function CustomerProfile({ user }: Props) {
    return (
        <AppLayout activeMenu="profile">
            <Head title="Profil Saya - AmbulanBDG" />
            
            <div className="max-w-3xl mx-auto px-6 py-12 space-y-8">
                <div>
                    <h1 className="text-2xl font-black text-slate-800 tracking-tight">Profil Akun Anda</h1>
                    <p className="text-gray-500 text-xs mt-1">Kelola data personal Anda untuk pemesanan ambulans cepat.</p>
                </div>

                <div className="bg-white rounded-3xl border border-gray-150 shadow-xl overflow-hidden p-8 space-y-6">
                    <div className="flex items-center gap-4 border-b border-gray-100 pb-6">
                        <div className="h-16 w-16 bg-slate-900 text-white rounded-full flex items-center justify-center text-2xl font-black shadow-md">
                            {user.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h2 className="text-lg font-black text-slate-800 uppercase">{user.name}</h2>
                            <span className="text-[10px] bg-slate-100 text-slate-500 px-2.5 py-0.5 rounded-full font-bold uppercase tracking-widest">
                                {user.role} Account
                            </span>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div className="space-y-1">
                            <span className="text-xs font-bold text-gray-400 uppercase tracking-wide block">Alamat Email</span>
                            <span className="font-semibold text-slate-700 block">{user.email}</span>
                        </div>

                        <div className="space-y-1">
                            <span className="text-xs font-bold text-gray-400 uppercase tracking-wide block">Nomor Telepon</span>
                            <span className="font-semibold text-slate-700 block">{user.phone || '-'}</span>
                        </div>

                        <div className="space-y-1">
                            <span className="text-xs font-bold text-gray-400 uppercase tracking-wide block">E-Verifikasi BPJS (Status Layanan Rujukan)</span>
                            <span className="font-semibold text-slate-750 block">
                                {user.bpjs_number ? (
                                    <span className="text-emerald-600 flex items-center gap-1">
                                        ✓ Aktif ({user.bpjs_number})
                                    </span>
                                ) : (
                                    <span className="text-gray-400 italic">
                                        Nomor BPJS belum dikaitkan (Tambahkan saat order non-darurat)
                                    </span>
                                )}
                            </span>
                        </div>

                        <div className="space-y-1">
                            <span className="text-xs font-bold text-gray-400 uppercase tracking-wide block">Terdaftar Sejak</span>
                            <span className="font-semibold text-slate-700 block">
                                {new Date(user.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
