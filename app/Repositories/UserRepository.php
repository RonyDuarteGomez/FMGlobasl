<?php
namespace FMGlobal\Repositories;
final class UserRepository
{
    public function __construct(private \mysqli $db) {}
    private function one(string $sql, array $args): ?array { return $this->db->execute_query($sql, $args)->fetch_assoc(); }
    public function permissions(int $id): array { return (new PermissionRepository($this->db))->effective($id); }
    public function identity(int $id): ?array { return $this->one('SELECT u.id,u.usuario,u.estado,p.rol_id FROM usuarios u LEFT JOIN personal p ON p.usuario_id=u.id WHERE u.id=?', [$id]); }
    public function forLogin(string $name): ?array { return $this->one('SELECT u.*,p.rol_id,p.nombre,p.apellido_paterno,p.apellido_materno FROM usuarios u LEFT JOIN personal p ON p.usuario_id=u.id WHERE u.usuario=?', [$name]); }
    public function roles(): \mysqli_result { return $this->db->query('SELECT rol_id,rol_nombre FROM rol'); }
    public function roleExists(int $id): bool { return $this->one('SELECT rol_id FROM rol WHERE rol_id=?',[$id]) !== null; }
    public function list(): \mysqli_result
    {
        return $this->db->query('SELECT u.id AS usuario_id,u.usuario,u.estado,p.id AS personal_id,p.nombre,p.apellido_paterno,p.apellido_materno,p.correo,p.telefono,r.rol_nombre AS cargo FROM usuarios u LEFT JOIN personal p ON u.id=p.usuario_id LEFT JOIN rol r ON p.rol_id=r.rol_id WHERE u.id<>1 ORDER BY r.rol_id,p.nombre,p.apellido_paterno,p.apellido_materno DESC');
    }
    public function details(int $id, ?int $personalId = null): ?array
    {
        $sql='SELECT u.id AS usuario_id,u.usuario,u.estado,p.id AS personal_id,p.nombre,p.apellido_paterno,p.apellido_materno,p.correo,p.telefono,p.celular,p.cargo,p.rol_id FROM usuarios u JOIN personal p ON p.usuario_id=u.id WHERE u.id=?';
        return $this->one($sql.($personalId !== null?' AND p.id=?':''), $personalId !== null?[$id,$personalId]:[$id]);
    }
    public function create(string $name, string $hash): int
    {
        $this->db->execute_query('INSERT INTO usuarios(usuario,password_hash,estado,fecha_registro) VALUES(?,?,1,NOW())',[$name,$hash]);
        return $this->db->insert_id;
    }
    public function password(int $id, string $hash): void { $this->db->execute_query('UPDATE usuarios SET password_hash=? WHERE id=?',[$hash,$id]); }
    public function savePersonal(int $id, array $data, bool $new): void
    {
        $values=array_map(fn($key)=>$data[$key],['nombre','apellido_paterno','apellido_materno','correo','telefono','celular','cargo','rol_id']); $values[]=$id;
        $sql=$new?'INSERT INTO personal(nombre,apellido_paterno,apellido_materno,correo,telefono,celular,cargo,rol_id,usuario_id) VALUES(?,?,?,?,?,?,?,?,?)':'UPDATE personal SET nombre=?,apellido_paterno=?,apellido_materno=?,correo=?,telefono=?,celular=?,cargo=?,rol_id=? WHERE usuario_id=?';
        $this->db->execute_query($sql,$values);
    }
    public function toggle(int $id): int
    {
        $this->db->execute_query('UPDATE usuarios SET estado=IF(estado=1,0,1) WHERE id=?',[$id]);
        return (int)$this->identity($id)['estado'];
    }
    public function transaction(callable $operation): mixed
    {
        $this->db->begin_transaction();
        try { $guard=new PermissionRepository($this->db); $guard->lock(); $result=$operation(); $guard->ensureManager(); $guard->bump(); $this->db->commit(); return $result; }
        catch (\Throwable $e) { $this->db->rollback(); throw $e; }
    }
}
