<?php
$names=\FMGlobal\Repositories\UserNames::all(database());
$nombre=$names[(int)$_SESSION['usuario_id']]['display_name']??$_SESSION['usuario'];
$permissions=\FMGlobal\Security\Access::permissions();
$menuGroups=\FMGlobal\Support\Navigation::groups($permissions);
require FM_ROOT.'/resources/views/Dashboard/home.php';
