// Presentación compartida: únicamente acciones dentro de estas tablas.
(() => {
 const rules=[
  ['#usuariosTable .btn-edit','fa-pen','Editar'],
  ['#usuariosTable .btn-delete','fa-user-slash','Inactivar'],
  ['#usuariosTable .btn-activate','fa-user-check','Activar'],
  ['#tablaGmail .gmail-refresh','fa-rotate-right','Actualizar token'],
  ['#tablaGmail .gmail-delete','fa-trash','Eliminar'],
  ['#spotifyPaymentsTable [data-spotify-action="edit"]','fa-pen','Editar'],
  ['#spotifyPaymentsTable [data-spotify-action="pay"]','fa-money-bill-wave','Pago'],
  ['#linkTable [data-link-action="generate"]','fa-link','Generar'],
  ['#linkTable [data-link-action="error"]','fa-triangle-exclamation','Error'],
  ['#linkTable [data-link-action="edit"]','fa-pen','Editar'],
  ['#linkTable [data-link-action="single"]','fa-user-plus','Asignación']
 ];
 const active=new Map();let queued=false;
 function sync(){
  queued=false;if(!window.bootstrap?.Tooltip)return;
  for(const [button,entry] of active)if(!button.isConnected){entry.tooltip.dispose();active.delete(button);}
  for(const [selector,icon,label] of rules)document.querySelectorAll(selector).forEach(button=>{
   if(active.has(button)){const wrap=active.get(button).wrap;wrap.tabIndex=button.disabled?0:-1;return;}
   const description=button.title||label;button.removeAttribute('title');button.classList.add('fm-table-icon');
   if(!button.hasAttribute('aria-label'))button.setAttribute('aria-label',label);
   const i=document.createElement('i');i.className='fas '+icon;i.setAttribute('aria-hidden','true');button.replaceChildren(i);
   const wrap=document.createElement('span');wrap.className='fm-table-icon-wrap';wrap.tabIndex=button.disabled?0:-1;wrap.setAttribute('aria-label',description);
   button.replaceWith(wrap);wrap.append(button);
   const tooltip=new bootstrap.Tooltip(wrap,{title:description,container:'body',trigger:'hover focus',placement:'top'});
   button.addEventListener('focus',()=>tooltip.show());button.addEventListener('blur',()=>tooltip.hide());button.addEventListener('click',()=>tooltip.hide());
   active.set(button,{wrap,tooltip});
  });
 }
 function start(){sync();new MutationObserver(()=>{if(!queued){queued=true;queueMicrotask(sync);}}).observe(document.body,{childList:true,subtree:true,attributes:true,attributeFilter:['disabled']});}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
