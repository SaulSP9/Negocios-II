import { $, all, action, empty, escape, fromForm, imageUrl, money, notify, pagination, request, showDialog } from './dom.js';
import { refreshClocks, renderTicker, startCarousel } from './campaign.js';
import { renderCampaign, renderCatalog, renderFamilies, toggleFavorite } from './catalog.js';
import { addToCart, openCart, setProfile, setupCart, syncCart } from './cart.js';
import { operationTab, orderRows, setOperator, setupOperations } from './operations.js';
import { ticketFolio, ticketRows, ticketStatus, ticketTimeline } from './tickets.js';
import { completeRequest, loadWallet, withRequestKey } from './wallet.js';

const views = all('[data-view]');
const managementShell = Boolean($('.management-head'));
const accountLinks = all('[data-account-section]');
const accountPanels = all('[data-account-panel]');
const accountAccessLinks = all('[data-account-mode]');
const aliases = { catalogo: 'coleccion', login: 'cuenta', perfil: 'cuenta', registro: 'cuenta', admin: 'operacion' };
let view = 'coleccion';
let user = null;
let profile = null;
let category = '';
let catalogVersion = 0;
let searchTimer;
let promos = [];
let auctionData;
let publications = [];
let ticketFilters = {};
let displayedClient = null;

function urlView(url = new URL(location.href)) {
    if (url.pathname === '/gestion/tienda' || url.hash.startsWith('#operacion-')) return 'operacion';
    const desired = url.searchParams.get('vista') || (url.pathname === '/promociones' ? 'campana' : 'coleccion');
    return aliases[desired] || desired;
}

