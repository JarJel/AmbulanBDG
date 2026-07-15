import { Head } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    user: any;
    profile: any;
}

export default function NurseProfile({ user, profile }: Props) {
    return (
        <AppLayout activeMenu="profile">
            <Head title="Profil Perawat - STR Verifikasi" />
            
            <div className="max-w-md mx-auto px-4 py-8 space-y-6">
                <div>
                    <h1 className="text-xl font-black text-slate-800 tracking-tight">Profil & Lisensi STR</h1>
                    <p className="text-gray-500 text-[11px] mt-1">Status keanggotaan medis dan dokumen lisensi Surat Tanda Registrasi (STR).</p>
                </div>

                <div className="bg-white rounded-2xl border border-gray-150 shadow-md p-6 space-y-6">
                    <div className="flex items-center gap-3 border-b pb-4">
                        <div className="h-12 w-12 bg-slate-900 text-white rounded-full flex items-center justify-center text-lg font-black uppercase">
                            {user.name.charAt(0)}
                        </div>
                        <div>
                            <h2 className="text-sm font-extrabold uppercase text-slate-800">{user.name}</h2>
                            <p className="text-[10px] text-gray-400 font-bold uppercase tracking-wider">{user.role}</p>
                        </div>
                    </div>

                    <div className="space-y-4 text-xs">
                        <div>
                            <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wide block">Email Staf</span>
                            <span className="font-semibold text-slate-700">{user.email}</span>
                        </div>

                        <div>
                            <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wide block">No. Telepon</span>
                            <span className="font-semibold text-slate-700">{user.phone || '-'}</span>
                        </div>

                        <div>
                            <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wide block">Dokumen STR Medis</span>
                            {profile ? (
                                <div className="mt-1 space-y-1">
                                    <p className="font-extrabold text-slate-750">{profile.license_number}</p>
                                    <div className="flex gap-2 items-center">
                                        <span className={`text-[9px] uppercase font-black px-2 py-0.5 rounded ${
                                            profile.verification_status === 'approved' ? 'bg-emerald-50 text-emerald-605' : 'bg-amber-50 text-amber-600'
                                        }`}>
                                            {profile.verification_status}
                                        </span>
                                        <span className="text-[10px] text-gray-400">Exp: {profile.license_expiry}</span>
                                    </div>
                                </div>
                            ) : (
                                <span className="text-gray-400 italic">Belum ada STR terunggah. Silakan hubungi admin institusi.</span>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
