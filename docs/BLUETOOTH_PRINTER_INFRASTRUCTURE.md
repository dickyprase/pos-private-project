# Infrastruktur dan Implementasi Printer Bluetooth KopiPOS

Dokumen ini menjelaskan alur printer thermal ESC/POS pada KopiPOS berbasis Laravel 12, Livewire 3, Vite, browser/PWA, dan Android Capacitor. Fokus: connect, remember, auto reconnect, antrean cetak, serta rancangan multiple-printer connection.

## 1. Batas Transport yang Wajib Dipahami

Nama perangkat seperti RPP02N tidak menjamin jenis Bluetooth. Unit bisa memakai BLE/GATT, Bluetooth Classic SPP/RFCOMM, atau dual mode.

| Target | Transport | Bisa dari browser/PWA | Penyimpanan identitas |
|---|---|---:|---|
| Chrome/Edge Android | BLE/GATT | Ya, jika Web Bluetooth tersedia | IndexedDB + permission browser origin |
| Brave/iOS/Safari tertentu | BLE/GATT | Dukungan terbatas atau tidak ada | Tergantung browser |
| Android APK Capacitor | Classic SPP/RFCOMM | Tidak langsung dari WebView; perlu native plugin | `SharedPreferences`/DataStore |
| Printer LAN | TCP/IP | Lewat service/backend khusus | DB/config aplikasi |

**PWA tidak mengubah printer Classic menjadi Web Bluetooth.** Laravel berjalan di server dan tidak bisa mengakses radio Bluetooth HP. Koneksi fisik selalu dilakukan browser atau native Android.

## 2. Arsitektur End-to-End

```text
Kasir menekan Cetak Bluetooth
        |
        v
Livewire CashierScreen::printOrderReceipt(orderId)
        |
        v
EscPosReceiptBuilder membuat raw ESC/POS 32 kolom
        |
        v
base64_encode lalu dispatch event `print-receipt`
        |
        v
Vite JS mendecode Base64 menjadi Uint8Array
        |
        +---------------- Browser/PWA ----------------+
        | PrinterManager -> BLE GATT -> characteristic |
        | service 18F0, characteristic 2AF1            |
        +-----------------------------------------------+
        |
        +---------------- Android APK ------------------+
          NativePrinterManager -> Capacitor plugin
          -> RFCOMM/SPP socket -> OutputStream printer
```

Pemisahan layer:

1. **Laravel/Livewire**: otorisasi order dan membentuk byte struk.
2. **Event contract**: payload Base64 melalui event `print-receipt`.
3. **Transport adapter**: BLE browser atau Classic native.
4. **UI state**: menampilkan status saved identity dan koneksi aktif.

Kontrak ini penting supaya format struk tidak bergantung pada transport.

## 3. File Utama

### Laravel web

- `app/Services/EscPosReceiptBuilder.php`: membentuk byte ESC/POS 58 mm, lebar 32 karakter.
- `app/Livewire/Pos/CashierScreen.php`: otorisasi order dan dispatch `print-receipt`.
- `resources/js/printer-bluetooth.js`: manager BLE persisten untuk browser.
- `resources/js/printer-native.js`: adapter JS menuju plugin Capacitor Classic SPP.
- `resources/js/app.js`: binding status dan tombol printer.
- `resources/views/livewire/pos/partials/printer-controls.blade.php`: UI connect, forget, dan print.
- `tests/Feature/PersistentBluetoothPrinterTest.php`: regression contract printer.

### Android Capacitor

- `android/app/src/main/java/<APP_PACKAGE>/BluetoothPrinterPlugin.java`: socket RFCOMM, remember MAC, reconnect, dan raw write.
- `android/app/src/main/java/<APP_PACKAGE>/MainActivity.java`: registrasi plugin.
- `android/app/src/main/AndroidManifest.xml`: permission Bluetooth.
- `capacitor.config.json`: WebView memakai Laravel remote atau bundle lokal.

