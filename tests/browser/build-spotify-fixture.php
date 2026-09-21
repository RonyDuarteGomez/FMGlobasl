<?php
if(PHP_SAPI!=='cli')exit;
$isAdmin=true;ob_start();require dirname(__DIR__,2).'/resources/views/Spotify/index.php';$view=ob_get_clean();
$script=<<<'JS'
window.fmCsrfToken=()=> 'test';
const fixtureRow={profile_id:1,profile:'Perfil 1',account_id:1,email:'secundaria@example.test',password:'synthetic-password',state:'enabled',account_revision:1,service_id:1,service:'Spotify',assignment_id:1,revision:1,advisor_id:2,start_date:'2026-09-20',end_date:'2026-10-20',last_renewed_at:null,client_name:'Cliente de ejemplo',phone:'+51987654321',main_id:1,main_email:'principal@example.test',payment_email:'pago@example.test',next_payment:'2026-10-20',days:30,payment_days:30,advisor_name:'Ana Ramirez Diaz'};
let lastAction='',lastPayload={};
window.fetch=async(url,options={})=>{
 const parsed=new URL(url),action=options.body?.get('action')||parsed.searchParams.get('action');let data={ok:true};
 if(action==='import')return {ok:true,json:async()=>({ok:true,loaded:1,errors:[{line:3,email:'duplicate@example.test',reason:'Correo ya registrado.'}],message:'1 filas cargadas; 1 rechazadas.'})};
 if(options.body){lastAction=action;lastPayload=JSON.parse(options.body.get('payload'));Object.assign(data,{message:'Operación guardada.',id:1});if(action==='obtain')Object.assign(data,{available:true,assignment_id:1});}
 else if(action==='list')Object.assign(data,{rows:[fixtureRow],total:1,page:1,pages:1});
 else if(action==='payments')Object.assign(data,{rows:[{...fixtureRow,main_revision:1,summary:{total:5,assigned:2,fallen:1,free:2}}],total:1,page:1,pages:1});
 else if(action==='metadata')Object.assign(data,{services:[{id:1,name:'Spotify',max_accounts:5,max_profiles:1,available:3}],users:[{id:2,name:'Ana Ramirez Diaz',eligible:true}],orphaned:0,admin:true});
 else if(action==='client')data.client={phone:'+51987654321',name:'Cliente de ejemplo'};
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
  clickAction('new');await wait();
  const addSecondary=document.querySelector('.spotify-secondary-add button');for(let n=0;n<4;n++)addSecondary.click();await wait();
  const modalBody=document.querySelector('#spotifyModal .modal-body');if(getComputedStyle(modalBody).overflowY!=='auto'||modalBody.scrollHeight<=modalBody.clientHeight)throw Error('Scroll de múltiples secundarias');modalBody.scrollTop=modalBody.scrollHeight;if(modalBody.scrollTop===0)throw Error('Scroll bloqueado');
  if(!addSecondary.disabled||document.querySelectorAll('.spotify-secondary-list .spotify-secondary-row').length!==5)throw Error('Límite de secundarias');
  for(let n=0;n<4;n++)document.querySelector('.spotify-secondary-list .spotify-secondary-row:last-child button').click();modalBody.scrollTop=0;
  byLabel('Correo principal').value='new@example.test';byLabel('Correo de pago').value='pay@example.test';byLabel('Correo secundario').value='secondary@example.test';byLabel('Contraseña').value='synthetic';
  form.requestSubmit();await wait();if(lastAction!=='save'||lastPayload.accounts.length!==1||lastPayload.accounts[0].profiles[0].name!=='')throw Error('Alta dinámica');
  fixtureRow.assignment_id=null;document.getElementById('spotifySearch').dispatchEvent(new Event('input'));await new Promise(r=>setTimeout(r,400));
  clickAction('assign');await wait();byLabel('Asesor').value='2';byLabel('Celular').value='+51987654321';[...document.querySelectorAll('#spotifyFields button')].find(b=>b.textContent==='Buscar cliente').click();await wait();if(!byLabel('Nombre').readOnly)throw Error('Búsqueda cliente');
  byLabel('Contraseña').value='changed-assigned';byLabel('Perfil').value='Perfil cambiado';form.requestSubmit();await wait();if(lastAction!=='obtain'||lastPayload.profile_id!==1||lastPayload.password!=='changed-assigned')throw Error('Asignación específica');
  fixtureRow.assignment_id=1;document.getElementById('spotifySearch').dispatchEvent(new Event('input'));await new Promise(r=>setTimeout(r,400));
  clickAction('renew');await wait();if(!document.getElementById('spotifyFields').textContent.includes('20/11/2026'))throw Error('Previsualización renovación');form.requestSubmit();await wait();if(lastAction!=='renew')throw Error('Renovación');
  clickAction('release');await wait();if(!byLabel('Nueva contraseña').required||byLabel('Nueva contraseña').value!=='')throw Error('Liberación exige contraseña');byLabel('Nueva contraseña').value='synthetic-new';form.requestSubmit();await wait();if(lastAction!=='release'||lastPayload.password!=='synthetic-new')throw Error('Liberación');
  clickAction('fall');await wait();form.requestSubmit();await wait();if(lastAction!=='fall')throw Error('Caído');
  if(document.getElementById('spotifyTogglePaymentData')||document.getElementById('spotifyService'))throw Error('Controles retirados siguen visibles');
  document.getElementById('spotifyModePayments').checked=true;document.getElementById('spotifyModePayments').dispatchEvent(new Event('change'));await wait();
  if(!document.getElementById('spotifySalesPanel').hidden||document.querySelectorAll('#spotifyPaymentsTable [data-spotify-action="edit"]').length!==1)throw Error('Modo pagos');
  const all=document.getElementById('spotifyPaymentsAll');all.checked=true;all.dispatchEvent(new Event('change'));document.getElementById('spotifyBulkPay').click();await wait();form.requestSubmit();await wait();if(lastAction!=='pay_bulk'||lastPayload.items.length!==1)throw Error('Pago masivo');
  if(!document.getElementById('spotifyBulkPay').disabled)throw Error('Selección no se limpia');
  if(!document.querySelector('#spotifyPaymentsTable [data-spotify-action=edit] i')||!document.querySelector('#spotifyPaymentsTable [data-spotify-action=pay]').getAttribute('aria-label'))throw Error('Iconos de pago ausentes');
  clickAction('edit');await wait();if(byLabel('Correo principal').value!=='principal@example.test')throw Error('Edición');await close();
  document.getElementById('spotifyModeSales').checked=true;document.getElementById('spotifyModeSales').dispatchEvent(new Event('change'));await wait();
  clickAction('import');await wait();const upload=byLabel('Archivo CSV'),dt=new DataTransfer();dt.items.add(new File(['test'],'datos.csv',{type:'text/csv'}));upload.files=dt.files;form.requestSubmit();await wait();
  if(document.getElementById('spotifyImportReport').hidden||!document.getElementById('spotifyImportReport').textContent.includes('duplicate@example.test'))throw Error('Informe CSV no visible');
  document.body.dataset.test='SPOTIFY_UI_PASS';
 }catch(e){document.body.dataset.test='FAIL '+e.message;}
});
JS;
$html='<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="http://localhost/FMGlobal/"><link rel="stylesheet" href="assets/css/main.css"><style>.modal.fade,.modal.fade .modal-dialog,.modal-backdrop.fade{transition:none!important}</style></head><body class="fm-panel"><main style="padding:16px">'.$view.'</main><script>'.$script.'</script><script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script><script src="assets/js/spotify.js"></script><script src="assets/js/table-actions.js"></script></body></html>';
file_put_contents(sys_get_temp_dir().'/fm-spotify-preview.html',$html);
