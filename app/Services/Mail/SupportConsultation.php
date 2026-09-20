<?php
namespace FMGlobal\Services\Mail;
use FMGlobal\Services\Http\MailApiClient;
use FMGlobal\Repositories\UsageRepository;
final class SupportConsultation
{
    public function __construct(private MailApiClient $client, private UsageRepository $usage) {}
    public function search(string $url,string $email,string $user): array
    {
        $data=$this->client->get($url);
        // Una consulta valida cuenta incluso si el buzon no devuelve correos.
        $this->usage->register($email,count($data['data']),$user,6);
        return $data;
    }
}