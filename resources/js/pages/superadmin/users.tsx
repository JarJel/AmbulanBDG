import { Head } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

interface Props {
    users: any[];
}

export default function SuperAdminUsers({ users }: Props) {
    return (
        <AppLayout activeMenu="users">
            <Head title="Manajemen Pengguna - Super Admin" />
            
            <div className="max-w-7xl mx-auto px-6 py-10 space-y-8">
                <div>
                    <h1 className="text-2xl font-black text-slate-800 tracking-tight">Manajemen Pengguna</h1>
                    <p className="text-gray-500 text-xs mt-1">Daftar lengkap seluruh pengguna, pembagian hak akses (role), institusi, dan nomor telepon.</p>
                </div>

                <div className="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-150 text-[10px] font-black uppercase text-gray-500 tracking-wider">
                                <th className="p-6">Nama Pengguna</th>
                                <th className="p-6">Email</th>
                                <th className="p-6">Role</th>
                                <th className="p-6">Institusi</th>
                                <th className="p-6">No. Telepon</th>
                                <th className="p-6">Terdaftar Pada</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-105 text-xs text-slate-700">
                            {users.map((user) => (
                                <tr key={user.id} className="hover:bg-gray-50/20 transition">
                                    <td className="p-6 font-extrabold text-slate-850">{user.name}</td>
                                    <td className="p-6 font-semibold">{user.email}</td>
                                    <td className="p-6">
                                        <span className={`px-2.5 py-1 rounded-full text-[9px] uppercase font-bold ${
                                            user.role === 'super_admin' ? 'bg-red-50 text-red-600' :
                                            user.role === 'institution_admin' ? 'bg-blue-50 text-blue-650' :
                                            user.role === 'driver' ? 'bg-emerald-50 text-emerald-600' :
                                            user.role === 'nurse' ? 'bg-purple-50 text-purple-650' : 'bg-gray-100 text-gray-600'
                                        }`}>
                                            {user.role}
                                        </span>
                                    </td>
                                    <td className="p-6 font-semibold text-gray-500">
                                        {user.institution?.name || '-'}
                                    </td>
                                    <td className="p-6">{user.phone || '-'}</td>
                                    <td className="p-6 font-mono text-gray-400">
                                        {new Date(user.created_at).toLocaleDateString('id-ID')}
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
