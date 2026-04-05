---
title: Gestión de Usuarios
description: "Administración de accesos, roles y permisos del sistema."
---

## Descripción
El módulo de usuarios permite al administrador gestionar el personal autorizado, definir sus roles y asignar permisos granulares por módulo.

### Funcionalidades
1. **Listar Usuarios**: Vista de todo el personal registrado y su estado actual.
2. **Creación de Perfiles**: Registro de nuevos colaboradores con hash de seguridad (BCRYPT).
3. **Gestión de Permisos**: Control ACL (Access Control List) para habilitar o deshabilitar módulos específicos por usuario.
4. **Cambio de Contraseña**: Interfaz segura para actualización de credenciales.

### Roles Disponibles
- **Admin**: Acceso total y gestión de finanzas avanzadas.
- **Cajera / Recepción**: Operaciones diarias de rooming y flujo de caja.
- **Supervisor**: Monitoreo de auditoría y reportes.
- **Limpieza**: Registro de estados de habitación.

---

---

> [!TIP]
> Referencia técnica detallada: [UsuarioController (PHP)](https://jf2021070309.github.io/hotel/php-api/classes/UsuarioController.html) | [Frontend Logic (JS)](https://jf2021070309.github.io/hotel/js-api/index.html)
