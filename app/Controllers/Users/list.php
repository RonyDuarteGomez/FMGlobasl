<?php
$result=(new \FMGlobal\Repositories\UserRepository(database()))->list();
require FM_ROOT.'/resources/views/Users/list.php';
