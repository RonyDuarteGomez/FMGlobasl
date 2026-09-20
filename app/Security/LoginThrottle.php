<?php
namespace FMGlobal\Security;
use FMGlobal\Http\HttpException;
final class LoginThrottle
{
    public function __construct(private string $path) {}
    public function attempt(string $username,string $ip,?int $now=null): void
    {
        $now??=time();
        $dir=dirname($this->path);
        if (!is_dir($dir) && !@mkdir($dir,0700,true) && !is_dir($dir)) throw new \RuntimeException('No se pudo crear el control de acceso.');
        $file=fopen($this->path,'c+');
        if (!$file) throw new \RuntimeException('No se pudo abrir el control de acceso.');
        try {
            if (!flock($file,LOCK_EX)) throw new \RuntimeException('No se pudo bloquear el control de acceso.');
            $raw=stream_get_contents($file);
            $entries=$raw===''?[]:json_decode($raw,true,512,JSON_THROW_ON_ERROR);
            foreach($entries as $key=>$entry) if($entry['until']<=$now) unset($entries[$key]);
            $buckets=[hash('sha256','ip:'.$ip)=>100,hash('sha256','account:'.$ip.':'.mb_strtolower($username,'UTF-8'))=>10];
            foreach($buckets as $key=>$limit) if(($entries[$key]['count']??0)>=$limit) throw new HttpException(429,'Demasiados intentos de acceso. Inténtalo nuevamente en 15 minutos.');
            foreach($buckets as $key=>$limit) {
                $entries[$key]??=['count'=>0,'until'=>$now+900];
                $entries[$key]['count']++;
            }
            $json=json_encode($entries,JSON_THROW_ON_ERROR);
            rewind($file); if(!ftruncate($file,0) || fwrite($file,$json)!==strlen($json) || !fflush($file)) throw new \RuntimeException('No se pudo guardar el control de acceso.');
        } finally { flock($file,LOCK_UN); fclose($file); }
    }
}