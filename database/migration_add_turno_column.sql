-- Migration: Adicionar coluna 'turno' na tabela 'turmas' e atualizar vista SQL
-- sftcoordenacao — ISPSN 2026/27

-- 1. Alteração de Schema
ALTER TABLE `turmas`
  ADD COLUMN `turno` ENUM('Manhã', 'Tarde', 'Noite', 'Pós-Laboral') DEFAULT NULL AFTER `designacao`;

-- 2.1 Popular Formato A
UPDATE `turmas` 
SET `turno` = 'Manhã' 
WHERE `designacao` LIKE 'Turma %' AND `designacao` LIKE '%MA)';

-- 2.2 Popular Formato B (resistente a encoding/acentuação)
UPDATE `turmas`
SET `turno` = CASE
    WHEN `designacao` LIKE 'TURMA-%M %' OR `designacao` LIKE '%(Manh%' THEN 'Manhã'
    WHEN `designacao` LIKE 'TURMA-%T %' OR `designacao` LIKE '%(Tarde%' THEN 'Tarde'
    WHEN `designacao` LIKE 'TURMA-%P %' OR `designacao` LIKE '%s-Laboral%' THEN 'Pós-Laboral'
    WHEN `designacao` LIKE 'TURMA-%N %' OR `designacao` LIKE '%(Noite%' THEN 'Noite'
    ELSE NULL
END
WHERE `designacao` LIKE 'TURMA-%';

-- 3. Atualizar Vista SQL
CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_linhas_cobertura_detalhada` AS 
SELECT 
    `lc`.`id` AS `id`,
    `lc`.`id` AS `linha_id`,
    `lc`.`plano_id` AS `plano_id`,
    `lc`.`disciplina_id` AS `disciplina_id`,
    `lc`.`turma_id` AS `turma_id`,
    `lc`.`docente_id` AS `docente_id`,
    `lc`.`conformidade` AS `conformidade`,
    `lc`.`justificacao` AS `justificacao`,
    `lc`.`regime` AS `regime`,
    `lc`.`categoria_carreira` AS `categoria_carreira`,
    `lc`.`parecer` AS `parecer`,
    COALESCE(`lc`.`decisao_aprovacao`, 'Aprovar') AS `decisao_aprovacao`,
    `lc`.`observacoes` AS `observacoes`,
    `lc`.`updated_at` AS `updated_at`,
    `pc`.`curso_id` AS `curso_id`,
    `pc`.`ano_lectivo` AS `ano_lectivo`,
    `pc`.`estado` AS `estado_plano`,
    `d`.`nome` AS `disciplina_nome`,
    `d`.`ano_curricular` AS `ano_curricular`,
    `d`.`semestre` AS `semestre`,
    `d`.`carga_horaria_semanal` AS `carga_horaria_semanal`,
    `d`.`creditos` AS `creditos`,
    `t`.`designacao` AS `turma_nome`,
    `t`.`turno` AS `turno`,
    `t`.`sumarios_registados` AS `sumarios_registados`,
    `t`.`sumarios_previstos` AS `sumarios_previstos`,
    `t`.`programa_carregado` AS `programa_carregado`,
    `t`.`dosificacao_carregada` AS `dosificacao_carregada`,
    `t`.`notas_no_prazo` AS `notas_no_prazo`,
    `t`.`inquerito_media` AS `inquerito_media`,
    `doc`.`nome` AS `docente_nome`,
    `doc`.`grau_academico` AS `docente_grau`,
    `doc`.`especialidade` AS `docente_especialidade`,
    `doc`.`tem_inaarees` AS `docente_inaarees`,
    `doc`.`tem_agregacao_pedag` AS `docente_agregacao`,
    `cap`.`num_cursos` AS `docente_num_cursos`,
    `cap`.`soma_horas_semanais` AS `docente_horas_semanais`,
    `cap`.`estado_capacidade` AS `docente_estado_capacidade`
FROM `linhas_cobertura` `lc` 
JOIN `planos_cobertura` `pc` ON `lc`.`plano_id` = `pc`.`id`
JOIN `disciplinas` `d` ON `lc`.`disciplina_id` = `d`.`id`
LEFT JOIN `turmas` `t` ON `lc`.`turma_id` = `t`.`id`
LEFT JOIN `docentes` `doc` ON `lc`.`docente_id` = `doc`.`id`
LEFT JOIN `vw_docentes_capacidade_carga` `cap` ON `lc`.`docente_id` = `cap`.`docente_id`;
