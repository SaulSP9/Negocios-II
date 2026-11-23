import { $, escape, empty, money, imageUrl } from './dom.js';

const favoritesKey = 'hf-favorite-products';
let favorites = new Set();
try {
    favorites = new Set(JSON.parse(localStorage.getItem(favoritesKey) || '[]').map(Number));
} catch {
    // El catálogo sigue disponible si el almacenamiento está desactivado.
}

function favoriteButton(product) {
    const selected = favorites.has(product.id);
    return `<button type="button" class="favorite-product" data-favorite="${product.id}" aria-pressed="${selected}" aria-label="Favorito: ${escape(product.nombre)}">${selected ? '♥' : '♡'}</button>`;
}

export function paintFavoriteButtons(id) {
    document.querySelectorAll(`[data-favorite="${id}"]`).forEach(button => {
        button.setAttribute('aria-pressed', String(favorites.has(id)));
        button.textContent = favorites.has(id) ? '♥' : '♡';
    });
}

export function toggleFavorite(id) {
    if (favorites.has(id)) favorites.delete(id);
    else favorites.add(id);
    try { localStorage.setItem(favoritesKey, JSON.stringify([...favorites])); } catch {}
    paintFavoriteButtons(id);
}


function stockLabel(product) {
    const level = product.stock_actual < 1 ? 'sold-out' : product.stock_actual <= 5 ? 'low' : 'available';
    return `<span class="product-stock" data-stock="${level}">${product.stock_actual < 1 ? 'Agotado' : product.stock_actual <= 5 ? 'Últimas ' + product.stock_actual + ' unidades' : 'Disponible'}</span>`;
}

export function prices(product) {
    return `<div class="price"><strong>${money(product.precio)}</strong>${product.precio_anterior ? `<del>${money(product.precio_anterior)}</del>` : ''}</div>`;
}

export function grid(products, quick = false) {
    if (!products.length) return empty('No hay piezas que coincidan.');
    return products.map(p => `<article class="product-card">
        <a class="product-media" href="/catalogo/${p.id}" data-product="${p.id}" aria-label="Ver ${escape(p.nombre)}">
            <img src="${imageUrl(p.imagen_url)}" alt="${escape(p.nombre + ' · ' + p.color)}" width="720" height="900" loading="lazy">
            ${p.etiqueta ? `<span class="flag ${p.descuento ? 'sale' : ''}">${escape(p.etiqueta)}</span>` : ''}
        </a>
        ${favoriteButton(p)}
        <div class="product-meta">
            <p class="tiny">${escape(p.familia)}</p>
            <h3><a href="/catalogo/${p.id}" data-product="${p.id}">${escape(p.nombre)}</a></h3>
            <p class="product-color">${escape(p.color)}</p>${prices(p)}
            <div class="product-info">${stockLabel(p)}<span class="tiny">${escape(p.tallas.join(' / '))}</span></div>
        </div>
        <div class="product-actions">
            <a class="button ${p.stock_actual > 0 ? 'primary' : ''}" href="/catalogo/${p.id}" data-product="${p.id}">${p.stock_actual > 0 ? 'Elegir talla' : 'Ver detalle'}</a>
            ${quick && p.stock_actual > 0 ? `<button class="button" data-quick-buy="${p.id}">Compra rápida · ${escape(p.tallas.find(size => ['M', '32'].includes(size)) ?? p.tallas[0])}</button>` : ''}
        </div>
    </article>`).join('');
}

export function renderCatalog(data, category, onlyFavorites = false) {
    $('#category-tabs').innerHTML = [{ slug: '', nombre: 'Todo' }, ...data.categorias].map(c =>
        `<button type="button" class="button" data-category="${escape(c.slug)}" aria-pressed="${c.slug === category}">${escape(c.nombre)}</button>`).join('');
    const items = onlyFavorites ? data.productos.filter(product => favorites.has(product.id)) : data.productos;
    const count = items.length;
    $('#catalog-count').textContent = `${count} ${count === 1 ? 'pieza' : 'piezas'}`;
    $('#catalog-products').innerHTML = grid(items);
}

export function renderFamilies(data) {
    const sections = data.categorias.map(family => {
        const products = data.productos.filter(p => p.categoria === family.slug);
        if (!products.length) return '';
        return `<section class="family"><header class="family-head"><h2>${escape(family.nombre)}</h2>
            <a class="text-link tiny" href="/catalogo?categoria=${encodeURIComponent(family.slug)}">Ver familia ↗</a></header>
            <div class="product-grid">${grid(products, true)}</div></section>`;
    });
    $('#families').innerHTML = sections.join('') || empty('El catálogo se está preparando.');
}

export function renderCampaign(data, promotions) {
    $('#campaign-promos').innerHTML = promotions.map(p => `<article class="card">
        <p class="tiny">Termina en <time data-countdown="${escape(p.fecha_fin)}"></time></p>
        <h3>${escape(p.nombre)}</h3><p class="promo-description">${escape(p.descripcion)}</p>
        <a class="button" href="/catalogo${p.producto_id ? '/' + p.producto_id : p.categoria ? '?categoria=' + encodeURIComponent(p.categoria.slug) : ''}">Ver prendas ↗</a>
        </article>`).join('') || empty('Por el momento no hay promociones vigentes.');
    $('#campaign-products').innerHTML = grid(data.productos.filter(p => p.descuento > 0));
    $('#campaign-featured').innerHTML = grid(data.productos.filter(p => p.destacado));
}

