import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

export default function CustomerBooking() {
    return (
        <AppLayout activeMenu="booking">
            <Head title="Pesan Ambulans - AmbulanBDG" />
            
            <div className="max-w-4xl mx-auto px-6 py-12 space-y-8">
                <div className="text-center">
                    <h1 className="text-3xl font-black text-slate-800 tracking-tight">Pesan / Peminjaman Ambulans</h1>
                    <p className="text-gray-500 text-sm mt-2">Silakan pilih jenis layanan pemesanan yang sesuai dengan kebutuhan medis Anda.</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    {/* SOS Emergency */}
                    <div className="bg-red-50/30 p-8 rounded-3xl border border-red-100 flex flex-col justify-between space-y-6 shadow-sm hover:shadow-md transition">
                        <div>
                            <span className="text-2xl">🚨</span>
                            <h2 className="text-lg font-black text-red-650 tracking-tight mt-4">SOS / Gawat Darurat</h2>
                            <p className="text-xs text-gray-550 mt-2 leading-relaxed">
                                Butuh pertolongan darurat segera? Panggil unit ambulans terdekat dengan respons cepat otomatis gratis.
                            </p>
                        </div>
                        <Link
                            href="/"
                            className="bg-red-600 hover:bg-red-700 text-white rounded-xl py-2.5 text-center text-xs font-bold tracking-wider uppercase block"
                        >
                            Panggil SOS Sekarang
                        </Link>
                    </div>

                    {/* Sewa Non-Emergency */}
                    <div className="bg-blue-50/20 p-8 rounded-3xl border border-blue-100 flex flex-col justify-between space-y-6 shadow-sm hover:shadow-md transition">
                        <div>
                            <span className="text-2xl">🗓️</span>
                            <h2 className="text-lg font-black text-blue-650 tracking-tight mt-4">Sewa Terjadwal</h2>
                            <p className="text-xs text-gray-550 mt-2 leading-relaxed">
                                Jadwalkan kepulangan pasien atau transfer rumah sakit non-darurat. Terintegrasi E-Verifikasi BPJS.
                            </p>
                        </div>
                        <Link
                            href="/"
                            className="bg-slate-850 hover:bg-black text-white rounded-xl py-2.5 text-center text-xs font-bold tracking-wider uppercase block"
                        >
                            Jadwalkan Sewa
                        </Link>
                    </div>

                    {/* Event Siaga */}
                    <div className="bg-purple-50/20 p-8 rounded-3xl border border-purple-100 flex flex-col justify-between space-y-6 shadow-sm hover:shadow-md transition">
                        <div>
                            <span className="text-2xl">🎪</span>
                            <h2 className="text-lg font-black text-purple-650 tracking-tight mt-4">Siaga Acara / Event</h2>
                            <p className="text-xs text-gray-550 mt-2 leading-relaxed">
                                Peminjaman armada ambulans beserta kru medis untuk standby selama acara sosial, korporat, atau komunitas Anda.
                            </p>
                        </div>
                        <Link
                            href="/"
                            className="bg-purple-600 hover:bg-purple-700 text-white rounded-xl py-2.5 text-center text-xs font-bold tracking-wider uppercase block"
                        >
                            Ajukan Proposal
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
