
<?php

date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conexion/conexion.php';

use Google\Client;
use Google\Service\Gmail;

class GmailProvider
{
    // =====================================================
    // LIMPIAR BODY
    // =====================================================

    private function limpiarBody($body)
    {
        $body = html_entity_decode(
            $body,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $body = strip_tags($body);

        $body = preg_replace('/\s+/', ' ', $body);

        return trim($body);
    }

    // =====================================================
    // DECODIFICAR BASE64URL
    // =====================================================

    private function decodeBody($data)
    {
        $data = str_replace(
            ['-', '_'],
            ['+', '/'],
            $data
        );

        return base64_decode($data);
    }

    // =====================================================
    // OBTENER BODY
    // =====================================================

    private function obtenerBody($payload)
    {
        if (!empty($payload->body->data)) {

            return $this->decodeBody(
                $payload->body->data
            );
        }

        if (!empty($payload->parts)) {

            foreach ($payload->parts as $part) {

                if (
                    $part->mimeType == 'text/html'
                    &&
                    !empty($part->body->data)
                ) {

                    return $this->decodeBody(
                        $part->body->data
                    );
                }

                if (
                    $part->mimeType == 'text/plain'
                    &&
                    !empty($part->body->data)
                ) {

                    return $this->decodeBody(
                        $part->body->data
                    );
                }

                if (!empty($part->parts)) {

                    $body = $this->obtenerBody($part);

                    if (!empty($body)) {

                        return $body;
                    }
                }
            }
        }

        return '';
    }

    // =====================================================
    // BUSCAR CORREOS
    // =====================================================

    public function buscarCorreos(
        $correo,
        $password = '',
        $minutes = 15
    ) {

        global $conexion;

        try {

            // =====================================================
            // CLIENT
            // =====================================================

            $client = new Client();

            $client->setAuthConfig(
                __DIR__ . '/../config/credentials.json'
            );

            $client->addScope(
                Gmail::GMAIL_READONLY
            );

            // =====================================================
            // OBTENER REFRESH TOKEN
            // =====================================================

            $sql = "
                SELECT refresh_token
                FROM gmail_tokens
                WHERE correo = ?
                LIMIT 1
            ";

            $stmt =
                $conexion->prepare(
                    $sql
                );

            $stmt->bind_param(
                "s",
                $correo
            );

            $stmt->execute();

            $resultado =
                $stmt->get_result();

            if (
                $resultado->num_rows === 0
            ) {

                return [
                    'ok' => false,
                    'message' => 'Correo no autorizado'
                ];
            }

            $row =
                $resultado->fetch_assoc();

            $refreshToken =
                $row['refresh_token'];

            $stmt->close();

            // =====================================================
            // ACCESS TOKEN
            // =====================================================

            $client->fetchAccessTokenWithRefreshToken(
                $refreshToken
            );

            // =====================================================
            // SERVICE
            // =====================================================

            $service = new Gmail($client);

            // =====================================================
            // QUERY
            // =====================================================

            $query = 'in:inbox';

            $messagesResponse =
                $service->users_messages->listUsersMessages(
                    'me',
                    [
                        'q' => $query,
                        'maxResults' => 50
                    ]
                );

            $messages =
                $messagesResponse->getMessages();

            $resultadoFinal = [];

            if (empty($messages)) {

                return [
                    'ok' => true,
                    'total' => 0,
                    'data' => []
                ];
            }

            // =====================================================
            // TIEMPO LÍMITE
            // =====================================================

            $limiteTiempo =
                time() - ($minutes * 60);

            foreach ($messages as $message) {

                $msg =
                    $service->users_messages->get(
                        'me',
                        $message->id,
                        ['format' => 'full']
                    );

                $payload =
                    $msg->getPayload();

                $headers =
                    $payload->getHeaders();

                $subject = '';
                $from = '';
                $date = '';

                foreach ($headers as $header) {

                    switch ($header->getName()) {

                        case 'Subject':

                            $subject =
                                $header->getValue();

                            break;

                        case 'From':

                            $from =
                                $header->getValue();

                            break;

                        case 'Date':

                            $date =
                                $header->getValue();

                            break;
                    }
                }

                // =====================================================
                // NORMALIZAR FECHA
                // =====================================================

                $dateLimpia = preg_replace(
                    '/\s+\([^)]+\)$/',
                    '',
                    $date
                );

                $dateObject =
                    new DateTime($dateLimpia);

                $dateObject->setTimezone(
                    new DateTimeZone(
                        'America/Lima'
                    )
                );

                $timestampCorreo =
                    $dateObject->getTimestamp();

                // =====================================================
                // VALIDAR MINUTOS
                // =====================================================

                if (
                    $timestampCorreo < $limiteTiempo
                ) {

                    continue;
                }

                // =====================================================
                // BODY
                // =====================================================

                $bodyHtml =
                    $this->obtenerBody(
                        $payload
                    );

                $body =
                    $this->limpiarBody(
                        $bodyHtml
                    );

                // =====================================================
                // RESULTADO
                // =====================================================

                $resultadoFinal[] = [

                    'subject' => $subject,

                    'from' => $from,

                    'date' => $dateObject->format(
                        'Y-m-d H:i:s'
                    ),

                    'timestamp' => $timestampCorreo,

                    'body' => $body,

                    'body_html' => $bodyHtml
                ];
            }

            return [

                'ok' => true,

                'total' => count(
                    $resultadoFinal
                ),

                'data' => $resultadoFinal
            ];

        } catch (Exception $e) {

            return [

                'ok' => false,

                'message' => $e->getMessage()
            ];
        }
    }
}

