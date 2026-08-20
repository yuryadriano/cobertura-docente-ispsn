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
     * Valida o token Bearer ou parâmetro GET/POST
     */
    public function validateToken(?string $authHeader, ?string $queryToken = null): bool {
        if (!empty($queryToken) && hash_equals($this->apiKey, $queryToken)) {
            return true;
        }

        if (empty($authHeader)) {
            return false;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            return hash_equals($this->apiKey, $token);
        }

        return hash_equals($this->apiKey, trim($authHeader));
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
                lc.id AS linha_id,
                pc.curso_id,
                lc.plano_id,
                lc.disciplina_id,
                d.codigo AS disciplina_codigo,
                d.nome AS disciplina_nome,
                d.ano_curricular,
                d.semestre,
                d.carga_horaria_semanal,
                d.creditos,
                lc.turma_id,
                t.designacao AS turma_nome,
                SUBSTRING_INDEX(t.id, '-D', 1) AS turma_codigo,
                lc.docente_id,
                doc.nome AS docente_nome,
                doc.email AS docente_email,
                doc.grau_academico AS docente_grau,
                doc.especialidade AS docente_especialidade,
                doc.tem_inaarees AS docente_inaarees,
                doc.tem_agregacao_pedag AS docente_agregacao,
                lc.conformidade,
                lc.regime,
                lc.categoria_carreira,
                lc.parecer,
                COALESCE(lc.decisao_aprovacao, 'Aprovar') AS decisao_aprovacao,
                t.sumarios_registados,
                t.sumarios_previstos,
                t.programa_carregado,
                t.dosificacao_carregada,
                t.notas_no_prazo,
                t.inquerito_media
            FROM linhas_cobertura lc
            JOIN planos_cobertura pc ON lc.plano_id = pc.id
            JOIN disciplinas d ON lc.disciplina_id = d.id
            LEFT JOIN turmas t ON lc.turma_id = t.id
            LEFT JOIN docentes doc ON lc.docente_id = doc.id
            WHERE lc.plano_id = ?
            ORDER BY d.ano_curricular ASC, d.semestre ASC, d.id ASC, lc.id ASC
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
     * Exporta os dados oficiais de TODOS OS CURSOS DE UMA VEZ (Global Export)
     */
    public function getPlanoExportTodos(string $anoLectivo = '2026/27'): array {
        $stmtCursos = $this->db->query("SELECT id, codigo, nome, grau, duracao_anos FROM cursos WHERE activo = 1 ORDER BY nome ASC");
        $cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [
            'ano_lectivo'   => $anoLectivo,
            'total_cursos'  => count($cursos),
            'total_linhas'  => 0,
            'cursos_planos' => []
        ];

        foreach ($cursos as $c) {
            $exportCurso = $this->getPlanoExportData((int)$c['id'], $anoLectivo);
            if ($exportCurso) {
                $resultado['total_linhas'] += $exportCurso['total_linhas'];
                $resultado['cursos_planos'][] = $exportCurso;
            }
        }

        return $resultado;
    }

    /**
     * Retorna lista completa de Cursos com seus IDs e Códigos
     */
    public function getCursosList(): array {
        $stmt = $this->db->query("
            SELECT id AS curso_id, codigo AS curso_codigo, nome AS curso_nome, grau, duracao_anos, activo
            FROM cursos
            ORDER BY nome ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna lista completa de Disciplinas com IDs para comparação
     */
    public function getDisciplinasList(?int $cursoId = null): array {
        $sql = "
            SELECT 
                d.id AS disciplina_id,
                d.curso_id,
                c.codigo AS curso_codigo,
                c.nome AS curso_nome,
                d.codigo AS disciplina_codigo,
                d.nome AS disciplina_nome,
                d.ano_curricular,
                d.semestre,
                d.carga_horaria_semanal,
                d.creditos,
                d.activo
            FROM disciplinas d
            JOIN cursos c ON d.curso_id = c.id
        ";
        if ($cursoId) {
            $sql .= " WHERE d.curso_id = ? ORDER BY d.ano_curricular ASC, d.semestre ASC, d.id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cursoId]);
        } else {
            $sql .= " ORDER BY c.nome ASC, d.ano_curricular ASC, d.semestre ASC, d.id ASC";
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna lista completa de Docentes com IDs para comparação
     */
    public function getDocentesList(): array {
        $stmt = $this->db->query("
            SELECT 
                id AS docente_id,
                nome AS docente_nome,
                email AS docente_email,
                grau_academico,
                especialidade,
                tem_inaarees,
                tem_agregacao_pedag,
                categoria_carreira,
                anos_experiencia_es,
                producao_cientifica_3a,
                activo
            FROM docentes
            ORDER BY nome ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Sincroniza em lote a lista de docentes vindos do Gestão Escolar (UPSERT Seguro com Documentos)
     */
    public function syncDocentes(array $docentesList): array {
        $stats = [
            'total'                   => count($docentesList),
            'inseridos'               => 0,
            'atualizados'             => 0,
            'documentos_sincronizados'=> 0,
            'erros'                   => []
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

            // Statements para sincronização de documentos do RH
            $stmtDocCheck = $this->db->prepare("SELECT id FROM documentos_docentes WHERE docente_id = ? AND tipo = ? LIMIT 1");
            $stmtDocInsert = $this->db->prepare("INSERT INTO documentos_docentes (docente_id, tipo, caminho_ficheiro, estado, validade) VALUES (?, ?, ?, ?, ?)");
            $stmtDocUpdate = $this->db->prepare("UPDATE documentos_docentes SET caminho_ficheiro = ?, estado = ?, validade = ? WHERE id = ?");
            $stmtUpdateIna = $this->db->prepare("UPDATE docentes SET tem_inaarees = 'Sim' WHERE id = ?");
            $stmtUpdatePed = $this->db->prepare("UPDATE docentes SET tem_agregacao_pedag = 'Sim' WHERE id = ?");

            $tipoMap = [
                'bi'               => 'bi',
                'bilhete'          => 'bi',
                'identificacao'    => 'bi',
                'cv'               => 'cv',
                'curriculum'       => 'cv',
                'certificados'     => 'certificados',
                'certificado'      => 'certificados',
                'cert'             => 'certificados',
                'diplomas'         => 'diplomas',
                'diploma'          => 'diplomas',
                'dip'              => 'diplomas',
                'inaarees'         => 'inaarees',
                'ina'              => 'inaarees',
                'homologacao'      => 'inaarees',
                'homologacao_inaarees' => 'inaarees',
                'agregacao_pedag'  => 'agregacao_pedag',
                'ped'              => 'agregacao_pedag',
                'agregacao'        => 'agregacao_pedag',
                'capacitacao'      => 'agregacao_pedag'
            ];

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

                $targetDocenteId = null;

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
                    $targetDocenteId = (int)$existingId;
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
                    $targetDocenteId = $id ? (int)$id : (int)$this->db->lastInsertId();
                    $stats['inseridos']++;
                }

                // Sincronizar Documentos do Docente se fornecidos no payload
                $docsList = $d['documentos'] ?? $d['anexos'] ?? $d['docs'] ?? [];
                if (is_array($docsList) && !empty($docsList) && $targetDocenteId) {
                    foreach ($docsList as $doc) {
                        if (!is_array($doc)) continue;

                        $rawTipo = strtolower(trim($doc['tipo'] ?? ''));
                        $tipoEnum = $tipoMap[$rawTipo] ?? null;
                        if (!$tipoEnum) continue;

                        $caminho = trim($doc['url'] ?? $doc['caminho_ficheiro'] ?? $doc['caminho'] ?? $doc['link'] ?? $doc['arquivo'] ?? '');
                        if (empty($caminho)) continue;

                        $estadoRaw = trim($doc['estado'] ?? 'Válido');
                        $estado = in_array($estadoRaw, ['Válido', 'Pendente', 'Em falta']) ? $estadoRaw : 'Válido';
                        $validade = !empty($doc['validade']) ? trim($doc['validade']) : null;

                        $stmtDocCheck->execute([$targetDocenteId, $tipoEnum]);
                        $existingDocId = $stmtDocCheck->fetchColumn();

                        if ($existingDocId) {
                            $stmtDocUpdate->execute([$caminho, $estado, $validade, $existingDocId]);
                        } else {
                            $stmtDocInsert->execute([$targetDocenteId, $tipoEnum, $caminho, $estado, $validade]);
                        }

                        if ($estado === 'Válido') {
                            if ($tipoEnum === 'inaarees') {
                                $stmtUpdateIna->execute([$targetDocenteId]);
                            } elseif ($tipoEnum === 'agregacao_pedag') {
                                $stmtUpdatePed->execute([$targetDocenteId]);
                            }
                        }

                        $stats['documentos_sincronizados']++;
                    }
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
