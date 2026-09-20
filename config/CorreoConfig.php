<?php

class CorreoConfig
{
    public static function getConfig(
        $correo
    ) {

        $correo =
            strtolower(trim($correo));

        // =====================================================
        // GMAIL
        // =====================================================

        if (
            str_contains(
                $correo,
                '@gmail.com'
            )
        ) {

            return [

                'proveedor' => 'gmail',

                // GMAIL NO USA PASSWORD
                'password' => '',

                // MINUTOS CONSULTA
                'minutes' => 15
            ];
        }

        // =====================================================
        // IMAP / DOMINIO PROPIO
        // =====================================================

        return [

            'proveedor' => 'imap',

            // PASSWORD TEMPORAL
            // MÁS ADELANTE:
            // CONSULTAR DESDE BD
            'password' => (require __DIR__ . '/private.php')['imap_password'],

            // MINUTOS CONSULTA
            'minutes' => 15
        ];
    }
}
