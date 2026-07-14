# Daftar Fitur Terimplementasi - AmbulanBDG

Dokumen ini berisi daftar lengkap fitur yang telah berhasil diimplementasikan pada sistem **Pemesanan, Peminjaman, dan Pelacakan Mobil Ambulans Terintegrasi di Kota Bandung (AmbulanBDG)**. 

Sistem ini dibangun menggunakan arsitektur modern:
- **Backend:** Laravel 11 (PHP)
- **Frontend:** ReactJS + TypeScript + Tailwind CSS (via Inertia.js)
- **Database:** MySQL/MariaDB dengan Spatial Index (`POINT`) untuk geolokasi presisi tinggi
- **Komunikasi Real-time:** Laravel Reverb (WebSockets native) & Event Broadcasting

---

## 🛠️ Ringkasan Status Modul (MoSCoW Method)

| Nama Modul | Deskripsi Fungsi Utama | Status Implementasi | Level Prioritas |
| :--- | :--- | :--- | :--- |
| **Modul 1: Radar & Geolokasi** | Pemetaan real-time, pencarian radius armada standby, geocoding | **Terimplementasi Penuh** | Must Have |
| **Modul 2: Pemesanan & Peminjaman** | 3 Jalur pemesanan: SOS (cepat), Sewa (jadwal), Event (proposal) | **Terimplementasi Penuh** | Must Have |
| **Modul 3: Pelacakan Pasca-Pemesanan** | Live tracking pergerakan ambulans di peta via WebSockets & Polling | **Terimplementasi Penuh** | Must Have |
| **Modul 4: Driver & Perawat** | PWA-style Dashboard, pengikatan armada, simulasi broadcast GPS | **Terimplementasi Penuh** | Must Have |
| **Modul 5: Admin & Fasyankes** | Verifikasi berkas (STNK/KIR/STR), kelola tarif & armada | **Terimplementasi (APIs)** | Should Have |
| **Modul 6: Notifikasi & Komunikasi** | Notifikasi multi-channel (DB, Mail, WA, SMS) ketika status berubah | **Terimplementasi Penuh** | Should Have |
| **Modul 7: Keamanan & Legalitas** | RBAC (Role-based access control), Audit Logging admin & driver | **Terimplementasi Penuh** | Must Have |
| **Modul 8: Rating & Feedback** | Bintang (1-5) & ulasan teks, proteksi ulasan ganda & kepemilikan | **Terimplementasi Penuh** | Could Have |

---

## 🔍 Detail Fitur Terimplementasi Per Modul

