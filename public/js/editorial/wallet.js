import { $, date, empty, escape, money, pagination, request, requestKey } from './dom.js';

const labels = { apertura: 'Saldo inicial', recarga: 'Recarga', compra: 'Compra', reembolso: 'Reembolso', ajuste: 'Ajuste' };
const requests = new WeakMap();

export function withRequestKey(form, data) {
    const signature = JSON.stringify(data);
    if (requests.get(form)?.signature !== signature) {
        requests.set(form, { signature, key: requestKey() });
    }

    return { ...data, request_key: requests.get(form).key };
}

export function completeRequest(form) {
    requests.delete(form);
}

export function walletRows(items) {
    if (!items.length) return empty('Aún no hay movimientos.');
    return `<div class="ledger-scroll"><table class="ledger">
        <thead><tr><th scope="col">Fecha y concepto</th><th scope="col">Importe</th><th scope="col">Saldo</th></tr></thead><tbody>
        ${items.map(item => `<tr><td><span class="tiny">${escape(date(item.created_at))} · ${escape(labels[item.tipo] ?? item.tipo)}</span>
            <strong>${escape(item.concepto)}</strong>${item.folio ? `<small>${escape(item.folio)}</small>` : ''}</td>
            <td class="${item.importe_cents < 0 ? 'ledger-debit' : 'ledger-credit'}">${item.importe_cents > 0 ? '+' : ''}${money(item.importe_cents)}</td>
            <td>${money(item.saldo_cents)}</td></tr>`).join('')}</tbody></table></div>`;
}

export async function loadWallet(page = 1) {
    const data = await request('/mi-monedero?page=' + page);
    $('#account-wallet').textContent = money(data.saldo_cents);
    $('#wallet-history').innerHTML = walletRows(data.movimientos.data) + pagination(data.movimientos, 'account-wallet');
    return data;
}
