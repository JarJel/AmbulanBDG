import { Head } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    logs: any[];
}

export default function AdminLogs({ logs }: Props) {
    return (
        <AppLayout activeMenu="logs">
            <Head title="Log Audit Mitra - Admin" />
            
            <div className="max-w-7xl mx-auto px-6 py-10 space-y-8">
                <div>
                    <h1 className="text-2xl font-black text-slate-800 tracking-tight">Log Audit Institusi</h1>
                    <p className="text-gray-500 text-xs mt-1">Riwayat seluruh aktivitas perubahan status armada, verifikasi lisensi staff, dan perubahan data internal Anda.</p>
                </div>

                <div className="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-150 text-[10px] font-black uppercase text-gray-500 tracking-wider">
                                <th className="p-6">Waktu</th>
                                <th className="p-6">Tindakan / Event</th>
                                <th className="p-6">Deskripsi Detail</th>
                                <th className="p-6">IP Address</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-105 text-xs text-slate-700">
                            {logs.map((log) => (
                                <tr key={log.id} className="hover:bg-gray-50/20 transition">
                                    <td className="p-6 font-mono text-gray-400">
                                        {new Date(log.created_at).toLocaleString('id-ID')}
                                    </td>
                                    <td className="p-6">
                                        <span className="px-2.5 py-0.5 rounded bg-slate-100 text-slate-650 text-[9px] font-extrabold uppercase tracking-wide">
                                            {log.action}
                                        </span>
                                    </td>
                                    <td className="p-6 font-semibold text-gray-650 leading-relaxed">
                                        {log.description}
                                    </td>
                                    <td className="p-6 font-mono text-gray-400">{log.ip_address || '-'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
