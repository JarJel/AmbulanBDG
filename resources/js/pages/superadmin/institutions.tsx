import { Head } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    institutions: any[];
}

export default function SuperAdminInstitutions({ institutions }: Props) {
    return (
        <AppLayout activeMenu="institutions">
            <Head title="Manajemen Institusi - Super Admin" />
            
            <div className="max-w-7xl mx-auto px-6 py-10 space-y-8">
                <div>
                    <h1 className="text-2xl font-black text-slate-800 tracking-tight">Daftar Institusi Terdaftar</h1>
                    <p className="text-gray-500 text-xs mt-1">Kelola data mitra fasyankes negeri, swasta, dan kelompok relawan se-Bandung.</p>
                </div>

                <div className="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-150 text-[10px] font-black uppercase text-gray-500 tracking-wider">
                                <th className="p-6">Nama Institusi</th>
                                <th className="p-6">Tipe</th>
                                <th className="p-6">Status Verifikasi</th>
                                <th className="p-6">No. Izin Kemenkes</th>
                                <th className="p-6">Total Armada</th>
                                <th className="p-6">Nomor Kontak</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-105 text-xs text-slate-700">
                            {institutions.map((inst) => (
                                <tr key={inst.id} className="hover:bg-gray-50/20 transition">
                                    <td className="p-6">
                                        <p className="font-extrabold text-slate-850">{inst.name}</p>
                                        <p className="text-[10px] text-gray-400 mt-1">{inst.address || '-'}</p>
                                    </td>
                                    <td className="p-6">
                                        <span className={`px-2.5 py-1 rounded-full text-[9px] uppercase font-bold ${
                                            inst.type === 'fasyankes' ? 'bg-blue-50 text-blue-600' :
                                            inst.type === 'swasta' ? 'bg-purple-50 text-purple-600' : 'bg-orange-50 text-orange-600'
                                        }`}>
                                            {inst.type}
                                        </span>
                                    </td>
                                    <td className="p-6">
                                        <span className="text-emerald-600 font-extrabold">✓ {inst.verified_status.toUpperCase()}</span>
                                    </td>
                                    <td className="p-6 font-mono">{inst.license_number || '-'}</td>
                                    <td className="p-6 font-bold">{inst.ambulances_count || 0} Unit</td>
                                    <td className="p-6">{inst.phone || '-'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
