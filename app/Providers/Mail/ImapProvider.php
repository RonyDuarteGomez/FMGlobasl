<?php
date_default_timezone_set(
    'America/Lima'
);

class ImapProvider
{
    private $hostname;

    public function __construct()
    {
        $this->hostname = '{mail.fmglobals.com:993/imap/ssl}INBOX';
    }

    // =====================================================
    // DECODIFICAR MIME UTF8
    // =====================================================

    private function decodeMime($texto)
    {
        $elementos = imap_mime_header_decode($texto);

        $resultado = '';

        foreach ($elementos as $elemento) {
            $resultado .= $elemento->text;
        }

        return $resultado;
    }

    // =====================================================
    // LIMPIAR HTML
    // =====================================================

    private function limpiarBody($body)
    {
        // Decodifica entidades HTML
        $body = html_entity_decode(
            $body,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        // Elimina etiquetas HTML
        $body = strip_tags($body);

        // Limpia espacios múltiples
        $body = preg_replace('/\s+/', ' ', $body);

        return trim($body);
    }

    // =====================================================
    // BUSCAR CORREOS
    // =====================================================

    public function buscarCorreos(
        $correo,
        $password,
        $minutes = 15
    ) {

        $resultado = [];

        // =====================================================
        // TIMEOUTS
        // =====================================================

        imap_timeout(IMAP_OPENTIMEOUT, 5);
        imap_timeout(IMAP_READTIMEOUT, 5);
        imap_timeout(IMAP_WRITETIMEOUT, 5);
        imap_timeout(IMAP_CLOSETIMEOUT, 5);

        // =====================================================
        // CONEXIÓN IMAP
        // =====================================================

        $inbox = @imap_open(
            $this->hostname,
            $correo,
            $password
        );

        if (!$inbox) {

            return [
                'ok' => false,
                'message' => 'No se pudo conectar al correo'
            ];
        }

        // =====================================================
        // OBTENER TODOS LOS CORREOS
        // =====================================================

        $emails = imap_search(
            $inbox,
            'ALL'
        );

        if (!$emails) {

            imap_close($inbox);

            return [
                'ok' => false,
                'message' => 'No se encontraron correos'
            ];
        }

        // =====================================================
        // MÁS RECIENTES PRIMERO
        // =====================================================

        rsort($emails);

        // =====================================================
        // TIEMPO LÍMITE
        // =====================================================

        $limiteTiempo = strtotime(
            "-{$minutes} minutes"
        );

        // =====================================================
        // RECORRER CORREOS
        // =====================================================

        foreach ($emails as $email_id) {

            // =====================================================
            // OVERVIEW
            // =====================================================

            $overview = imap_fetch_overview(
                $inbox,
                $email_id,
                0
            );

            if (empty($overview[0])) {
                continue;
            }

            $overview = $overview[0];

            // =====================================================
            // NORMALIZAR FECHA
            // =====================================================
            
            // LIMPIAR TIMEZONE EXTRA
            $dateOriginal =
                trim($overview->date ?? '');
            
            // SI NO HAY FECHA
            if (empty($dateOriginal)) {
                continue;
            }
            
            // LIMPIAR TIMEZONE EXTRA
            $dateLimpia = preg_replace(
                '/\s+\([^)]+\)$/',
                '',
                $dateOriginal
            );
            
            // VALIDAR PARSE
            try {
            
                $dateObject =
                    new DateTime($dateLimpia);
            
            } catch (Exception $e) {
            
                // FECHA INVÁLIDA
                continue;
            }
            
            // CONVERTIR A LIMA
            $dateObject->setTimezone(
                new DateTimeZone('America/Lima')
            );
            
            // TIMESTAMP REAL
            $fechaCorreo = $dateObject->getTimestamp() + (2 * 60 * 60);
            
            // =====================================================
            // VALIDAR TIEMPO REAL
            // =====================================================
            
            $limiteTiempo =
                time() - ($minutes * 60);
            
            if ($fechaCorreo < $limiteTiempo) {
                continue;
            }

            // =====================================================
            // SUBJECT
            // =====================================================

            $subjectCorreo = $this->decodeMime(
                $overview->subject ?? ''
            );

            // =====================================================
            // HEADER
            // =====================================================

            $header = imap_headerinfo(
                $inbox,
                $email_id
            );

            // =====================================================
            // BODY
            // =====================================================

            $body = "";

            $structure = imap_fetchstructure(
                $inbox,
                $email_id
            );

            // =====================================================
            // BODY SIMPLE
            // =====================================================

            if (!isset($structure->parts)) {

                $body = imap_body(
                    $inbox,
                    $email_id
                );

                if ($structure->encoding == 3) {
                    $body = base64_decode($body);
                }

                if ($structure->encoding == 4) {
                    $body = quoted_printable_decode($body);
                }

            } else {

                // =====================================================
                // MULTIPART
                // =====================================================

                foreach ($structure->parts as $part_num => $part) {

                    // TEXTO / HTML
                    if ($part->type == 0) {

                        $body = imap_fetchbody(
                            $inbox,
                            $email_id,
                            $part_num + 1
                        );

                        if ($part->encoding == 3) {
                            $body = base64_decode($body);
                        }

                        if ($part->encoding == 4) {
                            $body = quoted_printable_decode($body);
                        }

                        break;
                    }
                }
            }

            // =====================================================
            // BODY LIMPIO
            // =====================================================

            $bodyLimpio = $this->limpiarBody($body);

            // =====================================================
            // RESULTADO
            // =====================================================

            $resultado[] = [
                'subject' => $subjectCorreo,
                'from' => $header->fromaddress ?? '',
                'date' => date('Y-m-d H:i:s',$fechaCorreo),
                'timestamp' => $fechaCorreo,
                'body' => $bodyLimpio,
                'body_html' => $body
            ];
        }

        imap_close($inbox);

        return [
            'ok' => true,
            'total' => count($resultado),
            'data' => $resultado
        ];
    }
}