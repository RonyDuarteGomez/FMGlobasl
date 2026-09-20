> Procedimiento vigente de cambios de base: [DATABASE_MIGRATIONS.md](DATABASE_MIGRATIONS.md). Ejecutar las migraciones del release antes de abrir el tráfico.

# Despliegue de la estructura modular

## Antes de publicar

1. Guardar el release y respaldo de base de datos actuales por separado.
2. Preparar la rama refactor/estructura-modular en un directorio nuevo; no subir
   archivos sobre una mezcla de la estructura antigua y la nueva.
3. Ejecutar composer install con composer.lock. No actualizar dependencias como
   parte de esta migración.
4. Incorporar config/private.php y config/credentials.json existentes por un canal
   privado. No reemplazar sus valores por los ejemplos del repositorio.
5. Mantener la base existente y aplicar `bin/migrate-all.php` según DATABASE_MIGRATIONS.md; este release incluye cambios de estructura y datos.
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
archivos privados. Este release sí incluye migraciones: seguir el procedimiento de reversión de DATABASE_MIGRATIONS.md antes de volver a una versión anterior. No restaurar
copias antiguas de tokens para revertir únicamente los archivos de aplicación.
## Validación posterior a importar la base local

Se conectó fmglobal_streaming_local mediante config/database.local.php (excluido
de Git). Se verificaron login, mantenimiento Gmail, inicio, usuarios, horarios y
reportes: HTTP 200 sin errores PHP visibles. La tabla gmail_tokens ya está presente.
Esto resuelve la limitación anterior del esquema local; no valida la vigencia de
los tokens ni realiza una autorización o consulta nueva a Gmail. No desplegar
config/database.local.php en producción.

## Actualización de lógica y seguridad

Publicar juntos controladores, bootstrap, vistas y JavaScript: CSRF necesita el
formulario o cabecera que genera la versión actual de la UI. Recargar las pestañas
abiertas después de desplegar. No copiar `config/database.local.php` a producción.
Se requiere PHP 8.2 para el acceso preparado usado por los repositorios nuevos.

Cerrar sesión y autorizar Gmail ahora requieren POST con CSRF; no usar enlaces
GET externos antiguos para esas acciones. El callback debe recibir el estado
emitido en la misma sesión de fmglobals.com. Se conserva su URL; no se probó una
autorización real ni una lectura de buzones durante esta entrega.

Pasaron las pruebas de estructura, unitarias y funcionales descritas en BACKEND.md.
La integración usó una base temporal con datos ficticios y verificó también
rollback, CSRF, permisos por rol y sesiones invalidadas. Se comprobó el login y
la carga de nueve módulos contra la copia local existente sin modificar registros.
Chrome validó la sintaxis de los scripts y que el token CSRF no se adjunta a otro
origen. Esto no sustituye una revisión completa de interacciones y diseño en todas
las pantallas, ni una prueba operativa de correo en el dominio configurado.


## Permisos dinámicos y nuevo Inicio

La gestión de accesos ahora se guarda por perfil y por usuario en la base de datos. Las reglas fijas descritas anteriormente se conservan como configuración inicial, no como autorización permanente. Esta entrega agrega una migración de tablas de permisos que debe ejecutarse antes de desplegar; reemplaza la indicación anterior de que no había cambios de esquema. Consulte [Permisos y dashboard](PERMISSIONS_AND_DASHBOARD.md) para uso, alcance, migración y pruebas.

## Programación semanal de Soporte usuarios

La disponibilidad pública se configura con días deshabilitados, días completos o hasta seis intervalos activos por día. Se guarda la semana entera en `fm_public_schedule` con revisión, usuario editor y fecha. La revisión evita sobrescribir la edición concurrente de otra persona. Se rechazan intervalos vacíos o superpuestos antes de escribir.

Antes de publicar estos archivos, ejecutar la migración con la configuración de base del entorno destino:

```sh
php bin/migrate-public-schedule.php --allow-deployment
```

