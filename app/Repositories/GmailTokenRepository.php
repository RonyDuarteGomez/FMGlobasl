<?php
namespace FMGlobal\Repositories;

final class GmailTokenRepository
{
    public function __construct(private \mysqli $connection) {}

    public function find(string $email): ?string
    {
        $row=$this->connection->execute_query('SELECT refresh_token FROM gmail_tokens WHERE correo=? AND activa=1 AND token_valido=1 LIMIT 1',[$email])->fetch_assoc();
        $token=$row['refresh_token']??null;
        return $token!==null && trim($token)!=='' ? $token : null;
    }

    public function listAuthorized(): \mysqli_result
    {
        return $this->connection->query("SELECT id,correo,created_at,token_valido AS token_active FROM gmail_tokens WHERE activa=1 ORDER BY updated_at DESC");
    }

    public function invalidate(string $email, string $token): void
    {
        // No invalidar un token renovado mientras habia una consulta en curso.
        $this->connection->execute_query('UPDATE gmail_tokens SET token_valido=0 WHERE correo=? AND BINARY refresh_token=BINARY ? AND activa=1',[$email,$token]);
    }

    public function deactivate(int $id): void
    {
        $this->connection->execute_query('UPDATE gmail_tokens SET activa=0 WHERE id=?',[$id]);
    }

    public function save(string $email, string $token): void
    {
        // Buscar tambien las cuentas dadas de baja para reactivarlas al autorizar.
        $existing=$this->connection->execute_query('SELECT id FROM gmail_tokens WHERE correo=? LIMIT 1',[$email])->fetch_assoc();
        $sql=$existing
            ? 'UPDATE gmail_tokens SET refresh_token=?,activa=1,token_valido=1 WHERE correo=?'
            : 'INSERT INTO gmail_tokens (refresh_token,correo,activa,token_valido) VALUES (?,?,1,1)';
        $this->connection->execute_query($sql,[$token,$email]);
    }
}