<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,2).'/bootstrap/app.php';
\FMGlobal\Security\Session::start();
$nombre='Usuario de prueba';$permissions=['services.advisor'=>true,'activity.own'=>true];$menuGroups=\FMGlobal\Support\Navigation::groups($permissions);
$_SERVER['REQUEST_URI']='/FMGlobal/home.php';
ob_start();require FM_ROOT.'/resources/views/Dashboard/home.php';$html=ob_get_clean();
$html=str_replace('<base href="/FMGlobal/">','<base href="http://localhost/FMGlobal/">',$html);
$mock='<style>.app-sidebar{transition:none!important}</style><script>window.fetch=async()=>({status:200,ok:true,headers:new Headers(),text:async()=>"Inicio"});history.replaceState=()=>{};history.pushState=()=>{};</script>';
$html=str_replace('</head>',$mock.'</head>',$html);
$test=<<<'JS'
<script>
window.addEventListener('load',()=>setTimeout(async()=>{
 const pause=()=>new Promise(r=>setTimeout(r,400));
 try {
 const menu=document.getElementById('sidebar'),btn=document.getElementById('toggle-btn');
 btn.click();await pause();const rect=menu.getBoundingClientRect();
 if(rect.left<0||rect.right>innerWidth||rect.width<200)throw Error('Menu fuera de pantalla');
 if(!menu.contains(document.elementFromPoint(rect.left+30,rect.top+70)))throw Error('Menu tapado');
 document.getElementById('overlay').click();await pause();
 if(menu.getBoundingClientRect().right>1)throw Error('No cierra');
 btn.click();await pause();document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape'}));await pause();
 if(menu.classList.contains('active'))throw Error('Escape no cierra');
 document.body.dataset.test='MOBILE_MENU_PASS';
 }catch(e){document.body.dataset.test='FAIL '+e.message;}
},600));
</script>
JS;
file_put_contents(sys_get_temp_dir().'/fm-mobile-menu.html',str_replace('</body>',$test.'</body>',$html));
