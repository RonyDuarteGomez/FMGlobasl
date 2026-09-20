# Menú, permisos y dashboard personalizado

> Las nuevas relaciones solicitadas entre servicios, reportes y tarjetas se documentan
> en [ACCESS_RULES.md](ACCESS_RULES.md). Esa especificación distingue reglas confirmadas,
> decisiones pendientes y diferencias con la implementación actual.

## Uso

Todos los usuarios entran en Inicio. El menú agrupa **Servicios**, **Reportes** y **Mantenimiento**; solo muestra opciones autorizadas y oculta grupos vacíos.

En **Mantenimiento → Permisos**:

1. Seleccionar «Por perfil» o «Por usuario» y el destino.
2. Para un perfil, permitir o denegar cada acceso.
3. Para un usuario, elegir «Heredar del perfil», «Permitir» o «Denegar».
4. Revisar la columna de acceso efectivo y guardar.

Una excepción individual prevalece sobre el perfil, incluso cuando deniega un acceso normalmente permitido. Elegir Heredar elimina la excepción. Guardar recarga el panel para actualizar el menú del gestor; las demás sesiones aplican los cambios en su siguiente solicitud. Si conservan una opción antigua visible, el servidor rechaza su uso y una recarga actualiza el menú.

El alcance depende del perfil validado en servidor: el administrador ve los datos
globales y los demás usuarios solo su actividad. Los permisos heredados de información
no afectan el acceso; se conservan únicamente por compatibilidad. Las tarjetas se
muestran por módulo o reporte, y el detalle requiere el permiso del reporte, según
[ACCESS_RULES.md](ACCESS_RULES.md).

Los permisos de Usuarios, Gmail y Horarios permiten administrar esos recursos compartidos y, por ello, ver sus respectivos contadores actuales.

## Dashboard

Las tarjetas dependen de permisos efectivos, no del número de rol. Incluyen consultas públicas, asesor, soporte, usuarios, horarios, Gmail, enlaces y permisos cuando corresponda. Los contadores de consultas y minigráficos cubren 30 días, con cero en días sin actividad y un resumen de hoy. Los detalles expandibles muestran hasta 50 registros recientes, filtrados en SQL. Los reportes ofrecen 7/30/90 días o todo el historial y paginación de 50 registros.

Las consultas propias se identifican mediante `uso_servicio.usuario`, el nombre de usuario que ya guardaba la aplicación. Los registros públicos sin usuario no se atribuyen a otra persona. No se modificó el historial importado.

Las cifras representan **consultas registradas**, no todos los intentos: las operaciones que antes no guardaban fallos o búsquedas vacías no tienen ese historial. Link Netflix no tiene un contador porque su generador todavía no registra uso. Gmail muestra cuentas autorizadas guardadas, sin consultar en vivo su vigencia. Activación muestra horarios configurados, no una cantidad inventada de códigos.

## Base de datos y migración

Ejecutar antes de publicar los nuevos controladores:

```text
C:/xampp/php/php.exe bin/migrate-permissions.php
```

En local solo acepta host localhost/127.0.0.1 y base terminada en `_local`. Para el despliegue explícito en el servidor configurado se usa `php bin/migrate-permissions.php --allow-deployment`, con respaldo previo y comprobando la configuración. No copiar el archivo de configuración local a producción.

La migración agrega:

- `fm_permissions`: catálogo.
- `fm_role_permissions`: permisos de perfiles.
- `fm_user_permissions`: excepciones.
- `fm_permission_audit`: responsable, destino y valores anteriores/nuevos de cambios desde Permisos.
- `fm_permission_lock`: bloqueo y revisión para concurrencia.
- `fm_migrations`: versiones aplicadas.

No cambia tablas, contraseñas, roles ni historial existentes. Inicializa Administrador con todos los permisos, Asesor con su servicio y actividad propia, y Soporte con su servicio y actividad propia. Es repetible: una nueva ejecución no sobrescribe decisiones tomadas desde el módulo. Los cambios futuros del catálogo deben tener una migración nueva, no reutilizar la versión 001.

