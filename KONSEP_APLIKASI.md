# Konsep Aplikasi Cerdas Cermat Islami

## 1. Ringkasan Produk

Nama kerja: **CCI An Nuur**. Nama ini masih berupa teks, bukan logo final.

CCI An Nuur adalah dummy aplikasi pertandingan cerdas cermat berbasis web yang menghubungkan panel operator, tombol bel digital di browser, juri, dan layar skor publik. Sistem mencatat regu yang menekan tombol pertama, mengunci tombol regu lain, membantu juri memberi keputusan, lalu memperbarui skor.

Produk dibuat sebagai aplikasi demonstrasi menggunakan PHP native dan MySQL. Seluruh interaksi bel dilakukan melalui tombol di halaman web. Cakupan konsep hanya perangkat lunak.

### Masalah yang diselesaikan

- Sulit memastikan regu yang menekan bel pertama ketika bunyi hampir bersamaan.
- Pencatatan skor manual lambat dan rentan salah.
- Peserta dan penonton tidak selalu memahami status pertandingan.
- Panitia baru memerlukan sistem yang dapat dipelajari melalui simulasi singkat.
- Kesalahan operator harus dapat dibatalkan tanpa menghapus riwayat pertandingan.

### Sasaran utama

- Penentuan bel pertama objektif dan transparan.
- Perubahan skor cepat, akurat, dan dapat ditelusuri.
- Satu layar pertandingan dapat dipahami dalam beberapa detik.
- Sistem mudah digunakan kembali untuk lomba berikutnya.

## 2. Pengguna dan Kebutuhannya

| Pengguna | Tugas utama | Kebutuhan aplikasi |
|---|---|---|
| Operator | Menjalankan pertandingan | Kontrol besar, urutan kerja jelas, pembatalan aman |
| Juri | Menentukan benar atau salah | Identitas regu dan jawaban aktif terlihat jelas |
| Moderator | Membacakan soal dan mengatur tempo | Status soal, timer, dan regu penjawab mudah dipantau |
| Regu peserta | Menekan bel dan menjawab | Konfirmasi bahwa bel diterima atau dikunci |
| Penonton | Mengikuti pertandingan | Skor, babak, timer, dan regu aktif terlihat dari jauh |
| Admin panitia | Menyiapkan acara | Pengaturan regu, babak, dan aturan nilai |

Satu orang boleh merangkap operator, moderator, dan admin pada acara kecil. Aplikasi tidak memaksa pembagian peran yang rumit.

## 3. Prinsip Produk

1. **Pertandingan adalah pusat aplikasi.** Layar utama bukan dashboard berisi grafik, melainkan konsol pertandingan.
2. **Server menentukan pemenang bel.** Klik pertama yang diterima aplikasi pada soal aktif menjadi pemenang.
3. **Semua perubahan skor dicatat sebagai kejadian.** Koreksi tidak menimpa sejarah secara diam-diam.
4. **Teknologi dibuat sederhana.** PHP native menangani halaman dan endpoint, MySQL menyimpan data, dan JavaScript menangani interaksi browser.
5. **Kontrol berisiko membutuhkan konfirmasi.** Mengakhiri pertandingan, menghapus regu, atau mereset skor tidak boleh terjadi karena salah tekan.
6. **Dummy tetap dapat didemonstrasikan dari satu komputer.** Tombol simulasi regu tersedia pada konsol operator.

## 4. Alur Pertandingan Utama

```text
Persiapan acara
    |
    v
Siapkan regu, babak, dan aturan nilai
    |
    v
Pilih babak dan regu
    |
    v
Buka soal
    |
    +--> Bel pertama diterima
    |         |
    |         v
    |     Bel lain dikunci
    |         |
    |         v
    |     Juri: benar / salah / batal
    |         |
    |         v
    |     Skor diperbarui dan dicatat
    |
    +--> Waktu habis tanpa bel
              |
              v
          Tutup soal
    |
    v
Soal berikutnya atau akhiri babak
```

### Mesin status pertandingan

