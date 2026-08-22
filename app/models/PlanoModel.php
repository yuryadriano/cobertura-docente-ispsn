<?php
/**
 * Modelo de Dados: Plano de Cobertura e Linhas
 * sftcoordenacao — Módulo de Cobertura Docente ISPSN 2026/27
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/Auth.php';

class PlanoModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getByCursoEAno(int $cursoId, string $anoLectivo = '2026/27'): ?array {
        $stmt = $this->db->prepare("SELECT * FROM planos_cobertura WHERE curso_id = ? AND ano_lectivo = ?");
        $stmt->execute([$cursoId, $anoLectivo]);
        $plano = $stmt->fetch();

        if (!$plano) {
            try {
                $stmtIns = $this->db->prepare("INSERT INTO planos_cobertura (curso_id, ano_lectivo, estado, observacoes) VALUES (?, ?, 'Rascunho', 'Plano gerado automaticamente')");
                $stmtIns->execute([$cursoId, $anoLectivo]);
                $planoId = (int)$this->db->lastInsertId();

                $stmt = $this->db->prepare("SELECT * FROM planos_cobertura WHERE id = ?");
                $stmt->execute([$planoId]);
                $plano = $stmt->fetch();

                // Gerar linhas de cobertura para as turmas existentes do curso
                $stmtLinhas = $this->db->prepare("
                    INSERT IGNORE INTO `linhas_cobertura` (`plano_id`, `disciplina_id`, `turma_id`, `conformidade`, `regime`, `parecer`)
                    SELECT ?, d.id, t.id, 'Por verificar', 'Tempo Parcial', 'Manter'
                    FROM turmas t
                    JOIN disciplinas d ON t.disciplina_id = d.id
                    WHERE d.curso_id = ? AND d.activo = 1
                ");
                $this->ensureLinhasCompletas($planoId);
            } catch (\Throwable $e) {
                // Silenciar em caso de exceção e retornar o que existir
            }
        } elseif (!empty($plano['id'])) {
            $this->ensureLinhasCompletas((int)$plano['id']);
        }

        return $plano ?: null;
    }

    /**
     * Garante de forma não destrutiva que todas as disciplinas ativas do curso
     * possuem turmas e linhas no plano de cobertura (auto-healing).
     */
    public function ensureLinhasCompletas(int $planoId): void {
        try {
            // Se o plano já possui linhas estruturadas, não há necessidade de reprocessar
            $countExisting = (int)$this->db->query("SELECT count(*) FROM linhas_cobertura WHERE plano_id = {$planoId}")->fetchColumn();
            if ($countExisting > 0) {
                return;
            }

            $stmtPlano = $this->db->prepare("SELECT id, curso_id, ano_lectivo FROM planos_cobertura WHERE id = ?");
            $stmtPlano->execute([$planoId]);
            $plano = $stmtPlano->fetch();
            if (!$plano) return;

            $cursoId = (int)$plano['curso_id'];

            // Obter dados do curso
            $stmtCurso = $this->db->prepare("SELECT id, codigo, nome FROM cursos WHERE id = ?");
            $stmtCurso->execute([$cursoId]);
            $curso = $stmtCurso->fetch();
            if (!$curso) return;

            $codCursoRaw = strtoupper(trim($curso['codigo'] ?? ''));
            $nomeCurso = $curso['nome'] ?? '';

            // 1. Obter todas as disciplinas ativas deste curso
            $stmtDiscs = $this->db->prepare("SELECT * FROM disciplinas WHERE curso_id = ? AND activo = 1 ORDER BY ano_curricular ASC, semestre ASC, nome ASC");
            $stmtDiscs->execute([$cursoId]);
            $discs = $stmtDiscs->fetchAll();
            if (empty($discs)) return;

            $discsByAno = [];
            foreach ($discs as $d) {
                $discsByAno[(int)$d['ano_curricular']][] = $d;
            }

            // Matriz Institucional Oficial do ISPSN 2026/27 (Distribuição Canónica)
            $cronogramaTurmas = [
                'CPRI' => [
                    1 => [
                        ['letra' => 'Turma A', 'cod' => 'CPRI1MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'CPRI1TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma C', 'cod' => 'CPRI1NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma D', 'cod' => 'CPRI-RB-MA', 'turno' => 'Regime B'],
                        ['letra' => 'Turma E', 'cod' => 'CPRI-RB-TA', 'turno' => 'Regime B'],
                    ],
                    2 => [
                        ['letra' => 'Turma A', 'cod' => 'CPRI2TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma B', 'cod' => 'CPRI-RB2', 'turno' => 'Regime B'],
                    ],
                    3 => [
                        ['letra' => 'Turma A', 'cod' => 'CPRI3NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'CPRI-RB3', 'turno' => 'Regime B'],
                    ],
                    4 => [
                        ['letra' => 'Turma A', 'cod' => 'CPRI4NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'CPRI-RB4', 'turno' => 'Regime B'],
                    ]
                ],
                'SOCI' => [
                    1 => [
                        ['letra' => 'Turma A', 'cod' => 'SOC1MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'SOC1TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma C', 'cod' => 'SOC1NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma D', 'cod' => 'SOC-RB-MA', 'turno' => 'Regime B'],
                        ['letra' => 'Turma E', 'cod' => 'SOC-RB-TA', 'turno' => 'Regime B'],
                    ],
                    2 => [
                        ['letra' => 'Turma A', 'cod' => 'SOC2TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma B', 'cod' => 'SOC-RB2', 'turno' => 'Regime B'],
                    ],
                    3 => [
                        ['letra' => 'Turma A', 'cod' => 'SOC3NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'SOC-RB3', 'turno' => 'Regime B'],
                    ],
                    4 => [
                        ['letra' => 'Turma A', 'cod' => 'SOC4NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'SOC-RB4', 'turno' => 'Regime B'],
                    ]
                ],
                'CONT' => [
                    1 => [
                        ['letra' => 'Turma A', 'cod' => 'COF1MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'COF1MB', 'turno' => 'Manhã'],
                        ['letra' => 'Turma C', 'cod' => 'COF1TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma D', 'cod' => 'COF1NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma E', 'cod' => 'COF-RB1', 'turno' => 'Regime B'],
                    ],
                    2 => [
                        ['letra' => 'Turma A', 'cod' => 'COF2MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'COF2TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma C', 'cod' => 'COF2NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma D', 'cod' => 'COF-RB2', 'turno' => 'Regime B'],
                    ],
                    3 => [
                        ['letra' => 'Turma A', 'cod' => 'COF3NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'COF-RB3', 'turno' => 'Regime B'],
                    ],
                    4 => [
                        ['letra' => 'Turma A', 'cod' => 'COF4NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'COF-RB4', 'turno' => 'Regime B'],
                    ]
                ],
                'ECON' => [
                    1 => [
                        ['letra' => 'Turma A', 'cod' => 'ECO1MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'ECO1MB', 'turno' => 'Manhã'],
                        ['letra' => 'Turma C', 'cod' => 'ECO1TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma D', 'cod' => 'ECO1NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma E', 'cod' => 'ECO-RB1', 'turno' => 'Regime B'],
                    ],
                    2 => [
                        ['letra' => 'Turma A', 'cod' => 'ECO2MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'ECO2TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma C', 'cod' => 'ECO2NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma D', 'cod' => 'ECO-RB2', 'turno' => 'Regime B'],
                    ],
                    3 => [
                        ['letra' => 'Turma A', 'cod' => 'ECO3NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'ECO-RB3', 'turno' => 'Regime B'],
                    ],
                    4 => [
                        ['letra' => 'Turma A', 'cod' => 'ECO4NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'ECO-RB4', 'turno' => 'Regime B'],
                    ]
                ],
                'GRH' => [
                    1 => [
                        ['letra' => 'Turma A', 'cod' => 'GRH1MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'GRH1TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma C', 'cod' => 'GRH1TB', 'turno' => 'Tarde'],
                        ['letra' => 'Turma D', 'cod' => 'GRH1NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma E', 'cod' => 'GRH1NTB', 'turno' => 'Noite'],
                        ['letra' => 'Turma F', 'cod' => 'GRH-RB1', 'turno' => 'Regime B'],
                    ],
                    2 => [
                        ['letra' => 'Turma A', 'cod' => 'GRH2MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'GRH2TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma C', 'cod' => 'GRH2NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma D', 'cod' => 'GRH-RB2', 'turno' => 'Regime B'],
                    ],
                    3 => [
                        ['letra' => 'Turma A', 'cod' => 'GRH3MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'GRH3NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma C', 'cod' => 'GRH-RB3', 'turno' => 'Regime B'],
                    ],
                    4 => [
                        ['letra' => 'Turma A', 'cod' => 'GRH4NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'GRH-RB4', 'turno' => 'Regime B'],
                    ]
                ],
                'HIST' => [
                    1 => [
                        ['letra' => 'Turma A', 'cod' => 'HIST1MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'HIST1TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma C', 'cod' => 'HIST1NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma D', 'cod' => 'HIST-RB1', 'turno' => 'Regime B'],
                    ],
                    2 => [
                        ['letra' => 'Turma A', 'cod' => 'HIST2MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'HIST2TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma C', 'cod' => 'HIST-RB2', 'turno' => 'Regime B'],
                    ],
                    3 => [
                        ['letra' => 'Turma A', 'cod' => 'HIST3MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'HIST3NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma C', 'cod' => 'HIST-RB3', 'turno' => 'Regime B'],
                    ],
                    4 => [
                        ['letra' => 'Turma A', 'cod' => 'HIST4NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'HIST-RB4', 'turno' => 'Regime B'],
                    ]
                ],
                'PSIC' => [
                    1 => [
                        ['letra' => 'Turma A', 'cod' => 'PSIC1MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'PSIC1TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma C', 'cod' => 'PSIC1NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma D', 'cod' => 'PSIC-RB1', 'turno' => 'Regime B'],
                    ],
                    2 => [
                        ['letra' => 'Turma A', 'cod' => 'PSIC2TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma B', 'cod' => 'PSIC2NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma C', 'cod' => 'PSIC-RB2', 'turno' => 'Regime B'],
                    ],
                    3 => [
                        ['letra' => 'Turma A', 'cod' => 'PSIC3TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma B', 'cod' => 'PSIC3NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma C', 'cod' => 'PSIC-RB3', 'turno' => 'Regime B'],
                    ],
                    4 => [
                        ['letra' => 'Turma A', 'cod' => 'PSIC4NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'PSIC-RB4', 'turno' => 'Regime B'],
                    ]
                ],
                'DIRE' => [
                    1 => [
                        ['letra' => 'Turma A', 'cod' => 'DIR1MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'DIR1MB', 'turno' => 'Manhã'],
                        ['letra' => 'Turma C', 'cod' => 'DIR1TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma D', 'cod' => 'DIR1NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma E', 'cod' => 'DIR-RB1MA', 'turno' => 'Regime B'],
                        ['letra' => 'Turma F', 'cod' => 'DIR-RB1MB', 'turno' => 'Regime B'],
                        ['letra' => 'Turma G', 'cod' => 'DIR-RB1TA', 'turno' => 'Regime B'],
                        ['letra' => 'Turma H', 'cod' => 'DIR-RB1NTA', 'turno' => 'Regime B'],
                    ],
                    2 => [
                        ['letra' => 'Turma A', 'cod' => 'DIR2MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'DIR2TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma C', 'cod' => 'DIR2NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma D', 'cod' => 'DIR-RB2', 'turno' => 'Regime B'],
                    ],
                    3 => [
                        ['letra' => 'Turma A', 'cod' => 'DIR3MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'DIR3NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma C', 'cod' => 'DIR-RB3', 'turno' => 'Regime B'],
                    ],
                    4 => [
                        ['letra' => 'Turma A', 'cod' => 'DIR4NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma B', 'cod' => 'DIR-RB4', 'turno' => 'Regime B'],
                    ],
                    5 => [['letra' => 'Turma A', 'cod' => 'DIR5NTA', 'turno' => 'Noite']]
                ],
                'ANLI' => [
                    1 => [
                        ['letra' => 'Turma A', 'cod' => 'ACSP1MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'ACSP1MB', 'turno' => 'Manhã'],
                        ['letra' => 'Turma C', 'cod' => 'ACSP1MC', 'turno' => 'Manhã'],
                        ['letra' => 'Turma D', 'cod' => 'ACSP1MD', 'turno' => 'Manhã'],
                        ['letra' => 'Turma E', 'cod' => 'ACSP1TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma F', 'cod' => 'ACSP1TB', 'turno' => 'Tarde'],
                        ['letra' => 'Turma G', 'cod' => 'ACSP1TC', 'turno' => 'Tarde'],
                        ['letra' => 'Turma H', 'cod' => 'ACSP1NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma I', 'cod' => 'ACSP1NTB', 'turno' => 'Noite'],
                        ['letra' => 'Turma J', 'cod' => 'ACSP1NTC', 'turno' => 'Noite'],
                    ],
                    2 => [
                        ['letra' => 'Turma A', 'cod' => 'ACSP2MA', 'turno' => 'Manhã'],
                        ['letra' => 'Turma B', 'cod' => 'ACSP2MB', 'turno' => 'Manhã'],
                        ['letra' => 'Turma C', 'cod' => 'ACSP2MC', 'turno' => 'Manhã'],
                        ['letra' => 'Turma D', 'cod' => 'ACSP2TA', 'turno' => 'Tarde'],
                        ['letra' => 'Turma E', 'cod' => 'ACSP2TB', 'turno' => 'Tarde'],
                        ['letra' => 'Turma F', 'cod' => 'ACSP2TC', 'turno' => 'Tarde'],
                        ['letra' => 'Turma G', 'cod' => 'ACSP2NTA', 'turno' => 'Noite'],
                        ['letra' => 'Turma H', 'cod' => 'ACSP2NTB', 'turno' => 'Noite'],
                        ['letra' => 'Turma I', 'cod' => 'ACSP2NTC', 'turno' => 'Noite'],
                    ],
                    3 => [['letra' => 'Turma A', 'cod' => 'ACSP3NTA', 'turno' => 'Noite']]
                ],
                'ENFE' => [
                    1 => array_merge(
                        array_map(function($idx, $l) { return ['letra' => "Turma {$l}", 'turno' => 'Manhã', 'cod' => "ENF1M{$l}"]; }, range(0, 7), range('A', 'H')),
                        array_map(function($idx, $l, $letraOffset) { return ['letra' => "Turma {$letraOffset}", 'turno' => 'Tarde', 'cod' => "ENF1T{$l}"]; }, range(0, 6), range('A', 'G'), range('I', 'O')),
                        array_map(function($idx, $l, $letraOffset) { return ['letra' => "Turma {$letraOffset}", 'turno' => 'Noite', 'cod' => "ENF1NT{$l}"]; }, range(0, 2), range('A', 'C'), range('P', 'R'))
                    ),
                    2 => array_merge(
                        array_map(function($idx, $l) { return ['letra' => "Turma {$l}", 'turno' => 'Manhã', 'cod' => "ENF2M{$l}"]; }, range(0, 8), range('A', 'I')),
                        array_map(function($idx, $l, $letraOffset) { return ['letra' => "Turma {$letraOffset}", 'turno' => 'Tarde', 'cod' => "ENF2T{$l}"]; }, range(0, 7), range('A', 'H'), range('J', 'Q')),
                        array_map(function($idx, $l, $letraOffset) { return ['letra' => "Turma {$letraOffset}", 'turno' => 'Noite', 'cod' => "ENF2NT{$l}"]; }, range(0, 2), range('A', 'C'), range('R', 'T'))
                    ),
                    3 => array_merge(
                        array_map(function($idx, $l) { return ['letra' => "Turma {$l}", 'turno' => 'Manhã', 'cod' => "ENF3M{$l}"]; }, range(0, 6), range('A', 'G')),
                        array_map(function($idx, $l, $letraOffset) { return ['letra' => "Turma {$letraOffset}", 'turno' => 'Tarde', 'cod' => "ENF3T{$l}"]; }, range(0, 6), range('A', 'G'), range('H', 'N')),
                        array_map(function($idx, $l, $letraOffset) { return ['letra' => "Turma {$letraOffset}", 'turno' => 'Noite', 'cod' => "ENF3NT{$l}"]; }, range(0, 3), range('A', 'D'), range('O', 'R'))
                    ),
                    4 => array_map(function($idx, $l) { return ['letra' => "Turma {$l}", 'turno' => 'Noite', 'cod' => "ENF4NT{$l}"]; }, range(0, 7), range('A', 'H'))
                ],
                'FISI' => [
                    1 => array_merge(
                        array_map(function($idx, $l) { return ['letra' => "Turma {$l}", 'turno' => 'Manhã', 'cod' => "FISIO1M{$l}"]; }, range(0, 4), range('A', 'E')),
                        array_map(function($idx, $l, $letraOffset) { return ['letra' => "Turma {$letraOffset}", 'turno' => 'Noite', 'cod' => "FISIO1NT{$l}"]; }, range(0, 1), range('A', 'B'), ['F', 'G'])
                    ),
                    2 => array_merge(
                        array_map(function($idx, $l) { return ['letra' => "Turma {$l}", 'turno' => 'Manhã', 'cod' => "FISIO2M{$l}"]; }, range(0, 5), range('A', 'F')),
                        [['letra' => 'Turma G', 'turno' => 'Noite', 'cod' => 'FISIO2NTA']]
                    ),
                    3 => [
                        ['letra' => 'Turma A', 'turno' => 'Manhã', 'cod' => 'FISIO3MA'],
                        ['letra' => 'Turma B', 'turno' => 'Noite', 'cod' => 'FISIO3NTA'],
                    ]
                ],
                'CARD' => [
                    1 => array_merge(
                        array_map(function($idx, $l) { return ['letra' => "Turma {$l}", 'turno' => 'Manhã', 'cod' => "CARDIO1M{$l}"]; }, range(0, 5), range('A', 'F')),
                        array_map(function($idx, $l, $letraOffset) { return ['letra' => "Turma {$letraOffset}", 'turno' => 'Tarde', 'cod' => "CARDIO1T{$l}"]; }, range(0, 6), range('A', 'G'), range('G', 'M')),
                        [['letra' => 'Turma N', 'turno' => 'Noite', 'cod' => 'CARDIO1NTA']]
                    ),
                    2 => array_merge(
                        array_map(function($idx, $l) { return ['letra' => "Turma {$l}", 'turno' => 'Manhã', 'cod' => "CARDIO2M{$l}"]; }, range(0, 3), range('A', 'D')),
                        array_map(function($idx, $l, $letraOffset) { return ['letra' => "Turma {$letraOffset}", 'turno' => 'Tarde', 'cod' => "CARDIO2T{$l}"]; }, range(0, 5), range('A', 'F'), range('E', 'J')),
                        array_map(function($idx, $l, $letraOffset) { return ['letra' => "Turma {$letraOffset}", 'turno' => 'Noite', 'cod' => "CARDIO2NT{$l}"]; }, range(0, 1), range('A', 'B'), ['K', 'L'])
                    ),
                    3 => array_map(function($idx, $l) { return ['letra' => "Turma {$l}", 'turno' => 'Noite', 'cod' => "CARDIO3NT{$l}"]; }, range(0, 3), range('A', 'D')),
                    4 => array_map(function($idx, $l) { return ['letra' => "Turma {$l}", 'turno' => 'Noite', 'cod' => "CARDIO4NT{$l}"]; }, range(0, 1), range('A', 'B'))
                ]
            ];

            // Identificar chave do cronograma
            $cronKey = null;
            if (isset($cronogramaTurmas[$codCursoRaw])) {
                $cronKey = $codCursoRaw;
            } else {
                $prefix = substr($codCursoRaw, 0, 4);
                if (isset($cronogramaTurmas[$prefix])) {
                    $cronKey = $prefix;
                } else {
                    foreach (array_keys($cronogramaTurmas) as $k) {
                        if (stripos($nomeCurso, $k) !== false || stripos($codCursoRaw, $k) !== false) {
                            $cronKey = $k;
                            break;
                        }
                    }
                }
            }

            if (!$cronKey && stripos($nomeCurso, 'Enfermagem') !== false) $cronKey = 'ENFE';
            if (!$cronKey && stripos($nomeCurso, 'Cardio') !== false) $cronKey = 'CARD';
            if (!$cronKey && stripos($nomeCurso, 'Direito') !== false) $cronKey = 'DIRE';
            if (!$cronKey && stripos($nomeCurso, 'Fisio') !== false) $cronKey = 'FISI';
            if (!$cronKey && (stripos($nomeCurso, 'Análise') !== false || stripos($nomeCurso, 'Analise') !== false)) $cronKey = 'ANLI';

            $stmtInsTurma = $this->db->prepare("
                INSERT INTO turmas (id, disciplina_id, docente_id, designacao, turno, sumarios_registados, sumarios_previstos, programa_carregado, dosificacao_carregada, notas_no_prazo, inquerito_media)
                VALUES (:id, :disciplina_id, NULL, :designacao, :turno, 180, 200, 1, 1, 'Sim', 4.50)
                ON DUPLICATE KEY UPDATE designacao = VALUES(designacao), turno = VALUES(turno)
            ");

            $stmtInsLinha = $this->db->prepare("
                INSERT IGNORE INTO linhas_cobertura (plano_id, disciplina_id, turma_id, conformidade, regime, parecer, decisao_aprovacao)
                VALUES (:plano_id, :disciplina_id, :turma_id, 'Por verificar', 'Tempo Parcial', 'Manter', 'Aprovar')
            ");

            if ($cronKey && isset($cronogramaTurmas[$cronKey])) {
                $specsPorAno = $cronogramaTurmas[$cronKey];
                foreach ($specsPorAno as $ano => $turmasList) {
                    $discsDoAno = $discsByAno[$ano] ?? [];
                    foreach ($turmasList as $tSpec) {
                        $tCod = $tSpec['cod'];
                        $tLetra = $tSpec['letra'] ?? 'Turma A';
                        $tTurno = $tSpec['turno'];
                        $desigCompleta = "{$tLetra} ({$tCod})";

                        foreach ($discsDoAno as $d) {
                            $discId = (int)$d['id'];
                            $turmaRowId = "{$tCod}-D{$discId}";

                            try {
                                $stmtInsTurma->execute([
                                    ':id'            => $turmaRowId,
                                    ':disciplina_id' => $discId,
                                    ':designacao'    => $desigCompleta,
                                    ':turno'         => $tTurno
                                ]);
                                $stmtInsLinha->execute([
                                    ':plano_id'      => $planoId,
                                    ':disciplina_id' => $discId,
                                    ':turma_id'      => $turmaRowId
                                ]);
                            } catch (\Throwable $e) {
                                // Ignorar se já existir
                            }
                        }
                    }
                }
            } else {
                // Fallback para cursos genéricos
                foreach ($discs as $d) {
                    $discId = (int)$d['id'];
                    $ano = (int)$d['ano_curricular'];
                    $turmaRowId = "{$codCursoRaw}{$ano}MA-D{$discId}";
                    try {
                        $stmtInsTurma->execute([
                            ':id'            => $turmaRowId,
                            ':disciplina_id' => $discId,
                            ':designacao'    => "{$codCursoRaw}{$ano}MA",
                            ':turno'         => 'Manhã'
                        ]);
                        $stmtInsLinha->execute([
                            ':plano_id'      => $planoId,
                            ':disciplina_id' => $discId,
                            ':turma_id'      => $turmaRowId
                        ]);
                    } catch (\Throwable $e) {
                        // Ignorar se já existir
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silenciar exceções para não interromper a navegação
        }
    }

    /**
     * Consulta otimizada de alta performance via View SQL vw_linhas_cobertura_detalhada
     */
    public function getLinhasPlano(int $planoId, string $anoLectivo = '2026/27'): array {
        $this->ensureLinhasCompletas($planoId);

        $stmt = $this->db->prepare("
            SELECT * FROM vw_linhas_cobertura_detalhada
            WHERE plano_id = ?
            ORDER BY ano_curricular ASC, semestre ASC, disciplina_id ASC, id ASC
        ");
        $stmt->execute([$planoId]);
        return $stmt->fetchAll();
    }

    public function getLinhaById(int $linhaId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM vw_linhas_cobertura_detalhada
            WHERE linha_id = ?
            LIMIT 1
        ");
        $stmt->execute([$linhaId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateLinha(int $linhaId, array $data): bool {
        // Verificar estado do plano para evitar edições não autorizadas
        $stmtCheck = $this->db->prepare("
            SELECT pc.estado 
            FROM linhas_cobertura lc 
            JOIN planos_cobertura pc ON lc.plano_id = pc.id 
            WHERE lc.id = ?
        ");
        $stmtCheck->execute([$linhaId]);
        $estado = $stmtCheck->fetchColumn();

        if ($estado === 'Validado' && !Auth::hasRole(['admin', 'presidente'])) {
            return false;
        }

        $fields = [];
        $params = [':id' => $linhaId];

        if (array_key_exists('docente_id', $data)) {
            $docenteId = !empty($data['docente_id']) ? (int)$data['docente_id'] : null;
            $fields[] = "docente_id = :docente_id";
            $params[':docente_id'] = $docenteId;

            // Recalcular conformidade automática baseada no perfil do docente atribuído
            if ($docenteId) {
                require_once __DIR__ . '/DocenteModel.php';
                $docModel = new DocenteModel();
                $doc = $docModel->getById($docenteId);
                if ($doc) {
                    if ($doc['estado_capacidade'] === 'Sobregregado') {
                        $conf = 'Parcial';
                    } elseif ($doc['grau_academico'] === 'Doutor') {
                        $conf = 'Sim';
                    } elseif ($doc['grau_academico'] === 'Mestre' && $doc['tem_inaarees'] === 'Sim') {
                        $conf = 'Sim';
                    } elseif ($doc['grau_academico'] === 'Mestre') {
                        $conf = 'Parcial';
                    } else {
                        $conf = 'Não';
                    }
                    if (!isset($data['conformidade'])) {
                        $fields[] = "conformidade = :auto_conf";
                        $params[':auto_conf'] = $conf;
                    }
                }
            } else {
                if (!isset($data['conformidade'])) {
                    $fields[] = "conformidade = 'Não'";
                }
            }
        }

        if (array_key_exists('conformidade', $data)) {
            $fields[] = "conformidade = :conf";
            $params[':conf'] = $data['conformidade'];
        }

        if (array_key_exists('justificacao', $data)) {
            $fields[] = "justificacao = :just";
            $params[':just'] = $data['justificacao'];
        }

        if (array_key_exists('regime', $data)) {
            $fields[] = "regime = :regime";
            $params[':regime'] = $data['regime'];
        }

        if (array_key_exists('categoria_carreira', $data)) {
            $fields[] = "categoria_carreira = :categoria";
            $params[':categoria'] = $data['categoria_carreira'];
        }

        if (array_key_exists('parecer', $data)) {
            $fields[] = "parecer = :parecer";
            $params[':parecer'] = $data['parecer'];
        }

        if (array_key_exists('decisao_aprovacao', $data)) {
            $fields[] = "decisao_aprovacao = :decisao_aprovacao";
            $params[':decisao_aprovacao'] = $data['decisao_aprovacao'];
        }

        if (array_key_exists('observacoes', $data)) {
            $fields[] = "observacoes = :obs";
            $params[':obs'] = $data['observacoes'];
        }

        if (empty($fields)) {
            return true;
        }

        $sql = "UPDATE linhas_cobertura SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Localiza a linha correspondente do par sequencial (ex: Semestre 1 -> Semestre 2)
     */
    public function findSequentialPairLinha(int $linhaId): ?array {
        $linha = $this->getLinhaById($linhaId);
        if (!$linha) return null;

        $nome = trim($linha['disciplina_nome'] ?? '');
        $planoId = (int)$linha['plano_id'];
        $ano = (int)$linha['ano_curricular'];
        $turmaId = $linha['turma_id'];

        // Determinar nome do par sequencial
        $targetNome = null;
        if (preg_match('/\bI\b(?!\s*I)/u', $nome) && !preg_match('/\b(II|III|IV|VI)\b/u', $nome)) {
            $targetNome = preg_replace('/\bI\b(?!\s*I)/u', 'II', $nome);
        } elseif (preg_match('/\bIII\b/u', $nome)) {
            $targetNome = preg_replace('/\bIII\b/u', 'IV', $nome);
        } elseif (preg_match('/\bII\b/u', $nome) && !preg_match('/\bIII\b/u', $nome)) {
            $targetNome = preg_replace('/\bII\b/u', 'I', $nome);
        } elseif (preg_match('/\bIV\b/u', $nome)) {
            $targetNome = preg_replace('/\bIV\b/u', 'III', $nome);
        } else {
            return null;
        }

        // Extrair raiz sem parênteses para matching flexível (ex: Microeconomia I (Consumo) -> Microeconomia II (Mercados))
        $rootTarget = preg_replace('/\s*\(.*?\)/', '', $targetNome);
        $rootTarget = trim(preg_replace('/\s+/', ' ', $rootTarget));
        $rootTargetBase = trim(preg_replace('/\b(I|II|III|IV)\b/u', '', $rootTarget));

        $stmt = $this->db->prepare("
            SELECT * FROM vw_linhas_cobertura_detalhada
            WHERE plano_id = :plano_id
              AND ano_curricular = :ano
              AND linha_id != :linha_id
        ");
        $stmt->execute([
            ':plano_id' => $planoId,
            ':ano'      => $ano,
            ':linha_id' => $linhaId
        ]);
        $candidates = $stmt->fetchAll();

        foreach ($candidates as $c) {
            if ($c['turma_id'] === $turmaId || (empty($c['turma_id']) && empty($turmaId))) {
                $cNome = trim($c['disciplina_nome']);
                $cRoot = preg_replace('/\s*\(.*?\)/', '', $cNome);
                $cRoot = trim(preg_replace('/\s+/', ' ', $cRoot));
                $cRootBase = trim(preg_replace('/\b(I|II|III|IV)\b/u', '', $cRoot));

                if (strcasecmp($cNome, $targetNome) === 0 || strcasecmp($cRoot, $rootTarget) === 0 || (strcasecmp($cRootBase, $rootTargetBase) === 0 && !empty($rootTargetBase))) {
                    return $c;
                }
            }
        }

        return null;
    }

    public function applyDocenteToAllTurmasSameYear(int $planoId, int $disciplinaId, ?int $docenteId, bool $includeSequential = true): array {
        $conf = 'Não';
        if ($docenteId) {
            require_once __DIR__ . '/DocenteModel.php';
            $docModel = new DocenteModel();
            $doc = $docModel->getById($docenteId);
            if ($doc) {
                if ($doc['estado_capacidade'] === 'Sobregregado') {
                    $conf = 'Parcial';
                } elseif ($doc['grau_academico'] === 'Doutor') {
                    $conf = 'Sim';
                } elseif ($doc['grau_academico'] === 'Mestre' && $doc['tem_inaarees'] === 'Sim') {
                    $conf = 'Sim';
                } elseif ($doc['grau_academico'] === 'Mestre') {
                    $conf = 'Parcial';
                } else {
                    $conf = 'Não';
                }
            }
        }

        // 1. Atualizar a disciplina em todas as turmas do plano
        $stmt = $this->db->prepare("
            UPDATE linhas_cobertura
            SET docente_id = :docente_id,
                conformidade = :conf
            WHERE plano_id = :plano_id 
              AND disciplina_id = :disciplina_id
        ");
        $stmt->execute([
            ':docente_id'    => $docenteId,
            ':conf'          => $conf,
            ':plano_id'      => $planoId,
            ':disciplina_id' => $disciplinaId
        ]);
        $affectedCount = $stmt->rowCount();

        // 2. Se houver disciplina sequencial correspondente, replicar também
        $pairNome = null;
        if ($includeSequential) {
            $stmtOne = $this->db->prepare("SELECT id FROM linhas_cobertura WHERE plano_id = ? AND disciplina_id = ? LIMIT 1");
            $stmtOne->execute([$planoId, $disciplinaId]);
            $sampleLinhaId = (int)$stmtOne->fetchColumn();
            if ($sampleLinhaId) {
                $pairLinha = $this->findSequentialPairLinha($sampleLinhaId);
                if ($pairLinha) {
                    $pairDiscId = (int)$pairLinha['disciplina_id'];
                    $stmt->execute([
                        ':docente_id'    => $docenteId,
                        ':conf'          => $conf,
                        ':plano_id'      => $planoId,
                        ':disciplina_id' => $pairDiscId
                    ]);
                    $affectedCount += $stmt->rowCount();
                    $pairNome = $pairLinha['disciplina_nome'];
                }
            }
        }

        return [
            'success'        => true,
            'affected_count' => $affectedCount,
            'pair_nome'      => $pairNome
        ];
    }

    /**
     * Prepara e simula a sincronização bidirecional do plano aprovado com o sistema de Gestão Escolar
     */
    public function sincronizarComGestaoEscolar(int $planoId): array {
        $linhas = $this->getLinhasPlano($planoId);
        $sincronizados = 0;
        $erros = [];

        foreach ($linhas as $l) {
            if ($l['docente_id'] && $l['turma_id']) {
                // Atualiza a turma correspondente no banco com o docente aprovado
                $stmt = $this->db->prepare("UPDATE turmas SET docente_id = ? WHERE id = ?");
                $res = $stmt->execute([$l['docente_id'], $l['turma_id']]);
                if ($res) {
                    $sincronizados++;
                } else {
                    $erros[] = "Falha ao sincronizar turma {$l['turma_id']}";
                }
            }
        }

        return [
            'success' => true,
            'sincronizados' => $sincronizados,
            'erros' => $erros,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public function updateEstadoPlano(int $planoId, string $estado, ?string $obs = null, ?int $userId = null): bool {
        $sql = "UPDATE planos_cobertura SET estado = :estado, observacoes = :obs";
        if ($estado === 'Submetido') {
            $sql .= ", data_submissao = NOW()";
        } elseif ($estado === 'Aprovado') {
            $sql .= ", data_aprovacao = NOW()";
        }
        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $res = $stmt->execute([':estado' => $estado, ':obs' => $obs, ':id' => $planoId]);

        if ($res && $userId) {
            $stmtHist = $this->db->prepare("
                INSERT INTO historico_aprovacoes (plano_id, utilizador_id, acao, comentario)
                VALUES (?, ?, ?, ?)
            ");
            $stmtHist->execute([$planoId, $userId, $estado, $obs]);
        }

        return $res;
    }

    public function getConsolidadosStats(string $anoLectivo = '2026/27'): array {
        $stmt = $this->db->prepare("
            SELECT 
                c.id AS curso_id,
                c.nome AS curso_nome,
                pc.id AS plano_id,
                COALESCE(pc.estado, 'Rascunho') AS estado,
                pc.data_submissao,
                COUNT(DISTINCT t.designacao) AS num_turmas,
                COUNT(lc.id) AS total_uc,
                SUM(CASE WHEN lc.docente_id IS NOT NULL THEN 1 ELSE 0 END) AS uc_atribuidas,
                SUM(CASE WHEN lc.conformidade = 'Sim' THEN 1 ELSE 0 END) AS conf_sim,
                SUM(CASE WHEN lc.conformidade = 'Parcial' THEN 1 ELSE 0 END) AS conf_parcial,
                SUM(CASE WHEN lc.conformidade = 'Não' THEN 1 ELSE 0 END) AS conf_nao,
                SUM(CASE WHEN lc.conformidade = 'Por verificar' THEN 1 ELSE 0 END) AS conf_ni
            FROM cursos c
            LEFT JOIN planos_cobertura pc ON c.id = pc.curso_id AND pc.ano_lectivo = ?
            LEFT JOIN linhas_cobertura lc ON pc.id = lc.plano_id
            LEFT JOIN turmas t ON lc.turma_id = t.id
            WHERE c.activo = 1
            GROUP BY c.id, c.nome, pc.id, pc.estado, pc.data_submissao
            ORDER BY c.nome ASC
        ");
        $stmt->execute([$anoLectivo]);
        return $stmt->fetchAll();
    }

    /**
     * Retorna a análise estatística comparativa entre dois Anos Lectivos (ex: 2025/26 vs 2026/27)
     */
    public function getComparativoAnualStats(string $anoA = '2025/26', string $anoB = '2026/27'): array {
        $stmtA = $this->db->prepare("
            SELECT 
                c.id AS curso_id,
                c.nome AS curso_nome,
                COUNT(lc.id) AS total_uc,
                SUM(CASE WHEN lc.docente_id IS NOT NULL THEN 1 ELSE 0 END) AS uc_atribuidas,
                SUM(CASE WHEN lc.conformidade = 'Sim' THEN 1 ELSE 0 END) AS conf_sim
            FROM cursos c
            LEFT JOIN planos_cobertura pc ON c.id = pc.curso_id AND pc.ano_lectivo = ?
            LEFT JOIN linhas_cobertura lc ON pc.id = lc.plano_id
            WHERE c.activo = 1
            GROUP BY c.id, c.nome
        ");
        $stmtA->execute([$anoA]);
        $statsA = $stmtA->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

        $stmtB = $this->db->prepare("
            SELECT 
                c.id AS curso_id,
                c.nome AS curso_nome,
                COUNT(lc.id) AS total_uc,
                SUM(CASE WHEN lc.docente_id IS NOT NULL THEN 1 ELSE 0 END) AS uc_atribuidas,
                SUM(CASE WHEN lc.conformidade = 'Sim' THEN 1 ELSE 0 END) AS conf_sim
            FROM cursos c
            LEFT JOIN planos_cobertura pc ON c.id = pc.curso_id AND pc.ano_lectivo = ?
            LEFT JOIN linhas_cobertura lc ON pc.id = lc.plano_id
            WHERE c.activo = 1
            GROUP BY c.id, c.nome
        ");
        $stmtB->execute([$anoB]);
        $statsB = $stmtB->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

        $resultado = [];
        $stmtCursos = $this->db->query("SELECT id, nome FROM cursos WHERE activo = 1 ORDER BY nome ASC");
        $cursos = $stmtCursos->fetchAll();

        foreach ($cursos as $c) {
            $cid = $c['id'];
            $dataA = $statsA[$cid] ?? ['total_uc' => 0, 'uc_atribuidas' => 0, 'conf_sim' => 0];
            $dataB = $statsB[$cid] ?? ['total_uc' => 0, 'uc_atribuidas' => 0, 'conf_sim' => 0];

            $totA  = (int)$dataA['total_uc'];
            $simA  = (int)$dataA['conf_sim'];
            $pctA  = $totA > 0 ? round(($simA / $totA) * 100, 1) : 0;

            $totB  = (int)$dataB['total_uc'];
            $simB  = (int)$dataB['conf_sim'];
            $pctB  = $totB > 0 ? round(($simB / $totB) * 100, 1) : 0;

            $variacaoPct = round($pctB - $pctA, 1);

            $resultado[] = [
                'curso_id'     => $cid,
                'curso_nome'   => $c['nome'],
                'anoA'         => ['ano' => $anoA, 'total_uc' => $totA, 'conf_sim' => $simA, 'pct' => $pctA],
                'anoB'         => ['ano' => $anoB, 'total_uc' => $totB, 'conf_sim' => $simB, 'pct' => $pctB],
                'variacao_pct' => $variacaoPct
            ];
        }

        return $resultado;
    }

    public function getDocentesSobrecargaCount(): int {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM vw_docentes_sobrecarga");
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Motor de Inteligência & Matchmaking SQL: Sugere os melhores docentes para uma disciplina
     */
    public function sugerirDocentesParaDisciplina(int $disciplinaId, int $limit = 5): array {
        $stmt = $this->db->prepare("
            SELECT * FROM vw_matchmaking_docentes
            WHERE disciplina_id = ?
            ORDER BY pontuacao_compatibilidade DESC, soma_horas_semanais ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $disciplinaId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Motor de Diagnóstico de Risco Académico e Pedagógico
     */
    public function getDiagnosticoRisco(?int $cursoId = null): array {
        $sql = "SELECT * FROM vw_diagnostico_risco_academico WHERE gravidade_risco > 0";
        $params = [];
        if ($cursoId) {
            $sql .= " AND curso_id = ?";
            $params[] = $cursoId;
        }
        $sql .= " ORDER BY gravidade_risco DESC, disciplina_nome ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Recalcula em lote a conformidade regulatória automática das linhas do plano
     */
    public function recalcularConformidadesEmLote(int $planoId): int {
        $linhas = $this->getLinhasPlano($planoId);
        $atualizados = 0;

        $stmtUpdate = $this->db->prepare("UPDATE linhas_cobertura SET conformidade = ? WHERE id = ?");

        foreach ($linhas as $l) {
            if (!$l['docente_id']) {
                $conf = 'Não';
            } elseif ($l['docente_estado_capacidade'] === 'Sobregregado') {
                $conf = 'Parcial';
            } elseif ($l['docente_grau'] === 'Doutor' || ($l['docente_grau'] === 'Mestre' && $l['docente_inaarees'] === 'Sim')) {
                $conf = 'Sim';
            } else {
                $conf = 'Parcial';
            }

            if ($conf !== $l['conformidade']) {
                $stmtUpdate->execute([$conf, $l['linha_id']]);
                $atualizados++;
            }
        }

        return $atualizados;
    }

    /**
     * Estatísticas Consolidadas de Conformidade por Curso para o Dashboard BI
     */
    public function getDashboardConformidadeCursos(string $anoLectivo = '2026/27'): array {
        $stmt = $this->db->prepare("
            SELECT 
                c.id AS curso_id,
                c.codigo AS curso_codigo,
                c.nome AS curso_nome,
                COUNT(lc.id) AS total_uc,
                SUM(CASE WHEN lc.conformidade = 'Sim' THEN 1 ELSE 0 END) AS conf_sim,
                SUM(CASE WHEN lc.conformidade = 'Parcial' THEN 1 ELSE 0 END) AS conf_parcial,
                SUM(CASE WHEN lc.conformidade = 'Não' THEN 1 ELSE 0 END) AS conf_nao,
                SUM(CASE WHEN lc.conformidade = 'Por verificar' OR lc.conformidade IS NULL THEN 1 ELSE 0 END) AS conf_ni
            FROM cursos c
            LEFT JOIN planos_cobertura pc ON c.id = pc.curso_id AND pc.ano_lectivo = ?
            LEFT JOIN linhas_cobertura lc ON pc.id = lc.plano_id
            WHERE c.activo = 1
            GROUP BY c.id, c.codigo, c.nome
        ");
        $stmt->execute([$anoLectivo]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $total = (int)$r['total_uc'];
            $sim = (int)$r['conf_sim'];
            $r['pct_conf'] = ($total > 0) ? round(($sim / $total) * 100, 1) : 0;
            if ($r['pct_conf'] >= 70) {
                $r['situacao'] = 'Favorável';
                $r['situacao_class'] = 'ok';
            } elseif ($r['pct_conf'] >= 50) {
                $r['situacao'] = 'Atenção';
                $r['situacao_class'] = 'warn';
            } else {
                $r['situacao'] = 'Crítico';
                $r['situacao_class'] = 'bad';
            }
        }

        return $rows;
    }

    public function aprovarPeloDepartamento(int $planoId, int $chefeDeptoId, ?string $parecer = null): bool {
        $stmt = $this->db->prepare("
            UPDATE planos_cobertura
            SET estado = 'Aprovado pelo Departamento',
                chefe_depto_id = ?,
                data_aprovacao_depto = NOW(),
                parecer_depto = ?
            WHERE id = ?
        ");
        $success = $stmt->execute([$chefeDeptoId, $parecer, $planoId]);

        if ($success) {
            require_once __DIR__ . '/../helpers/Notification.php';
            Notification::add($planoId, 'Aprovado pelo Departamento', $parecer);
        }
        return $success;
    }

    public function validarPelaPresidencia(int $planoId, int $presidenteId, ?string $parecer = null): bool {
        $stmt = $this->db->prepare("
            UPDATE planos_cobertura
            SET estado = 'Validado',
                presidente_id = ?,
                data_validacao_pr = NOW(),
                parecer_pr = ?
            WHERE id = ?
        ");
        $success = $stmt->execute([$presidenteId, $parecer, $planoId]);

        if ($success) {
            require_once __DIR__ . '/../helpers/Notification.php';
            Notification::add($planoId, 'Validado', $parecer);
        }
        return $success;
    }

    public function submeterPlano(int $planoId, int $utilizadorId): bool {
        $stmt = $this->db->prepare("
            UPDATE planos_cobertura
            SET estado = 'Submetido',
                criado_por = ?,
                data_submissao = NOW()
            WHERE id = ?
        ");
        $success = $stmt->execute([$utilizadorId, $planoId]);

        if ($success) {
            require_once __DIR__ . '/../helpers/Notification.php';
            Notification::add($planoId, 'Submetido', 'Plano submetido para apreciação do Chefe de Departamento.');
        }
        return $success;
    }

    public function devolverPlano(int $planoId, int $utilizadorId, ?string $parecer = null): bool {
        $stmt = $this->db->prepare("
            UPDATE planos_cobertura
            SET estado = 'Devolvido',
                observacoes = ?
            WHERE id = ?
        ");
        $success = $stmt->execute([$parecer, $planoId]);

        if ($success) {
            require_once __DIR__ . '/../helpers/Notification.php';
            Notification::add($planoId, 'Devolvido', $parecer);
        }
        return $success;
    }

    public function getHistoricoAprovacoes(int $planoId): array {
        $stmt = $this->db->prepare("
            SELECT h.*, u.nome AS utilizador_nome, u.perfil AS utilizador_perfil
            FROM historico_aprovacoes h
            LEFT JOIN utilizadores u ON h.utilizador_id = u.id
            WHERE h.plano_id = ?
            ORDER BY h.created_at DESC
        ");
        $stmt->execute([$planoId]);
        return $stmt->fetchAll();
    }

    /**
     * Algoritmo Institucional de Roll-Over de Ano Lectivo (Transição 2026/27 -> 2027/28)
     * Duplica os planos de cobertura e replica todas as atribuições em transação SQL segura.
     */
    public function executarRollOver(string $anoOrigem = '2026/27', string $anoDestino = '2027/28', ?int $userId = null): array {
        try {
            $this->db->beginTransaction();

            // 1. Buscar todos os cursos ativos
            $stmtCursos = $this->db->query("SELECT id, nome FROM cursos WHERE activo = 1");
            $cursos = $stmtCursos->fetchAll();

            $planosCriados = 0;
            $linhasReplicadas = 0;

            foreach ($cursos as $c) {
                $cursoId = (int)$c['id'];

                // Buscar o plano de origem
                $planoOrigem = $this->getByCursoEAno($cursoId, $anoOrigem);
                if (!$planoOrigem) {
                    continue;
                }

                // Verificar ou criar o plano de destino
                $planoDestino = $this->getByCursoEAno($cursoId, $anoDestino);
                if (!$planoDestino) {
                    $stmtInsPlano = $this->db->prepare("
                        INSERT INTO planos_cobertura (curso_id, ano_lectivo, estado, criado_por, observacoes)
                        VALUES (?, ?, 'Rascunho', ?, ?)
                    ");
                    $obsRollOver = "Plano gerado automaticamente via Roll-Over a partir de {$anoOrigem}";
                    $stmtInsPlano->execute([$cursoId, $anoDestino, $userId, $obsRollOver]);
                    $planoDestinoId = (int)$this->db->lastInsertId();
                    $planosCriados++;
                } else {
                    $planoDestinoId = (int)$planoDestino['id'];
                }

                // Buscar linhas do plano de origem
                $stmtLinhasOrigem = $this->db->prepare("SELECT * FROM linhas_cobertura WHERE plano_id = ?");
                $stmtLinhasOrigem->execute([$planoOrigem['id']]);
                $linhasOrigem = $stmtLinhasOrigem->fetchAll();

                // Limpar linhas existentes no plano de destino para idempotência
                $stmtDelDestino = $this->db->prepare("DELETE FROM linhas_cobertura WHERE plano_id = ?");
                $stmtDelDestino->execute([$planoDestinoId]);

                // Inserir linhas duplicadas no destino
                $stmtInsLinha = $this->db->prepare("
                    INSERT INTO linhas_cobertura (
                        plano_id, disciplina_id, turma_id, docente_id, 
                        conformidade, justificacao, regime, categoria_carreira, parecer, observacoes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                foreach ($linhasOrigem as $l) {
                    $stmtInsLinha->execute([
                        $planoDestinoId,
                        $l['disciplina_id'],
                        $l['turma_id'],
                        $l['docente_id'],
                        $l['conformidade'],
                        $l['justificacao'],
                        $l['regime'],
                        $l['categoria_carreira'],
                        $l['parecer'],
                        $l['observacoes']
                    ]);
                    $linhasReplicadas++;
                }

                // Registar histórico de auditoria
                if ($userId) {
                    $stmtHist = $this->db->prepare("
                        INSERT INTO historico_aprovacoes (plano_id, utilizador_id, acao, comentario)
                        VALUES (?, ?, 'Submetido', ?)
                    ");
                    $stmtHist->execute([$planoDestinoId, $userId, "Transição de atribuições (Roll-Over de {$anoOrigem} para {$anoDestino})"]);
                }
            }

            $this->db->commit();

            return [
                'success'           => true,
                'planos_criados'    => $planosCriados,
                'linhas_replicadas' => $linhasReplicadas,
                'ano_origem'        => $anoOrigem,
                'ano_destino'       => $anoDestino,
                'message'           => "Roll-Over concluído! Replicadas {$linhasReplicadas} atribuições para o ano lectivo {$anoDestino}."
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Erro ao executar Roll-Over: ' . $e->getMessage()
            ];
        }
    }

    public function recalcularConformidadeLinha(int $linhaId): bool {
        $linha = $this->getLinhaById($linhaId);
        if (!$linha) return false;

        $conf = 'Não';
        if ($linha['docente_id']) {
            if ($linha['docente_estado_capacidade'] === 'Sobregregado') {
                $conf = 'Parcial';
            } elseif ($linha['docente_grau'] === 'Doutor' || ($linha['docente_grau'] === 'Mestre' && $linha['docente_inaarees'] === 'Sim')) {
                $conf = 'Sim';
            } else {
                $conf = 'Parcial';
            }
        }

        $stmt = $this->db->prepare("UPDATE linhas_cobertura SET conformidade = ? WHERE id = ?");
        return $stmt->execute([$conf, $linhaId]);
    }

    /**
     * Propaga atualizações de perfil/CV do docente para todas as linhas de cobertura ativas onde está atribuído
     */
    public function propagarPerfilDocenteNasLinhas(int $docenteId): int {
        // Buscar todas as linhas ativas do docente
        $stmt = $this->db->prepare("SELECT id FROM linhas_cobertura WHERE docente_id = ?");
        $stmt->execute([$docenteId]);
        $linhas = $stmt->fetchAll();

        $count = 0;
        foreach ($linhas as $l) {
            $this->recalcularConformidadeLinha((int)$l['id']);
            $count++;
        }
        return $count;
    }
}

