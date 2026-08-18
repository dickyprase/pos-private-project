# PLAN.md — Aplikasi POS Coffee Shop

## 1. Ringkasan Proyek

Aplikasi ini adalah **Point of Sale (POS) untuk satu coffee shop** dengan ketentuan utama:

- **1 aplikasi untuk 1 toko**.
- Menggunakan **Laravel 12** sebagai framework utama.
- Menggunakan **Livewire** agar proses kasir berjalan interaktif tanpa full-page reload.
- Menggunakan **Blade, Alpine.js, Tailwind CSS, dan Vite** untuk antarmuka.
- Dibangun sebagai **full monolith**: frontend, backend, autentikasi, business logic, dan reporting berada dalam satu aplikasi Laravel.
- Dioptimalkan untuk tablet dan desktop yang digunakan oleh kasir.
- Memiliki UI/UX modern, cepat, responsif, dan touch-friendly.
- Role awal hanya `OWNER`, `MANAGER`, dan `CASHIER`.
- Tidak menggunakan role atau layar khusus barista.

Fokus utama MVP adalah membuat alur berikut berjalan cepat dan stabil:

```text
Login kasir
→ Buka shift
→ Pilih produk
→ Pilih modifier
→ Checkout
→ Pembayaran
→ Cetak struk
→ Stok otomatis berkurang
```

---

## 2. Keputusan Produk

### 2.1 Scope awal

Aplikasi hanya digunakan oleh satu coffee shop dan satu database.

Tidak ada:

- Multi-tenant.
- Multi-vendor.
- Multi-cabang.
- Database terpisah per toko.
- Domain atau subdomain per toko.
- Aplikasi pelanggan terpisah.
- Kitchen display atau barista display.
- Progressive Web App (PWA).
- Transaksi offline atau sinkronisasi offline.

### 2.2 Prinsip pengembangan

- Dahulukan alur transaksi kasir.
- Jangan membuat microservices.
- Jangan membuat API-first apabila belum dibutuhkan.
- Gunakan Livewire actions untuk mayoritas interaksi aplikasi.
- Gunakan JavaScript seminimal mungkin.
- Gunakan Alpine.js hanya untuk interaksi UI lokal yang lebih nyaman.
- Modul laporan dan inventori lanjutan dikerjakan setelah transaksi stabil.

### 2.3 Scope aplikasi web

Aplikasi dibuat sebagai **web application online** dan belum menggunakan PWA maupun mekanisme transaksi offline.

Prioritas MVP:

- Halaman POS tidak reload saat menambahkan produk.
- Cart tidak hilang ketika modal dibuka atau filter produk berubah.
- Checkout tidak memindahkan kasir ke banyak halaman.
- Loading state jelas dan singkat.
- Transaksi tersimpan aman di server MySQL.
- Koneksi internet atau jaringan lokal wajib tersedia saat transaksi.

---

## 3. Tujuan Produk

1. Mempercepat input pesanan dan pembayaran.
2. Mengurangi kesalahan pencatatan pesanan.
3. Memudahkan kasir menggunakan aplikasi melalui tablet atau desktop.
4. Menyimpan data transaksi, pembayaran, shift, dan stok secara konsisten.
5. Memberikan laporan harian yang mudah dibaca oleh owner.
6. Mengurangi perpindahan halaman selama proses transaksi.
7. Menyediakan fondasi yang mudah dirawat oleh tim kecil.

---

## 4. Tipe Pengguna

## 4.1 Owner

Owner memiliki akses penuh terhadap:

- Dashboard.
- Transaksi.
- Produk dan kategori.
- Modifier dan varian.
- Inventori dan resep.
- User dan role.
- Shift.
- Laporan.
- Pengaturan toko.
- Void dan refund.
- Audit log.

## 4.2 Manager

Manager memiliki akses terhadap:

- Dashboard operasional.
- POS.
- Produk dan ketersediaan menu.
- Stok.
- Shift.
- Laporan harian.
- Persetujuan void dan refund.
- Penyesuaian stok sesuai permission.

## 4.3 Cashier

Cashier memiliki akses terhadap:

- Membuka shift.
- Menjalankan halaman POS.
- Membuat dan menahan pesanan.
- Menerima pembayaran.
- Mencetak ulang struk.
- Melihat transaksi dalam shift aktif.
- Menutup shift.

---

## 5. Modul Aplikasi

## 5.1 Authentication dan Authorization

Fitur:

- Login dengan username atau email dan password.
- Logout.
- Reset password oleh owner.
- User aktif/nonaktif.
- Role-based access control.
- PIN manager untuk aksi sensitif.
- Session Laravel.
- Audit login dan aksi sensitif.

Role awal:

```text
OWNER
MANAGER
CASHIER
```

Implementasi:

- Laravel authentication berbasis session.
- Middleware untuk autentikasi.
- Policies atau Gates untuk permission.
- Password di-hash menggunakan mekanisme hashing Laravel.
- PIN manager disimpan dalam bentuk hash.

---

## 5.2 POS / Kasir

Halaman POS merupakan halaman paling penting dan harus dibuat sebagai satu pengalaman interaktif tanpa full-page reload.

### Fitur utama

- Daftar kategori.
- Grid produk.
- Pencarian produk.
- Filter kategori.
- Produk favorit.
- Status produk tersedia/habis.
- Tambah produk ke cart.
- Ubah jumlah produk.
- Hapus item dari cart.
- Catatan per item.
- Pilih varian ukuran.
- Pilih modifier.
- Perhitungan harga otomatis.
- Dine in dan take away.
- Nomor meja opsional.
- Hold order.
- Resume held order.
- Diskon sederhana.
- Pajak dan service charge.
- Pembayaran cash dan non-cash manual.
- Perhitungan kembalian.
- Simpan transaksi.
- Cetak struk.
- Riwayat transaksi shift aktif.

