-- ====================================================================
-- SCRIPT SQL PARA EXECUTAR DIRETAMENTE NO phpMyAdmin / MySQL
-- ISPSN 2026/27 — Ativação de Chefes de Departamento e Coordenadores
-- ====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. CHEFES DE DEPARTAMENTO
INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo)
VALUES 
('Boaventura Feuerbach Fernando', 'boaventura.fernando@ispsn.org', NULL, 'chefe_departamento', NULL, 1),
('Edmundo da Costa Francisco', 'edmundo.francisco@ispsn.org', NULL, 'chefe_departamento', NULL, 1),
('Kianguembeni Teófilo Canania', 'kianguenbeni.canania@ispsn.org', NULL, 'chefe_departamento', NULL, 1),
('Kianguembeni Teófilo Canania (Alias)', 'kianguembeni.canania@ispsn.org', NULL, 'chefe_departamento', NULL, 1)
ON DUPLICATE KEY UPDATE 
nome = VALUES(nome), 
perfil = 'chefe_departamento', 
activo = 1;

-- 2. COORDENADORES DE CURSO
INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo)
VALUES 
('Dânia Castro Estupiña', 'dania.castro@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%enfermagem%' LIMIT 1), 1),
('Deoladeu Joaquim Ferramenta', 'deuladeu.ferramenta@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%história%' LIMIT 1), 1),
('Domingos João Pedro Bernardo', 'domingos.bernardo@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%fisioterapia%' LIMIT 1), 1),
('Fernando Macedo', 'fernando.macedo@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%direito%' LIMIT 1), 1),
('Isata Gomes Cabaça', 'isata.cabaca@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%recursos humanos%' LIMIT 1), 1),
('João Miguel Catombela', 'joao.miguel@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%economia%' LIMIT 1), 1),
('Jorge Alberto Montane', 'jorge.montane@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%psicologia%' LIMIT 1), 1),
('Maria de Fátima Luis Falso Kessongo', 'maria.falso@ispsn.org', NULL, 'coordenador', NULL, 1),
('Miriam Ovideo Herrera', 'miriam.herrera@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%análises%' LIMIT 1), 1),
('Nelson Garcia Sungo', 'nelson.sungo@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%contabilidade%' LIMIT 1), 1),
('Sebastião Gonçalo Joaquim', 'sebastao.joaquim@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%sociologia%' LIMIT 1), 1),
('Silvia Catarina Adolfo Chitangua', 'silvia.chitangua@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%cardiopneumologia%' LIMIT 1), 1),
('Valeriano Mangandi', 'valeriano.mangandi@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE LOWER(nome) LIKE '%relações internacionais%' LIMIT 1), 1)
ON DUPLICATE KEY UPDATE 
nome = VALUES(nome), 
perfil = 'coordenador', 
activo = 1;

SET FOREIGN_KEY_CHECKS = 1;
