const SERVICE_UUID = '000018f0-0000-1000-8000-00805f9b34fb';
const PREFERRED_CHARACTERISTIC_UUID = '00002af1-0000-1000-8000-00805f9b34fb';
const BLE_CHUNK_SIZE = 20;
const CHUNK_DELAY_MS = 15;
const DB_NAME = 'kopipos-printer';
const STORE_NAME = 'settings';
const DEVICE_KEY = 'selected-device';

class PersistentPrinterManager {
    constructor() {
        this.device = null;
        this.characteristic = null;
        this.status = 'disconnected';
        this.listeners = new Set();
        this.connecting = null;
        this.initialized = false;
        this.initPromise = null;
        this.savedDevice = null;
    }

    supported() {
        return window.isSecureContext && 'bluetooth' in navigator;
    }

    getStatus() { return this.status; }

    onStatusChange(callback) {
        this.listeners.add(callback);
        callback(this.status, this.device?.name || this.savedDevice?.name || '', Boolean(this.savedDevice));
        return () => this.listeners.delete(callback);
    }

    setStatus(status) {
        this.status = status;
        this.listeners.forEach((callback) => callback(status, this.device?.name || this.savedDevice?.name || '', Boolean(this.savedDevice)));
    }

    openDb() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, 1);
            request.onupgradeneeded = () => request.result.createObjectStore(STORE_NAME);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async readSaved() {
        const db = await this.openDb();
        return new Promise((resolve, reject) => {
            const request = db.transaction(STORE_NAME).objectStore(STORE_NAME).get(DEVICE_KEY);
            request.onsuccess = () => resolve(request.result || null);
            request.onerror = () => reject(request.error);
            request.transaction?.addEventListener('complete', () => db.close());
        });
    }

    async saveDevice(device) {
        const db = await this.openDb();
        await new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).put({ id: device.id, name: device.name || 'Thermal Printer', pairedAt: Date.now() }, DEVICE_KEY);
            tx.oncomplete = resolve;
            tx.onerror = () => reject(tx.error);
        });
        db.close();
        this.savedDevice = { id: device.id, name: device.name || 'Thermal Printer', pairedAt: Date.now() };
        console.log('[BT DEBUG] IndexedDB write selesai:', { id: device.id, name: device.name });
    }

    async clearSaved() {
        const db = await this.openDb();
        await new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).delete(DEVICE_KEY);
            tx.oncomplete = resolve;
            tx.onerror = () => reject(tx.error);
        });
        db.close();
    }

    bindDevice(device) {
        if (this.device === device) return;
        this.device = device;
        device.addEventListener('gattserverdisconnected', () => {
            this.characteristic = null;
            this.connecting = null;
            this.setStatus('disconnected');
        });
    }

    async findWritableCharacteristic(service) {
        try {
            const preferred = await service.getCharacteristic(PREFERRED_CHARACTERISTIC_UUID);
            if (preferred.properties.write || preferred.properties.writeWithoutResponse) return preferred;
        } catch (_) { /* Discover vendor-specific writable characteristic below. */ }
        const characteristics = await service.getCharacteristics();
        const writable = characteristics.find((item) => item.properties.writeWithoutResponse || item.properties.write);
        if (!writable) throw new Error('Characteristic tulis printer tidak ditemukan pada service BLE 18F0.');
        return writable;
    }

    async connectDevice(device) {
        if (device.gatt?.connected && this.characteristic) return device;
        if (this.connecting) return this.connecting;
        this.bindDevice(device);
        this.setStatus('connecting');
        this.connecting = (async () => {
            const server = device.gatt.connected ? device.gatt : await device.gatt.connect();
            const service = await server.getPrimaryService(SERVICE_UUID);
            this.characteristic = await this.findWritableCharacteristic(service);
            this.setStatus('connected');
            return device;
        })();
        try { return await this.connecting; }
        catch (error) { this.characteristic = null; this.setStatus('disconnected'); throw error; }
        finally { this.connecting = null; }
    }

    async init() {
        if (this.initPromise) return this.initPromise;
        this.initialized = true;
        this.setStatus('checking');
        this.initPromise = (async () => {
            if (!this.supported()) {
                console.log('[BT DEBUG] Web Bluetooth unsupported:', { secureContext: window.isSecureContext, bluetooth: 'bluetooth' in navigator });
                this.setStatus('no-permission');
                return this.status;
            }
            try {
                const saved = await this.readSaved();
                this.savedDevice = saved;
                console.log('[BT] IndexedDB device.id tersimpan:', saved?.id || null);
                console.log('[BT DEBUG] savedId dari IndexedDB:', saved?.id || null, saved);
                if (!saved) {
                    console.log('[BT DEBUG] saved device/getDevices unavailable:', { saved: Boolean(saved), getDevices: typeof navigator.bluetooth.getDevices });
                    this.setStatus('no-permission');
                    return this.status;
                }
                if (typeof navigator.bluetooth.getDevices !== 'function') {
                    console.log('[BT DEBUG] permission persistence unsupported: navigator.bluetooth.getDevices is undefined');
                    console.log('[BT] getDevices() hasil: API TIDAK TERSEDIA');
                    console.log('[BT] Match ditemukan: TIDAK BISA DIPERIKSA');
                    console.log('[BT] gatt.connect() hasil: TIDAK DIJALANKAN');
                    this.setStatus('saved-unavailable');
                    return this.status;
                }
                const permitted = await navigator.bluetooth.getDevices();
                console.log('[BT] getDevices() hasil:', permitted.map((item) => ({ id: item.id, name: item.name })));
                console.log('[BT DEBUG] getDevices() count:', permitted.length, permitted);
                const device = permitted.find((item) => item.id === saved.id);
                console.log('[BT] Match ditemukan:', device ? device.name : 'TIDAK ADA');
                console.log('[BT DEBUG] match ditemukan?', Boolean(device), device || null);
                if (!device) {
                    this.setStatus('saved-unavailable');
                    return this.status;
                }
                console.log('[BT DEBUG] mencoba silent gatt.connect():', { id: device.id, name: device.name });
                await this.connectDevice(device);
                console.log('[BT] gatt.connect() hasil:', device.gatt.connected);
                console.log('[BT DEBUG] silent reconnect berhasil:', { id: device.id, name: device.name });
            } catch (error) {
                console.error('[BT DEBUG] init/silent reconnect gagal:', error);
                this.setStatus(this.savedDevice ? 'saved-disconnected' : 'no-permission');
            }
            return this.status;
        })();
        return this.initPromise;
    }

    async pairNewPrinter() {
        if (!this.supported()) throw new Error('Web Bluetooth tidak tersedia. Gunakan Chrome Android melalui HTTPS.');
        try {
            const device = await navigator.bluetooth.requestDevice({ filters: [{ services: [SERVICE_UUID] }], optionalServices: [SERVICE_UUID] });
            console.log('[BT DEBUG] requestDevice() resolve:', { id: device.id, name: device.name });
            this.bindDevice(device);
            await this.saveDevice(device);
            console.log('[BT DEBUG] IndexedDB read-back:', await this.readSaved());
            await this.connectDevice(device);
            return device;
        } catch (error) {
            if (error.name === 'NotFoundError') throw new Error('Pemilihan printer dibatalkan atau printer BLE tidak ditemukan.');
            throw error;
        }
    }

    async ensureConnected() {
        await this.init();
        if (this.status === 'saved-unavailable' && !this.device) {
            throw new Error('Chrome Android ini tidak mendukung getDevices(), jadi izin printer tidak bisa dipulihkan setelah refresh. Hubungkan ulang atau gunakan APK/native bridge.');
        }
        if (this.device?.gatt?.connected && this.characteristic) return this.device;
        if (!this.device && typeof navigator.bluetooth.getDevices === 'function') {
            const saved = await this.readSaved();
            const permitted = await navigator.bluetooth.getDevices();
            console.log('[BT DEBUG] ensureConnected getDevices():', permitted.length, permitted, 'savedId:', saved?.id || null);
            const device = permitted.find((item) => item.id === saved?.id);
            if (device) this.bindDevice(device);
        }
        if (!this.device) { this.setStatus('no-permission'); throw new Error('Printer belum terhubung ke browser ini. Klik Hubungkan Printer.'); }
        let lastError;
        for (let attempt = 0; attempt < 2; attempt += 1) {
            try { return await this.connectDevice(this.device); }
            catch (error) { lastError = error; await new Promise((resolve) => setTimeout(resolve, 500)); }
        }
        throw new Error(`Gagal reconnect printer: ${lastError?.message || 'koneksi GATT gagal'}`);
    }

    async printEscPos(payload) {
        if (!(payload instanceof Uint8Array)) throw new TypeError('Payload ESC/POS harus Uint8Array.');
        await this.ensureConnected();
        try {
            for (let offset = 0; offset < payload.length; offset += BLE_CHUNK_SIZE) {
                const chunk = payload.slice(offset, offset + BLE_CHUNK_SIZE);
                if (this.characteristic.properties.writeWithoutResponse && this.characteristic.writeValueWithoutResponse) {
                    await this.characteristic.writeValueWithoutResponse(chunk);
                } else {
                    await this.characteristic.writeValue(chunk);
                }
                await new Promise((resolve) => setTimeout(resolve, CHUNK_DELAY_MS));
            }
        } catch (error) {
            this.characteristic = null;
            this.setStatus('disconnected');
            throw new Error('Printer terputus, silakan cek koneksi lalu coba cetak ulang.');
        }
    }

    async forgetPrinter() {
        if (this.device?.gatt?.connected) this.device.gatt.disconnect();
        if (this.device?.forget) await this.device.forget();
        this.device = null;
        this.characteristic = null;
        this.savedDevice = null;
        await this.clearSaved();
        this.setStatus('no-permission');
    }
}

export const PrinterManager = new PersistentPrinterManager();
window.PrinterManager = PrinterManager;

const decodeBase64 = (value) => {
    const binary = atob(value);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) bytes[i] = binary.charCodeAt(i);
    return bytes;
};

const notify = (message, type = 'info') => window.dispatchEvent(new CustomEvent('printer-notice', { detail: { message, type } }));

const installLivewireListener = () => {
    if (!window.Livewire || window.__printerLivewireInstalled) return;
    window.__printerLivewireInstalled = true;
    window.Livewire.on('print-receipt', async ({ escposBase64 }) => {
        try { await PrinterManager.printEscPos(decodeBase64(escposBase64)); notify('Struk berhasil dicetak.', 'success'); }
        catch (error) { notify(error.message, 'error'); }
    });
};

document.addEventListener('livewire:init', installLivewireListener);
document.addEventListener('DOMContentLoaded', async () => { await PrinterManager.init(); installLivewireListener(); });
