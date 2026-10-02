(() => {
    const $ = selector => document.querySelector(selector);
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const money = n => new Intl.NumberFormat('es-MX', {style:'currency', currency:'MXN'}).format(n);
    const date = v => v ? new Date(v).toLocaleString('es-MX') : '—';
    const admin = document.body.dataset.role === 'admin';
    const state = {suppliers:[], products:[], productPage:1, orderPage:1, historyPage:1, selected:null, productRevision:0, orderRevision:0};
    const keys = new WeakMap();
    let noticeTimer;
    function notice(text, error = false) {
        const el = $('#notice'); el.textContent = text; el.classList.toggle('error', error); el.hidden = false;
        clearTimeout(noticeTimer); noticeTimer = setTimeout(() => el.hidden = true, error ? 12000 : 6000);
    }
    async function action(element, fn) {
        if (element?.dataset.busy) return;
        if (element) {element.dataset.busy = '1'; element.disabled = true;}
        try {await fn();} catch (e) {notice(e.message, true);} finally {if (element) {delete element.dataset.busy; element.disabled = false;}}
    }
    function tab(id) {
        document.querySelectorAll('.panel').forEach(el => el.hidden = el.id !== id);
        document.querySelectorAll('[data-tab]').forEach(el => {if (el.dataset.tab === id) el.setAttribute('aria-current','page'); else el.removeAttribute('aria-current');});
    }
    function open(id) {$(id).showModal();}
    function close(id) {$(id).close();}
    function field(form, name) {return form.elements.namedItem(name);}
    function values(form) {return Object.fromEntries(new FormData(form));}
    function requestKey(form) {if (!keys.has(form)) keys.set(form, crypto.randomUUID()); return keys.get(form);}
    function pagination(selector, data, kind) {
        $(selector).innerHTML = `<button data-page="${kind}" data-number="${data.current_page-1}" ${data.current_page<=1?'disabled':''}>Anterior</button><span>${data.total} registros · ${data.current_page}/${data.last_page}</span><button data-page="${kind}" data-number="${data.current_page+1}" ${data.current_page>=data.last_page?'disabled':''}>Siguiente</button>`;
    }
    async function loadProducts(page = state.productPage) {
        state.productPage = page; const revision = ++state.productRevision;
        const query = new URLSearchParams(values($('#product-filter'))); query.set('page', page);
        const data = await HF.request('/productos?'+query);
        if (revision !== state.productRevision) return;
        state.products = data.data;
        $('#product-rows').innerHTML = data.data.map(p => `<tr><td><strong>${esc(p.nombre)}</strong><small>${esc(p.categoria)} · ${esc(p.proveedor||'Sin proveedor')}</small><small>${esc(p.ubicacion)}${p.activo?'':' · Oculto en tienda'}</small></td><td><span class="badge ${p.stock_bajo?'critical':''}">${p.stock_actual} ${p.stock_bajo?'· CRÍTICO':''}</span></td><td>${p.stock_minimo} / ${p.stock_objetivo}</td><td>${money(p.costo_unitario)}<small>Venta: ${money(p.precio_venta)}</small></td><td><span class="badge">${esc(p.estrategia_logistica)}</span></td><td><button data-history="${p.id}">Historial</button>${!p.deleted_at && admin?`<button data-edit-product="${p.id}">Editar</button><button data-strategy="${p.id}" data-value="${p.estrategia_logistica==='PUSH'?'PULL':'PUSH'}">Cambiar a ${p.estrategia_logistica==='PUSH'?'Pull':'Push'}</button><button class="danger" data-delete-product="${p.id}">Archivar</button>`:''}${p.deleted_at?'<span class="badge">Archivado</span>':''}</td></tr>`).join('') || '<tr><td colspan="6">Sin productos con estos filtros.</td></tr>';
        pagination('#product-pages', data, 'product');
    }
    async function loadSuppliers() {
        state.suppliers = await HF.request('/proveedores');
        $('#supplier-rows').innerHTML = state.suppliers.map(p => `<tr><td>${esc(p.nombre)}</td><td>${esc(p.contacto)}</td><td>${esc(p.correo)}</td><td>${esc(p.telefono)}</td><td>${admin?`<button data-edit-supplier="${p.id}">Editar</button><button class="danger" data-delete-supplier="${p.id}">Eliminar</button>`:'Consulta'}</td></tr>`).join('') || '<tr><td colspan="5">Registra el primer proveedor para asociarlo al catálogo.</td></tr>';
        const select = $('#product-form select[name="proveedor_id"]');
        if (select) {const chosen = select.value; select.innerHTML = '<option value="">Sin proveedor</option>'+state.suppliers.map(p=>`<option value="${p.id}">${esc(p.nombre)}</option>`).join(''); select.value = chosen;}
    }
    async function loadOrders(page = state.orderPage) {
        state.orderPage = page; const revision = ++state.orderRevision;
        const query = new URLSearchParams(values($('#order-filter'))); query.set('page', page);
        const data = await HF.request('/pedidos?'+query);
        if (revision !== state.orderRevision) return;
        $('#order-rows').innerHTML = data.data.map(p=>`<tr><td><strong>#${p.id} · ${esc(p.producto?.name)}</strong><small>Responsable: ${esc(p.usuario?.name||'Sistema')}</small></td><td>${p.cantidad}</td><td>${p.tipo==='reposicion'?'Reposición':'Venta'}<small>${p.automatico?'Push automático':'Manual'}</small></td><td><span class="badge ${p.estado==='pendiente'?'critical':''}">${esc(p.estado)}</span></td><td>${date(p.created_at)}<small>Surtido: ${date(p.fecha_surtido)}</small></td><td>${p.estado==='pendiente'?`<button class="primary" data-fulfill="${p.id}">Marcar surtido</button>`:'Registrado'}</td></tr>`).join('') || '<tr><td colspan="6">Sin pedidos con estos filtros.</td></tr>';
        pagination('#order-pages', data, 'order');
    }
    function bars(rows, label, amount, suffix) {
        const max = Math.max(1, ...rows.map(amount));
        return rows.map(r=>`<div class="bar-row"><div><span>${esc(label(r))}</span><strong>${amount(r)} ${suffix}</strong></div><div class="bar-track"><div class="bar-fill" style="width:${Math.max(0,Math.min(100,amount(r)/max*100))}%"></div></div></div>`).join('') || '<p>Aún no hay ventas surtidas registradas.</p>';
    }
    async function loadReports() {
        const d = await HF.request('/scm/reportes');
        $('#counters').innerHTML = [['Productos',d.total_productos],['Unidades disponibles',d.unidades_stock],['Valor a costo',money(d.valor_inventario_cents/100)],['Pedidos pendientes',d.pedidos_pendientes]].map(([label,value])=>`<div class="card"><small>${label}</small><strong>${esc(value)}</strong></div>`).join('');
        $('#top-chart').innerHTML = bars(d.productos_mas_vendidos,r=>r.nombre,r=>r.unidades,'u.');
        $('#strategy-chart').innerHTML = bars(d.comparacion,r=>`${r.estrategia}: ${r.productos} productos · ${r.criticos} críticos`,r=>r.stock,'u.');
        $('#critical').innerHTML = d.inventario_critico.map(p=>`<div class="list-row"><div><strong>${esc(p.nombre)}</strong><small> · ${esc(p.estrategia_logistica)}</small></div><button data-history="${p.id}">${p.stock_actual} / mín. ${p.stock_minimo}</button></div>`).join('') || '<p>No hay productos en nivel crítico.</p>';
        $('#slow').innerHTML = d.rotacion_lenta.map(p=>`<div class="list-row"><span>${esc(p.nombre)}<small> · stock ${p.stock_actual}</small></span><strong>${p.unidades_30_dias} ventas</strong></div>`).join('') || '<p>No hay productos con rotación lenta.</p>';
    }
    const descriptions = {'Inicial':'Se registra el catálogo y se establecen controles básicos de proveedores e inventario.','En desarrollo':'Se aplican movimientos trazables, pedidos y estrategias. El equipo revisa al menos tres evidencias.','Optimizado':'Las seis evidencias están verificadas y el equipo revisa reportes para ajustar decisiones logísticas.'};
    async function loadMaturity() {
        const data = await HF.request('/scm/estado'); const form = $('#maturity-form');
        field(form,'nivel_scm').value = data.nivel_scm;
        Object.entries(data.checklist).forEach(([k,v])=>field(form,k).checked = Boolean(v));
        if (!admin) [...form.elements].forEach(el=>el.disabled = true);
        $('#level-indicator').textContent = data.nivel_scm; $('#level-description').textContent = descriptions[data.nivel_scm];
    }
    async function refresh() {await Promise.all([loadProducts(),loadSuppliers(),loadOrders(),loadReports(),loadMaturity()]);}
    async function refreshInventory() {await Promise.all([loadProducts(),loadOrders(),loadReports()]);}
    async function productEditor(id) {
        const form = $('#product-form'); form.reset();
        const p = id ? await HF.request('/productos/'+id) : {id:'',nombre:'',descripcion:'',categoria:'Hoodie',proveedor_id:'',stock_actual:0,stock_minimo:0,stock_objetivo:10,costo_unitario:0,precio_venta:1,estrategia_logistica:'PULL',ubicacion:'Almacén principal',imagen:'',color:'',activo:true};
        Object.entries(p).forEach(([k,v])=>{const el = field(form,k);if(el) {if(el.type==='checkbox')el.checked=Boolean(v);else el.value=v??'';}});
        field(form,'stock_actual').disabled = Boolean(id); $('#product-title').textContent = id?'Editar producto':'Nuevo producto'; open('#product-dialog');
    }
    function supplierEditor(id) {
        const form = $('#supplier-form'); form.reset(); field(form,'id').value = id||'';
        if (id) Object.entries(state.suppliers.find(p=>p.id===id)).forEach(([k,v])=>{if(field(form,k))field(form,k).value=v;});
        open('#supplier-dialog');
    }
    async function loadHistory(page = 1) {
        state.historyPage = page;
        const [p,data] = await Promise.all([HF.request('/productos/'+state.selected),HF.request(`/productos/${state.selected}/movimientos?page=${page}`)]);
        $('#history-title').textContent = 'Movimientos · '+p.nombre; $('#history-stock').textContent = `Stock disponible: ${p.stock_actual} · mínimo: ${p.stock_minimo} · ${p.estrategia_logistica}${p.deleted_at?' · Archivado':''}`;
        const form = $('#movement-form'); form.hidden = Boolean(p.deleted_at); $('#movement-hint').hidden = Boolean(p.deleted_at); field(form,'producto_id').value = p.id;
        $('#history-rows').innerHTML = data.data.map(m=>`<tr><td>${date(m.fecha)}<small>${esc(m.usuario?.name||'Sistema')}</small></td><td>${esc(m.tipo)}<small>${esc(m.motivo)}</small></td><td>${m.cantidad}</td><td>${m.stock_anterior} → ${m.stock_resultante}</td><td>${esc(m.referencia||'—')}${m.pedido_id?`<small>Pedido SCM #${m.pedido_id}</small>`:''}</td></tr>`).join('') || '<tr><td colspan="5">Sin movimientos registrados.</td></tr>';
        pagination('#history-pages', data, 'history');
    }
    async function findOrderProducts() {
        const data = await HF.request('/productos?q='+encodeURIComponent($('#order-search').value));
        field($('#order-form'),'producto_id').innerHTML = data.data.map(p=>`<option value="${p.id}">${esc(p.nombre)} · ${p.estrategia_logistica} · stock ${p.stock_actual}</option>`).join('') || '<option value="">Sin resultados</option>';
    }
    document.addEventListener('click', e => {
        const b = e.target.closest('button'); if (!b || (b.form && b.type === 'submit')) return;
        if (b.dataset.tab) {tab(b.dataset.tab); return;}
        if (b.dataset.close) {close('#'+b.dataset.close); return;}
        action(b, async()=>{
            if (b.id==='logout') {await HF.request('/logout','POST',{}); location.href='/';}
            else if (b.id==='refresh') {await refresh(); notice('Datos actualizados.');}
            else if (b.id==='new-product') await productEditor();
            else if (b.dataset.editProduct) await productEditor(Number(b.dataset.editProduct));
            else if (b.dataset.deleteProduct) {if(!confirm('¿Archivar este producto? Su historial se conserva. Requiere stock cero y ningún pedido pendiente.'))return; await HF.request('/productos/'+b.dataset.deleteProduct,'DELETE'); await refreshInventory(); notice('Producto archivado.');}
            else if (b.dataset.strategy) {await HF.request(`/productos/${b.dataset.strategy}/estrategia`,'PUT',{estrategia_logistica:b.dataset.value}); await refreshInventory(); notice('Estrategia actualizada. Revisa los pedidos pendientes.');}
            else if (b.id==='new-supplier') supplierEditor();
            else if (b.dataset.editSupplier) supplierEditor(Number(b.dataset.editSupplier));
            else if (b.dataset.deleteSupplier) {if(!confirm('¿Eliminar proveedor? Solo se permite si no está asociado a productos.'))return;await HF.request('/proveedores/'+b.dataset.deleteSupplier,'DELETE');await loadSuppliers();notice('Proveedor eliminado.');}
            else if (b.dataset.history) {state.selected = Number(b.dataset.history); const form=$('#movement-form');form.reset();keys.delete(form);await loadHistory();open('#history-dialog');}
            else if (b.id==='new-order') {const form=$('#order-form');form.reset();keys.delete(form);await findOrderProducts();open('#order-dialog');}
            else if (b.id==='order-search-button') await findOrderProducts();
            else if (b.dataset.fulfill) {if(!confirm('¿Confirmas que este pedido fue surtido físicamente? Esta acción registra el movimiento de inventario.'))return;await HF.request(`/pedidos/${b.dataset.fulfill}/estado`,'PUT',{estado:'surtido'});await refreshInventory();notice('Pedido surtido e inventario actualizado.');}
            else if (b.dataset.page) {const page=Number(b.dataset.number);if(b.dataset.page==='product')await loadProducts(page);if(b.dataset.page==='order')await loadOrders(page);if(b.dataset.page==='history')await loadHistory(page);}
        });
    });
    document.addEventListener('submit', e => {
        const form=e.target; e.preventDefault(); const b=e.submitter||form.querySelector('button[type="submit"],button.primary,button:not([type])');
        action(b, async()=>{
            const d=values(form);
            if(form.id==='product-filter') await loadProducts(1);
            else if(form.id==='order-filter') await loadOrders(1);
            else if(form.id==='product-form') {
                const id=d.id; delete d.id; d.activo=field(form,'activo').checked; d.proveedor_id=d.proveedor_id?Number(d.proveedor_id):null;
                ['stock_actual','stock_minimo','stock_objetivo','costo_unitario','precio_venta'].forEach(k=>{if(k in d)d[k]=Number(d[k]);});
                await HF.request(id?'/productos/'+id:'/productos',id?'PUT':'POST',d);close('#product-dialog');await refreshInventory();notice('Producto guardado.');
            } else if(form.id==='supplier-form') {const id=d.id;delete d.id;await HF.request(id?'/proveedores/'+id:'/proveedores',id?'PUT':'POST',d);close('#supplier-dialog');await Promise.all([loadSuppliers(),loadProducts()]);notice('Proveedor guardado.');}
            else if(form.id==='movement-form') {d.producto_id=Number(d.producto_id);d.cantidad=Number(d.cantidad);d.request_key=requestKey(form);await HF.request('/inventario/movimiento','POST',d);keys.delete(form);field(form,'cantidad').value='';field(form,'referencia').value='';await Promise.all([loadHistory(1),refreshInventory()]);notice('Movimiento registrado.');}
            else if(form.id==='order-form') {d.producto_id=Number(d.producto_id);d.cantidad=Number(d.cantidad);d.request_key=requestKey(form);await HF.request('/pedidos','POST',d);keys.delete(form);close('#order-dialog');await Promise.all([loadOrders(1),loadReports()]);notice('Pedido generado. Recíbelo desde la lista cuando esté surtido.');}
            else if(form.id==='maturity-form' && admin) {const checklist={};['catalogo','proveedores','inventario','estrategias','pedidos','reportes'].forEach(k=>checklist[k]=field(form,k).checked);await HF.request('/scm/nivel','PUT',{nivel_scm:d.nivel_scm,checklist});await loadMaturity();notice('Nivel SCM guardado.');}
        });
    });
    // Una edición del formulario inicia una nueva operación; un reintento sin cambios conserva la clave.
    ['movement-form','order-form'].forEach(id=>$('#'+id).addEventListener('input',e=>keys.delete(e.currentTarget)));
    action(null, refresh);
})();
