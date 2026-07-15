import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    currentUser: any;
    boundAmbulance: any;
}

export default function NurseDashboard({ currentUser, boundAmbulance }: Props) {
    const [assignedAmbulance, setAssignedAmbulance] = useState<any | null>(boundAmbulance);
    const [activeTrip, setActiveTrip] = useState<any | null>(null);
    const [patientCondition, setPatientCondition] = useState<string>('');
    const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    // Polling active trip
    useEffect(() => {
        if (!assignedAmbulance) return;

        const checkTrips = async () => {
            try {
                const res = await fetch('/api/driver/active-trips');
                const data = await res.json();
                if (data.status === 'success' && data.order) {
                    setActiveTrip(data.order);
                    setPatientCondition(data.order.patient_condition || '');
                } else {
                    setActiveTrip(null);
                }
            } catch (err) {
                console.error("Gagal memeriksa tugas aktif:", err);
            }
        };

        checkTrips();
        const interval = setInterval(checkTrips, 5000);

        return () => clearInterval(interval);
    }, [assignedAmbulance]);

    const handleSaveCondition = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!activeTrip) return;
        setMessage(null);

        try {
            const res = await fetch(`/api/orders/${activeTrip.id}/patient-condition`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ patient_condition: patientCondition }),
            });
            const data = await res.json();
            if (res.ok) {
                setMessage({ type: 'success', text: 'Kondisi pasien berhasil disimpan.' });
                setActiveTrip(data.order);
            } else {
                setMessage({ type: 'error', text: data.message || 'Gagal menyimpan catatan.' });
            }
        } catch (err) {
            setMessage({ type: 'error', text: 'Kesalahan jaringan.' });
        }
    };

    return (
        <AppLayout activeMenu="dashboard">
            <Head title="Nurse Dashboard - AmbulanBDG" />
            
            <div className="max-w-md mx-auto px-4 py-8 space-y-6">
                {/* Greeting */}
                <div className="bg-slate-900 text-white p-6 rounded-2xl shadow-lg">
                    <h1 className="text-xl font-black uppercase">Nurse Portal</h1>
                    <p className="text-[11px] opacity-75 mt-1">Layanan Pendamping Medis Terintegrasi</p>
                </div>

                {message && (
                    <div className={`p-4 rounded-xl text-xs font-semibold ${
                        message.type === 'success' ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-650'
                    }`}>
                        {message.text}
                    </div>
                )}

                {/* Assigned Ambulance Status */}
                {assignedAmbulance ? (
                    <div className="bg-white p-6 rounded-2xl border border-gray-150 shadow-md space-y-2">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Armada Terikat</span>
                        <h2 className="text-lg font-black text-slate-800 tracking-tight">{assignedAmbulance.plate_number}</h2>
                        <span className="inline-block px-2.5 py-0.5 rounded-full text-[9px] uppercase font-black bg-gray-105 text-gray-500">
                            {assignedAmbulance.category} ambulance
                        </span>
                    </div>
                ) : (
                    <div className="bg-white p-6 rounded-2xl border border-gray-150 shadow-md text-center py-10">
                        <span className="text-gray-400 text-xs">Anda belum terikat ke armada ambulans apa pun. Hubungi admin institusi Anda.</span>
                    </div>
                )}

                {/* Active Trip Info and Patient Condition reporting */}
                {assignedAmbulance && (
                    <div className="bg-white p-6 rounded-2xl border border-gray-150 shadow-md space-y-4">
                        <h3 className="font-extrabold text-sm border-b pb-3 text-slate-850 flex items-center gap-1.5">
                            <span className="h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                            Tugas Medis Pendampingan
                        </h3>

                        {activeTrip ? (
                            <div className="space-y-4">
                                <div className="bg-blue-50/20 p-4 rounded-xl space-y-2">
                                    <div className="flex justify-between text-xs font-bold text-blue-600">
                                        <span>TRIP #{activeTrip.id}</span>
                                        <span className="uppercase">{activeTrip.status}</span>
                                    </div>
                                    <div className="text-xs text-gray-650 space-y-1">
                                        <p><b>Jemput:</b> {activeTrip.pickup_address}</p>
                                        {activeTrip.destination_address && (
                                            <p><b>Tujuan:</b> {activeTrip.destination_address}</p>
                                        )}
                                    </div>
                                </div>

                                <form onSubmit={handleSaveCondition} className="space-y-3">
                                    <div>
                                        <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1.5">Catatan Kondisi Pasien</label>
                                        <textarea
                                            value={patientCondition}
                                            onChange={(e) => setPatientCondition(e.target.value)}
                                            placeholder="Masukkan kondisi ringkas pasien..."
                                            className="w-full text-xs border border-gray-200 rounded-xl p-3 focus:outline-none focus:border-slate-400 min-h-[100px]"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        className="w-full bg-slate-900 text-white rounded-xl py-2.5 text-xs font-bold uppercase tracking-wider hover:bg-slate-800 transition"
                                    >
                                        Simpan Catatan Pasien
                                    </button>
                                </form>
                            </div>
                        ) : (
                            <p className="text-xs text-gray-400 text-center py-6">Tidak ada tugas aktif atau panggilan darurat saat ini.</p>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