### Contoh modifier

- Size: Regular, Large.
- Sugar: No Sugar, Less Sugar, Normal.
- Milk: Fresh Milk, Oat Milk, Soy Milk.
- Extra: Extra Shot, Syrup, Whipped Cream.

### Alur transaksi

1. Kasir membuka shift.
2. Kasir memilih jenis pesanan.
3. Kasir memilih produk.
4. Modal modifier muncul jika diperlukan.
5. Produk masuk ke cart tanpa reload halaman.
6. Kasir mengubah kuantitas atau menambahkan catatan.
7. Sistem menghitung subtotal, diskon, pajak, service charge, dan total.
8. Kasir membuka panel pembayaran.
9. Kasir memilih metode pembayaran.
10. Sistem menyimpan order dan payment dalam database transaction.
11. Sistem mengurangi stok sesuai resep.
12. Sistem menampilkan status berhasil dan membuka struk.
13. Cart dikosongkan tanpa pindah halaman.

### Interaksi Livewire

Halaman POS menggunakan komponen utama:

```text
App\Livewire\Pos\CashierScreen
```

Subkomponen yang disarankan:

```text
App\Livewire\Pos\CategoryFilter
App\Livewire\Pos\ProductGrid
App\Livewire\Pos\CartPanel
App\Livewire\Pos\ModifierModal
App\Livewire\Pos\PaymentModal
App\Livewire\Pos\HeldOrdersModal
```

Namun, pada MVP sebaiknya hindari terlalu banyak nested Livewire component yang saling bergantung. Cart dan checkout dapat tetap berada dalam satu komponen utama agar state lebih mudah dikontrol.

### Aturan performa POS

- Jangan reload seluruh halaman saat produk ditambahkan.
- Jangan query seluruh catalog pada setiap klik.
- Cache catalog yang aktif.
- Gunakan eager loading untuk relasi modifier.
- Gunakan pagination atau lazy loading bila jumlah produk sangat banyak.
- Gunakan `wire:key` yang stabil pada setiap item.
- Gunakan loading indicator hanya pada area yang sedang diproses.
- Gunakan Alpine.js untuk membuka/menutup drawer atau modal yang tidak membutuhkan server.
- Batasi ukuran state Livewire agar payload tetap kecil.
- Jangan menyimpan object model Eloquent besar sebagai state komponen.
- Simpan hanya ID, quantity, snapshot nama, dan nominal yang dibutuhkan.

---

## 5.3 Manajemen Menu

Fitur:

- Kategori produk.
- Produk.
- SKU.
- Foto produk.
- Deskripsi.
- Harga dasar.
- Estimasi modal.
- Status aktif/nonaktif.
- Status tersedia/habis sementara.
- Urutan tampilan.
- Produk favorit.
- Varian ukuran.
- Modifier group.
- Modifier option.
- Harga tambahan modifier.
- Minimal dan maksimal pilihan modifier.

Aturan:

- Produk nonaktif tidak muncul di POS.
- Produk habis tetap dapat terlihat dengan status disabled, jika diinginkan.
- Harga lama pada transaksi tidak berubah ketika harga master diperbarui.
- Produk sebaiknya dinonaktifkan, bukan dihapus permanen, apabila sudah memiliki transaksi.

---

## 5.4 Shift dan Cash Management

Fitur:

- Buka shift.
- Modal kas awal.
- Cash in.
- Cash out.
- Total transaksi tunai.
- Total transaksi non-tunai.
- Estimasi kas.
- Input kas aktual.
- Selisih kas.
- Catatan selisih.
- Tutup shift.
- Laporan shift.

Aturan:

- Cashier wajib memiliki shift aktif sebelum membuat transaksi.
- Satu cashier hanya dapat memiliki satu shift aktif.
- Shift yang ditutup tidak dapat diedit langsung.
- Koreksi dilakukan melalui owner atau manager dengan audit log.

Status shift:

```text
OPEN
CLOSED
```

---

## 5.5 Payment

Metode pembayaran awal:

```text
CASH
QRIS
DEBIT_CARD
CREDIT_CARD
BANK_TRANSFER
EWALLET
OTHER
```

MVP menggunakan pencatatan pembayaran manual.

Data payment:

- Order.
- Metode pembayaran.
- Jumlah tagihan.
- Jumlah diterima.
- Kembalian.
- Nomor referensi opsional.
- Status pembayaran.
- Kasir.
- Waktu pembayaran.

Status:

```text
PENDING
PAID
PARTIALLY_REFUNDED
REFUNDED
FAILED
```

Integrasi payment gateway atau QRIS dinamis bukan bagian dari MVP.

---

## 5.6 Receipt

Fitur:

- Struk ukuran 58 mm.
- Struk ukuran 80 mm.
- Nama dan alamat toko.
- Nomor transaksi.
- Nama kasir.
- Waktu transaksi.
- Item dan modifier.
- Subtotal.
- Diskon.
- Pajak.
- Service charge.
- Total.
- Metode pembayaran.
- Uang diterima dan kembalian.
- Footer struk.
- Cetak ulang.

Implementasi awal:

- Blade view khusus print.
- CSS print khusus thermal.
- Browser print dialog.

Direct print tanpa dialog dan pembukaan cash drawer dapat ditambahkan setelah printer target ditentukan.

---

## 5.7 Inventory dan Recipe

### Inventory item

- Nama bahan.
- SKU.
- Satuan.
- Stok saat ini.
- Minimum stock.
- Average cost.
- Status aktif.

