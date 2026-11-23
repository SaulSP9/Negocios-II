import { $, all, action, empty, escape, fromForm, money, notify, pagination, request } from './dom.js';

import { ticketFilter, ticketRows } from './tickets.js';
import { completeRequest, walletRows, withRequestKey } from './wallet.js';

const tabList = $('.operation-tabs');
const tabs = all('[data-operation-tab]', tabList);
const permissions = Object.fromEntries(tabs.map(tab => [tab.dataset.operationTab, tab.dataset.permission]));
let staff = null;
let redrawPromotions;
let nextLoad = 0;
let ticketFilters = {};

const has = (permission) => staff?.permissions?.includes(permission);
const tabsRoot = $('#operation-content');

function localDate(value) {
    if (!value) return '';
    const stamp = new Date(value);
    return new Date(stamp.getTime() - stamp.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
}

function rows(entries, render, text) {
    return entries.map(render).join('') || empty(text);
}

function promoForm(categories, products, promo = {}) {
    return `<form class="card form-stack" data-ops-form="promociones" data-id="${promo.id || ''}">
        <h2>${promo.id ? 'Editar promoción' : 'Nueva promoción'}</h2>
        <label>Nombre <input name="nombre" maxlength="120" value="${escape(promo.nombre)}" required></label>
        <label>Descripción <textarea name="descripcion" rows="3" maxlength="1500">${escape(promo.descripcion)}</textarea></label>
        <label>Familia <select name="categoria_id"><option value="">Todas las familias</option>
            ${categories.map(c => `<option value="${c.id}" ${promo.categoria_id === c.id ? 'selected' : ''}>${escape(c.nombre)}</option>`).join('')}
        </select></label>
        <label>Producto específico <select name="producto_id"><option value="">Cualquier producto de la familia elegida</option>
            ${products.map(product => `<option value="${product.id}" ${promo.producto_id === product.id ? 'selected' : ''}>${escape(product.nombre)}</option>`).join('')}
        </select></label>
        <p class="tiny">Elige una familia o un producto específico. Deja ambos sin selección para aplicar a todo el catálogo.</p>
        <label>Tipo de descuento <select name="tipo_descuento">
            <option value="porcentaje">Porcentaje</option><option value="fijo" ${promo.tipo_descuento === 'fijo' ? 'selected' : ''}>Importe fijo en MXN</option>
        </select></label>
        <label>Valor <input name="valor" type="number" min=".01" step=".01" value="${escape(promo.valor)}" required></label>
        <label>Fecha de inicio <input name="fecha_inicio" type="datetime-local" value="${localDate(promo.fecha_inicio)}" required></label>
        <label>Fecha de término <input name="fecha_fin" type="datetime-local" value="${localDate(promo.fecha_fin)}" required></label>
        <label class="inline-check"><input name="estado" type="checkbox" ${promo.estado === false ? '' : 'checked'}> Activa</label>
        <button class="button primary">Guardar promoción</button></form>`;
}

function taxForm(tax = {}) {
    return `<form class="card form-stack" data-ops-form="impuestos" data-id="${tax.id || ''}">
        <h2>${tax.id ? 'Editar impuesto' : 'Nuevo impuesto'}</h2>
        <label>Nombre <input name="nombre" maxlength="120" value="${escape(tax.nombre)}" required></label>
        <label>Porcentaje <input name="porcentaje" type="number" min="0" max="100" step=".01" value="${escape(tax.porcentaje)}" required></label>
        <label class="inline-check"><input name="estado" type="checkbox" ${tax.estado === false ? '' : 'checked'}> Activo</label>
        <button class="button primary">Guardar impuesto</button></form>`;
}

export function orderRows(entries, operational = false) {
    return rows(entries, order => {
        const editable = operational && staff?.role === 'admin';
        const choices = {
            Pagado: ['Enviado', 'Cancelado'], Pendiente: ['Enviado', 'Cancelado'], Enviado: ['Entregado'],
        }[order.status] || [];
        return `<article class="card">
            <p class="tiny">${escape(order.date)} · ${escape(order.status)}${order.pago ? ' · ' + escape(order.pago.estado) : ''}</p>
            <h3>${escape(order.id)}</h3><p>${operational ? escape(order.user) + ' · ' : ''}${money(order.total * 100)}</p>
            <p class="tiny">${order.items.reduce((sum, item) => sum + item.quantity, 0)} unidades · ${escape(order.items.map(item => item.name).slice(0, 2).join(', '))}</p>
            <div class="actions">
                <a class="button" href="/compras/${encodeURIComponent(order.id)}">${order.pago ? 'Ver recibo' : 'Ver detalle'}</a>
            </div>
            ${editable && choices.length ? `<form class="form-stack" data-ops-form="pedidos" data-id="${order.database_id}">
                <label>Actualizar estado <select name="status">${choices.map(value => `<option>${value}</option>`).join('')}</select></label>
                <button class="button primary">Actualizar pedido</button></form>` : ''}
            </article>`;
    }, 'Todavía no hay pedidos.');
}

export function setOperator(user) {
    staff = user;
    const internal = Boolean(has('crm') || has('scm.read') || has('erp.read'));
    all('[data-staff-link]').forEach(link => { link.hidden = !internal; });
    const commercial = Object.values(permissions).some(has);
    tabsRoot.hidden = !commercial;
    tabList.hidden = !commercial;
    $('#operation-gate').hidden = commercial;
    tabs.forEach(tab => { tab.hidden = !has(permissions[tab.dataset.operationTab]); });

}

export async function operationTab(key, page = 1) {
    if (tabsRoot.hidden) return;
    if (!has(permissions[key])) {
        key = Object.keys(permissions).find(name => has(permissions[name]));
        if (location.pathname === '/gestion/tienda') history.replaceState({}, '', '#operacion-' + key);
    }
    tabs.forEach(tab => {
        const selected = tab.dataset.operationTab === key;
        tab.setAttribute('aria-selected', String(selected));
        tab.tabIndex = selected ? 0 : -1;
        $('#' + tab.getAttribute('aria-controls')).hidden = !selected;
    });
    const panel = $('#operacion-' + key);
    if (!has(permissions[key])) {
        panel.innerHTML = empty('Tu rol no tiene permisos para gestionar este panel.');
        return;
    }
    const load = String(++nextLoad);
    panel.dataset.load = load;
    panel.innerHTML = empty('Cargando…');
    let markup;
    if (key === 'promociones') {
        const [promos, { categorias, productos }] = await Promise.all([request('/promociones?administrar=1'), request('/catalogo')]);
        markup = `<div class="content-grid">${promoForm(categorias, productos)}<div class="stack">
            ${rows(promos, promo => promoForm(categorias, productos, promo), 'Sin promociones registradas.')}</div></div>`;
    } else if (key === 'impuestos') {
        const taxes = await request('/impuestos');
        markup = `<div class="content-grid">${taxForm()}<div class="stack">
            ${rows(taxes, taxForm, 'Sin impuestos configurados.')}</div></div>`;
    } else if (key === 'monederos') {
        const [data, clients, report] = await Promise.all([
            request('/operacion/monederos?page=' + page), request('/clientes?per_page=100'), request('/operacion/monederos/reporte'),
        ]);
        markup = `<div class="content-grid"><form class="form-stack card" data-ops-form="monederos">
            <h2>Ajuste de saldo</h2><label>Cliente (ID) <input name="cliente" type="number" min="1" list="wallet-clients" required>
            <datalist id="wallet-clients">${(clients.data ?? clients).map(c => `<option value="${c.id}">${escape(c.nombre)} · ${escape(c.correo)}</option>`).join('')}</datalist></label>
            <label>Tipo <select name="tipo"><option value="abono">Abono</option><option value="cargo">Cargo</option></select></label>
            <label>Importe MXN <input name="importe" type="number" min=".01" step=".01" required></label>
            <label>Motivo <input name="concepto" minlength="3" maxlength="200" required></label>
            <button class="button primary">Registrar ajuste</button>
            </form><div class="stack"><article class="card"><h2>Uso del monedero</h2>
                ${report.map(row => `<p>${escape(row.tipo)} · ${row.operaciones} movimientos · ${money(row.importe_cents)}</p>`).join('') || empty('Todavía no hay movimientos.')}
                <a class="button" href="/operacion/monederos/reporte?csv=1">Descargar reporte CSV</a></article>
            ${rows(data.data, wallet => `<article class="card"><h3>${escape(wallet.cliente.nombre)}</h3>
                <p>${money(wallet.saldo_cents)} · ${escape(wallet.cliente.correo)}</p>
                <button type="button" class="button" data-wallet-client="${wallet.cliente.id}" data-wallet-page="1">Ver movimientos</button>
                <div id="wallet-client-${wallet.cliente.id}"></div></article>`, 'Sin monederos registrados.')}
            ${pagination(data, 'op-monederos')}</div></div>`;
    } else if (key === 'pedidos') {
        const data = await request('/operacion/pedidos?page=' + page);
        markup = '<div class="table-list">' + orderRows(data.data, true) + pagination(data, 'op-pedidos') + '</div>';
    } else {
        const params = new URLSearchParams({ page, ...ticketFilters });
        const data = await request('/atencion/tickets?' + params);
        markup = ticketFilter(ticketFilters) + '<div class="table-list">' + ticketRows(data.data, true) + pagination(data, 'op-atencion') + '</div>';
    }
    if (panel.dataset.load === load) panel.innerHTML = markup;
}

export function setupOperations(select, onPromotionChange) {
    redrawPromotions = onPromotionChange;
    tabsRoot.addEventListener('click', (event) => {
        const wallet = event.target.closest('[data-wallet-client]');
        if (wallet) action(wallet, async () => {
            const { walletClient: id, walletPage: page } = wallet.dataset;
            const data = await request(`/operacion/monederos/${id}?page=${page}`);
            const moves = data.movimientos;
            $('#wallet-client-' + id).innerHTML = walletRows(moves.data) + `<div class="actions">
                ${moves.current_page > 1 ? `<button type="button" class="button" data-wallet-client="${id}" data-wallet-page="${moves.current_page - 1}">Anterior</button>` : ''}
                ${moves.current_page < moves.last_page ? `<button type="button" class="button" data-wallet-client="${id}" data-wallet-page="${moves.current_page + 1}">Siguiente</button>` : ''}
            </div>`;
        });
    });
    tabList.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-operation-tab]');
        if (tab && tab.getAttribute('aria-selected') !== 'true') action(null, () => select(tab.dataset.operationTab));
    });
    tabList.addEventListener('keydown', (event) => {
        const available = tabs.filter(tab => !tab.hidden);
        const current = available.indexOf(event.target);
        const offsets = { ArrowRight: 1, ArrowLeft: -1 };
        if (current < 0 || (!offsets[event.key] && event.key !== 'Home' && event.key !== 'End')) return;
        event.preventDefault();
        const target = available[event.key === 'Home' ? 0 : event.key === 'End' ? available.length - 1 : (current + offsets[event.key] + available.length) % available.length];
        target.focus();
        if (target.getAttribute('aria-selected') !== 'true') action(null, () => select(target.dataset.operationTab));
    });
    tabsRoot.addEventListener('change', (event) => {
        const field = event.target;
        const form = field.closest('[data-ops-form="promociones"]');
        if (!form || !field.value) return;
        if (field.name === 'producto_id') form.elements.categoria_id.value = '';
        if (field.name === 'categoria_id') form.elements.producto_id.value = '';
    });
    tabsRoot.addEventListener('submit', (event) => {
        const form = event.target;
        if (form.id === 'ops-ticket-filter') {
            event.preventDefault();
            ticketFilters = fromForm(form);
            action(null, () => operationTab('atencion'));
            return;
        }
        if (!form.dataset.opsForm) return;
        event.preventDefault();
        action(event.submitter, async () => {
            const { opsForm: key, id } = form.dataset;
            const payload = fromForm(form);
            if (key === 'promociones' || key === 'impuestos') {
                payload.estado = form.elements.estado.checked;
                if (key === 'promociones') {
                    payload.fecha_inicio = new Date(payload.fecha_inicio).toISOString();
                    payload.fecha_fin = new Date(payload.fecha_fin).toISOString();
                }
                await request('/' + key + (id ? '/' + id : ''), id ? 'PUT' : 'POST', payload);
                if (key === 'promociones') await redrawPromotions();
            } else if (key === 'monederos') {
                const { cliente, ...adjustment } = payload;
                await request(`/operacion/monederos/${cliente}/ajustes`, 'POST', withRequestKey(form, adjustment));
                completeRequest(form);
            } else if (key === 'pedidos') {
                await request('/tienda/pedidos/' + id + '/estado', 'PUT', payload);
            } else if (key === 'atencion') {
                await request('/atencion/ticket/' + id + '/respuesta', 'PUT', payload);
            }
            if (location.hash === '#operacion-' + key) await operationTab(key);
            notify('Cambios guardados.');
        });
    });
}
