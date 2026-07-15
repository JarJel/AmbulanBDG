<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ringkasan Perjalanan Medis #{{ $order->id }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.5; padding: 20px; font-size: 13px; }
        .header { border-bottom: 2px solid #D9383A; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; color: #D9383A; margin: 0; }
        .subtitle { font-size: 11px; color: #666; margin: 2px 0 0 0; }
        .section-title { font-size: 12px; font-weight: bold; text-transform: uppercase; background: #f5f5f5; padding: 5px 8px; margin-top: 15px; border-left: 3px solid #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td { padding: 6px 8px; vertical-align: top; }
        td.label { width: 30%; color: #666; font-weight: bold; }
        .badge { display: inline-block; padding: 3px 6px; font-size: 10px; font-weight: bold; border-radius: 3px; background: #ddd; }
        .badge-success { background: #d1e7dd; color: #0f5132; }
        .badge-danger { background: #f8d7da; color: #842029; }
        .print-btn { background: #D9383A; color: #fff; border: none; padding: 8px 16px; font-weight: bold; border-radius: 4px; cursor: pointer; margin-bottom: 15px; }
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <button class="print-btn" onclick="window.print()">Cetak Ringkasan</button>
    <div class="header">
        <div class="title">AMBULANBDG (AMBUFIND)</div>
        <div class="subtitle">Layanan Koordinasi Tanggap Darurat & Transportasi Medis Terintegrasi Kota Bandung</div>
    </div>

    <h2>RINGKASAN PERJALANAN MEDIS</h2>
    <p>ID Transaksi: <strong>#{{ $order->id }}</strong> | Waktu Cetak: {{ now()->toDateTimeString() }}</p>

    <div class="section-title">Status Pelayanan</div>
    <table>
        <tr>
            <td class="label">Kategori Layanan</td>
            <td>{{ strtoupper($order->order_type) }}</td>
        </tr>
        <tr>
            <td class="label">Status Akhir</td>
            <td><span class="badge badge-success">{{ strtoupper($order->status) }}</span></td>
        </tr>
        <tr>
            <td class="label">Waktu Pemesanan</td>
            <td>{{ $order->created_at }}</td>
        </tr>
    </table>

    <div class="section-title">Informasi Pasien</div>
    <table>
        <tr>
            <td class="label">Nama Pasien</td>
            <td>{{ $order->patientDetail?->patient_name ?? 'Pasien Umum / Tamu' }}</td>
        </tr>
        <tr>
            <td class="label">Usia Pasien</td>
            <td>{{ $order->patientDetail?->patient_age ?? '-' }} Tahun</td>
        </tr>
        <tr>
            <td class="label">No. HP Pemesan</td>
            <td>{{ $order->guest_phone ?? ($order->user?->phone ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label">Catatan Medis Triage (Perawat)</td>
            <td>{{ $order->patient_condition ?? 'Tidak ada catatan Triage.' }}</td>
        </tr>
        <tr>
            <td class="label">Keluhan / Gejala Awal</td>
            <td>{{ $order->patientDetail?->condition_notes ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Rute Perjalanan & Lokasi</div>
    <table>
        <tr>
            <td class="label">Alamat Penjemputan</td>
            <td>{{ $order->pickup_address }} (Koordinat: {{ $order->pickup_latitude ?? '-' }}, {{ $order->pickup_longitude ?? '-' }})</td>
        </tr>
        <tr>
            <td class="label">Alamat Rujukan / IGD RS</td>
            <td>{{ $order->destination_address ?? '-' }} (Koordinat: {{ $order->destination_latitude ?? '-' }}, {{ $order->destination_longitude ?? '-' }})</td>
        </tr>
    </table>

    <div class="section-title">Armada & Kru Medis</div>
    <table>
        <tr>
            <td class="label">Instansi Penanggung</td>
            <td>{{ $order->ambulance?->institution?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Plat Ambulans</td>
            <td>{{ $order->ambulance?->plate_number ?? '-' }} ({{ strtoupper($order->ambulance?->category ?? '') }})</td>
        </tr>
        <tr>
            <td class="label">Nama Pengemudi (Driver)</td>
            <td>{{ $order->ambulance?->currentDriver?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nama Perawat (Nurse)</td>
            <td>{{ $order->ambulance?->currentNurse?->name ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Rincian Transaksi & Tarif</div>
    <table>
        <tr>
            <td class="label">Total Tarif Perjalanan</td>
            <td><strong>Rp {{ number_format($order->fare_amount, 2, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Status Pembayaran</td>
            <td>{{ $order->payment?->status === 'paid' ? 'LUNAS DP / SELESAI' : 'GRATIS / SUBSIDI BPJS' }}</td>
        </tr>
        @if($order->payment)
        <tr>
            <td class="label">Metode Pembayaran</td>
            <td>{{ strtoupper($order->payment->payment_method) }} (VA/QRIS)</td>
        </tr>
        @endif
    </table>

    @if($order->rating)
    <div class="section-title">Ulasan & Rating Customer</div>
    <table>
        <tr>
            <td class="label">Rating Bintang</td>
            <td>{{ $order->rating->rating_value }} / 5</td>
        </tr>
        <tr>
            <td class="label">Ulasan Masukan</td>
            <td>"{{ $order->rating->review_comment ?? '-' }}"</td>
        </tr>
    </table>
    @endif

</body>
</html>
