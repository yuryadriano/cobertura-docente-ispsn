-- ====================================================================
-- ESQUEMA DA BASE DE DADOS: sftcoordenacao_db
-- Módulo de Cobertura Docente — ISPSN 2026/27
-- Arquitetura: MySQL / MariaDB (XAMPP Compatible)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `sftcoordenacao_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `sftcoordenacao_db`;

-- --------------------------------------------------------------------
-- 1. TABELA DE CURSOS
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cursos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(20) NOT NULL UNIQUE,
    `nome` VARCHAR(150) NOT NULL,
    `grau` VARCHAR(50) DEFAULT 'Licenciatura',
    `duracao_anos` INT DEFAULT 4,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 2. TABELA DE DISCIPLINAS
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disciplinas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `curso_id` INT NOT NULL,
    `codigo` VARCHAR(20) DEFAULT NULL,
    `nome` VARCHAR(150) NOT NULL,
    `ano_curricular` INT NOT NULL CHECK (`ano_curricular` BETWEEN 1 AND 5),
    `semestre` VARCHAR(10) NOT NULL CHECK (`semestre` IN ('I', 'II', 'Anual')),
    `carga_horaria_semanal` INT DEFAULT 0,
    `creditos` INT DEFAULT 0,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`curso_id`) REFERENCES `cursos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 3. TABELA DE DOCENTES
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `docentes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(150) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `grau_academico` ENUM('Licenciado', 'Mestre', 'Doutor') DEFAULT 'Licenciado',
    `especialidade` VARCHAR(150) DEFAULT 'Não identificada',
    `tem_inaarees` VARCHAR(10) DEFAULT 'Não',
    `tem_agregacao_pedag` VARCHAR(10) DEFAULT 'Não',
    `categoria_carreira` VARCHAR(50) DEFAULT 'Assistente',
    `anos_experiencia_es` INT DEFAULT 0,
    `producao_cientifica_3a` INT DEFAULT 0,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 4. TABELA DE TURMAS & MÉTRICAS OPERACIONAIS
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `turmas` (
    `id` VARCHAR(50) PRIMARY KEY,
    `disciplina_id` INT NOT NULL,
    `docente_id` INT DEFAULT NULL,
    `designacao` VARCHAR(100) NOT NULL,
    `sumarios_registados` INT DEFAULT 0,
    `sumarios_previstos` INT DEFAULT 200,
    `programa_carregado` TINYINT(1) DEFAULT 1,
    `dosificacao_carregada` TINYINT(1) DEFAULT 1,
    `notas_no_prazo` VARCHAR(10) DEFAULT 'Sim',
    `inquerito_media` DECIMAL(3,2) DEFAULT 4.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`docente_id`) REFERENCES `docentes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 5. TABELA DE UTILIZADORES E PERFIS (RBAC)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `utilizadores` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(150) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `senha_hash` VARCHAR(255) DEFAULT NULL,
    `perfil` ENUM('coordenador', 'gestor_academico', 'grh', 'presidente', 'secretario_geral', 'admin') NOT NULL,
    `curso_id` INT DEFAULT NULL,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`curso_id`) REFERENCES `cursos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 6. TABELA DE PLANOS DE COBERTURA
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `planos_cobertura` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `curso_id` INT NOT NULL,
    `ano_lectivo` VARCHAR(10) NOT NULL DEFAULT '2026/27',
    `estado` ENUM('Rascunho', 'Submetido', 'Aprovado', 'Devolvido') DEFAULT 'Rascunho',
    `criado_por` INT DEFAULT NULL,
    `data_submissao` DATETIME DEFAULT NULL,
    `data_aprovacao` DATETIME DEFAULT NULL,
    `observacoes` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_curso_ano` (`curso_id`, `ano_lectivo`),
    FOREIGN KEY (`curso_id`) REFERENCES `cursos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`criado_por`) REFERENCES `utilizadores`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 7. TABELA DE LINHAS DO PLANO DE COBERTURA
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `linhas_cobertura` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plano_id` INT NOT NULL,
    `disciplina_id` INT NOT NULL,
    `turma_id` VARCHAR(50) DEFAULT NULL,
    `docente_id` INT DEFAULT NULL,
    `conformidade` ENUM('Sim', 'Parcial', 'Não', 'Por verificar') DEFAULT 'Por verificar',
    `justificacao` TEXT DEFAULT NULL,
    `regime` ENUM('Tempo Integral', 'Tempo Parcial', 'Colaborador') DEFAULT 'Tempo Parcial',
    `categoria_carreira` VARCHAR(50) DEFAULT 'Assistente',
    `parecer` ENUM('Manter', 'Manter c/ acompanhamento', 'Substituir', 'Recrutar') DEFAULT 'Manter',
    `observacoes` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`plano_id`) REFERENCES `planos_cobertura`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`docente_id`) REFERENCES `docentes`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`turma_id`) REFERENCES `turmas`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 8. TABELA DE CV ESTRUTURADO (1:1 COM DOCENTE)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cvs_estruturados` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `docente_id` INT NOT NULL UNIQUE,
    `grau_academico` ENUM('Licenciado', 'Mestre', 'Doutor') NOT NULL,
    `especialidade` VARCHAR(150) NOT NULL,
    `tem_inaarees` TINYINT(1) DEFAULT 0,
    `tem_agregacao_pedag` TINYINT(1) DEFAULT 0,
    `categoria_carreira` VARCHAR(50) DEFAULT 'Assistente',
    `anos_experiencia_es` INT DEFAULT 0,
    `producao_cientifica_3a` INT DEFAULT 0,
    `formacoes_json` JSON DEFAULT NULL,
    `experiencias_json` JSON DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`docente_id`) REFERENCES `docentes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 9. TABELA DE DOCUMENTOS DOS DOCENTES
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `documentos_docentes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `docente_id` INT NOT NULL,
    `tipo` ENUM('cv', 'certificados', 'diplomas', 'bi', 'inaarees', 'agregacao_pedag') NOT NULL,
    `caminho_ficheiro` VARCHAR(255) NOT NULL,
    `estado` ENUM('Válido', 'Pendente', 'Em falta') DEFAULT 'Pendente',
    `validado_por` INT DEFAULT NULL,
    `validade` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`docente_id`) REFERENCES `docentes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`validado_por`) REFERENCES `utilizadores`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 10. TABELA DE HISTÓRICO DE APROVAÇÕES E AUDITORIA
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `historico_aprovacoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plano_id` INT NOT NULL,
    `utilizador_id` INT NOT NULL,
    `acao` ENUM('Submetido', 'Aprovado', 'Devolvido') NOT NULL,
    `comentario` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`plano_id`) REFERENCES `planos_cobertura`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 11. ÍNDICES DE ALTA PERFORMANCE
