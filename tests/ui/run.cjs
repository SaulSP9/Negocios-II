/* Prueba de eventos DOM -> fetch HTTP real -> Laravel -> SQLite temporal.
   JSDOM no verifica la presentación visual ni sustituye Chrome. */
const fs=require('node:fs'),path=require('node:path'),os=require('node:os'),cp=require('node:child_process'),assert=require('node:assert/strict'),crypto=require('node:crypto');
const {JSDOM,VirtualConsole,CookieJar}=require('jsdom');
const net=require('node:net');
const root=path.resolve(__dirname,'../..'),tmp=fs.mkdtempSync(path.join(os.tmpdir(),'hf-u3-ui-')),db=path.join(tmp,'ui.sqlite');
fs.writeFileSync(db,'');
const env={...process.env,APP_ENV:'local',APP_DEBUG:'false',APP_KEY:'base64:'+crypto.randomBytes(32).toString('base64'),DB_CONNECTION:'sqlite',DB_DATABASE:db,DB_URL:'',SESSION_DRIVER:'database',CACHE_STORE:'array',QUEUE_CONNECTION:'sync',
    APP_CONFIG_CACHE:path.join(tmp,'config.php'),APP_ROUTES_CACHE:path.join(tmp,'routes.php'),HF_UI_TEST:'1'};
