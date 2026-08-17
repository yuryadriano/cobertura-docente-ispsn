<?php
/**
 * Modelo de Dados: Docente
 * sftcoordenacao — Módulo de Cobertura Docente ISPSN 2026/27
 */

require_once __DIR__ . '/../../config/database.php';

class DocenteModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Retorna todos os docentes com métricas agregadas pré-calculadas via View SQL
     */
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT d.*, 
                   cap.num_cursos,
                   cap.num_turmas,
                   cap.soma_horas_semanais,
                   cap.estado_capacidade,
                   COALESCE(doc_res.documentos_validos, 0) AS total_docs_validos
            FROM docentes d
            LEFT JOIN vw_docentes_capacidade_carga cap ON d.id = cap.docente_id
            LEFT JOIN vw_resumo_documental doc_res ON d.id = doc_res.docente_id
            WHERE d.activo = 1
            ORDER BY d.nome ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Pesquisa e filtragem avançada de docentes (Busca inteligente)
     */
    public function searchAndFilter(array $filters = []): array {
        $sql = "
            SELECT d.*, 
                   cap.num_cursos,
                   cap.num_turmas,
                   cap.soma_horas_semanais,
                   cap.estado_capacidade
            FROM docentes d
            LEFT JOIN vw_docentes_capacidade_carga cap ON d.id = cap.docente_id
            WHERE d.activo = 1
        ";
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (d.nome LIKE :q OR d.especialidade LIKE :q OR d.email LIKE :q)";
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }

        if (!empty($filters['grau'])) {
            $sql .= " AND d.grau_academico = :grau";
            $params[':grau'] = $filters['grau'];
        }

        if (!empty($filters['inaarees'])) {
            $sql .= " AND d.tem_inaarees = :ina";
            $params[':ina'] = $filters['inaarees'];
        }

        if (!empty($filters['capacidade'])) {
            $sql .= " AND cap.estado_capacidade = :cap";
            $params[':cap'] = $filters['capacidade'];
        }

        $sql .= " ORDER BY d.nome ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT d.*, 
                   cap.num_cursos,
                   cap.num_turmas,
                   cap.soma_horas_semanais,
                   cap.estado_capacidade
            FROM docentes d
            LEFT JOIN vw_docentes_capacidade_carga cap ON d.id = cap.docente_id
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getCountCoursesAssigned(int $docenteId, string $anoLectivo = '2026/27'): int {
        $stmt = $this->db->prepare("
            SELECT num_cursos FROM vw_docentes_capacidade_carga WHERE docente_id = ?
        ");
        $stmt->execute([$docenteId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int)$val : 0;
    }

    public function createDocente(array $data): array {
        $nome = trim($data['nome'] ?? '');
        $email = !empty($data['email']) ? trim(strtolower($data['email'])) : null;
        $grau = in_array($data['grau_academico'] ?? '', ['Licenciado', 'Mestre', 'Doutor']) ? $data['grau_academico'] : 'Licenciado';
        $especialidade = !empty($data['especialidade']) ? trim($data['especialidade']) : 'Não identificada';
        $inaarees = ($data['tem_inaarees'] ?? 'Não') === 'Sim' ? 'Sim' : 'Não';
        $pedag = ($data['tem_agregacao_pedag'] ?? 'Não') === 'Sim' ? 'Sim' : 'Não';
        $categoria = !empty($data['categoria_carreira']) ? trim($data['categoria_carreira']) : 'Assistente';

        if (empty($nome)) {
            return ['success' => false, 'message' => 'O nome do docente é obrigatório.'];
        }

        // 1. Validar e-mail duplicado
        if ($email) {
            $stmtCheck = $this->db->prepare("SELECT id FROM docentes WHERE LOWER(email) = ? LIMIT 1");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetchColumn()) {
                return ['success' => false, 'message' => "Já existe um docente registado com o e-mail '{$email}'."];
            }
        }

        // 2. Avisar sobre nome semelhante caso não tenha sido confirmado
        if (empty($data['confirm_dup'])) {
            $stmtName = $this->db->prepare("SELECT nome FROM docentes WHERE LOWER(nome) LIKE ? LIMIT 1");
            $stmtName->execute(['%' . strtolower($nome) . '%']);
            $existingName = $stmtName->fetchColumn();
            if ($existingName) {
                return [
                    'success' => false,
                    'dup_warning' => true,
                    'message' => "Aviso: Já existe um docente registado com nome semelhante ('{$existingName}'). Deseja cadastrar mesmo assim?"
                ];
            }
        }

        // 3. Inserção do docente no catálogo
        $stmt = $this->db->prepare("
            INSERT INTO docentes (nome, email, grau_academico, especialidade, tem_inaarees, tem_agregacao_pedag, categoria_carreira, activo)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $res = $stmt->execute([$nome, $email, $grau, $especialidade, $inaarees, $pedag, $categoria]);
        $newId = (int)$this->db->lastInsertId();

        if ($res) {
            return ['success' => true, 'docente_id' => $newId, 'message' => 'Docente cadastrado com sucesso no catálogo institucional!'];
        }
        return ['success' => false, 'message' => 'Falha ao gravar docente na base de dados.'];
    }

    public function updatePerfilDocente(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE docentes 
            SET grau_academico = :grau,
                especialidade = :esp,
                tem_inaarees = :ina,
                tem_agregacao_pedag = :cap,
                categoria_carreira = :car,
                anos_experiencia_es = :exp,
                producao_cientifica_3a = :prod
            WHERE id = :id
        ");
        $res = $stmt->execute([
            ':grau' => $data['grau_academico'] ?? 'Licenciado',
            ':esp'  => $data['especialidade'] ?? 'Não identificada',
            ':ina'  => (!empty($data['tem_inaarees']) && $data['tem_inaarees'] !== 'Não') ? 'Sim' : 'Não',
            ':cap'  => (!empty($data['tem_agregacao_pedag']) && $data['tem_agregacao_pedag'] !== 'Não') ? 'Sim' : 'Não',
            ':car'  => $data['categoria_carreira'] ?? 'Assistente',
            ':exp'  => (int)($data['anos_experiencia_es'] ?? 0),
            ':prod' => (int)($data['producao_cientifica_3a'] ?? 0),
            ':id'   => $id
        ]);

        if ($res) {
            $this->recalcularConformidadeDocenteEmTodosPlanos($id);
        }

        return $res;
    }

    /**
     * Propaga o recálculo automático de conformidade em todas as linhas de planos onde o docente está atribuído
     */
    public function recalcularConformidadeDocenteEmTodosPlanos(int $docenteId): int {
        // Buscar o docente atualizado
        $doc = $this->getById($docenteId);
        if (!$doc) return 0;

        // Buscar todas as linhas de cobertura associadas a este docente
        $stmtLinhas = $this->db->prepare("
            SELECT lc.id AS linha_id, lc.conformidade, d.nome AS disc_nome
            FROM linhas_cobertura lc
            JOIN disciplinas d ON lc.disciplina_id = d.id
            WHERE lc.docente_id = ?
        ");
        $stmtLinhas->execute([$docenteId]);
        $linhas = $stmtLinhas->fetchAll();

        $stmtUpdate = $this->db->prepare("UPDATE linhas_cobertura SET conformidade = ? WHERE id = ?");
        $atualizados = 0;

        foreach ($linhas as $l) {
            $novaConf = 'Por verificar';

            if ($doc['estado_capacidade'] === 'Sobregregado') {
                $novaConf = 'Parcial';
            } elseif ($doc['grau_academico'] === 'Doutor') {
                $novaConf = 'Sim';
            } elseif ($doc['grau_academico'] === 'Mestre' && $doc['tem_inaarees'] === 'Sim') {
                $novaConf = 'Sim';
            } elseif ($doc['grau_academico'] === 'Mestre') {
                $novaConf = 'Parcial';
            } else {
                $novaConf = 'Não';
            }

            if ($novaConf !== $l['conformidade']) {
                $stmtUpdate->execute([$novaConf, $l['linha_id']]);
                $atualizados++;
            }
        }

        return $atualizados;
    }


    /**
     * Carrega o CV completo de um docente: dados base (tabela docentes)
     * + dados estruturados (tabela cvs_estruturados) num único array normalizado.
     */
    public function getCVCompleto(int $docenteId): array {
        // Dados base do docente
        $stmtDoc = $this->db->prepare("SELECT * FROM docentes WHERE id = ?");
        $stmtDoc->execute([$docenteId]);
        $docente = $stmtDoc->fetch(PDO::FETCH_ASSOC);
        if (!$docente) return [];

        // Dados estruturados (pode não existir ainda)
        $stmtCV = $this->db->prepare("SELECT * FROM cvs_estruturados WHERE docente_id = ?");
        $stmtCV->execute([$docenteId]);
        $cv = $stmtCV->fetch(PDO::FETCH_ASSOC) ?: [];

        // Desserializar JSONs com fallback para array vazio
        $formacoes    = json_decode($cv['formacoes_json']    ?? '[]', true) ?: [];
        $experiencias = json_decode($cv['experiencias_json'] ?? '[]', true) ?: [];
        $publicacoes  = json_decode($cv['publicacoes_json']  ?? '[]', true) ?: [];

        return [
            // Identificação
            'id'                    => $docente['id'],
            'nome'                  => $docente['nome'],
            'email'                 => $docente['email'] ?? '',
            'telefone'              => $cv['telefone'] ?? '',
            'bilhete_identidade'    => $cv['bilhete_identidade'] ?? '',
            'foto_path'             => $cv['foto_path'] ?? '',
            // Formação académica (↳ plano)
            'grau_academico'        => $docente['grau_academico'] ?? 'Licenciado',
            'especialidade'         => $docente['especialidade'] ?? '',
            'tem_inaarees'          => $docente['tem_inaarees'] ?? 'Não',
            'tem_agregacao_pedag'   => $docente['tem_agregacao_pedag'] ?? 'Não',
            'categoria_carreira'    => $docente['categoria_carreira'] ?? 'Assistente',
            'formacoes'             => $formacoes,
            // Situação profissional (↳ plano)
            'instituicao_atual'     => $cv['instituicao_atual'] ?? '',
            'regime_contratual'     => $cv['regime_contratual'] ?? '',
            'anos_experiencia_es'   => $docente['anos_experiencia_es'] ?? 0,
            // Investigação & produção (↳ plano)
            'producao_cientifica_3a' => $docente['producao_cientifica_3a'] ?? 0,
            'linhas_pesquisa'       => $cv['linhas_pesquisa'] ?? '',
            'publicacoes'           => $publicacoes,
            'cursos_ministrados'    => $cv['cursos_ministrados'] ?? '',
            'outras_atividades'     => $cv['outras_atividades'] ?? '',
            'experiencias'          => $experiencias,
        ];
    }

    /**
     * Grava o CV completo em transação:
     *   1. UPDATE tabela docentes (campos que alimentam o plano via JOIN)
     *   2. UPSERT tabela cvs_estruturados (campos extras + JSONs)
     *   3. Recálculo automático de conformidade em todos os planos atribuídos
     *
     * @return array ['success' => bool, 'linhas_atualizadas' => int, 'error' => string]
     */
    public function saveCVCompleto(int $docenteId, array $data): array {
        try {
            $this->db->beginTransaction();

            // 1 — Atualizar tabela docentes (campos que alimentam diretamente o plano via JOIN)
            $stmtDoc = $this->db->prepare("
                UPDATE docentes SET
                    nome                 = :nome,
                    email                = :email,
                    grau_academico       = :grau,
                    especialidade        = :esp,
                    tem_inaarees         = :ina,
                    tem_agregacao_pedag  = :cap,
                    categoria_carreira   = :cat,
                    anos_experiencia_es  = :exp,
                    producao_cientifica_3a = :prod
                WHERE id = :id
            ");
            $stmtDoc->execute([
                ':nome'  => trim($data['nome'] ?? ''),
                ':email' => trim($data['email'] ?? ''),
                ':grau'  => $data['grau_academico'] ?? 'Licenciado',
                ':esp'   => trim($data['especialidade'] ?? 'Não identificada'),
                ':ina'   => (isset($data['tem_inaarees']) && $data['tem_inaarees'] === 'Sim') ? 'Sim' : 'Não',
                ':cap'   => (isset($data['tem_agregacao_pedag']) && $data['tem_agregacao_pedag'] === 'Sim') ? 'Sim' : 'Não',
                ':cat'   => $data['categoria_carreira'] ?? 'Assistente',
                ':exp'   => (int)($data['anos_experiencia_es'] ?? 0),
                ':prod'  => (int)($data['producao_cientifica_3a'] ?? 0),
                ':id'    => $docenteId,
            ]);

            // 2 — UPSERT tabela cvs_estruturados (campos extras não presentes em docentes)
            $formacoesJson    = json_encode($data['formacoes']    ?? [], JSON_UNESCAPED_UNICODE);
            $experienciasJson = json_encode($data['experiencias'] ?? [], JSON_UNESCAPED_UNICODE);
            $publicacoesJson  = json_encode($data['publicacoes']  ?? [], JSON_UNESCAPED_UNICODE);

            $stmtCV = $this->db->prepare("
                INSERT INTO cvs_estruturados (
                    docente_id, grau_academico, especialidade,
                    tem_inaarees, tem_agregacao_pedag, categoria_carreira,
                    anos_experiencia_es, producao_cientifica_3a,
                    telefone, bilhete_identidade, foto_path,
                    instituicao_atual, regime_contratual,
                    linhas_pesquisa, publicacoes_json, cursos_ministrados, outras_atividades,
                    formacoes_json, experiencias_json
                ) VALUES (
                    :docente_id, :grau, :esp,
                    :ina, :cap, :cat,
                    :exp, :prod,
                    :tel, :bi, :foto,
                    :inst, :regime,
                    :linhas, :pub_json, :cursos, :outras,
                    :form_json, :exp_json
                )
                ON DUPLICATE KEY UPDATE
                    grau_academico        = VALUES(grau_academico),
                    especialidade         = VALUES(especialidade),
                    tem_inaarees          = VALUES(tem_inaarees),
                    tem_agregacao_pedag   = VALUES(tem_agregacao_pedag),
                    categoria_carreira    = VALUES(categoria_carreira),
                    anos_experiencia_es   = VALUES(anos_experiencia_es),
                    producao_cientifica_3a = VALUES(producao_cientifica_3a),
                    telefone              = VALUES(telefone),
                    bilhete_identidade    = VALUES(bilhete_identidade),
                    foto_path             = VALUES(foto_path),
                    instituicao_atual     = VALUES(instituicao_atual),
                    regime_contratual     = VALUES(regime_contratual),
                    linhas_pesquisa       = VALUES(linhas_pesquisa),
                    publicacoes_json      = VALUES(publicacoes_json),
                    cursos_ministrados    = VALUES(cursos_ministrados),
                    outras_atividades     = VALUES(outras_atividades),
                    formacoes_json        = VALUES(formacoes_json),
                    experiencias_json     = VALUES(experiencias_json)
            ");
            $stmtCV->execute([
                ':docente_id' => $docenteId,
                ':grau'       => $data['grau_academico'] ?? 'Licenciado',
                ':esp'        => trim($data['especialidade'] ?? 'Não identificada'),
                ':ina'        => (isset($data['tem_inaarees']) && $data['tem_inaarees'] === 'Sim') ? 1 : 0,
                ':cap'        => (isset($data['tem_agregacao_pedag']) && $data['tem_agregacao_pedag'] === 'Sim') ? 1 : 0,
                ':cat'        => $data['categoria_carreira'] ?? 'Assistente',
                ':exp'        => (int)($data['anos_experiencia_es'] ?? 0),
                ':prod'       => (int)($data['producao_cientifica_3a'] ?? 0),
                ':tel'        => trim($data['telefone'] ?? ''),
                ':bi'         => trim($data['bilhete_identidade'] ?? ''),
                ':foto'       => trim($data['foto_path'] ?? ''),
                ':inst'       => trim($data['instituicao_atual'] ?? ''),
                ':regime'     => $data['regime_contratual'] ?? '',
                ':linhas'     => trim($data['linhas_pesquisa'] ?? ''),
                ':pub_json'   => $publicacoesJson,
                ':cursos'     => trim($data['cursos_ministrados'] ?? ''),
                ':outras'     => trim($data['outras_atividades'] ?? ''),
                ':form_json'  => $formacoesJson,
                ':exp_json'   => $experienciasJson,
            ]);

            // 3 — Propagar recálculo de conformidade a todos os planos
            $linhasAtualizadas = $this->recalcularConformidadeDocenteEmTodosPlanos($docenteId);

            $this->db->commit();
            return ['success' => true, 'linhas_atualizadas' => $linhasAtualizadas];

        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'linhas_atualizadas' => 0, 'error' => $e->getMessage()];
        }
    }

    public function getCVStructured(int $docenteId): ?array {

        $stmt = $this->db->prepare("SELECT * FROM cvs_estruturados WHERE docente_id = ?");
        $stmt->execute([$docenteId]);
        $cv = $stmt->fetch();
        if ($cv) {
            $cv['formacoes_json'] = json_decode($cv['formacoes_json'] ?? '[]', true);
            $cv['experiencias_json'] = json_decode($cv['experiencias_json'] ?? '[]', true);
            return $cv;
        }
        return null;
    }

    public function getDocumentos(int $docenteId): array {
        $stmt = $this->db->prepare("SELECT * FROM documentos_docentes WHERE docente_id = ? ORDER BY created_at DESC");
        $stmt->execute([$docenteId]);
        return $stmt->fetchAll();
    }

    public function saveDocumento(int $docenteId, string $tipo, string $caminhoFicheiro, ?int $validadoPor = null): bool {
        $tipoMap = [
            'cv'    => 'cv',
            'cert'  => 'certificados',
            'dip'   => 'diplomas',
            'bi'    => 'bi',
            'ina'   => 'inaarees',
            'ped'   => 'agregacao_pedag',
            'certificados' => 'certificados',
            'diplomas'     => 'diplomas',
            'inaarees'     => 'inaarees',
            'agregacao_pedag' => 'agregacao_pedag'
        ];

        $enumTipo = $tipoMap[$tipo] ?? 'cv';

        // Se o documento for INAAREES ou Agregação Pedagógica, atualiza automaticamente o status no perfil do docente
        if ($enumTipo === 'inaarees') {
            $this->db->prepare("UPDATE docentes SET tem_inaarees = 'Sim' WHERE id = ?")->execute([$docenteId]);
        } elseif ($enumTipo === 'agregacao_pedag') {
            $this->db->prepare("UPDATE docentes SET tem_agregacao_pedag = 'Sim' WHERE id = ?")->execute([$docenteId]);
        }

        $stmt = $this->db->prepare("INSERT INTO documentos_docentes (docente_id, tipo, caminho_ficheiro, estado, validado_por) VALUES (?, ?, ?, 'Válido', ?)");
        $res = $stmt->execute([$docenteId, $enumTipo, $caminhoFicheiro, $validadoPor]);

        if ($res) {
            $this->recalcularConformidadeDocenteEmTodosPlanos($docenteId);
        }

        return $res;
    }

    public function deleteDocumento(int $docId): bool {
        $stmtSelect = $this->db->prepare("SELECT * FROM documentos_docentes WHERE id = ?");
        $stmtSelect->execute([$docId]);
        $doc = $stmtSelect->fetch();
        if (!$doc) return false;

        $docenteId = (int)$doc['docente_id'];
        $tipoDoc = $doc['tipo'] ?? '';

        // Tentar remover o ficheiro do disco se existir
        $rawPath = str_replace('\\', '/', $doc['caminho_ficheiro']);
        $relPath = ltrim($rawPath, '/');
        $cleanRelPath = preg_replace('#^public/#i', '', $relPath);
        $filename = basename($rawPath);

        $possiblePaths = [
            $rawPath,
            $doc['caminho_ficheiro'],
            $relPath,
            __DIR__ . '/../../public/' . $cleanRelPath,
            __DIR__ . '/../../' . $cleanRelPath,
            __DIR__ . '/../../public/' . $relPath,
            __DIR__ . '/../../' . $relPath,
            __DIR__ . '/../../public/uploads/docentes/' . $filename,
            __DIR__ . '/../../uploads/docentes/' . $filename,
        ];
        foreach ($possiblePaths as $p) {
            if (!empty($p) && file_exists($p) && !is_dir($p)) {
                @unlink($p);
                break;
            }
        }

        $stmt = $this->db->prepare("DELETE FROM documentos_docentes WHERE id = ?");
        $res = $stmt->execute([$docId]);
        if ($res) {
            // Se o documento era INAAREES ou Agregação Pedagógica, verificar se ainda resta algum válido
            if ($tipoDoc === 'inaarees' || $tipoDoc === 'ina') {
                $stmtCheckIna = $this->db->prepare("SELECT COUNT(*) FROM documentos_docentes WHERE docente_id = ? AND tipo IN ('inaarees', 'ina') AND estado = 'Válido'");
                $stmtCheckIna->execute([$docenteId]);
                if ((int)$stmtCheckIna->fetchColumn() === 0) {
                    $this->db->prepare("UPDATE docentes SET tem_inaarees = 'Não' WHERE id = ?")->execute([$docenteId]);
                }
            } elseif ($tipoDoc === 'agregacao_pedag' || $tipoDoc === 'ped') {
                $stmtCheckPed = $this->db->prepare("SELECT COUNT(*) FROM documentos_docentes WHERE docente_id = ? AND tipo IN ('agregacao_pedag', 'ped') AND estado = 'Válido'");
                $stmtCheckPed->execute([$docenteId]);
                if ((int)$stmtCheckPed->fetchColumn() === 0) {
                    $this->db->prepare("UPDATE docentes SET tem_agregacao_pedag = 'Não' WHERE id = ?")->execute([$docenteId]);
                }
            }

            $this->recalcularConformidadeDocenteEmTodosPlanos($docenteId);
        }
        return $res;
    }

    public function removerFotoCV(int $docenteId): bool {
        $stmt = $this->db->prepare("SELECT foto_path FROM cvs_estruturados WHERE docente_id = ?");
        $stmt->execute([$docenteId]);
        $fotoPath = $stmt->fetchColumn();
        if ($fotoPath) {
            $rawPath = str_replace('\\', '/', $fotoPath);
            $relPath = ltrim($rawPath, '/');
            $cleanRelPath = preg_replace('#^public/#i', '', $relPath);
            $filename = basename($rawPath);
            $possiblePaths = [
                __DIR__ . '/../../public/' . $cleanRelPath,
                __DIR__ . '/../../' . $cleanRelPath,
                __DIR__ . '/../../public/uploads/docentes/' . $filename,
                __DIR__ . '/../../uploads/docentes/' . $filename,
                $rawPath
            ];
            foreach ($possiblePaths as $p) {
                if (!empty($p) && file_exists($p) && !is_dir($p)) {
                    @unlink($p);
                    break;
                }
            }
        }
        $stmtUp = $this->db->prepare("UPDATE cvs_estruturados SET foto_path = '' WHERE docente_id = ?");
        return $stmtUp->execute([$docenteId]);
    }

    public function updateDocumentoEstado(int $docId, string $estado, ?int $validadoPor = null): bool {
        $stmt = $this->db->prepare("UPDATE documentos_docentes SET estado = ?, validado_por = ? WHERE id = ?");
        return $stmt->execute([$estado, $validadoPor, $docId]);
    }

    /**
     * Estatísticas de Qualificações Académicas para o Dashboard Institucional (BI)
     * grau_academico é ENUM('Licenciado','Mestre','Doutor') na BD real
     */
    public function getDashboardQualificacoes(): array {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN grau_academico = 'Doutor' THEN 1 ELSE 0 END) as doutores,
                SUM(CASE WHEN grau_academico = 'Mestre' THEN 1 ELSE 0 END) as mestres,
                0 as lic_mest_em_curso,
                SUM(CASE WHEN grau_academico = 'Licenciado' THEN 1 ELSE 0 END) as licenciados
            FROM docentes
            WHERE activo = 1
        ");
        return $stmt->fetch() ?: [
            'total' => 0, 'doutores' => 0, 'mestres' => 0, 'lic_mest_em_curso' => 0, 'licenciados' => 0
        ];
    }

    /**
     * Estatísticas dos 3 Pilares de Regularização Docente (INAAREES, Capacitação, Carreira)
     * Valores reais BD: tem_inaarees e tem_agregacao_pedag = 'Sim'|'Não'
     * categoria_carreira = 'Sim'|'Não'|'Não registado'
     */
    public function getDashboardPilares(): array {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN tem_inaarees = 'Sim' THEN 1 ELSE 0 END) as inaarees_sim,
                SUM(CASE WHEN tem_inaarees = 'Não' OR tem_inaarees IS NULL THEN 1 ELSE 0 END) as inaarees_nao,
                0 as inaarees_ni,
                
                SUM(CASE WHEN tem_agregacao_pedag = 'Sim' THEN 1 ELSE 0 END) as `capacitação_sim`,
                SUM(CASE WHEN tem_agregacao_pedag = 'Não' OR tem_agregacao_pedag IS NULL THEN 1 ELSE 0 END) as `capacitação_nao`,
                0 as `capacitação_ni`,

                SUM(CASE WHEN categoria_carreira = 'Sim' THEN 1 ELSE 0 END) as carreira_sim,
                SUM(CASE WHEN categoria_carreira = 'Não' THEN 1 ELSE 0 END) as carreira_nao,
                SUM(CASE WHEN categoria_carreira = 'Não registado' OR categoria_carreira IS NULL THEN 1 ELSE 0 END) as carreira_ni
            FROM docentes
            WHERE activo = 1
        ");
        return $stmt->fetch() ?: [];
    }

    /**
     * Estatísticas de Partilha de Docentes entre Cursos (Risco de Sobrecarga)
     * num_cursos vem da view vw_docentes_capacidade_carga; 0 = sem atribuições ainda
     */
    public function getDashboardSobrecargaPartilha(): array {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_docentes,
                SUM(CASE WHEN nc = 0 THEN 1 ELSE 0 END) as c0,
                SUM(CASE WHEN nc = 1 THEN 1 ELSE 0 END) as c1,
                SUM(CASE WHEN nc = 2 THEN 1 ELSE 0 END) as c2,
                SUM(CASE WHEN nc = 3 THEN 1 ELSE 0 END) as c3,
                SUM(CASE WHEN nc = 4 THEN 1 ELSE 0 END) as c4,
                SUM(CASE WHEN nc >= 5 THEN 1 ELSE 0 END) as c5_plus,
                SUM(CASE WHEN nc >= 3 THEN 1 ELSE 0 END) as em_sobrecarga
            FROM (
                SELECT d.id, COALESCE(cap.num_cursos, 0) as nc
                FROM docentes d
                LEFT JOIN vw_docentes_capacidade_carga cap ON d.id = cap.docente_id
                WHERE d.activo = 1
            ) sub
        ");
        return $stmt->fetch() ?: [
            'total_docentes' => 0, 'c0' => 0, 'c1' => 0, 'c2' => 0, 'c3' => 0, 'c4' => 0, 'c5_plus' => 0, 'em_sobrecarga' => 0
        ];
    }
}

