# Cuentas de Link Netflix

## Alcance y casos de uso

El permiso efectivo `services.links` controla el acceso. El perfil administrador
(`rol_id=1`) administra todas las cuentas; los demás usuarios solo ven y generan
sus cuentas. El administrador también debe tener acceso al módulo. No se añade
un permiso administrativo independiente ni eliminación de cuentas.
El superusuario `admin` es de TI: no es destino operativo ni aparece como candidato
para asignación o traslado. El servidor rechaza esas operaciones. Si hubiera cuentas
asignadas previamente a él, se señalan como no elegibles y se permite liberarlas o
trasladarlas a un usuario operativo; no se modifican asignaciones automáticamente.

- Registrar/editar: ID y secure como texto; correo:contraseña en un único campo.
  El correo se normaliza a minúsculas; la contraseña distingue mayúsculas y puede
  contener `/`. Se evita duplicar la combinación completa. Ediciones conservan
  el estado y la asignación; revisión optimista evita sobreescrituras.
- Importar: CSV UTF-8, coma o punto y coma, encabezados `ID,secure,correo_contrasena`.
  Máximo 5000 filas y 5 MB (también sujeto a los límites PHP del servidor).
  El modelo descargable contiene solo los encabezados. Se importan filas válidas
  como Activas y sin asignar. El resultado indica cargadas y rechazadas, con fila
  y motivo descargables. Por solicitud del administrador, el informe de rechazos incluye
  correo y todos los campos originales (ID, secure y correo:contraseña); solo se entrega
  al administrador que importa y no se persiste en registros técnicos. Los rechazos no detienen las
  otras filas; un fallo de infraestructura sí puede interrumpir el proceso.
- Generar: el servidor utiliza los parámetros almacenados contra la API existente.
  Éxito válido HTTPS marca Activo; respuesta inválida, fallo o timeout marca No Link (error automático).
  Se puede reintentar. El resultado ofrece Copiar y Abrir, y no se persiste la URL.
- Error manual: exclusivo del administrador, tanto en la tabla como en el servidor; volver a Activo requiere
  una generación exitosa.
- Asignar: cantidad o Todas, selección aleatoria entre cuentas sin asignar y Activas;
  check Incluir con error. Destinos activos con permiso efectivo de perfil/usuario.
- Liberar/trasladar: cantidad o Todas del usuario origen, sin obligar a liberar antes
  de trasladar. También se admiten cuentas de usuarios sin acceso/inactivos como origen.
- Gestión individual: desde cada fila se puede asignar, liberar o trasladar esa cuenta.
- Confirmación: se revisa disponibilidad en servidor. Si la cantidad alcanza, se ejecuta
  al pulsar Aplicar; solo si falta stock se pide confirmación adicional. Si falta stock, se indica lo solicitado
  y lo existente. La confirmación queda invalidada si cambia el conjunto disponible.
- Historial: `fm_link_audit` registra actor, acción, cuenta, origen, destino, resultado y fecha.
  No registra secretos. `assigned_at` es la fecha de asignación vigente (vacía al liberar);
  `moved_at` y `moved_by` conservan el último movimiento incluso después de liberar.
- Dashboard: totales Activas/Error; administrador además Sin asignar, cuentas por usuario
  y alerta de asignaciones a usuarios sin acceso. Cada usuario ve solo su inventario.

## Datos y concurrencia

`fm_link_accounts` cifra ID, secure y credenciales con AES-256-GCM y usa HMAC para
unicidad. La clave de 32 bytes reside en `config/links.key`, excluida de Git.
Las asignaciones usan transacciones y el bloqueo compartido con permisos. Una cuenta
con generación en curso no se edita ni mueve durante 90 segundos; el cliente externo
vence a los 40 segundos. Un resultado obsoleto no sobrescribe ediciones o movimientos.

La búsqueda y paginación se resuelven en servidor. La búsqueda de credenciales cifradas
requiere descifrar el inventario visible; para volúmenes muy grandes habrá que incorporar
un índice de búsqueda de correos separado antes de escalar esa operación.

## Validación

`php tests/link-accounts.php`: base temporal, sin API externa; aislamiento, permisos,
CSV, cifrado, duplicados, operaciones masivas, concurrencia y cambios de estado.
`php tests/backend-integration.php`: autenticación/CSRF y endpoints del sistema.
`php tests/browser/build-links-fixture.php`: vista ficticia aislada en el directorio temporal;
abrir `fm-links-preview.html#test` para verificar modales y confirmación parcial.


## Identificación visual

Los selectores, inventario, dashboard, permisos y reportes muestran nombre y apellidos
asociados en personal. Usuarios/login conserva los nombres de acceso. Las operaciones
continúan utilizando IDs y el historial conserva sus autores originales. Los registros
sin datos personales mantienen el usuario como respaldo; no se descartan del historial.
El traslado deshabilita el origen en el selector de destino y el servidor rechaza que
ambos IDs sean iguales.

## Distinción de errores y asignación

Se conserva `status` (`active`/`error`) para los conteos. `error_type` distingue 0 sin error, 1 automático (etiqueta **No Link**) y 2 manual (etiqueta **Error**). Una generación exitosa limpia el tipo; una fallida establece 1. Los filtros de tabla distinguen los dos errores; el check de asignación «Incluir con error» incorpora ambos.

`migrate-links.php`, incluido en `migrate-all.php`, añade la columna y clasifica los errores históricos por el último evento de generación/error manual de la auditoría. Si no hay un evento que identifique un error manual, se conserva como fallo automático. Puede repetirse sin cambiar tipos ya clasificados. Aplicado solo en local.

Los usuarios operativos solo disponen de Generar en las filas. El administrador dispone además de Error, Editar y Asignación.

«Todas las disponibles» muestra la cantidad disponible en el campo bloqueado y se actualiza al cambiar los filtros. Una asignación individual oculta «Todas las disponibles» e «Incluir con error», muestra el estado de esa cuenta y permite moverla aunque tenga error. Se mantienen las validaciones de destino, revisiones y bloqueo durante generación.
