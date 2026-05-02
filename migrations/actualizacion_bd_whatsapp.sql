-- RBAC + WhatsApp Template Integration Migration
-- Date: 2026-05-02
-- Description: Add WhatsApp template customization to usuarios table

ALTER TABLE usuarios ADD COLUMN whatsapp_template LONGTEXT DEFAULT NULL COMMENT 'Custom WhatsApp message template per user';
