# Diseño de FM Globals con AdminLTE

Toda la interfaz utiliza AdminLTE 4.9.1 (incluye Bootstrap 5.3 en su CSS). Se incluyen Bootstrap JS 5.3.8 y jQuery 3.6.0, este último para conservar los flujos AJAX existentes. Recursos fijados y licencias en `public/assets/vendor/`; no editar los archivos del proveedor.

## Capas de estilos

- `main.css`: entrada del panel interno.
- `site.css`: entrada de portada, login y consulta pública.
- `theme.css`: fuente única de colores, radios y densidad de FM Globals.
- `adminlte/brand.css`: conecta el tema con los componentes Bootstrap/AdminLTE comunes.
- `adminlte/panel.css`: integra el layout `app-wrapper`, `app-sidebar`, `app-header`, `app-main` y el acordeón propio.
- `adminlte/modules.css`: distribución de dashboard, servicios, horarios, permisos y usuarios.
- `adminlte/site.css`: distribución de las páginas públicas.

No cargar Bootstrap CSS adicional: AdminLTE ya lo incluye. No cargar el antiguo sistema de componentes o la muestra `enterprise.css` junto a AdminLTE.

## Crear módulos

Reutilizar las clases de Bootstrap/AdminLTE directamente en la vista PHP:

```html
<article class="card card-outline card-primary">
  <div class="card-header"><h3 class="card-title">Título</h3></div>
  <div class="card-body">
    <label for="campo" class="form-label">Campo</label>
    <input id="campo" class="form-control form-control-sm">
    <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
  </div>
</article>
```

- Selectores: `form-select form-select-sm`.
- Tablas: envolver `table table-sm table-hover align-middle` en `table-responsive`.
- Botones secundarios/destructivos: `btn-outline-secondary` / `btn-outline-danger`.
- Paginación: `btn btn-sm btn-outline-primary`, conservando las clases usadas por JavaScript.
- Diálogos existentes: `ModalGeneral modal` contiene `modal-content`, `modal-header`, `modal-body` y `modal-footer`. Su apertura/cierre sigue a cargo de los controladores AJAX existentes; no mezclar estos eventos con `data-bs-toggle="modal"`. Los nuevos diálogos pueden usar íntegramente Bootstrap Modal.
- Preservar IDs, nombres de campos, CSRF, atributos `data-*` y controles de permisos en servidor.

El acordeón usa `details/summary`, iconos SVG locales y `main.js`. Al contraer el menú se cierran los grupos; pulsar una categoría expande el menú. No añadir `data-lte-toggle="sidebar"` al botón propio: habría dos controladores compitiendo. En móvil se cierra al navegar, pulsar fuera o presionar Escape.

## Tema compacto

Ajustar `--color-primario`, `--color-secundario`, `--radio-empresa`, `--espacio-empresa`, `--separacion-empresa`, `--alto-control-empresa` y `--padding-control-empresa` en `theme.css`. Los controles conservan un mínimo de 44 px cuando el dispositivo usa un puntero táctil. Evitar estilos inline para colores y medidas.

## Validación

Ejecutar `tests/structure.php`, `tests/backend.php` y `tests/backend-integration.php` con PHP CLI. La integración usa una base temporal, exige configuración local y no consulta servicios externos.

Revisar navegación, acordeón, dashboard expandible, búsqueda/paginación, apertura/cierre y guardado de formularios, filtros de permisos, horarios y páginas públicas en escritorio y móvil. No probar consultas de correo ni generar enlaces externos para validar únicamente el diseño.

La migración visual no requiere cambios de base de datos ni de conexiones de servicios.
La regresión de búsqueda y paginación se ejecuta abriendo `tests/browser/users-pagination.html` como archivo local en Chrome. Usa registros sintéticos, no conecta a la base de datos y debe terminar con el título PAGINATION_PASS.

La pantalla Soporte usuarios utiliza una tarjeta AdminLTE a todo el ancho con disponibilidad por día, intervalos activos, copia al resto y guardado semanal único. La programación compartida con Validación requiere la migración descrita en DEPLOYMENT.md.


## Referencia aprobada: tabla de Autorizar Gmail

Usar esta tabla como referencia para interfaces actuales y nuevas: encabezado con título a la izquierda y módulo a la derecha; buscador flexible, filtro de estado y tamaño de página; títulos y datos alineados a la izquierda; filas compactas, badges pequeños de estado con fondo semántico y texto blanco; acciones de 22 px de alto separadas por 12 px, color primario para habilitadas y gris para deshabilitadas; fechas con mes abreviado y hora am/pm. Conservar la tabla responsive de AdminLTE y la paginación, sin copiar reglas aisladas en cada página.

Asesor conserva cards AdminLTE y usa el modal Bootstrap incluido, con fondo bloqueante y spinner durante la consulta. La demostración temporal fue retirada tras aprobar el diseño; los resultados provienen de las consultas reales.