### Stock movement

```text
PURCHASE
SALE_USAGE
ADJUSTMENT
WASTE
RETURN
OPNAME
```

### Recipe

Setiap produk atau varian dapat memiliki resep.

Contoh Cafe Latte Regular:

- Coffee bean: 18 gram.
- Fresh milk: 180 ml.
- Cup 12 oz: 1 pcs.
- Lid: 1 pcs.

Saat transaksi berhasil:

- Sistem membuat order.
- Sistem membuat payment.
- Sistem membuat stock movement.
- Sistem memperbarui stok.
- Seluruh proses berjalan dalam satu database transaction.

Aturan:

- Pengurangan stok tidak boleh terjadi dua kali.
- Void atau refund tidak otomatis mengembalikan stok tanpa aturan yang jelas.
- Adjustment dan waste harus memiliki alasan.
- Stock opname menghasilkan selisih dan movement baru.

---

## 5.8 Customer

Fitur opsional untuk MVP:

- Nama.
- Nomor telepon.
- Email.
- Catatan.
- Riwayat transaksi.
- Total transaksi.
- Total belanja.

Customer tidak wajib diisi saat checkout.

---

## 5.9 Promotion dan Discount

MVP:

- Diskon nominal.
- Diskon persentase.
- Diskon per order.
- Diskon per item.
- Batas diskon berdasarkan role.
- Alasan diskon tertentu.

Setelah MVP:

- Promo kode.
- Buy one get one.
- Bundling.
- Happy hour.
- Loyalty point.

---

## 5.10 Dashboard dan Reports

### Dashboard owner

- Penjualan hari ini.
- Jumlah transaksi.
- Average order value.
- Produk terlaris.
- Penjualan per jam.
- Penjualan per metode pembayaran.
- Diskon.
- Refund.
- Stok hampir habis.

### Laporan

- Penjualan harian.
- Penjualan per periode.
- Penjualan per produk.
- Penjualan per kategori.
- Penjualan per kasir.
- Penjualan per metode pembayaran.
- Shift.
- Cash movement.
- Void dan refund.
- Inventory movement.
- Waste.
- Estimasi laba kotor.

Ekspor:

- CSV pada MVP.
- Excel dan PDF setelah MVP.

---

## 5.11 Settings

Pengaturan toko:

- Nama toko.
- Logo.
- Alamat.
- Nomor telepon.
- Mata uang.
- Zona waktu.
- Pajak.
- Service charge.
- Pembulatan.
- Prefix nomor transaksi.
- Footer struk.
- Metode pembayaran aktif.
- Kebijakan stok negatif.
- Warna tema.

Default:

```text
Currency: IDR
Timezone: Asia/Jakarta
```

Karena hanya satu toko, settings dapat menggunakan single-row table.

---

## 6. UI/UX

## 6.1 Prinsip desain

- Cepat dipahami kasir baru.
- Touch-friendly.
- Informasi utama terlihat jelas.
- Aksi checkout selalu mudah ditemukan.
- Tidak banyak perpindahan halaman.
- Tidak banyak modal bertumpuk.
- Error harus menjelaskan tindakan berikutnya.
- Loading state tidak menutup seluruh layar kecuali saat menyimpan pembayaran.
- Tombol berbahaya memiliki konfirmasi.

## 6.2 Layout POS desktop dan tablet landscape

Tiga area utama:

```text
Sidebar kategori | Grid produk | Cart dan total
```

Proporsi awal:

- Kategori: 12–16%.
- Produk: 50–58%.
- Cart: 30–35%.

## 6.3 Layout tablet portrait

- Kategori horizontal.
- Product grid 2–3 kolom.
- Cart menjadi drawer atau bottom sheet.
- Tombol cart dan checkout sticky.

## 6.4 Layout mobile

- Cocok untuk monitoring owner dan transaksi darurat.
- Product grid 2 kolom.
- Cart menggunakan sticky bottom bar.
- Menu back-office menggunakan navigation drawer.

## 6.5 Komponen utama

- App shell.
- Sidebar.
- Top navigation.
- Category chip.
- Product card.
- Cart item.
- Quantity control.
- Modifier modal.
- Payment modal.
- Number pad.
- Confirmation dialog.
- Toast.
- Badge status.
- Table.
- Empty state.
- Skeleton loader.
- Error state.

## 6.6 Design system

Gunakan token untuk:

- Color.
- Typography.
- Spacing.
- Radius.
- Shadow.
- Motion.
- Z-index.

Arah visual:

- Warm neutral.
- Bersih dan modern.
- Kontras tinggi.
- Warna aksen yang konsisten.
- Tidak terlalu banyak warna dekoratif.
- Light mode sebagai default.

## 6.7 Keyboard shortcut

Shortcut yang dapat ditambahkan:

```text
F2  : fokus pencarian produk
F4  : buka held orders
F8  : buka pembayaran
Esc : tutup modal
+   : tambah quantity
-   : kurangi quantity
```

Shortcut tidak boleh menggantikan tombol visual.

---

## 7. Tech Stack

## 7.1 Core stack

- **PHP 8.2 atau versi yang kompatibel dengan Laravel 12**.
- **Laravel 12**.
- **Livewire** versi stabil yang kompatibel dengan Laravel 12.
- Blade.
- Alpine.js.
- Tailwind CSS.
- Vite.
- Composer.
- Node.js hanya untuk build asset frontend.

## 7.2 Database

Database utama:

- **MySQL 8.0 atau lebih baru**.
- Storage engine menggunakan **InnoDB** agar mendukung transaction, row-level locking, dan foreign key.
- Character set menggunakan `utf8mb4`.
- Collation direkomendasikan `utf8mb4_unicode_ci` atau collation `utf8mb4` yang sesuai dengan versi MySQL.
- Gunakan versi MySQL yang sama atau kompatibel pada development, staging, dan production.

