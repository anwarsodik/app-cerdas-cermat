# CCI An Nuur

Dummy aplikasi cerdas cermat berbasis PHP native, MySQL, HTML, CSS, dan JavaScript vanilla.

## Kebutuhan

- PHP 8.1 atau lebih baru dengan ekstensi `pdo_mysql`
- MySQL 8 atau MariaDB 10.6 atau lebih baru
- Browser modern

## Instalasi

1. Buat database dan tabel:

   ```bash
   mysql -u root -p < database/schema.sql
   ```

2. Atur variabel lingkungan jika konfigurasi MySQL berbeda dari nilai bawaan pada `.env.example`.

3. Jalankan aplikasi dari folder proyek:

   ```bash
   php -S 127.0.0.1:8080
   ```

4. Buka `http://127.0.0.1:8080`.

## Akun awal

- Username: `admin`
- Password: `admin123`

Ganti password akun awal sebelum menggunakan aplikasi di luar demonstrasi lokal.

## Alur demonstrasi

1. Masuk sebagai operator.
2. Buat kegiatan dan pilih sebagai kegiatan aktif.
3. Tambahkan minimal dua regu.
4. Buat satu babak beserta aturan nilai.
5. Buka halaman Pertandingan dan mulai babak.
6. Buka soal, tekan tombol simulasi salah satu regu, lalu nilai jawabannya.
7. Buka Layar Publik pada tab lain untuk melihat pembaruan skor.

