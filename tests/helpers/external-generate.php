<?php
require dirname(__DIR__,2).'/bootstrap/app.php';
$repo=new \FMGlobal\Repositories\ExternalLinkRepository(database(),new \FMGlobal\Services\Links\LinkGenerator(fn($payload)=>['status'=>200,'body'=>'{"status":"success","login_url":"https://example.test/link"}']));
try{$result=$repo->generate((int)$argv[1],['id'=>'synthetic','secure'=>'synthetic','request_key'=>bin2hex(random_bytes(16))],'test-browser','test-session');echo json_encode(['status'=>200]+$result);}catch(\FMGlobal\Http\HttpException $e){echo json_encode(['status'=>$e->status]);}
