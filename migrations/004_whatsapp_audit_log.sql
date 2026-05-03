-- Tabla para auditar envíos de WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_audit_log (
      id INT AUTO_INCREMENT PRIMARY KEY,
      reseller_id INT NOT NULL,
      cliente_id INT NOT NULL,
      numero_telefono VARCHAR(20) NOT NULL,
      nombre_cliente VARCHAR(255),
      mensaje TEXT NOT NULL,
      usuario_email VARCHAR(255),
      estado ENUM('enviado', 'pendiente', 'fallido') DEFAULT 'enviado',
      fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      ip_address VARCHAR(45),
      user_agent TEXT,
      FOREIGN KEY (reseller_id) REFERENCES usuarios(id),
      FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
      INDEX idx_reseller (reseller_id),
      INDEX idx_cliente (cliente_id),
      INDEX idx_fecha (fecha_envio)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
