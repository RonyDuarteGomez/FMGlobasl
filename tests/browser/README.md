# Pruebas de navegador

`php tests/backend-integration.php --browser` crea usuarios y base temporales, inicia PHP y recorre la aplicación real en Chrome headless. Verifica login, menús, URLs limpias, atrás/adelante, selección de permisos tras recarga, clientes, Spotify, beneficiarios, caídas/restauración y aprobación del navegador externo. Detecta excepciones JavaScript. No llama a las APIs de producción.

Requisitos locales actuales: PHP/XAMPP, Node 24 y Chrome en las rutas usadas en el ejecutor. El servidor usa tests/helpers/http-router.php para reproducir las rutas limpias de Apache. Cada rol usa un contexto de navegador independiente; la base temporal se elimina al terminar.

Los fixtures build-*.php y users-pagination.html prueban interacciones aisladas con datos ficticios. Los archivos temporales están fuera del repositorio. Al ejecutar Chrome por PowerShell usar pipeline para esperar su salida y perfil independiente.

Fixtures antiguos clean-urls.html y permissions-clean-url.html reemplazados por navegación real (dependían de un servidor externo no incluido). link-counter.html retirado porque llamaba funciones eliminadas y exigía no contar fallos, contrario al contrato vigente. El conteo, identidad e idempotencia están cubiertos en tests/link-accounts.php y tests/evolution.php.
