<?php
namespace FMGlobal\Services\Users;
use FMGlobal\Http\HttpException;
use FMGlobal\Support\Input;
use FMGlobal\Repositories\UserRepository;
use FMGlobal\Repositories\ScheduleRepository;
final class UserService
{
    public function __construct(private UserRepository $users, private ScheduleRepository $schedules) {}
    public function save(array $input, string $actor, int $actorId): int
    {
        $id=isset($input['usuario_id']) && $input['usuario_id']!==''?Input::id($input,'usuario_id'):null;
        $this->authorizeTarget($id,$actorId);
        $old=$id?$this->users->details($id):null;
        if ($id && !$old) throw new HttpException(404,'Usuario no encontrado.');
        foreach(['usuario'=>'El usuario','clave'=>'La contraseña','telefono'=>'El teléfono'] as $field=>$label){
            if(array_key_exists($field,$input)&&is_string($input[$field])&&preg_match('/[\s\p{Z}]/u',$input[$field]))throw new HttpException(422,$label.' no debe contener espacios en blanco.');
        }
        $data=[];
        foreach(['nombre'=>100,'apellido_paterno'=>100,'apellido_materno'=>100,'correo'=>100,'telefono'=>20,'celular'=>20,'cargo'=>50] as $key=>$max) {
            // Los campos ausentes no borran datos guardados por otra pantalla.
            $data[$key]=array_key_exists($key,$input)?Input::text($input,$key,$max,$key==='nombre'):($old[$key]??'');
        }
        if ($data['nombre']==='') throw new HttpException(422,'Debe ingresar un nombre.');
        if ($data['apellido_paterno']==='') throw new HttpException(422,'Debe ingresar el apellido paterno.');
        Input::email($data,'correo',false);
        $data['rol_id']=Input::id($input,'rol');
        if (!$this->users->roleExists($data['rol_id'])) throw new HttpException(422,'Rol no válido.');
        if ((!$id || (int)$old['rol_id']!==$data['rol_id']) && $actorId!==0 && empty($this->users->permissions($actorId)['permissions.manage'])) throw new HttpException(403,'Asignar perfiles requiere permiso para administrar permisos.');
        $password=Input::text($input,'clave',72,false,false);
        if (strlen($password)>72) throw new HttpException(422,'La contraseña supera los 72 bytes permitidos.');
        $name=$id?$old['usuario']:Input::text($input,'usuario',50,true,false);
        if (!$id && preg_match('/[\\s\\p{Z}]/u',$name)) throw new HttpException(422,'El usuario debe ser una sola palabra, sin espacios.');
        if (!$id && $password==='') throw new HttpException(422,'Debe ingresar una contraseña para un nuevo usuario.');
        $hours=[];
        foreach(ScheduleService::DAYS as $n=>$day) {
            if (!$id) $hours[$day]=$n===7?['00:00:00','00:00:00']:['09:00:00','19:00:00'];
            elseif (array_key_exists('hora_inicio_'.$n,$input) || array_key_exists('hora_fin_'.$n,$input)) throw new HttpException(422,'Los horarios no se administran desde Usuarios.');
        }
        if (!$id && $this->users->forLogin($name)) throw new HttpException(409,'El usuario ya existe. Ingrese uno nuevo.');
        try {
            return $this->users->transaction(function() use($id,$name,$password,$data,$hours,$actor,$actorId) {
                $this->authorizeTarget($id,$actorId);
                if ($actorId!==0 && (!$id || (int)$this->users->details($id)['rol_id']!==$data['rol_id']) && empty($this->users->permissions($actorId)['permissions.manage'])) throw new HttpException(403,'Asignar perfiles requiere permiso para administrar permisos.');
                $new=$id===null;
                $savedId=$id??$this->users->create($name,password_hash($password,PASSWORD_DEFAULT));
                if (!$new && $password!=='') $this->users->password($savedId,password_hash($password,PASSWORD_DEFAULT));
                $this->users->savePersonal($savedId,$data,$new);
                foreach($hours as $day=>[$start,$end]) $this->schedules->saveDay($savedId,$day,$start,$end,$actor);
                return $savedId;
            });
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode()===1062) throw new HttpException(409,'El usuario ya existe.');
            throw $e;
        }
    }
    public function toggle(int $id, int $actorId): int
    {
         $this->authorizeTarget($id, $actorId);
        if ($id===$actorId) throw new HttpException(409,'No puedes desactivar tu propia cuenta.');
        if (!$this->users->identity($id)) throw new HttpException(404,'Usuario no encontrado.');
        return $this->users->transaction(function() use($id,$actorId) { $this->authorizeTarget($id,$actorId); return $this->users->toggle($id); });
    }
    private function authorizeTarget(?int $id,int $actorId): void
    {
        // Solamente el sembrado local de pruebas usa actor 0, nunca una solicitud HTTP.
        if ($actorId===0 && PHP_SAPI==='cli') return;
        $actor=$this->users->identity($actorId);
        $permissions=$this->users->permissions($actorId);
        if (!$actor || (int)$actor['estado']!==1 || empty($permissions['users.manage'])) throw new HttpException(403,'No tienes permiso para administrar usuarios.');
        if ($id===null || !empty($permissions['permissions.manage'])) return;
        foreach($this->users->permissions($id) as $code=>$allowed) if ($allowed && empty($permissions[$code])) throw new HttpException(403,'No puedes modificar una cuenta con accesos superiores a los tuyos.');
    }
}
