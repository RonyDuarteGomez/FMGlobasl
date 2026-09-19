<?php
namespace FMGlobal\Repositories;

final class GmailTokenRepository
{
    public function __construct(private \mysqli $connection) {}

    public function find(string $email): ?string
    {
        $stmt = $this->connection->prepare('SELECT refresh_token FROM gmail_tokens WHERE correo=? LIMIT 1');
        try {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            return $row['refresh_token'] ?? null;
        } finally { $stmt->close(); }
    }

    public function listAuthorized(): \mysqli_result
    {
        return $this->connection->query('SELECT id,correo,created_at,updated_at FROM gmail_tokens ORDER BY updated_at DESC');
    }

    public function save(string $email, string $token): void
    {
        // Mantener compatibilidad con el esquema actual, sin exigir una migracion.
        $existing = $this->find($email);
        $stmt = $this->connection->prepare($existing !== null
            ? 'UPDATE gmail_tokens SET refresh_token=? WHERE correo=?'
            : 'INSERT INTO gmail_tokens (refresh_token,correo) VALUES (?,?)');
        try { $stmt->bind_param('ss', $token, $email); $stmt->execute(); }
        finally { $stmt->close(); }
    }
}
