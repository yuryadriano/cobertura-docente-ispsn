<?php
/**
 * Modelo de Dados: Curso e Disciplinas
 * sftcoordenacao — Módulo de Cobertura Docente ISPSN 2026/27
 */

require_once __DIR__ . '/../../config/database.php';

class CursoModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM cursos WHERE activo = 1 ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM cursos WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getDisciplinasByCurso(int $cursoId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM disciplinas 
            WHERE curso_id = ? AND activo = 1 
            ORDER BY ano_curricular ASC, semestre ASC, nome ASC
        ");
        $stmt->execute([$cursoId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca turmas de um determinado curso com metadados da disciplina e docente atribuído
     */
    public function getTurmasDetalhadas(int $cursoId, ?int $anoCurricular = null): array {
        $sql = "
            SELECT 
                t.*,
                d.nome AS disciplina_nome,
                d.ano_curricular,
                d.semestre,
                d.carga_horaria_semanal,
                c.nome AS curso_nome,
                doc.nome AS docente_nome
            FROM turmas t
            JOIN disciplinas d ON t.disciplina_id = d.id
            JOIN cursos c ON d.curso_id = c.id
            LEFT JOIN docentes doc ON t.docente_id = doc.id
            WHERE c.id = :curso_id
        ";
        $params = [':curso_id' => $cursoId];

        if ($anoCurricular) {
            $sql .= " AND d.ano_curricular = :ano";
            $params[':ano'] = $anoCurricular;
        }

        $sql .= " ORDER BY d.ano_curricular ASC, d.semestre ASC, d.nome ASC, t.designacao ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Atualiza os indicadores operacionais de uma turma
     */
    public function updateTurmaIndicadores(string $turmaId, array $data): bool {
        $fields = [];
        $params = [':id' => $turmaId];

        if (array_key_exists('sumarios_registados', $data)) {
            $fields[] = "sumarios_registados = :sum_reg";
            $params[':sum_reg'] = (int)$data['sumarios_registados'];
        }

        if (array_key_exists('sumarios_previstos', $data)) {
            $fields[] = "sumarios_previstos = :sum_prev";
            $params[':sum_prev'] = max(1, (int)$data['sumarios_previstos']);
        }

        if (array_key_exists('programa_carregado', $data)) {
            $fields[] = "programa_carregado = :prog";
            $params[':prog'] = !empty($data['programa_carregado']) ? 1 : 0;
        }

        if (array_key_exists('dosificacao_carregada', $data)) {
            $fields[] = "dosificacao_carregada = :dosi";
            $params[':dosi'] = !empty($data['dosificacao_carregada']) ? 1 : 0;
        }

        if (array_key_exists('notas_no_prazo', $data)) {
            $fields[] = "notas_no_prazo = :notas";
            $params[':notas'] = in_array($data['notas_no_prazo'], ['Sim', 'Não']) ? $data['notas_no_prazo'] : 'Sim';
        }

        if (array_key_exists('inquerito_media', $data)) {
            $fields[] = "inquerito_media = :inq";
            $params[':inq'] = min(5.00, max(1.00, (float)$data['inquerito_media']));
        }

        if (empty($fields)) {
            return true;
        }

        $sql = "UPDATE turmas SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Insere ou edita uma disciplina da matriz curricular
     */
    public function saveDisciplina(array $data): bool {
        $id = !empty($data['disciplina_id']) ? (int)$data['disciplina_id'] : null;
        $cursoId = (int)($data['curso_id'] ?? 0);
        $nome = trim($data['nome'] ?? '');
        $ano = (int)($data['ano_curricular'] ?? 1);
        $semestre = in_array($data['semestre'] ?? '', ['I', 'II']) ? $data['semestre'] : 'I';
        $carga = (int)($data['carga_horaria_semanal'] ?? 4);
        $creditos = (int)($data['creditos'] ?? 6);

        if (!$cursoId || empty($nome)) {
            return false;
        }

        if ($id) {
            $stmt = $this->db->prepare("
                UPDATE disciplinas SET
                    nome = :nome,
                    ano_curricular = :ano,
                    semestre = :sem,
                    carga_horaria_semanal = :carga,
                    creditos = :cred
                WHERE id = :id AND curso_id = :curso_id
            ");
            $res = $stmt->execute([
                ':nome'     => $nome,
                ':ano'      => $ano,
                ':sem'      => $semestre,
                ':carga'    => $carga,
                ':cred'     => $creditos,
                ':id'       => $id,
                ':curso_id' => $cursoId
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO disciplinas (curso_id, nome, ano_curricular, semestre, carga_horaria_semanal, creditos, activo)
                VALUES (:curso_id, :nome, :ano, :sem, :carga, :cred, 1)
            ");
            $res = $stmt->execute([
                ':curso_id' => $cursoId,
                ':nome'     => $nome,
                ':ano'      => $ano,
                ':sem'      => $semestre,
                ':carga'    => $carga,
                ':cred'     => $creditos
            ]);
        }

        if ($res) {
            try {
                require_once __DIR__ . '/PlanoModel.php';
                $planoModel = new PlanoModel();
                $stmtPlanos = $this->db->prepare("SELECT id FROM planos_cobertura WHERE curso_id = ?");
                $stmtPlanos->execute([$cursoId]);
                $planos = $stmtPlanos->fetchAll();
                foreach ($planos as $p) {
                    $planoModel->ensureLinhasCompletas((int)$p['id']);
                }
            } catch (\Throwable $e) {
                // Silenciar exceção auxiliar
            }
        }

        return (bool)$res;
    }
}
