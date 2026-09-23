<?php
use FMGlobal\Repositories\SpotifySalesRepository;
if(isset($_GET['action'])){if($_GET['action']!=='list')throw new \FMGlobal\Http\HttpException(422,'Acción no válida.');$result=(new SpotifySalesRepository(database()))->report((int)$_SESSION['usuario_id'],$_GET);header('Content-Type: application/json; charset=UTF-8');echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);return;}
require FM_ROOT.'/resources/views/Reports/spotify-sales.php';
