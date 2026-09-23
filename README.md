# FMGlobals

Aplicación PHP reorganizada a partir de la base de producción `7cfa2de`.
Las rutas de consulta, los servidores de correo, la API de enlaces y las
credenciales predeterminadas se conservan.

## Estructura

```text
app/
  Controllers/       Entradas de cada módulo; coordinan operación y vista
  Providers/Mail/    Adaptadores Gmail e IMAP
  Services/          Correo, extracción y preparación de datos del panel
  Repositories/      Conexión, historial de uso y tokens Gmail
  Support/           Reglas y utilidades de extracción
bootstrap/app.php    Inicialización y carga de clases
config/              Configuración y archivos privados no versionados
public/              Único directorio que debe publicar el servidor web
  api/               Rutas JSON existentes
  usuario/           Rutas de usuarios existentes
  activacion/        Rutas de horarios existentes
  soporte/           Rutas existentes de consulta, enlaces y reportes
  assets/            CSS, JavaScript e imágenes
resources/views/     Plantillas agrupadas por módulo
routes/web.php       Mapa de las 27 rutas conservadas
bin/                 Comandos de mantenimiento por consola
storage/             Logs y archivos privados, excluidos de Git
tests/               Comprobaciones automatizadas
docs/                Arquitectura y despliegue
```

## Instalación

1. Usar PHP 8.2 o compatible con las dependencias bloqueadas y ejecutar `composer install`.
2. Incorporar `config/private.php` a partir de `config/private.example.php` por un canal privado.
3. Incorporar `config/credentials.json` de la integración OAuth existente.
4. Configurar la base MySQL y disponer del esquema completo, incluida `gmail_tokens`. No se incluyen bases ni tokens en Git.
5. Configurar el directorio web en `public/`. Leer `docs/DEPLOYMENT.md` antes de actualizar producción.
6. Ejecutar `php tests/structure.php` o `composer test`.

`config/database.php` conserva los valores de conexión importados. Para una
instancia de prueba se pueden definir las variables `FMGLOBAL_DB_HOST`,
`FMGLOBAL_DB_USER`, `FMGLOBAL_DB_PASSWORD` y `FMGLOBAL_DB_NAME` en el proceso del
servidor. No se carga `.env` automáticamente. Una variable vacía se respeta.

Los secretos permanecen fuera del directorio público y del repositorio.
Las rutas OAuth conservan `https://fmglobals.com/oauth2callback.php`.
Las API de correo siguen en `https://fmglobals.com/api/` y la API de enlaces
continúa en su dirección de Render existente.

## Desarrollo

La rama `main` conserva la base importada. La reorganización se desarrolla en
`refactor/estructura-modular`. No mezclar una instalación nueva con restos de los
archivos de la estructura anterior. Las comprobaciones HTTP realizadas y sus
limitaciones están en `docs/DEPLOYMENT.md`.
## Base de datos local

Copiar config/database.local.example.php a config/database.local.php para utilizar
una base local. Este último archivo está excluido de Git y no debe copiarse a
producción. Prioridad: variables de entorno, archivo local y valores de producción.
La copia local actual usa fmglobal_streaming_local.

Guía de mantenimiento del tema y los componentes: [docs/DESIGN.md](docs/DESIGN.md).

Cambios de lógica, permisos y pruebas funcionales: [docs/BACKEND.md](docs/BACKEND.md).


## Permisos dinámicos y nuevo Inicio

La gestión de accesos ahora se guarda por perfil y por usuario en la base de datos. Las reglas fijas descritas anteriormente se conservan como configuración inicial, no como autorización permanente. Esta entrega agrega una migración de tablas de permisos que debe ejecutarse antes de desplegar; reemplaza la indicación anterior de que no había cambios de esquema. Consulte [Permisos y dashboard](docs/PERMISSIONS_AND_DASHBOARD.md) para uso, alcance, migración y pruebas.

## Módulos

- [Spotify: requerimientos y casos de uso](docs/SPOTIFY_REQUIREMENTS.md). Módulo operativo inicial disponible; requiere `php bin/migrate-spotify.php` después de preparar la clave. Pruebas: `php tests/spotify.php`. CSV, reportes, dashboard y mantenimientos adicionales quedan pendientes.

Clientes y Ventas Spotify: [requerimientos, permisos y migración](docs/CLIENTS_AND_SPOTIFY_SALES.md). Pruebas: `php tests/clients-sales.php`.
