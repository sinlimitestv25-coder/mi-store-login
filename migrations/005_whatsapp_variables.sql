-- Tabla para variables personalizables de WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_custom_variables (
      id INT AUTO_INCREMENT PRIMARY KEY,
      reseller_id INT NOT NULL,
      variable_name VARCHAR(100) NOT NULL UNIQUE,
      variable_value VARCHAR(500) NOT NULL,
      variable_type ENUM('text', 'number', 'date', 'email') DEFAULT 'text',
      placeholder VARCHAR(255),
      description TEXT,
      is_active BOOLEAN DEFAULT 1,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (reseller_id) REFERENCES usuarios(id) ON DELETE CASCADE,
      INDEX idx_reseller (reseller_id),
      INDEX idx_active (is_active)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para registrar envíos de confirmación por email
CREATE TABLE IF NOT EXISTS whatsapp_email_logs (
      id INT AUTO_INCREMENT PRIMARY KEY,
      audit_log_id INT NOT NULL,
      email_to VARCHAR(255) NOT NULL,
      subject VARCHAR(255) NOT NULL,
      sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      status ENUM('enviado', 'fallido', 'pendiente') DEFAULT 'pendiente',
      error_message TEXT,
      FOREIGN KEY (audit_log_id) REFERENCES whatsapp_audit_log(id) ON DELETE CASCADE,
      INDEX idx_status (status),
      INDEX idx_sent_at (sent_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