Gunakan placeholder `<APP_PACKAGE>` saat menyalin tutorial ke project lain. Jangan masukkan host produksi, MAC printer, credential, atau data transaksi ke dokumentasi/log bersama.

## 4. Kontrak Laravel ke Browser

Backend mengambil order yang boleh diakses kasir, membuat raw bytes, lalu mengirim Base64:

```php
public function printOrderReceipt(int $orderId): void
{
    $order = $this->authorizedOrder($orderId)
        ->with('items.modifiers', 'payment', 'cashier')
        ->firstOrFail();

    $receipt = app(EscPosReceiptBuilder::class)->build($order);

    $this->dispatch(
        'print-receipt',
        escposBase64: base64_encode($receipt),
    );
}
```

Browser/native adapter mendecode payload:

```js
const decodeBase64 = (value) => {
    const binary = atob(value);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
};

Livewire.on('print-receipt', async ({ escposBase64 }) => {
    await PrinterManager.printEscPos(decodeBase64(escposBase64));
});
```

Keuntungan Base64:

- Byte kontrol seperti `ESC @`, alignment, bold, dan feed tidak rusak oleh JSON.
- Backend menjadi satu sumber format struk.
- Payload yang sama bisa dikirim melalui BLE atau RFCOMM.

## 5. Browser/PWA: Connect Pertama Kali

Konfigurasi BLE yang dipakai implementasi saat ini:

```js
const SERVICE_UUID = '000018f0-0000-1000-8000-00805f9b34fb';
const PREFERRED_CHARACTERISTIC_UUID = '00002af1-0000-1000-8000-00805f9b34fb';
const BLE_CHUNK_SIZE = 20;
const CHUNK_DELAY_MS = 15;
```

Alur connect:

1. Pengguna menekan tombol `data-printer-pair`.
2. Click handler langsung menjalankan `PrinterManager.pairNewPrinter()`.
3. `navigator.bluetooth.requestDevice()` membuka chooser browser.
4. Browser memberikan `BluetoothDevice` untuk origin HTTPS tersebut.
5. Aplikasi menyimpan `{id, name, pairedAt}` ke IndexedDB.
6. `device.gatt.connect()` membuka koneksi GATT.
7. Aplikasi mengambil service `18F0`.
8. Aplikasi mencari characteristic `2AF1`; jika tidak ada, cari characteristic lain yang writable.
9. Status berubah menjadi `connected`.

Contoh inti:

```js
const device = await navigator.bluetooth.requestDevice({
    filters: [{ services: [SERVICE_UUID] }],
    optionalServices: [SERVICE_UUID],
});

await saveDevice(device);
const server = await device.gatt.connect();
const service = await server.getPrimaryService(SERVICE_UUID);
const characteristic = await findWritableCharacteristic(service);
```

**`requestDevice()` wajib dipanggil langsung dari user gesture.** Jangan taruh chooser setelah Livewire request, timer, page init, atau auto reconnect.

## 6. Browser/PWA: Remember Printer

`BluetoothDevice` tidak bisa diserialisasi ke localStorage/IndexedDB. Yang disimpan hanya identitas:

```js
{
    id: device.id,
    name: device.name || 'Thermal Printer',
    pairedAt: Date.now()
}
```

Storage KopiPOS:

```text
Database : kopipos-printer
Store    : settings
Key      : selected-device
```

Saat reload:

1. Baca saved record dari IndexedDB.
2. Panggil `navigator.bluetooth.getDevices()`.
3. Cari objek browser yang `id`-nya sama dengan saved record.
4. Jika ketemu, bind event disconnect dan jalankan `gatt.connect()` tanpa chooser.

```js
const saved = await readSaved();
const permitted = await navigator.bluetooth.getDevices();
const device = permitted.find((item) => item.id === saved.id);
if (device) await connectDevice(device);
```

Penyimpanan IndexedDB dan permission browser adalah dua hal berbeda:

