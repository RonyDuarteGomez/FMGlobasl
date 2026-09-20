<?php




class NetflixOtpService
{
    public function __construct(private ?CorreoService $correoService = null) { $this->correoService ??= new CorreoService(); }

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
            $this->correoService;

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

        if (!$response['ok']) return $response;
        if (empty($response['data'])) return ['ok'=>true,'total'=>0,'data'=>[]];

        // =====================================================
        // SUBJECTS NETFLIX OTP
        // =====================================================

        $subjectsNetflix =
            StreamingRules::getNetflixOtpSubjects();

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
            // EXTRAER OTP
            // =====================================================

            $codigo =
                RegexHelper::extraerNetflixOtp(
                    $correoData['body']
                );

            if (empty($codigo)) {
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

                'date' =>
                    $correoData['date'],

                'from' =>
                    $correoData['from'],

                'nombre' =>
                    $nombre,

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
