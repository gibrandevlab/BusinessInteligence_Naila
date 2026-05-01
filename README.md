# 🍽️ Naila F&B ERP System

Naila ERP adalah sistem Perencanaan Sumber Daya Perusahaan (Enterprise Resource Planning) berbasis web yang dirancang khusus untuk bisnis *Food & Beverage* (F&B) skala menengah. Sistem ini menjembatani operasional dapur (Inventory & Produksi), penjualan (Kasir/POS), hingga pembukuan otomatis (Laporan Keuangan & SPK).

Dibangun dengan **Laravel 12, Tailwind CSS, dan Alpine.js**, sistem ini menggunakan pendekatan *Mobile-First Design*, menjadikannya sangat ringan, intuitif, dan siap diakses melalui tablet kasir maupun *smartphone* *owner*.

---

## 🌟 Fitur Utama (Core Modules)

### 1. 🛒 Modul Kasir Pintar (Point of Sale - POS)
- **Made to Order vs Ready Stock:** Mampu membedakan penjualan barang yang langsung dibuat (memotong bahan mentah) dan barang yang diambil dari etalase (stok matang).
- **Multi-Harga:** Mendukung skema harga fleksibel (Eceran, Reseller, Agen) di dalam satu layar.
- **Keranjang Cerdas (Alpine.js):** Sistem reaktif tanpa *reload* halaman untuk kalkulasi total bayar seketika.

### 2. 📦 Manajemen Inventori & Produksi (Warehouse)
- **Beli Bahan Baku (Purchase):** Terintegrasi dengan algoritma *Moving Average*. Setiap pembelian bahan dengan harga baru akan secara otomatis menghitung ulang harga rata-rata bahan di gudang tanpa campur tangan manusia.
- **Sistem Produksi (Manufacture):** Mengubah bahan mentah (beras, ayam) menjadi stok siap jual (Nasi Ayam) dalam satu klik. Sistem secara cerdas akan menyedot stok bahan baku sesuai takaran resep.
- **Stok Opname:** Penyesuaian stok fisik vs sistem. Jika ada bahan busuk/terbuang, sistem otomatis membukukannya sebagai "Kerugian" (Expense).

### 3. 🍳 Manajemen Resep Terpadu (Recipe & Costing)
- **Auto-Kalkulasi HPP:** Harga Pokok Penjualan (HPP) setiap menu dihitung secara super presisi sampai satuan gram/mililiter. Termasuk kalkulasi biaya kemasan (*packaging*) dan tenaga kerja (*overhead*).
- **Dynamic Sync:** Jika harga Telur naik di pasar, Anda hanya perlu input di "Beli Bahan". Sistem akan berkeliling mencari semua resep yang menggunakan telur dan meng-update nilai HPP-nya secara otomatis.

### 4. 📊 Dashboard & Laporan Keuangan (Finance)
- **Laba Bersih Riil:** Metrik keuangan yang tidak menipu. Menghitung (Total Omset Kasir) - (Total Belanja Bahan) - (Biaya Operasional) = Kas Bersih di Tangan.
- **Filter Pintar:** Mampu melihat arus kas harian, mingguan, bulanan, hingga sepanjang masa.

### 5. 🧠 Analisis Keputusan Menu (Sistem Pakar/SPK)
Sistem menggunakan **BCG Matrix** (Matriks Boston Consulting Group) untuk mengklasifikasikan menu secara *real-time* berdasarkan Penjualan (Laris) dan Margin (Untung):
- ⭐ **PRIMADONA:** Laris manis & Untung besar.
- 🐴 **KUDA BEBAN:** Sangat laris, tapi untungnya mepet. (Saran: naikkan harga).
- 🧩 **UNTUNG TAPI SEPI:** Untungnya besar, tapi jarang laku. (Saran: gencarkan promosi).
- 🐶 **MERUGIKAN:** Tidak laku & rugi bandar. (Saran: coret dari menu).

### 6. 🖨️ Pusat Cetak Laporan (PDF Generator)
Fasilitas sekali klik untuk mengunduh laporan resmi berbentuk PDF:
- **PDF Laporan Analisis SPK**
- **PDF Laporan Keuangan (Cash Flow & PnL)**
- **PDF Laporan Aset Inventori (Stock Opname)**

---

## 🛠️ Stack Teknologi
- **Backend:** Laravel 12.x (PHP 8.2+)
- **Frontend:** Blade Templating, Tailwind CSS (Styling), Alpine.js (Reactivity)
- **Database:** MySQL
- **Library Tambahan:** `barryvdh/laravel-dompdf` (Untuk *generate* dokumen laporan PDF)

---

## 🚀 Panduan Instalasi (Development)

Ikuti langkah-langkah berikut untuk menjalankan sistem ini di komputer/server Anda:

1. **Clone Repositori (Jika menggunakan Git):**
   ```bash
   git clone <url-repo>
   cd "Aplikasi Produksi Naila"
   ```

2. **Install Dependensi PHP & Node:**
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Buka file `.env`, sesuaikan nama database Anda (misal: `DB_DATABASE=naila_production`).*

4. **Jalankan Migrasi & Database Seeder:**
   *(PENTING: Langkah ini akan mengisi database dengan struktur lengkap beserta data dummy yang siap pakai seperti Resep, Bahan, Transaksi Kasir, dan Pengeluaran)*
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Kompilasi Aset Frontend:**
   ```bash
   npm run build
   # atau 'npm run dev' jika sedang tahap pengembangan aktif
   ```

6. **Jalankan Server:**
   ```bash
   php artisan serve
   ```
   Buka `http://127.0.0.1:8000` di browser Anda.

---

## 🔑 Hak Akses Pengguna (Role Base)
Sistem ini secara default menyiapkan data *login* awal melalui proses *seeding*:
- **Email:** `admin@naila.com` atau `kasir@naila.com`
- **Password:** `password`

*(Saat ini semua sistem dapat diakses secara merata untuk memudahkan presentasi, namun secara tabel sudah dipisah perannya).*

---

## 💡 Alur Kerja Harian (SOP Penggunaan)

Agar sistem menghasilkan kalkulasi yang akurat 100%, ikuti Standar Operasional berikut:
1. **Pagi Hari (Belanja):** Bagian gudang mencatat hasil belanja pasar di menu **Stok > Beli Bahan**.
2. **Pagi Hari (Masak):** Jika ada bahan baku yang diolah menjadi stok etalase (misal merebus ayam), catat di menu **Stok > Tambah Produksi**.
3. **Siang-Malam (Jualan):** Kasir melayani pelanggan hanya menggunakan halaman **Kasir**.
4. **Malam Hari (Tutup Toko):** Owner membuka menu **Dashboard** untuk melihat Laba Bersih harian. Jika ada staf dapur yang merusakkan bahan, catat di **Stok > Opname Fisik**.
5. **Akhir Bulan:** Owner masuk ke menu **Laporan > Catat Pengeluaran Operasional** untuk memasukkan biaya bayar Listrik, Air, dan Gaji Karyawan. Lalu cetak seluruh PDF untuk dievaluasi bersama manajemen.

---
*Dikembangkan dengan ❤️ oleh Tim Sistem Informasi.*
