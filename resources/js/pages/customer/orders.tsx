import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    orders: any[];
}

export default function CustomerOrders({ orders }: Props) {
    return (
        <AppLayout activeMenu="orders">
            <Head title="Pesanan Saya - AmbulanBDG" />
            
            <div className="max-w-6xl mx-auto px-6 py-10 space-y-8">
                <div>
                    <h1 className="text-2xl font-black text-slate-800 tracking-tight">Riwayat Pemesanan Anda</h1>
                    <p className="text-gray-500 text-xs mt-1">Daftar lengkap permohonan pemesanan, darurat, dan siaga acara Anda.</p>
                </div>

                <div className="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                    {orders.length === 0 ? (
                        <div className="p-16 text-center text-gray-400 text-sm">
                            Belum ada pemesanan ambulans yang dilakukan.
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-105">
                            {orders.map((order) => (
                                <div key={order.id} className="p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 hover:bg-gray-50/30 transition">
                                    <div className="space-y-2 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="text-sm font-black text-slate-850">ORDER #{order.id}</span>
                                            <span className="text-[10px] uppercase font-extrabold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-650">
                                                {order.order_type}
                                            </span>
                                            <span className={`text-[10px] uppercase font-extrabold px-2.5 py-0.5 rounded-full ${
                                                order.status === 'completed' ? 'bg-emerald-50 text-emerald-650' :
                                                order.status === 'cancelled' ? 'bg-red-50 text-red-650' : 'bg-amber-50 text-amber-600 animate-pulse'
                                            }`}>
                                                {order.status.toUpperCase()}
                                            </span>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-gray-550">
                                            <p><span className="font-bold text-gray-700">Titik Jemput:</span> {order.pickup_address}</p>
                                            {order.destination_address && (
                                                <p><span className="font-bold text-gray-700">Tujuan:</span> {order.destination_address}</p>
                                            )}
                                        </div>

                                        <div className="flex gap-6 text-[11px] text-gray-400 font-bold uppercase mt-2">
                                            <span>Tarif: Rp {parseFloat(order.fare_amount).toLocaleString('id-ID')}</span>
                                            {order.ambulance && (
                                                <span>Unit: {order.ambulance.plate_number} ({order.ambulance.institution?.name})</span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap gap-2 items-center">
                                        {order.status === 'completed' && !order.rating && (
                                            <Link
                                                href={`/orders/${order.id}/tracking`}
                                                className="border border-amber-500 text-amber-600 px-4 py-2 text-xs font-bold uppercase hover:bg-amber-500 hover:text-white transition"
                                            >
                                                Beri Rating
                                            </Link>
                                        )}
                                        {['completed', 'cancelled', 'no_ambulance_available'].includes(order.status) ? (
                                            <Link
                                                href={`/orders/${order.id}/detail`}
                                                className="bg-black text-white px-5 py-2.5 text-xs font-bold uppercase tracking-wider hover:bg-slate-900 transition"
                                            >
                                                Lihat Detail
                                            </Link>
                                        ) : (
                                            <Link
                                                href={`/orders/${order.id}/tracking`}
                                                className="bg-teal-600 text-white px-5 py-2.5 text-xs font-bold uppercase tracking-wider hover:bg-teal-700 transition"
                                            >
                                                Lacak Live
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
