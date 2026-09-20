<?php
namespace FMGlobal\Services\Auth;
final class LoginService
{
    public function __construct(private \FMGlobal\Repositories\UserRepository $users) {}
    public function authenticate(string $username, string $password): ?array
    {
        $user=$this->users->forLogin($username);
        // Hash de relleno: verificar tambien cuando el usuario no existe.
        $hash=$user['password_hash']??'$2y$10$pZldnpCwfJfAd7aULX.0e.nIfK1yqSd9av3uUzIPD5hM1UiUhTzqG';
        $valid=password_verify($password,$hash);
        if (!$user || !$valid || (int)$user['estado']!==1 || !in_array((int)$user['rol_id'],[1,2,3],true)) return null;
        // La restricción horaria del login permanece desactivada por decisión existente.
        return $user;
    }
}
