<?php
/**
 * Modelo de Integração & Sincronização Enterprise
 * ISPSN — Módulo de Cobertura Docente ↔ Sistema de Gestão Escolar
 */

require_once __DIR__ . '/../../config/database.php';

class IntegracaoModel {
    private PDO $db;
    private string $apiKey;

    public function __construct() {
        $this->db = Database::getInstance();
        // Chave de serviço (pode ser sobrescrita via variável de ambiente)
        $this->apiKey = getenv('INTEGRATION_API_KEY') ?: 'ISPSN_INTEGRATION_KEY_2026_SECRET_TOKEN';
    }

    /**
     * Valida o token Bearer enviado no cabeçalho HTTP Authorization
     */
    public function validateToken(?string $authHeader): bool {
        if (empty($authHeader)) {
            return false;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            return hash_equals($this->apiKey, $token);
        }

        return false;
    }

    /**
     * Exporta os dados oficiais do Plano de Cobertura de um curso para o Gestão Escolar
     */
    public function getPlanoExportData(int $cursoId, string $anoLectivo = '2026/27'): ?array {
        $stmtCurso = $this->db->prepare("SELECT id, codigo, nome, grau, duracao_anos FROM cursos WHERE id = ?");
        $stmtCurso->execute([$cursoId]);
        $curso = $stmtCurso->fetch(PDO::FETCH_ASSOC);

        if (!$curso) {
            return null;
        }

        $stmtPlano = $this->db->prepare("
            SELECT id, curso_id, ano_lectivo, estado, data_submissao, data_aprovacao_depto, data_validacao_pr, updated_at
            FROM planos_cobertura
            WHERE curso_id = ? AND ano_lectivo = ?
            LIMIT 1
        ");
        $stmtPlano->execute([$cursoId, $anoLectivo]);
        $plano = $stmtPlano->fetch(PDO::FETCH_ASSOC);

        if (!$plano) {
            return [
                'curso'       => $curso,
                'plano'       => null,
                'ano_lectivo' => $anoLectivo,
                'total_linhas'=> 0,
                'atribuicoes' => []
            ];
        }

        $stmtLinhas = $this->db->prepare("
            SELECT 
                linha_id,
                disciplina_id,
                disciplina_nome,
                ano_curricular,
                semestre,
                carga_horaria_semanal,
                creditos,
                turma_id,
                turma_nome,
                docente_id,
                docente_nome,
                docente_grau,
                docente_especialidade,
                docente_inaarees,
                conformidade,
                regime,
                categoria_carreira,
                parecer,
                decisao_aprovacao,
                sumarios_registados,
                sumarios_previstos,
                programa_carregado,
                dosificacao_carregada,
                notas_no_prazo,
                inquerito_media
            FROM vw_linhas_cobertura_detalhada
            WHERE plano_id = ?
            ORDER BY ano_curricular ASC, semestre ASC, disciplina_id ASC, id ASC
        ");
        $stmtLinhas->execute([$plano['id']]);
        $linhas = $stmtLinhas->fetchAll(PDO::FETCH_ASSOC);

        return [
            'curso'        => $curso,
            'plano'        => $plano,
            'ano_lectivo'  => $anoLectivo,
            'total_linhas' => count($linhas),
            'atribuicoes'  => $linhas
        ];
    }

    /**
     * Sincroniza em lote a lista de docentes vindos do Gestão Escolar (UPSERT Seguro)
     */
    public function syncDocentes(array $docentesList): array {
        $stats = [
            'total'       => count($docentesList),
            'inseridos'   => 0,
            'atualizados' => 0,
            'erros'       => []
        ];

        if (empty($docentesList)) {
            return $stats;
        }

        $this->db->beginTransaction();
        try {
            $stmtCheck = $this->db->prepare("SELECT id FROM docentes WHERE email = ? OR id = ? LIMIT 1");
            $stmtInsert = $this->db->prepare("
                INSERT INTO docentes (id, nome, email, grau_academico, especialidade, tem_inaarees, tem_agregacao_pedag, categoria_carreira, anos_experiencia_es, producao_cientifica_3a, activo)
                VALUES (:id, :nome, :email, :grau, :especialidade, :inaarees, :agregacao, :categoria, :anos_exp, :prod_cient, :activo)
            ");
            $stmtUpdate = $this->db->prepare("
                UPDATE docentes 
                SET nome = :nome,
                    grau_academico = :grau,
                    especialidade = :especialidade,
                    tem_inaarees = :inaarees,
                    tem_agregacao_pedag = :agregacao,
                    categoria_carreira = :categoria,
                    anos_experiencia_es = :anos_exp,
                    producao_cientifica_3a = :prod_cient,
                    activo = :activo
                WHERE id = :id
            ");

            foreach ($docentesList as $idx => $d) {
                $nome = trim($d['nome'] ?? '');
                $email = trim($d['email'] ?? '');
                $id = !empty($d['id']) ? (int)$d['id'] : null;

                if (empty($nome)) {
                    $stats['erros'][] = "Registo #{$idx}: Nome é obrigatório.";
                    continue;
                }

                $grau = in_array($d['grau_academico'] ?? '', ['Licenciado', 'Mestre', 'Doutor']) ? $d['grau_academico'] : 'Licenciado';
                $especialidade = trim($d['especialidade'] ?? 'Não identificada');
                $inaarees = ($d['tem_inaarees'] ?? false) ? 'Sim' : 'Não';
                $agregacao = ($d['tem_agregacao_pedag'] ?? false) ? 'Sim' : 'Não';
                $categoria = trim($d['categoria_carreira'] ?? 'Assistente');
                $anosExp = (int)($d['anos_experiencia_es'] ?? 0);
                $prodCient = (int)($d['producao_cientifica_3a'] ?? 0);
                $activo = isset($d['activo']) ? ((int)$d['activo'] ? 1 : 0) : 1;

                $stmtCheck->execute([$email, $id]);
                $existingId = $stmtCheck->fetchColumn();

                if ($existingId) {
                    $stmtUpdate->execute([
                        ':id'            => $existingId,
                        ':nome'          => $nome,
                        ':grau'          => $grau,
                        ':especialidade' => $especialidade,
                        ':inaarees'      => $inaarees,
                        ':agregacao'     => $agregacao,
                        ':categoria'     => $categoria,
                        ':anos_exp'      => $anosExp,
                        ':prod_cient'    => $prodCient,
                        ':activo'        => $activo
                    ]);
                    $stats['atualizados']++;
                } else {
                    $stmtInsert->execute([
                        ':id'            => $id,
                        ':nome'          => $nome,
                        ':email'         => $email ?: null,
                        ':grau'          => $grau,
                        ':especialidade' => $especialidade,
                        ':inaarees'      => $inaarees,
                        ':agregacao'     => $agregacao,
                        ':categoria'     => $categoria,
                        ':anos_exp'      => $anosExp,
                        ':prod_cient'    => $prodCient,
                        ':activo'        => $activo
                    ]);
                    $stats['inseridos']++;
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $stats['erros'][] = "Erro transacional: " . $e->getMessage();
        }

        return $stats;
    }

    /**
     * Sincroniza métricas operacionais reais das turmas vindas do Gestão Escolar
     */
    public function syncMetricasOperacionais(array $metricasList): array {
        $stats = [
            'total'       => count($metricasList),
            'atualizados' => 0,
            'erros'       => []
        ];

        if (empty($metricasList)) {
            return $stats;
        }

        $this->db->beginTransaction();
        try {
            $stmtUpdate = $this->db->prepare("
                UPDATE turmas 
                SET sumarios_registados = :sum_reg,
                    sumarios_previstos   = :sum_prev,
                    programa_carregado   = :prog,
                    dosificacao_carregada= :dosif,
                    notas_no_prazo       = :notas,
                    inquerito_media      = :inq
                WHERE id = :id OR designacao LIKE :desig
            ");

            foreach ($metricasList as $idx => $m) {
                $turmaId = trim($m['turma_id'] ?? '');
                if (empty($turmaId)) {
                    $stats['erros'][] = "Item #{$idx}: Identificador da turma é obrigatório.";
                    continue;
                }

                $sumReg  = isset($m['sumarios_registados']) ? (int)$m['sumarios_registados'] : 0;
                $sumPrev = isset($m['sumarios_previstos']) ? (int)$m['sumarios_previstos'] : 200;
                $prog    = !empty($m['programa_carregado']) ? 1 : 0;
                $dosif   = !empty($m['dosificacao_carregada']) ? 1 : 0;
                $notas   = in_array($m['notas_no_prazo'] ?? '', ['Sim', 'Não']) ? $m['notas_no_prazo'] : 'Sim';
                $inq     = isset($m['inquerito_media']) ? (float)$m['inquerito_media'] : 4.00;

                $stmtUpdate->execute([
                    ':id'       => $turmaId,
                    ':desig'    => "%{$turmaId}%",
                    ':sum_reg'  => $sumReg,
                    ':sum_prev' => $sumPrev,
                    ':prog'     => $prog,
                    ':dosif'    => $dosif,
                    ':notas'    => $notas,
                    ':inq'      => $inq
                ]);

                if ($stmtUpdate->rowCount() > 0) {
                    $stats['atualizados']++;
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $stats['erros'][] = "Erro transacional: " . $e->getMessage();
        }

        return $stats;
    }

    /**
     * Regista eventos de sincronização para auditoria de TI
     */
    public function logSyncEvent(string $endpoint, string $metodo, int $statusCode, int $registos, float $tempoMs, array $detalhes): void {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $this->db->prepare("
                INSERT INTO sync_logs (endpoint, metodo, origem_ip, status_code, registos_processados, tempo_execucao_ms, detalhes_json)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $endpoint,
                $metodo,
                $ip,
                $statusCode,
                $registos,
                $tempoMs,
                json_encode($detalhes, JSON_UNESCAPED_UNICODE)
            ]);
        } catch (\Throwable $e) {
            // Não interromper o fluxo principal caso ocorra falha de log
        }
    }

    /**
     * Retorna os últimos logs de integração para diagnóstico
     */
    public function getRecentSyncLogs(int $limit = 50): array {
        $stmt = $this->db->prepare("
            SELECT id, endpoint, metodo, origem_ip, status_code, registos_processados, tempo_execucao_ms, detalhes_json, created_at
            FROM sync_logs
            ORDER BY id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna o estado global de saúde e estatísticas de sincronização
     */
    public function getIntegrationStatus(): array {
        $totalDocentes = (int)$this->db->query("SELECT count(*) FROM docentes WHERE activo = 1")->fetchColumn();
        $totalCursos   = (int)$this->db->query("SELECT count(*) FROM cursos WHERE activo = 1")->fetchColumn();
        $totalTurmas   = (int)$this->db->query("SELECT count(*) FROM turmas")->fetchColumn();
        $totalLinhas   = (int)$this->db->query("SELECT count(*) FROM linhas_cobertura")->fetchColumn();

        $stmtLastLog = $this->db->query("SELECT created_at, status_code, endpoint FROM sync_logs ORDER BY id DESC LIMIT 1");
        $lastLog = $stmtLastLog->fetch(PDO::FETCH_ASSOC);

        return [
            'status'         => 'ONLINE',
            'versao_api'     => '1.0.0-Enterprise',
            'ambiente'       => 'ISPSN Production/Staging',
            'total_docentes' => $totalDocentes,
            'total_cursos'   => $totalCursos,
            'total_turmas'   => $totalTurmas,
            'total_linhas'   => $totalLinhas,
            'ultimo_sync'    => $lastLog ? $lastLog['created_at'] : 'Nenhum registo',
            'ultimo_status'  => $lastLog ? (int)$lastLog['status_code'] : 200,
            'timestamp'      => date('c')
        ];
    }
}
