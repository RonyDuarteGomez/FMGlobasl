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

    public function register(string $email, int $count, ?string $user, int $operation): void
    {
        $stmt = $this->connection->prepare('INSERT INTO uso_servicio (correo,num_urls,fecha,usuario,streaming) VALUES (?,?,NOW(),?,?)');
        try {
            $stmt->bind_param('sisi', $email, $count, $user, $operation);
            $stmt->execute();
        } finally {
            $stmt->close();
        }
    }
}
