# Reglas de tarjetas, reportes y alcance

Fecha: 2026-09-20. Sustituye la propuesta anterior de permisos implícitos.

## Regla confirmada

Los permisos de módulo y reporte son independientes. La tarjeta de consultas se
muestra con módulo O reporte. Ver detalles aparece solo con permiso de reporte.
No se conceden permisos de reporte por habilitar un módulo.

| Tarjeta | Módulo | Reporte |
| --- | --- | --- |
| Consultas clientes | Soporte clientes | Consultas clientes |
| Consultas asesores | Asesor | Consultas asesores |
| Consultas soporte | Soporte | Consultas soporte |
| Consultas link | Link Netflix | Consultas link |

Cuentas Link Netflix depende exclusivamente del módulo Link Netflix.
En el banner superior, Soporte de clientes es visible para quienes tengan acceso a ese módulo (services.activation), independientemente de su perfil. Solo la alerta de Link Netflix es exclusiva del administrador (rol 1 validado en servidor) y aparece cuando hay cuentas asignadas a usuarios sin acceso. Estas reglas no cambian la visibilidad de tarjetas o reportes.

## Alcance aplicado

Solo el perfil administrador (rol 1, comprobado en servidor) obtiene datos globales.
Los demás perfiles ven exclusivamente su usuario en tarjetas, gráficos y reportes,
aunque conserven un valor activity.all antiguo. Los parámetros de la URL no amplían
el alcance. Las consultas públicas sin autor no se asignan artificialmente al usuario.
La lectura de inventario Link continúa limitada a sus cuentas asignadas para operativos.

## Permisos heredados

El usuario confirmó retirar la condición adicional de ver actividad. Los valores
`activity.own` y `activity.all` se conservan por compatibilidad de datos, pero no
conceden ni bloquean tarjetas, reportes ni amplían su alcance. La autorización
se basa en el módulo/reporte y la identidad validada en servidor.

## Validación

Pruebas de integración cubren la matriz módulo/reporte, enlaces a detalle y alcance
propio incluso con activity.all. La prueba tests/browser/build-mobile-menu.php comprueba
el menú móvil con AdminLTE real: apertura, cierre por fondo y Escape.

## Identidad del historial

El alcance propio utiliza el ID de la sesión validada: `uso_servicio.usuario_id` para consultas y `fm_link_audit.actor_id` para links. Cambiar el login o el nombre de la persona no traslada ni pierde su actividad. El campo antiguo `uso_servicio.usuario` es una referencia histórica y no concede acceso. Los registros sin autor identificado se conservan para el administrador; no se atribuyen al usuario que consulta el reporte. Ver [migraciones](DATABASE_MIGRATIONS.md).

En Link Netflix, marcar Error manual es exclusivo del administrador. Los usuarios operativos con acceso solo pueden generar en sus cuentas; el fallo automático No Link se registra desde el resultado del generador.

## Spotify

`services.spotify` habilita Gestión → Spotify, sin reportes ni tarjetas implícitas. Inicialmente se concede solo al administrador. El administrador puede registrar/editar inventario, asignar, trasladar, rehabilitar y operar sobre cualquier asignación. La cuenta técnica admin no recibe asignaciones operativas.

Los demás usuarios solo consultan y operan sobre sus asignaciones actuales; reciben conteos de disponibilidad sin correos/contraseñas libres ni datos del proveedor. Pueden buscar un cliente por su celular internacional exacto y registrarlo durante la solicitud, sin acceder a un listado general. Renovar, liberar, cambiar credenciales y marcar Caído se validan por el ID de usuario en servidor. Sin permiso o inactivo no accede; sus asignaciones permanecen y generan una alerta administrativa dentro de Spotify.

## Clientes y Ventas Spotify

`clients.manage` permite mantener el registro compartido de clientes. `reports.spotify_sales` permite el reporte independiente de Spotify: administrador con permiso ve todo, los demás solo su propio ID. Ningún administrador, incluida la cuenta técnica admin, evita las denegaciones de perfil/usuario; aparece en el selector de permisos. Se conserva la protección del último gestor activo y la exclusión de TI en asignaciones. Ver [detalle de módulos](CLIENTS_AND_SPOTIFY_SALES.md).