Konfigurasi awal `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coffee_pos
DB_USERNAME=coffee_pos_user
DB_PASSWORD=
```

## 7.3 Authentication

- Laravel session authentication.
- Middleware `auth`.
- Policies dan Gates.
- Secure HTTP-only cookie.
- CSRF protection bawaan Laravel.
- Rate limiting login.

## 7.4 Frontend state

- Livewire state untuk data yang berkaitan dengan server dan transaksi.
- Alpine.js untuk UI state lokal seperti drawer, dropdown, tabs, dan modal ringan.
- Session Laravel dapat digunakan untuk held cart sederhana apabila dibutuhkan.
- Jangan menggunakan Redux, Zustand, atau SPA state library lain.

## 7.5 Cache dan queue

Cache:

- Catalog aktif.
- Settings toko.
- Dashboard summary dengan TTL pendek.

Driver yang dapat digunakan:

- Database untuk setup sederhana.
- Redis jika trafik dan kebutuhan queue meningkat.

Queue digunakan untuk proses non-kritis seperti:

- Ekspor laporan besar.
- Pengiriman email.
- Pengiriman notifikasi.

Penyimpanan order dan payment tidak boleh bergantung pada queue.

## 7.6 Testing

- Pest atau PHPUnit.
- Laravel feature test.
- Livewire component test.
- Browser test untuk alur kritis bila diperlukan.

## 7.7 Code quality

- Laravel Pint.
- Static analysis menggunakan Larastan/PHPStan apabila diterapkan.
- Conventional commit opsional.
- Pull request review untuk perubahan besar.

---

## 8. Arsitektur Sistem

Gunakan **Laravel modular monolith**.

```text
Browser
  |
  | HTTP + Livewire requests
  v
Laravel 12 Application
  |-- Routes
  |-- Middleware
  |-- Livewire Components
  |-- Controllers untuk halaman non-Livewire bila diperlukan
  |-- Form Requests
  |-- Actions / Services
  |-- Policies
  |-- Eloquent Models
  |-- Jobs
  |-- Reports
  |
  v
MySQL 8.0+
```

### Alasan monolith

- Deployment sederhana.
- Satu codebase.
- Satu database.
- Cocok untuk satu toko.
- Business transaction lebih mudah dijaga.
- Development lebih cepat.
- Debugging lebih mudah dibanding frontend-backend terpisah.

### Modul domain

```text
Auth
Users
Catalog
Orders
Payments
Shifts
Inventory
Customers
Promotions
Reports
Settings
Audit
```

---

## 9. Struktur Folder

```text
app/
├── Actions/
│   ├── Orders/
│   │   ├── CreateOrder.php
│   │   ├── CompleteOrder.php
│   │   ├── HoldOrder.php
│   │   ├── VoidOrder.php
│   │   └── RefundOrder.php
│   ├── Shifts/
│   └── Inventory/
├── Enums/
│   ├── UserRole.php
│   ├── OrderStatus.php
│   ├── OrderType.php
│   ├── PaymentMethod.php
│   ├── PaymentStatus.php
│   └── StockMovementType.php
├── Exceptions/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Livewire/
│   ├── Auth/
│   ├── Dashboard/
│   ├── Pos/
│   │   ├── CashierScreen.php
│   │   ├── HeldOrders.php
│   │   └── TransactionHistory.php
│   ├── Catalog/
│   ├── Inventory/
│   ├── Shifts/
│   ├── Reports/
│   ├── Users/
│   └── Settings/
├── Models/
├── Policies/
├── Providers/
├── Services/
│   ├── PricingService.php
│   ├── OrderNumberService.php
│   ├── InventoryService.php
│   ├── ShiftService.php
│   └── ReceiptService.php
└── Support/
    ├── Money.php
    └── DateRange.php

resources/
├── css/
│   └── app.css
├── js/
│   └── app.js
└── views/
    ├── components/
    ├── layouts/
    ├── livewire/
    │   ├── pos/
    │   ├── catalog/
    │   ├── inventory/
    │   └── reports/
    └── receipts/

routes/
├── web.php
├── console.php
└── api.php              # Tidak wajib digunakan pada MVP

database/
├── factories/
├── migrations/
└── seeders/

tests/
├── Feature/
├── Unit/
└── Browser/
```

---

## 10. Data Model Awal

## 10.1 Users

### users

- `id`
- `name`
- `username`
- `email`
- `password`
- `pin`
- `role`
- `is_active`
- `last_login_at`
- `remember_token`
- timestamps

### audit_logs

- `id`
- `user_id`
- `action`
- `entity_type`
- `entity_id`
- `before_data` JSON nullable
- `after_data` JSON nullable
- `ip_address`
- `user_agent`
- `created_at`

---

## 10.2 Catalog

### categories

- `id`
- `name`
- `slug`
- `sort_order`
- `is_active`
- timestamps

### products

- `id`
- `category_id`
- `name`
- `slug`
- `sku`
- `description`
- `image_path`
- `base_price`
- `cost_estimate`
- `is_active`
- `is_available`
- `is_favorite`
- `sort_order`
- timestamps
- soft deletes opsional

### product_variants

- `id`
- `product_id`
- `name`
- `sku`
- `price_adjustment`
- `is_default`
- `is_active`
- timestamps

### modifier_groups

- `id`
- `name`
- `selection_type`
- `min_selection`
- `max_selection`
- `is_required`
- timestamps

### modifier_options

- `id`
- `modifier_group_id`
- `name`
- `price_adjustment`
- `is_active`
- timestamps

