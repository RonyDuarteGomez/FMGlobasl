<?php


class CorreoService
{
    public function buscarCorreos(
        $proveedor,
        $correo,
        $password,
        $minutes = 30
    ) {

        // =====================================================
        // DETECTAR PROVEEDOR AUTOMÁTICAMENTE
        // =====================================================

        if (
            stripos(
                $correo,
                '@gmail.com'
            ) !== false
        ) {

            $provider =
                new GmailProvider();

        } else {

            $provider =
                new ImapProvider();
        }

        // =====================================================
        // BUSCAR CORREOS
        // =====================================================

        return $provider->buscarCorreos(
            $correo,
            $password,
            $minutes
        );
    }
}