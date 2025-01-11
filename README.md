# 📦 Sistem Manajemen Inventaris

> Sistem manajemen inventaris berbasis Laravel dengan kemampuan pelacakan nomor seri.

![PHP Version](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php&logoColor=white)
![Laravel Version](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![Filament Version](https://img.shields.io/badge/Filament-3.x-coral?style=flat-square)
![MySQL](https://img.shields.io/badge/MySQL-latest-4479A1?style=flat-square&logo=mysql&logoColor=white)

## 🌟 Gambaran Umum

Solusi manajemen inventaris yang komprehensif dirancang untuk menangani inventaris masuk dan keluar dengan pelacakan nomor seri yang presisi. Cocok untuk bisnis yang mengelola produk berseri, penyewaan, dan item batch.

## ✨ Fitur Utama

### 📋 Manajemen Data Master
- **Registrasi Merek** - Manajemen merek terpusat
- **Sistem Part Number** - Pelacakan part yang terorganisir dengan relasi merek
- **Manajemen Proyek** - Penanganan data proyek yang komprehensif
- **Manajemen Vendor** - Database pelanggan & pemasok yang terpadu
- **Format Satuan** - Sistem satuan yang fleksibel untuk item batch

### 📥 Proses Barang Masuk
- Pencatatan berbasis LPB (Lembar Penerimaan Barang)
- Dua metode input:
  - Pelacakan Nomor Seri
  - Pemrosesan Batch
- Validasi nomor seri real-time
- Pelacakan status
- Integrasi dengan Purchase Order

### 📤 Proses Barang Keluar
- Dokumentasi LKB (Lembar Keluar Barang)
- Beberapa metode pengiriman:
  - Berbasis Nomor Seri
  - Berbasis jumlah Batch
- Mendukung berbagai jenis transaksi:
  - Penyewaan
  - Penjualan
  - Peminjaman
- Validasi stok & status otomatis

### 📊 Manajemen Stok
- **Item dengan Nomor Seri:**
  - Pelacakan nomor seri yang presisi
  - Sistem status multi-kondisi
  - Riwayat perpindahan yang komprehensif
- **Item Batch:**
  - Manajemen stok berbasis kuantitas
  - Pencatatan riwayat transaksi
  - Validasi kuantitas real-time

### 🛍️ Sistem Purchase Order
- Pembuatan PO untuk pemasok
- Integrasi dengan barang masuk yang mulus
- Pemantauan status

## 🚀 Panduan Cepat

### Prasyarat
```bash
PHP >= 8.1
Composer
MySQL
```

### Instalasi

1. Clone repository
```bash
git clone [repository-url]
```

2. Install dependensi PHP
```bash
composer install
```

3. Konfigurasi environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Setup database
```bash
php artisan migrate
php artisan db:seed
```

5. Jalankan aplikasi
```bash
php artisan serve
```

## 💡 Panduan Penggunaan

### Alur Proses Barang Masuk
1. Buat Purchase Order (opsional)
2. Catat barang masuk:
   - Isi detail LPB dan proyek
   - Masukkan nomor seri atau jumlah batch
   - Sistem memvalidasi dan menyimpan data

### Alur Proses Barang Keluar
1. Inisiasi record barang keluar
2. Lengkapi form LKB dengan pelanggan dan tujuan
3. Pilih metode pengiriman:
   - Input nomor seri untuk item yang dilacak
   - Input kuantitas untuk item batch
4. Validasi stok dan pembaruan status otomatis

## 🔒 Aturan Bisnis

### Validasi Nomor Seri
- Nomor seri unik untuk item masuk baru
- Pengecekan ketersediaan berdasarkan status
- Penghapusan bersyarat berdasarkan status item

### Aturan Pemrosesan Batch
- Validasi kuantitas berdasarkan stok
- Penyesuaian inventaris otomatis
- Kepatuhan format satuan

## 🛠️ Pemeliharaan Data

### Manajemen Record
- **Barang Masuk:**
  - Penghapusan dibatasi untuk status 'diterima'
  - Penyesuaian stok otomatis saat penghapusan
- **Barang Keluar:**
  - Aturan penghapusan berdasarkan tujuan
  - Pemulihan stok saat penghapusan

### Manajemen Status
- Transisi status otomatis
- Jejak audit lengkap
- Pemantauan status real-time

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan kirim Pull Request.

## 📝 Lisensi

[Jenis Lisensi]

## 🔧 Dukungan

Untuk dukungan, silakan buka issue di repository GitHub.

---
Dibuat dengan ❤️ menggunakan Laravel & Filament