function urlTab() {
    const requested = location.hash.replace(/^#operacion-/, '');
    return ['promociones', 'impuestos', 'monederos', 'pedidos', 'atencion'].includes(requested) ? requested : 'promociones';
}

function writeUrl(url, replace = false) {
    if (url !== location.pathname + location.search + location.hash) {
        history[replace ? 'replaceState' : 'pushState']({}, '', url);
    }
}

async function loadCatalog() {
    const version = ++catalogVersion;
    const params = new URLSearchParams();
    if (category) params.set('categoria', category);
    const text = $('#catalog-search').value.trim();
    if (text) params.set('buscar', text);
    const selectedSort = $('#catalog-sort').value;
    if (selectedSort !== 'coleccion') params.set('orden', selectedSort);
    const data = await request('/catalogo' + (params.size ? '?' + params : ''));
    if (version === catalogVersion) {
        const slug = data.categorias.find(item => item.slug === category || item.nombre === category)?.slug || category;
        renderCatalog(data, slug, $('#catalog-favorites').checked);
    }
}

async function loadPromotions() {
    promos = await request('/promociones');
    renderTicker(promos);
}

async function loadProfile(page = 1) {
    profile = user?.role === 'cliente' ? await request('/auth/perfil?page=' + page) : null;
    setProfile(profile);
    return profile;
}

function renderComments(comments) {
    $('#comments').innerHTML = comments.map(comment => `<article class="card"><h3>${escape(comment.name)}</h3>
        <p>${escape(comment.text)}</p></article>`).join('') || empty('Todavía no hay comentarios.');
}

function renderAuction(data) {
    auctionData = data;
    const ends = new Date(Date.now() + data.timerSeconds * 1000).toISOString();
    const offers = data.offers.map(offer => `<li>${escape(offer.user)} · ${money(offer.amount * 100)}</li>`).join('');
    $('#auction').innerHTML = `<article class="card"><img class="auction-image" src="${imageUrl(data.product.image)}" alt="${escape(data.product.name)}">
        <div class="auction-meta"><h2>${escape(data.product.name)}</h2><p>Oferta actual <strong>${money(data.product.currentBid * 100)}</strong></p>
            ${data.timerSeconds > 0 ? `<p>Termina en <time data-countdown="${ends}"></time></p>` : '<p>Subasta cerrada</p>'}</div>
        ${data.timerSeconds > 0 && user ? `<form class="form-stack" id="bid-form">
            <label>Tu oferta (MXN) <input name="amount" type="number" min="${(data.product.currentBid + .01).toFixed(2)}" step=".01" required></label>
            <button class="button primary">Hacer oferta</button></form>` : ''}
        ${data.timerSeconds > 0 && !user ? '<a class="button" href="/?vista=cuenta">Inicia sesión para ofertar ↗</a>' : ''}</article>
        <section class="card"><h2>Últimas ofertas</h2>${offers ? '<ol class="receipt-lines">' + offers + '</ol>' : empty('Todavía no hay ofertas.')}</section>`;
    refreshClocks();
}

function renderPublications(list) {
    publications = list;
    $('#publication-form').hidden = !user;
    $('#publications').innerHTML = user ? list.map(p => `<article class="card">
        <img src="${imageUrl(p.image)}" alt="" width="130" height="150" loading="lazy">
        <h3>${escape(p.name)}</h3><p>${money(p.price * 100)}</p><p>${escape(p.description)}</p>
        <div class="actions"><button type="button" class="button" data-pub-edit="${p.id}">Editar</button>
            <button type="button" class="button" data-pub-delete="${p.id}">Eliminar</button></div>
        </article>`).join('') || empty('Todavía no tienes publicaciones.')
        : '<p class="card">Inicia sesión para administrar tus publicaciones. <a class="text-link" href="/?vista=cuenta">Ir a Mi cuenta ↗</a></p>';
}

async function loadTickets(container, page = 1) {
    if (!profile) {
        $(container).innerHTML = empty('Inicia sesión como cliente para ver tus tickets.');
        return;
    }
    const filters = container === '#tickets' ? ticketFilters : {};
    const tickets = await request('/atencion/tickets?' + new URLSearchParams({ page, ...filters }));
    $(container).innerHTML = ticketRows(tickets.data) + pagination(tickets, container === '#tickets' ? 'support-tickets' : 'account-tickets');
}

function markAccountLink(link, active) {
    if (active) link.setAttribute('aria-current', 'page');
    else link.removeAttribute('aria-current');
}

async function loadAccountOrders(page = 1) {
    const orders = page === 1 ? profile.pedidos : (await request('/auth/perfil?page=' + page)).pedidos;
    $('#account-orders').innerHTML = orderRows(orders.data) + pagination(orders, 'account-orders');
}

async function renderAccount() {
    $('#account-guest').hidden = Boolean(user);
    $('#account-member').hidden = !user;
    $('#account-client').hidden = !profile;
    $('#account-stage').hidden = !profile;
    if (!user) {
        const params = new URLSearchParams(location.search);
        const mode = params.get('vista') === 'registro' ? 'register' : 'login';
        $('#login-form').hidden = mode !== 'login';
        $('#register-form').hidden = mode !== 'register';
        accountAccessLinks.forEach(link => {
            const target = new URLSearchParams({ vista: link.dataset.accountMode === 'register' ? 'registro' : 'cuenta' });
            if (params.get('continuar') === 'checkout') target.set('continuar', 'checkout');
            link.href = '/?' + target + location.hash;
            markAccountLink(link, link.dataset.accountMode === mode);
        });
        displayedClient = null;
        return;
    }

    $('#account-name').textContent = profile?.cliente.nombre ?? user.name;
    $('#account-email').textContent = user.email;
    if (!profile) return;

    const fragment = location.hash.slice(1);
    const section = accountPanels.some(panel => panel.id === fragment) ? fragment : 'mis-datos';
    accountLinks.forEach(link => markAccountLink(link, link.dataset.accountSection === section));
    accountPanels.forEach(panel => { panel.hidden = panel.id !== section; });

    if (section === 'mis-datos' && displayedClient !== profile.cliente) {
        const inputs = $('#profile-form').elements;
        for (const [key, value] of Object.entries(profile.cliente)) {
            if (inputs.namedItem(key)) inputs.namedItem(key).value = value ?? '';
        }
        displayedClient = profile.cliente;
    } else if (section === 'mi-monedero') {
        $('#account-wallet').textContent = money(profile.saldo_cents);
        await loadWallet();
    } else if (section === 'mis-pedidos') {
        await loadAccountOrders();
    } else if (section === 'mis-tickets') {
        await loadTickets('#account-tickets');
    }
}

async function go(url, { reload = false, focus = false } = {}) {
    clearTimeout(searchTimer);
    if (!reload) writeUrl(url);
    const wanted = urlView();
    view = views.some(section => section.dataset.view === wanted) ? wanted : 'coleccion';
    $('#hero').hidden = view !== 'coleccion' || location.pathname !== '/' || location.search !== '';
    views.forEach(section => { section.hidden = section.dataset.view !== view; });
    all('[data-view-link]').forEach(link => {
        if (link.dataset.viewLink === view) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
    if (focus && view !== 'cuenta') {
        window.scrollTo(0, 0);
        const heading = $('section[data-view="' + view + '"] h1');
        heading?.focus({ preventScroll: true });
    }

    if (view === 'coleccion') {
        const params = new URLSearchParams(location.search);
        category = params.get('categoria') || '';
        $('#catalog-search').value = params.get('buscar') || '';
        const sorting = $('#catalog-sort');
        sorting.value = params.get('orden') || 'coleccion';
        if (!sorting.value) sorting.value = 'coleccion';
        $('#catalog-favorites').checked = params.get('favoritos') === '1';
        await loadCatalog();
    } else if (view === 'tienda') {
        renderFamilies(await request('/catalogo'));
    } else if (view === 'campana') {
        renderCampaign(await request('/catalogo'), promos);
        refreshClocks();
    } else if (view === 'comunidad') {
        renderComments(await request('/tienda/comentarios'));
    } else if (view === 'subasta') {
        renderAuction(await request('/tienda/subasta'));
    } else if (view === 'mis-publicaciones') {
        renderPublications(user ? await request('/tienda/publicaciones') : []);
    } else if (view === 'ayuda') {
        $('#support-gate').hidden = Boolean(profile);
        $('#ticket-form').hidden = !profile;
        $('#ticket-filter').hidden = !profile;
        await loadTickets('#tickets');
    } else if (view === 'cuenta') {
        const accountRender = renderAccount();
        if (focus) {
            window.scrollTo(0, 0);
            $(user ? '#account-member h1' : '#account-guest h1')?.focus({ preventScroll: true });
        }
        await accountRender;
    } else if (view === 'operacion') {
        if ($('#operation-content').hidden) return;
        if (!managementShell) {
            location.replace('/gestion/tienda#operacion-' + urlTab());
            return;
        }
        if (!location.hash.startsWith('#operacion-')) writeUrl('/gestion/tienda#operacion-promociones', true);
        await operationTab(urlTab());
    }
}

function refineCatalog() {
    const params = new URLSearchParams();
    if (category) params.set('categoria', category);
    const text = $('#catalog-search').value.trim();
    if (text) params.set('buscar', text);
    const selectedSort = $('#catalog-sort').value;
    if (selectedSort !== 'coleccion') params.set('orden', selectedSort);
    if ($('#catalog-favorites').checked) params.set('favoritos', '1');
    writeUrl('/catalogo' + (params.size ? '?' + params : ''), true);
    $('#hero').hidden = true;
    return loadCatalog();
}

async function setSession(next) {
    user = next;
    setOperator(user);
    try {
        await loadProfile();
        await syncCart(user, profile);
        if (profile && new URLSearchParams(location.search).get('continuar') === 'checkout') location.assign('/checkout');
    } catch (error) {
        await syncCart(user, null);
        notify(error.message);
    }
}

function publicationForm(item = {}) {
    const form = $('#publication-form');
    ['id', 'name', 'price', 'image', 'description'].forEach(name => {
        form.elements.namedItem(name).value = item[name] ?? '';
    });
    $('#publication-heading').textContent = item.id ? 'Editar publicación' : 'Nueva publicación';
}

async function showTicket(id) {
    const item = await request('/atencion/ticket/' + id);
    $('#ticket-body').innerHTML = `<div class="ticket-card-head"><p class="overline">${ticketFolio(item.id)} · ${escape(item.categoria)}</p>${ticketStatus(item.estado)}</div>
        <h2 id="ticket-title">${escape(item.asunto)}</h2>${ticketTimeline(item)}`;
    showDialog($('#ticket-dialog'));
}

async function onPage(key, page) {
    if (key.startsWith('op-')) await operationTab(key.substring(3), page);
    if (key === 'account-wallet') await loadWallet(page);
    if (key === 'account-orders') await loadAccountOrders(page);
    if (key === 'support-tickets') await loadTickets('#tickets', page);
    if (key === 'account-tickets') await loadTickets('#account-tickets', page);
}

document.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target.closest('a[href]');
    if (link) {
        if (managementShell) return;
        const url = new URL(link.href);
        if (url.origin === location.origin && (url.pathname === '/' || url.pathname === '/catalogo' || url.pathname === '/promociones')) {
            event.preventDefault();
            link.closest('dialog')?.close();
            const withinAccount = link.matches('[data-account-section], [data-account-mode]');
            if (withinAccount && link.hasAttribute('aria-current')) return;
            action(null, () => go(url.pathname + url.search + url.hash, { focus: !withinAccount }));
        }
        return;
    }

    const button = event.target.closest('button');
    if (!button) return;
    const data = button.dataset;
    if (data.category !== undefined) {
        category = data.category;
        action(null, refineCatalog);
    } else if (data.favorite) {
        toggleFavorite(Number(data.favorite));
        if (view === 'coleccion' && $('#catalog-favorites').checked) action(null, loadCatalog);
    } else if (data.rechargeAmount) {
        const amount = $('#recharge-form [name=importe]');
        amount.value = data.rechargeAmount;
        amount.focus();
    } else if (button.id === 'recover-password') {
        $('#forgot-form [name=email]').value = $('#login-form [name=email]').value;
        showDialog($('#password-dialog'));
    } else if (data.quickBuy) {
        action(button, async () => {
            const item = await request('/catalogo/' + data.quickBuy);
            await addToCart(item, item.tallas.find(size => ['M', '32'].includes(size)) ?? item.tallas[0], 1);
        });
    } else if (data.ticket) {
        action(button, () => showTicket(data.ticket));
    } else if (data.page) {
        action(button, () => onPage(data.page, Number(data.number)));
    } else if (data.pubEdit) {
        publicationForm(publications.find(p => p.id === Number(data.pubEdit)));
        $('#publication-form [name=name]').focus();
    } else if (data.pubDelete && confirm('¿Eliminar esta publicación?')) {
        action(button, async () => {
            await request('/tienda/publicaciones/' + data.pubDelete, 'DELETE');
            publicationForm();
            renderPublications(await request('/tienda/publicaciones'));
            notify('Publicación eliminada.');
        });
    } else if (button.id === 'publication-reset') {
        publicationForm();
    } else if (button.id === 'demo-login') {
        action(button, async () => {
            await setSession((await request('/auth/demo', 'POST')).user);
            await renderAccount();
        });
    } else if (['logout', 'management-logout'].includes(button.id)) {
        action(button, async () => {
            await setSession((await request('/auth/logout', 'POST')).user);
            if (button.id === 'management-logout') { location.assign('/'); return; }
            await go('/?vista=cuenta');
            notify('Sesión cerrada.');
        });
    }
});

document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;
    const form = event.target;
    const handlers = {
        'search-form': () => { clearTimeout(searchTimer); return refineCatalog(); },
        'forgot-form': async (data) => {
            const { message } = await request('/auth/recuperar', 'POST', data);
            $('#password-dialog').close();
            notify(message);
        },
        'recharge-form': async (data) => {
            await request('/mi-monedero/recargas', 'POST', withRequestKey(form, data));
            completeRequest(form);
            form.reset();
            $('#wallet-recharge').open = false;
            const wallet = await loadWallet();
            profile.saldo_cents = wallet.saldo_cents;
            setProfile(profile);
            notify('Recarga de demostración registrada.');
        },
        'ticket-filter': async (data) => { ticketFilters = data; await loadTickets('#tickets'); },
        'login-form': async (data) => {
            await setSession((await request('/auth/login', 'POST', data)).user);
            form.reset();
            await renderAccount();
        },
        'register-form': async (data) => {
            await setSession((await request('/auth/registro', 'POST', data)).user);
            form.reset();
            await renderAccount();
            notify('Cuenta creada.');
        },
        'profile-form': async (data) => {
            profile = await request('/auth/perfil', 'PUT', data);
            setProfile(profile);
            await renderAccount();
            notify('Perfil guardado.');
        },
        'comment-form': async (data) => {
            if (!user) {
                await go('/?vista=cuenta', { focus: true });
                notify('Inicia sesión para publicar un comentario.');
                return;
            }
            renderComments(await request('/tienda/comentarios', 'POST', data));
            form.reset();
        },
        'bid-form': async (data) => renderAuction(await request('/tienda/subastas/' + auctionData.id + '/ofertas', 'POST', data)),
        'publication-form': async ({ id, ...data }) => {
            await request('/tienda/publicaciones' + (id ? '/' + id : ''), id ? 'PUT' : 'POST', data);
            publicationForm();
            renderPublications(await request('/tienda/publicaciones'));
            notify('Publicación guardada.');
        },
        'ticket-form': async (data) => {
            const ticket = await request('/atencion/ticket', 'POST', data);
            form.reset();
            $('#ticket-filter').reset();
            ticketFilters = {};
            await loadTickets('#tickets');
            await showTicket(ticket.id);
        },
        'newsletter-form': async (data) => {
            await request('/tienda/suscripciones', 'POST', data);
            form.reset();
            notify('Suscripción registrada.');
        },
    };
    if (!handlers[form.id]) return;
    event.preventDefault();
    action(event.submitter, () => handlers[form.id](fromForm(form)));
});

