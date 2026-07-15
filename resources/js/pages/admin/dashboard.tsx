import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    institution: {
        id: number;
        name: string;
        type: 'fasyankes' | 'swasta' | 'relawan';
        verified_status: string;
    };
    stats: {
        total_ambulances: number;
        total_orders: number;
        pending_proposals: number;
        staff_count: number;
    };
}

export default function AdminDashboard({ institution, stats }: Props) {
    return (
        <AppLayout activeMenu="dashboard">
            <Head title={`Dashboard Admin - ${institution.name}`} />
            
            <div className="max-w-7xl mx-auto px-6 py-10 space-y-10">
                {/* Header Welcome Scoped */}
                <div className="bg-gradient-to-r from-blue-700 to-blue-500 text-white p-8 rounded-3xl shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 className="text-3xl font-black tracking-tight">{institution.name}</h1>
                        <p className="text-sm opacity-90 mt-2">
                            Dashboard Manajemen Mitra — Tipe: <span className="underline font-bold uppercase">{institution.type}</span>.
                        </p>
                    </div>
                    <span className="bg-white/20 text-white border border-white/30 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider">
                        {institution.verified_status} Mitra
                    </span>
                </div>

                {/* Specific features warnings based on type */}
                {institution.type === 'fasyankes' && (
                    <div className="p-4 bg-emerald-50 border border-emerald-250 text-emerald-800 rounded-2xl text-xs font-semibold">
                        💡 <b>Fitur Utama: E-Verifikasi BPJS Aktif</b>. Seluruh armada instansi Anda terdaftar sebagai <b>SOS Free Service Eligible</b>. Klaim rujukan gratis tervalidasi menggunakan validasi BPJS.
                    </div>
                )}
                {institution.type === 'swasta' && (
                    <div className="p-4 bg-purple-50 border border-purple-250 text-purple-800 rounded-2xl text-xs font-semibold">
                        💡 <b>Aturan Bisnis: Layanan Swasta Berbayar</b>. Semua armada instansi Anda tidak di-assign secara otomatis pada order SOS gratis. Silakan optimalkan manajemen tarif komersial Anda di menu Kelola Armada.
                    </div>
                )}
                {institution.type === 'relawan' && (
                    <div className="p-4 bg-orange-50 border border-orange-250 text-orange-850 rounded-2xl text-xs font-semibold">
                        💡 <b>Layanan Sosial / Relawan (PMI)</b>. Seluruh armada terdaftar sebagai <b>SOS Free Service Eligible</b> (tulang punggung darurat). Fitur konfigurasi tarif dinonaktifkan secara otomatis demi kegiatan kemanusiaan.
                    </div>
                )}

                {/* Stats cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-md hover:scale-[1.02] transition">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Armada Dikelola</span>
                        <span className="text-3xl font-black text-slate-800 mt-2 block">{stats.total_ambulances} Unit</span>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-md hover:scale-[1.02] transition">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Total Perjalanan</span>
                        <span className="text-3xl font-black text-slate-800 mt-2 block">{stats.total_orders} Trip</span>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-md hover:scale-[1.02] transition">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Proposal Masuk</span>
                        <span className="text-3xl font-black text-slate-800 mt-2 block">{stats.pending_proposals} Berkas</span>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-md hover:scale-[1.02] transition">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Jumlah Staf (Kru)</span>
                        <span className="text-3xl font-black text-slate-800 mt-2 block">{stats.staff_count} Anggota</span>
                    </div>
                </div>

                {/* Quick actions for staff */}
                <div className="bg-white rounded-3xl border border-gray-100 shadow-xl p-8 space-y-6">
                    <h2 className="text-md font-black uppercase tracking-wider text-slate-850">Aksi Cepat Menu</h2>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Link href="/admin/ambulances" className="border border-blue-500 text-blue-600 rounded-xl p-4 text-center text-xs font-bold uppercase hover:bg-blue-500 hover:text-white transition">
                            Kelola Armada & Verifikasi KIR/STNK
                        </Link>
                        <Link href="/admin/staff" className="border border-blue-500 text-blue-600 rounded-xl p-4 text-center text-xs font-bold uppercase hover:bg-blue-500 hover:text-white transition">
                            Kelola Staff & Verifikasi SIM/STR
                        </Link>
                        <Link href="/admin/proposals" className="border border-blue-500 text-blue-600 rounded-xl p-4 text-center text-xs font-bold uppercase hover:bg-blue-500 hover:text-white transition">
                            Tinjau Proposal Event
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
