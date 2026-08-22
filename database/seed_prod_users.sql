-- ====================================================================
-- SEEDING CANÓNICO OFICIAL DE UTILIZADORES DE PRODUÇÃO (ISPSN 2026/27)
-- ====================================================================

-- 0. Desativar entidades de turno cadastradas incorretamente como cursos
UPDATE `cursos` SET `activo` = 0 WHERE `id` = 134 OR UPPER(`codigo`) = 'COOR' OR `nome` LIKE '%Regime B%';

-- 1. Inserir ou Atualizar Super Administradores
INSERT INTO `utilizadores` (`nome`, `email`, `senha_hash`, `perfil`, `curso_id`, `activo`)
VALUES 
('Evaristo Adriano (Admin)', 'evaristo.adriano@ispsn.org', NULL, 'admin', NULL, 1),
('David Boio (Admin)', 'david.boio@ispsn.org', NULL, 'admin', NULL, 1)
ON DUPLICATE KEY UPDATE `perfil` = 'admin', `curso_id` = NULL, `activo` = 1;

-- 2. Chefes de Departamento e Gestão Académica
INSERT INTO `utilizadores` (`nome`, `email`, `senha_hash`, `perfil`, `curso_id`, `activo`)
VALUES 
('Boaventura Feuerbach Fernando', 'boaventura.fernando@ispsn.org', NULL, 'chefe_departamento', NULL, 1),
('Edmundo da Costa Francisco', 'edmundo.francisco@ispsn.org', NULL, 'chefe_departamento', NULL, 1),
('Kianguembeni Teófilo Canania', 'kianguenbeni.canania@ispsn.org', NULL, 'chefe_departamento', NULL, 1),
('Maria de Fátima Luis Falso Kessongo', 'maria.falso@ispsn.org', NULL, 'gestor_academico', NULL, 1)
ON DUPLICATE KEY UPDATE `perfil` = VALUES(`perfil`), `curso_id` = NULL, `activo` = 1;

-- 3. Coordenadores de Curso (Vinculados estritamente aos 12 Cursos Oficiais)
INSERT INTO `utilizadores` (`nome`, `email`, `senha_hash`, `perfil`, `curso_id`, `activo`)
VALUES 
('Dânia Castro Estupiña', 'dania.castro@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'ENFE' OR LOWER(nome) LIKE '%enfermagem%' LIMIT 1), 1),
('Deoladeu Joaquim Ferramenta', 'deuladeu.ferramenta@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'HIST' OR LOWER(nome) LIKE '%história%' LIMIT 1), 1),
('Domingos João Pedro Bernardo', 'domingos.bernardo@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'FISI' OR LOWER(nome) LIKE '%fisioterapia%' LIMIT 1), 1),
('Fernando Macedo', 'fernando.macedo@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'DIRE' OR LOWER(nome) LIKE '%direito%' LIMIT 1), 1),
('Isata Gomes Cabaça', 'isata.cabaca@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'GRH' OR LOWER(nome) LIKE '%recursos humanos%' LIMIT 1), 1),
('João Miguel Catombela', 'joao.miguel@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'ECON' OR LOWER(nome) LIKE '%economia%' LIMIT 1), 1),
('Jorge Alberto Montane', 'jorge.montane@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'PSIC' OR LOWER(nome) LIKE '%psicologia%' LIMIT 1), 1),
('Miriam Ovideo Herrera', 'miriam.herrera@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'ANLI' OR LOWER(nome) LIKE '%análises clínicas%' LIMIT 1), 1),
('Nelson Garcia Sungo', 'nelson.sungo@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'CONT' OR LOWER(nome) LIKE '%contabilidade%' LIMIT 1), 1),
('Sebastião Gonçalo Joaquim', 'sebastao.joaquim@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'SOCI' OR LOWER(nome) LIKE '%sociologia%' LIMIT 1), 1),
('Silvia Catarina Adolfo Chitangua', 'silvia.chitangua@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'CARD' OR LOWER(nome) LIKE '%cardiopneumologia%' LIMIT 1), 1),
('Valeriano Mangandi', 'valeriano.mangandi@ispsn.org', NULL, 'coordenador', (SELECT id FROM cursos WHERE UPPER(codigo) = 'CPRI' OR LOWER(nome) LIKE '%relações internacionais%' LIMIT 1), 1)
ON DUPLICATE KEY UPDATE `curso_id` = VALUES(`curso_id`), `perfil` = 'coordenador', `activo` = 1;
