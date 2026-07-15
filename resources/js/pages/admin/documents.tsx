import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    documents: any[];
}

export default function AdminDocuments({ documents }: Props) {
    const [message, setMessage] = useState<string | null>(null);

    const handleVerify = async (id: number, status: 'approved' | 'rejected') => {
        try {
            const res = await fetch(`/api/admin/documents/${id}/verify`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    verification_status: status,
                })
            });
            const data = await res.json();
            if (res.ok) {
                setMessage(`Verifikasi dokumen KIR/STNK berhasil diperbarui menjadi ${status}.`);
                window.location.reload();
            } else {
                setMessage(data.message || 'Gagal memproses verifikasi dokumen.');
            }
        } catch (err) {
            setMessage('Kesalahan koneksi ke server.');
        }
    };

    return (
        <AppLayout activeMenu="documents">
            <Head title="Verifikasi Dokumen KIR/STNK - Admin" />
            
            <div className="max-w-7xl mx-auto px-6 py-10 space-y-8">
                <div>
                    <h1 className="text-2xl font-black text-slate-800 tracking-tight">Dokumen Legal Kendaraan</h1>
                    <p className="text-gray-500 text-xs mt-1">Verifikasi berkas berkala KIR, STNK, dan izin dispensasi jalan unit ambulans Anda.</p>
                </div>

                {message && (
                    <div className="p-4 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-semibold">
                        {message}
                    </div>
                )}

                <div className="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-150 text-[10px] font-black uppercase text-gray-500 tracking-wider">
                                <th className="p-6">Plat Nomor Kendaraan</th>
                                <th className="p-6">Tipe Dokumen</th>
                                <th className="p-6">Dokumen File (PDF)</th>
                                <th className="p-6">Status Verifikasi</th>
                                <th className="p-6">Tanggal Kedaluwarsa</th>
                                <th className="p-6 text-right">Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-105 text-xs text-slate-700">
                            {documents.map((doc) => (
                                <tr key={doc.id} className="hover:bg-gray-50/20 transition">
                                    <td className="p-6 font-extrabold text-slate-850">{doc.ambulance?.plate_number}</td>
                                    <td className="p-6 uppercase font-bold">{doc.doc_type}</td>
                                    <td className="p-6">
                                        <a href={doc.document_path} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline font-bold">
                                            📄 Unduh Berkas
                                        </a>
                                    </td>
                                    <td className="p-6">
                                        <span className={`px-2.5 py-1 rounded-full text-[9px] uppercase font-bold ${
                                            doc.verification_status === 'approved' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'
                                        }`}>
                                            {doc.verification_status}
                                        </span>
                                    </td>
                                    <td className="p-6 font-mono text-gray-400">
                                        {doc.expiry_date}
                                    </td>
                                    <td className="p-6 text-right">
                                        {doc.verification_status === 'pending' && (
                                            <div className="flex gap-2 justify-end">
                                                <button
                                                    onClick={() => handleVerify(doc.id, 'approved')}
                                                    className="bg-emerald-600 text-white rounded px-3 py-1.5 font-bold uppercase text-[9px]"
                                                >
                                                    Setujui
                                                </button>
                                                <button
                                                    onClick={() => handleVerify(doc.id, 'rejected')}
                                                    className="bg-red-655 text-white rounded px-3 py-1.5 font-bold uppercase text-[9px]"
                                                >
                                                    Tolak
                                                </button>
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
