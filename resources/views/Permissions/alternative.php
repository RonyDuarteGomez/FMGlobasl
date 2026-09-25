<?php
$escape=static fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
$groups=[];$visible=[];
foreach(\FMGlobal\Support\Navigation::GROUPS as $section=>$items)foreach($items as $item){$groups[$section][$item[3]]=$item[1];$visible[$item[3]]=true;}
?>
<section class="permissions-page permission-preview">
<div class="module-heading"><h2>Permisos usuarios</h2><span class="module-category">Mantenimiento</span></div>
<div class="card card-body">
<form class="permission-preview-toolbar" data-module-filter="permisos/index.php">
<input type="hidden" name="view" value="alternative">
<div class="permission-preview-mode"><div class="btn-group" role="group" aria-label="Configurar permisos de"><input type="radio" class="btn-check" name="type" value="role" id="permissionModeRole" <?= $type==='role'?'checked':'' ?>><label class="btn btn-sm btn-outline-primary" for="permissionModeRole">Perfiles</label><input type="radio" class="btn-check" name="type" value="user" id="permissionModeUser" <?= $type==='user'?'checked':'' ?>><label class="btn btn-sm btn-outline-primary" for="permissionModeUser">Usuarios</label></div></div>
<div class="permission-preview-selection">
<select class="form-select form-select-sm" name="id" aria-label="Seleccionar <?= $type==='role'?'perfil':'usuario' ?>"><?php foreach($targets as $target): ?><option value="<?= (int)$target['id'] ?>" <?= (int)$target['id']===$id?'selected':'' ?>><?= $escape($target['label']) ?></option><?php endforeach ?></select>
<?php if($type==='user'): ?><button type="button" id="restoreProfilePermissions" class="btn btn-sm btn-outline-secondary">Restaurar a permisos de perfil</button><?php endif ?>
<button id="savePermissions" class="btn btn-sm btn-primary" type="submit" form="permissionsForm">Guardar cambios</button>
</div></form>


<form id="permissionsForm" action="permisos/save.php" method="POST" data-alternative="1">
<?= \FMGlobal\Security\Csrf::field() ?>
<input type="hidden" name="type" value="<?= $type ?>"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="revision" value="<?= $revision ?>"><input type="hidden" name="view" value="alternative">
<p id="permissionStatus" role="status" aria-live="polite" class="small mb-2">Los cambios se aplican al guardar.</p>
<?php foreach($catalog as $code=>$_): if(isset($visible[$code]))continue;$value=array_key_exists($code,$values)?($values[$code]?'allow':'deny'):($type==='user'?'inherit':'deny'); ?><input type="hidden" name="permissions[<?= $escape($code) ?>]" value="<?= $value ?>"><?php endforeach ?>
<?php foreach($groups as $section=>$items): ?>
<details class="permission-preview-group"><summary><?= $escape($section) ?><span class="permission-group-count"></span></summary>
<?php foreach($items as $code=>$label): $value=array_key_exists($code,$values)?($values[$code]?'allow':'deny'):($type==='user'?'inherit':'deny');$profileAllowed=!empty($inherited[$code]);$allowed=$value==='allow'||($value==='inherit'&&$profileAllowed);$key=str_replace('.','-',$code); ?>
<div class="permission-preview-row" data-inherited="<?= $profileAllowed?'1':'0' ?>">
<strong class="permission-option-name"><?= $escape($label) ?></strong>
<div class="btn-group permission-choice" role="group" aria-label="Configurar <?= $escape($label) ?>">
<?php foreach(($type==='user'?['inherit'=>'Perfil','allow'=>'Permitir','deny'=>'Denegar']:['allow'=>'Permitido','deny'=>'Sin acceso']) as $choice=>$text): ?>
<input class="btn-check" type="radio" name="permissions[<?= $escape($code) ?>]" value="<?= $choice ?>" id="preview-<?= $key ?>-<?= $choice ?>" <?= $value===$choice?'checked':'' ?>><label class="btn btn-sm btn-outline-<?= $choice==='allow'?'success':($choice==='deny'?'danger':'secondary') ?>" for="preview-<?= $key ?>-<?= $choice ?>"><?= $text ?></label>
<?php endforeach ?></div>
<?php if($type==='user'): ?><div class="permission-access-labels" aria-label="Acceso final"><div><span class="badge permission-final-access <?= $allowed?'bg-success':'bg-danger' ?> text-white"><?= $allowed?'Permitido':'Sin acceso' ?></span><small class="permission-access-source" <?= $value==='inherit'?'':'hidden' ?>>Por perfil</small></div></div>
<?php endif ?>
</div><?php endforeach ?></details><?php endforeach ?>
</form></div></section>
