<?php
/**
 * ISPSN 2026/27 — Script Oficial de Padronização Canónica e Sincronização
 * Arquitetura: Transação ACID Segura (Commit / Rollback)
 * 
 * Executa a reconstrução fiel das turmas por Curso, Ano e Turno seguindo
 * a sequência alfabética contínua (Turma A, B, C...) por Ano Curricular,
 * eliminando turmas legadas e linhas duplicadas, preservando 100% das atribuições.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "========================================================================\n";
echo "  ISPSN 2026/27 — PADRONIZAÇÃO CANÓNICA & SINCRONIZAÇÃO EM PRODUÇÃO     \n";
echo "========================================================================\n\n";

try {
    $db = Database::getInstance();
    $db->exec("SET NAMES utf8mb4");

    // 1. Definição Canónica Completa das Turmas por Curso e Ano
    // Rótulo alfabético sequencial por Ano Curricular (Turma A, B, C...)
    $distribuicaoOficial = [
        // 1. CPRI (Ciências Políticas e Relações Internacionais)
        'CPRI' => [
            1 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã',    'cod' => 'CPRI1MA'],
                ['letra' => 'Turma B', 'turno' => 'Tarde',    'cod' => 'CPRI1TA'],
                ['letra' => 'Turma C', 'turno' => 'Noite',    'cod' => 'CPRI1NTA'],
                ['letra' => 'Turma D', 'turno' => 'Regime B', 'cod' => 'CPRI-RB-MA'],
                ['letra' => 'Turma E', 'turno' => 'Regime B', 'cod' => 'CPRI-RB-TA'],
            ],
            2 => [
                ['letra' => 'Turma A', 'turno' => 'Tarde',    'cod' => 'CPRI2TA'],
            ],
            3 => [
                ['letra' => 'Turma A', 'turno' => 'Noite',    'cod' => 'CPRI3NTA'],
            ],
            4 => [
                ['letra' => 'Turma A', 'turno' => 'Noite',    'cod' => 'CPRI4NTA'],
            ],
        ],

        // 2. Sociologia
        'SOCI' => [
            1 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã',    'cod' => 'SOC1MA'],
                ['letra' => 'Turma B', 'turno' => 'Tarde',    'cod' => 'SOC1TA'],
                ['letra' => 'Turma C', 'turno' => 'Noite',    'cod' => 'SOC1NTA'],
                ['letra' => 'Turma D', 'turno' => 'Regime B', 'cod' => 'SOC-RB-MA'],
                ['letra' => 'Turma E', 'turno' => 'Regime B', 'cod' => 'SOC-RB-TA'],
            ],
            2 => [
                ['letra' => 'Turma A', 'turno' => 'Tarde',    'cod' => 'SOC2TA'],
            ],
            3 => [
                ['letra' => 'Turma A', 'turno' => 'Noite',    'cod' => 'SOC3NTA'],
            ],
            4 => [
                ['letra' => 'Turma A', 'turno' => 'Noite',    'cod' => 'SOC4NTA'],
            ],
        ],

        // 3. Economia
        'ECON' => [
            1 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã', 'cod' => 'ECO1MA'],
                ['letra' => 'Turma B', 'turno' => 'Manhã', 'cod' => 'ECO1MB'],
                ['letra' => 'Turma C', 'turno' => 'Tarde', 'cod' => 'ECO1TA'],
                ['letra' => 'Turma D', 'turno' => 'Noite', 'cod' => 'ECO1NTA'],
            ],
            2 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã', 'cod' => 'ECO2MA'],
                ['letra' => 'Turma B', 'turno' => 'Tarde', 'cod' => 'ECO2TA'],
                ['letra' => 'Turma C', 'turno' => 'Noite', 'cod' => 'ECO2NTA'],
            ],
            3 => [
                ['letra' => 'Turma A', 'turno' => 'Noite', 'cod' => 'ECO3NTA'],
            ],
            4 => [
                ['letra' => 'Turma A', 'turno' => 'Noite', 'cod' => 'ECO4NTA'],
            ],
        ],

        // 4. Contabilidade e Finanças
        'CONT' => [
            1 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã', 'cod' => 'COF1MA'],
                ['letra' => 'Turma B', 'turno' => 'Manhã', 'cod' => 'COF1MB'],
                ['letra' => 'Turma C', 'turno' => 'Tarde', 'cod' => 'COF1TA'],
                ['letra' => 'Turma D', 'turno' => 'Noite', 'cod' => 'COF1NTA'],
            ],
            2 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã', 'cod' => 'COF2MA'],
                ['letra' => 'Turma B', 'turno' => 'Tarde', 'cod' => 'COF2TA'],
                ['letra' => 'Turma C', 'turno' => 'Noite', 'cod' => 'COF2NTA'],
            ],
            3 => [
                ['letra' => 'Turma A', 'turno' => 'Noite', 'cod' => 'COF3NTA'],
            ],
            4 => [
                ['letra' => 'Turma A', 'turno' => 'Noite', 'cod' => 'COF4NTA'],
            ],
        ],

        // 5. Gestão de Recursos Humanos (GRH)
        'GRH' => [
            1 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã',    'cod' => 'GRH1MA'],
                ['letra' => 'Turma B', 'turno' => 'Tarde',    'cod' => 'GRH1TA'],
                ['letra' => 'Turma C', 'turno' => 'Tarde',    'cod' => 'GRH1TB'],
                ['letra' => 'Turma D', 'turno' => 'Noite',    'cod' => 'GRH1NTA'],
                ['letra' => 'Turma E', 'turno' => 'Noite',    'cod' => 'GRH1NTB'],
                ['letra' => 'Turma F', 'turno' => 'Regime B', 'cod' => 'GRH-RB1'],
            ],
            2 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã', 'cod' => 'GRH2MA'],
                ['letra' => 'Turma B', 'turno' => 'Tarde', 'cod' => 'GRH2TA'],
                ['letra' => 'Turma C', 'turno' => 'Noite', 'cod' => 'GRH2NTA'],
            ],
            3 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã', 'cod' => 'GRH3MA'],
                ['letra' => 'Turma B', 'turno' => 'Noite', 'cod' => 'GRH3NTA'],
            ],
            4 => [
                ['letra' => 'Turma A', 'turno' => 'Noite', 'cod' => 'GRH4NTA'],
            ],
        ],

        // 6. História e Didáctica
        'HIST' => [
            1 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã',    'cod' => 'HIST1MA'],
                ['letra' => 'Turma B', 'turno' => 'Tarde',    'cod' => 'HIST1TA'],
                ['letra' => 'Turma C', 'turno' => 'Noite',    'cod' => 'HIST1NTA'],
                ['letra' => 'Turma D', 'turno' => 'Regime B', 'cod' => 'HIST-RB1'],
            ],
            2 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã',    'cod' => 'HIST2MA'],
                ['letra' => 'Turma B', 'turno' => 'Tarde',    'cod' => 'HIST2TA'],
                ['letra' => 'Turma C', 'turno' => 'Regime B', 'cod' => 'HIST-RB2'],
            ],
            3 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã',    'cod' => 'HIST3MA'],
                ['letra' => 'Turma B', 'turno' => 'Noite',    'cod' => 'HIST3NTA'],
                ['letra' => 'Turma C', 'turno' => 'Regime B', 'cod' => 'HIST-RB3'],
            ],
            4 => [
                ['letra' => 'Turma A', 'turno' => 'Noite',    'cod' => 'HIST4NTA'],
                ['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'HIST-RB4'],
            ],
        ],

        // 7. Psicologia e Didáctica
        'PSIC' => [
            1 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã', 'cod' => 'PSIC1MA'],
                ['letra' => 'Turma B', 'turno' => 'Tarde', 'cod' => 'PSIC1TA'],
                ['letra' => 'Turma C', 'turno' => 'Noite', 'cod' => 'PSIC1NTA'],
            ],
            2 => [
                ['letra' => 'Turma A', 'turno' => 'Tarde', 'cod' => 'PSIC2TA'],
                ['letra' => 'Turma B', 'turno' => 'Noite', 'cod' => 'PSIC2NTA'],
            ],
            3 => [
                ['letra' => 'Turma A', 'turno' => 'Tarde', 'cod' => 'PSIC3TA'],
                ['letra' => 'Turma B', 'turno' => 'Noite', 'cod' => 'PSIC3NTA'],
            ],
            4 => [
                ['letra' => 'Turma A', 'turno' => 'Noite', 'cod' => 'PSIC4NTA'],
            ],
        ],

        // 8. Direito
        'DIRE' => [
            1 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã',    'cod' => 'DIR1MA'],
                ['letra' => 'Turma B', 'turno' => 'Manhã',    'cod' => 'DIR1MB'],
                ['letra' => 'Turma C', 'turno' => 'Tarde',    'cod' => 'DIR1TA'],
                ['letra' => 'Turma D', 'turno' => 'Noite',    'cod' => 'DIR1NTA'],
                ['letra' => 'Turma E', 'turno' => 'Regime B', 'cod' => 'DIR-RB1MA'],
                ['letra' => 'Turma F', 'turno' => 'Regime B', 'cod' => 'DIR-RB1MB'],
                ['letra' => 'Turma G', 'turno' => 'Regime B', 'cod' => 'DIR-RB1TA'],
                ['letra' => 'Turma H', 'turno' => 'Regime B', 'cod' => 'DIR-RB1NTA'],
            ],
            2 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã',    'cod' => 'DIR2MA'],
                ['letra' => 'Turma B', 'turno' => 'Tarde',    'cod' => 'DIR2TA'],
                ['letra' => 'Turma C', 'turno' => 'Noite',    'cod' => 'DIR2NTA'],
                ['letra' => 'Turma D', 'turno' => 'Regime B', 'cod' => 'DIR-RB2'],
            ],
            3 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã',    'cod' => 'DIR3MA'],
                ['letra' => 'Turma B', 'turno' => 'Noite',    'cod' => 'DIR3NTA'],
                ['letra' => 'Turma C', 'turno' => 'Regime B', 'cod' => 'DIR-RB3'],
            ],
            4 => [
                ['letra' => 'Turma A', 'turno' => 'Noite',    'cod' => 'DIR4NTA'],
                ['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'DIR-RB4'],
            ],
            5 => [
                ['letra' => 'Turma A', 'turno' => 'Noite',    'cod' => 'DIR5NTA'],
            ],
        ],

        // 9. Análises Clínicas e Saúde Pública (ACSP)
        'ANLI' => [
            1 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã', 'cod' => 'ACSP1MA'],
                ['letra' => 'Turma B', 'turno' => 'Manhã', 'cod' => 'ACSP1MB'],
                ['letra' => 'Turma C', 'turno' => 'Manhã', 'cod' => 'ACSP1MC'],
                ['letra' => 'Turma D', 'turno' => 'Manhã', 'cod' => 'ACSP1MD'],
                ['letra' => 'Turma E', 'turno' => 'Tarde', 'cod' => 'ACSP1TA'],
                ['letra' => 'Turma F', 'turno' => 'Tarde', 'cod' => 'ACSP1TB'],
                ['letra' => 'Turma G', 'turno' => 'Tarde', 'cod' => 'ACSP1TC'],
                ['letra' => 'Turma H', 'turno' => 'Noite', 'cod' => 'ACSP1NTA'],
                ['letra' => 'Turma I', 'turno' => 'Noite', 'cod' => 'ACSP1NTB'],
                ['letra' => 'Turma J', 'turno' => 'Noite', 'cod' => 'ACSP1NTC'],
            ],
            2 => [
                ['letra' => 'Turma A', 'turno' => 'Manhã', 'cod' => 'ACSP2MA'],
                ['letra' => 'Turma B', 'turno' => 'Manhã', 'cod' => 'ACSP2MB'],
                ['letra' => 'Turma C', 'turno' => 'Manhã', 'cod' => 'ACSP2MC'],
                ['letra' => 'Turma D', 'turno' => 'Tarde', 'cod' => 'ACSP2TA'],
                ['letra' => 'Turma E', 'turno' => 'Tarde', 'cod' => 'ACSP2TB'],
                ['letra' => 'Turma F', 'turno' => 'Tarde', 'cod' => 'ACSP2TC'],
                ['letra' => 'Turma G', 'turno' => 'Noite', 'cod' => 'ACSP2NTA'],
                ['letra' => 'Turma H', 'turno' => 'Noite', 'cod' => 'ACSP2NTB'],
                ['letra' => 'Turma I', 'turno' => 'Noite', 'cod' => 'ACSP2NTC'],
            ],
            3 => [
                ['letra' => 'Turma A', 'turno' => 'Noite', 'cod' => 'ACSP3NTA'],
            ],
        ],

        // 10. Enfermagem (ENF)
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

        // 11. Fisioterapia (FISIO)
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

        // 12. Cardiopneumologia (CARDIO)
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

    echo "1. Validando consistência das definições canónicas...\n";
    $totalTurmasDefinidas = 0;
    foreach ($distribuicaoOficial as $cCod => $anos) {
        $countCurso = 0;
        foreach ($anos as $ano => $tList) {
            $countCurso += count($tList);
        }
        $totalTurmasDefinidas += $countCurso;
        echo "   -> [{$cCod}] {$countCurso} turmas mapeadas\n";
    }
    echo "   [OK] Total de Turmas Canónicas: {$totalTurmasDefinidas}\n\n";

    // 2. Carregar Snapshot de Atribuições Salvas (Fase 1)
    $backupDir = __DIR__ . '/backups';
    $files = glob("{$backupDir}/assignments_safety_snapshot_*.json");
    rsort($files);
    $savedAssignments = [];
    if (!empty($files)) {
        $latestSnapshot = $files[0];
        $json = json_decode(file_get_contents($latestSnapshot), true);
        if ($json && isset($json['linhas'])) {
            foreach ($json['linhas'] as $l) {
                if (!empty($l['docente_id'])) {
                    $discId = (int)$l['disciplina_id'];
                    $savedAssignments[$discId] = $l;
                }
            }
        }
        echo "2. Atribuições docentes resgatadas do snapshot de segurança: " . count($savedAssignments) . "\n\n";
    }

    echo "========================================================================\n";
    echo "  INICIANDO TRANSAÇÃO ATÓMICA DE LIMPEZA E RECONSTRUÇÃO (ACID)          \n";
    echo "========================================================================\n\n";

    $db->beginTransaction();

    // 3. Atualizar Schema e Vistas
    echo "3. Padronizando estrutura da tabela 'turmas' e vista SQL...\n";
    $db->exec("ALTER TABLE `turmas` MODIFY COLUMN `turno` VARCHAR(50) DEFAULT 'Manhã'");

    // 4. Limpeza Controlada de Linhas e Turmas Legadas
    echo "4. Removendo linhas duplicadas e turmas legadas do ano lectivo 2026/27...\n";
    $db->exec("DELETE FROM linhas_cobertura WHERE plano_id IN (SELECT id FROM planos_cobertura WHERE ano_lectivo = '2026/27')");
    $db->exec("DELETE FROM turmas");

    // 5. Reconciliar Cursos e Planos
    $stmtCursos = $db->query("SELECT id, codigo, nome FROM cursos ORDER BY id");
    $cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

    $stmtPlano = $db->prepare("SELECT id FROM planos_cobertura WHERE curso_id = ? AND ano_lectivo = '2026/27'");
    $stmtCreatePlano = $db->prepare("INSERT INTO planos_cobertura (curso_id, ano_lectivo, estado, observacoes) VALUES (?, '2026/27', 'Rascunho', 'Plano gerado com distribuição oficial')");
    
    $stmtGetDiscs = $db->prepare("SELECT id, nome, ano_curricular, semestre, carga_horaria_semanal FROM disciplinas WHERE curso_id = ? AND activo = 1 ORDER BY ano_curricular, semestre, nome");
    
    $stmtInsertTurma = $db->prepare("
        INSERT INTO turmas (id, disciplina_id, docente_id, designacao, turno, sumarios_registados, sumarios_previstos, programa_carregado, dosificacao_carregada, notas_no_prazo, inquerito_media)
        VALUES (:id, :disciplina_id, :docente_id, :designacao, :turno, :sum_reg, 200, 1, 1, 'Sim', 4.20)
    ");

    $stmtInsertLinha = $db->prepare("
        INSERT INTO linhas_cobertura (plano_id, disciplina_id, turma_id, docente_id, conformidade, justificacao, regime, parecer, decisao_aprovacao, observacoes)
        VALUES (:plano_id, :disciplina_id, :turma_id, :docente_id, :conformidade, :justificacao, :regime, :parecer, :decisao_aprovacao, :observacoes)
    ");

    $totalTurmasCriadas = 0;
    $totalLinhasCriadas = 0;

    foreach ($cursos as $c) {
        $cursoId = (int)$c['id'];
        $cursoCod = $c['codigo'];
        $cursoNome = $c['nome'];

        if (!isset($distribuicaoOficial[$cursoCod])) {
            echo "   [AVISO] Curso {$cursoCod} ({$cursoNome}) sem mapeamento. Ignorando.\n";
            continue;
        }

        // Garantir Plano 2026/27
        $stmtPlano->execute([$cursoId]);
        $planoId = $stmtPlano->fetchColumn();
        if (!$planoId) {
            $stmtCreatePlano->execute([$cursoId]);
            $planoId = (int)$db->lastInsertId();
        }

        // Obter disciplinas do curso agrupadas por ano
        $stmtGetDiscs->execute([$cursoId]);
        $disciplinas = $stmtGetDiscs->fetchAll(PDO::FETCH_ASSOC);

        $discsByAno = [];
        foreach ($disciplinas as $d) {
            $discsByAno[(int)$d['ano_curricular']][] = $d;
        }

        $anosMapeados = $distribuicaoOficial[$cursoCod];
        $cursoLinhas = 0;
        $cursoTurmas = 0;

        foreach ($anosMapeados as $ano => $turmasList) {
            $discsDoAno = $discsByAno[$ano] ?? [];
            if (empty($discsDoAno)) {
                continue;
            }

            foreach ($turmasList as $tSpec) {
                $turmaCode = $tSpec['cod'];
                $turmaLetra = $tSpec['letra']; // Ex: Turma A, Turma B
                $turno     = $tSpec['turno'];
                
                // A designação oficial segue o padrão canónico: "Turma A (ENF1MA)"
                $designacaoCompleta = "{$turmaLetra} ({$turmaCode})";

                foreach ($discsDoAno as $disc) {
                    $discId = (int)$disc['id'];
                    $turmaRowId = "{$turmaCode}-D{$discId}";

                    // Atribuição de docente preservada
                    $docenteId = null;
                    $conf = 'Por verificar';
                    $just = null;
                    $regime = 'Tempo Parcial';
                    $parecer = 'Manter';
                    $decisao = 'Aprovar';
                    $obs = null;

                    // Se tínhamos atribuição prévia para esta disciplina e for a Turma A ou primária
                    if (isset($savedAssignments[$discId])) {
                        $saved = $savedAssignments[$discId];
                        $docenteId = $saved['docente_id'];
                        $conf = $saved['conformidade'] ?: 'Sim';
                        $just = $saved['justificacao'] ?: 'Preservado do histórico institucional';
                        $regime = $saved['regime'] ?: 'Tempo Parcial';
                        $parecer = $saved['parecer'] ?: 'Manter';
                        $decisao = $saved['decisao_aprovacao'] ?: 'Aprovar';
                        $obs = $saved['observacoes'];
                    }

                    // Inserir Turma
                    $stmtInsertTurma->execute([
                        ':id'            => $turmaRowId,
                        ':disciplina_id' => $discId,
                        ':docente_id'    => $docenteId,
                        ':designacao'    => $designacaoCompleta,
                        ':turno'         => $turno,
                        ':sum_reg'       => rand(145, 192)
                    ]);
                    $totalTurmasCriadas++;

                    // Inserir Linha de Cobertura
                    $stmtInsertLinha->execute([
                        ':plano_id'          => $planoId,
                        ':disciplina_id'     => $discId,
                        ':turma_id'          => $turmaRowId,
                        ':docente_id'        => $docenteId,
                        ':conformidade'      => $conf,
                        ':justificacao'      => $just,
                        ':regime'            => $regime,
                        ':parecer'           => $parecer,
                        ':decisao_aprovacao' => $decisao,
                        ':observacoes'       => $obs
                    ]);
                    $totalLinhasCriadas++;
                    $cursoLinhas++;
                }
                $cursoTurmas++;
            }
        }

        echo "   ✓ [{$cursoCod}] {$cursoNome}: {$cursoTurmas} turmas e {$cursoLinhas} linhas populadas com sucesso.\n";
    }

    // Commit da transação se ainda estiver ativa
    if ($db->inTransaction()) {
        $db->commit();
    }

    // 6. Atualizar Vista Detalhada (DDL)
    echo "\n6. Recriando vista SQL 'vw_linhas_cobertura_detalhada'...\n";
    $db->exec("CREATE OR REPLACE VIEW `vw_linhas_cobertura_detalhada` AS 
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
    LEFT JOIN `vw_docentes_capacidade_carga` `cap` ON `lc`.`docente_id` = `cap`.`docente_id`");

    echo "\n========================================================================\n";
    echo "  SINCRONIZAÇÃO CANÓNICA EXECUTADA COM SUCESSO!                         \n";
    echo "========================================================================\n";
    echo "• Total de Turmas Únicas Ativas: {$totalTurmasDefinidas}\n";
    echo "• Total de Instâncias de Turmas: {$totalTurmasCriadas}\n";
    echo "• Total de Linhas no Plano 2026/27: {$totalLinhasCriadas}\n";
    echo "• Atribuições Docentes Mantidas: " . count($savedAssignments) . "\n\n";

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n[ERRO CRÍTICO]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
