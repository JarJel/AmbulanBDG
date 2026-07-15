import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    trips: any[];
}

export default function NurseHistory({ trips }: Props) {
    return (
        <AppLayout activeMenu="history">
            <Head title="Riwayat Pendampingan - Nurse" />
            
            <div className="max-w-md mx-auto px-4 py-8 space-y-6">
                <div>
                    <h1 className="text-xl font-black text-slate-800 tracking-tight">Riwayat Pendampingan</h1>
                    <p className="text-gray-500 text-[11px] mt-1">Daftar perjalanan medis gawat darurat (SOS) dan sewa yang telah selesai didampingi.</p>
                </div>

                <div className="bg-white rounded-2xl border border-gray-150 shadow-md overflow-hidden">
                    {trips.length === 0 ? (
                        <div className="p-12 text-center text-gray-400 text-xs">
                            Belum ada riwayat pendampingan medis.
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {trips.map((trip) => (
                                <Link 
                                    key={trip.id} 
                                    href={`/orders/${trip.id}/detail`}
                                    className="block p-4 space-y-2 hover:bg-slate-50 transition cursor-pointer"
                                >
                                    <div className="flex justify-between text-xs">
                                        <span className="font-extrabold text-slate-850">TRIP #{trip.id}</span>
                                        <span className="font-mono text-gray-400">{new Date(trip.created_at).toLocaleDateString('id-ID')}</span>
                                    </div>
                                    <div className="text-[11px] text-gray-500 space-y-1">
                                        <p><b>Jemput:</b> {trip.pickup_address}</p>
                                        {trip.destination_address && (
                                            <p><b>Tujuan:</b> {trip.destination_address}</p>
                                        )}
                                        {trip.patient_condition && (
                                            <p className="text-slate-700 bg-gray-50 p-2 rounded mt-1 font-semibold">
                                                📝 <b>Catatan Medis:</b> {trip.patient_condition}
                                            </p>
                                        )}
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
