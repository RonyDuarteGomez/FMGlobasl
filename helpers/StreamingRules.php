<?php

class StreamingRules
{
    // =====================================================
    // SUBJECTS NETFLIX LINK
    // =====================================================

    public static function getNetflixLinkSubjects()
    {
        return [
    
            // ESPAÑOL
            'Tu código de acceso temporal de Netflix',
    
            // INGLÉS
            'Your temporary Netflix access code',
    
            // PORTUGUÉS
            'Seu código de acesso temporário da Netflix',
    
            // ITALIANO
            'Il tuo codice di accesso temporaneo Netflix'
        ];
    }
    
    // =====================================================
    // EXTRAER NOMBRE NETFLIX
    // =====================================================
    
    public static function extraerNombreNetflix($html) 
    {
    
        $patterns = [
    
            // ESPAÑOL
            '/Hola,\s*(.*?):/i',
    
            // INGLÉS
            '/Hi,\s*(.*?):/i',
    
            // PORTUGUÉS
            '/Olá,\s*(.*?):/i',
    
            // ITALIANO
            '/Ciao,\s*(.*?):/i'
        ];
    
        foreach ($patterns as $pattern) {
    
            preg_match(
                $pattern,
                strip_tags($html),
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

    // =====================================================
    // SUBJECTS NETFLIX OTP
    // =====================================================

    public static function getNetflixOtpSubjects()
    {
        return [
    
            // ESPAÑOL
            'Tu código de inicio de sesión',
    
            // INGLÉS
            'Your sign-in code',
    
            // PORTUGUÉS
            'Seu código de acesso',
    
            // ITALIANO
            'Il tuo codice di accesso'
        ];
    }

    // =====================================================
    // SUBJECTS DISNEY OTP
    // =====================================================

    
    public static function getDisneyOtpSubjects()
    {
        return [
            

            // ESPAÑOL
            'Tu código de acceso único para Disney+',

            // INGLÉS
            'Your Disney+ one-time passcode',

            // PORTUGUÉS
            'Seu código de acesso único do Disney+',

            // ITALIANO
            'Il tuo codice monouso Disney+'
        ];
    }
    /*
    public static function getDisneyOtpSubjects()
{
    return ['Test'];
}*/

    // =====================================================
    // DETECTAR IDIOMA POR SUBJECT
    // =====================================================

    public static function detectarIdioma($subject)
    {
        $subject = mb_strtolower(
            $subject,
            'UTF-8'
        );

        // ESPAÑOL
        if (
            str_contains($subject, 'código')
            ||
            str_contains($subject, 'paso')
            ||
            str_contains($subject, 'hogar')
        ) {
            return 'es';
        }

        // INGLÉS
        if (
            str_contains($subject, 'code')
            ||
            str_contains($subject, 'step')
            ||
            str_contains($subject, 'household')
        ) {
            return 'en';
        }

        // PORTUGUÉS
        if (
            str_contains($subject, 'código')
            ||
            str_contains($subject, 'residência')
        ) {
            return 'pt';
        }

        // ITALIANO
        if (
            str_contains($subject, 'codice')
            ||
            str_contains($subject, 'passaggio')
            ||
            str_contains($subject, 'nucleo')
        ) {
            return 'it';
        }

        return 'unknown';
    }
}