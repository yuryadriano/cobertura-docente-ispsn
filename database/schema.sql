-- MariaDB dump 10.19  Distrib 10.4.19-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: sftcoordenacao_db
-- ------------------------------------------------------
-- Server version	10.4.19-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cursos`
--

DROP TABLE IF EXISTS `cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grau` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Licenciatura',
  `duracao_anos` int(11) DEFAULT 4,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cvs_estruturados`
--

DROP TABLE IF EXISTS `cvs_estruturados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_estruturados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `telefone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bilhete_identidade` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grau_academico` enum('Licenciado','Mestre','Doutor') COLLATE utf8mb4_unicode_ci NOT NULL,
  `especialidade` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tem_inaarees` tinyint(1) DEFAULT 0,
  `tem_agregacao_pedag` tinyint(1) DEFAULT 0,
  `categoria_carreira` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Assistente',
  `instituicao_atual` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regime_contratual` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anos_experiencia_es` int(11) DEFAULT 0,
  `producao_cientifica_3a` int(11) DEFAULT 0,
  `linhas_pesquisa` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publicacoes_json` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cursos_ministrados` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `outras_atividades` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `formacoes_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`formacoes_json`)),
  `experiencias_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`experiencias_json`)),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `docente_id` (`docente_id`),
  CONSTRAINT `cvs_estruturados_ibfk_1` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disciplinas`
--

DROP TABLE IF EXISTS `disciplinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disciplinas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ano_curricular` int(11) NOT NULL CHECK (`ano_curricular` between 1 and 5),
  `semestre` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL CHECK (`semestre` in ('I','II','Anual')),
  `carga_horaria_semanal` int(11) DEFAULT 0,
  `creditos` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_disciplinas_curso_ano` (`curso_id`,`ano_curricular`,`semestre`),
  CONSTRAINT `disciplinas_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1208 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `docentes`
--

DROP TABLE IF EXISTS `docentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grau_academico` enum('Licenciado','Mestre','Doutor') COLLATE utf8mb4_unicode_ci DEFAULT 'Licenciado',
  `especialidade` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT 'N├úo identificada',
  `tem_inaarees` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'N├úo',
  `tem_agregacao_pedag` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'N├úo',
  `categoria_carreira` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Assistente',
  `anos_experiencia_es` int(11) DEFAULT 0,
  `producao_cientifica_3a` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9991 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documentos_docentes`
--

DROP TABLE IF EXISTS `documentos_docentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentos_docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `tipo` enum('cv','certificados','diplomas','bi','inaarees','agregacao_pedag') COLLATE utf8mb4_unicode_ci NOT NULL,
  `caminho_ficheiro` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('V├ílido','Pendente','Em falta') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendente',
  `validado_por` int(11) DEFAULT NULL,
  `validade` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `validado_por` (`validado_por`),
  KEY `idx_documentos_docente_tipo` (`docente_id`,`tipo`,`estado`),
  CONSTRAINT `documentos_docentes_ibfk_1` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documentos_docentes_ibfk_2` FOREIGN KEY (`validado_por`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historico_aprovacoes`
--

DROP TABLE IF EXISTS `historico_aprovacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historico_aprovacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plano_id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `acao` enum('Submetido','Aprovado','Devolvido') COLLATE utf8mb4_unicode_ci NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `plano_id` (`plano_id`),
  KEY `utilizador_id` (`utilizador_id`),
  CONSTRAINT `historico_aprovacoes_ibfk_1` FOREIGN KEY (`plano_id`) REFERENCES `planos_cobertura` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historico_aprovacoes_ibfk_2` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `linhas_cobertura`
--

