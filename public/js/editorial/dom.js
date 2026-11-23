export const $ = (selector, root = document) => root.querySelector(selector);
export const all = (selector, root = document) => Array.from(root.querySelectorAll(selector));
export const escape = (value) => String(value ?? '').replace(/[&<>"']/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[character]));
export const money = (cents) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(cents || 0) / 100);
export const fromForm = (form) => Object.fromEntries(new FormData(form));
export const date = (value) => value ? new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '';
export const empty = (text) => `<p class="empty">${escape(text)}</p>`;
export const fallback = document.body.dataset.fallback;

export function requestKey() {
    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
    return [hex.slice(0, 8), hex.slice(8, 12), hex.slice(12, 16), hex.slice(16, 20), hex.slice(20)].join('-');
}

export async function request(url, method = 'GET', body) {
    const token = $('meta[name="csrf-token"]');
    const response = await fetch(url, {
        method, headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token.content },
        credentials: 'same-origin', ...(body === undefined ? {} : { body: JSON.stringify(body) }),
    });
    const data = response.status === 204 ? null : await response.json().catch(() => null);
    if (!response.ok) {
        const message = data?.errors ? Object.values(data.errors).flat()[0] : data?.message;
        const error = new Error(message || `La solicitud falló (${response.status}).`);
        error.status = response.status;
        throw error;
    }
    if (data?.csrf_token) token.content = data.csrf_token;
    return data;
}

let toastTimer;
export function notify(text) {
    const dialog = $('dialog[open]');
    if (dialog) {
        let message = $('.dialog-message', dialog);
        if (!message) {
            message = document.createElement('output');
            message.className = 'dialog-message';
            message.setAttribute('role', 'status');
            dialog.append(message);
        }
        message.textContent = text;
        message.scrollIntoView({ block: 'nearest' });
        return;
    }
    const message = $('#message');
    message.textContent = text;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { message.textContent = ''; }, 4800);
}

export async function action(button, callback) {
    if (button?.dataset.busy) return;
    if (button) {
        button.dataset.busy = '1';
        button.disabled = true;
    }
    try {
        return await callback();
    } catch (error) {
        notify(error.message);
    } finally {
        if (button) {
            delete button.dataset.busy;
            button.disabled = false;
        }
    }
}

export function imageUrl(value) {
    try {
        const url = new URL(value || fallback, location.origin);
        return ['http:', 'https:'].includes(url.protocol) ? escape(url.href) : escape(fallback);
    } catch {
        return escape(fallback);
    }
}

document.addEventListener('error', (event) => {
    const image = event.target;
    if (image instanceof HTMLImageElement && image.src !== fallback && !image.dataset.fallbackUsed && !image.hasAttribute('onerror')) {
        image.dataset.fallbackUsed = 'true';
        image.src = fallback;
    }
}, true);

export function showDialog(dialog) {
    $('.dialog-message', dialog)?.remove();
    if (!dialog.open) dialog.showModal();
}
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-close]');
    if (button) button.closest('dialog').close();
    if (event.target.matches('dialog')) {
        const bounds = event.target.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
            event.target.close();
        }
    }
});

export function pagination(data, key) {
    if (!data || data.last_page < 2) return '';
    return `<div class="actions"><button class="button" data-page="${key}" data-number="${data.current_page - 1}" ${data.current_page <= 1 ? 'disabled' : ''}>Anterior</button>
         <span class="tiny">Página ${data.current_page} de ${data.last_page}</span>
         <button class="button" data-page="${key}" data-number="${data.current_page + 1}" ${data.current_page >= data.last_page ? 'disabled' : ''}>Siguiente</button></div>`;
}
