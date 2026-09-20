<?php
$nombre=$_SESSION['nombre_completo']??$_SESSION['usuario'];
$permissions=\FMGlobal\Security\Access::permissions();
$menuGroups=\FMGlobal\Support\Navigation::groups($permissions);
require FM_ROOT.'/resources/views/Dashboard/home.php';
