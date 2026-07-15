import { Head } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    ambulances: any[];
}

export default function SuperAdminAmbulances({ ambulances }: Props) {
    return (
        <AppLayout activeMenu="ambulances">
            <Head title="Manajemen Armada Global - Super Admin" />
            
            <div className="max-w-7xl mx-auto px-6 py-10 space-y-8">
                <div>
                    <h1 className="text-2xl font-black text-slate-800 tracking-tight">Semua Armada Ambulans</h1>
                    <p className="text-gray-500 text-xs mt-1">Daftar global unit kendaraan, status siaga, ketersediaan pelayanan gratis, dan tarif dasar.</p>
                </div>

                <div className="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-150 text-[10px] font-black uppercase text-gray-500 tracking-wider">
                                <th className="p-6">Plat Nomor</th>
                                <th className="p-6">Pemilik / Institusi</th>
                                <th className="p-6">Kategori</th>
                                <th className="p-6">Status Siaga</th>
                                <th className="p-6">SOS Layanan Gratis</th>
                                <th className="p-6">Tarif Dasar</th>
                                <th className="p-6">Tarif per KM</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-105 text-xs text-slate-700">
                            {ambulances.map((amb) => (
                                <tr key={amb.id} className="hover:bg-gray-50/20 transition">
                                    <td className="p-6 font-extrabold text-slate-850">{amb.plate_number}</td>
                                    <td className="p-6 font-semibold">{amb.institution?.name || '-'}</td>
                                    <td className="p-6">
                                        <span className="uppercase text-[9px] font-extrabold bg-slate-100 text-slate-600 px-2 py-0.5 rounded">
                                            {amb.category}
                                        </span>
                                    </td>
                                    <td className="p-6">
                                        <span className={`px-2.5 py-1 rounded-full text-[9px] uppercase font-bold ${
                                            amb.status === 'standby' ? 'bg-emerald-50 text-emerald-600' :
                                            amb.status === 'suspended' ? 'bg-red-50 text-red-650' : 'bg-purple-50 text-purple-600'
                                        }`}>
                                            ● {amb.status}
                                        </span>
                                    </td>
                                    <td className="p-6 font-bold">
                                        {amb.is_free_service_eligible ? (
                                            <span className="text-emerald-600">✓ YA (SOS Eligible)</span>
                                        ) : (
                                            <span className="text-gray-400">❌ TIDAK (Swasta Berbayar)</span>
                                        )}
                                    </td>
                                    <td className="p-6">Rp {parseFloat(amb.base_fare).toLocaleString('id-ID')}</td>
                                    <td className="p-6">Rp {parseFloat(amb.price_per_km).toLocaleString('id-ID')}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
