<?php
namespace FMGlobal\Services\Reports;
use FMGlobal\Repositories\ActivityRepository;
use FMGlobal\Security\ActivityScope;
final class PersonalDashboard
{
    public function __construct(private ActivityRepository $activity) {}
    public function build(array $permissions,int $userId,bool $administrator=false): array
    {
        $visible=ActivityScope::visible($permissions);
        $user=ActivityScope::user($permissions,$userId,$administrator);
        $cards=[];
        foreach([
            ['reports.public','Consultas clientes',[1,2],'reportePublico',true],
            ['services.advisor','Consultas asesores',[3,4,5],'asesorMenu',true],
            ['services.support','Consultas soporte',[6],'soporteMenu',false],
        ] as [$permission,$label,$operations,$destination,$mix]) {
            if (empty($permissions[$permission]) && empty($permissions[match($destination){'reportePublico'=>'services.activation','asesorMenu'=>'reports.internal','soporteMenu'=>'reports.support'}])) continue;
            $card=['title'=>$label,'destination'=>$destination,'action'=>'Ver reporte detallado','reportUrl'=>match($destination){'reportePublico'=>'inicio.php','asesorMenu'=>'dashboard.php?report=advisor','soporteMenu'=>'dashboard.php?report=support','Link'=>'dashboard.php?report=links'},'primary'=>$mix,'kind'=>'activity','visible'=>$visible,'links'=>$operations===[7]];
            $card['canReport']=!empty($permissions[match($destination){'asesorMenu'=>'reports.internal','soporteMenu'=>'reports.support',default=>'reports.public'}]);
            if ($visible) {
                $card['summary']=$this->activity->summary($operations,$user);
                if ($mix) $card['mix']=$this->activity->platforms($operations,$user);
                else $card['series']=$this->activity->daily($operations,$user);
                if ($destination==='asesorMenu') $card['breakdown']=$this->activity->advisorBreakdown($user);
            }
            $cards[]=$card;
        }
        return ['cards'=>$cards,'global'=>$administrator,'visible'=>$visible,
            'activation'=>$this->activity->publicAvailability(),
            'showActivation'=>!empty($permissions['services.activation']),
            'summary'=>$visible?$this->activity->summary(ActivityScope::operations($permissions),$user):null];
    }
}