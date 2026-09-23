<?php
if(PHP_SAPI!=='cli')exit;
ob_start();require dirname(__DIR__,2).'/resources/views/Clients/index.php';require dirname(__DIR__,2).'/resources/views/Reports/spotify-sales.php';$view=ob_get_clean();
$script=<<<'JS'
window.fmCsrfToken=()=> 'test';
let clients=[{phone:'+51987654321',name:'Cliente de ejemplo'}],lastQuery,lastPayload;
window.fetch=async(url,options={})=>{
 const u=new URL(url);let data={ok:true};
 if(u.pathname.endsWith('clientes.php')){
  if(options.body?.get('action')==='import'){Object.assign(data,{message:'1 clientes importados.',imported:1,errors:[]});}
  else if(options.body){lastPayload=JSON.parse(options.body.get('payload'));clients=[{phone:lastPayload.phone,name:lastPayload.name}];data.message='Cliente guardado.';}
  else Object.assign(data,{rows:clients,total:clients.length,page:1,pages:1});
 }else{lastQuery=u.searchParams;Object.assign(data,{rows:[{id:2,name:'Ana Ramirez',sale:3,renewal:2,loss:1,fallen:0},{id:3,name:'Carlos Gomez',sale:1,renewal:1,loss:0,fallen:1}],total:2,page:1,pages:1,from:'2026-09-18',to:'2026-09-20',totals:{sale:4,renewal:3,loss:1,fallen:1},metric:u.searchParams.get('metric'),dates:['2026-09-18','2026-09-19','2026-09-20'],series:[{id:'sale',name:'Ventas',values:[1,2,1]},{id:'renewal',name:'Renovaciones',values:[1,1,1]},{id:'loss',name:'Pérdidas',values:[0,1,0]},{id:'fallen',name:'Caídas',values:[0,0,1]}]});}
 return {ok:true,json:async()=>data};
};
window.addEventListener('load',async()=>{
 const wait=()=>new Promise(r=>setTimeout(r,150));iniciarClientes();iniciarVentasSpotify();await wait();
 try{
  if(!document.querySelector('#clientsTable button i'))throw Error('Icono de cliente');
  if(document.querySelectorAll('#salesChart svg polyline').length!==4)throw Error('Faltan líneas');if(document.getElementById('salesCustom').hidden)throw Error('Calendarios ocultos');
  if(!location.hash.includes('test')){document.body.dataset.test='PREVIEW';return;}
  const wrap=document.querySelector('#clientsTable .fm-table-icon-wrap');wrap.dispatchEvent(new MouseEvent('mouseenter'));await wait();if(document.querySelectorAll('.tooltip.show').length!==1)throw Error('Tooltip no abre');wrap.dispatchEvent(new MouseEvent('mouseleave'));await wait();if(document.querySelector('.tooltip.show'))throw Error('Tooltip persiste al salir');
  document.querySelector('#clientsTable button').click();await wait();document.getElementById('clientPhone').value='+12025550123';document.getElementById('clientName').value='Nombre nuevo';document.getElementById('clientForm').requestSubmit();await wait();
  if(lastPayload.original_phone!=='+51987654321'||lastPayload.phone!=='+12025550123')throw Error('Edición no conserva clave original');
  if(!document.getElementById('clientsTable').textContent.includes('Nombre nuevo'))throw Error('Tabla no refresca');
  const period=document.getElementById('salesPeriod');period.value='custom';period.dispatchEvent(new Event('change'));await wait();if(document.getElementById('salesCustom').hidden)throw Error('Rango oculto');
  document.getElementById('salesFrom').value='2026-01-01';document.getElementById('salesTo').value='2026-01-02';document.getElementById('salesTo').dispatchEvent(new Event('change'));await wait();if(lastQuery.get('from')!=='2026-01-01'||lastQuery.get('to')!=='2026-01-02')throw Error('Rango incorrecto');
  if(period.value!=='custom')throw Error('Calendario no activa rango');
  document.getElementById('clientImport').click();await wait();const transfer=new DataTransfer();transfer.items.add(new File(['nombre,celular\nPrueba,+51987654321'],'clientes.csv',{type:'text/csv'}));document.getElementById('clientCsv').files=transfer.files;document.getElementById('clientImportForm').requestSubmit();await wait();if(document.getElementById('clientsImportReport').hidden)throw Error('Resultado CSV oculto');
  document.body.dataset.test='CLIENTS_SALES_UI_PASS';
 }catch(e){document.body.dataset.test='FAIL '+e.message;}
});
JS;
$html='<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="http://localhost/FMGlobal/"><link rel="stylesheet" href="assets/css/main.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><style>.modal.fade,.modal.fade .modal-dialog,.modal-backdrop.fade{transition:none!important}body{padding:16px}</style></head><body class="fm-panel">'.$view.'<script>'.$script.'</script><script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script><script src="assets/js/clients-sales.js"></script><script src="assets/js/table-actions.js"></script></body></html>';
file_put_contents(sys_get_temp_dir().'/fm-clients-sales-preview.html',$html);
