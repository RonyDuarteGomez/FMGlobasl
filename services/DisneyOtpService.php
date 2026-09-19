<?php

require_once __DIR__ . '/CorreoService.php';

require_once __DIR__ . '/../helpers/StreamingRules.php';

require_once __DIR__ . '/../helpers/RegexHelper.php';

class DisneyOtpService
{
    public function buscarOtp(
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
        // SUBJECTS DISNEY
        // =====================================================

        $subjectsDisney =
            StreamingRules::getDisneyOtpSubjects();
        
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
                    $subjectsDisney
                );

            if (!$coincide) {
                continue;
            }

            // =====================================================
            // EXTRAER OTP
            // =====================================================

            $codigo =
                RegexHelper::extraerDisneyOtp(
                    $correoData['body']
                );

            if (empty($codigo)) {
                continue;
            }

            // =====================================================
            // RESULTADO
            // =====================================================

            $resultado[] = [

                'date' =>
                    $correoData['date'],

                'from' =>
                    $correoData['from'],

                'nombre' =>
                    null,

                'codigo' =>
                    $codigo,

                'link' =>
                    null
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