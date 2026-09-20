<?php $escape=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); ?>
<section class="permissions-page">
<div class="module-heading"><h2>Permisos</h2><span class="module-category">Mantenimiento</span></div>
<form class="filtros permission-filters" data-module-filter="permisos/index.php">
<select class="form-select form-select-sm" name="type" aria-label="Definir permisos por"><option value="role" <?= $type==='role'?'selected':'' ?>>Definir permiso por perfil</option><option value="user" <?= $type==='user'?'selected':'' ?>>Definir permiso por usuario</option></select>
<select class="form-select form-select-sm" name="id" aria-label="<?= $type==='user'?'Usuario':'Perfil' ?>"><?php foreach($targets as $target): ?><option value="<?= (int)$target['id'] ?>" <?= (int)$target['id']===$id?'selected':'' ?>><?= $escape($target['label']) ?></option><?php endforeach ?></select>
<button id="savePermissions" class="btn btn-sm btn-primary" type="submit" form="permissionsForm">Guardar permisos</button>
</form>
<form id="permissionsForm" action="permisos/save.php" method="POST">
<?= \FMGlobal\Security\Csrf::field() ?>
<p id="permissionStatus" role="status" aria-live="polite"></p>
<input type="hidden" name="type" value="<?= $type ?>"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="revision" value="<?= $revision ?>">
<?php
$groups=[];
foreach($catalog as $code=>[$section,$label]) {
    $value=array_key_exists($code,$values)?((int)$values[$code]===1?'allow':'deny'):($type==='user'?'inherit':'deny');
    if (str_starts_with($code,'activity.')) {
        // Conservar el alcance actual; no se administra desde esta tabla de accesos.
        ?><input type="hidden" name="permissions[<?= $escape($code) ?>]" value="<?= $value ?>"><?php
        continue;
    }
    $groups[$section][$code]=[$label,$value];
}
?>
<div class="tablaContainer table-responsive"><table class="tabla table table-sm table-hover align-middle permission-table"><colgroup><col style="width:18%"><col style="width:28%"><col style="width:32%"><col style="width:22%"></colgroup><thead><tr><th>Categoría</th><th>Opción</th><th>Configuración</th><th>Acceso efectivo</th></tr></thead><tbody>
<?php foreach(['Gestión','Servicios','Mantenimiento','Reportes'] as $section): if(empty($groups[$section])) continue; ?>

<?php $firstInGroup=true; foreach($groups[$section] as $code=>[$label,$value]): $allowed=$type==='user'?($effective[$code]??false):!empty($values[$code]); ?>
<tr><?php if($firstInGroup): $firstInGroup=false; ?><td class="permission-category" rowspan="<?= count($groups[$section]) ?>"><?= $escape($section) ?></td><?php endif ?><td><label for="permission-<?= $escape($code) ?>"><?= $escape($label) ?></label></td><td>
<select class="form-select form-select-sm" id="permission-<?= $escape($code) ?>" name="permissions[<?= $escape($code) ?>]" data-inherited="<?= !empty($inherited[$code])?'1':'0' ?>">
<?php if($type==='user'): ?><option value="inherit" <?= $value==='inherit'?'selected':'' ?>>Definido por el perfil</option><?php endif ?>
<option value="allow" <?= $value==='allow'?'selected':'' ?>><?= $type==='user'?'Permitir al usuario':'Permitir' ?></option><option value="deny" <?= $value==='deny'?'selected':'' ?>>Denegar</option></select></td>
<td><span class="effective-access badge text-white <?= $allowed?'bg-success':'bg-danger' ?>"><?= ($type==='user'?($effective[$code]??false):!empty($values[$code]))?'Permitido':'Sin acceso' ?> · <?= $type==='role'||$value==='inherit'?'Perfil':'Usuario' ?></span></td></tr>
<?php endforeach; endforeach ?></tbody></table></div>

</form>

</section>