const php=process.env.HF_PHP||'php';
function command(args){const r=cp.spawnSync(php,args,{cwd:root,env,encoding:'utf8'});if(r.status!==0)throw Error(r.stdout+'\n'+r.stderr);return r.stdout;}
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
const checks=[],errors=[],sessions=[];
let server,base,fixtures;
function passed(label){checks.push(label);console.log('OK '+label);}
async function until(fn,label){for(let n=0;n<250;n++){if(fn())return;await sleep(20);}throw Error('Tiempo agotado: '+label);}
async function session(role){
    const jar=new CookieJar(),calls=[];let pending=0;
    async function fetcher(url,options={}){
        url=new URL(url,base).href;
        const headers=new Headers(options.headers||{}),cookies=jar.getCookieStringSync(url);if(cookies)headers.set('Cookie',cookies);
        pending++;let r;try{r=await fetch(url,{...options,headers,redirect:'manual'});}finally{pending--;}
        for(const cookie of r.headers.getSetCookie())jar.setCookieSync(cookie,url);
        calls.push({path:new URL(url).pathname,method:options.method||'GET',status:r.status});
        return r;
    }
    let token=(await(await fetcher('/sesion')).json()).csrf_token;
    const login=await fetcher('/auth/login',{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify({email:role+'@ui.example.test',password:'UiPassword123456!'})});
    assert.equal(login.status,200);token=(await login.json()).csrf_token;
    async function api(url,method='GET',body,csrf=true){
        const r=await fetcher(url,{method,headers:{Accept:'application/json','Content-Type':'application/json',...(csrf?{'X-CSRF-TOKEN':token}:{})},...(body===undefined?{}:{body:JSON.stringify(body)})});
        const text=await r.text();let json;try{json=JSON.parse(text);}catch{}
        return {status:r.status,json,text};
    }
    const s={role,api,calls,async page(url,script){
        const r=await fetcher(url);assert.equal(r.status,200,url);
        const vc=new VirtualConsole();vc.on('jsdomError',e=>{if(!/Not implemented: (navigation|window.scrollTo)/.test(e.message))errors.push(e.message);});
        const dom=new JSDOM(await r.text(),{url:new URL(url,base).href,runScripts:'outside-only',pretendToBeVisual:true,cookieJar:jar,virtualConsole:vc});
        const w=dom.window;w.fetch=fetcher;w.confirm=()=>true;w.alert=()=>{};w.scrollTo=()=>{};w.print=()=>s.printed=true;
        w.HTMLDialogElement.prototype.showModal=function(){this.open=true;};w.HTMLDialogElement.prototype.close=function(){this.open=false;};
        w.eval(fs.readFileSync(path.join(root,'public/js/http.js'),'utf8'));
        w.eval(fs.readFileSync(path.join(root,'public/js/'+script+'.js'),'utf8'));
        s.dom=dom;s.w=w;s.d=w.document;
        return s;
    },async close(){await until(()=>pending===0,'solicitudes terminadas');await sleep(50);s.dom?.window.close();}};
    sessions.push(s);return s;
}
async function click(s,selector){await until(()=>s.d.querySelector(selector)&&!s.d.querySelector(selector).disabled,'botón habilitado '+selector);s.d.querySelector(selector).click();}
function fill(s,selector,value){const e=s.d.querySelector(selector);assert(e,'Campo '+selector);e.value=value;return e;}
async function submit(s,selector){await until(()=>!s.d.querySelector(selector+' button[type="submit"],'+selector+' button.primary').disabled,'botón habilitado '+selector);await click(s,selector+' button[type="submit"],'+selector+' button.primary');}
async function erp(admin){
    await admin.page('/erp','erp');
    await until(()=>admin.d.querySelectorAll('#kpis .kpi').length===5,'dashboard ERP');
    await click(admin,'#refresh');await until(()=>admin.d.querySelector('#notice').textContent.includes('actualizados'),'actualizar ERP');
    await click(admin,'[data-panel="new"]');
    await until(()=>admin.d.querySelector('[data-client]'),'clientes ERP');
    await click(admin,'#new-client');
    fill(admin,'#client-form [name="nombre"]','UI Cliente nuevo');fill(admin,'#client-form [name="correo"]','nuevo@ui.example.test');fill(admin,'#client-form [name="telefono"]','4491234567');await submit(admin,'#client-form');
    await until(()=>!admin.d.querySelector('#client-dialog').open,'alta cliente CRM');
    await click(admin,'#new-client');
    fill(admin,'#client-form [name="nombre"]','UI Duplicado');fill(admin,'#client-form [name="correo"]','nuevo@ui.example.test');await submit(admin,'#client-form');
    await until(()=>admin.d.querySelector('#client-dialog #notice')?.textContent.includes('422'),'error visible en cliente');
    assert(admin.d.querySelector('#client-dialog').open);await click(admin,'[data-close="client-dialog"]');
    await click(admin,'#client-next');
    fill(admin,'#product-search [name="q"]','UI ');await submit(admin,'#product-search');
    await until(()=>admin.d.querySelector('[data-add="'+fixtures.products.PUSH+'"]'),'catálogo ERP');
    fill(admin,'[data-quantity="'+fixtures.products.PUSH+'"]','2');await click(admin,'[data-add="'+fixtures.products.PUSH+'"]');
    fill(admin,'[data-quantity="'+fixtures.products.PULL+'"]','2');await click(admin,'[data-add="'+fixtures.products.PULL+'"]');
    await click(admin,'[data-remove="'+fixtures.products.PULL+'"]');await click(admin,'[data-add="'+fixtures.products.PULL+'"]');
    await click(admin,'#product-next');assert(admin.d.querySelector('#order-total').textContent.includes('996'));
    await click(admin,'#review-back');await click(admin,'#product-back');await click(admin,'#client-next');await click(admin,'#product-next');
    const b=admin.d.querySelector('#confirm-order');b.click();b.click();
    await until(()=>!admin.d.querySelector('#success').hidden,'procesar orden');
    assert.equal(admin.calls.filter(c=>c.path==='/ordenes/procesar'&&c.method==='POST').length,1);
    const r=await admin.api('/ordenes');const first=r.json.data[0];assert.equal(first.total,996);
    assert.equal((await admin.api('/productos/'+fixtures.products.PUSH)).json.stock_actual,2);
    assert.equal((await admin.api('/productos/'+fixtures.products.PULL)).json.stock_actual,8);
    const scm=await admin.api('/pedidos');assert(scm.json.data.some(p=>p.producto_id===fixtures.products.PUSH&&p.automatico));
    await click(admin,'#view-created');await until(()=>admin.d.querySelector('#order-dialog').open,'ver orden');
    assert(admin.d.querySelector('#order-detail').textContent.includes('Movimientos de inventario'));
    await click(admin,'#print-order');assert(admin.printed);await click(admin,'[data-close="order-dialog"]');
    passed('ERP: cliente CRM, rechazo duplicado visible, selección, cantidades, quitar, atrás/siguiente, confirmar una vez, stock compartido, Push, detalle y botón de impresión');
    await click(admin,'#another-order');await until(()=>admin.d.querySelector('[data-client]'),'otra orden');
    await click(admin,'[data-client="'+fixtures.client+'"]');await click(admin,'#client-next');
    fill(admin,'#product-search [name="q"]','UI PULL');await submit(admin,'#product-search');await until(()=>admin.d.querySelector('[data-add="'+fixtures.products.PULL+'"]'),'producto Pull');
    fill(admin,'[data-quantity="'+fixtures.products.PULL+'"]','1');await click(admin,'[data-add="'+fixtures.products.PULL+'"]');await click(admin,'#product-next');await click(admin,'#save-draft');
    await until(()=>admin.d.querySelector('#success-title').textContent.includes('pendiente'),'guardar pendiente');
    assert.equal((await admin.api('/productos/'+fixtures.products.PULL)).json.stock_actual,8);
    await click(admin,'[data-panel="orders"]');await until(()=>admin.d.querySelector('[data-process]'),'listado pendientes');
    const second=Number(admin.d.querySelector('[data-process]').dataset.process);
    await click(admin,'[data-process="'+second+'"]');await until(()=>admin.d.querySelector('[data-state="completada"][data-order="'+second+'"]'),'procesar pendiente');
    await click(admin,'[data-state="completada"][data-order="'+second+'"]');await until(()=>admin.d.querySelector('#order-rows').textContent.includes('completada'),'entregar');
    await click(admin,'[data-state="cancelada"][data-order="'+first.id+'"]');await until(()=>admin.d.querySelector('#order-rows').textContent.includes('cancelada'),'cancelar');
    assert.equal((await admin.api('/productos/'+fixtures.products.PUSH)).json.stock_actual,4);
    assert.equal((await admin.api('/productos/'+fixtures.products.PULL)).json.stock_actual,9);
    fill(admin,'#order-filter [name="q"]','inexistente');await submit(admin,'#order-filter');await until(()=>admin.d.querySelector('#order-rows').textContent.includes('No hay'),'buscar vacío');
    await click(admin,'#clear-order-filter');await until(()=>admin.d.querySelectorAll('#order-rows tr').length===2,'limpiar filtro');
    passed('ERP: guardar pendiente, procesar, completar, cancelar con devolución, búsqueda y limpiar filtros');
    await click(admin,'[data-panel="reports"]');await until(()=>admin.d.querySelector('#report-kpis .kpi'),'reportes');
    await click(admin,'#generate-report');await until(()=>admin.d.querySelector('[data-snapshot]'),'corte guardado');
    await click(admin,'[data-snapshot]');await until(()=>admin.d.querySelector('#snapshot-dialog').open,'ver corte');
    const csv=admin.d.querySelector('#snapshot-detail a').getAttribute('href');assert.equal((await admin.api(csv)).status,200);await click(admin,'[data-close="snapshot-dialog"]');
    await click(admin,'[data-panel="maturity"]');await until(()=>admin.d.querySelector('#evidence-list input'),'evidencias');
    for(const box of admin.d.querySelectorAll('#evidence-list input')){assert(!box.disabled);box.checked=true;}
    fill(admin,'#maturity-form [name="nivel"]','Optimizado');await submit(admin,'#maturity-form');
    await until(()=>admin.d.querySelector('#level-name').textContent.includes('Optimizado'),'nivel ERP');
    await click(admin,'[data-panel="resources"]');await until(()=>admin.d.querySelector('#resource-rows tr'),'recursos');
    await click(admin,'#new-resource');await until(()=>admin.d.querySelector('#resource-dialog').open,'nuevo recurso');
    fill(admin,'#resource-form [name="nombre"]','UI Empaque');fill(admin,'#resource-form [name="responsable_id"]',String(fixtures.users.logistica));await submit(admin,'#resource-form');
    await until(()=>admin.d.querySelector('[data-resource]'),'recurso guardado');await click(admin,'[data-resource]');
    await until(()=>admin.d.querySelector('#resource-dialog').open,'editar recurso');admin.d.querySelector('#resource-form [name="disponible"]').checked=false;await submit(admin,'#resource-form');
    await until(()=>admin.d.querySelector('#resource-rows').textContent.includes('No disponible'),'recurso editado');
    await click(admin,'[data-panel="users"]');await until(()=>admin.d.querySelector('[data-role-user]'),'usuarios');
    assert(admin.d.querySelector('[data-role-user="'+fixtures.users.admin+'"]').disabled);
    await click(admin,'#new-user');fill(admin,'#user-form [name="name"]','UI Nueva cuenta');fill(admin,'#user-form [name="email"]','nueva.cuenta@ui.example.test');fill(admin,'#user-form [name="password"]','UiPassword123456!');await submit(admin,'#user-form');
    await until(()=>admin.d.querySelector('#user-rows').textContent.includes('nueva.cuenta@'),'cuenta creada');
    const created=(await admin.api('/usuarios')).json.find(u=>u.email==='nueva.cuenta@ui.example.test');
    fill(admin,'[data-role-for="'+created.id+'"]','logistica');await click(admin,'[data-role-user="'+created.id+'"]');
    await until(()=>admin.d.querySelector('#notice').textContent.includes('Rol actualizado'),'cambio de rol');
    assert.equal((await admin.api('/usuarios')).json.find(u=>u.id===created.id).role,'logistica');
    passed('ERP: reportes y CSV persistentes, madurez con evidencia, recursos alta/edición, usuarios y cambio de rol');
}
async function crm(admin){
    await admin.close();await admin.page('/crm','crm');await until(()=>admin.d.querySelector('#counters .card'),'CRM');
    await click(admin,'[data-tab="clients"]');await until(()=>admin.d.querySelector('#client-list tr'),'listado CRM');await click(admin,'#new-client');
    fill(admin,'#client-form [name="nombre"]','UI CRM temporal');fill(admin,'#client-form [name="correo"]','crm.temporal@ui.example.test');await submit(admin,'#client-form');
    await until(()=>!admin.d.querySelector('#client-dialog').open,'guardar cliente CRM');
    const c=(await admin.api('/clientes?q=crm.temporal')).json.data[0];
    await click(admin,'[data-edit="'+c.id+'"]');await until(()=>admin.d.querySelector('#client-dialog').open,'editar cliente');
    fill(admin,'#client-form [name="empresa"]','Empresa UI');await submit(admin,'#client-form');await until(()=>!admin.d.querySelector('#client-dialog').open,'editar guardado');
    await click(admin,'[data-history="'+c.id+'"]');await until(()=>admin.d.querySelector('#detail-stage'),'historial CRM');
    fill(admin,'#interaction-form [name="descripcion"]','Interacción desde eventos de la interfaz');await submit(admin,'#interaction-form');
    await until(()=>admin.d.querySelector('#history .list-item'),'interacción CRM');
    fill(admin,'#evaluation-form [name="observaciones"]','Evaluación UI');await submit(admin,'#evaluation-form');await until(()=>admin.d.querySelector('#evaluation-list .list-item'),'evaluación CRM');
    const stage=fill(admin,'#detail-stage','Frecuente');stage.dispatchEvent(new admin.w.Event('change',{bubbles:true}));
    await until(()=>admin.d.querySelector('#message').textContent.includes('Etapa actualizada'),'etapa CRM');
    await click(admin,'[data-tab="activity"]');await until(()=>admin.d.querySelector('#my-activity .list-item'),'mi actividad');
    await click(admin,'#back-clients');await until(()=>admin.d.querySelector('[data-delete="'+c.id+'"]'),'regresar clientes');
    await click(admin,'[data-delete="'+c.id+'"]');await until(()=>!admin.d.querySelector('[data-delete="'+c.id+'"]'),'eliminar cliente');
    await click(admin,'[data-tab="contacts"]');await until(()=>admin.d.querySelector('#contact-list').textContent.includes('Sin mensajes'),'contactos vacíos');
    await click(admin,'[data-tab="users"]');await until(()=>admin.d.querySelector('#user-list .list-item'),'equipo CRM');
    await click(admin,'[data-tab="dashboard"]');await click(admin,'#refresh-metrics');
    const rolesLink=admin.d.querySelector('a[href="/erp#users"]');assert(rolesLink);await admin.close();await admin.page(rolesLink.getAttribute('href'),'erp');await until(()=>admin.d.querySelector('#user-rows tr'),'enlace CRM a roles ERP');
    passed('CRM: alta, edición, historial, interacción, evaluación, etapa, Mi actividad, eliminación, contactos, equipo y actualizar');
}
async function scm(admin){
    await admin.close();await admin.page('/scm','scm');await until(()=>admin.d.querySelector('#counters .card'),'SCM');
    await click(admin,'[data-tab="suppliers"]');await click(admin,'#new-supplier');
    fill(admin,'#supplier-form [name="nombre"]','UI Proveedor nuevo');fill(admin,'#supplier-form [name="contacto"]','Contacto UI');fill(admin,'#supplier-form [name="correo"]','nuevo.proveedor@ui.example.test');fill(admin,'#supplier-form [name="telefono"]','4491234567');await submit(admin,'#supplier-form');
    await until(()=>!admin.d.querySelector('#supplier-dialog').open,'proveedor guardado');
    const supplier=(await admin.api('/proveedores')).json.find(p=>p.correo==='nuevo.proveedor@ui.example.test');
    await click(admin,'[data-edit-supplier="'+supplier.id+'"]');fill(admin,'#supplier-form [name="contacto"]','Contacto editado UI');await submit(admin,'#supplier-form');await until(()=>!admin.d.querySelector('#supplier-dialog').open,'proveedor editado');
    await click(admin,'#new-supplier');fill(admin,'#supplier-form [name="nombre"]','UI Duplicado');fill(admin,'#supplier-form [name="contacto"]','Contacto UI');fill(admin,'#supplier-form [name="correo"]','nuevo.proveedor@ui.example.test');fill(admin,'#supplier-form [name="telefono"]','4491234567');await submit(admin,'#supplier-form');
    await until(()=>admin.d.querySelector('#supplier-dialog .dialog-notice')?.textContent.includes('422'),'duplicado proveedor visible');await click(admin,'[data-close="supplier-dialog"]');
    await click(admin,'[data-tab="catalog"]');await click(admin,'#new-product');
    fill(admin,'#product-form [name="nombre"]','UI Producto temporal');fill(admin,'#product-form [name="descripcion"]','Producto para validar todos los controles');fill(admin,'#product-form [name="imagen"]','https://example.test/ui.jpg');fill(admin,'#product-form [name="stock_actual"]','5');fill(admin,'#product-form [name="stock_minimo"]','2');fill(admin,'#product-form [name="proveedor_id"]',String(supplier.id));await submit(admin,'#product-form');
    await until(()=>!admin.d.querySelector('#product-dialog').open,'producto guardado');
    const p=(await admin.api('/productos?q=UI%20Producto%20temporal')).json.data[0];
    await click(admin,'[data-edit-product="'+p.id+'"]');await until(()=>admin.d.querySelector('#product-dialog').open,'editar producto');assert(admin.d.querySelector('#product-form [name="stock_actual"]').disabled);
    fill(admin,'#product-form [name="precio_venta"]','199.90');fill(admin,'#product-form [name="proveedor_id"]','');await submit(admin,'#product-form');await until(()=>!admin.d.querySelector('#product-dialog').open,'producto editado');
    await click(admin,'[data-strategy="'+p.id+'"]');await until(()=>admin.d.querySelector('#notice').textContent.includes('422'),'Push exige proveedor');
    await click(admin,'[data-edit-product="'+p.id+'"]');await until(()=>admin.d.querySelector('#product-dialog').open,'asociar proveedor');fill(admin,'#product-form [name="proveedor_id"]',String(supplier.id));await submit(admin,'#product-form');await until(()=>!admin.d.querySelector('#product-dialog').open,'asociación guardada');
    await click(admin,'[data-strategy="'+p.id+'"]');await until(()=>admin.d.querySelector('[data-strategy="'+p.id+'"]').dataset.value==='PULL','Push configurado');
    await click(admin,'[data-strategy="'+p.id+'"]');await until(()=>admin.d.querySelector('[data-strategy="'+p.id+'"]').dataset.value==='PUSH','Pull configurado');
    await click(admin,'[data-history="'+p.id+'"]');await until(()=>admin.d.querySelector('#history-dialog').open,'historial SCM');
    fill(admin,'#movement-form [name="cantidad"]','1');fill(admin,'#movement-form [name="referencia"]','UI conteo');await submit(admin,'#movement-form');await until(()=>admin.d.querySelector('#history-stock').textContent.includes('6'),'entrada SCM');
    fill(admin,'#movement-form [name="tipo"]','salida');fill(admin,'#movement-form [name="cantidad"]','6');await submit(admin,'#movement-form');await until(()=>admin.d.querySelector('#history-stock').textContent.includes('0'),'salida SCM');
    await click(admin,'[data-close="history-dialog"]');await click(admin,'[data-edit-product="'+p.id+'"]');await until(()=>admin.d.querySelector('#product-dialog').open,'desasociar proveedor');fill(admin,'#product-form [name="proveedor_id"]','');await submit(admin,'#product-form');await until(()=>!admin.d.querySelector('#product-dialog').open,'desasociación');
    await click(admin,'[data-delete-product="'+p.id+'"]');await until(()=>!admin.d.querySelector('[data-delete-product="'+p.id+'"]'),'archivar producto');
    fill(admin,'#product-filter [name="archivados"]','1');await submit(admin,'#product-filter');await until(()=>admin.d.querySelector('[data-restore-product="'+p.id+'"]'),'producto archivado visible');await click(admin,'[data-restore-product="'+p.id+'"]');await until(()=>!admin.d.querySelector('[data-restore-product="'+p.id+'"]'),'restaurar producto');fill(admin,'#product-filter [name="archivados"]','0');await submit(admin,'#product-filter');await until(()=>admin.d.querySelector('[data-delete-product="'+p.id+'"]'),'producto restaurado visible');await click(admin,'[data-delete-product="'+p.id+'"]');await until(()=>!admin.d.querySelector('[data-delete-product="'+p.id+'"]'),'volver a archivar');
    await click(admin,'[data-tab="suppliers"]');await click(admin,'[data-delete-supplier="'+supplier.id+'"]');await until(()=>!admin.d.querySelector('[data-delete-supplier="'+supplier.id+'"]'),'eliminar proveedor');
    await click(admin,'[data-tab="orders"]');await click(admin,'#new-order');await until(()=>admin.d.querySelector('#order-dialog').open,'pedido SCM');
    fill(admin,'#order-search','UI PULL');await click(admin,'#order-search-button');await until(()=>admin.d.querySelector('#order-form [name="producto_id"]').value===String(fixtures.products.PULL),'buscar producto pedido');
    fill(admin,'#order-form [name="cantidad"]','2');await submit(admin,'#order-form');await until(()=>!admin.d.querySelector('#order-dialog').open,'pedido creado');
    const order=(await admin.api('/pedidos')).json.data.find(p=>p.producto_id===fixtures.products.PULL&&p.estado==='pendiente');
    await click(admin,'[data-fulfill="'+order.id+'"]');await until(()=>!admin.d.querySelector('[data-fulfill="'+order.id+'"]'),'surtir SCM');
    await click(admin,'[data-tab="maturity"]');fill(admin,'#maturity-form [name="nivel_scm"]','Optimizado');for(const box of admin.d.querySelectorAll('#maturity-form input[type="checkbox"]'))box.checked=true;await submit(admin,'#maturity-form');
    await until(()=>admin.d.querySelector('#level-indicator').textContent==='Optimizado','madurez SCM');
    await click(admin,'#refresh');await until(()=>admin.d.querySelector('#notice').textContent.includes('actualizados'),'actualizar SCM');
    assert.equal((await admin.api('/scm/reportes/csv')).status,200);
    passed('SCM: proveedores alta/editar/duplicado/eliminar, productos alta/editar/archivar, Push/Pull, movimientos, búsqueda/pedido/surtir, madurez, actualizar y CSV');
}
async function storefront(admin){
    await admin.close();await admin.page('/','storefront');
    admin.w.eval(fs.readFileSync(path.join(root,'node_modules/alpinejs/dist/cdn.js'),'utf8'));
    await until(()=>admin.w.Alpine?.$data(admin.d.body).ready,'Alpine tienda');
    const app=admin.w.Alpine.$data(admin.d.body);
    const button=expression=>[...admin.d.querySelectorAll('button')].find(b=>b.getAttribute('@click')===expression||b.getAttribute('@click')?.startsWith(expression+';'));
    async function action(expression){await until(()=>button(expression)&&!button(expression).disabled,'botón Alpine '+expression);button(expression).click();await sleep(20);}
    async function form(expression){const f=[...admin.d.querySelectorAll('form')].find(f=>f.getAttribute('@submit.prevent')===expression);assert(f,expression);await sleep(25);const b=f.querySelector('button[type="submit"]')||f.querySelector('button:not([type="button"])');assert(b);b.click();}
    await action('handleLogout()');await until(()=>!app.isLoggedIn,'logout tienda');
    app.navigateTo('registro');app.registroForm={name:'UI Cliente tienda',email:'tienda@ui.example.test',password:'UiPassword123456!'};
    await form('handleRegistro()');await until(()=>app.isLoggedIn&&!app.isSaving,'registro público');
    assert.equal(app.user.role,'cliente');assert(!app.can('erp.read'));
    app.navigateTo('catalogo');await sleep(30);const product=app.allProducts.find(p=>p.stock>4);
    app.viewProductDetail(product);await action('addToCart(selectedProduct)');assert.equal(app.cartTotalItems,1);
    await action('increaseQty(index)');assert.equal(app.cartTotalItems,2);await action('decreaseQty(index)');assert.equal(app.cartTotalItems,1);
    app.discountCode='HF10';await form('applyDiscount()');assert(app.discountApplied);
    await action('handleCheckout()');assert.equal(app.checkoutStep,1);
    app.checkoutForm={address:'Calle UI 123',city:'Aguascalientes',zip:'20000'};await form('checkoutStep = 2');await until(()=>app.checkoutStep===2,'dirección checkout');
    await action('checkoutStep = 3');await action('processFinalPayment()');
    await until(()=>app.orderSuccessOpen&&!app.isCheckingOut,'checkout persistente');
    assert.equal(app.cart.length,0);assert.equal(app.userOrders.length,1);
    const order=app.userOrders[0];
    app.openInvoice(order);
    const {jsPDF}=require(path.join(root,'node_modules/jspdf'));const {applyPlugin}=require(path.join(root,'node_modules/jspdf-autotable'));applyPlugin(jsPDF);
    const originalSave=jsPDF.API.save;jsPDF.API.save=function(){fs.writeFileSync(path.join(tmp,'ticket.pdf'),Buffer.from(this.output('arraybuffer')));};
    admin.w.jspdf={jsPDF};app.downloadInvoice(order);jsPDF.API.save=originalSave;
    assert(fs.readFileSync(path.join(tmp,'ticket.pdf')).subarray(0,4).equals(Buffer.from('%PDF')));
    app.orderSuccessOpen=false;app.invoiceModalOpen=false;
    app.navigateTo('publicar');app.publishForm={name:'UI Publicación',price:'250',description:'Publicación de prueba con descripción completa',image:'https://example.test/publicacion.jpg'};
    await form('handlePublishSubmit()');await until(()=>app.userProducts.length===1&&!app.isSaving,'publicar');
    app.openEditModal(app.userProducts[0],0);app.editForm.description='Descripción editada desde la interfaz';await form('saveEdit()');
    await until(()=>!app.editModalOpen,'editar publicación');
    await action('deleteUserProduct(index)');await until(()=>app.userProducts.length===0,'eliminar publicación');
    app.navigateTo('comunidad');app.newComment='Reseña desde la interfaz de prueba';await form('addComment()');await until(()=>app.comments.some(c=>c.text==='Reseña desde la interfaz de prueba'),'reseña');
    app.navigateTo('subasta');app.auction.newOffer=Number(app.auction.product.currentBid)+100;await form('handleOfferSubmit()');await until(()=>app.auction.offers.length>0,'oferta subasta');
    app.navigateTo('contacto');app.contactForm={name:'Contacto UI',email:'contacto@ui.example.test',message:'Mensaje real para el equipo CRM'};await form('handleContactSubmit()');await until(()=>app.contactForm.message==='','mensaje contacto');
    app.newsletterEmail='newsletter@ui.example.test';await form('subscribeNewsletter()');await until(()=>app.newsletterEmail==='','suscripción');
    await action('handleLogout()');await until(()=>!app.isLoggedIn,'logout final');
    app.navigateTo('login');app.loginForm={email:'tienda@ui.example.test',password:'UiPassword123456!'};await form('handleLogin()');await until(()=>app.isLoggedIn&&!app.isLoggingIn,'login tienda');
    assert.equal(app.userOrders.length,1);
    passed('Tienda: Alpine real, registro/login/logout, carrito y cantidades, cupón, checkout, persistencia, PDF real, publicaciones alta/editar/eliminar, reseñas, subasta, contacto y suscripción');
}
(async()=>{
    try{
        command(['artisan','migrate','--seed','--force','--no-ansi']);
        fixtures=JSON.parse(command(['tests/ui/fixtures.php']));
        const port=await new Promise((resolve,reject)=>{const probe=net.createServer();probe.once('error',reject);probe.listen(Number(process.env.HF_UI_PORT||0),'127.0.0.1',()=>{const chosen=probe.address().port;probe.close(()=>resolve(chosen));});});base='http://127.0.0.1:'+port;
        server=cp.spawn(php,['-S','127.0.0.1:'+port,'-t',path.join(root,'public'),path.join(root,'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],{cwd:path.join(root,'public'),env,stdio:'ignore'});
        let ready=false;for(let n=0;n<100;n++){try{const r=await fetch(base+'/up');if(r.ok){ready=true;break;}}catch{}await sleep(30);}assert(ready,'Servidor de pruebas disponible');
        const admin=await session('admin');
        assert.equal((await admin.api('/proveedores','POST',{},false)).status,419);passed('HTTP real: sesión, cookies, login /auth/login y protección CSRF 419');
        await erp(admin);await crm(admin);await scm(admin);await storefront(admin);
        for(const role of ['ventas','logistica']){
            const s=await session(role);await s.page('/erp','erp');await until(()=>s.d.querySelector('#kpis .kpi'),'panel por rol');
            assert(!s.d.querySelector('#users'));
            if(role==='ventas'){assert(s.d.querySelector('#confirm-order'));assert(!s.d.querySelector('#resource-form'));assert.equal((await s.api('/inventario/movimiento','POST',{})).status,403);}
            else{assert(!s.d.querySelector('#confirm-order'));assert(s.d.querySelector('#resource-form'));assert.equal((await s.api('/ordenes/procesar','POST',{})).status,403);assert.equal((await s.api('/crm')).status,403);}
            passed('Rol '+role+': menú dinámico y bloqueos reales del backend');
        }
        assert.deepEqual(errors,[]);
        passed('Sin excepciones de JavaScript no controladas en los recorridos');
        console.log(JSON.stringify({success:true,groups:checks.length,checks},null,2));
    }catch(e){console.error(e.stack);process.exitCode=1;}
    finally{for(const s of sessions)await s.close();if(server)server.kill();fs.rmSync(tmp,{recursive:true,force:true});}
})();
