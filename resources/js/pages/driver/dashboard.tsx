import { useState, useEffect, useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface DriverDashboardProps {
    ambulances: any[];
    currentUser: any;
}

// Koordinat rute simulasi di Kota Bandung (Gedung Sate -> Cihampelas Walk)
const SIMULATED_ROUTE = [
    [-6.902481, 107.618810], // Gedung Sate
    [-6.902000, 107.616000],
    [-6.901000, 107.613000],
    [-6.900000, 107.610000],
    [-6.898500, 107.608000],
    [-6.897500, 107.606000],
    [-6.896582, 107.604215], // Cihampelas Walk
];

export default function DriverDashboard({ ambulances, currentUser }: DriverDashboardProps) {
    // 1. Crew & Vehicle State
    const [assignedAmbulance, setAssignedAmbulance] = useState<any | null>(null);
    const [onlineStatus, setOnlineStatus] = useState<'standby' | 'offline'>('offline');
    const [selectedAmbulanceId, setSelectedAmbulanceId] = useState<number | null>(null);

    // 2. Active Trip State
    const [activeTrip, setActiveTrip] = useState<any | null>(null);
    const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    // 3. GPS Simulator State
    const [isSimulatingGps, setIsSimulatingGps] = useState(false);
    const simIndexRef = useRef(0);
    const simIntervalRef = useRef<NodeJS.Timeout | null>(null);

    // 4. GPS Riil State
    const [isBroadcastingRealGps, setIsBroadcastingRealGps] = useState(false);
    const watchIdRef = useRef<number | null>(null);

    // Deteksi ambulans yang sudah terikat pada user saat ini di awal
    useEffect(() => {
        if (currentUser) {
            const boundAmb = ambulances.find(
                (amb) => amb.current_driver_id === currentUser.id || amb.current_nurse_id === currentUser.id
            );
            if (boundAmb) {
                setAssignedAmbulance(boundAmb);
                setOnlineStatus(boundAmb.status);
            }
        }
    }, [currentUser, ambulances]);

    // 1. Polling: Deteksi order aktif baru setiap 5 detik
    useEffect(() => {
        if (!assignedAmbulance) return;

        const checkTrips = async () => {
            try {
                const res = await fetch('/api/driver/active-trips');
                const data = await res.json();
                if (data.status === 'success' && data.order) {
                    setActiveTrip(data.order);
                } else {
                    setActiveTrip(null);
                }
            } catch (err) {
                console.error("Gagal memeriksa tugas aktif:", err);
            }
        };

        checkTrips(); // Run immediately
        const interval = setInterval(checkTrips, 5000);

        return () => clearInterval(interval);
    }, [assignedAmbulance]);

    // 2. GPS Broadcaster Simulator
    useEffect(() => {
        if (isSimulatingGps && activeTrip) {
            // Jalankan simulator GPS bergerak
            simIntervalRef.current = setInterval(async () => {
                const routeLen = SIMULATED_ROUTE.length;
                const coords = SIMULATED_ROUTE[simIndexRef.current];

                try {
                    await fetch(`/api/orders/${activeTrip.id}/tracking`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            latitude: coords[0],
                            longitude: coords[1],
                            bearing: simIndexRef.current * 45 // Simulasi bearing berputar
                        })
                    });

                    // Increment index rute
                    simIndexRef.current = (simIndexRef.current + 1) % routeLen;
                } catch (err) {
                    console.error("Gagal menyiarkan GPS simulator:", err);
                }
            }, 3000);
        } else {
            if (simIntervalRef.current) {
                clearInterval(simIntervalRef.current);
                simIntervalRef.current = null;
            }
            simIndexRef.current = 0;
        }

        return () => {
            if (simIntervalRef.current) clearInterval(simIntervalRef.current);
        };
    }, [isSimulatingGps, activeTrip]);

    // 3. GPS Riil Broadcaster
    useEffect(() => {
        if (isBroadcastingRealGps && activeTrip) {
            if (navigator.geolocation) {
                watchIdRef.current = navigator.geolocation.watchPosition(
                    async (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const heading = position.coords.heading || 0;
                        
                        try {
                            await fetch(`/api/orders/${activeTrip.id}/tracking`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                body: JSON.stringify({
                                    latitude: lat,
                                    longitude: lng,
                                    bearing: heading
                                })
                            });
                        } catch (err) {
                            console.error("Gagal mengirim koordinat GPS riil:", err);
                        }
                    },
                    (error) => {
                        console.error("Gagal mendeteksi koordinat GPS riil:", error);
                        alert("Gagal membaca GPS riil perangkat Anda. Pastikan izin lokasi aktif.");
                        setIsBroadcastingRealGps(false);
                    },
                    { enableHighAccuracy: true, maximumAge: 1000, timeout: 5000 }
                );
            } else {
                alert("Browser ini tidak mendukung pembacaan GPS.");
                setIsBroadcastingRealGps(false);
            }
        } else {
            if (watchIdRef.current !== null) {
                navigator.geolocation.clearWatch(watchIdRef.current);
                watchIdRef.current = null;
            }
        }

        return () => {
            if (watchIdRef.current !== null) {
                navigator.geolocation.clearWatch(watchIdRef.current);
                watchIdRef.current = null;
            }
        };
    }, [isBroadcastingRealGps, activeTrip]);

    // Aksi 1: Ikat driver ke nomor plat ambulans
    const handleAssignAmbulance = async (e: React.FormEvent) => {
        e.preventDefault();
        setMessage(null);

        const targetId = selectedAmbulanceId || ambulances[0]?.id;
        if (!targetId) {
            setMessage({ type: 'error', text: 'Tidak ada armada ambulans yang tersedia.' });
            return;
        }

        try {
            const res = await fetch('/api/driver/assign-ambulance', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ ambulance_id: targetId })
            });
            const data = await res.json();
            if (res.ok) {
                setAssignedAmbulance(data.ambulance);
                setOnlineStatus(data.ambulance.status);
                setMessage({ type: 'success', text: data.message });
            } else {
                setMessage({ type: 'error', text: data.message || 'Gagal mengikat armada.' });
            }
        } catch (err) {
            setMessage({ type: 'error', text: 'Koneksi gagal ke server.' });
        }
    };

    // Aksi 2: Ubah status online (standby / offline)
    const handleToggleStatus = async (status: 'standby' | 'offline') => {
        setMessage(null);
        try {
            const res = await fetch('/api/driver/status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ status })
            });
            const data = await res.json();
            if (res.ok) {
                setOnlineStatus(status);
                setMessage({ type: 'success', text: data.message });
            } else {
                setMessage({ type: 'error', text: data.message || 'Gagal mengubah status.' });
            }
        } catch (err) {
            setMessage({ type: 'error', text: 'Koneksi gagal ke server.' });
        }
    };

    // Aksi 3: Perbarui Status Perjalanan (Trip Progress)
    const handleUpdateTripStatus = async (newStatus: string) => {
        if (!activeTrip) return;
        setMessage(null);

        try {
            const res = await fetch(`/api/orders/${activeTrip.id}/status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });
            const data = await res.json();
            if (res.ok) {
                setActiveTrip(data.order);
                setMessage({ type: 'success', text: data.message });
                
                // Matikan simulator jika perjalanan telah selesai/dibatalkan
                if (['completed', 'cancelled'].includes(newStatus)) {
                    setIsSimulatingGps(false);
                    setIsBroadcastingRealGps(false);
                    setActiveTrip(null);
                }
            } else {
                setMessage({ type: 'error', text: data.message || 'Gagal memperbarui status perjalanan.' });
            }
        } catch (err) {
            setMessage({ type: 'error', text: 'Koneksi gagal ke server.' });
        }
    };

    // Aksi 4: Terima Panggilan SOS (Accept SOS Trip)
    const handleAcceptTrip = async () => {
        if (!activeTrip) return;
        setMessage(null);

        try {
            const res = await fetch(`/api/orders/${activeTrip.id}/accept`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (res.ok) {
                setActiveTrip(data.order);
                setMessage({ type: 'success', text: data.message });
            } else {
                setMessage({ type: 'error', text: data.message || 'Gagal menerima panggilan darurat.' });
            }
        } catch (err) {
            setMessage({ type: 'error', text: 'Koneksi gagal ke server.' });
        }
    };

    return (
        <AppLayout activeMenu="dashboard">
            <Head title="Driver Dashboard PWA - AmbulanBDG" />
            <div className="w-full h-full overflow-y-auto p-6 max-w-md mx-auto space-y-6">
                    {/* Message Banner */}
                    {message && (
                        <div className={`p-4 rounded-xl text-xs font-semibold ${message.type === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30' : 'bg-teal-50 text-teal-700 dark:bg-teal-950/30'}`}>
                            {message.text}
                        </div>
                    )}

                    {/* TAHAP 1: IKAT ARMADA (Jika belum terikat) */}
                    {!assignedAmbulance ? (
                        <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-md dark:border-slate-800 dark:bg-slate-900 space-y-4">
                            <div className="text-center">
                                <h2 className="font-extrabold text-base">Ikat Unit Kendaraan</h2>
                                <p className="text-xs text-slate-500 mt-1">Pilih armada ambulans yang Anda bawa hari ini untuk memulai penerimaan tugas perjalanan.</p>
                            </div>

                            <form onSubmit={handleAssignAmbulance} className="space-y-4">
                                <div>
                                    <label className="block text-xs font-bold text-slate-400 uppercase mb-1">Pilih Plat Nomor</label>
                                    <select 
                                        onChange={(e) => setSelectedAmbulanceId(parseInt(e.target.value))}
                                        className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm bg-white dark:border-slate-800 dark:bg-slate-950 focus:outline-none"
                                    >
                                        {ambulances.map((amb) => (
                                            <option key={amb.id} value={amb.id}>
                                                {amb.plate_number} - {amb.category.toUpperCase()}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <button 
                                    type="submit"
                                    className="w-full bg-teal-600 hover:bg-teal-700 text-white rounded-lg py-2.5 text-xs font-bold shadow-md transition"
                                >
                                    Mulai Tugas & Ikat Armada
                                </button>
                            </form>
                        </div>
                    ) : (
                        /* TAHAP 2: KONTROL SIAGA & DETAIL ARMADA TERIKAT */
                        <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-md dark:border-slate-800 dark:bg-slate-900 space-y-4">
                            <div className="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
                                <div>
                                    <h3 className="font-extrabold text-sm">{assignedAmbulance.plate_number}</h3>
                                    <p className="text-[10px] text-slate-400 font-bold uppercase">{assignedAmbulance.category} ambulance</p>
                                </div>
                                <span className={`text-[10px] uppercase font-extrabold px-2.5 py-1 rounded-full ${onlineStatus === 'standby' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500'}`}>
                                    ● {onlineStatus}
                                </span>
                            </div>

                            <div className="flex gap-3">
                                <button 
                                    onClick={() => handleToggleStatus('standby')}
                                    className={`flex-1 py-2 text-xs font-bold rounded-lg transition ${onlineStatus === 'standby' ? 'bg-teal-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'}`}
                                >
                                    Mulai Siaga (Online)
                                </button>
                                <button 
                                    onClick={() => handleToggleStatus('offline')}
                                    className={`flex-1 py-2 text-xs font-bold rounded-lg transition ${onlineStatus === 'offline' ? 'bg-teal-800 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'}`}
                                >
                                    Offline
                                </button>
                            </div>
                        </div>
                    )}

                    {/* TAHAP 3: DETAIL TUGAS PERJALANAN AKTIF */}
                    {assignedAmbulance && (
                        <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-md dark:border-slate-800 dark:bg-slate-900 space-y-4">
                            <h3 className="font-extrabold text-sm border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                                <span className="h-2.5 w-2.5 rounded-full bg-red-500 animate-pulse"></span>
                                Tugas Perjalanan Aktif
                            </h3>

                            {activeTrip ? (
                                <div className="space-y-4">
                                    <div className="bg-teal-50/20 dark:bg-teal-950/10 p-4 rounded-xl space-y-2">
                                        <div className="flex justify-between text-xs font-bold text-teal-600 dark:text-teal-500">
                                            <span>ORDER ID: #{activeTrip.id}</span>
                                            <span>STATUS: {activeTrip.status.toUpperCase()}</span>
                                        </div>
                                        <div className="text-xs space-y-1">
                                            <p className="text-slate-500"><b className="text-slate-700">Jemput:</b> {activeTrip.pickup_address}</p>
                                            {activeTrip.destination_address && (
                                                <p className="text-slate-500"><b className="text-slate-700">Tujuan:</b> {activeTrip.destination_address}</p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Action Buttons based on context */}
                                    <div className="space-y-2">
                                        {activeTrip.status === 'awaiting_driver_response' && (
                                            <button 
                                                onClick={handleAcceptTrip}
                                                className="w-full bg-red-600 hover:bg-red-700 text-white rounded-lg py-2.5 text-xs font-bold shadow transition animate-pulse"
                                            >
                                                Terima Panggilan Darurat (SOS)
                                            </button>
                                        )}

                                        {activeTrip.status === 'confirmed' && (
                                            <button 
                                                onClick={() => handleUpdateTripStatus('on_the_way')}
                                                className="w-full bg-teal-600 hover:bg-teal-700 text-white rounded-lg py-2.5 text-xs font-bold shadow transition"
                                            >
                                                Mulai Menuju Lokasi Jemput
                                            </button>
                                        )}

                                        {activeTrip.status === 'on_the_way' && (
                                            <button 
                                                onClick={() => handleUpdateTripStatus('arrived')}
                                                className="w-full bg-teal-600 hover:bg-teal-700 text-white rounded-lg py-2.5 text-xs font-bold shadow transition"
                                            >
                                                Ambulans Tiba di Lokasi Jemput
                                            </button>
                                        )}

                                        {activeTrip.status === 'arrived' && (
                                            <button 
                                                onClick={() => handleUpdateTripStatus('transporting')}
                                                className="w-full bg-teal-600 hover:bg-teal-700 text-white rounded-lg py-2.5 text-xs font-bold shadow transition"
                                            >
                                                Mulai Antar Pasien ke RS
                                            </button>
                                        )}

                                        {activeTrip.status === 'transporting' && (
                                            <button 
                                                onClick={() => handleUpdateTripStatus('completed')}
                                                className="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg py-2.5 text-xs font-bold shadow transition"
                                            >
                                                Perjalanan Selesai
                                            </button>
                                        )}

                                        <button 
                                            onClick={() => handleUpdateTripStatus('cancelled')}
                                            className="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg py-2 text-xs font-bold transition"
                                        >
                                            Batalkan Tugas
                                        </button>
                                    </div>

                                    {/* GPS Broadcaster Control */}
                                    {['on_the_way', 'transporting'].includes(activeTrip.status) && (
                                        <div className="border border-slate-100 dark:border-slate-800 p-4 rounded-xl space-y-3 bg-slate-50/20 text-center">
                                            <span className="text-[10px] uppercase font-black text-slate-400 block tracking-wider">Pemancar Koordinat GPS</span>
                                            
                                            <div className="flex flex-col gap-2">
                                                {/* Option 1: Real GPS */}
                                                <button 
                                                    type="button"
                                                    onClick={() => {
                                                        if (!isBroadcastingRealGps) {
                                                            setIsSimulatingGps(false);
                                                        }
                                                        setIsBroadcastingRealGps(!isBroadcastingRealGps);
                                                    }}
                                                    className={`w-full py-2.5 rounded-lg text-xs font-black tracking-wide uppercase transition shadow-sm ${isBroadcastingRealGps ? 'bg-emerald-600 text-white animate-pulse' : 'bg-slate-800 text-white'}`}
                                                >
                                                    {isBroadcastingRealGps ? '⏹️ Hentikan Geolocation Riil' : '📡 Aktifkan GPS Riil Perangkat'}
                                                </button>

                                                {/* Option 2: Mock Simulator */}
                                                <button 
                                                    type="button"
                                                    onClick={() => {
                                                        if (!isSimulatingGps) {
                                                            setIsBroadcastingRealGps(false);
                                                        }
                                                        setIsSimulatingGps(!isSimulatingGps);
                                                    }}
                                                    className={`w-full py-2.5 rounded-lg text-xs font-black tracking-wide uppercase transition shadow-sm ${isSimulatingGps ? 'bg-teal-600 text-white animate-pulse' : 'bg-slate-200 text-slate-800 hover:bg-slate-300'}`}
                                                >
                                                    {isSimulatingGps ? '⏹️ Hentikan Simulator GPS' : '▶️ Aktifkan Simulator GPS Bergerak'}
                                                </button>
                                            </div>
                                            
                                            <span className="text-[10px] text-slate-450 block font-mono">
                                                {isBroadcastingRealGps && '✓ Geolocation Aktif: memancarkan koordinat fisik browser.'}
                                                {isSimulatingGps && '✓ Simulasi Aktif: rute Gedung Sate ke Cihampelas.'}
                                                {!isBroadcastingRealGps && !isSimulatingGps && 'Pemancar GPS dinonaktifkan.'}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <p className="text-xs text-slate-400 text-center py-6">Saat ini Anda tidak memiliki tugas aktif berjalan. Status armada Anda siaga di radar pemesanan.</p>
                            )}
                        </div>
                    )}
            </div>
        </AppLayout>
    );
}
