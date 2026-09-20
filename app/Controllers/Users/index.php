<?php
$result=(new \FMGlobal\Repositories\UserRepository(database()))->roles();
require FM_ROOT.'/resources/views/Users/index.php';
