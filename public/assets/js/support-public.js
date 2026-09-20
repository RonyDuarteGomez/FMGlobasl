(() => {
 const form=document.getElementById('supportSearch');
 const loading=document.getElementById('supportLoading');
 let busy=false;
 form?.addEventListener('submit',event=>{
  if(busy){event.preventDefault();return;}
  if(!form.checkValidity())return;
  busy=true;loading.hidden=false;
  document.querySelector('main').setAttribute('aria-busy','true');
  document.querySelector('main').inert=true;
  document.querySelector('header').inert=true;
 });
 window.addEventListener('pageshow',()=>{
  busy=false;loading.hidden=true;
  document.querySelector('main').removeAttribute('aria-busy');
  document.querySelector('main').inert=false;
  document.querySelector('header').inert=false;
 });
})();