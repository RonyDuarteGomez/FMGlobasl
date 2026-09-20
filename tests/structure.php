<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap/app.php';
$count = 0;
function verifyStructure(bool $condition, string $label): void {
    global $count;
    if (!$condition) throw new RuntimeException($label);
    $count++;
}
$routes = require FM_ROOT . '/routes/web.php';
verifyStructure(count($routes) === 31, 'Conservar 27 rutas y añadir dashboard y permisos');
foreach ($routes as $url => $controller) {
    verifyStructure(is_file(FM_ROOT . '/public/' . $url), 'Entrada publica: ' . $url);
    verifyStructure(is_file(FM_ROOT . '/' . $controller), 'Controlador: ' . $url);
    verifyStructure(str_contains(file_get_contents(FM_ROOT . '/public/' . $url), var_export($url, true)), 'Despacho de la ruta: '.$url);
}
foreach (['CorreoConfig','CorreoService','GmailProvider','ImapProvider','NetflixLinkService','DisneyOtpService','NetflixOtpService','RegexHelper','StreamingRules','FMGlobal\\Repositories\\Database','FMGlobal\\Repositories\\UsageRepository','FMGlobal\\Repositories\\GmailTokenRepository'] as $class) {
    verifyStructure(class_exists($class), 'Autoload: '.$class);
}
$series = \FMGlobal\Services\Reports\DashboardData::series([
    ['fecha'=>'2026-09-18','streaming'=>1,'total_consultas'=>3],
    ['fecha'=>'2026-09-19','streaming'=>2,'total_consultas'=>5],
],[1,2]);
verifyStructure($series['values'][1] === [3,0] && $series['values'][2] === [0,5], 'Alinear series con dias sin consultas');
$empty=\FMGlobal\Services\Reports\DashboardData::series([],[1,2]);
verifyStructure($empty === ['labels'=>[], 'values'=>[1=>[],2=>[]]], 'Panel sin registros');
foreach (glob(FM_ROOT.'/public/assets/js/*.js') as $file) verifyStructure(!str_contains(file_get_contents($file),'<?'), 'JS sin PHP: '.basename($file));
foreach (['Gmail/authorize.php','Gmail/callback.php'] as $file) verifyStructure(str_contains(file_get_contents(FM_ROOT.'/app/Controllers/'.$file),'https://fmglobals.com/oauth2callback.php'), 'Retorno OAuth conservado');
verifyStructure(str_contains(file_get_contents(FM_ROOT.'/public/assets/js/link.js'),'https://apitoken-eeds.onrender.com/generate'),'API externa conservada');
foreach (['disney','netflix-link','netflix-otp','messages','public'] as $file) verifyStructure(str_contains(file_get_contents(FM_ROOT.'/app/Controllers/Consultations/'.$file.'.php'),'https://fmglobals.com/api/'),'API de consultas conservada');
verifyStructure(!is_dir(FM_ROOT.'/public/config') && !is_dir(FM_ROOT.'/public/vendor'), 'Archivos privados fuera de public');
echo "$count comprobaciones de estructura correctas.\n";

// Validar recursos literales de las plantillas y de la portada.
$templates = [FM_ROOT . '/public/index.html'];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(FM_ROOT . '/resources/views', FilesystemIterator::SKIP_DOTS)) as $entry) {
    if ($entry->getExtension() === 'php') $templates[] = $entry->getPathname();
}
$missingAssets = [];
foreach ($templates as $template) {
    preg_match_all('~(?:src|href)=[\'"](assets/[^\'"?<>]+)[\'"]~', file_get_contents($template), $matches);
    foreach ($matches[1] as $asset) if (!is_file(FM_ROOT . '/public/' . $asset)) $missingAssets[] = $asset;
}
if ($missingAssets) throw new RuntimeException('Recursos ausentes: ' . implode(', ', array_unique($missingAssets)));
echo "Recursos de plantillas comprobados.\n";
