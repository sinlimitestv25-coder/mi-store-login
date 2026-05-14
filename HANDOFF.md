# Handoff — MI STORE Login

> Estado al 14 de mayo de 2026

---

## Resumen ejecutivo

**MI STORE** es una plataforma PHP multi-tenant para gestión de revendedores con integración de WhatsApp. Permite a administradores y resellers gestionar clientes, servicios y sub-usuarios bajo una jerarquía de roles con un sistema de créditos. El proyecto corre en un servidor PHP embebido (sin framework), está containerizado con Docker/Nixpacks y se despliega en Railway con MySQL como base de datos.

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.2+ (vanilla, sin framework) |
| Base de datos | MySQL / MariaDB (PDO) |
| Frontend | HTML/CSS/JS vanilla — tema oscuro/futurista |
| Despliegue | Docker · Nixpacks · Railway |
| Integración WhatsApp | wa.me links (sin API oficial) |

---

## Estructura de archivos

```
mi-store-login/
├── config.php                    # Conexión DB, helpers globales, constantes
├── login.php / logout.php        # Autenticación
├── setup.php                     # Reset de admin (uso único, borrar después)
├── dashboard.php                 # Panel principal
├── clients.php                   # Gestión de clientes
├── services.php                  # Catálogo de servicios
├── users.php                     # Gestión de usuarios/resellers
├── settings.php                  # Configuración de cuenta
│
├── enviar_whatsapp.php           # Envío de mensajes WhatsApp
├── clientes_whatsapp.php         # Lista rápida de clientes para WA
├── settings_whatsapp.php         # Plantillas de mensajes por usuario
├── historial_whatsapp.php        # Auditoría y exportación CSV
├── variables_whatsapp.php        # Variables personalizadas de plantillas
├── notificaciones_whatsapp.php   # Notificaciones automáticas
├── graficos_whatsapp.php         # Estadísticas de mensajes
├── api_whatsapp_notifications.php# Endpoint de notificaciones
├── enviar_confirmacion_email.php # Confirmación por email
│
├── install.sql                   # Schema base de la DB
├── migrations/                   # Migraciones SQL + documentación de seguridad
│   ├── 004_whatsapp_audit_log.sql
│   ├── 005_whatsapp_variables.sql
│   ├── actualizacion_bd_whatsapp.sql
│   └── CAMBIOS_SEGURIDAD_ROLES.md
│
├── modal_whatsapp.js             # JS para modales de WhatsApp
├── Dockerfile                    # Imagen PHP 8.2-cli
├── nixpacks.toml                 # Config para Railway Nixpacks
└── WHATSAPP_IMPLEMENTATION_GUIDE.md
```

---

## Base de datos

### Tablas principales

**`usuarios`** — Usuarios y resellers  
`id · nombre · email · password (bcrypt) · rol · creditos · estado · creado_por (FK self) · fecha_vence · whatsapp_template`

**`servicios`** — Catálogo de servicios/productos  
`id · nombre · logo_url · creado_por (FK usuarios)`

**`clientes`** — Clientes finales  
`id · servicio_id · propietario · nombre · email · telefono · precio · fecha_inicio · fecha_vence · estado · notas`

**`transacciones`** — Log de operaciones  
`id · tipo (nuevo|renovacion|credito) · cliente_id · usuario_id · monto · descripcion · fecha`

**`whatsapp_audit_log`** — Auditoría de mensajes  
`id · reseller_id · cliente_id · numero_telefono · mensaje · estado · fecha_envio · ip_address · user_agent`

**`whatsapp_custom_variables`** — Variables de plantilla por reseller  
`id · reseller_id · variable_name (UNIQUE) · variable_value · variable_type · is_active`

**`whatsapp_email_logs`** — Log de emails de confirmación  
`id · audit_log_id (FK) · email_to · subject · sent_at · status`

### Setup inicial

```bash
# 1. Crear la base de datos
mysql -u root -p < install.sql

# 2. Aplicar migraciones WhatsApp
mysql -u root -p mi_store_db < migrations/004_whatsapp_audit_log.sql
mysql -u root -p mi_store_db < migrations/005_whatsapp_variables.sql
mysql -u root -p mi_store_db < migrations/actualizacion_bd_whatsapp.sql
```

Credenciales por defecto del admin: `admin@mistore.com` / `admin123`  
**Cambiar inmediatamente en producción** (usar `setup.php`, luego borrarlo).

