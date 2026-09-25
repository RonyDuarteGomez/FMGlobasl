# Cambios acordados — septiembre 2026

Respaldo previo: `800a05c`, enviado a origin/refactor/estructura-modular antes de implementar.

## Spotify

- Afiliación y renovación aceptan meses enteros, 1 por defecto (límite técnico 120). Afiliación: inicio + meses; renovación: vencimiento registrado + meses. Ajuste al último día del mes destino, sin sumar 30 días. Renovar varios meses cuenta como una operación y registra meses/actor.
- Cliente opcional (incluida carga CSV); si se informa, nombre y celular internacional válidos. Celular único con cascada al editar.
- Beneficiarios opcionales por cliente: etiqueta como mamá. Reutilizables en varias cuentas, sin duplicar el celular principal. Presentación Nombre (beneficiario).
- Caída mantiene asignación y fechas, visible al responsable y bloqueada para renovar/liberar/cambiar credenciales. Administrador habilita, puede modificar correo, perfil y contraseña. No genera venta ni altera fechas. Se conserva el identificador de cuenta.
- Restablecer crea notificación persistente al asesor actual. Visible en inicio y Spotify; desde Spotify puede ver la cuenta y marcar como leída. No contiene contraseñas.
- Reasignación individual: administrador cambia asesor y cliente; asesor solo cambia/vincula/edita cliente de sus asignaciones. Editar cliente afecta sus otras cuentas y exige celular sin colisión, con aviso en formulario. Auditoría del antes/después.
- Reasignación masiva: botón administrativo, origen y destino distintos, todas las asignaciones vigentes incluidas las caídas. Muestra cantidad antes de confirmar. Destino activo, autorizado y distinto de admin técnico. Concurrencia e idempotencia dentro de transacción. El historial anterior mantiene su vendedor.

## Generador de Link externo

- Perfil Externo creado sin ID fijo: permiso inicial únicamente services.external_links.
- Servicios → Generador de Link. Usuario ingresa ID y secure; misma API existente, sin modificar conexiones. No se almacenan parámetros ni enlace generado, solo intento, usuario ID, fecha Lima, resultado y clave de idempotencia.
- Reportes → Links externos, permiso reports.external_links independiente y no concedido por defecto a Externo. Administrador autorizado ve todos; otros solo su ID. Fechas completas, búsqueda, paginación y gráfico diario.
- Card propia en inicio, visible por permiso al servicio o reporte; enlace a reporte solo con ese permiso.
- Mantenimiento → Restricciones del generador (external.restrictions). Separado de permisos de acceso al menú. Perfil como base, excepciones por usuario independientes para horario, cupo y navegador; null hereda, falso/0 desactiva.
- Horarios por siete días, hasta seis rangos diarios, Lima, usando el mismo validador de Soporte clientes. El módulo se mantiene visible bloqueado con causa y próximo horario cuando existe.
- Cupo individual diario: todo intento autorizado enviado a API cuenta aunque falle. Día de Lima [00:00, siguiente 00:00). Reserva transaccional antes de salir a red; bloqueados por horario/cupo/navegador no son llamadas a API. Un request_key repetido no genera una segunda llamada.
- Navegador solo si la restricción efectiva lo exige: cookie aleatoria HttpOnly/SameSite, Secure con HTTPS, hash en DB. En la primera visita se ofrece Registrar este navegador, con aviso de uso exclusivo; el registro no requiere aprobación. Si se revocó una vinculación previa, se informa que no hay navegador autorizado y se solicita autorización, sin afirmar que existe otro activo. Otro navegador solicita el cambio desde el aviso; el gestor lo aprueba y revoca el anterior. La solicitud pendiente mantiene vigente el navegador anterior. Restablecer invalida vinculación. No es identificación física ni IP. Borrar cookies requiere nueva autorización; la copia deliberada de cookies queda fuera de esta garantía.
- Disponibilidad se actualiza al abrir/generar o con el botón Actualizar disponibilidad; no hay sondeo que prorrogue sesiones inactivas.
- Una sesión operativa por navegador vinculado; un navegador no autorizado no desplaza al autorizado. Se validan permisos/restricciones al generar, no solo visualmente.
- Solicitudes de navegador limitadas a cinco pendientes por usuario. Cambios y aprobaciones auditados. No se registran credenciales en auditoría.

## Migraciones y pase

Con escrituras detenidas y respaldo de base y config/links.key: ejecutar el procedimiento de docs/DATABASE_MIGRATIONS.md (`bin/migrate-all.php --apply`, y --allow-deployment en producción). No cambiar conexiones existentes.

008_spotify_evolution: cliente nullable, meses, beneficiarios y notificaciones. Recupera únicamente última asignación cerrada por caída en cuentas que siguen caídas y no tienen asignación actual; no recupera liberaciones normales. Conserva auditorías.
009_external_links: tablas independientes de restricciones, navegadores, intentos y auditoría; perfil Externo y permisos iniciales. Reejecuciones respetan configuración/permisos posteriores.

Pruebas: tests/evolution.php, tests/spotify.php, tests/clients-sales.php, tests/backend-integration.php, tests/structure.php y fixtures de navegador. Servicios externos sustituidos en pruebas; no se generan links reales para verificar.