| Status | Arti | Aksi operator yang tersedia |
|---|---|---|
| Persiapan | Regu dan aturan belum lengkap | Ubah regu, pilih babak, isi aturan |
| Siap | Pertandingan siap dijalankan | Buka soal |
| Soal dibuka | Bel peserta diterima | Tutup soal, jeda timer |
| Bel terkunci | Satu regu memperoleh hak jawab | Benar, salah, batalkan bel |
| Penilaian selesai | Nilai sudah dicatat | Batalkan keputusan, lanjut soal |
| Babak selesai | Hasil babak dikunci | Tampilkan hasil, buka kembali dengan konfirmasi |

Server hanya menerima bel pada status **Soal dibuka**. Bel yang masuk sebelum atau sesudah status tersebut dicatat sebagai ditolak, tetapi tidak memengaruhi pertandingan.

## 5. Struktur Layar

### A. Konsol Pertandingan

Pekerjaan layar ini: membantu operator mengambil keputusan berikutnya dengan benar.

```text
+------------------------------------------------------------------+
| CCI An Nuur | Babak Penyisihan | Soal aktif | Bel digital siap    |
+------------------------------------------------------------------+
|                                                                  |
|   REGU B MENEKAN BEL PERTAMA                                     |
|   Timer jawaban: 00:08                                           |
|                                                                  |
|   [Jawaban Benar]     [Jawaban Salah]     [Batalkan Bel]          |
|                                                                  |
+-------------------------------------+----------------------------+
| Skor regu                           | Urutan soal                |
| Regu A     nilai saat ini           | Soal aktif                |
| Regu B     nilai saat ini           | Soal berikutnya           |
| Regu C     nilai saat ini           | Riwayat keputusan terbaru |
+-------------------------------------+----------------------------+
| [Jeda] [Buka Soal Berikutnya]                     [Akhiri Babak]  |
+------------------------------------------------------------------+
```

Fokus visual berubah sesuai status. Saat menunggu bel, tombol **Buka Soal** menjadi fokus. Saat bel terkunci, nama regu dan keputusan juri menjadi fokus. Kontrol lain diturunkan kontrasnya untuk mengurangi salah tekan.

### B. Layar Skor Publik

Pekerjaan layar ini: membuat peserta dan penonton memahami kondisi pertandingan dari jauh.

Isi yang ditampilkan:

- Nama acara dan babak.
- Status pertandingan: bersiap, soal dibuka, bel terkunci, atau waktu habis.
- Nama regu dan skor dalam ukuran besar.
- Regu yang memperoleh hak jawab.
- Timer soal atau timer jawaban.
- Nomor soal, jika panitia memilih menampilkannya.

Layar publik tidak menampilkan tombol, menu, riwayat koreksi, atau informasi teknis aplikasi.

### C. Persiapan Pertandingan

Pekerjaan layar ini: memastikan data lomba lengkap sebelum pertandingan dimulai.

- Identitas kegiatan dan tanggal.
- Daftar regu serta nama anggota opsional.
- Pemilihan babak.
- Aturan nilai yang diisi panitia.
- Durasi soal dan durasi jawaban.
- Pilihan penggunaan tombol bel dari konsol operator atau halaman regu.
- Pratinjau layar skor publik.
- Tombol **Mulai Simulasi** dan **Mulai Pertandingan**.

### D. Pengaturan Babak dan Nilai

Aturan dibuat dapat dikonfigurasi karena proposal tidak menetapkan nominal skor atau mekanisme pengurangan nilai.

- Jenis babak: wajib, lemparan, rebutan, atau format khusus panitia.
- Nilai jawaban benar.
- Pengurangan nilai jawaban salah, jika digunakan.
- Apakah soal boleh dilempar ke regu lain.
- Apakah regu yang salah boleh menekan kembali.
- Durasi menjawab.
- Jumlah soal.
- Aturan seri.

Nilai awal tidak diisi dengan angka rekaan. Panitia harus memilih aturan sebelum pertandingan dapat dimulai.

### E. Riwayat dan Koreksi

Setiap kejadian memiliki waktu, soal, regu, perubahan nilai, dan operator yang melakukan tindakan.

Contoh jenis kejadian:

