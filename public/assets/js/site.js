(() => {
 const button=document.querySelector('.web-menu-toggle');
 const nav=document.getElementById('web-nav');
 function closeMenu(){nav?.classList.remove('active');button?.setAttribute('aria-expanded','false');}
 button?.addEventListener('click',()=>{const open=nav.classList.toggle('active');button.setAttribute('aria-expanded',String(open));});
 nav?.addEventListener('click',event=>{if(event.target.closest('a'))closeMenu();});
 document.addEventListener('keydown',event=>{if(event.key==='Escape'&&nav?.classList.contains('active')){closeMenu();button.focus();}});
 document.addEventListener('click',event=>{if(!event.target.closest('.web-header'))closeMenu();});
 window.addEventListener('resize',()=>{if(window.innerWidth>700)closeMenu();});
 const year=document.getElementById('web-year');if(year)year.textContent=new Date().getFullYear();
})();