-- --------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS `idx_linhas_plano_docente` ON `linhas_cobertura` (`plano_id`, `docente_id`);
CREATE INDEX IF NOT EXISTS `idx_linhas_docente_conf` ON `linhas_cobertura` (`docente_id`, `conformidade`);
CREATE INDEX IF NOT EXISTS `idx_planos_ano_estado` ON `planos_cobertura` (`ano_lectivo`, `estado`);
CREATE INDEX IF NOT EXISTS `idx_documentos_docente_tipo` ON `documentos_docentes` (`docente_id`, `tipo`, `estado`);
CREATE INDEX IF NOT EXISTS `idx_turmas_disciplina_docente` ON `turmas` (`disciplina_id`, `docente_id`);
CREATE INDEX IF NOT EXISTS `idx_disciplinas_curso_ano` ON `disciplinas` (`curso_id`, `ano_curricular`, `semestre`);

-- --------------------------------------------------------------------
-- 12. VISTAS SQL INTELIGENTES (VIEWS DE AGREGAÇÃO E INTELIGÊNCIA)
-- --------------------------------------------------------------------
CREATE OR REPLACE VIEW `vw_docentes_capacidade_carga` AS
SELECT 
    d.id AS docente_id,
    d.nome AS docente_nome,
    d.grau_academico,
    d.especialidade,
    d.tem_inaarees,
    d.tem_agregacao_pedag,
    d.categoria_carreira,
    COALESCE(SUM(d_disc.carga_horaria_semanal), 0) AS soma_horas_semanais,
    COUNT(DISTINCT pc.curso_id) AS num_cursos,
    COUNT(DISTINCT lc.turma_id) AS num_turmas,
    CASE 
        WHEN COUNT(DISTINCT pc.curso_id) >= 3 OR COALESCE(SUM(d_disc.carga_horaria_semanal), 0) > 20 THEN 'Sobregregado'
        WHEN COALESCE(SUM(d_disc.carga_horaria_semanal), 0) BETWEEN 14 AND 20 THEN 'No Limite'
        ELSE 'Disponível'
    END AS estado_capacidade
