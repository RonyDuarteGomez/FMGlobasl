<?php

require_once __DIR__ . '/CorreoService.php';

require_once __DIR__ . '/../helpers/StreamingRules.php';

require_once __DIR__ . '/../helpers/RegexHelper.php';

class NetflixLinkService
{
    public function buscarLinks(
        $proveedor,
        $correo,
        $password = '',
        $minutes = 15
    ) {

        // =====================================================
        // OBTENER CORREOS
        // =====================================================

        $correoService =
            new CorreoService();

        $response =
            $correoService->buscarCorreos(
                $proveedor,
                $correo,
                $password,
                $minutes
            );
            
    

        // =====================================================
        // VALIDAR RESPONSE
        // =====================================================

        
        if (
            !$response['ok']
            ||
            empty($response['data'])
        ) {

            return [

                'ok' => false,

                'message' =>
                    'No se encontraron correos'
            ];
        }

        // =====================================================
        // SUBJECTS NETFLIX
        // =====================================================

        $subjectsNetflix =
            StreamingRules::getNetflixLinkSubjects();

        $resultado = [];

        // =====================================================
        // RECORRER CORREOS
        // =====================================================

        foreach (
            $response['data']
            as $correoData
        ) {

            $subject =
                $correoData['subject'] ?? '';

            // =====================================================
            // VALIDAR SUBJECT
            // =====================================================

            $coincide =
                RegexHelper::subjectCoincide(
                    $subject,
                    $subjectsNetflix
                );

            if (!$coincide) {
                continue;
            }

            // =====================================================
            // EXTRAER LINK
            // =====================================================

            $link =
                RegexHelper::extraerNetflixLink(
                    $correoData['body_html']
                );

            if (empty($link)) {
                continue;
            }
            
            // =====================================================
            // EXTRAER NOMBRE
            // =====================================================
            
            $nombre =
                RegexHelper::extraerNombreNetflix(
                    $correoData['body']
                );

            // =====================================================
            // RESULTADO
            // =====================================================

            $resultado[] = [

                'subject' =>
                    $correoData['subject'],

                'from' =>
                    $correoData['from'],

                'date' =>
                    $correoData['date'],
                    
                'nombre' => 
                    $nombre,
                
                'codigo' =>
                    null,

                'link' =>
                    $link
            ];
        }

        // =====================================================
        // RESPONSE FINAL
        // =====================================================

        return [

            'ok' => true,

            'total' => count($resultado),

            'data' => $resultado
        ];
    }
}