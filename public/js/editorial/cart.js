import { $, action, escape, imageUrl, money, notify, request, requestKey, showDialog } from './dom.js';

const storageKey = 'hf-editorial-guest-cart';
const zero = () => ({ cantidad: 0, items: [], subtotal: 0, descuento: 0, impuesto: 0, impuestos: [], total: 0 });
const dialog = $('#cart-dialog');
let actor = null;
let profile = null;
let bag = zero();
let taxes = [];
let memoryDraft = [];
let draftImport;
let processing = false;

function draft() {
    try {
        const items = JSON.parse(sessionStorage.getItem(storageKey) || JSON.stringify(memoryDraft));
        return Array.isArray(items) ? items : [];
    } catch {
        return memoryDraft;
    }
}

function saveDraft(items) {
    memoryDraft = items;
    try {
        sessionStorage.setItem(storageKey, JSON.stringify(items));
    } catch {
        // El carrito se conserva durante esta visita si el navegador bloquea el almacenamiento.
    }
}

function draftQuote(items) {
    const subtotal = items.reduce((total, item) => total + item.cantidad * item.precio_unitario, 0);
    const descuento = items.reduce((total, item) => total + item.cantidad * item.discount, 0);
    const impuestos = taxes.map(tax => ({ nombre: tax.nombre, porcentaje: tax.porcentaje, importe: Math.round((subtotal - descuento) * Number(tax.porcentaje) / 100) }));
    const impuesto = impuestos.reduce((total, tax) => total + tax.importe, 0);
    return { items, cantidad: items.reduce((total, item) => total + item.cantidad, 0), subtotal, descuento, impuestos, impuesto, total: subtotal - descuento + impuesto };
}

function totals(value) {
    const taxDetail = value.impuestos?.map(tax => `${tax.nombre} ${parseFloat(tax.porcentaje)}%`).join(' + ');
    return `<dl class="totals">
        <div><dt>Subtotal</dt><dd>${money(value.subtotal)}</dd></div>
        <div><dt>Descuento aplicado</dt><dd>${value.descuento ? '−' : ''}${money(value.descuento)}</dd></div>
        <div><dt>${escape(taxDetail || 'Impuesto')}</dt><dd>${money(value.impuesto)}</dd></div>
        <div class="total"><dt>Total final</dt><dd>${money(value.total)}</dd></div></dl>`;
}

function renderCart() {
    if (!dialog) return;
    $('#cart-count').textContent = bag.cantidad;
    if (!bag.items.length) {
        $('#cart-panel').innerHTML = '<div class="cart-empty"><h3>Tu carrito está vacío.</h3><p>Explora la colección para agregar prendas.</p><button type="button" class="button primary" data-close>Seguir comprando</button></div>';
        return;
    }
    const lines = bag.items.map(item => {
        const unit = item.precio_unitario - (item.discount || 0);
        const held = item.cantidad_producto ?? bag.items.filter(row => row.producto_id === item.producto_id).reduce((sum, row) => sum + row.cantidad, 0);
        const canAdd = held < item.stock && item.cantidad < 99;
        return `<article class="cart-item">
            <img src="${imageUrl(item.imagen_url)}" alt="" width="80" height="100">
            <div class="cart-item-copy"><h3>${escape(item.nombre)}</h3><p class="tiny">${escape(item.color ? item.color + ' · ' : '')}Talla ${escape(item.talla)}</p>
                <p class="cart-unit">${money(unit)} / ud. ${item.discount ? `<del>${money(item.precio_unitario)}</del>` : ''}</p>
                ${held > item.stock ? '<p class="cart-alert">Ajusta la cantidad. El stock cambió.</p>' : ''}
                <button type="button" data-cart-remove="${escape(item.id)}" aria-label="Eliminar ${escape(item.nombre)}" class="text-link">Eliminar</button>
            </div>
            <div class="cart-item-control"><strong>${money(unit * item.cantidad)}</strong>
                <div class="quantity" aria-label="Cantidad de ${escape(item.nombre)}">
                    <button type="button" data-cart-change="${escape(item.id)}" data-delta="-1" aria-label="Restar una unidad" ${item.cantidad <= 1 ? 'disabled' : ''}>−</button>
                    <span>${item.cantidad}</span>
                    <button type="button" data-cart-change="${escape(item.id)}" data-delta="1" aria-label="Sumar una unidad" ${canAdd ? '' : 'disabled'}>+</button>
                </div>
            </div>
        </article>`;
    }).join('');
    const unavailable = bag.items.some(item => (item.cantidad_producto ?? item.cantidad) > item.stock);
    $('#cart-panel').innerHTML = `<div class="cart-lines-head"><span>${bag.cantidad} ${bag.cantidad === 1 ? 'artículo' : 'artículos'}</span><span>Subtotal por artículo</span></div>${lines}
        <section class="cart-summary" aria-label="Resumen del carrito">${totals(bag)}
        <div class="actions"><button class="button" type="button" id="empty-cart">Vaciar carrito</button>
        <button class="button primary" type="button" id="start-checkout" ${unavailable ? 'disabled' : ''}>Continuar compra</button></div>
        ${!profile ? '<p class="tiny">Los importes se confirman al iniciar sesión.</p>' : ''}</section>`;
}

