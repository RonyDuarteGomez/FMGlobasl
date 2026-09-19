# Despliegue de la estructura modular

## Antes de publicar

1. Guardar el release y respaldo de base de datos actuales por separado.
2. Preparar la rama refactor/estructura-modular en un directorio nuevo; no subir
   archivos sobre una mezcla de la estructura antigua y la nueva.
3. Ejecutar composer install con composer.lock. No actualizar dependencias como
   parte de esta migración.
4. Incorporar config/private.php y config/credentials.json existentes por un canal
   privado. No reemplazar sus valores por los ejemplos del repositorio.
5. Mantener la base existente y todas sus tablas; esta reforma no necesita DDL.
6. Configurar el DocumentRoot en la carpeta public/ del release nuevo.
7. Verificar login, roles, mantenimiento Gmail, callbacks, consultas, enlaces,
   resultados e historial antes de cambiar el tráfico de producción.

## Hosting con raíz web fija

La .htaccess de la raíz redirige internamente las peticiones a public/ y deniega
el acceso a app, config, bootstrap, resources, routes, vendor y almacenamiento.
Esto requiere Apache con mod_rewrite y AllowOverride habilitados. Se comprobó
este modo en el Apache local de XAMPP.

En Nginx u otro servidor no se aplican los archivos .htaccess: apuntar directamente
a public/ y configurar las restricciones equivalentes. No publicar la raíz sin
estas reglas. Los directorios vacíos de almacenamiento se crean en el servidor
cuando sean necesarios; no hace falta copiar logs antiguos.

## Compatibilidad

Las 27 direcciones anteriores siguen disponibles: home.php, login.php,
validacion.php, las rutas de api, usuario, activacion, soporte y las tres rutas
Gmail. El callback sigue en https://fmglobals.com/oauth2callback.php; mover el
DocumentRoot no cambia esa URL ni requiere registrar localhost en Google.

Los antiguos archivos estáticos styles.css, main.js e img/ ahora están bajo
assets/css/site.css, assets/js/site.js y assets/img/. Las referencias internas se
actualizaron. Si hay consumidores externos de esas direcciones estáticas, agregar
redirecciones o actualizar sus referencias antes de retirar el release anterior.

La ejecución HTTP de llama.php se sustituyó explícitamente por CLI. Ejecutar
`php bin/run-token.php` solo cuando se requiera esa tarea en el servidor adecuado;
no fue ejecutada durante las pruebas.

## Comprobaciones realizadas

- 114 comprobaciones automatizadas de rutas, autoload, separación de archivos,
  destinos de API/OAuth y datos del panel, sin acceder a correos.
- Login con la cuenta autorizada y carga de panel, inicio, usuarios, listado,
  horarios, asesor, soporte, generador de enlaces y reportes en un servidor local
  temporal con conexión a la base local mediante variables de entorno.
- Respuestas HTTP 200 sin errores PHP visibles en esos módulos y recursos.
- API Disney sin correo devuelve JSON de validación sin necesitar conexión a la BD.
- Apache local mantiene las rutas anteriores; configuración y controladores internos
  responden 403. llama.php responde 410.
- No se generaron enlaces externos, no se autorizaron cuentas Gmail y no se
  consultaron buzones ni modificaron usuarios durante estas pruebas.

La base local disponible no contiene gmail_tokens: no se pudo validar contra ella
el mantenimiento real de Gmail, el almacenamiento de tokens o un callback completo.
Falta comprobarlos con el esquema de producción. El navegador automatizado falló
al iniciar, por lo que falta una revisión visual/funcional de JavaScript en un
navegador. Las comprobaciones HTTP no sustituyen esa revisión.

## Reversión

Volver a apuntar el servidor al release anterior (base 7cfa2de) y conservar sus
archivos privados. No hay una migración de base de datos que revertir. No restaurar
copias antiguas de tokens para revertir únicamente los archivos de aplicación.
## Validación posterior a importar la base local

Se conectó fmglobal_streaming_local mediante config/database.local.php (excluido
de Git). Se verificaron login, mantenimiento Gmail, inicio, usuarios, horarios y
reportes: HTTP 200 sin errores PHP visibles. La tabla gmail_tokens ya está presente.
Esto resuelve la limitación anterior del esquema local; no valida la vigencia de
los tokens ni realiza una autorización o consulta nueva a Gmail. No desplegar
config/database.local.php en producción.
