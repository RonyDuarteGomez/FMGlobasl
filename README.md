# FMGlobals

Base importada desde la copia de producción. Aplicación PHP con MySQL,
integraciones Gmail/IMAP y consultas a la API publicada.

## Configuración

1. Ejecutar `composer install` para instalar las dependencias de `composer.lock`.
2. Copiar `config/private.example.php` a `config/private.php` y completar sus valores mediante un canal privado.
3. Incorporar el archivo OAuth autorizado en `config/credentials.json`; no versionarlo.
4. Configurar el acceso a la base de datos y disponer de su esquema y datos por separado. Los tokens Gmail se conservan en la base de datos, no en Git.
5. Mantener las rutas de API, servidor de correo y retorno OAuth correspondientes al entorno autorizado.

La base conserva las conexiones y reglas de la copia importada. Para poder
versionarla, las contraseñas y la clave OAuth incrustadas se trasladaron a
`config/private.php` sin cambiar sus valores. El código carga esa configuración
también en producción: incluir ese archivo privado al desplegar, sin subirlo al repositorio.

No se incluyen dependencias instaladas, credenciales, respaldos, logs ni volcados
de base de datos. En Apache, `config/.htaccess` impide descargar la configuración;
en otro servidor debe aplicarse una protección equivalente.
