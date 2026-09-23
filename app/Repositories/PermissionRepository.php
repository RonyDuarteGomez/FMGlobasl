<?php
namespace FMGlobal\Repositories;
use FMGlobal\Http\HttpException;
final class PermissionRepository
{
    public function __construct(private \mysqli $db) {}
    public function isSuperuser(int $userId): bool
    {
        return $userId===1 && (bool)$this->db->query("SELECT id FROM usuarios WHERE id=1 AND usuario='admin'")->num_rows;
    }
    public function effective(int $userId): array
    {
        $rows=$this->db->execute_query('SELECT p.code,COALESCE(up.allowed,rp.allowed,0) AS allowed FROM fm_permissions p JOIN personal pe ON pe.usuario_id=? LEFT JOIN fm_role_permissions rp ON rp.role_id=pe.rol_id AND rp.permission_code=p.code LEFT JOIN fm_user_permissions up ON up.user_id=pe.usuario_id AND up.permission_code=p.code',[$userId])->fetch_all(MYSQLI_ASSOC);
        $result=[];
        foreach ($rows as $row) $result[$row['code']]=(int)$row['allowed']===1;
        return $result;
    }
    public function settings(string $type,int $id): array
    {
        $table=$type==='role'?'fm_role_permissions':'fm_user_permissions';
        $column=$type==='role'?'role_id':'user_id';
        $rows=$this->db->execute_query("SELECT permission_code,allowed FROM $table WHERE $column=?",[$id])->fetch_all(MYSQLI_ASSOC);
        return array_column($rows,'allowed','permission_code');
    }
    public function lock(): int
    {
        return (int)$this->db->query('SELECT revision FROM fm_permission_lock WHERE id=1 FOR UPDATE')->fetch_assoc()['revision'];
    }
    public function revision(): int { return (int)$this->db->query('SELECT revision FROM fm_permission_lock WHERE id=1')->fetch_assoc()['revision']; }
    public function bump(): void { $this->db->query('UPDATE fm_permission_lock SET revision=revision+1 WHERE id=1'); }
    public function ensureManager(): void
    {
        $result=$this->db->query("SELECT u.id FROM usuarios u JOIN personal pe ON pe.usuario_id=u.id LEFT JOIN fm_role_permissions rp ON rp.role_id=pe.rol_id AND rp.permission_code='permissions.manage' LEFT JOIN fm_user_permissions up ON up.user_id=u.id AND up.permission_code='permissions.manage' WHERE u.estado=1 AND COALESCE(up.allowed,rp.allowed,0)=1 LIMIT 1");
        if (!$result->num_rows) throw new HttpException(409,'Debe quedar al menos un usuario activo con permiso para administrar permisos.');
    }
    public function save(string $type,int $id,array $values,int $actor,int $revision): void
    {
        $this->db->begin_transaction();
        try {
            if ($this->lock()!==$revision) throw new HttpException(409,'Los permisos cambiaron mientras editabas. Recarga y vuelve a intentarlo.');
            if (!(new UserRepository($this->db))->identity($actor) || (int)(new UserRepository($this->db))->identity($actor)['estado']!==1 || empty($this->effective($actor)['permissions.manage'])) throw new HttpException(403,'Ya no tienes permiso para esta operación.');
            $table=$type==='role'?'fm_role_permissions':'fm_user_permissions';
            $column=$type==='role'?'role_id':'user_id';
            $target=$type==='role'?$this->db->execute_query('SELECT rol_id FROM rol WHERE rol_id=?',[$id]):$this->db->execute_query('SELECT id FROM usuarios WHERE id=?',[$id]);
            if (!$target->num_rows) throw new HttpException(404,'Perfil o usuario no encontrado.');
            $before=$this->settings($type,$id);
            $this->db->execute_query("DELETE FROM $table WHERE $column=?",[$id]);
            foreach ($values as $code=>$value) if ($value!=='inherit') $this->db->execute_query("INSERT INTO $table($column,permission_code,allowed) VALUES(?,?,?)",[$id,$code,$value==='allow'?1:0]);
            $this->ensureManager();
            $this->db->execute_query('INSERT INTO fm_permission_audit(actor_id,target_type,target_id,before_json,after_json,created_at) VALUES(?,?,?,?,?,NOW())',[$actor,$type,$id,json_encode($before,JSON_THROW_ON_ERROR),json_encode($this->settings($type,$id),JSON_THROW_ON_ERROR)]);
            $this->bump();
            $this->db->commit();
        } catch (\Throwable $e) { $this->db->rollback(); throw $e; }
    }
}