$('#catalog-search').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => action(null, refineCatalog), 220);
});
for (const selector of ['#catalog-sort', '#catalog-favorites']) {
    $(selector).addEventListener('change', () => action(null, refineCatalog));
}
window.addEventListener('popstate', () => action(null, () => go(null, { reload: true })));
window.addEventListener('hashchange', () => {
    if (location.hash.startsWith('#operacion-') || accountLinks.some(link => link.hash === location.hash)) {
        action(null, () => go(null, { reload: true }));
    }
});

startCarousel();
setupCart();
setupOperations(async (key) => {
    writeUrl('/gestion/tienda#operacion-' + key);
    await operationTab(key);
}, loadPromotions);
setInterval(() => {
    const active = promos.filter(promotion => new Date(promotion.fecha_fin).getTime() > Date.now());
    if (active.length !== promos.length) {
        promos = active;
        renderTicker(promos);
        if (view === 'coleccion' || view === 'tienda' || view === 'campana') action(null, () => go(null, { reload: true }));
    }
    refreshClocks();
}, 1000);

await action(null, async () => {
    const [session, promotionResult] = await Promise.allSettled([request('/sesion'), loadPromotions()]);
    if (promotionResult.status === 'rejected') notify(promotionResult.reason.message);
    await setSession(session.status === 'fulfilled' ? session.value.user : null);
    await go(null, { reload: true });
    if ($('#open-cart') && new URLSearchParams(location.search).get('carrito') === '1') await openCart();
});
