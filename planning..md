
# Prompt Final — Sistem Pemesanan, Peminjaman & Pelacakan Ambulans Terintegrasi Kota Bandung

Bertindaklah sebagai Senior System Analyst dan Full-Stack Developer berpengalaman (spesialisasi Laravel + ReactJS/Tailwind CSS, sistem real-time berbasis geolocation).

## Konteks Proyek

Saya sedang merancang sistem informasi berbasis website untuk **"Pemesanan, Peminjaman, dan Pelacakan Mobil Ambulans Terintegrasi di Kota Bandung"**. Sistem ini belum ada sebelumnya dan bertujuan menyatukan 3 jenis layanan dalam satu platform:

1. Ambulans gratis (darurat/BPJS, rujukan antar-fasyankes)
2. Ambulans swasta berbayar (non-darurat/jenazah)
3. Peminjaman instansi untuk acara/event

Saya sudah punya draft fitur awal, skema database (ERD), dan alur bisnis yang dilampirkan di bawah. Tugas kamu **BUKAN membuat ulang dari nol**, tapi:

- Menyempurnakan & merapikan draft yang sudah ada (jangan hilangkan konten yang sudah bagus)
- Melengkapi kekurangan modul yang belum tercakup
- Menambahkan detail teknis yang masih kurang di tiap fitur, konsisten dengan ERD dan alur bisnis yang sudah ditentukan

---

## Struktur Modul yang Diminta

1. **Modul Radar & Geolokasi** (Guest/User Side)
2. **Modul Pemesanan & Peminjaman** (Booking System — 3 jalur: SOS, Non-Darurat, Event)
3. **Modul Pelacakan Pasca-Pemesanan** (Live Tracking)
4. **Modul Driver & Perawat** (Status, Broadcast GPS, PWA)
5. **Modul Admin & Fasyankes** (Validasi dokumen, manajemen armada/tarif, anti-double-booking, analytics)
6. **Modul Notifikasi & Komunikasi** (Push notification, WhatsApp/SMS gateway, integrasi darurat 112/119)
7. **Modul Keamanan & Legalitas** (RBAC per role, audit log, dokumen kendaraan STNK/KIR, sertifikasi driver)
8. **Modul Rating & Feedback**

Untuk setiap fitur, sebutkan:

- **Nama Fitur**
- **Deskripsi Fungsi** (2-3 kalimat, fokus ke user experience & business logic)
- **Teknologi/API yang disarankan**
- **Entitas Database Utama yang terlibat** (rujuk ke ERD di bawah — jangan buat entitas baru tanpa alasan kuat)
- **Prioritas implementasi** (Must Have / Should Have / Could Have — MoSCoW method)

---

## Keputusan Arsitektur yang Sudah Disepakati (wajib diikuti, jangan diubah)

- **Real-time tracking:** Laravel Reverb (native WebSocket Laravel), bukan Pusher atau polling. Broadcast lokasi driver di-throttle 3-5 detik atau tiap pergerakan >10 meter, bukan tiap perubahan sekecil apa pun.
- **Query radar ambulans terdekat:** gunakan kolom `POINT` dengan `SPATIAL INDEX` di MySQL, bukan dua kolom decimal biasa, agar query radius (misal `ST_Distance_Sphere()`) tetap cepat saat jumlah armada bertambah.
- **Integrasi 112/119:** tidak ada API publik resmi. Pendekatan yang dipakai adalah kombinasi (a) tombol *click-to-call* (`tel:112`) sebagai fallback saat radar kosong, dan (b) *SOS Priority Queue* internal — sistem sendiri bertindak sebagai mini dispatch center dengan auto-assign ke ambulans terdekat. Kerja sama formal dengan Command Center 112 Kota Bandung disebut sebagai *future work*, bukan fitur inti.
- **Notifikasi:** gunakan Laravel Notification class sebagai lapisan abstraksi, satu event bisa broadcast ke banyak channel sekaligus (push/FCM, WhatsApp Business API, SMS fallback, email, in-app).
- **Dokumen legal (STNK/KIR/SIM/sertifikasi):** wajib ada sistem *expiry alert* — cron job harian, dan armada otomatis disuspend dari radar jika dokumen kadaluarsa.

---

## Alur Bisnis per Jalur Pemesanan (wajib jadi acuan urutan status)

