<?php
if(PHP_SAPI!=='cli')exit;
$isAdmin=true;ob_start();require dirname(__DIR__,2).'/resources/views/Spotify/index.php';$view=ob_get_clean();
$script=<<<'JS'
window.fmCsrfToken=()=> 'test';
if(location.hash.includes('test')){const anchorClick=HTMLAnchorElement.prototype.click;HTMLAnchorElement.prototype.click=function(){if(!this.download)return anchorClick.call(this);};}
const fixtureRow={profile_id:1,profile:'Perfil 1',account_id:1,email:'secundaria@example.test',password:'synthetic-password',state:'enabled',account_revision:1,service_id:1,service:'Spotify',assignment_id:1,revision:1,advisor_id:2,start_date:'2026-09-20',end_date:'2026-10-20',last_renewed_at:null,client_name:'Cliente de ejemplo',phone:'+51987654321',main_id:1,main_email:'principal@example.test',payment_email:'pago@example.test',next_payment:'2026-10-20',days:30,payment_days:30,advisor_name:'Ana Ramirez Diaz'};
let lastAction='',lastPayload={};let fixtureNotifications=Array.from({length:20},(_,i)=>({id:i+1,account_id:1,message:'Cuenta '+(i+1)+' restablecida. Revisa los cambios.'}));
window.fetch=async(url,options={})=>{
 const parsed=new URL(url),action=options.body?.get('action')||parsed.searchParams.get('action');let data={ok:true};
 if(action==='import')return {ok:true,json:async()=>({ok:true,loaded:1,errors:[{line:3,email:'duplicate@example.test',reason:'Duplicado: combinación ya registrada.',values:['Spotify','principal@example.test','pago@example.test','2027-01-01','duplicate@example.test','demo-pass','','habilitada','','','','','','']}],message:'1 filas cargadas; 1 rechazadas.'})};
 if(options.body){lastAction=action;lastPayload=JSON.parse(options.body.get('payload'));Object.assign(data,{message:'Operación guardada.',id:1});if(action==='read_notification')fixtureNotifications=fixtureNotifications.filter(n=>n.id!==lastPayload.id);if(action==='obtain')Object.assign(data,{available:true,assignment_id:1});}
 else if(action==='list')Object.assign(data,{rows:[fixtureRow],total:1,page:1,pages:1});
 else if(action==='payments')Object.assign(data,{rows:[{...fixtureRow,main_revision:1,summary:{total:5,assigned:2,fallen:1,free:2}}],total:1,page:1,pages:1});
 else if(action==='metadata')Object.assign(data,{services:[{id:1,name:'Spotify',max_accounts:5,max_profiles:1,available:3}],users:[{id:2,name:'Ana Ramirez Diaz',eligible:true}],orphaned:0,admin:true,notifications:fixtureNotifications});
 else if(action==='client')data.client={phone:'+51987654321',name:'Cliente de ejemplo',beneficiaries:[{label:'Mamá'},{label:'Papá'}]};
 else if(action==='assigned')data.assignment={id:1,revision:1,account_revision:1,email:fixtureRow.email,password:fixtureRow.password,profile:'Perfil 1'};
 else if(action==='main')data.main={id:1,revision:1,service_id:1,email:'principal@example.test',payment_email:'pago@example.test',next_payment:'2026-10-20',accounts:[{id:1,revision:1,email:fixtureRow.email,password:fixtureRow.password,profiles:[{id:1,name:'Perfil 1'}]}]};
 return {ok:true,json:async()=>data};
};
window.addEventListener('load',async()=>{
 iniciarSpotify();const wait=()=>new Promise(r=>setTimeout(r,120)),form=document.getElementById('spotifyForm');
 const byLabel=text=>{const label=[...document.querySelectorAll('#spotifyFields label')].find(l=>l.textContent.startsWith(text));if(!label)throw Error('Campo ausente '+text);return document.getElementById(label.htmlFor);};
 const clickAction=action=>document.querySelector('[data-spotify-action="'+action+'"]').click();
 const close=async()=>{bootstrap.Modal.getInstance(document.getElementById('spotifyModal')).hide();await wait();};
 try{
  await wait();if(!document.getElementById('spotifyTable').textContent.includes('Ana Ramirez Diaz'))throw Error('Listado no inicializa');
  if(!location.hash.includes('test')){document.body.dataset.test='PREVIEW';return;}
  const notices=document.getElementById('spotifyNotifications');let details=notices.querySelector('details');
  if(details.open||!details.querySelector('summary').textContent.includes('20 avisos'))throw Error('Avisos no agrupados');details.querySelector('summary').click();
  const noticeList=details.querySelector('.notification-list');if(noticeList.clientHeight>240||noticeList.scrollHeight<=noticeList.clientHeight)throw Error('Avisos no limitan altura');
  details.querySelector('.notification-actions button:last-child').click();await wait();details=notices.querySelector('details');
  if(!details.open||!details.querySelector('summary').textContent.includes('19 avisos'))throw Error('Lectura no actualiza contador o pierde desplegado');details.querySelector('summary').click();
  clickAction('new');await wait();
  const addSecondary=document.querySelector('.spotify-secondary-add button');for(let n=0;n<4;n++)addSecondary.click();await wait();
  const modalBody=document.querySelector('#spotifyModal .modal-body');if(getComputedStyle(modalBody).overflowY!=='auto'||modalBody.scrollHeight<=modalBody.clientHeight)throw Error('Scroll de múltiples secundarias');modalBody.scrollTop=modalBody.scrollHeight;if(modalBody.scrollTop===0)throw Error('Scroll bloqueado');
  if(!addSecondary.disabled||document.querySelectorAll('.spotify-secondary-list .spotify-secondary-row').length!==5)throw Error('Límite de secundarias');
  for(let n=0;n<4;n++)document.querySelector('.spotify-secondary-list .spotify-secondary-row:last-child button').click();modalBody.scrollTop=0;
  byLabel('Correo principal').value='new@example.test';byLabel('Correo de pago').value='pay@example.test';byLabel('Correo secundario').value='secondary@example.test';byLabel('Contraseña').value='synthetic';
  form.requestSubmit();await wait();if(lastAction!=='save'||lastPayload.accounts.length!==1||lastPayload.accounts[0].profiles[0].name!=='')throw Error('Alta dinámica');
  fixtureRow.assignment_id=null;document.getElementById('spotifySearch').dispatchEvent(new Event('input'));await new Promise(r=>setTimeout(r,400));
  clickAction('assign');await wait();byLabel('Asesor').value='2';byLabel('Celular').value='+51987654321';[...document.querySelectorAll('#spotifyFields button')].find(b=>b.textContent==='Buscar cliente').click();await wait();if(byLabel('Nombre').value!=='Cliente de ejemplo')throw Error('Búsqueda cliente');
  byLabel('Contraseña').value='changed-assigned';byLabel('Perfil').value='Perfil cambiado';form.requestSubmit();await wait();if(lastAction!=='obtain'||lastPayload.profile_id!==1||lastPayload.password!=='changed-assigned')throw Error('Asignación específica');
  fixtureRow.assignment_id=1;document.getElementById('spotifySearch').dispatchEvent(new Event('input'));await new Promise(r=>setTimeout(r,400));
  clickAction('transfer');await wait();
  const clientGroup=document.querySelector('.fm-client-fields'),lookup=clientGroup.querySelector('button');
  if(!clientGroup.querySelector('.btn-group .btn-check:checked'))throw Error('Modo cliente sin botón seleccionado');
  const beneficiaryInput=byLabel('Beneficiario (opcional)');
  const beneficiaryToggle=clientGroup.querySelector('[aria-controls]');beneficiaryToggle.click();const beneficiaryOptions=document.getElementById(beneficiaryToggle.getAttribute('aria-controls'));if(beneficiaryOptions.hidden||beneficiaryOptions.querySelectorAll('button').length!==2)throw Error('Beneficiarios no visibles al desplegar');beneficiaryOptions.querySelector('button').click();if(beneficiaryInput.value!=='Mamá'||!beneficiaryOptions.hidden)throw Error('Selección de beneficiario no funciona');
  beneficiaryInput.value=' mama ';beneficiaryInput.dispatchEvent(new Event('change',{bubbles:true}));
  if(beneficiaryInput.value!=='Mamá'||!clientGroup.textContent.includes('Se utilizará'))throw Error('No reutiliza beneficiario existente');
  if(!lookup.hidden||byLabel('Nombre').value!==fixtureRow.client_name)throw Error('Editar debe mostrar cliente sin buscador');
  byLabel('Nombre').value='Nombre editado';clientGroup.querySelector('input[type=radio][value=select]').click();
  if(lookup.hidden||lookup.parentElement!==byLabel('Celular').parentElement||byLabel('Celular').value!=='')throw Error('Cambiar cliente debe buscar junto al celular');
  clientGroup.querySelector('input[type=radio][value=edit]').click();
  if(!lookup.hidden||byLabel('Nombre').value!=='Nombre editado')throw Error('Cambio de modo pierde edición');
  form.requestSubmit();await wait();if(lastAction!=='transfer'||lastPayload.client_mode!=='edit')throw Error('No envía modo editar cliente');
  clickAction('renew');await wait();if(!document.getElementById('spotifyFields').textContent.includes('20/11/2026'))throw Error('Previsualización renovación');byLabel('Cantidad de meses').value='3';byLabel('Cantidad de meses').dispatchEvent(new Event('input'));if(!document.getElementById('spotifyFields').textContent.includes('20/01/2027'))throw Error('Previsualización varios meses');form.requestSubmit();await wait();if(lastAction!=='renew'||lastPayload.months!=='3')throw Error('Renovación');
  clickAction('release');await wait();if(!byLabel('Nueva contraseña').required||byLabel('Nueva contraseña').value!=='')throw Error('Liberación exige contraseña');byLabel('Nueva contraseña').value='synthetic-new';form.requestSubmit();await wait();if(lastAction!=='release'||lastPayload.password!=='synthetic-new')throw Error('Liberación');
  clickAction('fall');await wait();form.requestSubmit();await wait();if(lastAction!=='fall')throw Error('Caído');
  if(document.getElementById('spotifyTogglePaymentData')||document.getElementById('spotifyService'))throw Error('Controles retirados siguen visibles');
  document.getElementById('spotifyModePayments').checked=true;document.getElementById('spotifyModePayments').dispatchEvent(new Event('change'));await wait();
  if(!document.getElementById('spotifySalesPanel').hidden||document.querySelectorAll('#spotifyPaymentsTable [data-spotify-action="edit"]').length!==1)throw Error('Modo pagos');
  clickAction('pay');await wait();if(byLabel('Cantidad de meses').value!=='1')throw Error('Pago individual no inicia en un mes');byLabel('Cantidad de meses').value='2';byLabel('Cantidad de meses').dispatchEvent(new Event('input'));if(!document.getElementById('spotifyFields').textContent.includes('20/12/2026'))throw Error('Fecha individual no refleja meses');form.requestSubmit();await wait();if(lastAction!=='pay'||lastPayload.months!=='2')throw Error('Pago individual no envía meses');
  const all=document.getElementById('spotifyPaymentsAll');all.checked=true;all.dispatchEvent(new Event('change'));document.getElementById('spotifyBulkPay').click();await wait();if(byLabel('Cantidad de meses').value!=='1')throw Error('Pago masivo no inicia en un mes');byLabel('Cantidad de meses').value='3';byLabel('Cantidad de meses').dispatchEvent(new Event('input'));form.requestSubmit();await wait();if(lastAction!=='pay_bulk'||lastPayload.items.length!==1||lastPayload.months!=='3')throw Error('Pago masivo');
  if(!document.getElementById('spotifyBulkPay').disabled)throw Error('Selección no se limpia');
  if(!document.querySelector('#spotifyPaymentsTable [data-spotify-action=edit] i')||!document.querySelector('#spotifyPaymentsTable [data-spotify-action=pay]').getAttribute('aria-label'))throw Error('Iconos de pago ausentes');
  clickAction('edit');await wait();if(byLabel('Correo principal').value!=='principal@example.test')throw Error('Edición');await close();
  document.getElementById('spotifyModeSales').checked=true;document.getElementById('spotifyModeSales').dispatchEvent(new Event('change'));await wait();
  clickAction('import');await wait();const upload=byLabel('Archivo CSV'),dt=new DataTransfer();dt.items.add(new File(['test'],'datos.csv',{type:'text/csv'}));upload.files=dt.files;form.requestSubmit();await wait();
  if(document.getElementById('spotifyImportReport').hidden||!document.getElementById('spotifyImportReport').textContent.includes('Se rechazaron 1 registros')||document.getElementById('spotifyImportReport').querySelector('table,ul')||!document.getElementById('spotifyModal').classList.contains('show'))throw Error('Informe CSV no visible');
  let downloaded='';const createUrl=URL.createObjectURL;URL.createObjectURL=blob=>{blob.text().then(text=>downloaded=text);return createUrl(blob);};
  document.querySelector('#spotifyImportReport button').click();await wait();
  if(!downloaded.includes('correo_principal')||!downloaded.includes('demo-pass')||!downloaded.includes('Duplicado: combinación'))throw Error('CSV de rechazos incompleto');
  document.body.dataset.test='SPOTIFY_UI_PASS';
 }catch(e){document.body.dataset.test='FAIL '+e.message;}
});
JS;
$html='<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="http://localhost/FMGlobal/"><link rel="stylesheet" href="assets/css/main.css"><style>.modal.fade,.modal.fade .modal-dialog,.modal-backdrop.fade{transition:none!important}</style></head><body class="fm-panel"><main style="padding:16px">'.$view.'</main><script>'.$script.'</script><script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script><script src="assets/js/table-actions.js"></script><script src="assets/js/spotify.js"></script></body></html>';
file_put_contents(sys_get_temp_dir().'/fm-spotify-preview.html',$html);
