<?php
use FMGlobal\Services\Mail\ProviderRegistry;
use FMGlobal\Support\Input;
class CorreoService
{
    public function __construct(private ?ProviderRegistry $registry = null) { $this->registry ??= new ProviderRegistry(); }
    public function buscarCorreos($proveedor, $correo, $password, $minutes = 30): array
    {
        $correo=Input::email(['correo'=>$correo]);
        $name=Input::text(['proveedor'=>$proveedor],'proveedor',32,true);
        if (!is_string($password)) throw new \FMGlobal\Http\HttpException(422,'Contraseña de correo no válida.');
        if (!is_scalar($minutes) || filter_var($minutes,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1440]])===false) throw new \FMGlobal\Http\HttpException(422,'El intervalo debe estar entre 1 y 1440 minutos.');
        return $this->registry->resolve(strtolower($name))->buscarCorreos($correo,$password,(int)$minutes);
    }
}
