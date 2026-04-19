# 🧪 Suite de Pruebas Automatizadas - HOTEL

Bienvenido a la arquitectura de pruebas del Sistema Hotelero. Este directorio centraliza toda la lógica de validación para asegurar la integridad contable y operativa del negocio.

## 📁 Estructura de Directorios

La suite se organiza siguiendo la **Pirámide de Pruebas**:

1.  **`/Unitarias`**: Pruebas de lógica pura. No tocan la base de datos.
    *   *Ejemplo:* `FinanzasHelperTest.php` (Cálculo de turnos).
2.  **`/Integracion`**: Pruebas que verifican la interacción entre módulos y la base de datos real.
    *   *Ejemplo:* `SincronizacionRoomingTest.php` (Flujo Rooming -> Finanzas).
3.  **`/Funcionales`**: Pruebas de alto nivel que validan controladores y flujos de API.
    *   *Ejemplo:* `RoomingControllerTest.php` (Flujo Completo de Check-in).
4.  **`/Aceptacion`**: Pruebas de interfaz de usuario (E2E) con navegación real.
    *   Ubicación: Carpeta raíz `/cypress`.

---

## 🚀 Cómo ejecutar las pruebas

### Pruebas de Código (PHPUnit)
Desde la terminal en la raíz del proyecto (`c:\xampp\htdocs\hotel`):

*   **Ejecutar TODO:**
    ```powershell
    C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit
    ```
*   **Ejecutar por Suite:**
    ```powershell
    C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit --testsuite Unitarias
    C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit --testsuite Integracion
    C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit --testsuite Funcionales
    ```

### Pruebas de Interfaz (Cypress)
Requiere tener el servidor Apache encendido.

*   **Abrir interfaz interactiva:**
    ```powershell
    npx cypress open
    ```
*   **Ejecutar en modo consola (Headless):**
    ```powershell
    npx cypress run
    ```

---

## 🏆 Hitos Recientes e Integraciones

Durante la última fase de desarrollo, hemos fortalecido la suite con las siguientes mejoras:

1.  **Flujo Crítico de Check-in (E2E)**: Implementación exitosa del test `checkin_completo.cy.js`. Este test valida el ciclo de vida completo: Login -> Apertura de Caja -> Selección de Habitación -> Registro de Huésped -> Sincronización Financiera automática.
2.  **Robustez de UI (Bugfix de Polling)**: Se corrigió un comportamiento en `rooming/index.js` donde el refresco automático de datos (polling) limpiaba la selección de habitación en el modal. Ahora el estado es persistente durante la edición.
3.  **Gestión Dinámica de Turnos**: Los tests de aceptación ahora son capaces de detectar y gestionar automáticamente los turnos de MAÑANA/TARDE, interactuando con los diálogos de `SweetAlert` de forma fluida.
4.  **Integración Financiera**: Mejora en la validación de observaciones en el Flujo de Caja, asegurando que el nombre del huésped y el número de habitación se registren correctamente para fines de auditoría.

---

## 🧹 Mantenimiento del Entorno

Para asegurar que los tests E2E (Cypress) pasen siempre en verde, es necesario limpiar el entorno periódicamente o antes de ejecuciones masivas. 

**Comando de Limpieza Rápida (SQL):**
```sql
-- Resetear estados de habitaciones y limpiar movimientos de prueba para el turno #34
USE hotel_db;
DELETE FROM flujo_caja_movimientos WHERE flujo_id = 34;
UPDATE habitaciones SET estado = 'libre' WHERE numero IN ('101', '201', '301');
DELETE FROM rooming_stays WHERE operador = 'roy' AND fecha_registro = CURDATE();
```

---

## 🛠️ Desarrollo de nuevos Tests

### 1. Clase Base (PHP)
Todos los tests de PHP deben extender de la clase `TestCase` ubicada en `tests/TestCase.php`.

### 2. Seguridad de Datos (Auto-Rollback)
Los tests de integración y funcionales están configurados para ser **seguros**. Al iniciar un test, se abre una transacción SQL y al terminar se ejecuta un `rollBack()`. 

### 3. Autocarga (Autoload)
Si creas una nueva clase, recuerda actualizar el autoloader:
```powershell
C:\xampp\php\php.exe composer.phar dump-autoload
```

### 4. Pruebas E2E (JS)
Añade nuevos archivos `.cy.js` en la carpeta `cypress/e2e/`.

---