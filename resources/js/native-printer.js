const native = () => window.Capacitor?.isNativePlatform?.() && window.Capacitor?.Plugins?.ThermalPrinter;
const plugin = () => window.Capacitor.Plugins.ThermalPrinter;

const line = (left, right = '', width = 32) => {
    const r = String(right); const l = String(left).slice(0, Math.max(1, width-r.length-1));
    return `${l}${' '.repeat(Math.max(1,width-l.length-r.length))}${r}\n`;
};
const center = (value, width = 32) => { const s=String(value).slice(0,width); return `${' '.repeat(Math.max(0,Math.floor((width-s.length)/2)))}${s}\n`; };
const money = value => new Intl.NumberFormat('id-ID',{maximumFractionDigits:0}).format(Number(value)||0);

export function nativeReceiptText(t) {
    const s=t.store||{}; let out=center(s.name||'KopiPOS');
    if(s.address) out+=center(s.address); if(s.phone) out+=center(s.phone);
    out+='--------------------------------\n'; out+=line('Order',t.orderNumber); out+=line('Waktu',t.paidAt); out+=line('Kasir',t.cashier);
    if(t.tableNumber) out+=line('Meja',t.tableNumber); if(t.customerName) out+=line('Nama',t.customerName);
    out+='--------------------------------\n';
    (t.items||[]).forEach(i=>{out+=line(`${i.quantity}x ${i.name}`,money(i.total)); if(i.variant)out+=`  + ${i.variant}\n`; (i.modifiers||[]).forEach(m=>out+=`  + ${m.name} ${money(m.price)}\n`); if(i.notes)out+=`  ${i.notes}\n`;});
    out+='--------------------------------\n';out+=line('Subtotal',money(t.subtotal));if(Number(t.discount))out+=line('Diskon',`-${money(t.discount)}`);if(Number(t.tax))out+=line('Pajak',money(t.tax));out+=line('TOTAL',`Rp ${money(t.total)}`);out+=line(t.paymentMethod||'Bayar',money(t.paidAmount));if(Number(t.change))out+=line('Kembali',money(t.change));out+='--------------------------------\n';out+=center(s.footer||'Terima kasih');return out;
}

export async function chooseNativePrinter() {
    if(!native()) return null;
    const result=await plugin().requestPermissionsAndList();
    if(result.savedAddress) return plugin().connect({address:result.savedAddress});
    const preferred=(result.devices||[]).find(d=>/RPP|POS|PRINTER/i.test(d.name))||(result.devices||[])[0];
    if(!preferred) throw new Error('Pairing RPP02N dulu di Settings Bluetooth Android.');
    return plugin().connect({address:preferred.address});
}

export async function nativePrint(transaction) {
    if(!native()) return false;
    await plugin().print({text:nativeReceiptText(transaction)}); return true;
}

export async function directNativePrint(transaction) {
    if(!native()) throw new Error('Native printer bridge tidak tersedia.');
    return nativePrint(transaction);
}

window.KopiPOSNative={available:native,choosePrinter:chooseNativePrinter,print:nativePrint,directPrint:directNativePrint};