- Soal dibuka.
- Regu menekan bel.
- Bel diterima atau ditolak.
- Jawaban dinilai benar atau salah.
- Nilai ditambah atau dikurangi.
- Keputusan dibatalkan.
- Babak diakhiri atau dibuka kembali.

Tombol **Batalkan keputusan terakhir** membuat kejadian koreksi baru. Data lama tetap tersimpan agar hasil dapat dijelaskan jika muncul keberatan.

### F. Hasil Pertandingan

- Peringkat akhir berdasarkan aturan babak.
- Rincian nilai setiap regu.
- Status seri jika aturan pemecah seri belum dijalankan.
- Ekspor hasil ke PDF atau spreadsheet.
- Cetak lembar hasil untuk tanda tangan panitia dan juri.

## 6. Simulasi Bel Digital

### Arsitektur yang disarankan

```text
[Tombol Regu di Browser] ---- POST ----+
                                      |
[Tombol Simulasi Operator] -- POST --> [PHP Native] --> [MySQL]
                                           |
                                           +--> [Konsol Operator]
                                           |
                                           +--> [Layar Skor Publik]
```

### Cara kerja

1. Regu menekan tombol besar pada halaman web, atau operator menekan tombol simulasi regu.
2. Browser mengirim permintaan `POST` berisi ID pertandingan dan ID regu.
3. PHP membuka transaksi MySQL dan mengunci baris pertandingan aktif.
4. Jika belum ada pemenang bel, regu disimpan sebagai pemenang. Permintaan berikutnya ditolak.
5. Konsol operator dan layar publik mengambil status terbaru melalui polling AJAX.
6. Operator membuka kembali bel saat soal berikutnya dimulai.

### Ketentuan penting

- Urutan bel ditentukan dari permintaan pertama yang berhasil disimpan MySQL.
- Endpoint bel menggunakan transaksi dan `SELECT ... FOR UPDATE` agar hanya satu regu yang menang.
- Tombol dinonaktifkan setelah ditekan untuk mencegah klik berulang.
- Validasi server tetap dilakukan karena JavaScript di browser dapat dimanipulasi.
- Untuk demo pada satu komputer, operator dapat menekan tombol simulasi Regu A, Regu B, atau Regu C.
- Bunyi konfirmasi dapat diputar oleh browser, tanpa perangkat tambahan.

## 7. Arsitektur Perangkat Lunak

### Bentuk produk

Aplikasi web PHP native yang berjalan pada web server biasa seperti Apache atau PHP built-in server. Data disimpan di MySQL. Konsol operator, halaman regu, dan layar publik dapat dibuka pada tab browser yang berbeda untuk kebutuhan demonstrasi.

### Komponen

- **Aplikasi operator:** konsol pertandingan dan konfigurasi.
- **Layar publik:** tampilan baca-saja untuk proyektor atau televisi.
- **Halaman regu:** tombol bel digital berbasis browser.
- **Endpoint PHP:** mesin status, skoring, dan penentu bel pertama.
- **Database MySQL:** menyimpan acara, regu, aturan, status, dan riwayat kejadian.
- **JavaScript vanilla:** mengirim aksi dengan `fetch()` dan memperbarui tampilan melalui polling.
- **Ekspor data:** cetak browser atau CSV untuk arsip panitia.

### Teknologi implementasi yang cocok

- Backend: PHP native tanpa framework.
- Database: MySQL dengan PDO dan prepared statement.
- Frontend: HTML5, CSS3, dan JavaScript vanilla.
- Pembaruan status: AJAX polling menggunakan `fetch()`.
- Autentikasi operator: session PHP.
- Tampilan: CSS responsif tanpa framework UI wajib.
- Lingkungan pengembangan: XAMPP, Laragon, MAMP, atau PHP dan MySQL lokal.

Polling berkala lebih mudah dijalankan pada PHP native dan cukup untuk mendemonstrasikan perubahan skor serta status bel.

### Struktur folder yang disarankan