En la base local basta `php bin/migrate-public-schedule.php`. Ya se aplicó localmente. No se aplicó en producción.

La migración es repetible: solo crea la tabla y la programación inicial si no existe. Conserva `activacion` sin modificar; convierte el intervalo público bloqueado en su complemento activo. Por ejemplo, bloqueo 09:00–18:00 pasa a actividad 00:00–09:00 y 18:00–24:00. Las horas iguales y los días sin fila mantienen la disponibilidad completa del comportamiento público anterior. Los horarios personales de los usuarios no se modifican.

Tras migrar, `fm_public_schedule` es la fuente de disponibilidad pública. No editar `activacion` esperando modificar Validación. Sin la migración, la lectura conserva la conversión del horario legado y la escritura informa que falta migrar.

`WeeklySchedule::active()` se usa tanto en el dashboard como en Validación. La zona horaria es America/Lima; el inicio se incluye y el final se excluye. 24:00 solo se admite como final. Los intervalos que cruzan medianoche se dividen entre dos días. Validación respeta el horario también con sesión iniciada; no existe el antiguo bypass por login. El acceso a los módulos privados y sus permisos permanece separado.

El endpoint existente `POST activacion/actualizar_activacion.php` ahora recibe `schedule` (JSON de siete días) y `revision`, exige sesión, permiso `services.activation` y CSRF, y devuelve JSON. El antiguo envío de una sola hora/día ya no es válido. Desplegar juntos PHP, vistas y JavaScript, y renovar la caché de recursos.

Pruebas: `tests/backend.php`, `tests/backend-integration.php` (base temporal), y `php tests/browser/build-weekly-fixture.php`. Este último genera un HTML temporal para abrir en Chrome y verificar WEEKLY_UI_PASS con datos sintéticos, sin conectar a la base.
### Categoría Gestión

Link Netflix se agrupa en Gestión y conserva el permiso services.links, incluidas las asignaciones por perfil y las excepciones por usuario. Al desplegar, ejecutar `php bin/migrate-permissions.php --allow-deployment` para actualizar la categoría del catálogo en la base de datos.

### Baja lógica de Gmail

Antes de publicar este cambio ejecutar `php bin/migrate-gmail.php --allow-deployment`. Agrega `gmail_tokens.activa` con valor inicial 1 y conserva los registros actuales. Eliminar establece 0 y excluye la cuenta del listado y de la búsqueda de tokens del proveedor. Reautorizar el correo reactiva su registro. La etiqueta Token indica solamente si existe un refresh_token guardado; no verifica su validez con Google. En local usar el comando sin --allow-deployment.

### Estado del token Gmail

Volver a ejecutar `php bin/migrate-gmail.php --allow-deployment` antes de desplegar: agrega `token_valido TINYINT(1) NOT NULL DEFAULT 1`. La columna Token ahora refleja este campo (1 inicial no implica comprobación previa con Google). La respuesta `invalid_grant` al renovar el acceso marca 0; errores de red, cuotas o configuración no cambian el estado. Autorizar nuevamente restaura 1. La actualización comprueba el token que falló para no invalidar una renovación concurrente.

Autorizar y Actualizar token abren otra pestaña. La renovación envía login_hint y valida el correo devuelto antes de guardarlo. El retorno OAuth sigue en producción y necesita la misma sesión que inició la autorización; iniciarlo desde localhost no completa un flujo local. La pestaña original puede recargarse tras autorizar para ver el estado nuevo. Los registros con activa=0 permanecen excluidos del listado.

### Permiso Consultas soporte

Ejecutar `php bin/migrate-permissions.php --allow-deployment` antes de publicar. La migración 002_support_report crea reports.support y copia una sola vez los accesos y excepciones de services.support para conservar la distribución existente. A partir de ahí el acceso al reporte se configura por separado; el menú y la ruta del reporte comprueban este permiso. La migración posterior 003 incorpora el permiso independiente reports.links.

### Seguridad del login empresarial

