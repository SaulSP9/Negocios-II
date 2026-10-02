(() => {
    const $ = selector => document.querySelector(selector);
    const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const date = value => value ? new Date(value).toLocaleString('es-MX') : 'Sin contacto';
    const state = {user:null, editing:null, client:null, clientPage:1, activityPage:1, contactsPage:1, sequence:0};
    let alertTimer;
    function alert(message, error=false) {
        const box=$('#message'); box.textContent=message; box.hidden=false; box.className=error?'error':'';
        clearTimeout(alertTimer); alertTimer=setTimeout(()=>box.hidden=true,8000);
    }
    async function run(action, button) {
        if (button?.disabled) return;
        if(button) button.disabled=true;
        try { await action(); } catch (e) { alert(e.message,true); } finally { if(button) button.disabled=false; }
    }
    function bind(selector, event, action) {
        $(selector)?.addEventListener(event,e=>{e.preventDefault(); run(()=>action(e),e.submitter || (e.currentTarget.tagName==='BUTTON'?e.currentTarget:null));});
    }
    function data(form) { return Object.fromEntries(new FormData(form)); }
    function pager(selector, response, action) {
        const root=$(selector); root.replaceChildren();
        for(const [label,page] of [['Anterior',response.current_page-1],['Siguiente',response.current_page+1]]) {
            const button=document.createElement('button'); button.textContent=label;
            button.disabled=page<1 || page>response.last_page; button.onclick=()=>run(()=>action(page),button); root.append(button);
        }
        const info=document.createElement('span'); info.textContent=`Página ${response.current_page} de ${response.last_page} · ${response.total} registros`; root.append(info);
    }
    async function tab(id) {
        document.querySelectorAll('.tab').forEach(s=>s.hidden=s.id!==id);
        document.querySelectorAll('[data-tab]').forEach(b=>b.classList.toggle('selected',b.dataset.tab===id));
        if(id==='dashboard') await metrics();
        if(id==='clients') await clients(1);
        if(id==='activity') await activity(1);
        if(id==='contacts') await contacts(1);
        if(id==='users') await users();
    }
    async function metrics() {
        const m=await HF.request('/metricas');
        $('#counters').innerHTML=[['Total de clientes',m.total],['Activos',m.activos],['Inactivos',m.inactivos],['Interacciones',m.interacciones]].map(([label,value])=>`<div class="card"><span>${label}</span><strong>${Number(value)}</strong></div>`).join('');
        const max=Math.max(1,...m.por_etapa.map(s=>s.total));
        $('#chart').innerHTML=m.por_etapa.map(s=>`<div class="bar-row"><span>${escape(s.etapa)}</span><div class="track"><div class="bar" style="width:${s.total/max*100}%"></div></div><strong>${Number(s.total)}</strong></div>`).join('');
        $('#risks').innerHTML=m.clientes_en_riesgo.length?m.clientes_en_riesgo.map(c=>`<div class="list-item"><button data-history="${c.id}">${escape(c.nombre)}</button><p class="muted">Última interacción: ${escape(date(c.interacciones_max_fecha))}</p></div>`).join(''):'<p class="muted">Sin clientes en riesgo.</p>';
        $('#interaction-counts').innerHTML=m.interacciones_por_cliente.map(c=>`<div class="list-item">${escape(c.nombre)} <strong>· ${Number(c.total)}</strong></div>`).join('')||'<p class="muted">Registra tu primer cliente.</p>';
    }
    async function clients(page=1) {
        state.clientPage=page; const sequence=++state.sequence;
        const filters=data($('#filters')); const query=new URLSearchParams(Object.entries(filters).filter(([,v])=>v)); query.set('page',page);
        const result=await HF.request('/clientes?'+query); if(sequence!==state.sequence) return;
        $('#client-list').innerHTML=result.data.map(c=>`<tr><td><strong>${escape(c.nombre)}</strong><br><span class="muted">Alta: ${escape(date(c.fecha_registro))}</span></td>
            <td>${escape(c.correo)}<br>${escape(c.telefono || 'Sin teléfono')}</td><td>${escape(c.empresa || '—')}</td>
            <td><span class="badge">${escape(c.estado)}</span><span class="badge stage-${escape(c.etapa_crm)}">${escape(c.etapa_crm)}</span></td>
            <td>${Number(c.interacciones_count)}</td><td><div class="row-actions"><button data-history="${c.id}">Historial</button><button data-edit="${c.id}">Editar</button>${state.user.role==='admin'?`<button class="danger" data-delete="${c.id}">Eliminar</button>`:''}</div></td></tr>`).join('')||'<tr><td colspan="6">No hay clientes con estos filtros.</td></tr>';
        pager('#client-pages',result,clients);
    }
    async function edit(id=null) {
        state.editing=id; const form=$('#client-form'); form.reset(); $('#client-error').textContent='';
        $('#client-form-title').textContent=id?'Editar cliente':'Nuevo cliente';
        if(id) { const c=await HF.request('/clientes/'+id); for(const key of ['nombre','correo','telefono','empresa','estado','etapa_crm']) form.elements[key].value=c[key]??''; }
        $('#client-dialog').showModal();
    }
    async function detail(id) {
        state.client=id; await tab('detail');
        const [client,history]=await Promise.all([HF.request('/clientes/'+id),HF.request(`/clientes/${id}/interacciones`)]);
        $('#detail-title').textContent=client.nombre;
        $('#detail-info').innerHTML=`<div>${escape(client.correo)}<br><small>${escape(client.telefono||'Sin teléfono')} · ${escape(client.empresa||'Sin empresa')}</small></div><span class="badge">${escape(client.estado)}</span>
            <label>Etapa CRM<select id="detail-stage">${['Prospecto','Activo','Frecuente','Inactivo'].map(s=>`<option ${s===client.etapa_crm?'selected':''}>${s}</option>`).join('')}</select></label>`;
        $('#detail-stage').onchange=e=>run(async()=>{await HF.request(`/clientes/${id}/etapa`,'PUT',{etapa_crm:e.target.value});alert('Etapa actualizada.');});
        $('#history').innerHTML=history.map(i=>`<div class="list-item"><strong>${escape(i.tipo)} · ${escape(date(i.fecha))}</strong><p>${escape(i.descripcion)}</p><small>Responsable: ${escape(i.usuario.name)}</small></div>`).join('')||'<p class="muted">Todavía no hay interacciones.</p>';
        $('#evaluation-list').innerHTML=[...client.evaluaciones].reverse().map(e=>`<div class="list-item"><strong>${Number(e.puntuacion)}/5</strong> · ${escape(e.usuario.name)}<p>${escape(e.observaciones||'')}</p><small>${escape(date(e.created_at))}</small></div>`).join('');
        const now=new Date(); const local=new Date(now.getTime()-now.getTimezoneOffset()*60000).toISOString().slice(0,16);
        $('#interaction-form').elements.fecha.value=local; $('#interaction-form').elements.fecha.max=local;
    }
    async function activity(page=1) {
        state.activityPage=page; const result=await HF.request('/mi-actividad?page='+page);
        $('#my-activity').innerHTML=result.data.map(i=>`<div class="list-item"><button data-history="${i.cliente_id}">${escape(i.cliente.nombre)}</button> · ${escape(i.tipo)}<p>${escape(i.descripcion)}</p><small>${escape(date(i.fecha))}</small></div>`).join('')||'<p class="muted">Aún no registraste interacciones.</p>';
        pager('#activity-pages',result,activity);
    }
    async function contacts(page=1) {
        state.contactsPage=page; const result=await HF.request('/contactos?page='+page);
        $('#contact-list').innerHTML=result.data.map(c=>`<div class="list-item"><strong>${escape(c.name)}</strong> · ${escape(c.email)}<p>${escape(c.message)}</p><small>${escape(date(c.created_at))}</small> ${c.handled?'<span class="badge">Registrado en CRM</span>':`<button data-contact="${c.id}">Registrar como interacción</button>`}</div>`).join('')||'<p class="muted">Sin mensajes.</p>';
        pager('#contact-pages',result,contacts);
    }
    async function users() {
        const list=await HF.request('/usuarios'); $('#user-list').innerHTML=list.map(u=>`<div class="list-item"><strong>${escape(u.name)}</strong> <span class="badge">${escape(u.role)}</span><p>${escape(u.email)}</p></div>`).join('');
    }
    document.addEventListener('click',e=>{
        const button=e.target.closest('button'); if(!button) return;
        if(button.dataset.tab) run(()=>tab(button.dataset.tab),button);
        if(button.dataset.history) run(()=>detail(Number(button.dataset.history)),button);
        if(button.dataset.edit) run(()=>edit(Number(button.dataset.edit)),button);
        if(button.dataset.delete && confirm('¿Eliminar el cliente y su historial? Esta acción no se puede deshacer.')) run(async()=>{await HF.request('/clientes/'+button.dataset.delete,'DELETE');await clients(state.clientPage);alert('Cliente eliminado.');},button);
        if(button.dataset.contact) run(async()=>{await HF.request(`/contactos/${button.dataset.contact}/registrar`,'POST',{});await contacts(state.contactsPage);alert('Cliente e interacción registrados.');},button);
    });
    bind('#filters','submit',()=>clients(1)); bind('#new-client','click',()=>edit());
    bind('#close-client','click',()=>$('#client-dialog').close()); bind('#back-clients','click',()=>tab('clients'));
    bind('#refresh-metrics','click',metrics);
    bind('#logout','click',async()=>{await HF.request('/logout','POST',{});location.href='/';});
    bind('#client-form','submit',async e=>{
        try { await HF.request(state.editing?'/clientes/'+state.editing:'/clientes',state.editing?'PUT':'POST',data(e.target));
            $('#client-dialog').close(); await clients(state.clientPage); alert('Cliente guardado.');
        } catch(error) { $('#client-error').textContent=error.message; }
    });
    bind('#interaction-form','submit',async e=>{const body=data(e.target);body.cliente_id=state.client;body.fecha=new Date(body.fecha).toISOString();await HF.request('/interacciones','POST',body);e.target.reset();await detail(state.client);alert('Interacción guardada.');});
    bind('#evaluation-form','submit',async e=>{await HF.request(`/clientes/${state.client}/evaluaciones`,'POST',data(e.target));e.target.reset();await detail(state.client);alert('Evaluación guardada.');});
    bind('#user-form','submit',async e=>{await HF.request('/usuarios','POST',data(e.target));e.target.reset();await users();alert('Cuenta creada.');});
    run(async()=>{const session=await HF.request('/sesion');state.user=session.user;if(!state.user){location.href='/login';return;}await metrics();});
})();
