import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    stats: {
        total_orders: number;
        completed_orders: number;
        active_orders: number;
    };
    recent_orders: any[];
}

export default function CustomerDashboard({ stats, recent_orders }: Props) {
    return (
        <AppLayout activeMenu="dashboard">
            <Head title="Dashboard Saya - AmbulanBDG" />
            
            <div className="max-w-7xl mx-auto px-6 py-10 space-y-10">
                {/* Header Welcome */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gradient-to-r from-red-650 to-red-500 text-white p-8 rounded-3xl shadow-xl">
                    <div>
                        <h1 className="text-3xl font-black tracking-tight">Halo, Selamat Datang!</h1>
                        <p className="text-sm opacity-90 mt-2">Pantau pesanan Anda, lakukan sewa terjadwal, atau panggil unit SOS dalam keadaan darurat.</p>
                    </div>
                    <Link
                        href="/"
                        className="bg-white text-red-600 px-6 py-3 rounded-full text-sm font-extrabold uppercase tracking-wider hover:bg-gray-100 transition shadow-md"
                    >
                        Panggil/Sewa Ambulans
                    </Link>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-lg flex items-center justify-between hover:scale-[1.02] transition">
                        <div>
                            <span className="text-xs font-bold text-gray-400 uppercase block tracking-wider">Total Pesanan</span>
                            <span className="text-4xl font-black text-slate-800 mt-2 block">{stats.total_orders}</span>
                        </div>
                        <div className="h-12 w-12 rounded-xl bg-gray-50 flex items-center justify-center text-xl">📦</div>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-lg flex items-center justify-between hover:scale-[1.02] transition">
                        <div>
                            <span className="text-xs font-bold text-gray-400 uppercase block tracking-wider">Perjalanan Selesai</span>
                            <span className="text-4xl font-black text-emerald-600 mt-2 block">{stats.completed_orders}</span>
                        </div>
                        <div className="h-12 w-12 rounded-xl bg-emerald-50 flex items-center justify-center text-xl">✅</div>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-lg flex items-center justify-between hover:scale-[1.02] transition">
                        <div>
                            <span className="text-xs font-bold text-gray-400 uppercase block tracking-wider">Trip Aktif</span>
                            <span className="text-4xl font-black text-red-500 mt-2 block">{stats.active_orders}</span>
                        </div>
                        <div className="h-12 w-12 rounded-xl bg-red-50 flex items-center justify-center text-xl animate-pulse">🚨</div>
                    </div>
                </div>

                {/* Recent Orders List */}
                <div className="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                    <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h2 className="text-lg font-black tracking-tight text-slate-850">10 Pesanan Terakhir</h2>
                        <Link href="/customer/orders" className="text-xs font-bold text-red-500 uppercase hover:underline">
                            Lihat Semua
                        </Link>
                    </div>

                    <div className="divide-y divide-gray-105">
                        {recent_orders.length === 0 ? (
                            <div className="p-12 text-center text-gray-400 text-sm">
                                Belum ada riwayat pesanan ambulans.
                            </div>
                        ) : (
                            recent_orders.map((order) => (
                                <div key={order.id} className="p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:bg-gray-50/40 transition">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm font-black text-slate-850">ORDER #{order.id}</span>
                                            <span className="text-[10px] uppercase font-extrabold px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-600">
                                                {order.order_type}
                                            </span>
                                            <span className={`text-[10px] uppercase font-extrabold px-2.5 py-0.5 rounded-full ${
                                                order.status === 'completed' ? 'bg-emerald-50 text-emerald-600' :
                                                order.status === 'cancelled' ? 'bg-red-50 text-red-650' : 'bg-amber-50 text-amber-600 animate-pulse'
                                            }`}>
                                                {order.status}
                                            </span>
                                        </div>
                                        <div className="text-xs text-gray-500 mt-2 space-y-1">
                                            <p><span className="font-bold">Penjemputan:</span> {order.pickup_address}</p>
                                            {order.destination_address && (
                                                <p><span className="font-bold">Tujuan:</span> {order.destination_address}</p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Link
                                            href={`/orders/${order.id}/tracking`}
                                            className="bg-black text-white px-5 py-2.5 text-xs font-bold uppercase tracking-widest hover:bg-slate-900 transition"
                                        >
                                            Lacak / Detail
                                        </Link>
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
