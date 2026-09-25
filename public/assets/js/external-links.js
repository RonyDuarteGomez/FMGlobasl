function iniciarExterno(){
 const root=document.getElementById('externalModule');if(!root||root.dataset.ready)return;root.dataset.ready='1';const $=id=>root.querySelector('#'+id),node=fmModuleNode,mode=root.dataset.mode;
 const path={generate:'servicios/generador.php',rules:'mantenimiento/restricciones-generador.php',report:'reportes/links-externos.php'}[mode];let busy=false,sequence=0,timer,noticeTimer;
 const key=()=>Array.from(crypto.getRandomValues(new Uint8Array(16)),b=>b.toString(16).padStart(2,'0')).join('');
 function notice(message,error=false){clearTimeout(noticeTimer);$('externalNotice').className='alert '+(error?'alert-danger':'alert-success');$('externalNotice').textContent=message;$('externalNotice').hidden=false;noticeTimer=setTimeout(()=>{$('externalNotice').hidden=true;},12000);}
 async function request(action,input=null,query={}){const url=new URL(path,document.baseURI),options={credentials:'same-origin'};if(input){const body=new FormData();body.set('action',action);body.set('payload',JSON.stringify(input));Object.assign(options,{method:'POST',body,headers:{'X-CSRF-Token':fmCsrfToken()}});}else url.search=new URLSearchParams({action,...query});const response=await fetch(url,options);const data=await response.json();if(!response.ok||!data.ok)throw Error(data.message||'No se pudo completar la operación.');return data;}
 function option(select,value,label){const el=node('option',label);el.value=value;select.append(el);}
 if(mode==='generate'){
  let requestKey=key(),browserRegistration=false;
  function clearResult(){ $('externalResult').hidden=true;$('externalGeneratedLink').value='';$('externalCopy').disabled=true;const link=$('externalOpen');link.removeAttribute('href');link.classList.add('disabled');link.setAttribute('aria-disabled','true');link.tabIndex=-1; }
  $('externalClear').onclick=()=>{if(busy)return;$('externalGenerateForm').reset();clearResult();requestKey=key();$('externalNotice').hidden=true;$('externalId').focus();};
  $('externalCopy').onclick=async()=>{const input=$('externalGeneratedLink');if(!input.value)return;try{await navigator.clipboard.writeText(input.value);notice('Link copiado.');}catch(_){input.focus();input.select();notice('Selecciona y copia el link.',true);}};
  function clockLabel(value){const [hour,minute]=value.split(':').map(Number);return String(hour%12||12).padStart(2,'0')+':'+String(minute).padStart(2,'0')+' '+(hour%24<12?'AM':'PM')+(hour===24?' (día siguiente)':'');}
  function timeRemaining(seconds){const minutes=Math.ceil(seconds/60);return Math.floor(minutes/60)+' h '+String(minutes%60).padStart(2,'0')+' min';}
  async function status(){try{
   const data=await request('status',{});if(!root.isConnected)return;
   browserRegistration=!!data.browser_registration_available;
   const indicators=data.indicators,box=$('externalStatus'),browserButton=$('externalRequestBrowser');
   root.append(browserButton);box.className='external-availability';box.replaceChildren();
   const state=$('externalServiceState');state.hidden=false;state.className='badge text-white '+(data.allowed?'bg-success':'bg-danger');state.textContent=data.allowed?'Servicio habilitado':'Servicio inhabilitado';
   const grid=node('div',undefined,'external-restrictions');
   function block(title,configuration,item,state){
    const section=node('section',undefined,'external-restriction');
    section.append(node('h3',title),node('div',configuration,'external-restriction-config'),node('span',state,'badge bg-'+item.level+(item.level==='warning'?' text-dark':'')));
    grid.append(section);return section;
   }
   if(indicators){const {attempts,schedule,browser}=indicators;
    if(attempts.remaining!==null){const section=block('Intentos diarios','Límite: '+data.limit+' intentos',attempts,attempts.remaining===0?'Cupo agotado':'Quedan '+attempts.remaining+' intentos');section.append(node('div','Utilizados hoy: '+data.used,'small'),node('div','Se reinicia a las 12:00 AM · Lima','external-restriction-note'));}
    if(schedule.restricted){
     const today=schedule.today;
     const hours=today?.mode==='all'?'Hoy: todo el día':today?.mode==='hours'?today.slots.map(slot=>clockLabel(slot.start)+' – '+clockLabel(slot.end)).join(' · '):'Hoy: sin horario habilitado';
     const section=block('Horario permitido · Lima',hours,schedule,schedule.level==='danger'?'Fuera del horario permitido':schedule.seconds===null?'Disponible todo el día':'Quedan '+timeRemaining(schedule.seconds));
     if(schedule.level==='danger'&&data.next){const [day,time]=data.next.split(' ');section.append(node('div','Próximo inicio: '+day.split('-').reverse().join('/')+' '+clockLabel(time),'external-restriction-note'));}
    }
    if(browser.required){
     const approved=browser.level==='success',pending=data.browser_state==='pending'&&!browserRegistration;
     const section=block('Restricción por navegador','Acceso desde un solo navegador',browser,approved?'Autorizado':pending?'Pendiente de autorización':browserRegistration?'Sin registrar':'No autorizado');
     section.append(node('div',approved?'Solo puedes utilizar el servicio desde este navegador.':browserRegistration?'Registra este navegador para comenzar.':pending?'El administrador debe revisar tu solicitud.':data.browser_linked?'Tu usuario tiene otro navegador vinculado.':data.browser_state==='approved'?'La sesión cambió. Vuelve a abrir el módulo.':'La vinculación anterior fue revocada.','external-restriction-note'),browserButton);
    }
   }
   box.hidden=!grid.childElementCount;if(grid.childElementCount)box.append(grid);
   $('externalGenerateForm').hidden=!data.allowed;
   $('externalGenerate').disabled=busy||!data.allowed;
   browserButton.hidden=!data.browser_required||data.browser_state==='approved';
   browserButton.disabled=!browserRegistration&&data.browser_state==='pending';
   browserButton.textContent=browserRegistration?'Registrar este navegador':data.browser_state==='pending'?'Solicitud pendiente de autorización':data.browser_linked?'Solicitar cambio de navegador':'Solicitar autorización de navegador';
  }catch(e){$('externalGenerateForm').hidden=true;$('externalGenerate').disabled=true;const state=$('externalServiceState');state.hidden=false;state.className='badge text-white bg-danger';state.textContent='Servicio inhabilitado';notice(e.message,true);}}
  $('externalRequestBrowser').onclick=async()=>{$('externalRequestBrowser').disabled=true;try{await request(browserRegistration?'register_browser':'request_browser',{});await status();}catch(e){$('externalRequestBrowser').disabled=false;notice(e.message,true);}};
  $('externalGenerateForm').addEventListener('input',()=>{if(!busy)requestKey=key();});
  $('externalGenerateForm').onsubmit=async e=>{e.preventDefault();if(busy||$('externalGenerateForm').hidden||$('externalGenerate').disabled)return;busy=true;$('externalGenerate').disabled=true;$('externalGenerate').textContent='Generando…';$('externalClear').disabled=true;clearResult();try{const data=await request('generate',{id:$('externalId').value,secure:$('externalSecure').value,request_key:requestKey});requestKey=key();notice(data.message,!data.generated);if(data.generated){$('externalGeneratedLink').value=data.url;$('externalResult').hidden=false;$('externalCopy').disabled=false;const link=$('externalOpen');link.href=data.url;link.classList.remove('disabled');link.setAttribute('aria-disabled','false');link.removeAttribute('tabindex');}$('externalId').value='';$('externalSecure').value='';}catch(error){notice(error.message,true);}finally{busy=false;$('externalClear').disabled=false;$('externalGenerate').textContent='Generar link';await status();}};
  status();return;
 }
 if(mode==='rules'){
  function pendingBrowsers(users=[]){$('externalPendingWarning').hidden=!users.length;$('externalPendingNames').textContent=users.length?users.length+(users.length===1?' usuario esperando autorización':' usuarios esperando autorización')+' ('+users.map(user=>user.display_name).join(', ')+')':'';}
  let metadata,revision=0,loadedTarget=null;const days=['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];let weekReaders=[];
  let weekState={},editingDay=null,hourInputs=[];
  const copyDay=day=>({mode:day.mode,slots:day.slots.map(slot=>({...slot}))});
  const hoursModal=bootstrap.Modal.getOrCreateInstance($('externalHoursModal'));
  const pendingHours=()=>{$('externalEffective').textContent='Horarios modificados. Pulsa Guardar cambios para aplicarlos.';};
  function drawWeek(week){weekState={};days.forEach((_,i)=>weekState[i+1]=copyDay(week?.[i+1]||{mode:'all',slots:[]}));renderWeek();}
  function renderWeek(displayWeek=weekState,readOnly=false){
   $('externalWeek').replaceChildren();weekReaders=[];
   days.forEach((name,index)=>{
    const day=index+1,data=displayWeek[day]||{mode:'all',slots:[]},row=node('div',undefined,'external-week-day'),title=node('strong',name),choices=node('div',undefined,'btn-group external-day-choices');
    choices.setAttribute('role','group');choices.setAttribute('aria-label',name);
    ['all','off','hours'].forEach((value,i)=>{const button=node('button',['Todo el día','Cerrado','Por horas'][i],'btn btn-sm btn-outline-secondary');button.type='button';button.dataset.dayMode=value;button.classList.toggle('active',data.mode===value);button.setAttribute('aria-pressed',String(data.mode===value));button.onclick=()=>{if(value==='hours')openHours(day);else{data.mode=value;renderWeek();pendingHours();}};choices.append(button);});
    const summary=node('div',undefined,'external-hour-summary');
    if(data.mode==='hours'){data.slots.forEach(slot=>summary.append(node('span',slot.start+' – '+slot.end,'badge bg-light text-dark border')));const edit=node('button','Editar horarios','btn btn-sm btn-outline-primary');edit.type='button';edit.onclick=()=>openHours(day);summary.append(edit);}
    else summary.append(node('span',data.mode==='all'?'Disponible todo el día':'Sin atención','text-body-secondary small'));
    const copy=node('button','Copiar a todos','btn btn-sm btn-outline-secondary');copy.type='button';copy.setAttribute('aria-label','Copiar '+name+' a todos los días');copy.onclick=()=>{days.forEach((_,i)=>weekState[i+1]=copyDay(data));renderWeek();pendingHours();};
    row.append(title,choices,summary,copy);row.querySelectorAll('button').forEach(button=>button.disabled=readOnly);$('externalWeek').append(row);weekReaders.push(()=>copyDay(weekState[day]));
   });
  }
  function addHour(slot={start:'09:00',end:'18:00'}){
   if(hourInputs.length>=6)return;
   const row=node('div',undefined,'external-time-slot d-flex gap-2 mb-2'),start=node('input'),end=node('input'),remove=node('button','Quitar','btn btn-sm btn-outline-danger');
   start.type='time';end.type='text';end.placeholder='HH:MM';end.pattern='(?:[01][0-9]|2[0-3]):[0-5][0-9]|24:00';start.required=end.required=true;start.className=end.className='form-control form-control-sm';start.value=slot.start;end.value=slot.end;
   const from=node('label','Desde','external-time-field'),to=node('label','Hasta','external-time-field');from.append(start);to.append(end);remove.type='button';
   const pair={start,end};hourInputs.push(pair);remove.onclick=()=>{hourInputs=hourInputs.filter(item=>item!==pair);row.remove();$('externalHoursAdd').disabled=false;};row.append(from,node('span','→','external-time-arrow'),to,remove);$('externalHoursRows').append(row);$('externalHoursAdd').disabled=hourInputs.length>=6;
  }
  function openHours(day){editingDay=day;$('externalHoursTitle').textContent='Horarios · '+days[day-1];$('externalHoursRows').replaceChildren();hourInputs=[];$('externalHoursError').hidden=true;$('externalHoursCopy').checked=false;$('externalHoursAdd').disabled=false;const slots=weekState[day].slots; (slots.length?slots:[{start:'09:00',end:'18:00'}]).forEach(addHour);hoursModal.show();}
  $('externalHoursAdd').onclick=()=>addHour();
  $('externalHoursForm').onsubmit=event=>{
   event.preventDefault();const fail=message=>{$('externalHoursError').textContent=message;$('externalHoursError').hidden=false;};
   if(!hourInputs.length)return fail('Agrega al menos un horario.');
   const slots=hourInputs.map(({start,end})=>({start:start.value,end:end.value})).sort((a,b)=>a.start.localeCompare(b.start));let previous='';
   for(const slot of slots){if(!/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(slot.start)||!/^(?:(?:[01]\d|2[0-3]):[0-5]\d|24:00)$/.test(slot.end))return fail('Ingresa horas válidas en formato HH:MM.');if(slot.start>=slot.end)return fail('Hasta debe ser posterior a Desde.');if(previous&&slot.start<previous)return fail('Los horarios no deben superponerse.');previous=slot.end;}
   const data={mode:'hours',slots};if($('externalHoursCopy').checked)days.forEach((_,i)=>weekState[i+1]=copyDay(data));else weekState[editingDay]=copyDay(data);
   renderWeek();pendingHours();hoursModal.hide();
  };
  function modes(){
   const user=$('externalTargetType').value==='user';$('externalRestoreProfile').hidden=!user;
   [['Schedule','schedule'],['Limit','limit'],['Browser','browser']].forEach(([name,code])=>{
    const select=$('external'+name+'Mode');select.querySelector('[value="inherit"]').hidden=!user;if(!user&&select.value==='inherit')select.value='off';
    const inherited=user&&select.value==='inherit',configured=inherited?(metadata?.inherited?.[code]??false):select.value==='on';
    $('external'+name+'Inheritance').hidden=!user;$('external'+name+'Choices').hidden=false;
    root.querySelectorAll('[data-rule="'+name+'"]').forEach(button=>{const choice=button.dataset.ruleChoice,selected=choice===select.value;button.disabled=false;button.classList.toggle('active',selected);button.setAttribute('aria-pressed',String(selected));});
    const active=!!configured,badge=$('external'+name+'Status');badge.textContent=active?'Activo':'Inactivo';badge.className='badge text-white '+(active?'bg-success':'bg-secondary');$('external'+name+'Source').textContent=inherited?'Por perfil':user?'Por usuario':'';
    const detail=$('external'+name+'Detail');detail.replaceChildren();
    if(!active)detail.textContent={schedule:'Disponible sin límite de horario.',limit:'Sin límite de intentos diarios.',browser:'Puede utilizar el servicio desde cualquier navegador.'}[code];
    if(name==='Browser')$('externalBrowsers').hidden=!user||!active;
    if(name==='Schedule'){
     $('externalWeek').hidden=!active;
     renderWeek(inherited&&active?configured:weekState,inherited||!active);
    }
    if(name==='Limit'){
     $('externalLimitFields').hidden=!active;$('externalLimit').hidden=!active;
     $('externalLimit').disabled=!active||inherited;
     if(inherited&&active)$('externalLimit').value=configured;
    }
   });
  }

  function userState(){
   const isUser=$('externalTargetType').value==='user',select=$('externalTargetId'),picker=$('externalUserPicker');
   select.hidden=isUser;picker.hidden=!isUser;if(!isUser)return;
   const current=metadata?.users.find(user=>String(user.id)===select.value),button=$('externalUserPickerButton'),options=$('externalUserOptions');
   function label(container,user){container.replaceChildren(node('span',user.display_name+' ('+user.profile_name+') · '+(user.has_access?'Con acceso':'Sin acceso')),node('span',user.has_access?'Activo':'Inactivo','badge text-white '+(user.has_access?'bg-success':'bg-danger')));}
   button.replaceChildren();if(current)label(button,current);else button.textContent='Seleccionar usuario';options.replaceChildren();
   (metadata?.users||[]).forEach(user=>{const item=node('button',undefined,'dropdown-item external-user-option');item.type='button';label(item,user);if(String(user.id)===select.value){item.classList.add('active');item.setAttribute('aria-current','true');}item.onclick=()=>{bootstrap.Dropdown.getInstance(button)?.hide();select.value=String(user.id);select.dispatchEvent(new Event('change'));button.focus();};options.append(item);});
   if(!options.childElementCount)options.append(node('span','No hay usuarios disponibles.','dropdown-item-text small'));
  }
  function targetOptions(){const type=$('externalTargetType').value,select=$('externalTargetId');select.replaceChildren();(type==='role'?metadata.roles:metadata.users).forEach(r=>option(select,type==='role'?r.rol_id:r.id,type==='role'?r.rol_nombre:r.display_name+' ('+r.profile_name+') · '+(r.has_access?'Con acceso':'Sin acceso')));}
  async function load(first=false){const seq=++sequence;loadedTarget=null;root.querySelectorAll('[data-rule-choice]').forEach(button=>button.disabled=true);root.querySelectorAll('.external-rule-group').forEach(group=>group.open=false);$('externalSave').disabled=true;$('externalRestoreProfile').disabled=true;try{if($('externalTargetType').value==='user'&&!$('externalTargetId').value){$('externalRulesForm').hidden=true;$('externalEffective').textContent='No hay usuarios con acceso al Generador de Link.';return;}$('externalRulesForm').hidden=false;const data=await request('config',null,{type:$('externalTargetType').value,id:$('externalTargetId').value||1});if(seq!==sequence||!root.isConnected)return;metadata=data;pendingBrowsers(data.pending_browser_users);revision=data.revision;const selectedTarget=$('externalTargetId').value;targetOptions();if(!first)$('externalTargetId').value=selectedTarget;userState();if($('externalTargetType').value==='user'&&!$('externalTargetId').value){$('externalRulesForm').hidden=true;$('externalEffective').textContent='Selecciona un usuario con acceso al Generador de Link.';return;}const settings=data.settings;for(const [name,code] of [['Schedule','schedule'],['Limit','limit'],['Browser','browser']])$('external'+name+'Mode').value=settings[code]===null||settings[code]===undefined?'inherit':settings[code]?'on':'off';$('externalLimit').value=settings.limit||20;drawWeek(settings.schedule||null);modes();loadedTarget=$('externalTargetType').value+':'+$('externalTargetId').value;$('externalEffective').textContent='Los cambios se aplican al guardar.';
   const box=$('externalBrowsers');box.replaceChildren();if($('externalTargetType').value==='user'){
    const heading=node('div',undefined,'d-flex flex-wrap align-items-center gap-2 mb-2');heading.append(node('strong','Navegadores registrados','small'));
    if(data.browsers.length){const reset=node('button','Restablecer vinculación','btn btn-sm btn-outline-danger ms-auto');reset.type='button';reset.onclick=()=>browserOperation('reset');heading.append(reset);}box.append(heading);
    if(!data.browsers.length)box.append(node('p','Sin navegadores registrados. El usuario registra el primero al entrar al Generador.','small text-body-secondary mb-0'));
    data.browsers.forEach(b=>{
     const row=node('div',undefined,'external-browser-row'),info=node('div',undefined,'external-browser-info');
     const browser=/Edg\//.test(b.label)?'Microsoft Edge':/Firefox\//.test(b.label)?'Firefox':/Chrome\//.test(b.label)?'Chrome':/Safari\//.test(b.label)?'Safari':b.label;
     const platform=/Android/.test(b.label)?'Android':/iPhone|iPad/.test(b.label)?'iOS':/Windows/.test(b.label)?'Windows':/Macintosh/.test(b.label)?'macOS':/Linux/.test(b.label)?'Linux':'';
     info.append(node('strong',browser+(platform?' · '+platform:'')),node('small',b.created_at,'text-body-secondary'));
     row.append(info,node('span',b.state==='pending'?'Pendiente':'Autorizado','badge '+(b.state==='pending'?'bg-warning text-dark':'bg-success text-white')));
     if(b.state==='pending'){const approve=node('button','Autorizar','btn btn-sm btn-primary');approve.type='button';approve.onclick=()=>browserOperation('approve',b.id);row.append(approve);}box.append(row);
    });
   }

  }catch(e){notice(e.message,true);}finally{if(seq===sequence){$('externalSave').disabled=!loadedTarget;$('externalRestoreProfile').disabled=!loadedTarget;root.querySelectorAll('[data-rule-choice]').forEach(button=>button.disabled=!loadedTarget);}}}
  async function browserOperation(operation,browser_id){if(busy||!confirm(operation==='approve'?'¿Autorizar este navegador y revocar la vinculación anterior?':'¿Eliminar todas las vinculaciones y solicitudes de este usuario? Podrá registrar un navegador nuevo sin aprobación, como en su primer acceso.'))return;busy=true;try{const data=await request('browser',{operation,browser_id,user_id:$('externalTargetId').value});notice(data.message);await load();}catch(e){notice(e.message,true);}finally{busy=false;}}
  root.querySelectorAll('[data-rule-choice]').forEach(button=>button.onclick=()=>{
   if(!loadedTarget||busy)return;const name=button.dataset.rule,select=$('external'+name+'Mode'),choice=button.dataset.ruleChoice;
   if(choice==='on'&&select.value==='inherit'){
    const inherited=metadata.inherited?.[{Schedule:'schedule',Limit:'limit',Browser:'browser'}[name]];
    if(name==='Schedule'&&inherited)drawWeek(inherited);if(name==='Limit'&&inherited)$('externalLimit').value=inherited;
   }
   select.value=choice;
   select.dispatchEvent(new Event('change'));
  });
  root.querySelectorAll('[name="externalTargetMode"]').forEach(input=>input.onchange=()=>{if(input.checked){$('externalTargetType').value=input.value;$('externalTargetType').dispatchEvent(new Event('change'));}});
  $('externalTargetType').onchange=()=>{root.querySelectorAll('[name="externalTargetMode"]').forEach(input=>input.checked=input.value===$('externalTargetType').value);modes();targetOptions();userState();load();};
  $('externalRestoreProfile').onclick=()=>{if(busy||!loadedTarget||$('externalTargetType').value!=='user')return;['Schedule','Limit','Browser'].forEach(n=>$('external'+n+'Mode').value='inherit');modes();$('externalEffective').textContent='Al guardar, horario, intentos y navegador usarán la configuración del perfil.';};$('externalTargetId').onchange=()=>{userState();load();};['Schedule','Limit','Browser'].forEach(n=>$('external'+n+'Mode').onchange=()=>{modes();$('externalEffective').textContent='Cambios pendientes de guardar.';});
  $('externalRulesForm').addEventListener('invalid',event=>{const group=event.target.closest('details');if(group)group.open=true;},true);
  $('externalRulesForm').onsubmit=async e=>{e.preventDefault();if(busy||loadedTarget!==$('externalTargetType').value+':'+$('externalTargetId').value)return;busy=true;$('externalSave').disabled=true;try{const settings={};for(const [name,code] of [['Schedule','schedule'],['Limit','limit'],['Browser','browser']]){const value=$('external'+name+'Mode').value;settings[code]=value==='inherit'?null:value==='off'?(code==='limit'?0:false):code==='browser'?true:code==='limit'?Number($('externalLimit').value):Object.fromEntries(weekReaders.map((fn,i)=>[i+1,fn()]));}const data=await request('save',{type:$('externalTargetType').value,id:$('externalTargetId').value,revision,settings});notice(data.message);await load();}catch(error){notice(error.message,true);}finally{busy=false;$('externalSave').disabled=false;}};load(true);return;
 }
 let page=1;async function loadReport(){const seq=++sequence;try{const data=await request('list',null,{period:$('externalPeriod').value,from:$('externalFrom').value,to:$('externalTo').value,q:$('externalSearch').value,size:$('externalSize').value,page});if(seq!==sequence||!root.isConnected)return;page=data.page;$('externalFrom').value=data.from;$('externalTo').value=data.to;const tbody=$('externalTable').tBodies[0];tbody.replaceChildren();data.rows.forEach(r=>{const tr=tbody.insertRow(),[date,time]=r.created_at.split(' '),[hour,minute]=time.split(':').map(Number),dateCell=tr.insertCell();dateCell.className='text-nowrap';dateCell.textContent=date.split('-').reverse().join('/')+' '+String(hour%12||12).padStart(2,'0')+':'+String(minute).padStart(2,'0')+' '+(hour<12?'AM':'PM');tr.insertCell().append(node('span',Number(r.finished)?(Number(r.success)?'Generado':'No generado'):'Pendiente','badge text-white '+(Number(r.finished)?(Number(r.success)?'bg-success':'bg-danger'):'bg-secondary')));tr.insertCell().textContent=r.name;});if(!data.rows.length){const td=tbody.insertRow().insertCell();td.colSpan=3;td.textContent='Sin registros en este periodo.';}$('externalCount').textContent=data.total+' registros · Página '+page+' de '+data.pages;fmModulePages($('externalPages'),page,data.pages,n=>{page=n;loadReport();});drawExternalChart($('externalChart'),data);$('externalChartEmpty').hidden=data.total>0;}catch(e){if(seq!==sequence)return;notice(e.message,true);$('externalTable').tBodies[0].replaceChildren();$('externalChart').replaceChildren();}}
 ['externalPeriod','externalFrom','externalTo','externalSize'].forEach(id=>$(id).onchange=()=>{if(id==='externalFrom'||id==='externalTo')$('externalPeriod').value='custom';page=1;loadReport();});$('externalSearch').oninput=()=>{clearTimeout(timer);sequence++;timer=setTimeout(()=>{page=1;loadReport();},300);};loadReport();
}
function drawExternalChart(container,data){
 const ns='http://www.w3.org/2000/svg',svg=document.createElementNS(ns,'svg');svg.setAttribute('viewBox','0 0 1000 200');svg.setAttribute('preserveAspectRatio','none');svg.setAttribute('role','img');svg.setAttribute('aria-label','Intentos de generación por día');
 const dayMap=Object.fromEntries(data.daily.map(r=>[r.day,Number(r.total)])),dates=[];for(let day=new Date(data.from+'T12:00:00Z');day<=new Date(data.to+'T12:00:00Z');day.setUTCDate(day.getUTCDate()+1))dates.push(day.toISOString().slice(0,10));
 const max=Math.max(4,Math.ceil(Math.max(0,...Object.values(dayMap))/4)*4),x=i=>42+i/Math.max(1,dates.length-1)*934,y=value=>170-value/max*158;
 const shape=(tag,attrs,text)=>{const el=document.createElementNS(ns,tag);Object.entries(attrs).forEach(([k,v])=>el.setAttribute(k,v));if(text!==undefined)el.textContent=text;svg.append(el);return el;};
 for(let i=0;i<=4;i++){const cy=y(max*i/4);shape('line',{x1:42,y1:cy,x2:976,y2:cy,class:'chart-grid'});shape('text',{x:32,y:cy+4,'text-anchor':'end',class:'chart-label'},max*i/4);}
 for(let i=0;i<=8;i++)shape('line',{x1:42+934*i/8,y1:12,x2:42+934*i/8,y2:170,class:'chart-grid'});
 const points=dates.map((d,i)=>x(i)+','+y(dayMap[d]||0));if(points.length===1)points.push('976,'+y(dayMap[dates[0]]||0));shape('polyline',{points:points.join(' '),fill:'none',stroke:'var(--bs-primary)','stroke-width':2,'stroke-linecap':'round','stroke-linejoin':'round','vector-effect':'non-scaling-stroke'});
 [...new Set([0,Math.floor((dates.length-1)/4),Math.floor((dates.length-1)/2),Math.floor(3*(dates.length-1)/4),dates.length-1])].forEach(i=>{if(dates[i])shape('text',{x:x(i),y:192,'text-anchor':'middle',class:'chart-label'},dates[i].slice(5).split('-').reverse().join('/'));});container.replaceChildren(svg);
}
