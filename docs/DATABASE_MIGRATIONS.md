# Migraciones de base de datos

## Procedimiento vigente

Se actualiza la base de producción existente mediante scripts versionados. **No se importa la base local sobre producción**: contiene una copia antigua de los datos y podría sobrescribir actividad nueva. El código y estas migraciones forman un mismo release.

El ejecutor `bin/migrate-all.php` reúne, en orden, los cambios disponibles:

1. Permisos, excepciones, auditoría y catálogo de reportes.
2. Estado lógico y validez del token de Gmail.
3. Programación semanal, conservando los horarios anteriores.
4. Inventario y auditoría de cuentas Link; preparación de su clave privada y códigos de error automático (1) y manual (2).
5. Conversión de credenciales Link de `correo/contraseña` a `correo:contraseña`.
6. Vinculación del historial de consultas mediante ID de usuario.
7. Sustitución de espacios en logins por `_`, solo cuando sea necesaria y sin duplicidades.
8. Tablas de Spotify, clientes y tipos de servicio de soporte; servicio inicial Spotify con límites 5/1. Su permiso inicial se incorpora en la migración de permisos.

No cambia conexiones, dominios ni destinos de las APIs. No consulta Gmail ni genera enlaces. No copia los permisos o usuarios locales: conserva los del destino y aplica los valores iniciales de las migraciones que aún no se ejecutaron.

## Preparación y ensayo

- Usar PHP 8.2 o superior y las extensiones mysqli, openssl y mbstring. La cuenta de despliegue necesita permisos de creación/alteración de tablas y actualización de datos.
- Ensayar el release sobre una copia **reciente** de producción. El diagnóstico comprueba requisitos conocidos; no sustituye comparar el esquema real ni probar la aplicación.
- Respaldar el código actual, toda la base (estructura y datos) y los archivos privados. Verificar que el respaldo se puede restaurar en una base temporal.
- Conservar `config/links.key` si ya existen cuentas cifradas. Una clave nueva no puede abrirlas. Si el módulo nunca se instaló y no hay cuentas, el script crea una clave nueva. No subirla a Git ni incluirla en logs.
- No publicar `config/database.local.php` ni claves de pruebas. Revisar las variables `FMGLOBAL_DB_*` del servidor y comprobar la base destino que imprime el ejecutor.

Ejemplo de respaldo (reemplazar los marcadores, contraseña solicitada interactivamente):

```sh
mysqldump --host=HOST --user=USUARIO --password --single-transaction --routines --triggers --events --result-file=RESPALDO.sql BASE
```

El archivo debe quedar fuera de la raíz pública. Para tablas no transaccionales, acordar con el administrador de la base un respaldo con bloqueo; `--single-transaction` solo garantiza consistencia de tablas transaccionales.

## Ejecución en producción

Desde la raíz del release nuevo:

```sh
php bin/migrate-all.php --check
```

Este modo no aplica cambios. Muestra la base destino, el orden de ejecución, los usuarios pendientes de normalización y los conteos del historial. Si `usuario_id` aún no existe, `unresolved` incluye todos los registros con nombre: la vinculación se realiza al aplicar.

1. Poner la aplicación en mantenimiento, pausando también APIs públicas, tareas y procesos que escriban. El ejecutor no activa el mantenimiento automáticamente.
2. Tomar el respaldo final consistente y verificar el destino del diagnóstico.
3. Ejecutar:

```sh
php bin/migrate-all.php --apply --allow-deployment
php bin/migrate-usage-users.php --check
```

4. Activar el código del mismo release, todavía sin abrir el tráfico. Validar login, permisos, horarios, Gmail, cuentas Link y reportes con un administrador y un usuario operativo. Comprobar que este último solo ve sus datos.
5. Revisar los conteos y abrir el tráfico. Conservar la salida del despliegue y los respaldos en almacenamiento privado.

En local se puede ejecutar `php bin/migrate-all.php --apply` sin la bandera de despliegue. Sin `--apply`, el ejecutor solo diagnostica. Las migraciones individuales se mantienen disponibles.

## Historial por ID

`uso_servicio.usuario_id` identifica al autor. `usuario` se conserva como texto histórico; no se utiliza para filtrar el acceso ni para relacionar la persona. Los nombres visibles se obtienen mediante el ID. `fm_link_audit.actor_id` ya tenía este comportamiento.

La migración `004_usage_user_id` vincula una sola vez las coincidencias exactas y únicas entre el texto histórico y `usuarios.usuario`. No adivina identidades por nombres, mayúsculas diferentes ni similitudes. Las consultas anónimas mantienen ID nulo. Las no coincidentes se conservan y solo aparecen en el alcance global del administrador.

La marca impide que al volver a ejecutar el script un usuario nuevo herede actividad antigua por reutilizar un login. No borrar esta marca para intentar resolver pendientes. Si hubo reutilización de logins antes de esta migración, revisar los históricos: el texto por sí solo no prueba quién fue su autor original.

Consulta de pendientes para una revisión manual de identidad:

```sql
SELECT usuario, COUNT(*) AS consultas
FROM uso_servicio
WHERE usuario_id IS NULL AND TRIM(COALESCE(usuario, '')) <> ''
GROUP BY usuario;
```

Cualquier corrección posterior debe usar IDs concretos de consultas, una identidad verificada y un script versionado. No se eliminan históricos ni se incorporan borrados en cascada. No deben borrarse físicamente usuarios con actividad: usar su estado inactivo.

Resultado local inicial: 1.530 consultas conservadas, 771 vinculadas, 759 anónimas y 0 pendientes. Estos números corresponden a la copia local; producción tendrá sus propios conteos.

## Fallos, repetición y reversión

Los scripts pueden repetirse sin duplicar históricos ni restablecer permisos personalizados. La normalización de usuarios verifica las colisiones antes de modificar datos. El ejecutor se detiene en el primer fallo y evita ejecutar los pasos siguientes.

**No existe una transacción única para todo el despliegue**: MySQL confirma cambios de estructura de forma implícita. Los pasos completados permanecen aplicados. Mantener el mantenimiento, corregir la causa y repetir el ejecutor. El bloqueo de migración no reemplaza detener las escrituras de la aplicación.

Si es necesario volver atrás antes de reabrir tráfico, restaurar en conjunto el release, la base y los archivos privados compatibles usando el respaldo verificado. No ejecutar una reversión automática que borre columnas o tablas. Si ya entraron operaciones nuevas, no restaurar un respaldo antiguo directamente: preservar y conciliar esas operaciones primero.

No reabrir una versión antigua que escriba consultas sin ID después de completar esta migración: produciría históricos sin relación, y la vinculación automática ya no se repetirá.

## Pruebas

`tests/usage-users.php` verifica vinculación, anónimos, ambigüedades, cambio/reutilización de login, aislamiento por ID y normalización. `tests/backend-integration.php` comprueba además el ejecutor completo, incluido ejecutarlo dos veces sobre una base temporal. Ninguna de estas pruebas llama servicios externos.

## Spotify

El ejecutor incluye `migrate-spotify.php` después de preparar la clave de cifrado. En local puede ejecutarse individualmente. Usa `config/links.key` para las contraseñas; conservarla aunque no existan cuentas Link Netflix. El diagnóstico y la preparación de clave revisan también la existencia de cuentas Spotify antes de permitir generar una nueva.

La migración crea tablas sin cargar inventario de prueba ni cambiar usuarios existentes. CSV, dashboard, reportes y mantenimientos independientes de clientes y tipos de servicio no forman parte de esta entrega.
