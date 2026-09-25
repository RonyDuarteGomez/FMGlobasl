# Formularios compactos

Referencia aprobada: formularios de Link Netflix. Estilos compartidos en public/assets/css/adminlte/forms.css, sobre AdminLTE y los colores de marca.

- Cabecera 10 × 16 px; contenido 14 × 16 px; pie 8 × 12 px.
- Etiquetas pequeñas, campos relacionados en columnas y controles táctiles accesibles.
- Modales simples de 560 px; Spotify asignación/reasignación 680 px y cuenta principal 860 px.
- En móvil los grupos se apilan; los formularios largos mantienen scroll.
- CSV: etiqueta de archivo y descarga del modelo en la misma cabecera, selector, límites e instrucciones compactas. Clases compartidas fm-import-*.
- Cada importador conserva endpoint, modelo, límites, validaciones y resultados propios. Spotify muestra sus 14 columnas en un desplegable; Clientes y Link Netflix las muestran directamente.

Aplicación: Usuarios, Clientes, operaciones de Spotify, Link Netflix, formularios del panel, login y búsqueda pública. No alterar nombres, identificadores ni reglas de validación al ajustar el diseño.

Validación del 24/09/2026: 151 comprobaciones de estructura; fixtures de Spotify, Clientes/ventas y Link Netflix correctos; 297 comprobaciones funcionales y 61 en Chrome real con base temporal. Actualizado el selector del ejecutor para el dashboard aprobado. Sin llamadas a servicios externos durante las pruebas.


## Resultado de importaciones CSV
Clientes, Link Netflix y Spotify conservan abierto el modal tras importar. El resultado muestra únicamente cantidades importadas y rechazadas. Cuando hay rechazos, aparece «Descargar CSV de rechazos», con datos originales y motivo por fila. No se muestran tablas ni listas de errores en pantalla. Al abrir otra carga o reintentar se limpia el resultado previo. Se mantienen formatos y reglas propios de cada módulo. Los errores generales del archivo se muestran en el modal.
