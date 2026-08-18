const SERVICE_UUID = '000018f0-0000-1000-8000-00805f9b34fb';
const COMMON_CHARACTERISTIC_UUID = '00002af1-0000-1000-8000-00805f9b34fb';
const LINE_WIDTH = 32;
const encoder = new TextEncoder();

let device = null;
let characteristic = null;

const ESC = 0x1b;
const GS = 0x1d;
const bytes = (...values) => new Uint8Array(values);
const textBytes = (value) => encoder.encode(String(value ?? '').normalize('NFKD').replace(/[^\x20-\x7E\n]/g, '?'));
const concat = (...parts) => {
    const size = parts.reduce((sum, part) => sum + part.length, 0);
    const output = new Uint8Array(size);
    let offset = 0;
    parts.forEach((part) => { output.set(part, offset); offset += part.length; });
    return output;
};
const money = (value) => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value) || 0);
const clip = (value, width) => String(value ?? '').slice(0, Math.max(0, width));
const center = (value) => {
    const line = clip(value, LINE_WIDTH);
    return `${' '.repeat(Math.max(0, Math.floor((LINE_WIDTH - line.length) / 2)))}${line}\n`;
};
const columns = (left, right) => {
    const rhs = String(right ?? '');
    const lhs = clip(left, Math.max(1, LINE_WIDTH - rhs.length - 1));
    return `${lhs}${' '.repeat(Math.max(1, LINE_WIDTH - lhs.length - rhs.length))}${rhs}\n`;
};
const wrap = (value, width = LINE_WIDTH) => {
    const words = String(value ?? '').trim().split(/\s+/).filter(Boolean);
    const lines = [];
    let line = '';
    words.forEach((word) => {
        const next = line ? `${line} ${word}` : word;
        if (next.length > width && line) { lines.push(line); line = word; } else line = next;
    });
    if (line) lines.push(line);
    return lines;
};

export function supportsWebBluetooth() {
    return window.isSecureContext && 'bluetooth' in navigator;
}

export function connectionState() {
    return { supported: supportsWebBluetooth(), connected: Boolean(device?.gatt?.connected && characteristic), name: device?.name || '' };
}

function emitState(message = '') {
    window.dispatchEvent(new CustomEvent('thermal-printer-state', { detail: { ...connectionState(), message } }));
}

async function findWritableCharacteristic(service) {
    try {
        const preferred = await service.getCharacteristic(COMMON_CHARACTERISTIC_UUID);
        if (preferred.properties.write || preferred.properties.writeWithoutResponse) return preferred;
    } catch (_) { /* Fall back to discovery. */ }
    const list = await service.getCharacteristics();
    const writable = list.find((item) => item.properties.writeWithoutResponse || item.properties.write);
    if (!writable) throw new Error('Characteristic tulis printer tidak ditemukan. Pastikan printer memakai service 0x18F0.');
    return writable;
}

async function connectDevice(selectedDevice) {
    device = selectedDevice;
    device.addEventListener('gattserverdisconnected', () => {
        characteristic = null;
        emitState('Koneksi printer terputus.');
    }, { once: true });
    let lastError;
    for (let attempt = 1; attempt <= 3; attempt += 1) {
        try {
            if (device.gatt.connected) device.gatt.disconnect();
            await new Promise((resolve) => setTimeout(resolve, attempt * 450));
            const server = await device.gatt.connect();
            const service = await server.getPrimaryService(SERVICE_UUID);
            characteristic = await findWritableCharacteristic(service);
            emitState(`Terhubung ke ${device.name || 'thermal printer'}`);
            return connectionState();
        } catch (error) {
            lastError = error;
            characteristic = null;
        }
    }
    if (lastError?.name === 'NotFoundError') {
        throw new Error('Printer ditemukan, tetapi service BLE 18F0 tidak tersedia. RPP02N ini kemungkinan memakai Bluetooth Classic, bukan BLE Web Bluetooth.');
    }
    if (lastError?.name === 'NetworkError') {
        throw new Error('GATT printer menolak koneksi. Putuskan RPP02N dari menu Bluetooth Android dan aplikasi lain, nyalakan ulang printer, lalu coba dari tombol situs.');
    }
    throw lastError;
}

export async function reconnectRememberedPrinter() {
    if (!supportsWebBluetooth() || typeof navigator.bluetooth.getDevices !== 'function') return null;
    const remembered = await navigator.bluetooth.getDevices();
    const selected = remembered.find((item) => item.name?.toUpperCase().includes('RPP')) || remembered[0];
    if (!selected) return null;
    return connectDevice(selected);
}

export async function ensurePrinterConnected() {
    if (characteristic && device?.gatt?.connected) return connectionState();
    const remembered = typeof navigator.bluetooth.getDevices === 'function'
        ? await navigator.bluetooth.getDevices()
        : [];
    const selected = remembered.find((item) => item.name?.toUpperCase().includes('RPP')) || remembered[0];
    if (selected) return connectDevice(selected);
    throw new Error('Printer belum pernah diizinkan untuk situs ini. Tekan Hubungkan RPP02N satu kali.');
}

