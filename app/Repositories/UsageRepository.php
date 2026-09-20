<?php
namespace FMGlobal\Repositories;

final class UsageRepository
{
    public function __construct(private \mysqli $connection) {}

    public function totalsByOperation(array $operations): array
    {
        $ids = implode(',', array_map('intval', $operations));
        if ($ids === '') return [];
        return $this->connection->query("SELECT DATE(fecha) AS fecha, streaming, COUNT(*) AS total_consultas FROM uso_servicio WHERE streaming IN ($ids) GROUP BY DATE(fecha), streaming ORDER BY fecha ASC")->fetch_all(MYSQLI_ASSOC);
    }

    public function register(string $email, int $count, ?int $userId, int $operation): void
    {
        $user = null;
        if ($userId !== null) {
            $identity = $this->connection->execute_query('SELECT usuario FROM usuarios WHERE id=?', [$userId])->fetch_assoc();
            if (!$identity) throw new \InvalidArgumentException('Usuario de consulta inexistente.');
            $user = $identity['usuario'];
        }
        $stmt = $this->connection->prepare('INSERT INTO uso_servicio (correo,num_urls,fecha,usuario,usuario_id,streaming) VALUES (?,?,NOW(),?,?,?)');
        try {
            $stmt->bind_param('sisii', $email, $count, $user, $userId, $operation);
            $stmt->execute();
        } finally {
            $stmt->close();
        }
    }
}