DROP TABLE IF EXISTS `linhas_cobertura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `linhas_cobertura` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plano_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `turma_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `docente_id` int(11) DEFAULT NULL,
  `conformidade` enum('Sim','N├úo','Por verificar') COLLATE utf8mb4_unicode_ci DEFAULT 'Por verificar',
  `justificacao` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regime` enum('Tempo Integral','Tempo Parcial','Colaborador') COLLATE utf8mb4_unicode_ci DEFAULT 'Tempo Parcial',
  `categoria_carreira` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Assistente',
  `parecer` enum('Manter','Manter c/ acompanhamento','Substituir','Recrutar') COLLATE utf8mb4_unicode_ci DEFAULT 'Manter',
  `observacoes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `decisao_aprovacao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Aprovar',
  PRIMARY KEY (`id`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `idx_linhas_plano_docente` (`plano_id`,`docente_id`),
  KEY `idx_linhas_docente_conf` (`docente_id`,`conformidade`),
  CONSTRAINT `linhas_cobertura_ibfk_1` FOREIGN KEY (`plano_id`) REFERENCES `planos_cobertura` (`id`) ON DELETE CASCADE,
  CONSTRAINT `linhas_cobertura_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `linhas_cobertura_ibfk_3` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1207 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `planos_cobertura`
--

DROP TABLE IF EXISTS `planos_cobertura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `planos_cobertura` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `ano_lectivo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2026/27',
  `estado` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Rascunho',
  `criado_por` int(11) DEFAULT NULL,
  `data_submissao` datetime DEFAULT NULL,
  `data_aprovacao` datetime DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `chefe_depto_id` int(11) DEFAULT NULL,
  `data_aprovacao_depto` datetime DEFAULT NULL,
  `parecer_depto` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `presidente_id` int(11) DEFAULT NULL,
  `data_validacao_pr` datetime DEFAULT NULL,
  `parecer_pr` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_curso_ano` (`curso_id`,`ano_lectivo`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_planos_ano_estado` (`ano_lectivo`,`estado`),
  CONSTRAINT `planos_cobertura_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `planos_cobertura_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `turmas`
--

DROP TABLE IF EXISTS `turmas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `turmas` (
  `id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `docente_id` int(11) DEFAULT NULL,
  `designacao` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sumarios_registados` int(11) DEFAULT 0,
  `sumarios_previstos` int(11) DEFAULT 200,
  `programa_carregado` tinyint(1) DEFAULT 1,
  `dosificacao_carregada` tinyint(1) DEFAULT 1,
  `notas_no_prazo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'Sim',
  `inquerito_media` decimal(3,2) DEFAULT 4.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `docente_id` (`docente_id`),
  KEY `idx_turmas_disciplina_docente` (`disciplina_id`,`docente_id`),
  CONSTRAINT `turmas_ibfk_1` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `turmas_ibfk_2` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `utilizadores`
--

DROP TABLE IF EXISTS `utilizadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilizadores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `perfil` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `utilizadores_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `vw_diagnostico_risco_academico`
--