---

## Configuración de entorno

`config.php` lee variables de entorno en este orden de prioridad:

```
Railway:  MYSQLHOST · MYSQLDATABASE · MYSQLUSER · MYSQLPASSWORD · MYSQLPORT
Genérico: DB_HOST · DB_NAME · DB_USER · DB_PASS · DB_PORT
Fallback: localhost · mi_store_db · root · (vacío)
```

Constantes editables en `config.php`:
```php
define('APP_NAME', 'MI STORE');
define('APP_URL',  'http://localhost/mi-store');
date_default_timezone_set('America/Argentina/Buenos_Aires');
```

---

## Autenticación y sesiones

Flujo en `login.php`:

1. POST con `email` + `password`
2. Consulta a `usuarios` por email con prepared statement
3. `password_verify()` contra hash bcrypt
4. Verificación de `estado = 'activo'`
5. Sesión: `$_SESSION['user_id']`, `$_SESSION['user']` (id, nombre, email, rol, creditos)
6. Redirect a `dashboard.php`

Todas las páginas protegidas llaman `requireLogin()` al inicio; redirige a login si no hay sesión activa.

---

## Control de acceso por roles (RBAC)

| Rol | Clientes | Servicios | Usuarios | Configuración | WhatsApp |
|-----|----------|-----------|----------|--------------|----------|
| `admin` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `super_reseller` | ✅ | ✅ | Limitado | ✅ | ✅ |
| `reseller` | ✅ | ❌ | ❌ | Limitado | ✅ |
| `final` | ❌ (403) | ❌ | ❌ | ❌ | ❌ |

- Cada usuario solo ve los datos de su propia jerarquía (`creado_por`).
- Crear un sub-reseller cuesta **1 crédito** al creador.
- Ver detalles en `migrations/CAMBIOS_SEGURIDAD_ROLES.md`.

---

## Integración WhatsApp

No usa la API oficial de WhatsApp. El flujo es:

1. El usuario selecciona un cliente y un template de mensaje.
2. Las variables `{nombre_cliente}`, `{usuario}`, `{contraseña}`, `{servicio}`, `{fecha_vence}`, `{app_name}` se reemplazan automáticamente.
3. Se genera un link `https://wa.me/{telefono}?text={mensaje_codificado}`.
4. El link abre WhatsApp Web o móvil con el mensaje prellenado.
5. El envío queda registrado en `whatsapp_audit_log` con IP y user-agent.

Cada reseller puede personalizar su plantilla desde `settings_whatsapp.php`.  
Los mensajes se pueden exportar en CSV desde `historial_whatsapp.php`.

---

## Despliegue

### Local (desarrollo)

```bash
php -S 0.0.0.0:80 -t .
```

### Docker

```bash
docker build -t mi-store .
docker run -p 80:80 \
  -e MYSQLHOST=... \
  -e MYSQLDATABASE=... \
  -e MYSQLUSER=... \
  -e MYSQLPASSWORD=... \
  mi-store
```

### Railway

Conectar el repositorio, agregar un plugin MySQL y configurar las variables de entorno. El `nixpacks.toml` se encarga del resto; Railway detecta el puerto con `$PORT` automáticamente.

---

## Seguridad — puntos clave

- Passwords con `password_hash()` (bcrypt).
- Todas las queries usan **prepared statements PDO** (sin SQL injection).
- Función `clean()` aplica `htmlspecialchars` + `strip_tags` en toda entrada de usuario.
- `setup.php` existe para reset de admin; **debe borrarse después de usarse**.
- Auditoría completa de mensajes WhatsApp con IP logging.

---

## Trabajo pendiente / deuda técnica conocida

- `setup.php` presente en el repositorio — eliminar o agregar protección adicional.
- Sin HTTPS configurado en el servidor PHP embebido (depende de proxy en producción).
- Sin pruebas automatizadas (tests unitarios o de integración).
- `notificaciones_whatsapp.php` y `graficos_whatsapp.php` están presentes pero su estado de completitud no está documentado.
- El `README.md` actual es mínimo; este archivo lo reemplaza como documentación principal.

---

## Contacto y recursos

- Guía detallada de WhatsApp: `WHATSAPP_IMPLEMENTATION_GUIDE.md`
- Documentación de roles y seguridad: `migrations/CAMBIOS_SEGURIDAD_ROLES.md`