export async function requestAndConnect() {
    if (!window.isSecureContext) throw new Error('Web Bluetooth wajib dibuka lewat HTTPS.');
    if (!('bluetooth' in navigator)) throw new Error('Browser tidak mendukung Web Bluetooth. Gunakan Chrome/Edge Android atau desktop.');
    try {
        const remembered = typeof navigator.bluetooth.getDevices === 'function'
            ? await navigator.bluetooth.getDevices()
            : [];
        const saved = remembered.find((item) => item.name?.toUpperCase().includes('RPP')) || remembered[0];
        if (saved) return await connectDevice(saved);
        const selectedDevice = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: [SERVICE_UUID],
        });
        return await connectDevice(selectedDevice);
    } catch (error) {
        characteristic = null;
        if (error.name === 'NotFoundError') throw new Error('Pemilihan printer dibatalkan atau RPP02N tidak ditemukan. Nyalakan Bluetooth dan printer.');
        if (error.name === 'SecurityError') throw new Error('Izin Bluetooth ditolak. Izinkan akses perangkat di browser.');
        if (error.name === 'NetworkError') throw new Error('GATT printer menolak koneksi. Putuskan pairing RPP02N dari Android/aplikasi lain, restart printer, lalu pilih lewat situs.');
        throw error;
    }
}

export function disconnectPrinter() {
    if (device?.gatt?.connected) device.gatt.disconnect();
    characteristic = null;
    emitState('Printer diputus.');
}

export function buildReceiptPayload(transaction) {
    const store = transaction.store || {};
    const lines = [];
    lines.push(center(store.name || 'KopiPOS'));
    if (store.address) wrap(store.address).forEach((line) => lines.push(center(line)));
    if (store.phone) lines.push(center(store.phone));
    lines.push(`${'-'.repeat(LINE_WIDTH)}\n`);
    lines.push(columns('Order', transaction.orderNumber));
    lines.push(columns('Waktu', transaction.paidAt));
    lines.push(columns('Kasir', transaction.cashier));
    if (transaction.orderType) lines.push(columns('Tipe', transaction.orderType));
    if (transaction.tableNumber) lines.push(columns('Meja', transaction.tableNumber));
    if (transaction.customerName) lines.push(columns('Nama', transaction.customerName));
    lines.push(`${'-'.repeat(LINE_WIDTH)}\n`);
    (transaction.items || []).forEach((item) => {
        wrap(`${item.quantity}x ${item.name}`, 21).forEach((line, index) => {
            lines.push(index === 0 ? columns(line, money(item.total)) : `${line}\n`);
        });
        if (item.variant) lines.push(`  + ${clip(item.variant, 28)}\n`);
        (item.modifiers || []).forEach((modifier) => lines.push(`  + ${clip(modifier.name, 22)} ${money(modifier.price)}\n`));
        if (item.notes) wrap(`  Catatan: ${item.notes}`, LINE_WIDTH).forEach((line) => lines.push(`${line}\n`));
    });
    lines.push(`${'-'.repeat(LINE_WIDTH)}\n`);
    lines.push(columns('Subtotal', money(transaction.subtotal)));
    if (Number(transaction.discount)) lines.push(columns('Diskon', `-${money(transaction.discount)}`));
    if (Number(transaction.tax)) lines.push(columns('Pajak', money(transaction.tax)));
    if (Number(transaction.serviceCharge)) lines.push(columns('Layanan', money(transaction.serviceCharge)));
    const total = columns('TOTAL', `Rp ${money(transaction.total)}`);
    const payment = columns(transaction.paymentMethod || 'Bayar', money(transaction.paidAmount));
    const change = Number(transaction.change) > 0 ? columns('Kembali', money(transaction.change)) : '';
    const footer = wrap(store.footer || 'Terima kasih', LINE_WIDTH).map(center).join('');

    return concat(
        bytes(ESC, 0x40),
        bytes(ESC, 0x61, 0x01), textBytes(lines.join('')),
        bytes(ESC, 0x61, 0x00, ESC, 0x45, 0x01), textBytes(total), bytes(ESC, 0x45, 0x00),
        textBytes(payment + change + `${'-'.repeat(LINE_WIDTH)}\n`),
        bytes(ESC, 0x61, 0x01), textBytes(footer),
        bytes(ESC, 0x61, 0x00), textBytes('\n\n\n\n'),
        bytes(GS, 0x56, 0x00),
    );
}

async function writeChunk(chunk) {
    if (characteristic.properties.writeWithoutResponse && characteristic.writeValueWithoutResponse) {
        return characteristic.writeValueWithoutResponse(chunk);
    }
    return characteristic.writeValue(chunk);
}

export async function printReceipt(transaction) {
    if (!characteristic || !device?.gatt?.connected) {
        await ensurePrinterConnected();
    }
    const payload = buildReceiptPayload(transaction);
    try {
        for (let offset = 0; offset < payload.length; offset += 180) {
            await writeChunk(payload.slice(offset, offset + 180));
            await new Promise((resolve) => setTimeout(resolve, 35));
        }
        emitState('Struk berhasil dikirim ke printer.');
    } catch (error) {
        characteristic = null;
        if (!device?.gatt?.connected || error.name === 'NetworkError') throw new Error('Koneksi printer terputus saat mencetak. Hubungkan ulang lalu coba lagi.');
        throw new Error(`Gagal mengirim struk: ${error.message}`);
    }
}

window.KopiPOSThermal = { requestAndConnect, reconnectRememberedPrinter, ensurePrinterConnected, disconnectPrinter, printReceipt, buildReceiptPayload, connectionState, supportsWebBluetooth };