**Jalur SOS/Darurat:** GPS auto-aktif → tombol SOS → cek radar radius → auto-assign ke ambulans terdekat (bukan pilih manual) → masuk SOS Priority Queue dengan SLA timer respons driver → status berjalan (Menuju Lokasi → Sampai → Menuju RS → Selesai) → pembayaran/klaim BPJS diproses belakangan, bukan di depan.

**Jalur Non-Darurat/Sewa:** user pilih jadwal & armada dari radar → Fare Calculator → E-Verifikasi BPJS atau lanjut pembayaran DP → cek anti-double-booking di kalender → driver/admin konfirmasi manual → reminder H-1 → status trip berjalan sama seperti SOS → pelunasan setelah selesai.

**Jalur Event/Instansi:** form e-proposal + upload PDF → masuk dashboard admin fasyankes/swasta dengan status "Menunggu Review" → approve (generate estimasi biaya, block kalender) atau reject (dengan alasan) → reminder standby H-1 → driver update status "Standby di Lokasi Acara" (bukan trip bergerak biasa) → selesai acara → laporan/invoice final.

**Titik temu ketiga jalur:** setelah status order menjadi `confirmed`, ketiganya masuk ke satu Modul Live Tracking yang sama — arsitektur tracking tidak perlu dibedakan berdasarkan jalur asal order.

---

## Skema Database (ERD) — Acuan Struktur Data

```
INSTITUTIONS ||--o{ AMBULANCES : owns
AMBULANCES ||--o{ ORDERS : serves
AMBULANCES ||--o{ VEHICLE_DOCUMENTS : has
USERS ||--o{ ORDERS : places
USERS ||--o| DRIVER_PROFILES : has
ORDERS ||--o| EVENT_PROPOSALS : includes
ORDERS ||--o{ TRIP_STATUS_HISTORY : logs
ORDERS ||--o{ TRIP_TRACKING_LOGS : tracked_by
ORDERS ||--o| PAYMENTS : paid_by
ORDERS ||--o| RATINGS : rated_by
ORDERS ||--o| SOS_LOGS : escalates

USERS(id, name, phone, role)
INSTITUTIONS(id, name, type, verified_status)
AMBULANCES(id, institution_id FK, plate_number, category, status, current_location POINT)
VEHICLE_DOCUMENTS(id, ambulance_id FK, doc_type, expiry_date)
DRIVER_PROFILES(id, user_id FK, sim_number, sim_expiry)
ORDERS(id, user_id FK, ambulance_id FK, order_type, status, pickup_location POINT, destination_location POINT, fare_amount)
EVENT_PROPOSALS(id, order_id FK, document_path, review_status)
TRIP_STATUS_HISTORY(id, order_id FK, status, changed_at)
TRIP_TRACKING_LOGS(id, order_id FK, coordinates POINT, recorded_at)
PAYMENTS(id, order_id FK, method, amount, status)
RATINGS(id, order_id FK, rating_value)
SOS_LOGS(id, order_id FK, response_time_seconds, escalated)
```

Catatan desain: `orders` satu tabel untuk ketiga jalur dengan kolom `order_type` sebagai discriminator (bukan 3 tabel terpisah). `event_proposals`, `sos_logs` dipisah dari `orders` (relasi 1:1 opsional) karena field-nya hanya relevan untuk 1 dari 3 jalur. `ambulances.current_location` (posisi terkini, untuk radar) dipisah dari `trip_tracking_logs` (riwayat historis, untuk playback/audit) agar query radar tetap ringan.

---

## Hal yang Wajib Dibahas dalam Output

- Bagaimana sistem menangani **race condition** (2 user memesan ambulans yang sama secara bersamaan)
- **Fallback** jika GPS user/driver mati atau sinyal lemah
- Pencegahan **location spoofing** dari sisi driver
- **SLA timer & auto-reassign** untuk order SOS yang tidak direspons driver dalam waktu tertentu

---

## Format Output

Markdown yang rapi dan scannable. Gunakan tabel ringkasan tiap modul di awal sebelum masuk ke detail per fitur, agar mudah dipahami tim frontend (ReactJS/Tailwind) dan backend (Laravel).

---

## Draft Fitur yang Sudah Ada (gunakan sebagai basis, JANGAN diganti isinya, hanya disempurnakan)

[Tempel draft fitur "List Fitur Website Sistem Pemesanan & Pelacakan Ambulans" di sini]
