<?php
namespace FMGlobal\Services\Http;
use FMGlobal\Http\HttpException;
final class MailApiClient
{
    private const ENDPOINTS=['/api/netflix_link.php','/api/netflix_otp.php','/api/disney_otp.php','/api/buscar_correo.php'];
    public function __construct(private ?\Closure $transport = null) {}
    public function get(string $url): array
    {
        $parts=parse_url($url);
        if (($parts['scheme']??'')!=='https' || ($parts['host']??'')!=='fmglobals.com' || !in_array($parts['path']??'',self::ENDPOINTS,true) || isset($parts['user']) || isset($parts['port'])) throw new \InvalidArgumentException('Endpoint no permitido.');
        $response=$this->transport?($this->transport)($url):$this->request($url);
        if (($response['status']??0)<200 || $response['status']>=300) throw new HttpException(502,'El servicio de correo no está disponible. Inténtalo nuevamente.');
        try { $data=json_decode($response['body'],true,512,JSON_THROW_ON_ERROR); }
        catch (\JsonException $e) { throw new HttpException(502,'El servicio devolvió una respuesta no válida.'); }
        if (!is_array($data) || !isset($data['ok']) || !is_bool($data['ok'])) throw new HttpException(502,'El servicio devolvió una respuesta no válida.');
        // Compatibilidad con la API publicada antes de normalizar búsquedas vacías.
        if (!$data['ok'] && ($data['message']??'')==='No se encontraron correos') return ['ok'=>true,'total'=>0,'data'=>[]];
        if (!$data['ok']) throw new HttpException(502,'No se pudo consultar el buzón. Revisa su autorización e inténtalo nuevamente.');
        if (!isset($data['data']) || !is_array($data['data']) || !array_is_list($data['data'])) throw new HttpException(502,'El servicio devolvió una respuesta no válida.');
        foreach ($data['data'] as &$row) {
            if (!is_array($row) || !is_string($row['date'] ?? null) || strtotime($row['date']) === false) {
                throw new HttpException(502, 'El servicio devolvió registros no válidos.');
            }
            $endpoint = $parts['path'];
            if (in_array($endpoint, ['/api/netflix_otp.php', '/api/disney_otp.php'], true)
                && !is_string($row['codigo'] ?? null) && !is_int($row['codigo'] ?? null)) {
                throw new HttpException(502, 'El servicio devolvió un código no válido.');
            }
            if ($endpoint === '/api/netflix_link.php') {
                $link = $row['link'] ?? null;
                if (!is_string($link) || !filter_var($link, FILTER_VALIDATE_URL)
                    || !in_array(parse_url($link, PHP_URL_SCHEME), ['https', 'http'], true)) {
                    throw new HttpException(502, 'El servicio devolvió un enlace no válido.');
                }
                $row['nombre'] = is_string($row['nombre'] ?? null) ? $row['nombre'] : '';
            }
            if ($endpoint === '/api/buscar_correo.php') {
                foreach (['from', 'subject', 'body'] as $field) {
                    if (isset($row[$field]) && !is_string($row[$field])) {
                        throw new HttpException(502, 'El servicio devolvió un correo no válido.');
                    }
                }
            }
        }
        unset($row);
        return $data;
    }
    private function request(string $url): array
    {
        $ch=curl_init($url); $body=''; $tooLarge=false;
        curl_setopt_array($ch,[CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_HTTPHEADER=>['Accept: application/json'],CURLOPT_WRITEFUNCTION=>static function($ch,string $part)use(&$body,&$tooLarge){ if(strlen($body)+strlen($part)>2*1024*1024){$tooLarge=true;return 0;} $body.=$part;return strlen($part);}]);
        try {
            $ok=curl_exec($ch); $error=curl_errno($ch); $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
            if ($tooLarge) throw new HttpException(502,'La respuesta del servicio excede el tamaño permitido.');
            if ($ok===false) throw new HttpException($error===CURLE_OPERATION_TIMEDOUT?504:502,$error===CURLE_OPERATION_TIMEDOUT?'El servicio tardó demasiado. Inténtalo nuevamente.':'No se pudo conectar con el servicio de correo.');
            return ['status'=>$status,'body'=>$body];
        } finally { curl_close($ch); }
    }
}
