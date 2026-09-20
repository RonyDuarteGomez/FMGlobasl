// El generador conserva https://apitoken-eeds.onrender.com/generate; ahora se consume desde el servidor.
function iniciarLink() {
 const root=document.getElementById('linkAccounts'); if(!root || root.dataset.ready) return; root.dataset.ready='1';
 const $=id=>root.querySelector('#'+id), form=$('linkAccountForm'), modalElement=$('linkAccountModal');
 const modal=bootstrap.Modal.getOrCreateInstance(modalElement);
 let rows=[], users=[], page=1, mode='', busy=false, preview=null, fixed=null, report=null, sequence=0, availabilitySequence=0;
 const notice=(text,error=false)=>{ $('linkNotice').textContent=text; $('linkNotice').className='alert '+(error?'alert-danger':'alert-success'); $('linkNotice').hidden=!text; };
 const fail=e=>{$('linkModalError').textContent=e.message;$('linkModalError').hidden=false;};
 function setBusy(value){busy=value;$('linkBusy').hidden=!value;form.querySelectorAll('button').forEach(b=>b.disabled=value);modalElement.querySelector('.btn-close').disabled=value;form.querySelectorAll('input,select,textarea').forEach(e=>e.disabled=value);if(!value && mode==='move')moveFields();}
 modalElement.addEventListener('hide.bs.modal',event=>{if(busy)event.preventDefault();});
 modalElement.addEventListener('hidden.bs.modal',()=>{form.reset();$('generatedLink').value='';$('linkOpen').removeAttribute('href');$('linkImportResult').replaceChildren();});
 async function request(action,data=null,query={}) {
  const url=new URL('soporte/link.php',document.baseURI), options={credentials:'same-origin'};
  if(data){options.method='POST'; options.headers={'X-CSRF-Token':fmCsrfToken()};data.set('action',action);options.body=data;}
  else {url.search=new URLSearchParams({action,...query});}
  const response=await fetch(url,options);let json;try{json=await response.json();}catch(_){throw Error('No se pudo completar la solicitud.');}
  if(!response.ok||!json.ok)throw Error(json.message||'No se pudo completar la operación.');return json;
 }
 const body=values=>{const data=new FormData();Object.entries(values).forEach(([k,v])=>data.set(k,v??''));return data;};
 function option(select,value,label,warning=false){const o=document.createElement('option');o.value=value;o.textContent=label;if(warning){o.style.color='var(--bs-danger)';o.style.fontWeight='700';}select.append(o);}
 function fillUsers(){
  const selected=$('linkOwner')?.value||'';
  if($('linkOwner')){$('linkOwner').replaceChildren();option($('linkOwner'),'','Todos los usuarios');option($('linkOwner'),'none','Sin asignar');users.forEach(u=>option($('linkOwner'),u.id,(u.display_name||u.usuario)+(u.eligible?'':' · Sin acceso'),!u.eligible));$('linkOwner').value=selected;}
 }
 async function load(){
  const seq=++sequence;
  try{const data=await request('list',null,{q:$('linkSearch').value,status:$('linkStatus').value,owner:$('linkOwner')?.value||'',size:$('linkSize').value,page});if(seq!==sequence||!root.isConnected)return;
   rows=data.rows;users=data.users;page=data.page;fillUsers();const tbody=$('linkTable').tBodies[0];tbody.replaceChildren();
   rows.forEach((row,index)=>{const tr=tbody.insertRow();const cell=text=>{const td=tr.insertCell();td.textContent=text??'';return td;};cell((page-1)*Number($('linkSize').value)+index+1);cell(row.credentials).className='link-credentials';const state=cell('');const badge=document.createElement('span');badge.className='badge gmail-token text-white '+(row.status==='active'?'bg-success':'bg-danger');badge.textContent=row.status==='active'?'Activo':Number(row.error_type)===2?'Error':'No Link';state.append(badge);const ownerCell=cell(row.display_name||row.usuario||'Sin asignar');if(row.assigned_user_id && row.owner_eligible===false)ownerCell.className='text-danger fw-bold';const date=cell(row.assigned_at);date.title='Último movimiento: '+row.moved_at+' · '+(row.moved_by||'—');const actions=cell('');const wrap=document.createElement('div');wrap.className='gmail-actions link-actions';actions.append(wrap);
    const button=(label,action,style='primary')=>{const b=document.createElement('button');b.type='button';b.className='btn btn-sm btn-'+style;b.textContent=label;b.dataset.linkAction=action;b.dataset.id=row.id;wrap.append(b);return b;};
    button('Generar','generate');
    if(root.dataset.admin==='1'){button('Error','error','outline-danger').disabled=row.status==='error'&&Number(row.error_type)===2;button('Editar','edit','outline-primary');button('Asignación','single','outline-primary');}
   });
   if(!rows.length){const td=tbody.insertRow().insertCell();td.colSpan=6;td.textContent='No hay cuentas que coincidan con los filtros.';}
   $('linkCount').textContent=data.total+' cuentas';$('linkPages').replaceChildren();
   const pages=[...new Set([1,...Array.from({length:5},(_,i)=>page+i-2).filter(n=>n>=1&&n<=data.pages),data.pages])];
   if(data.pages>1)pages.forEach(n=>{const b=document.createElement('button');b.className='btn btn-sm btn-outline-primary pagination-button'+(n===page?' is-active':'');b.textContent=n;b.type='button';b.onclick=()=>{page=n;load();};$('linkPages').append(b);});
   $('linkOrphanWarning').hidden=!data.summary.orphaned;$('linkOrphanText').textContent=data.summary.orphaned+' cuentas asignadas a usuarios sin acceso o inactivos. Libera o traslada esas cuentas.';
  }catch(e){notice(e.message,true);}
 }
 function open(panel,title){mode=panel;preview=null;fixed=null;report=null;form.reset();$('linkImportResult').replaceChildren();$('linkModalError').hidden=true;$('linkMovePreview').hidden=true;$('linkAvailability').textContent='';$('linkModalTitle').textContent=title;root.querySelectorAll('[data-link-panel]').forEach(p=>p.hidden=p.dataset.linkPanel!==panel);$('linkSubmit').hidden=panel==='result';$('linkSubmit').textContent=panel==='move'?'Aplicar':panel==='import'?'Importar':'Guardar';form.querySelectorAll('[required]').forEach(e=>e.required=false);if(panel==='edit')['linkCredentials','linkExternalId','linkSecure'].forEach(id=>$(id).required=true);if(panel==='import')$('linkCsv').required=true;setBusy(false);modal.show();}
 function moveFields(){const op=$('linkOperation').value;const source=String(fixed?.assigned_user_id||$('linkSource').value);[...$('linkTarget').options].forEach(o=>{o.disabled=op==='transfer'&&o.value===source&&!!source;});if(op==='transfer'&&$('linkTarget').value===source)$('linkTarget').value='';$('linkSourceGroup').hidden=op==='assign';$('linkTargetGroup').hidden=op==='release';$('linkSource').disabled=!!fixed||op==='assign';$('linkQuantity').disabled=!!fixed||$('linkAll').checked;$('linkAll').disabled=!!fixed;$('linkAllGroup').hidden=!!fixed;$('linkIncludeErrorGroup').hidden=!!fixed;}
 function moveData(){const data=new FormData(form);data.set('source',fixed?.assigned_user_id||$('linkSource').value);data.set('quantity',fixed?'1':$('linkAll').checked?'all':$('linkQuantity').value);if(fixed)data.set('account_id',fixed.id);return data;}
 async function availability(){
  if(!fixed&&!$('linkAll').checked&&Number($('linkQuantity').value)<1)$('linkQuantity').value=1;
  const seq=++availabilitySequence;preview=null;$('linkMovePreview').hidden=true;$('linkSubmit').textContent='Aplicar';moveFields();
  try{
   const input=moveData();input.set('availability','1');const data=await request('preview',input);
   if(seq!==availabilitySequence||mode!=='move')return;
   if(fixed){
    $('linkQuantity').value=1;
    $('linkAvailability').textContent=data.available?(data.status==='active'?'1 cuenta activa.':Number(data.error_type)===2?'1 cuenta con error.':'1 cuenta con error (No Link).'):'La cuenta no está disponible para este movimiento.';
   }else{
    if($('linkAll').checked)$('linkQuantity').value=data.available;
    else if(Number($('linkQuantity').value)<1)$('linkQuantity').value=1;
    const label=$('linkIncludeError').checked?'cuentas activas y con error':'cuentas activas';
    const operation=$('linkOperation').value;
    $('linkAvailability').textContent=(operation==='assign'?'':data.assigned+' cuentas asignadas; ')+data.available+' '+label+' para '+({assign:'asignar',release:'desasignar',transfer:'trasladar'}[operation])+'.';
   }
  }catch(e){if(seq===availabilitySequence)$('linkAvailability').textContent=e.message;}
 }

 function openMove(row=null){open('move',row?'Gestionar cuenta':'Gestionar asignaciones');fixed=row;$('linkSource').replaceChildren();$('linkTarget').replaceChildren();option($('linkSource'),'','Seleccionar usuario');option($('linkTarget'),'','Seleccionar usuario');users.filter(u=>u.accounts>0).forEach(u=>option($('linkSource'),u.id,(u.display_name||u.usuario)+' ('+u.accounts+')'+(u.eligible?'':' · Sin acceso'),!u.eligible));users.filter(u=>u.eligible).forEach(u=>option($('linkTarget'),u.id,(u.display_name||u.usuario)));
  [...$('linkOperation').options].forEach(o=>o.disabled=!!row&&(row.assigned_user_id?o.value==='assign':o.value!=='assign'));
  $('linkOperation').value=row?.assigned_user_id?'transfer':'assign';if(row){$('linkSource').value=row.assigned_user_id||'';$('linkIncludeError').checked=row.status==='error';}availability();
 }
 root.addEventListener('click',async event=>{const button=event.target.closest('[data-link-action]');if(!button||busy)return;const action=button.dataset.linkAction,row=rows.find(r=>r.id===Number(button.dataset.id));
  if(action==='new'){open('edit','Agregar cuenta');return;}if(action==='import'){open('import','Importar cuentas');return;}if(action==='bulk'||action==='single'){openMove(row);return;}
  if(action==='edit'){open('edit','Editar cuenta');setBusy(true);try{const data=await request('details',null,{id:row.id});for(const key of ['id','revision','credentials','external_id','secure'])form.elements[key].value=data[key];}catch(e){fail(e);}finally{setBusy(false);}return;}
  if(action==='error'){if(!confirm('¿Marcar con Error la cuenta '+row.credentials.split(':')[0]+'?'))return;button.disabled=true;try{await request('error',body({id:row.id,revision:row.revision}));await load();}catch(e){notice(e.message,true);button.disabled=false;}return;}
  if(action==='generate'){open('result','Generar enlace');$('generatedLink').value='';$('linkExpiry').textContent='';$('linkOpen').removeAttribute('href');$('linkCopy').disabled=true;setBusy(true);try{const data=await request('generate',body({id:row.id}));$('generatedLink').value=data.url;$('linkOpen').href=data.url;$('linkExpiry').textContent=data.expires?'Vence: '+data.expires:'';}catch(e){fail(e);}finally{setBusy(false);$('linkCopy').disabled=!$('generatedLink').value;await load();}}
 });
 form.addEventListener('change',()=>{if(mode==='move'&&!busy)availability();});
 form.addEventListener('input',event=>{if(mode==='move'){preview=null;$('linkMovePreview').hidden=true;$('linkSubmit').textContent='Aplicar';}});
 form.addEventListener('submit',async event=>{event.preventDefault();if(busy)return;$('linkModalError').hidden=true;const data=mode==='move'?moveData():new FormData(form);setBusy(true);
  try{
   if(mode==='move'&&!preview){preview=await request('preview',data);if(!preview.count)throw Error('No hay cuentas disponibles para esta operación.');if(preview.shortage){$('linkMovePreview').hidden=false;$('linkMovePreview').textContent='Solicitaste '+preview.requested+' cuentas, pero solo hay '+preview.available+'. ¿Deseas continuar con esas '+preview.count+'?';$('linkSubmit').textContent='Confirmar '+preview.count+' cuentas';return;}}
   if(mode==='move'){data.set('fingerprint',preview.fingerprint);const result=await request('move',data);notice(result.message);}
   if(mode==='edit'){const result=await request('save',data);notice(result.message);}
   if(mode==='import'){report=await request('import',data);$('linkImportResult').replaceChildren();const p=document.createElement('p');p.textContent=report.loaded+' cargadas · '+report.rejected+' rechazadas';$('linkImportResult').append(p);if(report.errors.length){const b=document.createElement('button');b.type='button';b.className='btn btn-sm btn-outline-primary';b.textContent='Descargar informe de rechazos';b.onclick=()=>{const csvCell=value=>{let text=String(value??'');if(/^[=+@\-\t\r]/.test(text))text="'"+text;return '"'+text.replaceAll('"','""')+'"';};const csv='Fila,Correo,ID,secure,correo_contrasena,Motivo,Datos originales\r\n'+report.errors.map(e=>[e.row,e.email,e.data?.[0],e.data?.[1],e.data?.[2],e.reason,JSON.stringify(e.data||[])].map(csvCell).join(',')).join('\r\n');const url=URL.createObjectURL(new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8'}));const a=document.createElement('a');a.href=url;a.download='rechazos-cuentas.csv';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);};$('linkImportResult').append(b);const list=document.createElement('ul');list.className='link-import-errors';report.errors.forEach(e=>{const li=document.createElement('li');li.textContent='Fila '+e.row+' · '+(e.email||'Sin correo')+': '+e.reason;list.append(li);});$('linkImportResult').append(list);} }
   setBusy(false);if(mode!=='import')modal.hide();await load();
  }catch(e){fail(e);preview=null;if(mode==='move')$('linkSubmit').textContent='Aplicar';}finally{setBusy(false);}
 });
 $('linkCopy').onclick=async()=>{try{await navigator.clipboard.writeText($('generatedLink').value);$('linkExpiry').textContent='Enlace copiado.';}catch(_){$('generatedLink').select();$('linkExpiry').textContent='Selecciona y copia el enlace.';}};
 let timer;$('linkSearch').oninput=()=>{clearTimeout(timer);timer=setTimeout(()=>{page=1;load();},300);};['linkStatus','linkOwner','linkSize'].forEach(id=>{if($(id))$(id).onchange=()=>{page=1;load();};});load();
}
