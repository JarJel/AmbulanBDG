import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { useEcho } from '@laravel/echo-react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    stats: {
        total_orders: number;
        total_revenue: number;
        avg_sla_seconds: number;
        total_institutions: number;
        total_ambulances: number;
        total_users: number;
    };
    recent_logs: any[];
}

export default function SuperAdminDashboard({ stats, recent_logs }: Props) {
    const [slaBreaches, setSlaBreaches] = useState<any[]>([]);

    useEcho(
        'superadmin.alerts',
        '.sla.breached',
        (e: any) => {
            setSlaBreaches((prev) => [e, ...prev]);
        },
        []
    );

    return (
        <AppLayout activeMenu="dashboard">
            <Head title="Super Admin Dashboard - AmbulanBDG" />
            
            <div className="max-w-7xl mx-auto px-6 py-10 space-y-10">
                {/* Header Banner */}
                <div className="bg-slate-900 text-white p-8 rounded-3xl shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 className="text-3xl font-black tracking-tight">Super Admin Global Control</h1>
                        <p className="text-sm opacity-90 mt-2">Pusat kendali, performa, log audit keamanan, dan verifikasi institusi se-Bandung Raya.</p>
                    </div>
                </div>

                {/* SLA Breach Alerts */}
                {slaBreaches.length > 0 && (
                    <div className="space-y-3">
                        {slaBreaches.map((breach, index) => (
                            <div key={index} className="bg-red-50 border border-red-200 text-red-900 rounded-2xl p-5 flex items-center justify-between shadow animate-pulse">
                                <div className="space-y-1">
                                    <span className="text-[10px] bg-red-600 text-white font-black uppercase px-2 py-0.5 rounded">SLA Breach Warning</span>
                                    <p className="text-xs font-bold leading-relaxed">{breach.message}</p>
                                    <p className="text-[10px] text-slate-500 font-mono">Waktu Kejadian: {new Date(breach.recorded_at).toLocaleTimeString()}</p>
                                </div>
                                <button 
                                    onClick={() => setSlaBreaches(slaBreaches.filter((_, i) => i !== index))}
                                    className="text-red-500 hover:text-red-800 text-xs font-black uppercase"
                                >
                                    Dismiss
                                </button>
                            </div>
                        ))}
                    </div>
                )}

                {/* Global Metrics grid */}
                <div className="grid grid-cols-2 md:grid-cols-6 gap-6">
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-md text-center hover:scale-[1.02] transition">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Total Orders</span>
                        <span className="text-3xl font-black text-slate-800 block mt-2">{stats.total_orders}</span>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-md text-center hover:scale-[1.02] transition">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Total Revenue</span>
                        <span className="text-xl font-black text-slate-800 block mt-3">Rp {stats.total_revenue.toLocaleString('id-ID')}</span>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-md text-center hover:scale-[1.02] transition">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Rata SLA SOS</span>
                        <span className="text-3xl font-black text-red-500 block mt-2">{stats.avg_sla_seconds}s</span>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-md text-center hover:scale-[1.02] transition">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Institusi</span>
                        <span className="text-3xl font-black text-slate-800 block mt-2">{stats.total_institutions}</span>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-md text-center hover:scale-[1.02] transition">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Armada</span>
                        <span className="text-3xl font-black text-slate-800 block mt-2">{stats.total_ambulances}</span>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-md text-center hover:scale-[1.02] transition">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Pengguna</span>
                        <span className="text-3xl font-black text-slate-800 block mt-2">{stats.total_users}</span>
                    </div>
                </div>

                {/* Audit Logs list */}
                <div className="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                    <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h2 className="text-sm font-black tracking-tight text-slate-850 uppercase">Audit Log Keamanan Sistem (Terbaru)</h2>
                        <Link href="/superadmin/audit-logs" className="text-xs font-bold text-slate-900 uppercase hover:underline">
                            Lihat Semua Log
                        </Link>
                    </div>

                    <div className="divide-y divide-gray-105">
                        {recent_logs.length === 0 ? (
                            <div className="p-12 text-center text-gray-400 text-sm">
                                Belum ada riwayat aktivitas sistem tercatat.
                            </div>
                        ) : (
                            recent_logs.map((log) => (
                                <div key={log.id} className="p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:bg-gray-50/40 transition">
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs font-black text-slate-850 uppercase tracking-wider">{log.action}</span>
                                            <span className="text-[9px] bg-slate-100 text-slate-500 font-bold px-2 py-0.5 rounded uppercase">
                                                {log.user ? log.user.name : 'System / Cron'}
                                            </span>
                                        </div>
                                        <p className="text-xs text-gray-650">{log.description}</p>
                                    </div>
                                    <div className="text-[10px] text-gray-400 font-mono">
                                        {new Date(log.created_at).toLocaleString('id-ID')}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
