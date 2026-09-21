<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,2).'/bootstrap/app.php';
if(!preg_match('/^fm_spotify_test_[a-f0-9]+$/D',getenv('FMGLOBAL_DB_NAME')?:''))throw new RuntimeException('Solo base temporal de pruebas.');
$input=json_decode($argv[2],true,512,JSON_THROW_ON_ERROR);
$repo=new \FMGlobal\Repositories\SpotifyRepository(database(),new \FMGlobal\Services\Links\AccountVault(hex2bin(getenv('FM_SP_TEST_KEY'))));
echo json_encode($repo->execute((int)$argv[1],'obtain',$input),JSON_THROW_ON_ERROR);
