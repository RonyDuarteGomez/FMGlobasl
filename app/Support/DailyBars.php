<?php
namespace FMGlobal\Support;
final class DailyBars
{
    public static function render(array $series): string
    {
        $escape=static fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
        $max=max(array_merge([1],array_map('intval',array_values($series))));
        $step=280/max(1,count($series));
        $svg='<svg class="daily-bars" viewBox="0 0 300 110" role="img" aria-label="Actividad diaria de los últimos 30 días"><title>Actividad diaria: '.array_sum($series).' registros en 30 días</title><path class="daily-grid" d="M10 15H290 M10 50H290 M10 85H290"/>';
        $i=0;
        foreach($series as $day=>$total){
            $height=70*max(0,(int)$total)/$max;
            if($height>0) $svg.='<rect class="daily-bar" x="'.round(10+$i*$step,2).'" y="'.round(85-$height,2).'" width="'.round(max(1,$step-3),2).'" height="'.round($height,2).'" rx="2"><title>'.$escape($day).': '.(int)$total.'</title></rect>';
            $i++;
        }
        if(array_sum($series)===0) $svg.='<text class="daily-empty" x="150" y="49" text-anchor="middle">Sin actividad en este período</text>';
        if($series) $svg.='<text class="daily-date" x="10" y="104">'.$escape(substr((string)array_key_first($series),5)).'</text><text class="daily-date" x="290" y="104" text-anchor="end">'.$escape(substr((string)array_key_last($series),5)).'</text>';
        return $svg.'</svg>';
    }
}