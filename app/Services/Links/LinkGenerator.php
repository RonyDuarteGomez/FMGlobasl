<?php
namespace FMGlobal\Services\Links;
use FMGlobal\Http\HttpException;
final class LinkGenerator {
 public const ENDPOINT='https://apitoken-eeds.onrender.com/generate';
 public function __construct(private ?\Closure $transport=null){}
 public function generate(string $id,string $secure):array {
  $payload=json_encode(['cookie_data'=>'NetflixId='.$id.';SecureNetflixId='.$secure],JSON_THROW_ON_ERROR);
  if($this->transport) $response=($this->transport)($payload);
  else {
   $ch=curl_init(self::ENDPOINT); $body='';
   curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json'],CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>40,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_WRITEFUNCTION=>static function($ch,$part)use(&$body){if(strlen($body)+strlen($part)>262144)return 0;$body.=$part;return strlen($part);}]);
   try{$ok=curl_exec($ch);$response=['status'=>$ok===false?0:(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE),'body'=>$body];}finally{curl_close($ch);}
  }
  $data=json_decode($response['body']??'',true);
  $url=$data['login_url']??null;
  if(($response['status']??0)<200||$response['status']>=300||($data['status']??null)!=='success'||!is_string($url)||!filter_var($url,FILTER_VALIDATE_URL)||parse_url($url,PHP_URL_SCHEME)!=='https'||parse_url($url,PHP_URL_USER)!==null) throw new HttpException(502,'No se obtuvo un enlace. La cuenta quedó en Error; puedes reintentar.');
  return ['url'=>$url,'expires'=>is_string($data['expires_at']??null)?$data['expires_at']:''];
 }
}
