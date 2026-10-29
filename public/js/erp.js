(() => {
    'use strict';
    const $ = s => document.querySelector(s);
    const $$ = s => [...document.querySelectorAll(s)];
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const money = v => new Intl.NumberFormat('es-MX', {style:'currency',currency:'MXN'}).format(Number(v || 0));
    const date = v => v ? new Date(v).toLocaleString('es-MX') : '—';
    const permissions = JSON.parse(document.body.dataset.permissions || '[]');
    const can = p => permissions.includes(p);
    const state = {client:null,clients:[],catalog:[],cart:new Map(),step:1,key:null,created:null,saving:false,
        orderPage:1,reportPage:1,clientRevision:0,catalogRevision:0,orderRevision:0,resources:[],reports:[],users:[]};
    const roles = {admin:'Administrador',ventas:'Ventas',logistica:'Logística',usuario:'Operador',cliente:'Cliente'};
    const evidenceLabels = {clientes:'Clientes activos en CRM',inventario:'Catálogo con inventario SCM',ordenes:'Órdenes de clientes procesadas',movimientos:'Movimientos automáticos de inventario',reportes:'Cortes empresariales guardados',procesos:'Procesos internos completados'};

    function notice(message, error = false) {
        const el = $('#notice'), host = $('dialog[open]') || $('main');
        if (el.parentElement !== host) host.prepend(el);
        el.textContent = message; el.classList.toggle('error', error); el.hidden = false;
        el.setAttribute('role', error ? 'alert' : 'status');
        el.scrollIntoView?.({block:'nearest'});
    }
    async function run(button, fn) {
        if (button?.dataset.busy) return;
        const label = button?.innerHTML;
        if (button) {button.dataset.busy='1';button.disabled=true;}
        try {await fn();}
        catch (e) {console.error(e);notice(e.message || 'No se pudo completar la operación.', true);}
        finally {if(button){delete button.dataset.busy;button.disabled=false;button.innerHTML=label;}}
    }
    function pager(root, result, kind) {
        const el = $(root); if (!el) return;
        el.innerHTML = '<button type="button" data-page="'+kind+'" data-number="'+(result.current_page-1)+'" '+(result.current_page<=1?'disabled':'')+'>Anterior</button><span>'+result.total+' registros · '+result.current_page+'/'+result.last_page+'</span><button type="button" data-page="'+kind+'" data-number="'+(result.current_page+1)+'" '+(result.current_page>=result.last_page?'disabled':'')+'>Siguiente</button>';
    }
    function showDialog(id) {
        $('#notice').hidden=true;
        $(id).showModal();
    }
    function clearKey() {state.key=null;}
    function clientCard(c) {
        return '<h3>'+esc(c.nombre)+'</h3><p>'+esc(c.empresa || 'Cliente CRM')+'<br>'+esc(c.correo)+'<br>'+esc(c.telefono || 'Sin teléfono')+'</p>';
    }
    function kpis(m) {
        return [
            ['Ventas totales',money(m.ventas_totales),'ERP + tienda surtida'],
            ['Órdenes ERP',m.ordenes_procesadas,'Procesadas y completadas'],
            ['Productos vendidos',m.productos_vendidos,'Unidades ERP + tienda'],
            ['Clientes activos',m.clientes_activos,'Directorio del CRM'],
            ['Inventario disponible',m.inventario_disponible,'Unidades en SCM']
        ].map(r=>'<div class="kpi"><span>'+r[0]+'</span><strong>'+esc(r[1])+'</strong><small>'+r[2]+'</small></div>').join('');
    }
    async function dashboard() {
        const m=await HF.request('/erp/metricas');
        $('#kpis').innerHTML=kpis(m);
        const max=Math.max(1,...m.ventas_por_mes.map(r=>r.total));
        $('#sales-chart').innerHTML=m.ventas_por_mes.map(r=>'<div class="month-bar"><span>'+money(r.total)+'</span><div class="bar" style="height:'+Math.max(1,r.total/max*150)+'px"></div><small>'+esc(r.etiqueta)+'</small></div>').join('');
        const top=Math.max(1,...m.productos_mas_vendidos.map(r=>r.unidades));
        $('#top-chart').innerHTML=m.productos_mas_vendidos.map(r=>'<div class="horizontal-bar"><div><span>'+esc(r.nombre)+'</span><strong>'+r.unidades+'</strong></div><div class="track"><div style="width:'+r.unidades/top*100+'%"></div></div></div>').join('') || '<p class="empty">Procesa una venta para ver los productos más vendidos.</p>';
        $('#critical-stock').innerHTML=m.inventario_critico.slice(0,6).map(p=>'<div class="list-row"><div><strong>'+esc(p.nombre)+'</strong><small>'+esc(p.estrategia_logistica)+' · mínimo '+p.stock_minimo+'</small></div><span class="badge badge-pendiente">'+p.stock_actual+' unidades</span></div>').join('') || '<p class="empty">El inventario se encuentra por encima de los mínimos.</p>';
        $('#integration').innerHTML='<div class="integration-row"><b>CRM</b><span>'+m.integracion.clientes_crm+' clientes<small>Datos del directorio existente</small></span></div><div class="integration-row"><b>SCM</b><span>'+m.integracion.productos_scm+' productos · '+m.reposiciones_scm_pendientes+' reposiciones pendientes<small>Inventario y proveedores compartidos</small></span></div><div class="integration-row"><b>ERP</b><span>'+m.integracion.ordenes_erp+' órdenes · '+m.ordenes_pendientes+' pendientes<small>'+m.integracion.procesos_completados+' procesos internos completados</small></span></div>';
    }
    async function orders(page=1) {
        state.orderPage=page;const rev=++state.orderRevision;
        const query=new URLSearchParams(new FormData($('#order-filter')));query.set('page',page);
        const r=await HF.request('/ordenes?'+query);if(rev!==state.orderRevision)return;
        $('#order-rows').innerHTML=r.data.map(o=>{
            let actions='<button type="button" data-view="'+o.id+'">Ver orden</button>';
            if(o.estado==='pendiente' && can('erp.write')) actions+='<button type="button" data-process="'+o.id+'">Procesar</button>';
            if(o.estado==='procesada' && can('erp.complete')) actions+='<button type="button" data-state="completada" data-order="'+o.id+'">Completar entrega</button>';
            if(o.estado!=='cancelada' && can('erp.cancel') && (document.body.dataset.role==='admin'||o.estado==='pendiente')) actions+='<button type="button" class="danger" data-state="cancelada" data-order="'+o.id+'">Cancelar</button>';
            return '<tr><td><strong>'+esc(o.folio)+'</strong><small>'+esc(date(o.fecha))+'</small></td><td>'+esc(o.cliente.nombre)+'<small>'+esc(o.cliente.correo)+'</small></td><td>'+esc(o.usuario.name)+'</td><td>'+money(o.total)+'</td><td><span class="badge badge-'+o.estado+'">'+esc(o.estado)+'</span></td><td>'+actions+'</td></tr>';
        }).join('')||'<tr><td colspan="6">No hay órdenes con estos filtros.</td></tr>';
        pager('#order-pages',r,'orders');
    }
    async function clients(page=1) {
        if(!can('erp.write'))return;
        const rev=++state.clientRevision;
        const query=new URLSearchParams(new FormData($('#client-search')));query.set('estado','activo');query.set('page',page);
        const r=await HF.request('/clientes?'+query);if(rev!==state.clientRevision)return;state.clients=r.data;
        $('#client-results').innerHTML=r.data.map(c=>'<button type="button" class="client-option" data-client="'+c.id+'" aria-pressed="'+(state.client?.id===c.id)+'"><strong>'+esc(c.nombre)+'</strong><small>'+esc(c.empresa||'Cliente CRM')+'</small><small>'+esc(c.correo)+'</small></button>').join('')||'<p class="empty">No hay clientes activos con esa búsqueda. Registra uno desde Nuevo cliente CRM.</p>';
        pager('#client-pages',r,'clients');
    }
    function selectClient(c) {
        state.client=c;clearKey();
        $('#selected-client').hidden=false;$('#selected-client').innerHTML='<p class="eyebrow">CLIENTE SELECCIONADO</p>'+clientCard(c);
        $$('[data-client]').forEach(b=>b.setAttribute('aria-pressed',String(Number(b.dataset.client)===c.id)));
    }
    async function catalog(page=1) {
        if(!can('erp.write'))return;
        const rev=++state.catalogRevision;
        const query=new URLSearchParams(new FormData($('#product-search')));query.set('page',page);
        const r=await HF.request('/productos?'+query);if(rev!==state.catalogRevision)return;state.catalog=r.data;
        for(const p of r.data) {
            const line=state.cart.get(p.id);
            if(line){const cents=Math.round(p.precio_venta*100);if(line.price!==cents)clearKey();line.price=cents;line.stock=p.stock_actual;}
        }
        $('#catalog-rows').innerHTML=r.data.map(p=>'<tr><td><strong>'+esc(p.nombre)+'</strong><small>'+esc(p.categoria)+' · '+esc(p.estrategia_logistica)+'</small></td><td><span class="badge '+(p.stock_actual<=p.stock_minimo?'badge-pendiente':'')+'">'+p.stock_actual+'</span></td><td>'+money(p.precio_venta)+'</td><td><input type="number" data-quantity="'+p.id+'" min="1" max="'+Math.max(1,p.stock_actual)+'" step="1" value="1" aria-label="Cantidad de '+esc(p.nombre)+'" '+(p.stock_actual<1?'disabled':'')+'></td><td><button type="button" data-add="'+p.id+'" '+(p.stock_actual<1?'disabled':'')+'>＋ Agregar</button></td></tr>').join('')||'<tr><td colspan="5">No hay productos disponibles con esa búsqueda.</td></tr>';
        pager('#catalog-pages',r,'catalog');renderCart();
    }
    function itemTable(editable) {
        if(!state.cart.size)return '<p class="empty">Aún no has agregado productos.</p>';
        return '<div class="table-wrap"><table><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio unitario</th><th>Subtotal</th>'+(editable?'<th>Acción</th>':'')+'</tr></thead><tbody>'+[...state.cart.values()].map(p=>'<tr><td>'+esc(p.name)+'</td><td>'+(editable?'<input type="number" data-cart-quantity="'+p.id+'" value="'+p.quantity+'" min="1" max="'+p.stock+'" step="1" aria-label="Cantidad en orden de '+esc(p.name)+'">':p.quantity)+'</td><td>'+money(p.price/100)+'</td><td>'+money(p.price*p.quantity/100)+'</td>'+(editable?'<td><button type="button" data-remove="'+p.id+'">Quitar</button></td>':'')+'</tr>').join('')+'</tbody></table></div>';
    }
    function total() {return [...state.cart.values()].reduce((s,p)=>s+p.price*p.quantity,0)/100;}
    function renderCart() {
        if(!$('#cart-preview'))return;
        $('#cart-preview').innerHTML=itemTable(true)+'<div class="total-box"><span>Total MXN</span><strong>'+money(total())+'</strong></div>';
        $('#review-items').innerHTML=itemTable(false);$('#order-total').textContent=money(total());
    }
    function step(number) {
        if(number>1 && !state.client)throw Error('Selecciona un cliente activo.');
        if(number>2 && !state.cart.size)throw Error('Agrega al menos un producto.');
        state.step=number;$$('[data-step]').forEach(el=>el.hidden=Number(el.dataset.step)!==number);
        $$('[data-step-indicator]').forEach(el=>{if(Number(el.dataset.stepIndicator)===number)el.setAttribute('aria-current','step');else el.removeAttribute('aria-current');});
        if(number===3){$('#review-client').innerHTML=clientCard(state.client);renderCart();}
        $('#notice').hidden=true;
    }
    function resetWizard() {
        state.client=null;state.cart.clear();state.created=null;clearKey();
        if($('#selected-client')){$('#selected-client').hidden=true;$('#order-notes').value='';$('#process-status').textContent='';step(1);renderCart();}
    }
    async function submitOrder(button, process) {
        if(state.saving)return;
        if(!state.client || !state.cart.size)throw Error('Selecciona cliente y productos.');
        state.saving=true;button.textContent=process?'Procesando…':'Guardando…';
        $('#process-status').textContent=process?'Validando existencias y registrando inventario e historial…':'Guardando la orden pendiente…';
        state.key ||= HF.uuid();
        const body={cliente_id:state.client.id,request_key:state.key,notas:$('#order-notes').value,
            productos:[...state.cart.values()].map(p=>({producto_id:p.id,cantidad:p.quantity,precio_unitario_cents:p.price}))};
        try {
            const o=await HF.request(process?'/ordenes/procesar':'/ordenes','POST',body);state.created=o;
            $('#success-title').textContent=process?'Orden procesada con éxito.':'Orden pendiente registrada.';
            $('#success-summary').innerHTML='<h2>'+esc(o.folio)+'</h2><p>'+esc(o.cliente.nombre)+'<br>'+esc(date(o.fecha))+'</p><div class="total-box"><span>Total MXN</span><strong>'+money(o.total)+'</strong></div>';
            $('#process-steps').innerHTML=process?o.procesos.flatMap(p=>p.pasos).map(s=>'<p>✓ '+esc(s)+'</p>').join(''):'<p>✓ Cotización registrada. El inventario se descontará al procesar.</p>';
            $('#process-status').textContent='';await panel('success');
        } catch(e) {$('#process-status').textContent='La operación no se confirmó. Revisa el mensaje y conserva los datos para reintentar.';throw e;}
        finally {state.saving=false;}
    }
    async function detail(id) {
        const o=await HF.request('/ordenes/'+id);
        $('#order-title').textContent=o.folio+' · '+o.estado;
        $('#order-detail').innerHTML='<div class="grid-main"><article><p class="eyebrow">CLIENTE CRM</p>'+clientCard(o.cliente)+'</article><article><p class="eyebrow">OPERACIÓN</p><p>Responsable: '+esc(o.usuario.name)+'<br>Fecha: '+esc(date(o.fecha))+'<br>Procesada: '+esc(date(o.procesada_at))+'</p><strong>Total: '+money(o.total)+'</strong></article></div><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>'+o.productos.map(p=>'<tr><td>'+esc(p.nombre)+'</td><td>'+p.cantidad+'</td><td>'+money(p.precio_unitario)+'</td><td>'+money(p.subtotal)+'</td></tr>').join('')+'</tbody></table></div><p>'+esc(o.notas||'Sin observaciones.')+'</p><h3>Historial de la orden</h3>'+o.historial.map(h=>'<div class="list-row"><div><strong>'+esc(h.evento)+'</strong><small>'+esc(h.descripcion)+'</small><small>'+esc(h.usuario.name)+' · '+esc(date(h.fecha))+'</small></div></div>').join('')+'<h3>Movimientos de inventario SCM</h3>'+o.movimientos.map(m=>'<div class="list-row"><span>'+esc(m.referencia)+'</span><span>'+esc(m.tipo)+' · '+m.cantidad+' unidades<small>Stock: '+m.stock_anterior+' → '+m.stock_resultante+'</small></span></div>').join('')+(o.movimientos.length?'':'<p class="hint">La orden pendiente no ha modificado inventario.</p>');
        showDialog('#order-dialog');
    }
    async function reports(page=1) {
        state.reportPage=page;
        const [m,r]=await Promise.all([HF.request('/erp/metricas'),HF.request('/erp/reportes?page='+page)]);
        $('#report-kpis').innerHTML=kpis(m);state.reports=r.data;
        $('#report-rows').innerHTML=r.data.map(s=>'<tr><td>#'+s.id+'</td><td>'+esc(date(s.created_at))+'</td><td>'+esc(s.usuario.name)+'</td><td>'+money(s.resumen.ventas_totales)+'</td><td>'+s.resumen.ordenes_procesadas+'</td><td><button type="button" data-snapshot="'+s.id+'">Ver corte</button><a class="button" href="/erp/reportes/'+s.id+'/csv">CSV</a></td></tr>').join('')||'<tr><td colspan="6">Guarda un corte para conservar los indicadores de este momento.</td></tr>';
        pager('#report-pages',r,'reports');
    }
    async function resources() {
        state.resources=await HF.request('/erp/recursos');
        $('#resource-rows').innerHTML=state.resources.map(r=>'<tr><td><strong>'+esc(r.nombre)+'</strong><small>'+esc(r.descripcion||'')+'</small></td><td>'+esc(r.tipo)+'</td><td>'+esc(r.responsable?.name||'Sin asignar')+'</td><td><span class="badge '+(r.disponible?'':'badge-pendiente')+'">'+(r.disponible?'Disponible':'No disponible')+'</span></td><td>'+(can('erp.resources')?'<button type="button" data-resource="'+r.id+'">Editar</button>':'Consulta')+'</td></tr>').join('')||'<tr><td colspan="5">Registra los recursos internos de la empresa.</td></tr>';
    }
    async function resourceEditor(id) {
        const form=$('#resource-form');form.reset();form.elements.id.value=id||'';
        const owners=await HF.request('/erp/responsables');
        form.elements.responsable_id.innerHTML='<option value="">Sin asignar</option>'+owners.map(u=>'<option value="'+u.id+'">'+esc(u.name)+'</option>').join('');
        if(id){const r=state.resources.find(x=>x.id===id);for(const key of ['nombre','tipo','descripcion','responsable_id'])form.elements[key].value=r[key]??'';form.elements.disponible.checked=r.disponible;}
        showDialog('#resource-dialog');
    }
    async function maturity() {
        const m=await HF.request('/erp/estado');
        $('#erp-levels').innerHTML=m.niveles.map(n=>'<span class="'+(n===m.nivel?'active':'')+'">'+esc(n)+'</span>').join('');
        $('#level-name').textContent='Nivel actual: '+m.nivel;
        $('#level-description').textContent={Básico:'La empresa comienza a centralizar sus datos.',Integrado:'CRM, SCM y ERP comparten clientes, catálogo y operaciones.',Automatizado:'Las órdenes generan inventario e historial de forma automática.',Optimizado:'El equipo verifica procesos completos y conserva reportes para tomar decisiones.'}[m.nivel];
        $('#erp-progress').value=m.progreso;$('#progress-text').textContent=m.progreso+'% de evidencias operativas disponibles.';
        $('#evidence-list').innerHTML=Object.entries(evidenceLabels).map(([key,label])=>'<label class="check"><input type="checkbox" name="'+key+'" '+(m.checklist[key]?'checked ':'')+(!can('erp.admin')||!m.evidencias[key]?'disabled ':'')+'><span>'+label+'<small>'+(m.evidencias[key]?'Evidencia registrada en el sistema':'Pendiente de registrar una operación real')+'</small></span></label>').join('');
        if($('#maturity-form').elements.nivel)$('#maturity-form').elements.nivel.value=m.nivel;
    }
    async function users() {
        state.users=await HF.request('/usuarios');
        $('#user-rows').innerHTML=state.users.map(u=>'<tr><td>'+esc(u.name)+'</td><td>'+esc(u.email)+'</td><td>'+esc(roles[u.role]||u.role)+'</td><td><select data-role-for="'+u.id+'" aria-label="Rol de '+esc(u.name)+'" '+(u.id===Number(document.body.dataset.userId)?'disabled':'')+'>'+Object.entries(roles).map(([key,label])=>'<option value="'+key+'" '+(key===u.role?'selected':'')+'>'+label+'</option>').join('')+'</select></td><td><button type="button" data-role-user="'+u.id+'" '+(u.id===Number(document.body.dataset.userId)?'disabled':'')+'>Guardar rol</button></td></tr>').join('');
    }
    async function panel(id) {
        if(!document.getElementById(id)?.classList.contains('panel')) id='dashboard';
        $$('.panel').forEach(el=>el.hidden=el.id!==id);
        $$('[data-panel]').forEach(b=>{if(b.dataset.panel===id)b.setAttribute('aria-current','page');else b.removeAttribute('aria-current');});
        history.replaceState(null,'','#'+(id==='success'?'orders':id));
        if(id==='dashboard')await dashboard();
        if(id==='orders')await orders(1);
        if(id==='new'){step(state.step);await Promise.all([clients(),catalog()]);}
        if(id==='reports')await reports(1);
        if(id==='resources')await resources();
        if(id==='maturity')await maturity();
        if(id==='users' && can('users'))await users();
    }
    document.addEventListener('click', e=>{
        const b=e.target.closest('button');if(!b || b.disabled || (b.form&&b.type==='submit'))return;
        run(b,async()=>{
            if(state.saving && !['confirm-order','save-draft'].includes(b.id)) throw Error('La orden se está procesando. Espera la confirmación.');
            if(b.dataset.close){$('#'+b.dataset.close).close();return;}
            if(b.dataset.panel){await panel(b.dataset.panel);return;}
            if(b.dataset.page){const n=Number(b.dataset.number);if(b.dataset.page==='orders')await orders(n);if(b.dataset.page==='clients')await clients(n);if(b.dataset.page==='catalog')await catalog(n);if(b.dataset.page==='reports')await reports(n);return;}
            if(b.dataset.client){selectClient(state.clients.find(c=>c.id===Number(b.dataset.client)));return;}
            if(b.dataset.add){const p=state.catalog.find(p=>p.id===Number(b.dataset.add)),input=$('[data-quantity="'+p.id+'"]');const count=Number(input.value);if(!input.reportValidity()||!Number.isInteger(count)||count<1)throw Error('Escribe una cantidad válida.');const line=state.cart.get(p.id),quantity=(line?.quantity||0)+count;if(quantity>p.stock_actual)throw Error('La cantidad total supera las existencias disponibles.');state.cart.set(p.id,{id:p.id,name:p.nombre,price:Math.round(p.precio_venta*100),stock:p.stock_actual,quantity});clearKey();renderCart();return;}
            if(b.dataset.remove){state.cart.delete(Number(b.dataset.remove));clearKey();renderCart();return;}
            if(b.dataset.view){await detail(Number(b.dataset.view));return;}
            if(b.dataset.process){if(!confirm('¿Procesar esta orden y descontar inventario?'))return;await HF.request('/ordenes/procesar','POST',{orden_id:Number(b.dataset.process)});await orders(state.orderPage);notice('Orden procesada. Inventario e historial actualizados.');return;}
            if(b.dataset.state){if(!confirm(b.dataset.state==='cancelada'?'¿Cancelar la orden? Si fue procesada, se devolverán sus existencias.':'¿Confirmar que esta orden fue entregada?'))return;await HF.request('/ordenes/'+b.dataset.order+'/estado','PUT',{estado:b.dataset.state});await orders(state.orderPage);notice('Estado actualizado.');return;}
            if(b.dataset.resource){await resourceEditor(Number(b.dataset.resource));return;}
            if(b.dataset.roleUser){const id=Number(b.dataset.roleUser);await HF.request('/usuarios/rol','PUT',{usuario_id:id,role:$('[data-role-for="'+id+'"]').value});await users();notice('Rol actualizado. Los permisos se aplican a las siguientes solicitudes.');return;}
            if(b.dataset.snapshot){const s=state.reports.find(r=>r.id===Number(b.dataset.snapshot));$('#snapshot-title').textContent='Corte #'+s.id+' · '+date(s.created_at);$('#snapshot-detail').innerHTML='<div class="kpis">'+kpis(s.resumen)+'</div><p>Responsable: '+esc(s.usuario.name)+'</p><a class="button primary" href="/erp/reportes/'+s.id+'/csv">Descargar este corte CSV</a>';showDialog('#snapshot-dialog');return;}
            switch(b.id){
                case 'logout':await HF.request('/logout','POST',{});location.href='/';break;
                case 'refresh':await dashboard();notice('Indicadores actualizados.');break;
                case 'clear-order-filter':$('#order-filter').reset();await orders(1);break;
                case 'new-client':$('#client-form').reset();showDialog('#client-dialog');break;
                case 'client-next':step(2);break;
                case 'product-back':step(1);break;
                case 'product-next':step(3);break;
                case 'review-back':step(2);break;
                case 'confirm-order':await submitOrder(b,true);break;
                case 'save-draft':await submitOrder(b,false);break;
                case 'cancel-wizard':if(confirm('¿Descartar los datos de esta nueva orden?')){resetWizard();await panel('orders');}break;
                case 'another-order':resetWizard();await panel('new');break;
                case 'view-created':await detail(state.created.id);break;
                case 'generate-report':await HF.request('/erp/reportes','POST',{});await reports(1);notice('Corte empresarial guardado.');break;
                case 'new-resource':await resourceEditor();break;
                case 'new-user':$('#user-form').reset();showDialog('#user-dialog');break;
                case 'print-order':window.print();break;
            }
        });
    });
    document.addEventListener('change',e=>{
        if(e.target.matches('[data-cart-quantity]'))run(null,async()=>{const p=state.cart.get(Number(e.target.dataset.cartQuantity));const n=Number(e.target.value);if(!Number.isInteger(n)||n<1||n>p.stock){e.target.value=p.quantity;throw Error('La cantidad debe estar entre 1 y el stock disponible.');}p.quantity=n;clearKey();renderCart();});
        if(e.target.id==='order-notes')clearKey();
    });
    document.addEventListener('invalid',e=>notice(e.target.validationMessage,true),true);
    document.addEventListener('submit',e=>{
        const form=e.target;e.preventDefault();run(e.submitter,async()=>{
            if(form.id==='order-filter')await orders(1);
            else if(form.getAttribute('id')==='client-search')await clients(1);
            else if(form.getAttribute('id')==='product-search')await catalog(1);
            else if(form.getAttribute('id')==='client-form'){const c=await HF.request('/clientes','POST',Object.fromEntries(new FormData(form)));selectClient(c);$('#client-dialog').close();await clients();notice('Cliente guardado en CRM y seleccionado.');}
            else if(form.getAttribute('id')==='resource-form'){const d=Object.fromEntries(new FormData(form)),id=d.id;delete d.id;d.disponible=form.elements.disponible.checked;d.responsable_id=d.responsable_id?Number(d.responsable_id):null;await HF.request(id?'/erp/recursos/'+id:'/erp/recursos',id?'PUT':'POST',d);$('#resource-dialog').close();await resources();notice('Recurso guardado.');}
            else if(form.getAttribute('id')==='user-form'){await HF.request('/usuarios','POST',Object.fromEntries(new FormData(form)));$('#user-dialog').close();form.reset();await users();notice('Cuenta del equipo creada.');}
            else if(form.getAttribute('id')==='maturity-form'){const checklist={};Object.keys(evidenceLabels).forEach(k=>checklist[k]=form.elements[k].checked);await HF.request('/erp/estado','PUT',{nivel:form.elements.nivel.value,checklist});await maturity();notice('Nivel ERP actualizado.');}
        });
    });
    run(null,()=>panel(location.hash.slice(1)||'dashboard'));
})();
