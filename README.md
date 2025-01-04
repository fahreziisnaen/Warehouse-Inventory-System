<div align="center">

# 🏭 Warehouse Inventory System

[![Laravel](https://img.shields.io/badge/Laravel-11.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-3.0-fb70a9?style=for-the-badge&logo=php&logoColor=white)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)

Sistem manajemen inventaris gudang modern berbasis web menggunakan Laravel 11 dan Filament 3. 
Dirancang untuk memudahkan pengelolaan stok, pembelian, dan distribusi barang.

[Lihat Demo](http://your-demo-link.com) • [Laporkan Bug](http://your-repo-link/issues) • [Ajukan Fitur](http://your-repo-link/issues)

![Dashboard Preview](https://via.placeholder.com/800x400?text=Dashboard+Preview)

</div>

## ✨ Highlight Fitur

- 🏢 **Multi Warehouse Support** - Kelola beberapa gudang dalam satu sistem
- 📦 **Real-time Inventory** - Pantau stok secara real-time
- 🔄 **Purchase Order Management** - Otomatisasi proses pembelian
- 📱 **Responsive Design** - Akses dari desktop maupun mobile
- 🔒 **Role-based Access** - Kontrol akses berdasarkan peran
- 📊 **Advanced Analytics** - Dashboard dan laporan yang komprehensif

## 🚀 Quick Start

### Persyaratan Sistem

| Aplikasi | Versi Minimum |
|----------|---------------|
| PHP | 8.2 |
| Composer | 2.0 |
| MySQL/MariaDB | 5.7 |
| Node.js | 16.0 |
| NPM | 8.0 |

### Instalasi

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd warehouse-inventory-system
   ```

2. **Install Dependensi**
   ```bash
   composer install
   npm install
   ```

3. **Setup Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Database**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=warehouse_inventory
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Migrasi & Seeding**
   ```bash
   php artisan migrate:fresh --seed
   php artisan storage:link
   ```

6. **Jalankan Aplikasi**
   ```bash
   php artisan serve
   ```

## 🎯 Fitur Utama

### 📋 Master Data
- 🏭 Supplier Management
- ™️ Brand Management
- 🔢 Part Number Management
- 📦 Item Management
- 👥 Customer Management
- 📑 Project Management

### 💼 Transaksi
- 📝 Purchase Order
- 📥 Barang Masuk (Inbound)
- 📤 Barang Keluar (Outbound)

### 📊 Laporan & Analytics
- 📈 Laporan Inventaris Real-time
- 📊 Dashboard Interaktif
- 📉 Analisis Trend

## 🏗 Struktur Aplikasi

### 📁 Struktur Folder
```
warehouse-inventory-system/
├── app/
│   ├── Filament/
│   │   ├── Resources/     # CRUD resources
│   │   ├── Pages/        # Custom pages
│   │   └── Widgets/      # Dashboard widgets
│   ├── Models/           # Eloquent models
│   └── Providers/        # Service providers
├── database/
│   ├── migrations/       # Database migrations
│   └── seeders/         # Database seeders
├── public/
│   └── images/          # Assets
└── resources/
    └── views/
        └── filament/     # Custom views
```

### 💾 Schema Database
<details>
<summary>Klik untuk melihat detail</summary>

#### Core Tables
- `suppliers` - Informasi supplier
- `brands` - Data merek
- `part_numbers` - Katalog part number
- `items` - Inventaris barang
- `customers` - Data pelanggan
- `projects` - Informasi proyek

#### Transaction Tables
- `purchase_orders` - Data PO
- `inbound_records` - Record barang masuk
- `outbound_records` - Record barang keluar
- `inbound_items` - Detail item masuk
- `outbound_items` - Detail item keluar

</details>

## 👩‍💻 Development Guide

### Membuat Resource Baru
```bash
php artisan make:filament-resource NamaResource
```

### Membuat Widget
```bash
php artisan make:filament-widget NamaWidget
```

### Kustomisasi Tema
Edit `app/Providers/Filament/AdminPanelProvider.php`

## 🔧 Troubleshooting

<details>
<summary><b>Masalah Permission</b></summary>

```bash
chmod -R 775 storage bootstrap/cache
```
</details>

<details>
<summary><b>Update Composer</b></summary>

```bash
composer dump-autoload
```
</details>

<details>
<summary><b>Clear Cache</b></summary>

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```
</details>

## 🛡 Security

- 🔐 Autentikasi Filament
- 🔒 Password Hashing (bcrypt)
- 🛡️ CSRF Protection
- ✅ Form Validation
- 🚫 Middleware Authentication

## 🤝 Kontribusi

1. Fork repository ini
2. Buat branch baru (`git checkout -b fitur-keren`)
3. Commit perubahan (`git commit -am 'Menambah fitur keren'`)
4. Push ke branch (`git push origin fitur-keren`)
5. Buat Pull Request

## 📝 Lisensi

Project ini dilisensikan di bawah [MIT License](LICENSE).

---

<div align="center">

### 🌟 Star us on GitHub — it helps!

[Laporkan Bug](http://your-repo-link/issues) • [Dokumentasi](http://your-docs-link) • [Kontribusi](CONTRIBUTING.md)

</div>