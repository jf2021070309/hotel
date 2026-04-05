---
title: Introducción
description: "Bienvenido a la documentación oficial del sistema Hotel Platinium."
---

## Descripción General
El sistema **Hotel Platinium** es una plataforma integral de gestión hotelera (PMS) diseñada para optimizar la operatividad diaria, el control financiero y la experiencia del huésped.

### Módulos Principales
- **FrontDesk / Rooming**: Control total de habitaciones, check-ins y check-outs.
- **Finanzas y Flujo de Caja**: Gestión multidivisa y rendiciones por turno.
- **Inventario**: Control de stock de minibares y suministros.
- **Dashboard**: Vista 360° de la operación para administración y recepción.

---

## Arquitectura de Documentación
El sistema utiliza una estrategia de **Ecosistema Multicapa** para cubrir todos los aspectos del proyecto:

### 1. Documentación Funcional (Guías de Usuario)
*   **Plataforma**: Mintlify (Este portal).
*   **Público**: Usuarios finales, Recepcionistas y Administradores.
*   **Propósito**: Explica las reglas de negocio, flujos operativos y manuales de uso diario.

### 2. Documentación Técnica (Código Fuente)
*   **Plataforma**: [PHPDoc (Backend)](https://jf2021070309.github.io/hotel/php-api/index.html) y [JSDoc (Frontend)](https://jf2021070309.github.io/hotel/js-api/index.html).
*   **Público**: Desarrolladores y Personal de Mantenimiento.
*   **Propósito**: Detalla la lógica interna de clases, métodos y variables del sistema.

### 3. Documentación de API (Especificación REST)
*   **Plataforma**: [ReDoc / OpenAPI](https://jf2021070309.github.io/hotel/api/docs/index.html).
*   **Público**: Desarrolladores de Apps Móviles o Integradores.
*   **Propósito**: Define los contratos de datos (JSON) y endpoints del servidor.

### 4. Documentación de Arquitectura de Datos (Persistencia)
*   **Plataforma**: [Esquema SQL (database.sql)](https://github.com/jf2021070309/hotel/blob/main/database.sql).
*   **Público**: DBAs y Arquitectos de Software.
*   **Propósito**: Documentar la estructura de tablas, relaciones y tipos de datos para asegurar la integridad de la información.

---
