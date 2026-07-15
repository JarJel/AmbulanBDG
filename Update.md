# 📝 Log Update Sistem AmbulanBDG (AMBUFIND)

Dokumen ini mencantumkan seluruh pembaruan, perbaikan, dan fitur baru yang telah diimplementasikan ke dalam sistem pemesanan, peminjaman, dan pelacakan ambulans terintegrasi **AmbulanBDG (AMBUFIND)**.

---

## 🔒 1. Keamanan & Pembatasan (Rate Limiting)
* **Batas Pemesanan Guest SOS:** Membatasi pemanggilan darurat SOS tanpa login maksimal **3 kali per jam per nomor handphone** untuk mencegah penyalahgunaan sistem (*fake emergency dispatch*).
* **Kombinasi Rate Limiting:** Mengintegrasikan custom rate limiter pada API dispatch SOS yang mengombinasikan pelacakan IP address pengirim dan nomor handphone darurat.

## 🚨 2. Manajemen Prioritas & Konflik Penugasan
* **Preemption Booking Otomatis:** Apabila sebuah armada ambulans ditugaskan untuk menangani panggilan darurat SOS, sistem secara otomatis akan membatalkan/melepas (*unassign*) jadwal pemesanan rental non-emergency yang dijadwalkan pada unit tersebut dalam kurun waktu **2 jam ke depan**, mengembalikan status booking rental tersebut ke pool pencarian agar dapat ditangani unit lain.

## 💼 3. Fitur Onboarding & CRUD Admin Instansi (RS / Fasyankes)
* **Pendaftaran Ambulans (CRUD):** 
  * Formulir registrasi unit armada mendukung opsi **Simpan sebagai Draft** (tidak langsung siaga).
  * Validasi regex ketat untuk plat nomor kendaraan Indonesia (contoh: `D 1234 ABC`).
  * Desain file uploader dokumen legalitas (STNK, KIR, Izin Operasional) yang diperbarui dengan **border visual yang jelas**, label petunjuk, dan penataan kolom tanggal masa berlaku dokumen berdampingan (*side-by-side*).
* **Fitur Duplikasi (Cloning):** Memungkinkan Admin menyalin konfigurasi tarif dan spesifikasi teknis unit ambulans lama ke plat nomor unit baru hanya dengan sekali klik (*Single-Click Duplicate*).
* **Onboarding Personel Medis:** Pendaftaran personel Driver/Nurse dengan validasi NIK (16 digit), upload sertifikasi (KTP, SIM/STR), dan pembuatan **Tautan Undangan Aktivasi (Invitation Link)** unik agar kru dapat mengatur password masuk secara mandiri.
* **Manajemen Sesi Device Aktif:** Admin dapat melihat daftar perangkat (IP & User Agent) yang sedang login atas nama kru medis instansi mereka dan melakukan **Force Logout (Keluar Paksa)** langsung dari dashboard.

## 🩺 4. Fitur Checklist Siaga & Offline-First Driver (PWA)
* **Formulir Checklist Shift:** Driver diwajibkan mengisi formulir checklist kondisi kesiapan armada (BBM, ketersediaan tabung oksigen, dan suplai medis darurat) sebelum mulai bekerja dan mengikat diri ke unit ambulans.
* **Sinkronisasi Offline (Offline Action Queue):** Jika driver kehilangan sinyal di perjalanan, status perjalanan, pelaporan kondisi medis pasien, dan perubahan status siaga disimpan di antrean lokal (*Local Storage Queue*) dan disinkronkan otomatis saat jaringan kembali online.

## 🏛️ 5. Portal Dashboard & Verifikasi Super Admin (Dinkes)
* **Antrean Verifikasi Mitra:** 
  * Menampilkan antrean pengajuan armada dan kru baru dari fasyankes kemitraan.
  * Diurutkan berdasarkan pengajuan terbaru (**Latest-First**).
  * Menampilkan **Tanggal Pengajuan** dokumen secara jelas di bawah plat nomor kendaraan dan nama staf.
* **Persetujuan Massal (Bulk Approval/Rejection):** Mendukung bulk-select (centang massal) untuk memproses persetujuan dokumen legalitas STNK/KIR/STR/SIM secara cepat, dengan kewajiban menuliskan **Catatan Alasan Penolakan** jika dokumen ditolak.

## 📡 6. Real-time Pelacakan & Peta Interaktif (OSRM & Reverb)
* **Peringatan SLA Breach:** Dasbor Dinkes & Fasyankes dilengkapi dengan banner peringatan *real-time push notification* via Laravel Echo/Reverb jika ada driver yang tidak merespons panggilan darurat SOS dalam waktu **30 detik**.
* **Estimasi ETA & Jarak OSRM:** Halaman pelacakan langsung (*live tracking*) kini menampilkan data durasi waktu tiba aktual (ETA dalam menit) dan jarak tempuh riil (dalam KM) berdasarkan koordinat jalan raya dari OSRM API, didukung fallback *silent polling* setiap 10 detik jika sinyal terputus.
