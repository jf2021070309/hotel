---
title: "Flujo de Caja"
description: "Gestión de turnos diarios, ingresos, egresos y auditoría financiera."
---

El módulo de **Flujo de Caja** es el componente central para el control del efectivo y movimientos bancarios diarios. Permite a las cajeras registrar operaciones y a los administradores supervisar el cuadre de caja.

## El Ciclo de Vida de un Turno

Un turno de caja atraviesa tres estados principales:

1.  **Borrador**: El turno está abierto. La cajera puede agregar, editar o eliminar movimientos.
2.  **Cerrado**: La cajera ha finalizado su arqueo. El turno se bloquea y solo el Administrador puede reabrirlo si detecta errores.
3.  **Depositado**: El Administrador confirma que el dinero físico ha sido entregado o depositado en el banco.

---

## Gestión de Movimientos

El sistema permite registrar dos tipos de movimientos:

### Ingresos
- **Alojamiento**: Pagos provenientes del módulo de Rooming (se sincronizan automáticamente).
- **Venta de Productos**: Ingresos por cafetería o minibar.
- **Otros Ingresos**: Cualquier entrada de dinero excepcional.

### Egresos
- **Compras**: Suministros inmediatos.
- **Servicios**: Pagos de luz, agua o internet realizados desde caja.
- **REPOSICIÓN C.CH.**: Envío de dinero al fondo de **Caja Chica**.

> [!IMPORTANT]
> **Sincronización Automática**: Cuando registras un egreso con la categoría "REPOSICIÓN C.CH.", el sistema crea automáticamente un ingreso equivalente en el módulo de **Caja Chica**. No es necesario registrarlo dos veces.

---

## Manejo Multidivisa

El sistema soporta tres monedas principales de forma nativa:
- **PEN (Soles)**: Moneda base del hotel.
- **USD (Dólares)**: Calcula el total basado en el tipo de cambio del día.
- **CLP (Pesos Chilenos)**: Ideal para hoteles con alta afluencia de turistas del sur.

> [!TIP]
> El sistema calcula automáticamente el **"Efectivo en Sobre"** desglosado por moneda, facilitando el arqueo físico al final del turno.

---

## Auditoría y Control

Todas las acciones críticas quedan registradas en el historial de auditoría:
- Quién cerró el turno y a qué hora.
- Quién autorizó la reapertura de un turno cerrado.
- Fecha y hora exacta de la confirmación de depósito.

---

> [!TIP]
> Referencia técnica detallada: [FlujoController (PHP)](https://jf2021070309.github.io/hotel/php-api/classes/FlujoController.html) | [FlujoModel (PHP)](https://jf2021070309.github.io/hotel/php-api/classes/FlujoModel.html) | [Frontend Logic (JS)](https://jf2021070309.github.io/hotel/js-api/index.html)
