import { Head } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    notifications: any[];
}

export default function CustomerNotifications({ notifications }: Props) {
    return (
        <AppLayout activeMenu="notifications">
            <Head title="Notifikasi Saya - AmbulanBDG" />
            
            <div className="max-w-4xl mx-auto px-6 py-10 space-y-8">
                <div>
                    <h1 className="text-2xl font-black text-slate-800 tracking-tight">Kotak Masuk Notifikasi</h1>
                    <p className="text-gray-500 text-xs mt-1">Dapatkan informasi status pesanan, penugasan kru medis, dan update penting lainnya.</p>
                </div>

                <div className="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                    {notifications.length === 0 ? (
                        <div className="p-16 text-center text-gray-400 text-sm">
                            Tidak ada notifikasi baru saat ini.
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-105">
                            {notifications.map((notif) => {
                                const data = JSON.parse(notif.data);
                                return (
                                    <div key={notif.id} className="p-6 flex gap-4 hover:bg-gray-50/20 transition">
                                        <div className="text-2xl mt-1">🔔</div>
                                        <div className="space-y-1">
                                            <p className="text-xs font-black text-slate-800 uppercase tracking-wide">
                                                {data.title || 'Pemberitahuan Sistem'}
                                            </p>
                                            <p className="text-sm text-gray-650">{data.message}</p>
                                            <span className="text-[10px] text-gray-400 font-bold block mt-2">
                                                {new Date(notif.created_at).toLocaleString('id-ID')}
                                            </span>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