### 1. Modul Radar & Geolokasi
*   **Peta Interaktif Premium:** Visualisasi peta *full-screen* menggunakan Leaflet.js dengan Tile Layer Voyager CARTO yang modern. Dilengkapi kustom marker interaktif (User, Ambulans Gawat Darurat/Emergency, Ambulans Non-Emergency/Sewa).
*   **Deteksi Geolocation Browser:** Secara otomatis meminta izin akses lokasi client dan menempatkan pin lokasi awal pengguna.
*   **Radar Ambulans Terdekat (Spatial Query):** Menggunakan query MySQL Spatial (`ST_Distance_Sphere`) untuk memindai ambulans berstatus `standby` dalam radius kilometer tertentu (default 10 KM) secara real-time.
*   **Geocoding & Reverse Geocoding (Mock API):** Mengonversi alamat teks (seperti "Gedung Sate", "Dago", "Cihampelas Walk") ke koordinat GPS dan sebaliknya untuk memudahkan penentuan rute jemput/tujuan.
*   *File Terkait:*
    *   [welcome.tsx](file:///c:/xampp/htdocs/AmbulanBDG/resources/js/pages/welcome.tsx) (Halaman pencarian & radar)
    *   [Map.tsx](file:///c:/xampp/htdocs/AmbulanBDG/resources/js/components/Map.tsx) (Komponen Leaflet Wrapper)
    *   [RadarController.php](file:///c:/xampp/htdocs/AmbulanBDG/app/Http/Controllers/RadarController.php) (Backend API Radar & Geocode)

### 2. Modul Pemesanan & Peminjaman (3 Jalur)
*   **Jalur SOS / Darurat (Emergency):**
    *   Satu tombol "PANGGIL SOS" untuk pemesanan instan.
    *   Secara otomatis menugaskan unit ambulans `standby` terdekat di radar.
    *   Membuat order berstatus `confirmed` (tarif Rp 0.00) dan mengubah status ambulans terpilih menjadi `on_duty`.
    *   Menampilkan peringatan *fallback* (Call 112/119) jika tidak ada ambulans standby di radar.
*   **Jalur Sewa / Non-Darurat (Scheduled Sewa):**
    *   Form pemesanan terjadwal (tanggal & waktu) dengan penentuan rute jemput dan tujuan.
    *   **Fare Calculator:** Menghitung jarak rute menggunakan formula matematika *Haversine* dan menentukan total biaya berdasarkan tarif dasar (`base_fare`) & tarif per kilometer (`price_per_km`) dari armada yang dipilih.
    *   **Anti-Double-Booking:** Validasi di tingkat database untuk mencegah tumpang tindih jadwal (overlapping calendar) peminjaman armada yang sama.
    *   **BPJS E-Verification (Mock):** Verifikasi otomatis nomor kartu BPJS (jika berawalan `'000'` maka tarif di-nol-kan karena ditanggung BPJS).
    *   Order masuk dengan status `pending` (menunggu konfirmasi).
*   **Jalur Event / Instansi (Peminjaman Acara):**
    *   Form pengajuan siaga acara dengan unggahan dokumen e-proposal (PDF, maks 10MB).
    *   Penetapan tarif flat awal sebesar Rp 1.500.000 (tarif siaga event standar).
    *   Order masuk ke database dengan status `pending` tanpa penugasan unit ambulans di awal (ambulans ditugaskan secara manual oleh admin fasyankes saat proposal disetujui).
*   *File Terkait:*
    *   [welcome.tsx](file:///c:/xampp/htdocs/AmbulanBDG/resources/js/pages/welcome.tsx) (Tab form pemesanan frontend)
    *   [OrderController.php](file:///c:/xampp/htdocs/AmbulanBDG/app/Http/Controllers/OrderController.php) (Logika backend pemesanan & kalkulasi)

### 3. Modul Pelacakan Pasca-Pemesanan (Live Tracking)
*   **Live Tracking Map:** Rute pelacakan visual yang dinamis di peta menunjukkan posisi penjemputan, tujuan (jika ada), dan pergerakan real-time ambulans.
*   **Echo WebSocket Integration:** Menggunakan Laravel Echo untuk berlangganan ke private channel `orders.{id}`. Mendengarkan event `.location.updated` untuk mengubah posisi marker ambulans di peta secara instan tanpa membebani server dengan HTTP polling berulang.
*   **Polling Hibrida (Status Tracker):** Tetap melakukan polling perubahan status trip setiap 7 detik sebagai *fallback* data utama jika WebSocket terputus.
*   **Card Info Detail:** Menampilkan informasi status perjalanan (human-readable), identitas kru (Driver & Perawat), detail plat nomor, instansi fasyankes penanggung jawab, serta rincian tarif.
*   *File Terkait:*
    *   [tracking.tsx](file:///c:/xampp/htdocs/AmbulanBDG/resources/js/pages/orders/tracking.tsx) (Halaman pelacakan pengguna)
    *   [AmbulanceLocationUpdated.php](file:///c:/xampp/htdocs/AmbulanBDG/app/Events/AmbulanceLocationUpdated.php) (Event Broadcasting WebSocket)

### 4. Modul Driver & Perawat (Workspace Driver)
*   **PWA Driver Dashboard:** Workspace ramah seluler untuk kru di lapangan.
*   **Ikat Armada (Assign Vehicle):** Kru dapat memilih plat nomor armada ambulans yang dikendarai untuk mendaftarkan dirinya (mengisi `current_driver_id` atau `current_nurse_id` pada tabel `ambulances`) yang secara otomatis mengubah status armada menjadi `standby` (online).
*   **Toggle Status Siaga:** Driver bisa mengubah status armada secara fleksibel (`standby` / `offline`).
*   **Deteksi Tugas Aktif (Polling):** Dashboard melakukan polling setiap 5 detik ke API `/api/driver/active-trips` untuk mendeteksi penugasan baru.
*   **Alur Perubahan Status Trip:** Tombol aksi sekuensial yang memandu driver memperbarui status perjalanan: `confirmed` -> `on_the_way` -> `arrived` -> `transporting` -> `completed` / `cancelled`.
*   **Simulator GPS Bergerak:** Simulator tertanam untuk mempermudah testing. Saat aktif, simulator memancarkan koordinat tiruan dari rute Gedung Sate ke Cihampelas Walk ke server setiap 3 detik, memicu broadcast WebSocket ke halaman pelacakan pengguna.
*   *File Terkait:*
    *   [dashboard.tsx (Driver)](file:///c:/xampp/htdocs/AmbulanBDG/resources/js/pages/driver/dashboard.tsx) (Frontend Workspace Kru)
    *   [DriverController.php](file:///c:/xampp/htdocs/AmbulanBDG/app/Http/Controllers/DriverController.php) (API ikat armada & status kru)

### 5. Modul Admin & Fasyankes (Backend API)
*   **Verifikasi Dokumen Kendaraan (STNK/KIR):** API untuk menyetujui (`approved`) atau menolak (`rejected`) dokumen legal kendaraan. Jika disetujui dan tidak ada masalah dokumen lain, status ambulans otomatis dipulihkan dari `suspended` menjadi `standby`.
*   **Verifikasi Lisensi Staff (SIM/STR):** API untuk memverifikasi berkas lisensi staf. Mengaktifkan kembali ambulans yang terikat jika status staf disetujui.
*   **Manajemen Armada & Tarif:** Konfigurasi status ambulans secara paksa dan kustomisasi struktur tarif dinamis (`base_fare` dan `price_per_km`).
*   **Verifikasi Proposal Event:** Memvalidasi PDF proposal event. Jika disetujui, admin menunjuk `ambulance_id` yang tersedia, mengubah status order menjadi `confirmed`, dan menandai status ambulans menjadi `on_duty`. Jika ditolak, status order diubah menjadi `cancelled`.
*   *File Terkait:*
    *   [AdminController.php](file:///c:/xampp/htdocs/AmbulanBDG/app/Http/Controllers/AdminController.php) (Backend API Admin)

### 6. Modul Notifikasi & Komunikasi
*   **Abstraksi Laravel Notification:** Setiap perubahan status order memicu notifikasi instan yang didistribusikan melalui beberapa channel sekaligus.
*   **Database Channel:** Menyimpan riwayat notifikasi ke tabel `notifications` untuk ditampilkan di aplikasi.
*   **Email Channel:** Mengirim template email pemberitahuan rincian status terbaru ke kotak masuk pelanggan.
*   **WhatsApp Channel (Mock):** Simulasi pengiriman notifikasi instan ke nomor WhatsApp pelanggan menggunakan mock integration.
*   **SMS Channel (Mock):** Simulasi fallback SMS gateway untuk pemberitahuan luar jaringan.
*   *File Terkait:*
    *   [OrderStatusUpdated.php](file:///c:/xampp/htdocs/AmbulanBDG/app/Notifications/OrderStatusUpdated.php) (Logika kelas notifikasi)
    *   [WhatsAppChannel.php](file:///c:/xampp/htdocs/AmbulanBDG/app/Channels/WhatsAppChannel.php) & [SmsChannel.php](file:///c:/xampp/htdocs/AmbulanBDG/app/Channels/SmsChannel.php) (Channel pengiriman kustom)

### 7. Modul Keamanan & Legalitas
*   **Role-Based Access Control (RBAC):** Middleware Laravel kustom membatasi hak akses rute API berdasarkan peran pengguna (`customer`, `driver`, `nurse`, `super_admin`, `institution_admin`).
*   **Audit Logging System:** Helper khusus (`AuditLogger`) mencatat setiap aksi administrasi penting ke dalam tabel `audit_logs` (seperti perubahan status dokumen, pengikatan armada, perubahan tarif, dan persetujuan proposal).
*   **Autentikasi Aman:** Pendaftaran dan login yang aman menggunakan hashing sandi `Bcrypt` (via `Hash::make`) dan perlindungan session token.
*   *File Terkait:*
    *   [AuthController.php](file:///c:/xampp/htdocs/AmbulanBDG/app/Http/Controllers/AuthController.php) (Registrasi & Login)
    *   [AuditLogger.php](file:///c:/xampp/htdocs/AmbulanBDG/app/Helpers/AuditLogger.php) (Pencatat riwayat audit)

### 8. Modul Rating & Feedback
*   **Widget Ulasan Interaktif:** Ditampilkan secara dinamis di halaman tracking setelah status order diperbarui oleh driver menjadi `completed`. Pengguna dapat memilih bintang (1-5) dan menulis masukan teks.
*   **Validasi Keamanan Rating:**
    *   Hanya order berstatus `completed` yang dapat dinilai.
    *   Proteksi Kepemilikan: Hanya customer pembuat order yang dapat mengirim ulasan.
    *   Anti-Ulasan Ganda: Mencegah pengiriman ulasan berulang untuk satu nomor order yang sama.
*   *File Terkait:*
    *   [RatingController.php](file:///c:/xampp/htdocs/AmbulanBDG/app/Http/Controllers/RatingController.php) (Backend API Rating)
    *   [tracking.tsx](file:///c:/xampp/htdocs/AmbulanBDG/resources/js/pages/orders/tracking.tsx) (Widget frontend rating)

---

## 🗄️ Skema Database & Migrasi Terkait

Semua tabel database telah dibuat menggunakan Laravel Migrations dengan skema relasi yang solid:

1.  `0001_01_01_000000_create_users_table.php` (Menyimpan user & `role`)
2.  `2026_07_11_223801_create_staff_profiles_table.php` (Lisensi driver/nurse & status verifikasi)
3.  `2026_07_11_223802_create_ambulances_table.php` (Tabel armada, kategori, `status` & `current_location POINT` spatial)
4.  `2026_07_11_223813_add_tariff_columns_to_ambulances_table.php` (Menambahkan `base_fare` dan `price_per_km` tarif dinamis)
5.  `2026_07_11_223803_create_vehicle_documents_table.php` (Berkas KIR/STNK kendaraan & tanggal kadaluarsa)
6.  `2026_07_11_223804_create_orders_table.php` (Pemesanan utama dengan discriminator `order_type`, lokasi `pickup_location` & `destination_location`)
7.  `2026_07_11_223805_create_event_proposals_table.php` (Unggahan berkas proposal PDF event)
8.  `2026_07_11_223806_create_trip_status_history_table.php` (Riwayat perubahan status order untuk SLA auditing)
9.  `2026_07_11_223807_create_trip_tracking_logs_table.php` (Log koordinat perjalanan real-time untuk audit playback)
10. `2026_07_11_223808_create_payments_table.php` (Informasi transaksi/pembayaran sewa)
11. `2026_07_11_223809_create_ratings_table.php` (Skor rating bintang & umpan balik ulasan)
12. `2026_07_11_223810_create_sos_logs_table.php` (SLA timer logs khusus penanganan darurat/SOS)
13. `2026_07_11_223811_create_audit_logs_table.php` (Log audit administratif admin)
14. `2026_07_11_223812_create_notifications_table.php` (Notifikasi database Laravel standard)
