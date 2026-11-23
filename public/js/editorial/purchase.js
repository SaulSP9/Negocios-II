import { $, all, action, escape, fromForm, imageUrl, money, request, requestKey } from './dom.js';
import { addToCart, setupCart, syncCart } from './cart.js';
import { paintFavoriteButtons, toggleFavorite } from './catalog.js';
import { refreshClocks, renderTicker } from './campaign.js';

const productPage = $('[data-product-page]');
if (productPage) {
    setupCart();
    const id = Number(productPage.dataset.initialProduct);
    const gallery = $('#product-gallery');
    const galleryButtons = all('[data-gallery-view]');
    const favorite = $('[data-favorite]', productPage);
    const form = $('#product-form');
    paintFavoriteButtons(id);
    favorite.addEventListener('click', () => toggleFavorite(id));
    galleryButtons.forEach((button, index) => {
        button.addEventListener('click', () => {
            const label = button.getAttribute('aria-label');
            galleryButtons.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
            gallery.dataset.frame = button.dataset.galleryView;
            $('img', gallery).alt = $('#product-title').textContent + ' · ' + label;
            $('#gallery-caption').innerHTML = `${escape(label)} <span>0${index + 1} / 0${galleryButtons.length}</span>`;
        });
    });
    const cartReady = request('/sesion').then(async session => {
        const profile = session.user?.role === 'cliente' ? await request('/auth/perfil') : null;
        await syncCart(session.user, profile);
    });
    action(null, () => cartReady);
    action(null, async () => renderTicker(await request('/promociones')));
    setInterval(refreshClocks, 1000);

    if (form) {
        const quantity = $('#product-quantity', form);
        all('[data-product-qty]', form).forEach(button => {
            button.addEventListener('click', () => {
                const desired = (Number(quantity.value) || 1) + Number(button.dataset.productQty);
                quantity.value = Math.max(1, Math.min(Number(quantity.max), desired));
            });
        });
        form.addEventListener('submit', event => {
            event.preventDefault();
            action(event.submitter, async () => {
                await cartReady;
                const values = fromForm(form);
                const product = await request('/catalogo/' + id);
                await addToCart(product, values.talla, Number(values.cantidad));
            });
        });
    }
}

const checkout = $('#checkout-form');
if (checkout) {
    const message = $('#checkout-message');
    const pay = $('#checkout-pay');
    const wallet = $('input[value=monedero]', checkout);
    let blocked = pay.disabled;
    let lastPayload;

    function explain(text) {
        message.textContent = text;
        message.hidden = false;
        message.focus();
    }

    async function updateCheckout() {
        const { cart, wallet: balance } = await request('/checkout');
        checkout.elements.total_esperado.value = cart.total;
        $('#summary-items').innerHTML = cart.items.map(line => `<li>
            <div class="summary-thumb"><img src="${imageUrl(line.imagen_url)}" alt="" width="56" height="65"><span>${line.cantidad}</span></div>
            <div><strong>${escape(line.nombre)}</strong><small>Talla ${escape(line.talla)}</small></div>
            <span>${money((line.precio_unitario - line.discount) * line.cantidad)}</span></li>`).join('');
        $('#summary-subtotal').textContent = money(cart.subtotal);
        $('#summary-discount').textContent = (cart.descuento ? '−' : '') + money(cart.descuento);
        $('#summary-tax').textContent = money(cart.impuesto);
        $('#summary-total').textContent = money(cart.total);
        $('#checkout-wallet').textContent = money(balance);
        wallet.disabled = balance < cart.total;
        if (wallet.disabled) wallet.checked = false;
        $('#wallet-shortfall').hidden = !wallet.disabled;
        blocked = !cart.items.length || cart.items.some(line => line.cantidad_producto > line.stock)
            || (wallet.disabled && checkout.dataset.simulated !== '1');

        return cart.items.length > 0;
    }

    checkout.addEventListener('submit', async event => {
        event.preventDefault();
        if (pay.disabled) return;
        const payload = fromForm(checkout);
        delete payload._token;
        delete payload.checkout_key;
        const signature = JSON.stringify(payload);
        if (lastPayload && lastPayload !== signature) checkout.elements.checkout_key.value = requestKey();
        lastPayload = signature;
        payload.checkout_key = checkout.elements.checkout_key.value;
        pay.disabled = true;
        pay.textContent = 'Procesando…';
        message.hidden = true;
        let navigating = false;
        try {
            const order = await request(checkout.action, 'POST', payload);
            navigating = true;
            location.assign(order.recibo_url);
        } catch (error) {
            if (error.status === 409 || error.status === 422) {
                try {
                    if (!await updateCheckout()) {
                        explain('El carrito está vacío. Revisa tus pedidos antes de iniciar otra compra.');
                        return;
                    }
                } catch {
                    // Mantiene el error del pago si tampoco se puede actualizar la cotización.
                }
            }
            explain(error.message);
        } finally {
            if (!navigating) {
                pay.disabled = blocked;
                pay.textContent = 'Confirmar y pagar';
            }
        }
    });
}

$('#print-receipt')?.addEventListener('click', () => window.print());
