<?php
namespace FMGlobal\Services\Links;
final class AccountVault
{
    private string $key;
    public function __construct(?string $key=null) {
        $this->key=$key ?? (is_file(FM_ROOT.'/config/links.key') ? file_get_contents(FM_ROOT.'/config/links.key') : '');
        if(strlen($this->key)!==32) throw new \FMGlobal\Http\HttpException(503,'Falta configurar la clave de cuentas. Ejecuta la migración del módulo.');
    }
    public function encrypt(string $text): string {
        $iv=random_bytes(12); $tag='';
        $cipher=openssl_encrypt($text,'aes-256-gcm',$this->key,OPENSSL_RAW_DATA,$iv,$tag);
        if($cipher===false) throw new \RuntimeException('No se pudo cifrar.');
        return base64_encode($iv.$tag.$cipher);
    }
    public function decrypt(string $text): string {
        $raw=base64_decode($text,true);
        if($raw===false || strlen($raw)<28) throw new \RuntimeException('Cifrado inválido.');
        $plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',$this->key,OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16));
        if($plain===false) throw new \RuntimeException('No se pudo descifrar.');
        return $plain;
    }
    public function fingerprint(string $text): string {return hash_hmac('sha256',$text,$this->key);}
}
