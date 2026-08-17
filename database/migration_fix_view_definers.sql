-- ====================================================================
-- MIGRAÇÃO DE CORREÇÃO DE DEFINER DAS VIEWS (MYSQL DOCKER / COOLIFY)
-- Define explicitamente DEFINER=`root`@`%` para evitar erro 1356 no mysqldump
-- ====================================================================

CREATE OR REPLACE DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `vw_matchmaking_docentes` AS
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

CREATE OR REPLACE DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `vw_docentes_sobrecarga` AS
SELECT 
    docente_id,
    docente_nome,
    num_cursos AS total_cursos,
    soma_horas_semanais,
    estado_capacidade
FROM `vw_docentes_capacidade_carga`
WHERE estado_capacidade = 'Sobregregado';

CREATE OR REPLACE DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `vw_resumo_documental` AS
SELECT 
    d.id AS docente_id,
    d.nome AS docente_nome,
    COUNT(doc.id) AS total_documentos,
    SUM(CASE WHEN doc.estado = 'Válido' THEN 1 ELSE 0 END) AS documentos_validos,
    SUM(CASE WHEN doc.estado = 'Pendente' THEN 1 ELSE 0 END) AS documentos_pendentes
FROM `docentes` d
LEFT JOIN `documentos_docentes` doc ON d.id = doc.docente_id
GROUP BY d.id, d.nome;