```text
app-cerdas-cermat/
|-- index.php
|-- login.php
|-- operator.php
|-- scoreboard.php
|-- buzzer.php
|-- logout.php
|-- api/
|   |-- match-status.php
|   |-- open-question.php
|   |-- press-buzzer.php
|   |-- judge-answer.php
|   `-- undo-score.php
|-- admin/
|   |-- events.php
|   |-- teams.php
|   |-- rounds.php
|   `-- settings.php
|-- config/
|   `-- database.php
|-- includes/
|   |-- auth.php
|   |-- functions.php
|   `-- header.php
|-- assets/
|   |-- css/app.css
|   `-- js/app.js
`-- database/schema.sql
```

## 8. Model Data Inti

| Tabel MySQL | Data utama |
|---|---|
| `users` | nama, username, password hash, peran |
| `events` | nama kegiatan, lokasi, tanggal, status |
| `rounds` | event, nama babak, urutan, nilai benar, nilai salah, durasi |
| `teams` | event, nama regu, anggota opsional, kode akses bel, status |
| `matches` | round, nomor soal aktif, regu pemenang bel, status |
| `match_teams` | match, team, skor saat ini, urutan tampil |
| `questions` | round, nomor, jenis, teks soal opsional, jawaban opsional |
| `buzz_events` | match, team, waktu server, status diterima atau ditolak |
| `score_events` | match, team, nilai sebelum, perubahan, nilai sesudah, alasan, operator |

Skor akhir dihitung dari rangkaian kejadian skor. Nilai tersimpan pada pertandingan dapat dipakai sebagai cache, tetapi riwayat kejadian tetap menjadi sumber audit.

## 9. Kondisi Tidak Normal

| Kondisi | Respons aplikasi |
|---|---|
| Dua tombol ditekan hampir bersamaan | Transaksi MySQL hanya menerima permintaan pertama |
| Halaman regu kehilangan koneksi | Tombol dinonaktifkan dan halaman menampilkan perintah memuat ulang |
| Layar publik tertinggal | Polling berikutnya mengambil status terbaru dari server |
| Tab browser tertutup | Status pertandingan tetap tersimpan di MySQL |
| Salah memberi nilai | Operator membatalkan keputusan terakhir dengan alasan koreksi |
| Session operator berakhir | Operator diarahkan ke login tanpa mengubah pertandingan |
| Tidak ada data acara | Tampilkan alasan dan tombol **Siapkan Pertandingan** |
| Gagal memuat pertandingan | Tampilkan bagian yang gagal dan tombol **Coba Muat Ulang** |

## 10. Ruang Lingkup MVP

Versi pertama sebaiknya hanya memuat fungsi yang dibutuhkan untuk menjalankan satu lomba secara utuh:

1. Membuat acara, babak, dan regu.
2. Mengatur nilai dan timer.
3. Menyediakan tombol bel digital pada konsol dan halaman regu.
4. Menjalankan mesin status pertandingan.
5. Menentukan bel pertama dan mengunci bel lain.
6. Memberi keputusan benar, salah, atau batal.
7. Menampilkan skor publik melalui polling AJAX.
8. Membatalkan keputusan terakhir dengan riwayat audit.
9. Memulihkan tampilan pertandingan dari data MySQL.
10. Mencetak atau mengekspor hasil pertandingan ke CSV.
11. Menjalankan simulasi dari satu komputer.

### Fitur lanjutan

- Bank soal dengan dukungan teks Arab dan media.
- Penyusunan bagan penyisihan, semifinal, dan final.
- Panel juri pada halaman browser tersendiri.
- Filter dan pencarian riwayat pertandingan.
- Presensi peserta.
- Mode beberapa arena pertandingan.

Presensi tidak masuk MVP karena masalah utama proposal adalah bel dan skoring. Menambahkannya pada versi awal akan memperbesar pekerjaan tanpa memperbaiki jalannya pertandingan inti.

## 11. Arah Visual

### Design Read

Membaca produk ini sebagai aplikasi pertandingan komunitas untuk panitia nonteknis, dengan bahasa visual Islami kontemporer yang hangat dan tegas, dial **ENERGY 2 / RHYTHM 2 / MOTION 1**.

### Sistem visual yang disarankan

