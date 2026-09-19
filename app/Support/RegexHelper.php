<?php

class RegexHelper
{
    // =====================================================
    // NORMALIZAR TEXTO
    // =====================================================

    public static function normalizarTexto($texto)
    {
        $texto = mb_strtolower(
            $texto,
            'UTF-8'
        );

        $texto = iconv(
            'UTF-8',
            'ASCII//TRANSLIT',
            $texto
        );

        return trim($texto);
    }

    // =====================================================
    // SUBJECT COINCIDE
    // =====================================================

    public static function subjectCoincide(
        $subjectCorreo,
        $subjectsValidos
    ) {

        $subjectCorreo =
            self::normalizarTexto(
                $subjectCorreo
            );

        foreach ($subjectsValidos as $subjectValido) {

            $subjectValido =
                self::normalizarTexto(
                    $subjectValido
                );

            if (
                str_contains(
                    $subjectCorreo,
                    $subjectValido
                )
            ) {
                return true;
            }
        }

        return false;
    }

    // =====================================================
    // EXTRAER LINK NETFLIX
    // =====================================================

    public static function extraerNetflixLink(
    $html
    ) {
    
        preg_match(
            '/https:\/\/www\.netflix\.com\/account\/travel\/verify[^"\s<]+/i',
            $html,
            $matches
        );
    
        $link = $matches[0] ?? null;
    
        if ($link) {
    
            $link = preg_replace(
                '/[\]\s]+$/',
                '',
                $link
            );
        }
    
        return $link;
    }

    // =====================================================
    // EXTRAER OTP GENERAL
    // =====================================================

    public static function extraerOtp(
        $texto
    ) {

        preg_match(
            '/\b(\d{4,8})\b/',
            $texto,
            $matches
        );

        return $matches[1] ?? null;
    }

    // =====================================================
    // EXTRAER OTP DISNEY
    // =====================================================
    /*
    public static function extraerDisneyOtp($texto) 
    {
    
        $texto = html_entity_decode(
            $texto,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
    
        preg_match(
            '/\b(\d{6})\b/u',
            $texto,
            $matches
        );
    
        return $matches[1] ?? null;
    }
    */
    public static function extraerDisneyOtp(
    $texto
) {

    $texto = html_entity_decode(
        $texto,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    // limpiar html
    $texto = strip_tags($texto);

    // limpiar espacios
    $texto = preg_replace(
        '/\s+/',
        ' ',
        $texto
    );

    // buscar OTP real Disney
    preg_match(
        '/(\d{6})\s+Si no lo solicitaste/i',
        $texto,
        $matches
    );

    return $matches[1] ?? null;
}
    
    // =====================================================
// EXTRAER NETFLIX OTP
// =====================================================

public static function extraerNetflixOtp(
    $texto
) {

    $texto = html_entity_decode(
        $texto,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    preg_match(
        '/\b(\d{4})\b/u',
        $texto,
        $matches
    );

    return $matches[1] ?? null;
}
    // =====================================================
    // EXTRAER LINKS GENERALES
    // =====================================================

    public static function extraerLinks(
    $html
    ) {
    
        preg_match_all(
            '/https?:\/\/[^\s"<]+/i',
            $html,
            $matches
        );
    
        $links = $matches[0] ?? [];
    
        $links = array_map(
    
            function ($link) {
    
                return preg_replace(
                    '/[\]\s]+$/',
                    '',
                    $link
                );
            },
    
            $links
        );
    
        return $links;
    }
    
    // =====================================================
    // EXTRAER NOMBRE NETFLIX
    // =====================================================
    
    public static function extraerNombreNetflix($texto) 
    {
        
    
        $texto = html_entity_decode(
            $texto,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
    
        $patterns = [
    
            // ESPAÑOL
            '/Hola,\s*(.+?):/iu',
    
            // INGLÉS
            '/Hi,\s*(.+?):/iu',
    
            // PORTUGUÉS
            '/Olá,\s*(.+?):/iu',
    
            // ITALIANO
            '/Ciao,\s*(.+?):/iu'
        ];
    
        foreach ($patterns as $pattern) {
    
            preg_match(
                $pattern,
                $texto,
                $matches
            );
    
            if (!empty($matches[1])) {
    
                return trim(
                    $matches[1]
                );
            }
        }
    
        return null;
    }
}