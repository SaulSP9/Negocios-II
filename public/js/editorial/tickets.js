import { date, empty, escape } from './dom.js';

export const ticketFolio = (id) => 'T-' + String(id).padStart(5, '0');
const states = { Abierto: 'open', 'En proceso': 'working', Respondido: 'answered', Cerrado: 'closed' };

export function ticketStatus(state) {
    return `<span class="ticket-status" data-status="${states[state] || 'open'}">${escape(state)}</span>`;
}

export function ticketFilter(filters = {}) {
    return `<form id="ops-ticket-filter" class="ticket-filters">
        <label>Buscar ticket<input name="q" type="search" placeholder="Folio o asunto" value="${escape(filters.q)}" maxlength="100"></label>
        <label>Estado<select name="estado"><option value="">Todos</option>${Object.keys(states).map(state => `<option ${filters.estado === state ? 'selected' : ''}>${state}</option>`).join('')}</select></label>
        <button class="button" type="submit">Filtrar</button></form>`;
}

export function ticketRows(entries, operational = false) {
    return entries.map(ticket => `<article class="card ticket-card">
        <div class="ticket-card-head"><span class="tiny">${ticketFolio(ticket.id)} · ${escape(ticket.categoria)}</span>${ticketStatus(ticket.estado)}</div>
        <h3>${escape(ticket.asunto)}</h3>
        <p class="ticket-date">${operational ? escape(ticket.cliente?.nombre) + ' · ' : ''}${escape(date(ticket.fecha))}</p>
        <p class="ticket-excerpt">${escape(ticket.respuesta || ticket.mensaje)}</p>
        <div class="actions"><button class="button" type="button" data-ticket="${ticket.id}">Ver seguimiento</button></div>
        ${operational && ticket.estado !== 'Cerrado' ? `<details class="ticket-reply"><summary>Actualizar atención</summary>
            <form class="form-stack" data-ops-form="atencion" data-id="${ticket.id}">
                <label>Estado <select name="estado"><option>Respondido</option><option ${ticket.estado === 'En proceso' ? 'selected' : ''}>En proceso</option><option>Cerrado</option></select></label>
                <label>Nuevo mensaje <textarea name="respuesta" rows="3" minlength="3" maxlength="4000"></textarea></label>
                <button class="button primary">Guardar respuesta</button>
            </form></details>` : ''}
    </article>`).join('') || empty('No hay tickets que coincidan.');
}

export function ticketTimeline(ticket) {
    return `<ol class="ticket-timeline"><li><p class="tiny">Solicitud · ${escape(date(ticket.fecha))}</p>
        <p>${escape(ticket.mensaje)}</p></li>${ticket.eventos.map(event => `<li>
        <div class="ticket-card-head"><p class="tiny">Equipo HF Studios · ${escape(date(event.created_at))}</p>${ticketStatus(event.estado)}</div>
        <p>${escape(event.mensaje)}</p></li>`).join('')}</ol>
        ${!ticket.eventos.length ? '<p class="tiny">Aún no hay actualizaciones.</p>' : ''}`;
}
