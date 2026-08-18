-- ==============================================================================
-- MIGRATION: Adição de Chave Única (UNIQUE CONSTRAINT) e Cargas de Turmas em Falta
-- Módulo de Cobertura Docente ISPSN 2026/27
-- ==============================================================================

-- PASSO 1: Adicionar a UNIQUE constraint na tabela linhas_cobertura
-- Garante a nível de base de dados que não existem registros duplicados para o mesmo plano, turma e disciplina.
ALTER TABLE `linhas_cobertura` 
  ADD UNIQUE KEY `uk_plano_turma_disciplina` (`plano_id`, `turma_id`, `disciplina_id`);


-- PASSO 2: Query de Preview (SELECT)
-- Conta a quantidade exata de linhas que serão inseridas por curso antes da execução real.
SELECT c.nome AS curso_nome, COUNT(*) AS linhas_a_criar
FROM turmas t
JOIN disciplinas d ON t.disciplina_id = d.id
JOIN cursos c ON d.curso_id = c.id
JOIN planos_cobertura pc ON pc.curso_id = c.id AND pc.ano_lectivo = '2026/27'
LEFT JOIN linhas_cobertura lc ON lc.plano_id = pc.id AND lc.turma_id = t.id AND lc.disciplina_id = d.id
WHERE d.activo = 1 AND lc.id IS NULL
GROUP BY c.nome;


-- PASSO 3: Inserção das linhas de cobertura em falta no plano
-- Insere as linhas com as turmas sincronizadas que não estavam mapeadas no plano de cobertura.
INSERT INTO `linhas_cobertura` (`plano_id`, `disciplina_id`, `turma_id`, `conformidade`, `regime`, `parecer`)
SELECT 
    pc.id AS plano_id, 
    d.id AS disciplina_id, 
    t.id AS turma_id, 
    'Por verificar' AS conformidade, 
    'Tempo Parcial' AS regime, 
    'Manter' AS parecer
FROM turmas t
JOIN disciplinas d ON t.disciplina_id = d.id
JOIN cursos c ON d.curso_id = c.id
JOIN planos_cobertura pc ON pc.curso_id = c.id AND pc.ano_lectivo = '2026/27'
LEFT JOIN linhas_cobertura lc ON lc.plano_id = pc.id AND lc.turma_id = t.id AND lc.disciplina_id = d.id
WHERE d.activo = 1 AND lc.id IS NULL;