FROM `docentes` d
LEFT JOIN `linhas_cobertura` lc ON d.id = lc.docente_id
LEFT JOIN `planos_cobertura` pc ON lc.plano_id = pc.id
LEFT JOIN `disciplinas` d_disc ON lc.disciplina_id = d_disc.id
GROUP BY d.id, d.nome, d.grau_academico, d.especialidade, d.tem_inaarees, d.tem_agregacao_pedag, d.categoria_carreira;

CREATE OR REPLACE VIEW `vw_linhas_cobertura_detalhada` AS
SELECT 
    lc.id AS id,
    lc.id AS linha_id,
    lc.plano_id,
    lc.disciplina_id,
    lc.turma_id,
    lc.docente_id,
    lc.conformidade,
    lc.justificacao,
    lc.regime,
    lc.categoria_carreira,
    lc.parecer,
    lc.observacoes,
    lc.updated_at,
    pc.curso_id,
    pc.ano_lectivo,
    pc.estado AS estado_plano,
    d.nome AS disciplina_nome,
    d.ano_curricular,
    d.semestre,
    d.carga_horaria_semanal,
    d.creditos,
    t.designacao AS turma_nome,
    t.sumarios_registados,
    t.sumarios_previstos,
    t.programa_carregado,
    t.dosificacao_carregada,
    t.notas_no_prazo,
    t.inquerito_media,
    doc.nome AS docente_nome,
    doc.grau_academico AS docente_grau,
    doc.especialidade AS docente_especialidade,
    doc.tem_inaarees AS docente_inaarees,
    doc.tem_agregacao_pedag AS docente_agregacao,
    cap.num_cursos AS docente_num_cursos,
    cap.soma_horas_semanais AS docente_horas_semanais,
    cap.estado_capacidade AS docente_estado_capacidade
FROM `linhas_cobertura` lc
JOIN `planos_cobertura` pc ON lc.plano_id = pc.id
JOIN `disciplinas` d ON lc.disciplina_id = d.id
LEFT JOIN `turmas` t ON lc.turma_id = t.id
LEFT JOIN `docentes` doc ON lc.docente_id = doc.id
LEFT JOIN `vw_docentes_capacidade_carga` cap ON lc.docente_id = cap.docente_id;

CREATE OR REPLACE VIEW `vw_matchmaking_docentes` AS
SELECT 
    disc.id AS disciplina_id,
    disc.nome AS disciplina_nome,
    disc.curso_id,
    d.id AS docente_id,
    d.nome AS docente_nome,
    d.grau_academico,
    d.especialidade,
    d.tem_inaarees,
    cap.soma_horas_semanais,
    cap.num_cursos,
    cap.estado_capacidade,
    (
        (CASE WHEN d.grau_academico = 'Doutor' THEN 40 WHEN d.grau_academico = 'Mestre' THEN 30 ELSE 15 END) +
        (CASE WHEN d.tem_inaarees = 'Sim' THEN 25 ELSE 0 END) +
        (CASE WHEN LOWER(d.especialidade) LIKE CONCAT('%', LOWER(SUBSTRING_INDEX(disc.nome, ' ', 1)), '%') THEN 25 ELSE 10 END) +
        (CASE WHEN cap.estado_capacidade = 'Disponível' THEN 10 WHEN cap.estado_capacidade = 'No Limite' THEN 5 ELSE 0 END)
    ) AS pontuacao_compatibilidade
