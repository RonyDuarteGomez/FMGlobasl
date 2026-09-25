<?php
if(PHP_SAPI!=='cli')exit;
$isAdmin=true;ob_start();require dirname(__DIR__,2).'/resources/views/Links/index.php';$view=ob_get_clean();
$script=<<<'JS'
window.fmCsrfToken=()=> 'test';
if(location.hash.includes('test')){const anchorClick=HTMLAnchorElement.prototype.click;HTMLAnchorElement.prototype.click=function(){if(!this.download)return anchorClick.call(this);};}
let sample={id:1,credentials:'cuenta@example.test:ejemplo',status:'active',error_type:0,usuario:'asesor',assigned_user_id:2,assigned_at:'20/09/2026 10:30 AM',moved_at:'20/09/2026 10:30 AM',moved_by:'admin',revision:1};
window.fetch=async(url,options={})=>{
 const action=options.body?.get('action')||new URL(url).searchParams.get('action');
 let data={ok:true};
 if(action==='list')Object.assign(data,{rows:[sample,{...sample,id:2,credentials:'otra@example.test:ejemplo',status:'error',error_type:1,usuario:null,assigned_user_id:null,assigned_at:'—'}],total:2,page:1,pages:1,users:[{id:2,usuario:'asesor',accounts:1,eligible:true},{id:3,usuario:'soporte',accounts:0,eligible:true}],summary:{orphaned:0}});
 if(action==='preview')Object.assign(data,{status:'active',error_type:0,assigned:1,available:1,requested:Number(options.body.get('quantity')||1),count:1,shortage:Number(options.body.get('quantity')||1)>1,fingerprint:'test'});
 if(action==='move')Object.assign(data,{message:'1 cuentas actualizadas.'});
 if(action==='details')Object.assign(data,{id:1,revision:1,external_id:'001',secure:'secure-demo',credentials:sample.credentials});
 if(action==='import')Object.assign(data,{loaded:1,rejected:1,errors:[{row:3,email:'cuenta@example.test',data:['001','secure-demo','cuenta@example.test:ejemplo'],reason:'Ya existe ese correo:contraseña.'}]});
 return {ok:true,json:async()=>data};
};
window.addEventListener('DOMContentLoaded',()=>iniciarLink());
window.addEventListener('load',async()=>{
 if(!location.hash.includes('test'))return;
 const wait=()=>new Promise(resolve=>setTimeout(resolve,180));
 try{
  if(location.hash.includes('import')) {
   await wait();document.querySelector('[data-link-action="import"]').click();await wait();
   const transfer=new DataTransfer();transfer.items.add(new File(['ID,secure,correo_contrasena\n001,secure-demo,cuenta@example.test:ejemplo'], 'cuentas.csv',{type:'text/csv'}));document.getElementById('linkCsv').files=transfer.files;
   document.getElementById('linkQuantity').value='0';
   const form=document.getElementById('linkAccountForm');
   if(!form.checkValidity())throw Error('Un campo oculto bloquea importar');
   form.requestSubmit();await wait();await wait();
   const result=document.getElementById('linkImportResult');
   if(!result.textContent.includes('Se importaron 1 registros')||!result.textContent.includes('Se rechazaron 1 registros'))throw Error('Falta resumen de importación');
   if(result.querySelector('ul,table')||!result.querySelector('button'))throw Error('Falta motivo o descarga');
   let downloaded='';const create=URL.createObjectURL;URL.createObjectURL=blob=>{blob.text().then(text=>downloaded=text);return create(blob);};
   result.querySelector('button').click();await wait();
   if(!downloaded.includes('cuenta@example.test')||!downloaded.includes('Ya existe'))throw Error('Log sin datos o motivo');
   document.body.dataset.test='LINK_IMPORT_PASS';return;
  }
  await wait();document.querySelector('[data-link-action="bulk"]').click();await wait();
  document.getElementById('linkOperation').value='transfer';document.getElementById('linkSource').value='2';document.getElementById('linkTarget').value='2';document.getElementById('linkSource').dispatchEvent(new Event('change',{bubbles:true}));await wait();
  if(document.getElementById('linkTarget').value==='2'||!document.querySelector('#linkTarget option[value="2"]').disabled)throw Error('Permite origen igual a destino');
  document.getElementById('linkOperation').value='assign';document.getElementById('linkOperation').dispatchEvent(new Event('change',{bubbles:true}));await wait();
  const all=document.getElementById('linkAll');all.checked=true;all.dispatchEvent(new Event('change',{bubbles:true}));await wait();
  if(document.getElementById('linkQuantity').value!=='1'||!document.getElementById('linkQuantity').disabled)throw Error('Todas no muestra cantidad');
  if(!document.getElementById('linkAvailability').textContent.includes('cuentas activas para asignar'))throw Error('Texto activas');
  const include=document.getElementById('linkIncludeError');include.checked=true;include.dispatchEvent(new Event('change',{bubbles:true}));await wait();
  if(!document.getElementById('linkAvailability').textContent.includes('cuentas activas y con error'))throw Error('Texto incluir error');
  all.checked=false;all.dispatchEvent(new Event('change',{bubbles:true}));await wait();
  const target=document.getElementById('linkTarget');target.value='3';target.dispatchEvent(new Event('change',{bubbles:true}));await wait();
  document.getElementById('linkQuantity').value='3';document.getElementById('linkAccountForm').requestSubmit();await wait();
  if(!document.getElementById('linkMovePreview').textContent.includes('solo hay 1'))throw Error('Falta confirmación parcial');
  if(location.hash.includes('modal')){document.body.dataset.test='MODAL_PASS';return;}
  document.getElementById('linkAccountForm').requestSubmit();await wait();await wait();
  if(document.getElementById('linkAccountModal').classList.contains('show'))throw Error('Modal no cerró');
  if(!document.getElementById('linkNotice').textContent.includes('actualizadas'))throw Error('Sin resultado');
  document.querySelector('[data-link-action="bulk"]').click();await wait();
  document.getElementById('linkTarget').value='3';document.getElementById('linkQuantity').value='1';
  document.getElementById('linkAccountForm').requestSubmit();await wait();await wait();
  if(document.getElementById('linkAccountModal').classList.contains('show'))throw Error('Cantidad suficiente requiere doble confirmación');
  document.querySelector('[data-link-action="single"]').click();await wait();
  if(!document.getElementById('linkAllGroup').hidden||!document.getElementById('linkIncludeErrorGroup').hidden)throw Error('Checks visibles para cuenta individual');
  if(document.getElementById('linkAvailability').textContent!=='1 cuenta activa.')throw Error('Resumen cuenta individual');
  bootstrap.Modal.getInstance(document.getElementById('linkAccountModal')).hide();await wait();await wait();
  document.querySelector('[data-link-action="edit"]').click();await wait();
  if(document.getElementById('linkExternalId').value!=='001')throw Error('Edición');
  bootstrap.Modal.getInstance(document.getElementById('linkAccountModal')).hide();await wait();await wait();
  document.querySelector('[data-link-action="import"]').click();await wait();
  const dt=new DataTransfer();dt.items.add(new File(['ID,secure,correo_contrasena'], 'test.csv',{type:'text/csv'}));document.getElementById('linkCsv').files=dt.files;
  document.getElementById('linkAccountForm').requestSubmit();await wait();
  if(!document.getElementById('linkImportResult').textContent.includes('Se rechazaron 1 registros'))throw Error('Informe CSV');
  bootstrap.Modal.getInstance(document.getElementById('linkAccountModal')).hide();await wait();await wait();
  const root=document.getElementById('linkAccounts');root.dataset.admin='0';delete root.dataset.ready;iniciarLink();await wait();
  if(root.querySelector('[data-link-action="error"]')||root.querySelector('[data-link-action="edit"]')||root.querySelector('[data-link-action="single"]'))throw Error('Acciones de administrador visibles');
  if(!document.getElementById('linkTable').textContent.includes('No Link'))throw Error('Etiqueta fallo automático');
  document.body.dataset.test='LINK_UI_PASS';
 }catch(e){document.body.dataset.test='FAIL '+e.message;}
});
JS;
$html='<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="http://localhost/FMGlobal/"><link rel="stylesheet" href="assets/css/main.css"><style>.modal.fade,.modal.fade .modal-dialog,.modal-backdrop.fade{transition:none!important}</style></head><body class="fm-panel"><main style="padding:20px">'.$view.'</main><script>'.$script.'</script><script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script><script src="assets/js/link.js"></script></body></html>';
file_put_contents(sys_get_temp_dir().'/fm-links-preview.html',$html);
