-- ====================================================================
-- MIGRAÇÃO: TABELA DE AUDITORIA DE SINCRONIZAÇÃO & INTEGRAÇÃO API
-- ISPSN — Módulo de Cobertura Docente 2026/27
-- ====================================================================

CREATE TABLE IF NOT EXISTS `sync_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `endpoint` VARCHAR(100) NOT NULL,
    `metodo` VARCHAR(10) NOT NULL,
    `origem_ip` VARCHAR(45) NOT NULL,
    `status_code` INT NOT NULL,
    `registos_processados` INT DEFAULT 0,
    `tempo_execucao_ms` DECIMAL(8,2) DEFAULT 0,
    `detalhes_json` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sync_endpoint_status` (`endpoint`, `status_code`),
    INDEX `idx_sync_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
