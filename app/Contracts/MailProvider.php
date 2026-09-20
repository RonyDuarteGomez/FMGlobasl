<?php
namespace FMGlobal\Contracts;
interface MailProvider
{
    /** @return array{ok: bool, data?: array, total?: int, message?: string} */
    public function buscarCorreos($correo, $password, $minutes = 15): array;
}
