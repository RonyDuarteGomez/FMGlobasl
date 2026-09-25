# Clientes y Ventas Spotify

## Clientes

Gestión → Clientes, permiso independiente `clients.manage`, inicialmente concedido solo al perfil administrador. Cualquier usuario autorizado consulta y mantiene todos los clientes. Campos obligatorios: nombre y celular internacional. Normalización de celular idéntica a Spotify, con + y código de país, unicidad en base de datos. Se permite cambiar ambos campos y se rechazan colisiones y ediciones obsoletas. Las asignaciones conservan el historial por `ON UPDATE CASCADE` de la clave del celular. No se incluye eliminación ni detalle de servicios.

## Ventas Spotify

Reportes → Ventas Spotify, permiso `reports.spotify_sales`, inicialmente administrador. No requiere permiso operativo de Spotify. Administrador autorizado ve todos los vendedores; cualquier otro perfil ve solo el ID de su sesión, incluso manipulando la URL.

Una fila por vendedor con actividad en el rango, usando su nombre actual. Cantidades: ventas (asignación inicial), renovaciones, pérdidas de clientes (liberación) y cuentas caídas. Reasignar no genera venta. Una caída no genera también una pérdida. Una caída sin asignación no se atribuye artificialmente a un vendedor.

Operaciones del administrador cuentan para el asesor responsable en ese momento. Se conserva además el actor real en auditoría. El vendedor se fija por ID al registrar la operación; la reasignación posterior no altera ventas o renovaciones anteriores. En cuentas con varios perfiles, una caída se cuenta una vez por vendedor afectado, no una vez por perfil.

Periodos por días completos, sin horas: Hoy, 7, 30, 180 y 365 días incluyendo el día actual de Lima. Fechas específicas: ambos extremos incluidos. Rango invertido o fecha inválida se rechaza; máximo técnico 10 años. Tabla y gráfico comparten rango y búsqueda. Cuatro líneas diarias: ventas, renovaciones, pérdidas y caídas; suman todos los vendedores autorizados del filtro, sin depender de la paginación. Fechas de inicio y fin siempre visibles con calendarios; editarlas activa el rango personalizado. Los días sin operaciones se muestran con cero.

## Permisos administrativos

El rol 1 determina alcance global, no acceso. También la cuenta técnica admin obedece los permisos y aparece en su mantenimiento por usuario. Sigue excluida de asignaciones operativas. No se permite quitar el último gestor activo de permisos.

## Historial y migración

`fm_spotify_sales_events`: ID de auditoría origen, vendedor, actor real, clase de operación y fecha (DATE). Índice único por auditoría/vendedor/clase evita duplicados. Escritura en la misma transacción que la operación e idempotencia existente de Spotify.

`fm_client_audit`: actor, valores anteriores/nuevos y fecha de modificación. Las credenciales Spotify no forman parte de estas tablas.

`SpotifySalesMigration`, invocada por `SpotifyMigration`, reconstruye una vez los eventos antiguos con evidencia en `fm_service_audit` y asignaciones. Para eventos anteriores a una reasignación usa el vendedor origen del primer traslado posterior. No fabrica renovaciones a partir de una fecha de importación ni ventas a partir de inventario importado. Las caídas históricas se reconstruyen siguiendo el orden de altas, asignaciones, reasignaciones y liberaciones en la auditoría, sin depender de horas idénticas; sin evidencia no se atribuyen. No modifica ni elimina auditorías existentes.

La migración de permisos `006_clients_sales` conserva cambios posteriores. El backfill `007_spotify_sales_history_v2` es repetible y no duplica eventos. Esta revisión completa también las instalaciones locales que ejecutaron la reconstrucción inicial, conservando sus registros. Ejecutar `php bin/migrate-spotify.php` en local o el ejecutor completo documentado para el pase, con escrituras detenidas. No requiere cambiar conexiones ni claves.

## Verificación

- `php tests/clients-sales.php`: unicidad, normalización, cascada, permisos, atribución, fechas y reconstrucción histórica.
- `php tests/backend-integration.php`: rutas HTTP, sesión, CSRF, revocación administrativa y migraciones repetidas.
- `tests/browser/build-clients-sales-fixture.php`: altas/edición y filtros/gráfico con datos ficticios.

## Importación de Clientes

Botón Importar CSV y descarga del modelo dentro del modal, con columnas `nombre,celular`. UTF-8, coma o punto y coma, hasta 2 MB y 500 filas. Se guardan las filas válidas con auditoría, sin sobrescribir clientes; los rechazos muestran registro, datos originales y motivo. Requiere `clients.manage` y CSRF.


## Información del cliente

Acción Información en la tabla Clientes. Requiere `clients.manage`, incluso para administradores. El administrador consulta todas las asignaciones asociadas al celular del cliente; otros usuarios solo las asignaciones cuyo `advisor_id` coincide con su sesión. Los parámetros del navegador no permiten ampliar ese alcance.

El modal muestra nombre/celular y una tabla paginada de diez servicios: servicio, correo secundario, beneficiario, inicio, próximo pago del cliente (fecha `end_date` de la asignación), días por vencer, estado y fecha de liberación. No consulta ni devuelve contraseñas, correo principal, correo de pago del proveedor ni fechas del proveedor. No representa cobros realizados: es el vencimiento del servicio del cliente.

Estados: Liberado si la asignación está cerrada; Caído si la cuenta actual está caída; Vencido si la fecha final es anterior al día actual en Lima; Activo en otro caso. Para liberados se muestran fecha final histórica y liberación, sin contar días restantes.

Se utiliza el historial de asignaciones existente. Correo y beneficiario proceden de sus registros asociados actuales; no se inventan versiones antiguas del correo si se cambió. Cambiar el cliente o asesor de una asignación modifica la asociación consultada; el historial de movimientos sigue en auditoría. Las asignaciones sin cliente no aparecen en esta ficha.

Validación: 49 comprobaciones Clientes/Ventas, 298 de integración y recorrido de interfaz Chrome con servicio activo y liberado.
