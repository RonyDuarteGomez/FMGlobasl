<?php
$escape=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
$days=[1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
$today=(int)(new DateTimeImmutable('now',new DateTimeZone('America/Lima')))->format('N');
$options=static function(string $selected,bool $end=false)use($escape):void {
    $values=[];for($minute=$end?15:0;$minute<=($end?1440:1425);$minute+=15)$values[]=\FMGlobal\Services\Schedules\WeeklySchedule::time($minute);
    if(!in_array($selected,$values,true)){$values[]=$selected;sort($values);}
    foreach($values as $value)echo '<option value="'.$escape($value).'"'.($value===$selected?' selected':'').'>'.$escape($value).'</option>';
};
$slot=static function(array $range)use($options):void { ?>
<div class="schedule-slot">
<label><span>Desde</span><select class="form-select form-select-sm" data-start><?php $options($range['start']); ?></select></label>
<span class="slot-divider" aria-hidden="true">—</span>
<label><span>Hasta</span><select class="form-select form-select-sm" data-end><?php $options($range['end'],true); ?></select></label>
<button type="button" class="btn btn-sm btn-outline-danger" data-remove-slot aria-label="Quitar horario" title="Quitar horario">×</button>
</div>
<?php }; ?>
<div class="contenedor-seccion support-users-page weekly-schedule-page">
<div class="module-heading"><h2>Soporte clientes</h2><span class="module-category">Servicios</span></div>
<div class="schedule-toolbar">
<div class="schedule-live">Servicio ahora <span id="scheduleLive" class="badge <?= $active?'text-bg-success':'text-bg-danger' ?>"><?= $active?'Activo':'Inactivo' ?></span><span class="text-body-secondary">Hora de Lima</span><a class="btn btn-primary btn-sm schedule-access" href="validacion.php" target="_blank" rel="noopener">Acceder al servicio</a></div>
</div>
<form id="formActivacion" class="card card-outline card-primary schedule-editor" data-revision="<?= $schedule['revision'] ?>">
<?= \FMGlobal\Security\Csrf::field() ?>
<div class="card-header"><h3 class="card-title">Programación semanal</h3><p class="schedule-description">Elige cuándo estarán disponibles los servicios de Validación. Fuera de estos horarios estarán deshabilitados.</p><small class="text-body-secondary">24:00 indica el final del día. Para continuar después de medianoche, agrega un horario al día siguiente.</small></div>
<div class="card-body schedule-days">
<?php foreach($days as $number=>$label): $day=$schedule['week'][$number]; ?>
<section class="schedule-day <?= $number===$today?'schedule-today':'' ?>" data-day="<?= $number ?>" aria-label="<?= $label ?>">
<div class="schedule-day-name"><h4><?= $label ?></h4><?php if($number===$today): ?><span class="badge text-bg-primary">Hoy</span><?php endif ?></div>
<label class="schedule-mode"><span class="visually-hidden">Disponibilidad del <?= $label ?></span><select class="form-select form-select-sm" data-mode>
<?php foreach(['all'=>'Activo todo el día','hours'=>'Definir horarios','off'=>'Deshabilitado'] as $mode=>$text): ?><option value="<?= $mode ?>" <?= $mode===$day['mode']?'selected':'' ?>><?= $text ?></option><?php endforeach ?>
</select></label>
<div class="schedule-periods">
<p class="schedule-day-summary" <?= $day['mode']==='hours'?'hidden':'' ?>><?= $day['mode']==='all'?'Disponible las 24 horas':'Sin atención este día' ?></p>
<div class="schedule-hours" <?= $day['mode']!=='hours'?'hidden':'' ?>><div class="schedule-slots"><?php foreach($day['slots'] as $range)$slot($range); ?></div><button type="button" class="btn btn-sm btn-outline-primary" data-add-slot>+ Agregar horario</button></div>
</div>
<button type="button" class="btn btn-sm btn-outline-secondary schedule-copy" data-copy-day title="Copiar la programación de este día a los otros seis días">Copiar al resto</button>
</section>
<?php endforeach ?>
</div>
<div class="card-footer schedule-savebar"><div id="scheduleStatus" role="status" aria-live="polite"><?= $schedule['ready']?'Programación guardada.':'La programación actual se conserva. Falta aplicar la migración para editarla.' ?></div><button class="btn btn-primary" type="submit" <?= !$schedule['ready']?'disabled':'' ?>>Guardar programación</button></div>
</form>
<template id="scheduleSlotTemplate"><?php $slot(['start'=>'09:00','end'=>'18:00']); ?></template>
</div>