El login usa AdminLTE local y solo usuario/contraseña, sin registro ni recuperación públicos. Conserva CSRF, consultas parametrizadas, password_verify y regeneración del ID de sesión. Añade mensajes genéricos, verificación con hash de relleno para cuentas inexistentes y cabeceras anti-iframe.

LoginThrottle limita cada combinación IP/usuario a 10 intentos por 15 minutos y cada IP a 100, independientemente de las cookies. Cuenta todos los intentos, también los exitosos; no bloquea permanentemente la cuenta. Usa REMOTE_ADDR, no confía en X-Forwarded-For enviado por el cliente. El estado se guarda bajo storage/security con bloqueo exclusivo de archivo y claves hash, sin contraseñas. PHP necesita permisos de escritura en ese directorio; no debe exponerse por HTTP y está excluido de Git. En despliegues con varios servidores deberá sustituirse por almacenamiento compartido. Detrás de proxy, configurar la IP real desde el servidor con proxies confiables.

Antes de producción verificar HTTPS obligatorio y cookies Secure efectivas. No se modificaron las conexiones ni se probó el hosting de producción. Esta revisión del acceso no sustituye una auditoría integral: la limitación por IP no detiene por sí sola ataques distribuidos y las contraseñas de prueba deben reemplazarse antes de uso real.
## URL limpias del sistema

El panel usa `/sistema/inicio`, `/sistema/asesor`, `/sistema/soporte`,
`/sistema/soporte-clientes`, `/sistema/autorizar-gmail`, `/sistema/link-netflix`,
`/sistema/usuarios`, `/sistema/permisos` y `/sistema/reportes/{clientes,asesores,soporte}`.
El acceso está en `/ingresar`. En una subcarpeta se antepone su ruta, por ejemplo `/FMGlobal/sistema/inicio`.

Publicar también `public/.htaccess` y `public/clean.php`. Apache necesita `mod_rewrite`
y permitir estas reglas mediante `AllowOverride`. Se admite tanto DocumentRoot en
`public/` como la raíz del proyecto con el `.htaccess` de compatibilidad existente.
Los endpoints PHP anteriores y el callback OAuth siguen disponibles. La navegación
actualiza el historial del navegador y restaura el módulo al recargar; los filtros
locales de tablas no se conservan al recargar. Los permisos se comprueban en los
endpoints originales; una URL limpia no concede acceso adicional.

## Gestión de cuentas Link Netflix

Antes de publicar el código, respaldar la base y ejecutar `php bin/migrate-links.php --allow-deployment`
en el servidor destino. En local basta `php bin/migrate-links.php`. La migración es aditiva
(idempotente): crea cuentas y auditoría sin modificar los registros anteriores.

Respaldar **config/links.key** de forma privada junto con la base. Si se trasladan cuentas
cifradas a otro servidor, instalar la misma clave; una clave diferente no puede leerlas.
El archivo no se sube a Git y la migración no crea una clave nueva si ya existen cuentas.
Restringir su lectura al usuario PHP y al operador de respaldo. PHP requiere OpenSSL y cURL.
La API de generación conserva su URL; ahora necesita salida HTTPS desde el servidor PHP.
Para admitir archivos de 5 MB, configurar `upload_max_filesize` al menos 5M y
`post_max_size` mayor (por ejemplo 8M). No se efectuaron consultas reales a la API durante pruebas.

Si ya se cargaron cuentas con `correo/contraseña`, ejecutar `php bin/migrate-link-credentials.php --allow-deployment` con la clave original para convertirlas a `correo:contraseña`. La migración conserva la contraseña completa y actualiza el control de duplicados.

Para habilitar Consultas link, ejecutar `php bin/migrate-permissions.php --allow-deployment`. Añade `reports.links` y lo habilita inicialmente para el perfil administrador; se puede conceder a otros perfiles o usuarios. El reporte incluye intentos registrados en `fm_link_audit` desde la incorporación del módulo de cuentas. El historial anterior de `uso_servicio` se conserva, pero no contiene cuenta ni intentos fallidos y no se mezcla con este reporte.