La migración ya se aplicó **solo en `fmglobal_streaming_local`** durante esta implementación. Producción no se modificó.

## Controles de seguridad

- La misma resolución alimenta menú, tarjetas y autorización HTTP; no se confía en opciones ocultas ni en un `usuario` recibido del navegador.
- Lectura de estado y permisos en cada solicitud protegida.
- CSRF, método POST y validación completa del catálogo al guardar.
- Auditoría de cambios y revisión optimista: una pantalla desactualizada no sobrescribe decisiones posteriores.
- Bloqueo compartido en cambios de permisos, perfiles y estado de usuarios; no se permite dejar cero gestores activos de permisos.
- El permiso de mantenimiento de usuarios por sí solo no permite asignar perfiles ni modificar una cuenta con accesos superiores a los del operador. Asignar perfiles requiere también gestionar permisos.

## Código

- `PermissionCatalog`: relación de rutas con capacidades y configuración inicial.
- `PermissionMigration` / `PermissionRepository`: esquema, resolución y guardado.
- `Access` / `ActivityScope`: autorización y alcance.
- `Navigation`: grupos y opciones.
- `ActivityRepository` / `PersonalDashboard`: consultas acotadas y tarjetas.
- `resources/views/Permissions/` y `Dashboard/summary.php`: pantallas.
- `public/assets/js/main.js`: navegación, filtros y envío de permisos.
- `public/assets/css/modules/dashboard.css`: distribución; usa las variables del tema compartido.

Las rutas anteriores se conservan. Se agregan `dashboard.php`, `permisos/index.php` y `permisos/save.php`. `inicio.php` sigue siendo el reporte público y la entrada inicial del panel ahora carga `dashboard.php`.

## Comprobación

```text
php tests/structure.php
php tests/backend.php
php tests/backend-integration.php
```

La integración usa una base temporal con datos ficticios y la elimina al terminar. Cubre herencia, permitir/denegar, alcance propio/global, ocultación y rechazo de rutas, migración repetida, edición desactualizada, último gestor, auditoría y escalada a través de mantenimiento. No llama a Gmail, IMAP ni las API externas.

La verificación de Chrome incluyó el menú, accesos desde tarjetas, detalles expandibles, vista previa de permisos y manejo del error de guardado con respuestas simuladas, además de capturas de los módulos reales en escritorio y pantalla estrecha.

## Dashboard de consultas

El dashboard presenta un banner agregado, vigencia del horario público (intervalo configurado = bloqueo), anillos por número de consultas Netflix/Disney para públicas y asesores y contadores de soporte/links de los últimos 30 días. Conserva el alcance por permisos y usuario. Las tablas detalladas siguen en los reportes.

La operación 7 en `uso_servicio` representa una generación de link reportada como exitosa por el navegador. `POST soporte/link.php` exige sesión, permiso `services.links`, CSRF y el evento `generated`. Solo almacena usuario, fecha y contador; no recibe cookies ni enlaces. El historial comienza con esta implementación, no se reconstruyen generaciones pasadas. Este contador es telemetría del cliente, no una auditoría de confirmación independiente por la API. Su fallo no oculta el enlace generado. Las estadísticas del banner siguen contabilizando las operaciones de consulta 1–6.

`tests/browser/link-counter.html` prueba el registro con la API simulada, sin conexiones externas. Se espera LINK_COUNTER_PASS. Los límites horarios y el aislamiento por usuario/plataforma están cubiertos por las pruebas PHP.
Los accesos de las tarjetas usan «Ver reporte detallado». Consultas públicas abre `inicio.php`; asesores, soporte y links abren `dashboard.php?report=advisor|support|links`. Estos detalles exigen permiso del servicio y de visualización de actividad, respetan el alcance propio/global y no conceden acceso al reporte interno general. El filtro de período y la paginación conservan el tipo de reporte. El reporte interno general incluye también links cuando el usuario tiene permiso del servicio. Todos los reportes muestran el usuario registrado.