### modifier_group_product

- `product_id`
- `modifier_group_id`
- `sort_order`

---

## 10.3 Orders

### orders

- `id`
- `order_number`
- `shift_id`
- `cashier_id`
- `customer_id` nullable
- `order_type`
- `table_number` nullable
- `status`
- `subtotal`
- `discount_total`
- `tax_total`
- `service_charge_total`
- `rounding_total`
- `grand_total`
- `notes`
- `paid_at` nullable
- timestamps

### order_items

- `id`
- `order_id`
- `product_id` nullable
- `product_variant_id` nullable
- `product_name_snapshot`
- `variant_name_snapshot` nullable
- `sku_snapshot` nullable
- `unit_price`
- `quantity`
- `discount_total`
- `line_total`
- `notes` nullable
- timestamps

### order_item_modifiers

- `id`
- `order_item_id`
- `modifier_option_id` nullable
- `name_snapshot`
- `price_adjustment`
- `quantity`
- timestamps

Held order cukup menggunakan tabel `orders` dengan status `HELD`.

---

## 10.4 Payments

### payments

- `id`
- `order_id`
- `method`
- `status`
- `amount`
- `received_amount`
- `change_amount`
- `reference_number` nullable
- `paid_at`
- `created_by`
- timestamps

### refunds

- `id`
- `order_id`
- `payment_id`
- `amount`
- `reason`
- `approved_by`
- `created_by`
- timestamps

---

## 10.5 Inventory

### units

- `id`
- `name`
- `symbol`
- `precision`
- timestamps

### inventory_items

- `id`
- `unit_id`
- `name`
- `sku`
- `current_stock`
- `minimum_stock`
- `average_cost`
- `allow_negative_stock`
- `is_active`
- timestamps

### recipes

- `id`
- `product_id`
- `product_variant_id` nullable
- `inventory_item_id`
- `quantity`
- timestamps

### modifier_recipes

- `id`
- `modifier_option_id`
- `inventory_item_id`
- `quantity`
- timestamps

### stock_movements

- `id`
- `inventory_item_id`
- `type`
- `quantity`
- `unit_cost` nullable
- `reference_type` nullable
- `reference_id` nullable
- `notes` nullable
- `created_by`
- `created_at`

### stock_opnames

- `id`
- `status`
- `started_by`
- `completed_by` nullable
- `started_at`
- `completed_at` nullable

### stock_opname_items

- `id`
- `stock_opname_id`
- `inventory_item_id`
- `system_quantity`
- `actual_quantity`
- `difference`

---

## 10.6 Shift

### shifts

- `id`
- `cashier_id`
- `opened_at`
- `closed_at` nullable
- `opening_cash`
- `expected_cash`
- `actual_cash` nullable
- `difference` nullable
- `status`
- `notes` nullable
- timestamps

### cash_movements

- `id`
- `shift_id`
- `type`
- `amount`
- `reason`
- `created_by`
- `created_at`

---

## 10.7 Customer dan Settings

### customers

- `id`
- `name`
- `phone` nullable
- `email` nullable
- `notes` nullable
- timestamps

### store_settings

- `id`
- `store_name`
- `logo_path` nullable
- `address`
- `phone`
- `currency`
- `timezone`
- `tax_rate`
- `service_charge_rate`
- `receipt_footer` nullable
- `allow_negative_stock`
- `transaction_prefix`
- timestamps

---

## 11. Enum Utama

```text
UserRole:
OWNER | MANAGER | CASHIER

OrderType:
DINE_IN | TAKE_AWAY

OrderStatus:
DRAFT | HELD | COMPLETED | CANCELLED | REFUNDED

PaymentStatus:
PENDING | PAID | PARTIALLY_REFUNDED | REFUNDED | FAILED

PaymentMethod:
CASH | QRIS | DEBIT_CARD | CREDIT_CARD | BANK_TRANSFER | EWALLET | OTHER

StockMovementType:
PURCHASE | SALE_USAGE | ADJUSTMENT | WASTE | RETURN | OPNAME

CashMovementType:
CASH_IN | CASH_OUT

ShiftStatus:
OPEN | CLOSED
```

Gunakan PHP backed enum agar nilai status konsisten di seluruh aplikasi.

---

## 12. Livewire Component Design

## 12.1 CashierScreen

State utama:

- Search term.
- Active category.
- Order type.
- Table number.
- Cart items.
- Selected customer.
- Discount.
- Notes.
- Payment input.

Method utama:

```text
selectCategory()
searchProducts()
openModifierModal()
addItem()
incrementItem()
decrementItem()
removeItem()
updateItemNotes()
holdOrder()
resumeOrder()
openPayment()
completePayment()
resetCart()
```

Computed data:

```text
subtotal
discountTotal
taxTotal
serviceChargeTotal
grandTotal
changeAmount
cartItemCount
```

## 12.2 Validasi

- Validasi server tetap wajib.
- Quantity minimal 1.
- Produk harus aktif dan tersedia.
- Harga tidak boleh dipercaya dari browser.
- Sistem membaca ulang harga master saat transaksi disimpan.
- Snapshot harga dan nama disimpan ke order item.
- Discount harus sesuai permission.
- Cashier harus memiliki shift aktif.

## 12.3 Event

Event yang dapat digunakan:

```text
product-added
cart-updated
open-modifier-modal
open-payment-modal
order-completed
receipt-ready
show-toast
```

Gunakan event hanya jika komunikasi antarbagian memang dibutuhkan. Jangan membuat event chain yang sulit dilacak.

---

## 13. Business Rules

