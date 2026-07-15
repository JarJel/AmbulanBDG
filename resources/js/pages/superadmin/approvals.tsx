import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { useEcho } from '@laravel/echo-react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    pendingAmbulances: any[];
    pendingStaff: any[];
}

export default function SuperAdminApprovals({ pendingAmbulances, pendingStaff }: Props) {
    const [ambulances, setAmbulances] = useState(pendingAmbulances);
    const [staff, setStaff] = useState(pendingStaff);
    const [message, setMessage] = useState<string | null>(null);

    // Selection states
    const [selectedAmbs, setSelectedAmbs] = useState<number[]>([]);
    const [selectedStaff, setSelectedStaff] = useState<number[]>([]);

    // Rejection Modal states
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [rejectType, setRejectType] = useState<'ambulance' | 'staff'>('ambulance');
    const [rejectionNotes, setRejectionNotes] = useState('');

    // Listener Real-time Reverb for notifications/updates (dynamic insertion)
    useEcho(
        'superadmin.alerts',
        '.document.uploaded',
        (e: any) => {
            // Reload page or dynamically fetch state to keep lists alive
            window.location.reload();
        },
        []
    );

    // Handle Bulk Action (Approve / Open Reject Dialog)
    const handleBulkApprove = async (type: 'ambulance' | 'staff') => {
        const ids = type === 'ambulance' ? selectedAmbs : selectedStaff;
        if (ids.length === 0) return;

        const targets = ids.map(id => ({
            id,
            type,
            status: 'verified'
        }));

        submitBulkAction(targets);
    };

    const handleOpenRejectModal = (type: 'ambulance' | 'staff') => {
        const ids = type === 'ambulance' ? selectedAmbs : selectedStaff;
        if (ids.length === 0) return;

        setRejectType(type);
        setRejectionNotes('');
        setShowRejectModal(true);
    };

    const handleConfirmReject = () => {
        const ids = rejectType === 'ambulance' ? selectedAmbs : selectedStaff;
        if (ids.length === 0 || !rejectionNotes.trim()) {
            alert('Catatan alasan reject wajib diisi!');
            return;
        }

        const targets = ids.map(id => ({
            id,
            type: rejectType,
            status: 'rejected',
            notes: rejectionNotes
        }));

        submitBulkAction(targets);
        setShowRejectModal(false);
    };

    const submitBulkAction = async (targets: any[]) => {
        setMessage(null);
        try {
            const res = await fetch('/api/admin/approvals/bulk', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ targets })
            });
            const data = await res.json();
            if (res.ok) {
                setMessage(data.message);
                // Clear selections
                setSelectedAmbs([]);
                setSelectedStaff([]);
                // Reload list
                window.location.reload();
            } else {
                setMessage(data.message || 'Gagal memproses persetujuan massal.');
            }
        } catch (err) {
            setMessage('Kesalahan koneksi ke server.');
        }
    };

    // Toggle Multi-select selections
    const toggleSelectAllAmbs = () => {
        if (selectedAmbs.length === ambulances.length) {
            setSelectedAmbs([]);
        } else {
            setSelectedAmbs(ambulances.map(a => a.id));
        }
    };

    const toggleSelectAmb = (id: number) => {
        if (selectedAmbs.includes(id)) {
            setSelectedAmbs(selectedAmbs.filter(x => x !== id));
        } else {
            setSelectedAmbs([...selectedAmbs, id]);
        }
    };

    const toggleSelectAllStaff = () => {
        if (selectedStaff.length === staff.length) {
            setSelectedStaff([]);
        } else {
            setSelectedStaff(staff.map(s => s.id));
        }
    };

    const toggleSelectStaff = (id: number) => {
        if (selectedStaff.includes(id)) {
            setSelectedStaff(selectedStaff.filter(x => x !== id));
        } else {
            setSelectedStaff([...selectedStaff, id]);
        }
    };

    return (
        <AppLayout activeMenu="approvals">
            <Head title="Verifikasi Pendaftaran Mitra - Dinkes" />

            <div className="max-w-7xl mx-auto px-6 py-10 space-y-10">
                <div>
                    <h1 className="text-2xl font-black text-slate-800 tracking-tight">Antrean Verifikasi Mitra</h1>
                    <p className="text-gray-500 text-xs mt-1">Review dokumen STNK, KIR, Surat Izin Operasional armada, serta STR & SIM kru medis baru.</p>
                </div>

                {message && (
                    <div className="p-4 bg-teal-50 text-teal-800 rounded-xl text-xs font-semibold">
                        {message}
                    </div>
                )}

                {/* QUEUE 1: AMBULANS PENDING VERIFICATION */}
                <div className="space-y-4">
                    <div className="flex justify-between items-center">
                        <h2 className="font-extrabold text-sm border-l-4 border-teal-500 pl-3">🚑 Antrean Verifikasi Kendaraan ({ambulances.length})</h2>
                        {selectedAmbs.length > 0 && (
                            <div className="flex gap-2">
                                <button 
                                    onClick={() => handleBulkApprove('ambulance')}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase transition"
                                >
                                    Setujui Terpilih ({selectedAmbs.length})
                                </button>
                                <button 
                                    onClick={() => handleOpenRejectModal('ambulance')}
                                    className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase transition"
                                >
                                    Tolak Terpilih ({selectedAmbs.length})
                                </button>
                            </div>
                        )}
                    </div>

                    <div className="bg-white rounded-3xl border border-gray-150 shadow-md overflow-hidden">
                        <table className="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr className="bg-gray-50 border-b border-gray-150 text-[10px] font-black uppercase text-gray-500 tracking-wider">
                                    <th className="p-4 w-12 text-center">
                                        <input 
                                            type="checkbox" 
                                            checked={ambulances.length > 0 && selectedAmbs.length === ambulances.length} 
                                            onChange={toggleSelectAllAmbs} 
                                        />
                                    </th>
                                    <th className="p-4">Plat Nomor</th>
                                    <th className="p-4">Brand / Tahun</th>
                                    <th className="p-4">Instansi</th>
                                    <th className="p-4">Kategori</th>
                                    <th className="p-4">Tarif</th>
                                    <th className="p-4">Berkas Pendukung</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-105 text-slate-700">
                                {ambulances.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="p-6 text-center text-slate-400 italic">Antrean kendaraan kosong.</td>
                                    </tr>
                                ) : (
                                    ambulances.map((amb) => (
                                        <tr key={amb.id} className="hover:bg-slate-50/30">
                                            <td className="p-4 text-center">
                                                <input 
                                                    type="checkbox" 
                                                    checked={selectedAmbs.includes(amb.id)} 
                                                    onChange={() => toggleSelectAmb(amb.id)} 
                                                />
                                            </td>
                                            <td className="p-4">
                                                <span className="font-black block">{amb.plate_number}</span>
                                                <span className="text-[10px] text-slate-450 block mt-0.5 font-medium">
                                                    📅 {new Date(amb.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                                                </span>
                                            </td>
                                            <td className="p-4 font-medium">{amb.brand} ({amb.year})</td>
                                            <td className="p-4">{amb.institution?.name || '-'}</td>
                                            <td className="p-4 font-bold uppercase">{amb.category}</td>
                                            <td className="p-4 font-semibold text-teal-600">Rp {parseFloat(amb.base_fare).toLocaleString('id-ID')}</td>
                                            <td className="p-4">
                                                <div className="flex gap-2 flex-wrap">
                                                    {amb.documents?.map((doc: any) => (
                                                        <a 
                                                            key={doc.id} 
                                                            href={`/storage/${doc.file_path}`} 
                                                            target="_blank" 
                                                            className="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-0.5 rounded text-[10px] font-bold inline-block border"
                                                        >
                                                            📄 {doc.document_type}
                                                        </a>
                                                    ))}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* QUEUE 2: STAFF (DRIVER / NURSE) PENDING VERIFICATION */}
                <div className="space-y-4">
                    <div className="flex justify-between items-center">
                        <h2 className="font-extrabold text-sm border-l-4 border-teal-500 pl-3">🩺 Antrean Verifikasi Kru Medis & Pengemudi ({staff.length})</h2>
                        {selectedStaff.length > 0 && (
                            <div className="flex gap-2">
                                <button 
                                    onClick={() => handleBulkApprove('staff')}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase transition"
                                >
                                    Setujui Terpilih ({selectedStaff.length})
                                </button>
                                <button 
                                    onClick={() => handleOpenRejectModal('staff')}
                                    className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase transition"
                                >
                                    Tolak Terpilih ({selectedStaff.length})
                                </button>
                            </div>
                        )}
                    </div>

                    <div className="bg-white rounded-3xl border border-gray-150 shadow-md overflow-hidden">
                        <table className="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr className="bg-gray-50 border-b border-gray-150 text-[10px] font-black uppercase text-gray-500 tracking-wider">
                                    <th className="p-4 w-12 text-center">
                                        <input 
                                            type="checkbox" 
                                            checked={staff.length > 0 && selectedStaff.length === staff.length} 
                                            onChange={toggleSelectAllStaff} 
                                        />
                                    </th>
                                    <th className="p-4">Nama Staf</th>
                                    <th className="p-4">NIK</th>
                                    <th className="p-4">Role</th>
                                    <th className="p-4">Instansi Asal</th>
                                    <th className="p-4">Nomor HP</th>
                                    <th className="p-4">Lisensi & Sertifikat</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-105 text-slate-700">
                                {staff.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="p-6 text-center text-slate-400 italic">Antrean lisensi kru kosong.</td>
                                    </tr>
                                ) : (
                                    staff.map((s) => (
                                        <tr key={s.id} className="hover:bg-slate-50/30">
                                            <td className="p-4 text-center">
                                                <input 
                                                    type="checkbox" 
                                                    checked={selectedStaff.includes(s.id)} 
                                                    onChange={() => toggleSelectStaff(s.id)} 
                                                />
                                            </td>
                                            <td className="p-4">
                                                <span className="font-black block">{s.name}</span>
                                                <span className="text-[10px] text-slate-450 block mt-0.5 font-medium">
                                                    📅 {new Date(s.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                                                </span>
                                            </td>
                                            <td className="p-4 font-mono font-bold text-slate-600">{s.nik || '-'}</td>
                                            <td className="p-4">
                                                <span className="uppercase text-[9px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-black">{s.role}</span>
                                            </td>
                                            <td className="p-4">{s.institution?.name || '-'}</td>
                                            <td className="p-4 font-mono">{s.phone}</td>
                                            <td className="p-4">
                                                <div className="flex gap-2 flex-wrap">
                                                    {s.documents?.map((doc: any) => (
                                                        <a 
                                                            key={doc.id} 
                                                            href={`/storage/${doc.file_path}`} 
                                                            target="_blank" 
                                                            className="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-0.5 rounded text-[10px] font-bold border"
                                                        >
                                                            📄 {doc.document_type}
                                                        </a>
                                                    ))}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* REJECTION NOTES REQUISITE MODAL */}
            {showRejectModal && (
                <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-sm w-full p-6 shadow-2xl space-y-4">
                        <div className="text-center">
                            <h3 className="font-extrabold text-base text-red-650">⚠️ Catatan Penolakan (Rejection)</h3>
                            <p className="text-xs text-slate-450 mt-1">Harap tuliskan catatan alasan penolakan berkas agar fasyankes/mitra dapat memperbaikinya.</p>
                        </div>
                        <div className="space-y-3">
                            <textarea 
                                required
                                value={rejectionNotes}
                                onChange={(e) => setRejectionNotes(e.target.value)}
                                placeholder="Tuliskan di sini, contoh: Berkas STNK buram / kedaluwarsa..."
                                className="w-full text-xs rounded-lg border border-slate-250 p-2.5 min-h-[80px] focus:outline-none focus:border-red-500"
                            />
                        </div>
                        <div className="flex gap-3">
                            <button 
                                onClick={() => setShowRejectModal(false)}
                                className="flex-1 py-2 font-bold bg-slate-150 rounded-lg hover:bg-slate-200 text-xs"
                            >
                                Batal
                            </button>
                            <button 
                                onClick={handleConfirmReject}
                                className="flex-1 py-2 font-bold bg-red-600 hover:bg-red-750 text-white rounded-lg shadow transition text-xs"
                            >
                                Konfirmasi Tolak
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
