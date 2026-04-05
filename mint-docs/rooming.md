---
title: Rooming & FrontDesk
description: "Gestión operativa de estadías, Rack de habitaciones y Check-ins."
---

## Flujo Operativo
El módulo de Rooming es el núcleo transaccional del sistema. Controla el flujo completo del huésped desde la reserva hasta la salida definitiva.

### Rack de Habitaciones
Permite visualizar en tiempo real el estado de cada habitación:
- **Disponible**: Lista para venta.
- **Ocupado**: Con huésped activo.
- **Limpieza**: Requiere atención tras un check-out.
- **Mantenimiento**: Bloqueada por reparaciones.

### Procesos de Alojamiento
1. **Check-in Dinámico**: Registro de titular, acompañantes, procedencia y vehículos.
2. **Validación de Limpieza**: El sistema bloquea el check-in si la habitación no ha sido validada como "LISTA" por el personal.
3. **Gestión de Pagos**: Registro de anticipos sincronizados automáticamente con el Flujo de Caja.
4. **Late Check-out**: Extensión de horario con recargo automático opcional.

---

### Sincronización Financiera
Cada vez que se registra un pago en Rooming (Adelanto o Saldo), el sistema:
1. Actualiza el `total_cobrado` en la estadía.
2. Registra un ingreso en la tabla `anticipos`.
3. Inserta un movimiento automático en el **Flujo de Caja** diario bajo la categoría "Alojamiento".

> [!TIP]
> Referencia técnica detallada: [RoomingController (PHP)](https://jf2021070309.github.io/hotel/php-api/classes/RoomingController.html) | [FrontDesk Logic (JS)](https://jf2021070309.github.io/hotel/js-api/index.html)

> [!IMPORTANT]
> Nunca registres pagos de alojamiento manualmente en el Flujo de Caja; el módulo de Rooming lo hace de forma automática para evitar duplicidades.