```text
IndexedDB punya ID + browser mengembalikan device = bisa silent reconnect
IndexedDB punya ID + getDevices() kosong         = tahu printer lama, tidak punya handle
IndexedDB punya ID + getDevices() tidak tersedia = browser tidak mendukung restore
IndexedDB kosong                                 = printer belum pernah dipilih
```

Karena itu status `saved-disconnected`/`saved-unavailable` tidak boleh disamakan dengan `no-permission`.

## 7. Browser/PWA: Auto Reconnect

Trigger reconnect saat ini:

- `DOMContentLoaded`: `PrinterManager.init()`.
- `gattserverdisconnected`: jadwalkan reconnect.
- Tab kembali visible: `visibilitychange`.
- Jaringan kembali online: event `online`. Ini bukan syarat Bluetooth, tetapi membantu memulihkan flow aplikasi setelah perangkat kembali aktif.
- Sebelum print: `ensureConnected()` mencoba pemulihan lagi.

Backoff:

```js
const RECONNECT_DELAYS = [1000, 2000, 4000, 8000, 15000];
```

Aturan:

- Hanya satu `initPromise` dan satu `connecting` promise.
- Jangan reconnect ketika tab hidden.
- Jangan buka chooser otomatis.
- Stop setelah daftar retry habis.
- Reset retry counter setelah koneksi berhasil.
- Print mencoba maksimal dua kali; retry pertama menunggu sekitar 750 ms.

State machine:

```text
checking
   |
   +-- tidak ada saved identity ------> no-permission
   +-- saved, handle tak bisa restore -> saved-unavailable
   +-- saved, connect gagal ----------> saved-disconnected
   +-- connect berjalan --------------> connecting/reconnecting
   +-- characteristic siap ----------> connected
                                           |
                                           +-- GATT putus -> disconnected
```

UI yang disarankan:

- Merah: `Belum ada printer`.
- Kuning: `Terputus - <nama>`, `Izin perlu diperbarui`, atau reconnecting.
- Hijau: `Terhubung - <nama>`.
- Tombol forget/ganti tetap terlihat selama saved identity masih ada.

## 8. Browser/PWA: Write ESC/POS

Printer BLE murah sering punya MTU kecil. Payload harus dikirim berurutan:

```js
for (let offset = 0; offset < payload.length; offset += 20) {
    const chunk = payload.slice(offset, offset + 20);

    if (characteristic.properties.writeWithoutResponse) {
        await characteristic.writeValueWithoutResponse(chunk);
    } else {
        await characteristic.writeValue(chunk);
    }

    await delay(15);
}
```

Jangan `Promise.all()` semua chunk. Byte receipt bisa bercampur, hilang, atau dipotong.

## 9. Android APK: Bluetooth Classic SPP/RFCOMM

Untuk printer Classic, browser tidak cukup. Capacitor plugin menyediakan bridge:

```text
Laravel remote UI
    -> window.Capacitor.Plugins.BluetoothPrinter
    -> BluetoothPrinterPlugin.java
    -> BluetoothSocket RFCOMM
    -> OutputStream.write(rawEscPos)
```

UUID standar Serial Port Profile:

```java
private static final UUID SPP_UUID = UUID.fromString(
    "00001101-0000-1000-8000-00805f9b34fb"
);
```

Permission Android:

```xml
<uses-permission android:name="android.permission.BLUETOOTH" android:maxSdkVersion="30" />
<uses-permission android:name="android.permission.BLUETOOTH_ADMIN" android:maxSdkVersion="30" />
<uses-permission android:name="android.permission.BLUETOOTH_CONNECT" />
<uses-permission android:name="android.permission.BLUETOOTH_SCAN"
    android:usesPermissionFlags="neverForLocation" />
```

Android 12+ perlu runtime permission `BLUETOOTH_CONNECT` dan `BLUETOOTH_SCAN`. Manifest saja tidak cukup.

## 10. Android APK: Connect, Remember, Auto Reconnect

### Connect