- **Latar krem terang:** nyaman untuk penggunaan siang hari dan sesuai ruang kegiatan yang terang.
- **Hijau zamrud gelap:** identitas utama dan kontras kuat untuk teks serta status siap.
- **Warna tinta gelap:** menjaga keterbacaan data pertandingan.
- **Aksen amber:** hanya untuk timer kritis dan regu yang memperoleh hak jawab.
- **Noto Sans:** terbaca baik untuk operator dan mendukung bahasa Indonesia.
- **Noto Naskh Arabic:** digunakan hanya ketika isi soal memerlukan aksara Arab.
- **Motif lengkung mihrab sederhana:** dipakai sebagai bingkai regu yang sedang aktif, bukan sebagai dekorasi di semua komponen.

Logo tidak dibuat pada tahap konsep. Sampai ada arahan pemilik produk, identitas ditampilkan sebagai teks **CCI An Nuur**.

### Alasan keputusan visual

| Keputusan | Alasan |
|---|---|
| Konsol berpusat pada status pertandingan | Operator perlu mengetahui satu tindakan berikutnya, bukan membaca dashboard umum |
| Palet terang | Kegiatan berlangsung pada siang hari dan ditampilkan melalui proyektor |
| Aksen hanya pada regu aktif dan timer | Dua elemen tersebut membutuhkan perhatian segera |
| Ukuran skor besar | Skor harus terbaca oleh peserta dan penonton dari jarak jauh |
| Gerak minimal | Animasi hanya menandai bel diterima dan perubahan status agar tidak mengganggu konsentrasi |
| Riwayat berbentuk urutan kejadian | Sengketa lebih mudah dijelaskan melalui kronologi dibanding grafik |
| Sudut komponen tidak seragam | Tombol tindakan, panel data, dan status memiliki fungsi serta hierarki berbeda |

## 12. Keamanan Operasional

- Layar publik hanya memiliki akses baca.
- Konsol operator memakai PIN acara atau sesi lokal.
- Aksi berisiko meminta konfirmasi dan alasan.
- Berkas ekspor tidak memuat data anak selain yang memang dimasukkan dan disetujui panitia.
- Nama anggota regu bersifat opsional. Nama regu cukup untuk menjalankan pertandingan.
- Backup otomatis dibuat setelah setiap keputusan skor.

## 13. Kriteria Keberhasilan MVP

MVP dinyatakan layak digunakan ketika:

- Dua atau lebih tombol yang ditekan hampir bersamaan hanya menghasilkan satu pemenang di database.
- Skor pada konsol dan layar publik selalu sama.
- Tampilan pertandingan dapat dipulihkan dari MySQL setelah tab browser ditutup.
- Operator baru dapat menyelesaikan simulasi tanpa bantuan pengembang.
- Semua perubahan skor memiliki riwayat dan dapat dikoreksi.
- Seluruh demonstrasi dapat dijalankan dari satu komputer melalui tombol simulasi operator.
- Konsol dapat digunakan dengan keyboard dan memiliki indikator fokus yang jelas.
- Tampilan tetap terbaca pada laptop, tablet, dan layar proyektor.

## 14. Keputusan yang Perlu Dikonfirmasi Sebelum Implementasi

1. Jumlah maksimum regu yang harus didukung dalam satu pertandingan.
2. Aturan nilai untuk setiap jenis babak.
3. Apakah soal disimpan di aplikasi atau tetap dibacakan dari dokumen terpisah.
4. Apakah juri memakai halaman sendiri atau keputusan tetap dimasukkan operator.
5. Apakah halaman bel regu diperlukan atau cukup tombol simulasi pada konsol operator.
6. Format hasil resmi yang diperlukan panitia.
7. Nama produk final, logo, dan warna identitas resmi.

## 15. Urutan Implementasi

1. Membuat skema MySQL dan koneksi PDO.
2. Membuat CRUD acara, babak, regu, dan aturan nilai.
3. Membuat mesin status pertandingan pada endpoint PHP.
4. Membuat konsol operator dan tombol simulasi bel.
5. Membuat layar skor publik dengan AJAX polling.
6. Membuat riwayat kejadian dan pembatalan nilai.
7. Menambahkan halaman bel regu jika diperlukan.
8. Menguji dua permintaan bel hampir bersamaan dan validasi session.
