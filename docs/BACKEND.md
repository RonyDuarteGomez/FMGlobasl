# Cambios de lógica y seguridad

## Responsabilidades

`fm_dispatch()` aplica métodos HTTP, sesiones, permisos, CSRF y respuestas de error antes de ejecutar un controlador. Todas las entradas públicas existentes pasan por ese punto.

- `app/Security/`: configuración de sesión, CSRF, permisos y estado OAuth.
- `app/Services/Auth/LoginService.php`: autenticación con los hashes existentes.
- `app/Services/Users/`: validación y operaciones de usuarios/horarios.
- `app/Repositories/UserRepository.php`, `ScheduleRepository.php` y `ReportRepository.php`: acceso a sus tablas. Los controladores coordinan el servicio y la respuesta.
- `app/Services/Http/MailApiClient.php`: transporte común hacia las cuatro API de correo actuales.
- `app/Contracts/MailProvider.php` y `app/Services/Mail/ProviderRegistry.php`: contrato y registro de adaptadores Gmail/IMAP. Para otro proveedor, implementar el contrato y añadir su fábrica al registro.
- `app/Support/Logger.php`: registros privados con referencia para localizar fallos. No registra mensajes de excepciones, parámetros, contraseñas, tokens ni cuerpos de correo.

## Permisos

| Operación | Administrador | Asesor | Soporte |
|---|---|---|---|
| Panel y cierre de sesión | Sí | Sí | Sí |
| Usuarios, horarios globales, Gmail, enlaces y reportes | Sí | No | No |
| Consultas de asesor | Sí | Sí | No |
| Consultas de soporte | Sí | No | Sí |

La cuenta y el rol se vuelven a consultar en cada solicitud protegida. Una sesión abierta pierde acceso si la cuenta se desactiva. Las estadísticas se entregan solo a administradores. El menú mantiene sus opciones anteriores.

Las API públicas de correo y `validacion.php` mantienen su acceso público existente. Esta entrega no añade autenticación entre servidores, permisos por buzón ni un límite global de consultas. Cambiar ese contrato requiere coordinar sus consumidores y la consulta pública.

## Sesiones y formularios

- Cookies HttpOnly y SameSite=Lax; Secure cuando PHP recibe HTTPS. Si el hosting termina TLS en un proxy, configurar correctamente HTTPS en el servidor de aplicación sin confiar en cabeceras arbitrarias del cliente.
- El identificador de sesión se regenera al autenticar; el cierre elimina sesión y cookie.
- Login, formularios y operaciones POST requieren token CSRF. `security.js` lo adjunta solo al AJAX del mismo origen. El formulario de horarios lo adjunta a su `fetch`.
- Guardar, desactivar, actualizar horarios, iniciar autorización Gmail y cerrar sesión requieren POST. La UI ya usa esos métodos.
- OAuth comprueba un estado de un solo uso con diez minutos de vigencia. El callback conserva `https://fmglobals.com/oauth2callback.php`. La autorización debe iniciarse desde ese mismo servidor/sesión; iniciarla en localhost y volver a producción no comparte la sesión.

## Usuarios y horarios

Se comprueban campos, longitudes, correo, rol existente y horarios antes de escribir. Un alta guarda usuario, personal y siete horarios dentro de una transacción. La edición conserva celular/cargo cuando no llegan en el formulario y no cambia una contraseña vacía. Las contraseñas no se recortan; los hashes existentes siguen siendo válidos.

Los horarios iniciales permanecen de 09 a 19 de lunes a sábado y de 00 a 00 el domingo. Horas iguales mantienen el día cerrado; el formulario no admite horas invertidas ni valores fuera de 00–23. El responsable de modificar un horario sale de la sesión, no de una cadena `admin` fija. La pantalla de horarios globales solo modifica registros con usuario_id=0.

No se puede desactivar la propia cuenta ni retirar el propio rol administrativo desde ese formulario. El bloqueo por horario del **login permanece desactivado**. La lógica horaria de la consulta pública tampoco se reinterpretó en esta entrega.

## Servicios y errores

El cliente conserva los destinos y parámetros publicados. Tiene cinco segundos de conexión, veinte de espera total, verificación TLS, ausencia de redirecciones automáticas y máximo de 2 MiB de respuesta. Valida HTTP, JSON y campos de los resultados. Una búsqueda vacía se distingue de fallo HTTP, timeout o respuesta inválida.

La API anterior devuelve `No se encontraron correos` también para algunos fallos internos: se conserva esa respuesta como vacío por compatibilidad. La distinción completa se obtiene cuando también se despliega la versión actualizada de los servicios de extracción.

Gmail e IMAP implementan el mismo contrato. Se respeta el proveedor explícito; si el consumidor del endpoint genérico lo omite, se conserva la selección automática por dominio. La extracción conserva los errores del proveedor y devuelve una lista vacía con éxito cuando no hay resultados.

Los errores esperados utilizan 400/401/403/404/405/409/419/422 según el caso, y 502/504 para el servicio externo. Los fallos inesperados responden con un mensaje genérico y referencia; el detalle operativo seguro queda en `storage/logs/application.log`. El callback de Gmail ya no imprime tokens o errores crudos.

Se conserva el parámetro de contraseña de la API genérica porque forma parte del contrato en producción. No registrar sus query strings en los servidores o intermediarios. Migrarlo a un mecanismo de autenticación entre servicios es una tarea de compatibilidad separada.

## Pruebas

```text
C:/xampp/php/php.exe tests/structure.php
C:/xampp/php/php.exe tests/backend.php
C:/xampp/php/php.exe tests/backend-integration.php
```

`composer test` ejecuta estructura y unitarias. `composer test:integration` ejecuta integración.

Las pruebas de integración solo aceptan localhost/127.0.0.1 y una base de origen terminada en `_local`. Copian únicamente la estructura de seis tablas a una base aleatoria `fmglobal_test_*`, generan datos ficticios, levantan PHP en un puerto temporal y prueban permisos, CSRF, login, usuarios, horarios, rollback y OAuth inválido. Eliminan esa base al terminar y no consultan servicios externos. Requieren permiso local para crear/eliminar esa base temporal y crear un trigger de prueba. No ejecutar en producción.

Una autorización Gmail real, lectura de buzones y pruebas con la API externa quedan pendientes de una validación operativa en el dominio configurado. No se generaron tokens ni se modificaron datos de producción durante estas pruebas.


## Permisos dinámicos y nuevo Inicio

La gestión de accesos ahora se guarda por perfil y por usuario en la base de datos. Las reglas fijas descritas anteriormente se conservan como configuración inicial, no como autorización permanente. Esta entrega agrega una migración de tablas de permisos que debe ejecutarse antes de desplegar; reemplaza la indicación anterior de que no había cambios de esquema. Consulte [Permisos y dashboard](PERMISSIONS_AND_DASHBOARD.md) para uso, alcance, migración y pruebas.
