# Hotel Platinium

Sistema de gestion hotelera orientado a la operacion diaria de recepcion, control financiero y seguimiento operativo del hotel. El proyecto centraliza procesos de check-in, check-out, reservas, flujo de caja, inventario, limpieza, reportes y administracion interna desde una sola aplicacion web.

## Objetivo del sistema

El sistema fue construido para dar soporte a la operacion del hotel en tiempo real, con foco en:

- control de habitaciones y estados de ocupacion
- registro de huespedes y estancias
- sincronizacion entre operacion y finanzas
- trazabilidad de movimientos, auditoria y cierres
- visualizacion de indicadores para perfiles administrativos y de caja

## Como funciona

La aplicacion sigue una arquitectura PHP modular con separacion por capas:

- `app/Views/`: pantallas del sistema por modulo
- `app/Controllers/`: logica de negocio y coordinacion entre vista, modelo y API
- `app/Models/`: acceso a datos y consultas sobre MySQL
- `api/`: endpoints HTTP para operaciones asincronas y consumo interno del frontend
- `config/db.php`: conexion centralizada por PDO y helpers comunes
- `rutas.php` y `router.php`: resolucion de rutas limpias tanto en Apache como en el servidor embebido de PHP

Flujo general de trabajo:

1. El usuario inicia sesion y accede segun su perfil.
2. Las vistas PHP cargan la interfaz del modulo correspondiente.
3. El frontend usa JavaScript y peticiones HTTP para consultar o actualizar datos via `api/`.
4. Los controladores validan reglas de negocio y delegan operaciones a los modelos.
5. Los modelos persisten la informacion en MySQL y devuelven resultados para refrescar la interfaz.

## Modulos principales

- `Dashboard`: resumen operativo para administracion y caja.
- `Rooming / Front Desk`: gestion de estancias, habitaciones, check-in y check-out.
- `Reservas`: control previo de ocupacion y seguimiento de ingresos.
- `Flujo de caja`: movimientos por turno, rendiciones y reportes diarios.
- `Caja chica`: registro y control de gastos menores.
- `Clientes`: alta y consulta de huespedes.
- `Inventario`: stock e historial de movimientos.
- `Limpieza`: seguimiento operativo y reportes del area.
- `Desayunos`: control de consumos asociados.
- `Yape y medios de pago`: conciliacion y trazabilidad de cobros.
- `Auditoria y usuarios`: administracion interna, permisos y seguimiento de acciones.
- `Reportes`: vistas operativas, financieros y graficos para analisis.

## Tecnologias empleadas

### Backend

- `PHP 7.4+`
- `PDO` para acceso seguro a base de datos
- arquitectura MVC ligera propia
- APIs REST internas en PHP

### Base de datos

- `MySQL / MariaDB`
- script base en [database.sql](database.sql)

### Frontend

- vistas renderizadas en `PHP`
- `JavaScript` modular por pantalla
- `Vue 3` por CDN para componentes interactivos
- `Axios` para consumo de APIs
- `Bootstrap 5.3`
- `Bootstrap Icons`
- `Chart.js` para graficos
- `jsPDF`, `AutoTable` y `SheetJS (xlsx)` para exportacion

### Infraestructura y ejecucion

- `Apache` con `.htaccess` para rutas limpias
- `PHP built-in server` mediante [router.php](router.php)
- compatibilidad con entorno local `XAMPP`
- soporte de variables de entorno para despliegue en Railway
- `Dockerfile` para empaquetado del proyecto

### Calidad y pruebas

- `PHPUnit` para pruebas unitarias, de integracion y funcionales
- `Cypress` para pruebas E2E de interfaz
- documentacion generada con `PHPDoc`, `JSDoc`, `Mintlify` y `OpenAPI`

## Recursos del proyecto

### Documentacion funcional

- guias en `mint-docs/`
- archivo de configuracion: [mint.json](mint.json)

### Documentacion tecnica

- backend documentado en `docs/php-api/`
- frontend documentado en `docs/js-api/`

### API y contratos

- especificacion OpenAPI en [api/docs/openapi.yaml](api/docs/openapi.yaml)
- visor HTML en [api/docs/index.html](api/docs/index.html)

### Pruebas automatizadas

- pruebas PHP en `tests/`
- pruebas E2E en `tests/Aceptacion/` y `cypress/`
- referencia adicional en [tests/README.md](tests/README.md)

## Estructura general

```text
hotel/
|- api/                Endpoints del sistema
|- app/
|  |- Controllers/     Logica de negocio
|  |- Models/          Acceso a datos
|  |- Views/           Modulos y pantallas
|- assets/             Recursos estaticos
|- auth/               Sesion y middleware
|- config/             Conexion y configuracion
|- docs/               Documentacion generada
|- mint-docs/          Documentacion funcional
|- tests/              Suite automatizada
|- rutas.php           Mapa de rutas limpias
|- router.php          Router para php -S
|- database.sql        Esquema base de datos
```

## Consideraciones funcionales

- La aplicacion trabaja con zona horaria `America/Lima`.
- El sistema contempla perfiles operativos y administrativos.
- Varias vistas dependen de consultas asincronas a endpoints internos para mantener la informacion actualizada sin recargar toda la pagina.
- Los modulos financieros y operativos estan conectados para reducir duplicidad de registro y mejorar la trazabilidad.

## Referencias rapidas

- Vista principal: [index.php](index.php)
- Configuracion de base de datos: [config/db.php](config/db.php)
- Mapa de rutas: [rutas.php](rutas.php)
- Configuracion de pruebas PHP: [phpunit.xml](phpunit.xml)
- Configuracion de Cypress: [cypress.config.js](cypress.config.js)