1. `listPairedDevices()` membaca `BluetoothAdapter.getBondedDevices()`.
2. JS memilih device bernama `RPP`, `POS`, atau `PRINTER`; jika hanya satu, pilih device tersebut.
3. Jika belum ada bonded device, buka Android Bluetooth Settings melalui `startPairing()`.
4. `connect({address})` dijalankan pada single-thread executor.
5. Socket lama ditutup.
6. `createRfcommSocketToServiceRecord(SPP_UUID)` membuat socket baru.
7. Discovery dibatalkan, lalu `socket.connect()`.

### Remember

Alamat terpilih disimpan app-private:

```java
private static final String PREFS = "kopipos_printer";
private static final String ADDRESS_KEY = "preferred_printer_address";

preferences.edit().putString(ADDRESS_KEY, address).apply();
```

Jangan log atau kirim alamat MAC ke server. MAC hanya identitas lokal perangkat.

### Auto reconnect

Saat plugin `load()`:

```java
String saved = preferredAddress();
if (saved != null && hasPermissions()) {
    executor.execute(() -> connectWithRetry(saved, 3));
}
```

Sebelum print:

```java
if (socket == null || !socket.isConnected()) {
    connectWithRetry(savedAddress, 1);
}
```

Jika write gagal:

- Tutup socket rusak.
- Ubah state menjadi `disconnected`.
- Return error jelas ke JS.
- Print berikutnya boleh mencoba reconnect lagi.

## 11. Antrean dan Pencegahan Receipt Bercampur

Native plugin memakai `Executors.newSingleThreadExecutor()`. Semua connect, disconnect, dan print masuk satu antrean. Ini mencegah dua transaksi menulis ke socket bersamaan.

Browser manager juga harus punya print queue. Pola yang direkomendasikan:

```js
class PrinterConnection {
    constructor() {
        this.queue = Promise.resolve();
    }

    enqueuePrint(payload) {
        const job = () => this.printEscPos(payload);
        this.queue = this.queue.then(job, job);
        return this.queue;
    }
}
```

Gunakan idempotency UI:

- Disable tombol selama Livewire membuat payload.
- Jangan dispatch payload dua kali untuk satu klik.
- Queue berdasarkan printer, bukan satu queue global jika memakai banyak printer.

## 12. Multiple Connection: Definisi dan Batas Implementasi Saat Ini

Implementasi web saat ini **single selected printer**:

- Satu key IndexedDB: `selected-device`.
- Satu `device`.
- Satu writable `characteristic`.
- Satu reconnect timer.

Implementasi native saat ini juga **single preferred printer**:

- Satu `preferred_printer_address`.
- Satu `BluetoothSocket`.
- Satu output queue.

Jadi “multiple connection” belum berarti satu HP tersambung aktif ke banyak printer. Yang sudah aman:

- Banyak kasir/device memakai printer masing-masing.
- Satu kasir mengganti printer dengan forget/pair ulang.
- Banyak receipt masuk antrean ke satu printer.

Yang tidak aman tanpa pengembangan tambahan:

- Dua HP menulis bersamaan ke satu printer murah. Banyak printer hanya menerima satu client aktif.
- Satu manager global menulis paralel ke dua printer.
- Auto reconnect tak terbatas dari banyak client, karena terjadi connection war.

## 13. Rancangan Multiple Printer yang Benar

Jika kebutuhan adalah kasir, bar, dan dapur memiliki printer berbeda, ubah model dari satu printer menjadi registry:

```js
const savedPrinters = [
    { id: '...', name: 'Kasir', role: 'cashier', transport: 'ble' },
    { id: '...', name: 'Bar', role: 'bar', transport: 'ble' },
    { id: '...', name: 'Dapur', role: 'kitchen', transport: 'lan' },
];
```

Setiap koneksi punya state sendiri:

```js
class PrinterConnection {
    constructor(config) {
        this.config = config;
        this.device = null;
        this.characteristic = null;
        this.status = 'disconnected';
        this.connecting = null;
        this.queue = Promise.resolve();
        this.reconnectAttempt = 0;
        this.reconnectTimer = null;
    }
}

class PrinterRegistry {
    constructor() {
        this.connections = new Map();
    }

    print(role, payload) {
        const connection = this.connections.get(role);
        if (!connection) throw new Error(`Printer role ${role} belum dikonfigurasi.`);
        return connection.enqueuePrint(payload);
    }
}
```

