# 🏨 Protocolo Oficial de Auditoría y Verificación de Flujo de Caja (E2E)

Este documento contiene la guía paso a paso y los datos de prueba exactos para validar desde cero la consistencia contable entre los **4 módulos principales del sistema**:
1. Dashboard Operativo (Caja de Turno)
2. Flujo de Caja (Cierre de Turno y Conciliación)
3. Sobre de Alex (Bóveda de Efectivo Físico Mensual)
4. Reporte Mendoza (Auditoría Comercial y Facturación P&L)

---

## 🧹 PASO 1: Reseteo y Limpieza de la Base de Datos

Para iniciar con una pizarra totalmente limpia en tu entorno de desarrollo/pruebas, ejecuta las siguientes sentencias SQL en tu gestor (phpMyAdmin o MySQL CLI) sobre la base de datos `hotel_db`:

```sql
-- Desactivar temporalmente revisión de claves foráneas
SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar tablas de flujo y movimientos
TRUNCATE TABLE flujo_caja;
TRUNCATE TABLE flujo_caja_movimientos;
TRUNCATE TABLE anticipos;
TRUNCATE TABLE rooming_consumos;
TRUNCATE TABLE caja_chica_movimientos;
TRUNCATE TABLE gastos_yape;
TRUNCATE TABLE gastos_yape_detalle;

-- Volver a activar revisión
SET FOREIGN_KEY_CHECKS = 1;
```

---

## 📝 PASO 2: Ingreso de Escenario de Prueba (Multi-Moneda)

Simularemos un día de operación estándar (Ejemplo: **18 de Mayo de 2026, Turno Mañana**).
Realiza exactamente las siguientes acciones en el sistema:

### 📥 A. Registro de Ingresos en Efectivo y Digitales
1. **Check-in Habitación 101**:
   * **Monto**: `S/ 500.00`
   * **Moneda**: Soles (PEN)
   * **Medio de Pago**: Efectivo Soles
2. **Check-in Habitación 102**:
   * **Monto**: `$ 200.00`
   * **Moneda**: Dólares (USD)
   * **Medio de Pago**: Efectivo Dólares
3. **Venta Directa de Consumo (Bebidas en Recepción)**:
   * **Monto**: `$ 50,000`
   * **Moneda**: Pesos Chilenos (CLP)
   * **Medio de Pago**: Efectivo Pesos
4. **Pago por POS (Tarjeta) en Habitación 201**:
   * **Monto**: `S/ 350.00`
   * **Moneda**: Soles (PEN)
   * **Medio de Pago**: Tarjeta / POS Soles
5. **Pago Digital por Yape en Habitación 202**:
   * **Monto**: `S/ 150.00`
   * **Moneda**: Soles (PEN)
   * **Medio de Pago**: Yape / Plin

### 📤 B. Registro de Egresos y Retiros (En Módulo Flujo de Caja)
En el turno en curso, haz clic en "+ Fila" en la sección de **EGRESOS** e ingresa:
1. **Pago de Lavandería**:
   * **Monto**: `S/ 100.00` (PEN)
   * **Switch**: Activo (`🟢 Fondo Mensual`)
2. **Compra de Repuestos (Dólares)**:
   * **Monto**: `$ 50.00` (USD)
   * **Switch**: Activo (`🟢 Fondo Mensual`)

---

## 🎯 PASO 3: Verificación de Resultados Esperados en los 4 Módulos

Una vez ingresados los datos, navega por los 4 módulos y verifica que las salidas coincidan exactamente con la siguiente tabla de auditoría:

### 1. 🖥️ Dashboard Operativo (`MI TURNO En curso`)
Al abrir el panel de control, en la barra lateral derecha verás la rendición instantánea del turno actual:
* **Efectivo Soles**: `S/ 500.00`
* **Efectivo Dólares**: `$ 200.00 USD`
* **Efectivo Pesos**: `$ 50,000 CLP`
* **Egresos Registrados**: `S/ 100.00 PEN` y `$ 50.00 USD`
* **Efectivo Físico Neto en Sobre (Turno)**:
  * Soles: `500 - 100 = S/ 400.00`
  * Dólares: `200 - 50 = $ 150.00 USD`
  * Pesos: `$ 50,000 CLP`

---

### 2. 💵 Flujo de Caja (Formulario de Cierre / Rendición)
Al abrir la pantalla de Flujo de Caja del turno actual:
* **Recuadro Verde Superior (`SE ENTREGA A ALEX - TURNO`)**:
  * Mostrará exactamente el efectivo cobrado en el turno: `S/ 500.00 PEN`, `$ 200.00 USD`, `$ 50,000 CLP`.
* **Recuadro Dorado Inferior (`ACUMULADO MENSUAL NETO DEL SOBRE`)**:
  * Como acabas de limpiar la base de datos, el acumulado mensual será idéntico al neto del turno:
  * **Soles (PEN)**: `S/ 400.00`
  * **Dólares (USD)**: `$ 150.00 USD`
  * **Pesos (CLP)**: `$ 50,000 CLP`

---

### 3. ✉️ Sobre de Alex (Consolidado Mensual de Bóveda)
Al ingresar al módulo "Sobre de Alex", las 3 grandes tarjetas de mando mostrarán la auditoría perfecta de la caja fuerte:
1. **Tarjeta 1 (Ingresos en Efectivo)**:
   * `S/ 500.00 PEN` | `$ 200.00 USD` | `$ 50,000 CLP`
2. **Tarjeta 2 (Retiros y Egresos)**:
   * `- S/ 100.00 PEN` | `- $ 50.00 USD` | `- $ 0 CLP`
3. **Tarjeta 3 (Fondo Neto en Caja Fuerte)**:
   * **Soles**: `500 - 100 = S/ 400.00`
   * **Dólares**: `200 - 50 = $ 150.00 USD`
   * **Pesos**: `50,000 - 0 = $ 50,000 CLP`

> **Nota de Auditoría:** ¡La Tarjeta 3 de Sobre de Alex coincide exactamente con el Recuadro Dorado del Flujo de Caja!

---

### 4. 📊 Reporte Mendoza (Auditoría Comercial y Bancaria)
Al ingresar al "Reporte Mendoza", verás la auditoría global de facturación y bancos sin importar en qué turno o sobre esté el dinero:
* **Transacciones Digitales**:
  * POS Soles: `S/ 350.00`
  * Yape / Plin: `S/ 150.00`
* **Efectivo & Bancos (Cobrado por Hospedaje y Consumos)**:
  * Efectivo Soles: `S/ 500.00`
  * Efectivo Dólares: `$ 200.00`
  * Efectivo Pesos: `$ 50,000`

---

## 🏆 Resumen de Cuadratura Contable

| Módulo | Enfoque Contable | Soles (PEN) | Dólares (USD) | Pesos (CLP) |
| :--- | :--- | :---: | :---: | :---: |
| **1. Dashboard** | Efectivo Físico del Turno | S/ 500.00 | $ 200.00 | $ 50,000 |
| **2. Flujo de Caja** | Neto Acumulado Mensual | **S/ 400.00** | **$ 150.00** | **$ 50,000** |
| **3. Sobre de Alex** | Bóveda Física Consolidada | **S/ 400.00** | **$ 150.00** | **$ 50,000** |
| **4. Reporte Mendoza** | Cobranza Comercial Bruta | S/ 500.00 | $ 200.00 | $ 50,000 |

¡Siguiendo esta guía confirmarás que todo el sistema opera de manera 100% predecible, auditada y exacta!
