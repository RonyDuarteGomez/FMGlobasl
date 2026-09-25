<?php
$status=$externalAvailability;
$clock=static function(string $time):string {[$h,$m]=array_map('intval',explode(':',$time));return sprintf('%02d:%02d %s',$h%12?:12,$m,$h%24<12?'AM':'PM').($h===24?' (día siguiente)':'');};
$badge=static fn(string $level):string=>'badge bg-'.$level.($level==='warning'?' text-dark':' text-white');
$attempts=$status['indicators']['attempts'];$schedule=$status['indicators']['schedule'];$browser=$status['indicators']['browser'];
?>
<article class="card card-body external-home-availability">
<div class="d-flex align-items-center flex-wrap gap-2"><h3 class="fs-6 mb-0">Generador de Link</h3><span class="badge text-white <?= $status['allowed']?'bg-success':'bg-danger' ?>"><?= $status['allowed']?'Servicio habilitado':'Servicio inhabilitado' ?></span><button type="button" class="btn btn-sm btn-outline-primary ms-auto" data-open-module="GeneradorExterno">Ir al generador</button></div>
<?php if($attempts['remaining']!==null||$schedule['restricted']||$browser['required']): ?>
<div class="external-restrictions mt-2">
<?php if($attempts['remaining']!==null): ?><section class="external-restriction"><h3>Intentos diarios</h3><div>Límite: <?= (int)$status['limit'] ?> intentos</div><span class="<?= $badge($attempts['level']) ?>"><?= $attempts['remaining']===0?'Cupo agotado':'Quedan '.(int)$attempts['remaining'].' intentos' ?></span><div class="small">Utilizados hoy: <?= (int)$status['used'] ?></div><div class="external-restriction-note">Se reinicia a las 12:00 AM · Lima</div></section><?php endif ?>
<?php if($schedule['restricted']): $today=$schedule['today'];$minutes=$schedule['seconds']===null?null:(int)ceil($schedule['seconds']/60); ?>
<section class="external-restriction"><h3>Horario permitido · Lima</h3><div class="external-restriction-config"><?= $escape($today['mode']==='all'?'Hoy: todo el día':($today['mode']==='hours'?implode(' · ',array_map(fn($slot)=>$clock($slot['start']).' – '.$clock($slot['end']),$today['slots'])):'Hoy: sin horario habilitado')) ?></div><span class="<?= $badge($schedule['level']) ?>"><?= $schedule['level']==='danger'?'Fuera del horario permitido':($minutes===null?'Disponible todo el día':'Quedan '.intdiv($minutes,60).' h '.sprintf('%02d',$minutes%60).' min') ?></span>
<?php if($schedule['level']==='danger'&&$status['next']): [$day,$time]=explode(' ',$status['next']); ?><div class="external-restriction-note">Próximo inicio: <?= $escape(implode('/',array_reverse(explode('-',$day))).' '.$clock($time)) ?></div><?php endif ?></section><?php endif ?>
<?php if($browser['required']): $approved=$browser['level']==='success';$initial=$status['browser_registration_available'];$pending=$status['browser_state']==='pending'&&!$initial; ?>
<section class="external-restriction"><h3>Restricción por navegador</h3><div>Acceso desde un solo navegador</div><span class="<?= $badge($browser['level']) ?>"><?= $approved?'Autorizado':($pending?'Pendiente de autorización':($initial?'Sin registrar':'No autorizado')) ?></span><div class="external-restriction-note"><?= $approved?'Solo puedes utilizar el servicio desde este navegador.':($initial?'Registra tu navegador desde el generador.':($pending?'El administrador debe revisar tu solicitud.':($status['browser_linked']?'Tu usuario tiene otro navegador vinculado.':'La vinculación anterior fue revocada.'))) ?></div></section><?php endif ?>
</div><?php endif ?>
</article>