DROP TABLE IF EXISTS `vw_diagnostico_risco_academico`;
/*!50001 DROP VIEW IF EXISTS `vw_diagnostico_risco_academico`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `vw_diagnostico_risco_academico` (
  `linha_id` tinyint NOT NULL,
  `curso_id` tinyint NOT NULL,
  `ano_lectivo` tinyint NOT NULL,
  `disciplina_nome` tinyint NOT NULL,
  `turma_nome` tinyint NOT NULL,
  `docente_nome` tinyint NOT NULL,
  `conformidade` tinyint NOT NULL,
  `nivel_risco` tinyint NOT NULL,
  `gravidade_risco` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_docentes_capacidade_carga`
--

DROP TABLE IF EXISTS `vw_docentes_capacidade_carga`;
/*!50001 DROP VIEW IF EXISTS `vw_docentes_capacidade_carga`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `vw_docentes_capacidade_carga` (
  `docente_id` tinyint NOT NULL,
  `docente_nome` tinyint NOT NULL,
  `grau_academico` tinyint NOT NULL,
  `especialidade` tinyint NOT NULL,
  `tem_inaarees` tinyint NOT NULL,
  `tem_agregacao_pedag` tinyint NOT NULL,
  `categoria_carreira` tinyint NOT NULL,
  `soma_horas_semanais` tinyint NOT NULL,
  `num_cursos` tinyint NOT NULL,
  `num_turmas` tinyint NOT NULL,
  `estado_capacidade` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_docentes_sobrecarga`
--

DROP TABLE IF EXISTS `vw_docentes_sobrecarga`;
/*!50001 DROP VIEW IF EXISTS `vw_docentes_sobrecarga`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `vw_docentes_sobrecarga` (
  `docente_id` tinyint NOT NULL,
  `docente_nome` tinyint NOT NULL,
  `total_cursos` tinyint NOT NULL,
  `soma_horas_semanais` tinyint NOT NULL,
  `estado_capacidade` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_estatisticas_cobertura`
--

DROP TABLE IF EXISTS `vw_estatisticas_cobertura`;
/*!50001 DROP VIEW IF EXISTS `vw_estatisticas_cobertura`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `vw_estatisticas_cobertura` (
  `curso_id` tinyint NOT NULL,
  `curso_codigo` tinyint NOT NULL,
  `curso_nome` tinyint NOT NULL,
  `ano_lectivo` tinyint NOT NULL,
  `estado_plano` tinyint NOT NULL,
  `num_turmas` tinyint NOT NULL,
  `total_uc` tinyint NOT NULL,
  `uc_atribuidas` tinyint NOT NULL,
  `conf_sim` tinyint NOT NULL,
  `conf_parcial` tinyint NOT NULL,
  `conf_nao` tinyint NOT NULL,
  `conf_ni` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_linhas_cobertura_detalhada`
--

DROP TABLE IF EXISTS `vw_linhas_cobertura_detalhada`;
/*!50001 DROP VIEW IF EXISTS `vw_linhas_cobertura_detalhada`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `vw_linhas_cobertura_detalhada` (
  `id` tinyint NOT NULL,
  `linha_id` tinyint NOT NULL,
  `plano_id` tinyint NOT NULL,
  `disciplina_id` tinyint NOT NULL,
  `turma_id` tinyint NOT NULL,
  `docente_id` tinyint NOT NULL,
  `conformidade` tinyint NOT NULL,
  `justificacao` tinyint NOT NULL,
  `regime` tinyint NOT NULL,
  `categoria_carreira` tinyint NOT NULL,
  `parecer` tinyint NOT NULL,
  `decisao_aprovacao` tinyint NOT NULL,
  `observacoes` tinyint NOT NULL,
  `updated_at` tinyint NOT NULL,
  `curso_id` tinyint NOT NULL,
  `ano_lectivo` tinyint NOT NULL,
  `estado_plano` tinyint NOT NULL,
  `disciplina_nome` tinyint NOT NULL,
  `ano_curricular` tinyint NOT NULL,
  `semestre` tinyint NOT NULL,
  `carga_horaria_semanal` tinyint NOT NULL,
  `creditos` tinyint NOT NULL,
  `turma_nome` tinyint NOT NULL,
  `sumarios_registados` tinyint NOT NULL,
  `sumarios_previstos` tinyint NOT NULL,
  `programa_carregado` tinyint NOT NULL,
  `dosificacao_carregada` tinyint NOT NULL,
  `notas_no_prazo` tinyint NOT NULL,
  `inquerito_media` tinyint NOT NULL,
  `docente_nome` tinyint NOT NULL,
  `docente_grau` tinyint NOT NULL,
  `docente_especialidade` tinyint NOT NULL,
  `docente_inaarees` tinyint NOT NULL,
  `docente_agregacao` tinyint NOT NULL,
  `docente_num_cursos` tinyint NOT NULL,
  `docente_horas_semanais` tinyint NOT NULL,
  `docente_estado_capacidade` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_matchmaking_docentes`
--

DROP TABLE IF EXISTS `vw_matchmaking_docentes`;
/*!50001 DROP VIEW IF EXISTS `vw_matchmaking_docentes`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `vw_matchmaking_docentes` (
  `disciplina_id` tinyint NOT NULL,
  `disciplina_nome` tinyint NOT NULL,
  `curso_id` tinyint NOT NULL,
  `docente_id` tinyint NOT NULL,
  `docente_nome` tinyint NOT NULL,
  `grau_academico` tinyint NOT NULL,
  `especialidade` tinyint NOT NULL,
  `tem_inaarees` tinyint NOT NULL,
  `soma_horas_semanais` tinyint NOT NULL,
  `num_cursos` tinyint NOT NULL,
  `estado_capacidade` tinyint NOT NULL,
  `pontuacao_compatibilidade` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_resumo_documental`
--

DROP TABLE IF EXISTS `vw_resumo_documental`;
/*!50001 DROP VIEW IF EXISTS `vw_resumo_documental`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `vw_resumo_documental` (
  `docente_id` tinyint NOT NULL,
  `docente_nome` tinyint NOT NULL,
  `total_documentos` tinyint NOT NULL,
  `documentos_validos` tinyint NOT NULL,
  `documentos_pendentes` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `vw_diagnostico_risco_academico`
--

/*!50001 DROP TABLE IF EXISTS `vw_diagnostico_risco_academico`*/;
/*!50001 DROP VIEW IF EXISTS `vw_diagnostico_risco_academico`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_diagnostico_risco_academico` AS select `lc`.`linha_id` AS `linha_id`,`lc`.`curso_id` AS `curso_id`,`lc`.`ano_lectivo` AS `ano_lectivo`,`lc`.`disciplina_nome` AS `disciplina_nome`,`lc`.`turma_nome` AS `turma_nome`,`lc`.`docente_nome` AS `docente_nome`,`lc`.`conformidade` AS `conformidade`,case when `lc`.`docente_id` is null then 'Sem Docente Atribu├¡do' when `lc`.`docente_estado_capacidade` = 'Sobregregado' then 'Docente Sobregregado' when `lc`.`inquerito_media` < 3.50 then 'Inqu├®rito Pedag├│gico Baixo' when `lc`.`notas_no_prazo` = 'N├úo' then 'Atraso nas Notas' when `lc`.`sumarios_registados` < `lc`.`sumarios_previstos` * 0.70 then 'Atraso nos Sum├írios' else 'Normal' end AS `nivel_risco`,case when `lc`.`docente_id` is null then 3 when `lc`.`docente_estado_capacidade` = 'Sobregregado' then 2 when `lc`.`inquerito_media` < 3.50 then 2 when `lc`.`notas_no_prazo` = 'N├úo' then 1 else 0 end AS `gravidade_risco` from `vw_linhas_cobertura_detalhada` `lc` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_docentes_capacidade_carga`
--

/*!50001 DROP TABLE IF EXISTS `vw_docentes_capacidade_carga`*/;
/*!50001 DROP VIEW IF EXISTS `vw_docentes_capacidade_carga`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_docentes_capacidade_carga` AS select `d`.`id` AS `docente_id`,`d`.`nome` AS `docente_nome`,`d`.`grau_academico` AS `grau_academico`,`d`.`especialidade` AS `especialidade`,`d`.`tem_inaarees` AS `tem_inaarees`,`d`.`tem_agregacao_pedag` AS `tem_agregacao_pedag`,`d`.`categoria_carreira` AS `categoria_carreira`,coalesce(sum(`d_disc`.`carga_horaria_semanal`),0) AS `soma_horas_semanais`,count(distinct `pc`.`curso_id`) AS `num_cursos`,count(distinct `lc`.`turma_id`) AS `num_turmas`,case when count(distinct `pc`.`curso_id`) >= 3 or coalesce(sum(`d_disc`.`carga_horaria_semanal`),0) > 20 then 'Sobregregado' when coalesce(sum(`d_disc`.`carga_horaria_semanal`),0) between 14 and 20 then 'No Limite' else 'Dispon├¡vel' end AS `estado_capacidade` from (((`docentes` `d` left join `linhas_cobertura` `lc` on(`d`.`id` = `lc`.`docente_id`)) left join `planos_cobertura` `pc` on(`lc`.`plano_id` = `pc`.`id`)) left join `disciplinas` `d_disc` on(`lc`.`disciplina_id` = `d_disc`.`id`)) group by `d`.`id`,`d`.`nome`,`d`.`grau_academico`,`d`.`especialidade`,`d`.`tem_inaarees`,`d`.`tem_agregacao_pedag`,`d`.`categoria_carreira` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_docentes_sobrecarga`
--

/*!50001 DROP TABLE IF EXISTS `vw_docentes_sobrecarga`*/;
/*!50001 DROP VIEW IF EXISTS `vw_docentes_sobrecarga`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_docentes_sobrecarga` AS select `vw_docentes_capacidade_carga`.`docente_id` AS `docente_id`,`vw_docentes_capacidade_carga`.`docente_nome` AS `docente_nome`,`vw_docentes_capacidade_carga`.`num_cursos` AS `total_cursos`,`vw_docentes_capacidade_carga`.`soma_horas_semanais` AS `soma_horas_semanais`,`vw_docentes_capacidade_carga`.`estado_capacidade` AS `estado_capacidade` from `vw_docentes_capacidade_carga` where `vw_docentes_capacidade_carga`.`estado_capacidade` = 'Sobregregado' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_estatisticas_cobertura`
--

/*!50001 DROP TABLE IF EXISTS `vw_estatisticas_cobertura`*/;
/*!50001 DROP VIEW IF EXISTS `vw_estatisticas_cobertura`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_estatisticas_cobertura` AS select `c`.`id` AS `curso_id`,`c`.`codigo` AS `curso_codigo`,`c`.`nome` AS `curso_nome`,`pc`.`ano_lectivo` AS `ano_lectivo`,`pc`.`estado` AS `estado_plano`,count(distinct `lc`.`turma_id`) AS `num_turmas`,count(`lc`.`id`) AS `total_uc`,sum(case when `lc`.`docente_id` is not null then 1 else 0 end) AS `uc_atribuidas`,sum(case when `lc`.`conformidade` = 'Sim' then 1 else 0 end) AS `conf_sim`,sum(case when `lc`.`conformidade` = 'Parcial' then 1 else 0 end) AS `conf_parcial`,sum(case when `lc`.`conformidade` = 'N├úo' then 1 else 0 end) AS `conf_nao`,sum(case when `lc`.`conformidade` = 'Por verificar' then 1 else 0 end) AS `conf_ni` from ((`cursos` `c` left join `planos_cobertura` `pc` on(`c`.`id` = `pc`.`curso_id`)) left join `linhas_cobertura` `lc` on(`pc`.`id` = `lc`.`plano_id`)) where `c`.`activo` = 1 group by `c`.`id`,`c`.`codigo`,`c`.`nome`,`pc`.`ano_lectivo`,`pc`.`estado` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_linhas_cobertura_detalhada`
--

/*!50001 DROP TABLE IF EXISTS `vw_linhas_cobertura_detalhada`*/;
/*!50001 DROP VIEW IF EXISTS `vw_linhas_cobertura_detalhada`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_linhas_cobertura_detalhada` AS select `lc`.`id` AS `id`,`lc`.`id` AS `linha_id`,`lc`.`plano_id` AS `plano_id`,`lc`.`disciplina_id` AS `disciplina_id`,`lc`.`turma_id` AS `turma_id`,`lc`.`docente_id` AS `docente_id`,`lc`.`conformidade` AS `conformidade`,`lc`.`justificacao` AS `justificacao`,`lc`.`regime` AS `regime`,`lc`.`categoria_carreira` AS `categoria_carreira`,`lc`.`parecer` AS `parecer`,coalesce(`lc`.`decisao_aprovacao`,'Aprovar') AS `decisao_aprovacao`,`lc`.`observacoes` AS `observacoes`,`lc`.`updated_at` AS `updated_at`,`pc`.`curso_id` AS `curso_id`,`pc`.`ano_lectivo` AS `ano_lectivo`,`pc`.`estado` AS `estado_plano`,`d`.`nome` AS `disciplina_nome`,`d`.`ano_curricular` AS `ano_curricular`,`d`.`semestre` AS `semestre`,`d`.`carga_horaria_semanal` AS `carga_horaria_semanal`,`d`.`creditos` AS `creditos`,`t`.`designacao` AS `turma_nome`,`t`.`sumarios_registados` AS `sumarios_registados`,`t`.`sumarios_previstos` AS `sumarios_previstos`,`t`.`programa_carregado` AS `programa_carregado`,`t`.`dosificacao_carregada` AS `dosificacao_carregada`,`t`.`notas_no_prazo` AS `notas_no_prazo`,`t`.`inquerito_media` AS `inquerito_media`,`doc`.`nome` AS `docente_nome`,`doc`.`grau_academico` AS `docente_grau`,`doc`.`especialidade` AS `docente_especialidade`,`doc`.`tem_inaarees` AS `docente_inaarees`,`doc`.`tem_agregacao_pedag` AS `docente_agregacao`,`cap`.`num_cursos` AS `docente_num_cursos`,`cap`.`soma_horas_semanais` AS `docente_horas_semanais`,`cap`.`estado_capacidade` AS `docente_estado_capacidade` from (((((`linhas_cobertura` `lc` join `planos_cobertura` `pc` on(`lc`.`plano_id` = `pc`.`id`)) join `disciplinas` `d` on(`lc`.`disciplina_id` = `d`.`id`)) left join `turmas` `t` on(`lc`.`turma_id` = `t`.`id`)) left join `docentes` `doc` on(`lc`.`docente_id` = `doc`.`id`)) left join `vw_docentes_capacidade_carga` `cap` on(`lc`.`docente_id` = `cap`.`docente_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_matchmaking_docentes`
--

/*!50001 DROP TABLE IF EXISTS `vw_matchmaking_docentes`*/;
/*!50001 DROP VIEW IF EXISTS `vw_matchmaking_docentes`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_matchmaking_docentes` AS select `disc`.`id` AS `disciplina_id`,`disc`.`nome` AS `disciplina_nome`,`disc`.`curso_id` AS `curso_id`,`d`.`id` AS `docente_id`,`d`.`nome` AS `docente_nome`,`d`.`grau_academico` AS `grau_academico`,`d`.`especialidade` AS `especialidade`,`d`.`tem_inaarees` AS `tem_inaarees`,`cap`.`soma_horas_semanais` AS `soma_horas_semanais`,`cap`.`num_cursos` AS `num_cursos`,`cap`.`estado_capacidade` AS `estado_capacidade`,case when `d`.`grau_academico` = 'Doutor' then 40 when `d`.`grau_academico` = 'Mestre' then 30 else 15 end + case when `d`.`tem_inaarees` = 'Sim' then 25 else 0 end + case when lcase(`d`.`especialidade`) like concat('%',lcase(substring_index(`disc`.`nome`,' ',1)),'%') then 25 else 10 end + case when `cap`.`estado_capacidade` = 'Dispon├¡vel' then 10 when `cap`.`estado_capacidade` = 'No Limite' then 5 else 0 end AS `pontuacao_compatibilidade` from ((`disciplinas` `disc` join `docentes` `d`) left join `vw_docentes_capacidade_carga` `cap` on(`d`.`id` = `cap`.`docente_id`)) where `d`.`activo` = 1 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_resumo_documental`
--

/*!50001 DROP TABLE IF EXISTS `vw_resumo_documental`*/;
/*!50001 DROP VIEW IF EXISTS `vw_resumo_documental`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_resumo_documental` AS select `d`.`id` AS `docente_id`,`d`.`nome` AS `docente_nome`,count(`doc`.`id`) AS `total_documentos`,sum(case when `doc`.`estado` = 'V├ílido' then 1 else 0 end) AS `documentos_validos`,sum(case when `doc`.`estado` = 'Pendente' then 1 else 0 end) AS `documentos_pendentes` from (`docentes` `d` left join `documentos_docentes` `doc` on(`d`.`id` = `doc`.`docente_id`)) group by `d`.`id`,`d`.`nome` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-11  9:31:03