FROM `disciplinas` disc
CROSS JOIN `docentes` d
LEFT JOIN `vw_docentes_capacidade_carga` cap ON d.id = cap.docente_id
WHERE d.activo = 1;

CREATE OR REPLACE VIEW `vw_diagnostico_risco_academico` AS
SELECT 
    lc.linha_id,
    lc.curso_id,
    lc.ano_lectivo,
    lc.disciplina_nome,
    lc.turma_nome,
    lc.docente_nome,
    lc.conformidade,
    CASE 
        WHEN lc.docente_id IS NULL THEN 'Sem Docente Atribuído'
        WHEN lc.docente_estado_capacidade = 'Sobregregado' THEN 'Docente Sobregregado'
        WHEN lc.inquerito_media < 3.50 THEN 'Inquérito Pedagógico Baixo'
        WHEN lc.notas_no_prazo = 'Não' THEN 'Atraso nas Notas'
        WHEN lc.sumarios_registados < (lc.sumarios_previstos * 0.70) THEN 'Atraso nos Sumários'
        ELSE 'Normal'
    END AS nivel_risco,
    CASE 
        WHEN lc.docente_id IS NULL THEN 3
        WHEN lc.docente_estado_capacidade = 'Sobregregado' THEN 2
        WHEN lc.inquerito_media < 3.50 THEN 2
        WHEN lc.notas_no_prazo = 'Não' THEN 1
        ELSE 0
    END AS gravidade_risco
FROM `vw_linhas_cobertura_detalhada` lc;

CREATE OR REPLACE VIEW `vw_estatisticas_cobertura` AS
SELECT 
    c.id AS curso_id,
    c.codigo AS curso_codigo,
    c.nome AS curso_nome,
    pc.ano_lectivo,
    pc.estado AS estado_plano,
    COUNT(DISTINCT lc.turma_id) AS num_turmas,
    COUNT(lc.id) AS total_uc,
    SUM(CASE WHEN lc.docente_id IS NOT NULL THEN 1 ELSE 0 END) AS uc_atribuidas,
    SUM(CASE WHEN lc.conformidade = 'Sim' THEN 1 ELSE 0 END) AS conf_sim,
    SUM(CASE WHEN lc.conformidade = 'Parcial' THEN 1 ELSE 0 END) AS conf_parcial,
    SUM(CASE WHEN lc.conformidade = 'Não' THEN 1 ELSE 0 END) AS conf_nao,
    SUM(CASE WHEN lc.conformidade = 'Por verificar' THEN 1 ELSE 0 END) AS conf_ni
FROM `cursos` c
LEFT JOIN `planos_cobertura` pc ON c.id = pc.curso_id
LEFT JOIN `linhas_cobertura` lc ON pc.id = lc.plano_id
WHERE c.activo = 1
GROUP BY c.id, c.codigo, c.nome, pc.ano_lectivo, pc.estado;

CREATE OR REPLACE VIEW `vw_docentes_sobrecarga` AS
SELECT 
    docente_id,
    docente_nome,
    num_cursos AS total_cursos,
    soma_horas_semanais,
    estado_capacidade
FROM `vw_docentes_capacidade_carga`
WHERE estado_capacidade = 'Sobregregado';

CREATE OR REPLACE VIEW `vw_resumo_documental` AS
SELECT 
    d.id AS docente_id,
    d.nome AS docente_nome,
    COUNT(doc.id) AS total_documentos,
    SUM(CASE WHEN doc.estado = 'Válido' THEN 1 ELSE 0 END) AS documentos_validos,
    SUM(CASE WHEN doc.estado = 'Pendente' THEN 1 ELSE 0 END) AS documentos_pendentes
FROM `docentes` d
LEFT JOIN `documentos_docentes` doc ON d.id = doc.docente_id
GROUP BY d.id, d.nome;


