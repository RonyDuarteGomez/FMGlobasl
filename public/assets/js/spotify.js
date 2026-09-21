function iniciarSpotify() {
 const root=document.getElementById('spotifyModule');if(!root||root.dataset.ready)return;root.dataset.ready='1';
 const $=id=>root.querySelector('#'+id), admin=root.dataset.admin==='1', form=$('spotifyForm'),fields=$('spotifyFields'),modalEl=$('spotifyModal'),modal=bootstrap.Modal.getOrCreateInstance(modalEl);
 let noticeTimer;
 let paymentMode=false,paymentRows=[],paymentPage=1,paymentSequence=0,selectedPayments=new Set();
 let rows=[],metadata={services:[],users:[]},page=1,sequence=0,busy=false,mode='',getInput=null,commandKey='',fieldCounter=0;
 const key=()=>Array.from(crypto.getRandomValues(new Uint8Array(16)),b=>b.toString(16).padStart(2,'0')).join('');
 const today=()=>{const d=new Date();return [d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');};
 const date=value=>value?value.slice(0,10).split('-').reverse().join('/'):'—';
 const stamp=value=>{if(!value)return '—';const [d,t]=value.split(' ');const [h,m]=(t||'00:00').split(':');return date(d)+' '+String(Number(h)%12||12).padStart(2,'0')+':'+m+' '+(Number(h)>=12?'PM':'AM');};
 const nextMonth=value=>{const [y,m,d]=value.split('-').map(Number),next=new Date(Date.UTC(y,m,1)),last=new Date(Date.UTC(y,m+1,0)).getUTCDate();return [next.getUTCFullYear(),String(next.getUTCMonth()+1).padStart(2,'0'),String(Math.min(d,last)).padStart(2,'0')].join('-');};
 function node(tag,text,cls){const e=document.createElement(tag);if(text!==undefined)e.textContent=text;if(cls)e.className=cls;return e;}
 function notify(text,error=false){clearTimeout(noticeTimer);noticeTimer=setTimeout(()=>{$('spotifyNotice').hidden=true;},12000);$('spotifyNotice').textContent=text;$('spotifyNotice').className='alert '+(error?'alert-danger':'alert-success');$('spotifyNotice').hidden=!text;}
 function fail(error){$('spotifyError').textContent=error.message;$('spotifyError').hidden=false;}
 async function request(action,input=null,query={}){
  const url=new URL('gestion/spotify.php',document.baseURI),options={credentials:'same-origin'};
  if(input){const body=new FormData();body.set('action',action);if(action==='import'){body.set('file',input.file);body.set('request_key',input.request_key);}else body.set('payload',JSON.stringify(input));Object.assign(options,{method:'POST',body,headers:{'X-CSRF-Token':fmCsrfToken()}});}else url.search=new URLSearchParams({action,...query});
  const response=await fetch(url,options);let data;try{data=await response.json();}catch(e){throw Error('No se pudo completar la solicitud.');}
  if(!response.ok||!data.ok)throw Error(data.message||'No se pudo completar la operación.');return data;
 }
 function selectOption(select,value,label){const option=node('option',label);option.value=value;select.append(option);}
 function fillSelect(select,items,first){const selected=select.value;select.replaceChildren();selectOption(select,'',first);items.forEach(i=>selectOption(select,i.id,i.name+(i.eligible===false?' · Sin acceso':'')));select.value=selected;}
 function field(parent,label,type='text',value='',required=false){
  const wrap=node('div',undefined,'mb-3'),id='spotifyField'+(++fieldCounter),title=node('label',undefined,'form-label');title.htmlFor=id;title.append(document.createTextNode(label));if(required)title.append(node('span',' *','text-danger'));
  const input=node(type==='select'?'select':'input');if(type!=='select')input.type=type;input.id=id;input.className=type==='select'?'form-select form-select-sm':'form-control form-control-sm';input.required=required;input.autocomplete=type==='password'?'new-password':'off';input.value=value??'';wrap.append(title,input);parent.append(wrap);return input;
 }
 function types(parent,value){const input=field(parent,'Tipo de servicio','select','',true);metadata.services.forEach(s=>selectOption(input,s.id,s.name));input.value=value||metadata.services[0]?.id||'';return input;}
 function advisors(parent,exclude=null){const input=field(parent,'Asesor','select','',true);selectOption(input,'','Seleccionar asesor');metadata.users.filter(u=>u.eligible&&String(u.id)!==String(exclude)).forEach(u=>selectOption(input,u.id,u.name));return input;}
 function setBusy(value){busy=value;$('spotifyBusy').hidden=!value;form.querySelectorAll('input,select,button').forEach(e=>{if(value){e.dataset.disabledBefore=e.disabled?'1':'0';e.disabled=true;}else if('disabledBefore' in e.dataset){e.disabled=e.dataset.disabledBefore==='1';delete e.dataset.disabledBefore;}});modalEl.querySelector('.btn-close').disabled=value;}
 modalEl.addEventListener('hide.bs.modal',event=>{if(busy)event.preventDefault();});
 modalEl.addEventListener('hidden.bs.modal',()=>{fields.replaceChildren();getInput=null;});
 form.addEventListener('input',()=>{if(!busy)commandKey=key();});
 function open(action,title){modalEl.querySelector('.modal-dialog').classList.toggle('modal-lg',action!=='import');modalEl.querySelector('.modal-dialog').classList.toggle('modal-dialog-scrollable',action!=='import');fields.classList.remove('spotify-compact-form');mode=action;commandKey=key();getInput=null;fields.replaceChildren();$('spotifyError').hidden=true;$('spotifyBusy').hidden=true;$('spotifyModalTitle').textContent=title;$('spotifySubmit').textContent=action==='renew'?'Renovar un mes':action==='release'?'Liberar':action==='obtain'?(admin?'Asignar cuenta':'Obtener cuenta'):action==='transfer'?'Reasignar':action==='rehabilitate'?'Habilitar':'Guardar';$('spotifyClose').textContent='Cerrar';setBusy(false);modal.show();}
 function badge(days){if(days===null)return node('span','—');return node('span',days<0?Math.abs(days)+' días vencido':days+' días','badge '+(days<0?'bg-danger':days<=3?'bg-warning text-dark':'bg-success'));}
 function draw(data){
  rows=data.rows;page=data.page;const tbody=$('spotifyTable').tBodies[0];tbody.querySelectorAll('[data-spotify-tip]').forEach(el=>bootstrap.Tooltip.getInstance(el)?.dispose());tbody.replaceChildren();
  const mainCounts=new Map(),shownMains=new Set();
  if(admin)rows.forEach(row=>mainCounts.set(row.main_id,(mainCounts.get(row.main_id)||0)+1));
  rows.forEach(row=>{const tr=tbody.insertRow(),cell=value=>{const td=tr.insertCell();if(value instanceof Node)td.append(value);else td.textContent=value??'—';return td;};
   if(admin&&!shownMains.has(row.main_id)){
    shownMains.add(row.main_id);const info=node('div',undefined,'spotify-main-summary');
    info.append(node('small','Correo principal','text-body-secondary'),node('div',row.main_email,'fw-semibold'));
    info.append(node('small','Correo de pago','text-body-secondary'),node('div',row.payment_email));
    const payment=node('div',undefined,'spotify-main-payment');payment.append(node('small','Próximo pago','text-body-secondary'),node('div',date(row.next_payment)),badge(row.payment_days));info.append(payment);
    const td=cell(info);td.rowSpan=mainCounts.get(row.main_id);td.className='spotify-main-cell spotify-payment-data';
   }
   cell(row.email);cell(row.password).className='spotify-password';cell(row.profile);
   cell(node('span',row.state==='fallen'?'Caído':row.assignment_id?'Asignado':'Libre','badge '+(row.state==='fallen'?'bg-danger':row.assignment_id?'bg-primary':'bg-success')));
   cell(row.advisor_name);const client=node('div',row.client_name||'—');if(row.phone)client.append(node('small',row.phone,'d-block text-body-secondary'));cell(client);
   cell(date(row.start_date));cell(date(row.last_renewed_at));cell(date(row.end_date));cell(badge(row.days));
   const actions=node('div',undefined,'gmail-actions spotify-actions');cell(actions);
   const icons={renew:'fa-rotate-right',release:'fa-unlock',fall:'fa-triangle-exclamation',transfer:'fa-right-left',rehabilitate:'fa-circle-check',assign:'fa-user-plus',edit:'fa-pen'};
   const button=(text,action,cls='outline-primary')=>{const b=node('button',undefined,'btn btn-sm btn-'+cls);b.type='button';b.title=text;b.setAttribute('aria-label',text);const icon=node('i',undefined,'fas '+icons[action]);icon.setAttribute('aria-hidden','true');b.append(icon);b.dataset.spotifyAction=action;b.dataset.profile=row.profile_id;const wrapper=node('span',undefined,'d-inline-flex');wrapper.dataset.spotifyTip=text;wrapper.append(b);actions.append(wrapper);return b;};
   if(row.assignment_id){button('Renovar','renew','primary');button('Liberar','release','outline-success');button('Caído','fall','outline-danger');if(admin)button('Reasignar','transfer');}
   else if(admin&&row.state==='fallen')button('Habilitar','rehabilitate','primary');
   else if(admin){button('Asignar','assign','primary');button('Caído','fall','outline-danger');}

  });
  tbody.querySelectorAll('[data-spotify-tip]').forEach(el=>{const b=el.querySelector('button');b.removeAttribute('title');new bootstrap.Tooltip(el,{title:el.dataset.spotifyTip,container:'body',trigger:'hover focus',placement:'top'});b.addEventListener('focus',()=>bootstrap.Tooltip.getInstance(el)?.show());b.addEventListener('blur',()=>bootstrap.Tooltip.getInstance(el)?.hide());b.addEventListener('click',()=>bootstrap.Tooltip.getInstance(el)?.hide());});
  if(!rows.length){const td=tbody.insertRow().insertCell();td.colSpan=$('spotifyTable').tHead.rows[0].cells.length;td.textContent='No hay cuentas que coincidan con los filtros.';}
  $('spotifyCount').textContent=data.total+' registros · Página '+data.page+' de '+data.pages;$('spotifyPages').replaceChildren();
  if(data.pages>1){const pages=[...new Set([1,...Array.from({length:5},(_,i)=>page+i-2).filter(n=>n>0&&n<=data.pages),data.pages])];pages.forEach(n=>{const b=node('button',String(n),'btn btn-sm btn-outline-primary pagination-button'+(n===page?' is-active':''));b.type='button';b.onclick=()=>{page=n;load();};$('spotifyPages').append(b);});}
 }
 async function load(){if(paymentMode)return loadPayments();const seq=++sequence;try{
  const [data,meta]=await Promise.all([request('list',null,{q:$('spotifySearch').value,state:$('spotifyState')?.value||'',advisor_id:$('spotifyAdvisor')?.value||'',expiry:$('spotifyExpiry').value,size:$('spotifySize').value,page}),request('metadata')]);
  if(seq!==sequence||!root.isConnected)return;metadata=meta;if(admin)fillSelect($('spotifyAdvisor'),meta.users,'Todos los asesores');
  $('spotifyAvailable').textContent=meta.services.map(s=>s.name+': '+s.available+' disponibles').join(' · ');
  if(admin){const alerts=[];if(meta.fallen)alerts.push(meta.fallen+' cuentas caídas. Revisa el filtro Caídas para habilitarlas.');if(meta.orphaned)alerts.push(meta.orphaned+' asignaciones en usuarios sin acceso. Revisa el filtro de asesores para reasignarlas o liberarlas.');$('spotifyAttention').hidden=!alerts.length;$('spotifyAttention').textContent=alerts.join(' ');}
  draw(data);
 }catch(e){notify(e.message,true);}}
 function mainForm(data={accounts:[]}){
  fields.classList.add('spotify-compact-form');
  const serviceId=data.service_id||metadata.services.find(s=>s.name.toLowerCase()==='spotify')?.id;
  const main=node('fieldset',undefined,'spotify-client-group');main.append(node('legend','Cuenta principal y pago'));const mainRow=node('div',undefined,'row g-2');main.append(mainRow);fields.append(main);
  const email=field(mainRow,'Correo principal','email',data.email||'',true),payment=field(mainRow,'Correo de pago','email',data.payment_email||'',true),next=field(mainRow,'Próximo pago al proveedor','date',data.next_payment||today(),true);
  [email,payment,next].forEach(input=>input.parentElement.classList.add('col-12','col-md-4'));
  const section=node('fieldset',undefined,'spotify-client-group');section.append(node('legend','Cuentas secundarias'));const headers=node('div',undefined,'spotify-secondary-labels');['Correo secundario','Contraseña','Perfil'].forEach(t=>headers.append(node('span',t)));section.append(headers);const container=node('div',undefined,'spotify-secondary-list'),add=node('button',undefined,'btn btn-sm btn-success');add.append(node('i',undefined,'fas fa-plus'));add.firstChild.setAttribute('aria-hidden','true');add.type='button';add.title='Agregar correo secundario';add.setAttribute('aria-label',add.title);const addRow=node('div',undefined,'spotify-secondary-add');addRow.append(add);section.append(container,addRow);fields.append(section);const groups=[],removed=[];
  const limits=()=>metadata.services.find(s=>String(s.id)===String(serviceId))||{max_accounts:5,max_profiles:1};
  const refresh=()=>{add.disabled=groups.length>=Number(limits().max_accounts);};
  function addAccount(account={profiles:[{name:''}]}){
   if(groups.length>=Number(limits().max_accounts)){fail(Error('Máximo '+limits().max_accounts+' correos secundarios.'));return;}
   const box=node('div',undefined,'spotify-secondary-row'),group={box,account,profiles:[]};const row=node('div',undefined,'row g-2 flex-grow-1');box.append(row);
   group.email=field(row,'Correo secundario','email',account.email||'',true);group.password=field(row,'Contraseña','password',account.password||'',true);
   group.email.parentElement.classList.add('col-12','col-md-5');group.password.parentElement.classList.add('col-12','col-md-4');
   (account.profiles||[{name:''}]).forEach(profile=>{const input=field(row,'Perfil (opcional)','text',profile.name||'');input.placeholder='Perfil 1';input.maxLength=80;input.parentElement.classList.add('col-12','col-md-3');group.profiles.push({profile,input});});
   const remove=node('button',undefined,'btn btn-sm btn-outline-danger spotify-remove-secondary');remove.append(node('i',undefined,'fas fa-trash'));remove.firstChild.setAttribute('aria-hidden','true');remove.type='button';remove.title='Quitar cuenta secundaria';remove.setAttribute('aria-label',remove.title);remove.onclick=()=>{if(Number(account.assigned)){fail(Error('No se puede quitar la cuenta: está asignada.'));return;}if(account.id)removed.push({id:account.id,revision:account.revision});groups.splice(groups.indexOf(group),1);box.remove();refresh();};if(!Number(account.assigned))box.append(remove);else{box.append(node('span',undefined,'spotify-remove-placeholder'));box.querySelectorAll('input').forEach(input=>input.disabled=true);box.title='Cuenta asignada';}
   groups.push(group);container.append(box);refresh();
  }
  add.onclick=()=>addAccount();(data.id?data.accounts:(data.accounts?.length?data.accounts:[{profiles:[{name:''}]}])).forEach(addAccount);
  getInput=()=>({removed_accounts:removed,id:data.id||0,revision:data.revision||0,service_id:serviceId,email:email.value,payment_email:payment.value,next_payment:next.value,accounts:groups.map(g=>({id:g.account.id||0,revision:g.account.revision||0,email:g.email.value,password:g.password.value,profiles:g.profiles.map(p=>({id:p.profile.id||0,name:p.input.value}))}))});
 }
 function obtainForm(row=null){
  fields.classList.add('spotify-compact-form');
  const serviceId=row?.service_id||metadata.services.find(s=>s.name.toLowerCase()==='spotify')?.id;
  if(row){const heading=node('div',undefined,'spotify-assignment-heading');heading.append(node('small','Asignación de cuenta','text-body-secondary'),node('div',row.email,'fw-semibold'));fields.append(heading);}
  const details=node('div',undefined,'row g-2');fields.append(details);
  const advisor=admin?advisors(details):null;if(advisor)advisor.parentElement.classList.add('col-12','col-md-6');
  const start=field(details,'Inicio del servicio','date',today(),true);start.parentElement.classList.add('col-12',admin?'col-md-6':'col-md-12');
  const client=node('fieldset',undefined,'spotify-client-group');client.append(node('legend','Datos del cliente'));fields.append(client);
  const phone=field(client,'Celular del cliente','tel','',true);phone.placeholder='+51987654321';phone.maxLength=40;
  const phoneGroup=node('div',undefined,'input-group input-group-sm');phone.parentElement.append(phoneGroup);phoneGroup.append(phone);
  const lookup=node('button','Buscar cliente','btn btn-outline-primary');lookup.type='button';phoneGroup.append(lookup);
  const name=field(client,'Nombre del cliente','text','',true);name.maxLength=150;
  const clientMessage=node('p','Busca el celular o registra un cliente nuevo.','form-text mb-0');clientMessage.setAttribute('aria-live','polite');client.append(clientMessage);
  const credentials=admin&&row?optionalCredentials(row,false):null;let lookupSequence=0;
  phone.oninput=()=>{lookupSequence++;name.readOnly=false;name.value='';clientMessage.textContent='Busca el celular o registra el nombre del cliente nuevo.';};
  lookup.onclick=async()=>{const seq=++lookupSequence;lookup.disabled=true;try{const data=await request('client',null,{phone:phone.value});if(seq!==lookupSequence)return;name.value=data.client?.name||'';name.readOnly=!!data.client;clientMessage.textContent=data.client?'Cliente encontrado.':'Cliente nuevo: completa su nombre.';}catch(e){fail(e);}finally{lookup.disabled=false;}};
  getInput=()=>({service_id:serviceId,advisor_id:advisor?.value||'',phone:phone.value,client_name:name.value,start_date:start.value,...(row?{profile_id:row.profile_id}:{}),...(credentials?credentials():{})});
 }
 function optionalCredentials(row,showEmail=true){
  if(showEmail)fields.append(node('p',row.email,'fw-semibold mb-2'));
  const group=node('div',undefined,'row g-2');fields.append(group);
  const profile=field(group,'Perfil','text',row.profile),password=field(group,'Contraseña','text',row.password,true);
  [profile,password].forEach(input=>input.parentElement.classList.add('col-12','col-md-6'));
  fields.append(node('small','Registra aquí los cambios realizados en el servicio.','text-body-secondary'));
  return ()=>({profile_id:row.profile_id,profile_name:profile.value,password:password.value,account_revision:row.account_revision});
 }
 function accountHeading(label,email){const heading=node('div',undefined,'spotify-assignment-heading');heading.append(node('small',label,'text-body-secondary'),node('div',email,'fw-semibold'));fields.append(heading);}
 function credentialFields(row,release=false){
  if(release){accountHeading('Liberar cuenta',row.email);const current=field(fields,'Contraseña actual','text',row.password);current.readOnly=true;}else fields.append(node('p',row.email,'fw-semibold'));fields.append(node('p',release?'Cambia la contraseña en el servicio y registra aquí la nueva antes de liberar.':'Estos datos ya están asignados. Puedes conservarlos o registrar un cambio realizado externamente.','small text-body-secondary'));
  const password=field(fields,release?'Nueva contraseña':'Contraseña','text',release?'':row.password,true),profile=field(fields,'Perfil (opcional)','text',row.profile||'');profile.placeholder='Perfil 1';
  getInput=()=>({assignment_id:row.assignment_id||row.id,revision:row.revision,account_revision:row.account_revision,password:password.value,profile_name:profile.value});
  if(!release)$('spotifyClose').textContent='Conservar datos';
 }
 root.addEventListener('click',async event=>{
  const button=event.target.closest('[data-spotify-action]');if(!button||busy)return;const action=button.dataset.spotifyAction,row=button.dataset.main?paymentRows.find(r=>String(r.main_id)===button.dataset.main):rows.find(r=>String(r.profile_id)===button.dataset.profile);
  if(action==='import'){
   open('import','Importar cuentas');$('spotifySubmit').textContent='Importar';
   const model=node('a','Descargar CSV modelo','btn btn-sm btn-outline-primary mb-3');model.href=new URL('gestion/spotify.php?action=template',document.baseURI);model.download='spotify-modelo.csv';fields.append(model);
   const file=field(fields,'Archivo CSV','file','',true);file.accept='.csv,text/csv';const fileWrap=file.parentElement;fileWrap.replaceWith(...fileWrap.childNodes);
   fields.append(node('p','Usa las columnas del modelo. UTF-8, máximo 2 MB y 500 filas. Se importan las filas válidas sin sobrescribir cuentas existentes.','form-text'));
   getInput=()=>({file:file.files[0]});return;
  }
  if(action==='new'){open('save','Agregar cuenta principal');mainForm();return;}
  if(action==='obtain'||action==='assign'){open('obtain',admin?'Asignar cuenta':'Obtener cuenta');obtainForm(row||null);return;}
  if(!row)return;
  if(action==='pay'){
   open('pay','Registrar pago al proveedor');$('spotifySubmit').textContent='Confirmar pago';
   fields.append(node('p',row.main_email,'fw-semibold mb-2'),node('p','Correo de pago: '+row.payment_email,'mb-2'),node('p','Fecha registrada: '+date(row.next_payment),'mb-2'),node('p','Próximo pago: '+date(nextMonth(row.next_payment)),'fw-semibold mb-0'));
   getInput=()=>({main_id:row.main_id,main_revision:row.main_revision});return;
  }
  if(action==='edit'){open('save','Editar cuenta principal');setBusy(true);try{const data=await request('main',null,{id:row.main_id});mainForm(data.main);}catch(e){fail(e);}finally{setBusy(false);}return;}
  open(action,{renew:'Renovar servicio',release:'Liberar cuenta',fall:'Marcar cuenta caída',rehabilitate:'Habilitar cuenta',transfer:'Reasignar cuenta'}[action]);
  if(action==='release'){credentialFields(row,true);return;}
  const input={assignment_id:row.assignment_id||null,revision:row.revision,account_id:row.account_id,account_revision:row.account_revision};
  accountHeading({fall:'Cuenta a marcar como caída',rehabilitate:'Habilitar cuenta',transfer:'Reasignar cuenta',renew:'Renovar servicio'}[action],row.email);
  if(action==='renew'){fields.append(node('p','Vencimiento actual: '+date(row.end_date)));fields.append(node('p','Nuevo vencimiento: '+date(nextMonth(row.end_date)),'fw-semibold'));getInput=()=>input;}
  if(action==='fall'){fields.append(node('p','Se cerrarán las asignaciones de este correo y quedará fuera de disponibles hasta que el administrador lo revise.'));const reason=field(fields,'Motivo (opcional)');reason.maxLength=500;getInput=()=>({...input,reason:reason.value});}
  if(action==='rehabilitate'){fields.append(node('p','Reportado por: '+(row.fallen_reporter_name||'Sin registro'),'mb-2'));fields.append(node('p',row.fallen_reason||'Sin motivo registrado.','text-body-secondary'));fields.append(node('p','Confirma que revisaste la cuenta. No se restaurarán las asignaciones anteriores. Puedes actualizar el perfil y la contraseña a continuación.'));const credentials=optionalCredentials(row,false);getInput=()=>({...input,...credentials()});}
  if(action==='transfer'){const current=field(fields,'Asesor actual','text',row.advisor_name||'Sin asesor');current.readOnly=true;const target=advisors(fields,row.advisor_id);target.parentElement.querySelector('label').firstChild.textContent='Nuevo asesor';fields.append(node('p','Se conserva el cliente, inicio y vencimiento del servicio.','small text-body-secondary'));const credentials=optionalCredentials(row,false);getInput=()=>({...input,advisor_id:target.value,...credentials()});}
 });
 form.addEventListener('submit',async event=>{
  event.preventDefault();if(busy||!getInput)return;const action=mode,input={...getInput(),request_key:commandKey};$('spotifyError').hidden=true;setBusy(true);
  try{
   const result=await request(action,input);notify(result.message);setBusy(false);
   if(action==='import'){
    const report=$('spotifyImportReport');report.hidden=false;report.replaceChildren(node('h3','Resultado de la carga','fs-6'),node('p',result.message));
    if(result.errors.length){const wrap=node('div',undefined,'table-responsive'),table=node('table',undefined,'tabla table table-sm table-hover align-middle');const head=table.createTHead().insertRow();['Registro CSV','Correo secundario','Motivo'].forEach(t=>head.append(node('th',t)));const body=table.createTBody();result.errors.forEach(error=>{const row=body.insertRow();[error.line,error.email,error.reason].forEach(value=>row.insertCell().textContent=value);});wrap.append(table);report.append(wrap);}
   }
   if(action==='obtain'&&result.available&&!admin){
    open('credentials','Cuenta asignada');setBusy(true);try{const data=await request('assigned',null,{id:result.assignment_id});credentialFields(data.assignment);}finally{setBusy(false);}await load();
   }else{modal.hide();await load();}
  }catch(e){fail(e);}finally{setBusy(false);}
 });
 function updateSelection(){
  $('spotifyBulkPay').disabled=!selectedPayments.size;$('spotifyBulkPay').textContent='Registrar pagos ('+selectedPayments.size+')';
  $('spotifyPaymentsAll').checked=!!paymentRows.length&&selectedPayments.size===paymentRows.length;
  $('spotifyPaymentsAll').indeterminate=selectedPayments.size>0&&selectedPayments.size<paymentRows.length;
 }
 async function loadPayments(){
  const seq=++paymentSequence;selectedPayments.clear();paymentRows=[];updateSelection();$('spotifyPaymentsAll').disabled=true;
  $('spotifyPaymentsTable').tBodies[0].replaceChildren();$('spotifyPaymentCount').textContent='Cargando…';$('spotifyPaymentPages').replaceChildren();
  try{
   const data=await request('payments',null,{q:$('spotifyPaymentSearch').value,state:$('spotifyPaymentState').value,size:$('spotifyPaymentSize').value,page:paymentPage});
   if(seq!==paymentSequence||!paymentMode||!root.isConnected)return;
   paymentRows=data.rows;paymentPage=data.page;const body=$('spotifyPaymentsTable').tBodies[0];
   paymentRows.forEach(row=>{const tr=body.insertRow(),cell=value=>{const td=tr.insertCell();if(value instanceof Node)td.append(value);else td.textContent=value;return td;};
    const check=node('input');check.type='checkbox';check.setAttribute('aria-label','Seleccionar '+row.main_email);check.dataset.paymentId=row.main_id;check.onchange=()=>{check.checked?selectedPayments.add(row.main_id):selectedPayments.delete(row.main_id);updateSelection();};cell(check);
    cell(row.main_email);cell(row.payment_email);cell(date(row.next_payment));cell(badge(row.payment_days));
    [row.summary.total,row.summary.assigned,row.summary.fallen,row.summary.free].forEach(value=>cell(value));
    const actions=node('div',undefined,'spotify-main-actions');[['Editar','edit','outline-primary'],['Pago','pay','outline-success']].forEach(([label,action,style])=>{const b=node('button',label,'btn btn-sm btn-'+style);b.type='button';b.dataset.spotifyAction=action;b.dataset.main=row.main_id;actions.append(b);});cell(actions);
   });
   if(!paymentRows.length){const td=body.insertRow().insertCell();td.colSpan=10;td.textContent='No hay cuentas que coincidan con los filtros.';}
   $('spotifyPaymentCount').textContent=data.total+' registros · Página '+data.page+' de '+data.pages;
   const pages=[...new Set([1,...Array.from({length:5},(_,i)=>paymentPage+i-2).filter(n=>n>0&&n<=data.pages),data.pages])];
   if(data.pages>1)pages.forEach(n=>{const b=node('button',String(n),'btn btn-sm btn-outline-primary'+(n===paymentPage?' active':''));b.type='button';b.onclick=()=>{paymentPage=n;loadPayments();};$('spotifyPaymentPages').append(b);});
   $('spotifyPaymentsAll').disabled=!paymentRows.length;updateSelection();
  }catch(e){if(seq===paymentSequence){$('spotifyPaymentCount').textContent='No se pudieron cargar los pagos.';notify(e.message,true);}}
 }
 if(admin){
  const switchMode=()=>{paymentMode=$('spotifyModePayments').checked;sequence++;paymentSequence++;selectedPayments.clear();$('spotifySalesPanel').hidden=paymentMode;$('spotifyPaymentsPanel').hidden=!paymentMode;$('spotifyImportReport').hidden=true;load();};
  $('spotifyModeSales').onchange=switchMode;$('spotifyModePayments').onchange=switchMode;

  let paymentTimer;$('spotifyPaymentSearch').oninput=()=>{selectedPayments.clear();updateSelection();$('spotifyPaymentsAll').disabled=true;$('spotifyPaymentsTable').tBodies[0].replaceChildren();paymentSequence++;clearTimeout(paymentTimer);paymentTimer=setTimeout(()=>{paymentPage=1;loadPayments();},300);};
  ['spotifyPaymentState','spotifyPaymentSize'].forEach(id=>$(id).onchange=()=>{paymentPage=1;loadPayments();});
  $('spotifyPaymentsAll').onchange=()=>{selectedPayments=new Set($('spotifyPaymentsAll').checked?paymentRows.map(r=>r.main_id):[]);$('spotifyPaymentsTable').querySelectorAll('[data-payment-id]').forEach(check=>check.checked=$('spotifyPaymentsAll').checked);updateSelection();};
  $('spotifyBulkPay').onclick=()=>{
   const chosen=paymentRows.filter(r=>selectedPayments.has(r.main_id));if(!chosen.length)return;
   open('pay_bulk','Confirmar '+chosen.length+' pagos');$('spotifySubmit').textContent='Confirmar pagos';
   const wrap=node('div',undefined,'table-responsive'),table=node('table',undefined,'table table-sm');const head=table.createTHead().insertRow();['Cuenta principal','Fecha registrada','Próximo pago'].forEach(t=>head.append(node('th',t)));const body=table.createTBody();
   chosen.forEach(r=>{const row=body.insertRow();[r.main_email,date(r.next_payment),date(nextMonth(r.next_payment))].forEach(v=>row.insertCell().textContent=v);});wrap.append(table);fields.append(wrap);
   getInput=()=>({items:chosen.map(r=>({main_id:r.main_id,main_revision:r.main_revision}))});
  };
 }
 let timer;$('spotifySearch').oninput=()=>{clearTimeout(timer);timer=setTimeout(()=>{page=1;load();},300);};['spotifyState','spotifyAdvisor','spotifyExpiry','spotifySize'].forEach(id=>{if($(id))$(id).onchange=()=>{page=1;load();};});load();
}