1. Nominal uang disimpan sebagai integer dalam rupiah atau decimal yang aman.
2. Jangan menggunakan floating point biasa untuk uang.
3. Harga dan nama produk disimpan sebagai snapshot pada transaksi.
4. Order yang sudah dibayar tidak boleh diedit langsung.
5. Koreksi dilakukan melalui void atau refund.
6. Void dan refund wajib memiliki alasan.
7. Aksi sensitif memerlukan role atau PIN manager.
8. Cashier wajib memiliki shift aktif.
9. Order, payment, dan stock movement disimpan dalam satu database transaction.
10. Jika salah satu proses gagal, seluruh transaksi harus rollback.
11. Produk yang pernah digunakan dalam transaksi tidak dihapus permanen.
12. Settings dan laporan menggunakan zona waktu toko.
13. Setiap perubahan harga, stok, refund, dan diskon penting masuk audit log.
14. Nomor order harus unik.
15. Stok negatif hanya diperbolehkan apabila settings mengizinkan.
16. Held order tidak mengurangi stok sebelum pembayaran selesai.
17. Refresh browser tidak boleh menggandakan transaksi.
18. Tombol pembayaran harus disabled ketika request sedang diproses.
19. Gunakan idempotency token atau submission token pada proses pembayaran untuk mencegah double submit.

---

## 14. Routing

Contoh route web:

```text
/login
/dashboard
/pos
/orders
/orders/{order}
/shifts
/products
/categories
/modifiers
/inventory
/inventory/movements
/inventory/opname
/customers
/reports/sales
/reports/products
/reports/payments
/reports/shifts
/settings
/users
```

Sebagian besar halaman menggunakan route yang merender Livewire component.

API tidak perlu dibuat pada MVP. API baru ditambahkan ketika terdapat kebutuhan integrasi eksternal atau aplikasi lain di masa depan.

---

## 15. Security Requirements

- Validasi seluruh input di server.
- Authorization menggunakan Policies atau Gates.
- CSRF protection aktif.
- Session cookie secure dan HTTP-only di production.
- Rate limit login.
- Password dan PIN di-hash.
- Jangan menyimpan data kartu pembayaran.
- Upload foto produk harus divalidasi.
- Batasi tipe dan ukuran file.
- Secrets disimpan di environment variables.
- Audit log untuk aksi sensitif.
- Jangan menampilkan stack trace di production.
- Backup MySQL berkala.
- Gunakan HTTPS di production.
- Jangan memberikan permission owner kepada cashier.
- Query laporan dan filter harus divalidasi.

---

## 16. Performance Requirements

Target pengalaman POS:

- Menambah produk terasa instan.
- Tidak ada full-page reload selama menyusun pesanan.
- Search dan filter terasa responsif.
- Modifier modal terbuka cepat.
- Cart tetap terlihat selama transaksi.
- Tombol checkout memiliki feedback segera.
- Double click tidak menghasilkan transaksi ganda.

Optimasi Laravel dan Livewire:

- Cache catalog aktif.
- Eager load variant dan modifier yang diperlukan.
- Gunakan database index.
- Hindari query di dalam loop Blade.
- Hindari menyimpan collection Eloquent besar di property Livewire.
- Gunakan pagination pada riwayat transaksi.
- Gunakan debounce pada pencarian.
- Gunakan lazy loading untuk dashboard dan laporan.
- Gunakan production cache untuk config, route, dan view.
- Optimalkan gambar produk.
- Pantau jumlah query halaman POS.

Target awal:

- Interaksi UI lokal di bawah 100 ms.
- Respons Livewire untuk cart idealnya di bawah 500 ms pada jaringan toko yang stabil.
- Penyimpanan transaksi idealnya di bawah 2 detik.
- Halaman POS tetap nyaman digunakan dengan minimal 200 produk aktif.

---

## 17. Database Index

Index awal:

- `users.username` unique.
- `users.email` unique nullable.
- `products.sku` unique.
- `products(category_id, is_active, is_available, sort_order)`.
- `orders.order_number` unique.
- `orders(created_at)`.
- `orders(status, created_at)`.
- `orders(shift_id, created_at)`.
- `orders(cashier_id, created_at)`.
- `payments(order_id)`.
- `payments(method, paid_at)`.
- `stock_movements(inventory_item_id, created_at)`.
- `shifts(cashier_id, status)`.
- `audit_logs(user_id, created_at)`.
- `audit_logs(entity_type, entity_id)`.

---

## 18. Testing Strategy

## 18.1 Unit tests

- Pricing service.
- Modifier price.
- Subtotal.
- Discount.
- Tax.
- Service charge.
- Rounding.
- Change amount.
- Recipe deduction.
- Shift reconciliation.
- Order number generation.

## 18.2 Feature tests

- Login setiap role.
- Authorization halaman.
- Open shift.
- Create order.
- Create order dengan modifier.
- Payment cash.
- Payment non-cash.
- Stock deduction.
- Hold dan resume order.
- Void.
- Refund.
- Close shift.
- Audit log.

## 18.3 Livewire tests

- Filter kategori.
- Search product.
- Add item.
- Increment dan decrement quantity.
- Modifier selection.
- Total calculation.
- Open payment modal.
- Complete transaction.
- Reset cart setelah transaksi.
- Validation error.
- Double-submit protection.

## 18.4 Critical flow

```text
Login cashier
→ Open shift
→ Add coffee
→ Select size and milk
→ Add another item
→ Checkout
→ Pay cash
→ Print receipt
→ Confirm stock movement
→ Close shift
```

---

## 19. CI/CD

Pipeline minimum:

