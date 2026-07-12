# Flujo Operativo y Financiero - Hotel Platinium

Este documento detalla el flujo de trabajo estándar del hotel, conectando la operación diaria (recepción/check-in) con la administración y el control financiero (caja y reportes gerenciales).

---

## 1. Inicio de Jornada y Operación Diaria

### A. Apertura de Turno (Caja Chica)
Antes de que el personal pueda registrar movimientos de huéspedes, **siempre** se debe garantizar que exista un fondo disponible.
- **Acción:** Se verifica el saldo de la **Caja Chica**. Si no hay un ciclo abierto o se quedó sin fondos, se apertura uno nuevo (ej. "FONDO JULIO").
- **Origen del Saldo:** El monto inicial (ej. S/ 100) se extrae del flujo de los ingresos generales.
- **Registro Automático:** Esta extracción queda registrada como un egreso en el **Flujo de Caja** bajo la columna `"RECEPCION C.C"` (haciendo referencia a la Caja Chica).
- *Nota operativa:* Un ciclo de caja chica dura aproximadamente 2 semanas. Si al día siguiente aún hay saldo, el personal salta este paso y continúa directamente con la operación normal.

### B. Gestión de Reservas
Módulo para clientes que solicitan estadías futuras o llegan sin previo aviso (*walk-in*).
- **Acción:** Se registra la habitación en el **Cuadro de Reservas**, bloqueando las fechas correspondientes a nombre del titular.
- **Estados:** Se manejan reservas con estado *Pagado* o *Pendiente*.
- **Transición:** Una vez que el huésped llega físicamente al hotel, esta reserva se convierte en un Check-in.

### C. Check-in (Módulo Rooming)
Módulo exclusivo para los huéspedes que **ya confirmaron su Check-in** y se encuentran en el hotel.
- **Acción:** Se registra o actualiza al huésped en el módulo de **Rooming**.
- **Datos recopilados:** Nombre completo, DNI/Pasaporte, Nacionalidad (clave por los huéspedes extranjeros), cantidad de PAX y comprobante asociado, completando los datos faltantes de su reserva previa.
- **Consumos Extra:** Durante su estadía, si el cliente consume productos adicionales (Bebidas, Desayuno Buffet), estos se agregan de manera independiente a su cuenta.

### D. Pagos y Cobranzas
El hotel recibe turistas de diversas nacionalidades, por lo que acepta una amplia variedad de medios de pago. Todo cobro realizado en el Rooming queda reflejado en el sistema según su tipo.
- **Medios aceptados:**
  - Efectivo (Soles, Dólares, Pesos Chilenos)
  - POS (Soles, Dólares)
  - Billeteras Digitales (Yape, Plin)
  - Transferencias Bancarias

---

## 2. Control de Ingresos (Caja)

A medida que los recepcionistas realizan check-ins y cobros, los montos se acumulan automáticamente agrupados por su respectivo Medio de Pago. Esto se visualiza en dos módulos clave:

### A. Flujo de Caja (Ingresos)
- Módulo de control operativo donde el cajero/recepcionista verifica los ingresos registrados.
- Se visualiza a nivel de **Día y Turno**, permitiendo cuadrar la caja física contra lo que marca el sistema.

### B. Reporte Mendoza (Gerencia)
- Módulo gerencial para la supervisión.
- Muestra los ingresos desglosados por **Día y Turno**.
- En la parte inferior, consolida los totales a nivel **Mensual**, permitiendo ver el rendimiento general del hotel.

---

## 3. Control de Egresos (Gastos y Sobres)

Para mantener la operatividad diaria (comprar insumos, pagar servicios, etc.), el sistema controla las salidas de dinero a través de tres mecanismos principales:

### A. Sobres de Alex
- Representan entregables de dinero físico (Efectivo Soles, Dólares o Pesos).
- Es el origen de los fondos para la caja diaria (Ej: Se extraen 100 soles de un sobre para tener caja/sencillo).
- *Nota operativa:* Actualmente los descuentos hacia los sobres se manejan a nivel mensual (descuento directo global) para evitar la selección manual por cada pequeño gasto.

### B. Gastos Yape
- Módulo específico donde el gerente (Sr. Mendoza) envía montos por Yape para cubrir necesidades operativas.
- **Registro:** Se anota el monto recibido y luego se desglosa exactamente en qué se gastó.
- **Vueltos:** Si sobra dinero de un "Gasto Yape" y ese vuelto se recibe en efectivo físico (Soles), ese saldo se registra y se inyecta directamente al "Sobre" correspondiente.

### C. Caja Chica y Gastos Operativos
- Módulo para registrar los gastos cotidianos diarios (comida, pan, mercado, taxis, lavandería, mantenimiento, etc.).
- Los fondos para cubrir esto provienen de los Sobres o de lo recibido por Yape.

---

## 4. Supervisión Gerencial (Cierre)

Con todos los módulos conectados, el gerente (Sr. Mendoza) tiene una visibilidad de 360 grados:
- **Rentabilidad:** Visualización en tiempo real de lo que se ganó (Ingresos vs. Gastos).
- **Trazabilidad:** Saber exactamente de qué sobre salió el dinero o en qué se gastó el Yape enviado.
- **Temporalidad:** Análisis a nivel Semanal y Mensual.

---
*Documento generado en base a los requerimientos y módulos estructurales del sistema (Rooming, Flujo, Reportes, Sobres y Yape).*
