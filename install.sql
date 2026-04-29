-- =============================================
--  install.sql
--  Ejecuta este archivo en phpMyAdmin o MySQL
--  para crear todas las tablas del sistema
-- =============================================

CREATE DATABASE IF NOT EXISTS mi_store_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE mi_store_db;

-- ---- TABLA: usuarios del sistema ----
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(120)  NOT NULL,
    email         VARCHAR(180)  NOT NULL UNIQUE,
    password      VARCHAR(255)  NOT NULL,
    rol           ENUM('admin','super_reseller','reseller','final') NOT NULL DEFAULT 'final',
    creditos      INT           NOT NULL DEFAULT 0,
    estado        ENUM('activo','inactivo','vencido') NOT NULL DEFAULT 'activo',
    creado_por    INT           NULL,
    fecha_vence   DATE          NULL,
    fecha_creado  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
  ) ENGINE=InnoDB;

-- Usuario admin por defecto (password: admin123)
INSERT INTO usuarios (nombre, email, password, rol, creditos, estado)
VALUES (
    'Administrador',
    'admin@mistore.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXkFBHzGm', -- admin123
  'admin',
    9999,
    'activo'
  );

-- ---- TABLA: servicios ----
CREATE TABLE IF NOT EXISTS servicios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(120)  NOT NULL,
    logo_url      TEXT          NULL,
    creado_por    INT           NOT NULL,
    fecha_creado  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE
  ) ENGINE=InnoDB;

-- ---- TABLA: clientes ----
CREATE TABLE IF NOT EXISTS clientes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    servicio_id   INT           NOT NULL,
    propietario   INT           NOT NULL,        -- ID del usuario que lo creó
  nombre        VARCHAR(120)  NOT NULL,
    email         VARCHAR(180)  NULL,
    telefono      VARCHAR(40)   NULL,
    precio        DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_inicio  DATE          NOT NULL,
    fecha_vence   DATE          NOT NULL,
    estado        ENUM('activo','por_vencer','suspendido') NOT NULL DEFAULT 'activo',
    notas         TEXT          NULL,
    fecha_creado  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
    FOREIGN KEY (propietario) REFERENCES usuarios(id) ON DELETE CASCADE
  ) ENGINE=InnoDB;

-- ---- TABLA: transacciones (historial) ----
CREATE TABLE IF NOT EXISTS transacciones (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    tipo          ENUM('nuevo','renovacion','credito') NOT NULL,
    cliente_id    INT           NULL,
    usuario_id    INT           NOT NULL,
    servicio_id   INT           NULL,
    monto         DECIMAL(10,2) NOT NULL DEFAULT 0,
    descripcion   TEXT          NULL,
    fecha         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id)  REFERENCES clientes(id)  ON DELETE SET NULL,
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE SET NULL
  ) ENGINE=InnoDB;

-- ---- INDICES utiles ----
ALTER TABLE clientes ADD INDEX idx_propietario (propietario);
ALTER TABLE clientes ADD INDEX idx_estado (estado);
ALTER TABLE clientes ADD INDEX idx_vence (fecha_vence);
ALTER TABLE transacciones ADD INDEX idx_usuario (usuario_id);