1. Install Composer dependencies.
2. Install frontend dependencies.
3. Copy environment testing.
4. Generate app key untuk testing.
5. Run Laravel Pint check.
6. Run static analysis jika digunakan.
7. Run unit dan feature tests.
8. Build frontend assets.
9. Validate migration.
10. Deploy staging.
11. Run smoke test.
12. Deploy production setelah approval.

Branch sederhana:

```text
main      : production-ready
develop   : staging, opsional
feature/* : pengembangan fitur
fix/*     : perbaikan
```

---

## 20. Deployment

Rekomendasi server:

- Linux server.
- Nginx.
- PHP-FPM.
- MySQL 8.0 atau lebih baru.
- Supervisor untuk queue worker jika queue digunakan.
- Cron untuk Laravel Scheduler.
- HTTPS.
- Backup MySQL otomatis dan terenkripsi.

Environment:

```text
local
staging
production
```

Production checklist:

- `APP_ENV=production`.
- `APP_DEBUG=false`.
- HTTPS aktif.
- Storage link tersedia.
- Permission folder benar.
- Config cache aktif.
- Route cache aktif jika kompatibel.
- View cache aktif.
- Scheduler aktif.
- Queue worker aktif jika digunakan.
- Backup telah diuji restore.

---

## 21. Roadmap Pengembangan

## Phase 0 — Discovery dan Foundation

Deliverables:

- Flow operasional toko.
- Role dan permission.
- Metode pembayaran.
- Struktur pajak dan service charge.
- Daftar perangkat kasir.
- Wireframe POS.
- Repository.
- Laravel 12 setup.
- Database setup.
- Tailwind, Livewire, Alpine, dan Vite setup.
- CI dasar.

Acceptance criteria:

- Project Laravel 12 berjalan lokal.
- Migration dapat dijalankan.
- Livewire component dapat dirender.
- Asset build berhasil.
- Test pipeline berjalan.

## Phase 1 — Authentication dan Settings

Deliverables:

- Login/logout.
- Role dan permission.
- User management.
- Store settings.
- Audit log dasar.

Acceptance criteria:

- Cashier tidak dapat mengakses pengaturan owner.
- User nonaktif tidak dapat login.
- Password dan PIN tidak tersimpan plaintext.

## Phase 2 — Catalog

Deliverables:

- Category CRUD.
- Product CRUD.
- Product image.
- Variant.
- Modifier group.
- Modifier option.
- Product availability.

Acceptance criteria:

- Owner dapat membuat produk lengkap dengan modifier.
- Produk nonaktif tidak muncul di POS.
- Harga lama transaksi tidak berubah.

## Phase 3 — POS Core

Deliverables:

- Layout POS responsif.
- Category filter.
- Product grid.
- Search.
- Cart.
- Modifier modal.
- Quantity control.
- Notes.
- Dine in dan take away.
- Tax dan service charge.
- Hold dan resume order.

Acceptance criteria:

- Kasir dapat menyusun pesanan tanpa reload halaman.
- Cart tidak hilang ketika modal dibuka.
- Total selalu diperbarui.
- POS usable pada tablet landscape.

## Phase 4 — Shift, Payment, dan Receipt

Deliverables:

- Open shift.
- Cash in/out.
- Payment modal.
- Cash dan manual non-cash payment.
- Close shift.
- Receipt 58/80 mm.
- Reprint.
- Void dengan PIN manager.

Acceptance criteria:

- Payment tersimpan sekali saja.
- Order dan payment berada dalam database transaction.
- Cashier tidak dapat transaksi tanpa shift aktif.
- Struk memuat detail lengkap.

## Phase 5 — Inventory dan Recipe

Deliverables:

- Inventory item.
- Unit.
- Recipe.
- Modifier recipe.
- Automatic stock deduction.
- Stock movement.
- Waste.
- Adjustment.
- Stock opname.

Acceptance criteria:

- Penjualan mengurangi stok sesuai resep.
- Modifier dapat menambah penggunaan bahan.
- Semua perubahan stok memiliki histori.

## Phase 6 — Dashboard dan Reports

Deliverables:

- Dashboard owner.
- Sales report.
- Product report.
- Payment report.
- Shift report.
- Inventory report.
- CSV export.

Acceptance criteria:

- Laporan sesuai transaksi aktual.
- Filter tanggal menggunakan zona waktu toko.
- Owner dapat mengekspor CSV.

## Phase 7 — Production Hardening

Deliverables:

- Performance audit.
- Security review.
- Backup dan restore test.
- Error monitoring.
- User acceptance testing.
- SOP kasir.
- SOP deployment dan rollback.

Acceptance criteria:

- Critical flow lulus test.
- Tidak ada bug severity tinggi.
- Backup dapat direstore.
- Aplikasi stabil selama satu shift penuh.

---

## 22. Prioritas MVP

### Wajib ada

- Laravel 12 monolith.
- Login dan role.
- Open dan close shift.
- Category dan product management.
- Variant dan modifier.
- POS tanpa full-page reload.
- Cart interaktif.
- Dine in dan take away.
- Cash dan pembayaran manual.
- Tax, service charge, dan diskon sederhana.
- Receipt.
- Riwayat transaksi.
- Inventory dasar dan recipe.
- Dashboard harian.
- Audit log dasar.

### Setelah MVP

- Customer database lengkap.
- Promo kompleks.
- Split bill.
- Partial payment.
- QRIS dinamis.
- WhatsApp receipt.
- Loyalty.
- Supplier dan purchase order.
- Multi-cabang.

---

## 23. Definition of Done

Sebuah fitur dianggap selesai apabila:

- Requirement terpenuhi.
- Acceptance criteria terpenuhi.
- UI responsif.
- Tidak menyebabkan full-page reload pada alur POS.
- Loading, empty, error, dan success state tersedia.
- Authorization diperiksa di server.
- Input divalidasi di server.
- Database transaction digunakan jika diperlukan.
- Test kritis tersedia dan lulus.
- Laravel Pint lulus.
- Build asset berhasil.
- Audit log tersedia untuk aksi sensitif.
- Fitur diuji pada staging.
- Fitur diuji pada perangkat kasir target.

---

## 24. Target Device

Urutan prioritas:

1. Tablet Android landscape untuk kasir.
2. Laptop atau desktop untuk owner dan manager.
3. Tablet portrait.
4. Ponsel untuk monitoring ringan.

Browser kasir utama sebaiknya Chromium modern.

---

## 25. Risiko dan Mitigasi

### Livewire terasa lambat

Mitigasi:

- Cache catalog.
- Batasi ukuran component state.
- Hindari nested component berlebihan.
- Hindari query di loop.
- Optimalkan database index.
- Gunakan Alpine.js untuk UI state lokal.
- Gunakan loading indicator per aksi.

### Double submit pembayaran

Mitigasi:

- Disable tombol saat request berjalan.
- Submission token atau idempotency key.
- Unique transaction reference.
- Database transaction.

### Stok tidak akurat

Mitigasi:

- Stock movement ledger.
- Pengurangan stok dalam transaction yang sama.
- Recipe yang jelas.
- Stock opname berkala.
- Audit log.

### Salah tekan kasir

Mitigasi:

- Touch target besar.
- Konfirmasi untuk void dan refund.
- Manager PIN.
- Undo hanya untuk aksi cart yang belum dibayar.

### Printer tidak kompatibel

Mitigasi:

- Mulai dengan browser print.
- Tentukan printer target sejak awal.
- Uji ukuran 58 mm dan 80 mm.
- Tambahkan print bridge hanya jika diperlukan.

### Hosting lambat

Mitigasi:

- Pilih server dekat lokasi pengguna.
- Aktifkan OPcache.
- Gunakan production cache Laravel.
- Pantau response time Livewire.
- Gunakan Redis jika benar-benar diperlukan.

---

## 26. Keputusan Teknis Final

```text
Architecture     : Laravel modular monolith
Scope            : Satu aplikasi untuk satu coffee shop
Backend          : Laravel 12
Interactive UI   : Livewire
Template         : Blade
Local UI state   : Alpine.js
Styling          : Tailwind CSS
Asset bundler    : Vite
Database         : MySQL 8.0+ dengan InnoDB
Authentication   : Laravel session auth
Authorization    : Policies dan Gates
Testing          : Pest/PHPUnit + Livewire tests
Deployment       : Linux + Nginx + PHP-FPM
Currency         : IDR
Timezone         : Asia/Jakarta
```

---

## 27. Urutan Implementasi

```text
Laravel Foundation
→ Authentication
→ Store Settings
→ Catalog
→ POS Screen
→ Cart dan Modifier
→ Shift
→ Payment
→ Receipt
→ Order History
→ Inventory dan Recipe
→ Dashboard
→ Reports
→ Production Hardening
```

Jangan memulai dari dashboard kompleks sebelum transaksi, payment, shift, dan stok stabil.

---

## 28. Kriteria Keberhasilan MVP

MVP berhasil apabila:

- Kasir dapat menyelesaikan transaksi tanpa full-page reload.
- Kasir dapat memahami POS dengan pelatihan singkat.
- Cart dan total diperbarui secara real time melalui Livewire.
- Owner dapat melihat penjualan dan kas harian.
- Stok bahan berkurang sesuai resep.
- Pembayaran tidak dapat tersimpan ganda.
- Struk dapat dicetak pada printer target.
- Aplikasi responsif pada tablet landscape.
- Aplikasi stabil digunakan selama satu shift penuh.

---

## 29. Checklist Sebelum Coding

- [ ] Tentukan nama aplikasi.
- [ ] Tentukan identitas visual.
- [ ] Tentukan perangkat kasir utama.
- [ ] Tentukan printer thermal target.
- [ ] Tentukan kebutuhan cash drawer.
- [ ] Tentukan tipe pesanan.
- [ ] Tentukan metode pembayaran.
- [ ] Tentukan pajak dan service charge.
- [ ] Tentukan aturan diskon.
- [ ] Tentukan role dan permission.
- [ ] Kumpulkan daftar menu.
- [ ] Kumpulkan daftar varian dan modifier.
- [ ] Kumpulkan daftar bahan dan satuan.
- [ ] Tentukan resep setiap produk.
- [ ] Buat wireframe halaman POS.
- [ ] Buat prototype checkout.
- [ ] Uji prototype pada tablet nyata.
- [ ] Siapkan database MySQL untuk development, staging, dan production.
- [ ] Tentukan server deployment.
- [ ] Tentukan strategi backup.

---

## 30. Langkah Pertama Pengerjaan

1. Buat project Laravel 12.
2. Buat database dan user MySQL, lalu konfigurasi koneksi pada `.env`.
3. Install dan konfigurasi Livewire.
4. Konfigurasi Tailwind CSS, Alpine.js, dan Vite.
5. Konfigurasi Laravel Pint dan testing.
6. Buat enum role dan status utama.
7. Buat migration users dan store settings.
8. Implementasikan login dan authorization.
9. Buat migration catalog.
10. Buat seed data produk dummy.
11. Buat design system dasar.
12. Buat prototype `CashierScreen` dengan data dummy.
13. Validasi UX pada tablet landscape.
14. Implementasikan cart Livewire.
15. Implementasikan shift.
16. Implementasikan order dan payment dalam database transaction.
17. Implementasikan receipt.
18. Implementasikan inventory dan recipe setelah alur pembayaran stabil.
