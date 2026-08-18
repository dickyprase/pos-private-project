# KopiPOS

KopiPOS adalah aplikasi point-of-sale single-store untuk coffee shop. UI kasir memakai Laravel Livewire, sedangkan integrasi eksternal memakai REST API dengan Laravel Sanctum.

## Stack

- PHP 8.3, Laravel 12, Livewire 3
- MySQL 8.0
- Vite + Tailwind CSS
- Laravel Sanctum untuk API token
- Scribe untuk dokumentasi interaktif dan Postman collection
- Web Bluetooth BLE/GATT untuk printer thermal RPP02N 58 mm

## Setup

```bash
git clone https://github.com/dickyprase/pos-private-project.git
cd pos-private-project
composer install
cp .env.example .env
php artisan key:generate
npm install
php artisan migrate --seed
npm run build
php artisan serve
```

Atur koneksi database di `.env`. Jangan commit `.env`, token, password, PIN, atau credential produksi.

## Commands

```bash
composer run dev     # server, queue, log, Vite
npm run build        # asset production
php artisan test     # seluruh test
php artisan scribe:generate
```

## API

Dokumentasi interaktif:

```text
GET /docs
GET /docs.postman
GET /docs.openapi
```

Semua endpoint selain `POST /api/auth/token` membutuhkan header:

```http
Authorization: Bearer <SANCTUM_TOKEN>
Accept: application/json
```

Response sukses:

```json
{"success":true,"data":{},"message":null}
```

Response error:

```json
{"success":false,"data":null,"message":"Validasi gagal.","errors":{}}
```

### Endpoint

- Auth: token, logout
- Catalog: categories, products, product detail
- Shifts: active, open, close
- Orders: checkout, list, detail, hold, complete, void
- Payments: create, list, refund

`POST /api/orders` adalah atomic checkout existing: order, payment, dan stock movement ditulis dalam satu DB transaction. Harga selalu dibaca dari master. Request identik dengan `submission_token` sama bersifat idempotent.

## Contoh alur API

### 1. Login

```bash
TOKEN=$(curl -s https://pos.zorroserver.net/api/auth/token \
  -H 'Content-Type: application/json' \
  -d '{"login":"kasir","password":"<PASSWORD>","device_name":"external-client"}' \
  | jq -r '.data.token')
```

### 2. Buka shift

```bash
curl https://pos.zorroserver.net/api/shifts/open \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"opening_cash":200000}'
```

### 3. Lihat catalog

```bash
curl https://pos.zorroserver.net/api/products?is_available=1 \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json'
```

### 4. Checkout order

```bash
SUBMISSION_TOKEN=$(python3 -c 'import uuid; print(uuid.uuid4())')

curl https://pos.zorroserver.net/api/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d "{
    \"submission_token\":\"$SUBMISSION_TOKEN\",
    \"table_number\":\"A1\",
    \"customer_name\":\"Budi\",
    \"order_type\":\"DINE_IN\",
    \"items\":[{
      \"product_id\":1,
      \"quantity\":1,
      \"modifier_ids\":[]
    }],
    \"payment\":{
      \"method\":\"CASH\",
      \"received_amount\":50000
    }
  }"
```

### 5. Complete/idempotency check

```bash
curl -X POST https://pos.zorroserver.net/api/orders/1/complete \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d "{\"submission_token\":\"$SUBMISSION_TOKEN\"}"
```

### 6. Tutup shift

```bash
curl -X POST https://pos.zorroserver.net/api/shifts/1/close \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"actual_cash":250000,"notes":"Shift selesai"}'
```

## Security

- Route API memakai `auth:sanctum`.
- Cashier hanya dapat membaca/mengubah order miliknya.
- Void/refund membutuhkan manager aktif, `approved_by`, dan PIN manager.
- Secret tidak boleh disimpan di source.
- Nominal uang berupa integer rupiah.

## Printer Bluetooth

Primary transport web memakai Web Bluetooth BLE/GATT. Chrome Android tertentu tidak menyediakan `navigator.bluetooth.getDevices()`, sehingga permission printer tidak bisa dipulihkan setelah reload dan chooser perlu dibuka ulang. Ini batas browser, bukan Laravel.

## License

MIT