async function cartAction(button, callback) {
    if (processing) return;
    processing = true;
    dialog.setAttribute('aria-busy', 'true');
    try {
        await action(button, callback);
    } finally {
        processing = false;
        dialog.removeAttribute('aria-busy');
    }
}

function assign(next) {
    bag = next;
    renderCart();
}

export function setProfile(value) {
    profile = value;
}

export async function syncCart(user, customer) {
    actor = user;
    profile = customer;
    if (!customer) {
        assign(actor ? zero() : draftQuote(draft()));
        return;
    }

    let next = await request(`/carrito/${customer.cliente.id}`);
    const items = draft();
    if (items.length) {
        const lines = items.map(item => ({ producto_id: item.producto_id, talla: item.talla, cantidad: item.cantidad }));
        const signature = JSON.stringify(lines);
        if (draftImport?.signature !== signature) {
            try { draftImport = JSON.parse(sessionStorage.getItem(storageKey + '-import')); } catch {}
        }
        if (draftImport?.signature !== signature) draftImport = { signature, key: requestKey() };
        try { sessionStorage.setItem(storageKey + '-import', JSON.stringify(draftImport)); } catch {}
        next = await request('/carrito/importar', 'POST', { request_key: draftImport.key, items: lines });
        saveDraft([]);
        draftImport = null;
        try { sessionStorage.removeItem(storageKey + '-import'); } catch {}
        if (next.rechazados.length) notify('Revisa las existencias de ' + next.rechazados.map(row => items[row.indice].nombre).join(', ') + '.');
    }
    assign(next);

}

export async function addToCart(product, size, quantity) {
    if (actor && !profile) throw new Error('Inicia sesión con una cuenta de cliente para comprar.');
    if (profile) {
        const next = await request('/carrito/agregar', 'POST', { producto_id: product.id, talla: size, cantidad: quantity });
        assign(next);
    } else {
        const items = bag.items;
        const item = items.find(row => row.producto_id === product.id && row.talla === size);
        const sum = items.filter(row => row.producto_id === product.id).reduce((total, row) => total + row.cantidad, 0);
        if (sum + quantity > product.stock_actual || (item?.cantidad ?? 0) + quantity > 99) {
            throw new Error('Existencias insuficientes.');
        }
        if (item) {
            item.cantidad += quantity;
        } else {
            if (items.length >= 50) throw new Error('El carrito admite hasta 50 renglones.');
            items.push({
                id: product.id + '-' + size, producto_id: product.id, talla: size, cantidad: quantity, nombre: product.nombre,
                stock: product.stock_actual, color: product.color, precio_unitario: product.precio_base, discount: product.descuento, imagen_url: product.imagen_url,
            });
        }
        saveDraft(items);
        assign(draftQuote(items));
    }
    return openCart();
}

export async function openCart() {
    showDialog(dialog);
    if (!actor) {
        taxes = await request('/impuestos');
        assign(draftQuote(bag.items));
    } else if (profile) {
        assign(await request(`/carrito/${profile.cliente.id}`));
    }
    renderCart();
}

async function changeLine(id, quantity) {
    if (profile) {
        const url = `/carrito/item/${id}`;
        assign(quantity ? await request(url, 'PUT', { cantidad: quantity }) : await request(url, 'DELETE'));
    } else {
        const items = bag.items.filter(row => row.id !== id || quantity > 0);
        const line = items.find(row => row.id === id);
        if (line) line.cantidad = quantity;
        saveDraft(items);
        assign(draftQuote(items));
    }
}

export function setupCart() {
    if (!dialog) return;
    assign(draftQuote(draft()));

    $('#open-cart').addEventListener('click', (event) => cartAction(event.currentTarget, openCart));
    dialog.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button || processing) return;
        if (button.dataset.cartChange !== undefined) {
            const line = bag.items.find(item => String(item.id) === button.dataset.cartChange);
            if (line) cartAction(button, () => changeLine(line.id, line.cantidad + Number(button.dataset.delta)));
        } else if (button.dataset.cartRemove !== undefined) {
            cartAction(button, () => changeLine(button.dataset.cartRemove, 0));
        } else if (button.id === 'empty-cart') {
            if (!window.confirm('¿Vaciar el carrito?')) return;
            cartAction(button, async () => {
                if (profile) assign(await request(`/carrito/vaciar/${profile.cliente.id}`, 'DELETE'));
                else { saveDraft([]); assign(draftQuote([])); }
            });
        } else if (button.id === 'start-checkout') {
            if (actor && !profile) {
                notify('Para comprar, inicia sesión como cliente.');
                return;
            }
            location.assign('/checkout');
        }
    });
}
