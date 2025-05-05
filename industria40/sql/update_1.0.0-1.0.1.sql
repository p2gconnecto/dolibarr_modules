-- Update script for Industria 4.0 module from version 1.0.0 to 1.0.1

-- Add fk_perizia if it doesn't exist (handles case where table was created before fk_perizia was added to create script)
-- Note: This syntax for checking column existence might vary slightly between MySQL/MariaDB versions.
-- This is a common approach but might need adjustment. A safer way is often to query INFORMATION_SCHEMA in PHP before executing.
-- For simplicity here, we assume direct execution or manual check. Consider adding robust checks in your module activation code.
ALTER TABLE llx_industria40project ADD COLUMN fk_perizia INT DEFAULT NULL AFTER fk_societe;

-- Add status and document columns for Analisi Tecnica
ALTER TABLE llx_industria40project ADD COLUMN status_analisi_tecnica SMALLINT DEFAULT 0 COMMENT '0=Draft, 1=In Progress, 2=Completed' AFTER status;
ALTER TABLE llx_industria40project ADD COLUMN doc_analisi_tecnica_file VARCHAR(255) DEFAULT NULL COMMENT 'Filename of generated Analisi Tecnica document' AFTER status_analisi_tecnica;

-- Add status and document columns for Perizia
ALTER TABLE llx_industria40project ADD COLUMN status_perizia SMALLINT DEFAULT 0 COMMENT '0=Not Started, 1=Generated' AFTER status_analisi_tecnica;
ALTER TABLE llx_industria40project ADD COLUMN doc_perizia_file VARCHAR(255) DEFAULT NULL COMMENT 'Filename of generated Perizia document' AFTER status_perizia;

-- Remove old document columns if they exist (optional, clean-up)
-- Use separate ALTER statements for robustness if one column doesn't exist
ALTER TABLE llx_industria40project DROP COLUMN IF EXISTS doc_perizia;
ALTER TABLE llx_industria40project DROP COLUMN IF EXISTS doc_analisi_tecnica;

