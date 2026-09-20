<?php
namespace FMGlobal\Services\Mail;
use FMGlobal\Contracts\MailProvider;
final class ProviderRegistry
{
    private array $factories;
    public function __construct(?array $factories = null)
    {
        $this->factories=$factories??['gmail'=>fn()=>new \GmailProvider(),'imap'=>fn()=>new \ImapProvider()];
    }
    public function resolve(string $name): MailProvider
    {
        if (!isset($this->factories[$name])) throw new \FMGlobal\Http\HttpException(422,'Proveedor de correo no válido.');
        $provider=($this->factories[$name])();
        if (!$provider instanceof MailProvider) throw new \LogicException('El proveedor debe implementar MailProvider.');
        return $provider;
    }
}