Storage berubah dari key tunggal menjadi object store per printer:

```text
Database : kopipos-printers-v2
Store    : printers
Key      : printer.id atau role
Value    : id, name, role, transport, pairedAt, enabled
```

Routing backend:

```text
Customer receipt -> cashier printer
Drink tickets    -> bar printer
Food tickets     -> kitchen printer
```

Rekomendasi operasional:

- Maksimal satu koneksi aktif per printer fisik.
- Satu queue per printer.
- Reconnect backoff per printer.
- Jangan reconnect semua printer saat tab hidden.
- Gunakan job ID untuk mencegah duplicate print.
- Simpan mapping role, bukan raw Bluetooth handle.
- Untuk Android Classic multiple-printer, gunakan `Map<String, BluetoothSocket>` dan `Map<String, ExecutorService>` atau queue per address. Jangan berbagi satu socket/output stream.
- Untuk deployment kedai yang butuh banyak endpoint stabil, printer LAN lebih cocok daripada mempertahankan banyak GATT connection dari satu browser.

## 14. Banyak Client ke Satu Printer

Satu printer thermal Bluetooth murah biasanya hanya bisa menjaga satu koneksi SPP/GATT aktif. Strategi aman:

1. Tetapkan satu device sebagai print owner.
2. Device lain tidak auto reconnect ke printer tersebut.
3. Jika multi-kasir wajib mencetak ke printer sama, gunakan print gateway:

```text
Kasir A/B/C -> Laravel print_jobs table/queue
                     |
                     v
              Local print agent
                     |
                     v
               Satu printer
```

Print gateway bisa berupa Android service, mini PC, Raspberry Pi, atau service lokal yang menjadi satu-satunya pemilik koneksi printer. Laravel menyimpan job dan status, bukan membuka Bluetooth dari server.

Minimal schema:

```text
print_jobs
- id
- printer_role
- order_id
- payload_base64 atau payload reference
- status: QUEUED/PRINTING/PRINTED/FAILED
- attempts
- idempotency_key
- error_message
- printed_at
```

## 15. Forget dan Ganti Printer

Browser:

1. Set `intentionalDisconnect = true`.
2. Batalkan reconnect timer.
3. `device.gatt.disconnect()` jika aktif.
4. Panggil `device.forget()` jika browser mendukung.
5. Hapus IndexedDB record.
6. Reset device, characteristic, retry counter, dan UI state.

Native:

- Memutus socket bukan berarti menghapus Android bond.
- Hapus preferred address dari `SharedPreferences` jika membuat method `forgetPrinter()`.
- Unpair penuh tetap melalui Android Settings kecuali aplikasi memang diberi flow khusus.

Contoh native forget yang disarankan:

```java
@PluginMethod
public void forgetPrinter(PluginCall call) {
    executor.execute(() -> {
        synchronized (socketLock) { closeSocketLocked(); }
        preferences.edit().remove(ADDRESS_KEY).apply();
        setState("disconnected", null);
        call.resolve();
    });
}
```

## 16. Error Handling

| Error/status | Makna | Aksi |
|---|---|---|
| `Web Bluetooth tidak tersedia` | Browser tidak punya API atau bukan secure context | Gunakan browser kompatibel atau APK |
| `getDevices()` undefined | Browser tidak bisa restore permission | Pair ulang atau gunakan native bridge |
| `saved-unavailable` | Identitas ada, handle browser tidak tersedia | Tampilkan reconnect/ganti, jangan hapus saved record |
| `NetworkError` saat GATT | Printer busy, stale session, atau transport salah | Matikan app lain, power cycle, cek BLE vs Classic |
| Service `18F0` tidak ada | UUID berbeda atau printer Classic-only | Inspect GATT atau pindah native SPP |
| Characteristic writable tidak ada | Service salah/tidak kompatibel | Audit characteristic printer |
| RFCOMM connect gagal | Bond/permission/socket/printer busy | Cek Android bond, permission, tutup socket stale |
| Cetak terpotong | Chunk terlalu besar/cepat | Pakai 20 byte + delay 15 ms untuk BLE |
| Receipt ganda | Click/event tidak di-idempotent | Disable tombol, queue, gunakan job/idempotency key |

