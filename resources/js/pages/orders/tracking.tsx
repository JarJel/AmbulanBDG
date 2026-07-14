import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import AppLayout from '../../layouts/AppLayout';
import Map, { MapMarker } from '../../components/Map';

interface TrackingProps {
    order: any;
}

export default function Tracking({ order }: TrackingProps) {
    const echo = useEcho();

    // 1. Order & Location States
    const [orderData, setOrderData] = useState(order);
    const [ambulanceCoords, setAmbulanceCoords] = useState<[number, number] | null>(null);
    const [trackingSignalLost, setTrackingSignalLost] = useState(!!order.tracking_lost_notified_at);

    // 2. Rating Form States
    const [ratingValue, setRatingValue] = useState(5);
    const [reviewText, setReviewText] = useState('');
    const [ratingSubmitted, setRatingSubmitted] = useState(false);
    const [ratingMessage, setRatingMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    // Sinkronisasi data saat Inertia reload prop 'order'
    useEffect(() => {
        setOrderData(order);
        setTrackingSignalLost(!!order.tracking_lost_notified_at);
    }, [order]);

    // Parse koordinat ambulans & tujuan di awal
    useEffect(() => {
        if (orderData.ambulance) {
            setAmbulanceCoords([
                orderData.ambulance.latitude ? parseFloat(orderData.ambulance.latitude) : -6.917,
                orderData.ambulance.longitude ? parseFloat(orderData.ambulance.longitude) : 107.619
            ]);
        }
    }, [orderData]);

    // 1. Polling Hibrida: Perbarui status order tiap 7 detik
    useEffect(() => {
        // Jangan melakukan polling jika order sudah selesai/batal
        if (['completed', 'cancelled'].includes(orderData.status)) return;

        const interval = setInterval(() => {
            router.reload({
                only: ['order'],
                preserveScroll: true
            });
        }, 7000);

        return () => clearInterval(interval);
    }, [orderData.status]);

    // 2. WebSocket Listener: Berlangganan koordinat GPS ter-update dari driver
    useEffect(() => {
        if (!echo || ['completed', 'cancelled'].includes(orderData.status)) return;

        const channel = echo.private(`orders.${orderData.id}`)
            .listen('.location.updated', (e: any) => {
                setAmbulanceCoords([parseFloat(e.latitude), parseFloat(e.longitude)]);
                setTrackingSignalLost(false);
            })
            .listen('.status.updated', (e: any) => {
                setOrderData((prev: any) => ({
                    ...prev,
                    status: e.status,
                    ambulance: e.ambulance ?? prev.ambulance
                }));
                setTrackingSignalLost(false);
            })
            .listen('.signal.lost', (e: any) => {
                setTrackingSignalLost(true);
            });

        return () => {
            channel.stopListening('.location.updated');
            channel.stopListening('.status.updated');
            channel.stopListening('.signal.lost');
        };
    }, [echo, orderData.id, orderData.status]);

    // Ekstraksi Koordinat User (Pickup) & Destination
    const getCoords = (point: any): [number, number] | null => {
        if (!point) return null;
        return [
            parseFloat(point.y ?? point.latitude ?? -6.917),
            parseFloat(point.x ?? point.longitude ?? 107.619)
        ];
    };

    const userCoords = getCoords(orderData.pickup_location) || [-6.917464, 107.619123];
    const destinationCoords = getCoords(orderData.destination_location);

    // Kumpulkan markers peta
    const markers: MapMarker[] = [
        // Marker User (Lokasi Jemput)
        {
            position: userCoords,
            title: 'Titik Penjemputan',
            popup: `<b>Penjemputan:</b><br/>${orderData.pickup_address}`,
            iconType: 'user'
        }
    ];

    // Marker Rumah Sakit / Tujuan
    if (destinationCoords) {
        markers.push({
            position: destinationCoords,
            title: 'Titik Tujuan',
            popup: `<b>Tujuan:</b><br/>${orderData.destination_address || 'Fasyankes Tujuan'}`,
            iconType: 'user' // Bisa pake user or default
        });
    }

    // Marker Ambulans Terkini
    if (ambulanceCoords) {
        markers.push({
            position: ambulanceCoords,
            title: `Ambulans ${orderData.ambulance?.plate_number || ''}`,
            popup: `<b>Ambulans Siaga</b><br/>Status: ${orderData.status}`,
            iconType: orderData.ambulance?.category === 'emergency' ? 'ambulance_emergency' : 'ambulance_non_emergency'
        });
    }

    // Centering Map (Prioritaskan lokasi ambulans, fallback ke user)
    const mapCenter = ambulanceCoords || userCoords;

    // Hitung bintang rating yang diklik
    const handleRatingSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setRatingMessage(null);

        try {
            const res = await fetch(`/api/orders/${orderData.id}/rating`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    rating_value: ratingValue,
                    review_text: reviewText
                })
            });
            const data = await res.json();
            if (res.status === 201) {
                setRatingSubmitted(true);
                setRatingMessage({ type: 'success', text: 'Terima kasih atas ulasan Anda!' });
            } else {
                setRatingMessage({ type: 'error', text: data.message || 'Gagal mengirimkan ulasan.' });
            }
        } catch (err) {
            setRatingMessage({ type: 'error', text: 'Koneksi gagal ke server.' });
        }
    };

    // Fungsi pemetaan status human readable
    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'pending': return 'Menunggu Konfirmasi';
            case 'awaiting_driver_response': return 'Menunggu Konfirmasi Driver (SOS)';
            case 'no_ambulance_available': return 'Tidak Ada Ambulans Tersedia (Hubungi 112/119)';
            case 'confirmed': return 'Telah Dikonfirmasi (Driver Sedang Siaga)';
            case 'on_the_way': return 'Ambulans Sedang Menuju ke Lokasi Anda';
            case 'arrived': return 'Ambulans Telah Tiba di Lokasi Anda';
            case 'transporting': return 'Dalam Perjalanan Menuju Rumah Sakit/Tujuan';
            case 'completed': return 'Perjalanan Selesai';
            case 'cancelled': return 'Dibatalkan';
            default: return status;
        }
    };

    return (
        <AppLayout activeMenu="booking">
            <Head title={`Live Tracking Order #${orderData.id} - AmbulanBDG`} />
            <div className="flex flex-col lg:flex-row gap-6 p-6 w-full h-full overflow-y-auto lg:overflow-hidden">
                {/* Left Column: Tracking Map */}
                <div className="flex-1 flex flex-col bg-white rounded-2xl border border-slate-200 shadow-lg dark:border-slate-800 dark:bg-slate-900 overflow-hidden relative min-h-[400px] lg:h-full">
                    <div className="flex-1 w-full h-full">
                        <Map center={mapCenter} zoom={14} markers={markers} />
                    </div>
                </div>

                {/* Right Column: Tracking Status & Details */}
                <div className="w-full lg:w-[450px] shrink-0 flex flex-col bg-white rounded-2xl border border-slate-200 shadow-lg dark:border-slate-800 dark:bg-slate-900 overflow-y-auto p-6 space-y-6 lg:h-full">
                        {/* Status Card */}
                        <div className="bg-teal-50 dark:bg-teal-950/20 p-5 rounded-2xl border border-teal-100/50 dark:border-teal-900/30 text-center space-y-2">
                            <span className="text-[10px] uppercase font-extrabold tracking-wider text-teal-500 dark:text-teal-400">Status Perjalanan</span>
                            <h2 className="font-extrabold text-lg text-teal-700 dark:text-teal-400">
                                {getStatusLabel(orderData.status)}
                            </h2>
                            {['confirmed', 'on_the_way', 'arrived', 'transporting'].includes(orderData.status) && (
                                <p className="text-[11px] text-slate-500">Mencari koordinat ter-update secara otomatis dari driver...</p>
                            )}
                        </div>

                        {/* Stale Tracking Signal Lost Alert */}
                        {trackingSignalLost && (
                            <div className="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 rounded-2xl p-4 text-xs space-y-2">
                                <div className="flex items-center gap-2 text-amber-700 dark:text-amber-300 font-bold">
                                    <span className="h-2 w-2 rounded-full bg-amber-500 animate-ping"></span>
                                    <span>Sinyal GPS Driver Terputus Sementara</span>
                                </div>
                                <p className="text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Driver kemungkinan berada di area basement rumah sakit, terowongan, atau zona sinyal lemah. Peta tetap menampilkan koordinat terakhir yang terdeteksi. Tim operasional kami terus memantau pergerakan unit via radio komunikasi darurat.
                                </p>
                            </div>
                        )}

                        {/* Driver & Nurse Crew Details */}
                        {orderData.ambulance && (
                            <div className="border border-slate-100 dark:border-slate-800 p-4 rounded-xl space-y-3 bg-slate-50/50 dark:bg-slate-900/30">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400">Informasi Kru & Armada</h3>
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">Nomor Plat</span>
                                        <span className="font-bold text-slate-800 dark:text-slate-200">{orderData.ambulance.plate_number}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">Kategori</span>
                                        <span className="font-medium">{orderData.ambulance.category === 'emergency' ? '🚨 Gawat Darurat' : '🟢 Transportasi / Sewa'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">Pengemudi (Driver)</span>
                                        <span className="font-medium text-slate-800 dark:text-slate-200">{orderData.ambulance.current_driver?.name || 'Menunggu Penugasan'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">Perawat (Nurse)</span>
                                        <span className="font-medium text-slate-800 dark:text-slate-200">{orderData.ambulance.current_nurse?.name || 'Tidak ada perawat'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">Fasyankes Penanggung</span>
                                        <span className="font-medium">{orderData.ambulance.institution?.name || '-'}</span>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Order Fare & Payment Info */}
                        <div className="border border-slate-100 dark:border-slate-800 p-4 rounded-xl space-y-3 bg-slate-50/50 dark:bg-slate-900/30">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400">Detail Pemesanan</h3>
                            <div className="space-y-2 text-xs">
                                <div>
                                    <span className="text-slate-400 block font-bold">ALAMAT JEMPUT</span>
                                    <span className="text-slate-700 dark:text-slate-300 font-medium block mt-0.5">{orderData.pickup_address}</span>
                                </div>
                                {orderData.destination_address && (
                                    <div>
                                        <span className="text-slate-400 block font-bold">ALAMAT TUJUAN</span>
                                        <span className="text-slate-700 dark:text-slate-300 font-medium block mt-0.5">{orderData.destination_address}</span>
                                    </div>
                                )}
                                <div className="border-t border-slate-200/50 dark:border-slate-800 pt-2 flex justify-between items-center text-sm">
                                    <span className="text-slate-500 font-medium">Total Tarif Perjalanan</span>
                                    <span className="font-extrabold text-teal-600 dark:text-teal-500 text-lg">
                                        {orderData.fare_amount == 0 ? 'GRATIS (Klaim BPJS/SOS)' : `Rp ${parseFloat(orderData.fare_amount).toLocaleString('id-ID')}`}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Modul 8 Rating & Feedback Widget (Muncul jika status completed) */}
                        {orderData.status === 'completed' && (
                            <div className="border-2 border-teal-100 dark:border-teal-950 p-5 rounded-2xl bg-teal-50/20 dark:bg-teal-950/10 space-y-4">
                                <h3 className="text-center font-extrabold text-sm text-teal-600 dark:text-teal-500 tracking-tight">Bagaimana Layanan Kami?</h3>
                                {ratingSubmitted ? (
                                    <div className="text-center text-xs font-semibold text-emerald-600 dark:text-emerald-400 py-2">
                                        ✓ Ulasan Anda telah berhasil dikirim. Terima kasih atas masukan Anda!
                                    </div>
                                ) : (
                                    <form onSubmit={handleRatingSubmit} className="space-y-4">
                                        {ratingMessage && (
                                            <div className="p-3 rounded-lg text-xs font-semibold bg-teal-50 text-teal-600">
                                                {ratingMessage.text}
                                            </div>
                                        )}
                                        {/* Star Selector */}
                                        <div className="flex justify-center items-center gap-2">
                                            {[1, 2, 3, 4, 5].map((val) => (
                                                <button
                                                    key={val}
                                                    type="button"
                                                    onClick={() => setRatingValue(val)}
                                                    className="focus:outline-none hover:scale-110 transition"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        fill={val <= ratingValue ? '#EAB308' : 'none'}
                                                        stroke={val <= ratingValue ? '#EAB308' : '#94A3B8'}
                                                        strokeWidth="2"
                                                        className="w-8 h-8"
                                                    >
                                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                                                    </svg>
                                                </button>
                                            ))}
                                        </div>

                                        {/* Review text */}
                                        <div>
                                            <textarea
                                                rows={2}
                                                placeholder="Tulis kritik, saran, atau pujian Anda di sini..."
                                                value={reviewText}
                                                onChange={(e) => setReviewText(e.target.value)}
                                                className="w-full text-xs rounded-lg border border-slate-200 p-2.5 bg-white dark:border-slate-800 dark:bg-slate-950 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                            />
                                        </div>

                                        <button
                                            type="submit"
                                            className="w-full bg-teal-600 hover:bg-teal-700 text-white rounded-lg py-2 text-xs font-bold transition shadow-sm"
                                        >
                                            Kirim Ulasan
                                        </button>
                                    </form>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </AppLayout>
    );
}
