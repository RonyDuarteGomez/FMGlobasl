<?php
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['ok'=>true,'remaining'=>\FMGlobal\Security\Session::IDLE_SECONDS]);