## 17. Security dan Privacy

- Jangan simpan credential, session cookie, PIN manager, data customer, atau MAC printer di log server.
- Jangan kirim MAC printer ke Laravel jika tidak diperlukan.
- Otorisasi order tetap dilakukan server-side sebelum payload dibuat.
- Jangan percaya order ID dari browser tanpa policy/query scope.
- Payload receipt hanya dikirim ke browser kasir yang berhak.
- Bersihkan debug log detail sebelum dokumentasi atau distribusi produksi.

## 18. Test Matrix

### Browser BLE

1. Pair pertama dari user click.
2. Cetak struk pendek.
3. Cetak struk kedua tanpa chooser.
4. Refresh halaman, cek silent reconnect.
5. Tutup dan buka ulang tab.
6. Tutup dan buka ulang browser.
7. Matikan printer saat connected, cek state disconnect.
8. Nyalakan printer, cek bounded reconnect.
9. Cetak struk panjang, pastikan tidak terpotong.
10. Forget lalu pair printer lain.
11. Coba browser yang tidak punya `getDevices()`.

### Android native

1. Android 11 dan Android 12+ permission flow.
2. Pair di Settings, list bonded device.
3. Connect dan simpan preferred address.
4. Tutup/buka APK, cek auto reconnect.
5. Power cycle printer.
6. Print saat socket mati, cek retry before print.
7. Dua klik print cepat, pastikan queue tidak mencampur receipt.
8. Unpair dari Settings, cek error jelas.
9. Build debug/release dan uji APK yang benar-benar terpasang.

### Multiple printer

1. Setiap role menuju printer benar.
2. Queue printer A tidak memblokir printer B.
3. Disconnect A tidak mengubah status B.
4. Retry counter dan timer terpisah.
5. Job ID mencegah duplicate print.
6. Dua client tidak connection war ke satu printer.

## 19. Verifikasi Project

```bash
npm run build
php artisan test tests/Feature/PersistentBluetoothPrinterTest.php
php artisan test tests/Feature/ThermalBluetoothReceiptTest.php
php artisan view:cache
```

Untuk Capacitor:

```bash
npx cap sync android
cd android
./gradlew assembleDebug
```

Verifikasi hasil:

- Bundle production memuat adapter printer yang benar.
- Tidak ada stale import/manager ganda pada entrypoint aktif.
- Event `print-receipt` hanya punya satu listener aktif per transport.
- APK mendaftarkan plugin dan permission runtime berjalan.
- Uji first print, second print, refresh/reopen, power cycle, dan long receipt pada hardware nyata.

## 20. Catatan Implementasi KopiPOS Saat Ini

- Web aktif memakai singleton `PersistentPrinterManager` untuk satu printer BLE.
- Identity browser disimpan di IndexedDB; permission tetap milik browser origin.
- Reconnect memakai `initPromise`, `connecting` promise, event disconnect, visibility, online, dan retry terbatas.
- Payload BLE dikirim per 20 byte dengan delay 15 ms.
- Native bridge memakai Classic SPP, preferred MAC app-private, satu socket, dan single-thread executor.
- Format struk berasal dari `EscPosReceiptBuilder`, bukan dibuat ulang oleh transport.
- Multiple-printer registry dan centralized print gateway adalah rancangan lanjutan; belum menjadi kemampuan aktif manager saat ini.

Prinsip utama: **pisahkan format receipt, saved identity, live connection, queue, dan transport.** Jangan menganggap status Android paired, izin browser, dan socket aktif sebagai satu state yang sama.
