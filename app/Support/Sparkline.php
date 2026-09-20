<?php
namespace FMGlobal\Support;
final class Sparkline
{
    public static function render(array $series): string
    {
        $values=array_values($series); $max=max(1,...$values); $count=count($values);$points=[];
        foreach($values as $i=>$value) $points[]=round(4+($count>1?$i/($count-1):0)*292,2).','.round(66-((int)$value/$max)*58,2);
        return '<svg class="sparkline" viewBox="0 0 300 74" role="img" aria-label="Tendencia de consultas de los últimos 30 días"><polyline points="'.implode(' ',$points).'" /></svg>';
    }
}
