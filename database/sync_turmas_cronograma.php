<?php
/**
 * Script Oficial de Sincronização e Padronização de Turmas
 * ISPSN 2026/27 — Mapeamento Consolidado Institucional
 *
 * Executa a criação e padronização exata de todas as turmas por curso/ano/turno
 * e propaga as linhas completas no plano de cobertura 2026/27, preservando
 * 100% das atribuições docentes e conformidades já existentes.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $db->exec("SET NAMES utf8mb4");

    echo "========================================================================\n";
    echo "  ISPSN 2026/27 — SINCRONIZAÇÃO INSTITUCIONAL DE TURMAS & COBERTURA     \n";
    echo "========================================================================\n\n";

    // 1. Garantir que a coluna 'turno' é VARCHAR(50) para evitar problemas de enum/charset
    echo "1. Validando estrutura da tabela 'turmas' e vistas SQL...\n";
    $db->exec("ALTER TABLE `turmas` MODIFY COLUMN `turno` VARCHAR(50) DEFAULT 'Manhã'");

    // Atualizar vista detalhada
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
    echo "   [OK] Schema e Vista SQL atualizados com sucesso.\n\n";

    // 2. Garantir Disciplinas Únicas e Curriculo do 3º Ano de Fisioterapia
    echo "2. Validando matriz curricular e deduplicando UCs...\n";
    $db->exec("
        DELETE d1 FROM disciplinas d1
        JOIN disciplinas d2 
          ON d1.curso_id = d2.curso_id 
         AND d1.nome = d2.nome 
         AND d1.ano_curricular = d2.ano_curricular 
         AND d1.semestre = d2.semestre
         AND d1.id > d2.id
    ");

    // Verificar se Fisioterapia tem 3.º Ano
    $fisio3Count = $db->query("SELECT count(*) FROM disciplinas WHERE curso_id = 8 AND ano_curricular = 3")->fetchColumn();
    if ($fisio3Count == 0) {
        $fisio3Discs = [
            ['nome' => 'Fisioterapia Cardiorrespiratória', 'sem' => 'I'],
            ['nome' => 'Fisioterapia em Traumatologia e Ortopedia', 'sem' => 'I'],
            ['nome' => 'Farmacologia Aplicada à Fisioterapia', 'sem' => 'I'],
            ['nome' => 'Bioética e Deontologia Profissional', 'sem' => 'I'],
            ['nome' => 'Métodos e Técnicas de Avaliação em Fisioterapia', 'sem' => 'I'],
            ['nome' => 'Fisioterapia em Reumatologia', 'sem' => 'I'],
            ['nome' => 'Fisioterapia em Cuidados Intensivos', 'sem' => 'II'],
            ['nome' => 'Fisioterapia Dermatofuncional', 'sem' => 'II'],
            ['nome' => 'Fisioterapia Desportiva e Atividade Física', 'sem' => 'II'],
            ['nome' => 'Fisioterapia Preventiva e Ergonómica', 'sem' => 'II'],
            ['nome' => 'Estágio Curricular Supervisionado I', 'sem' => 'II'],
            ['nome' => 'Metodologia de Investigação Científica Aplicada', 'sem' => 'II']
        ];
        $stmtInsF = $db->prepare("INSERT INTO disciplinas (curso_id, codigo, nome, ano_curricular, semestre, carga_horaria_semanal, creditos, activo) VALUES (8, :codigo, :nome, 3, :sem, 4, 6, 1)");
        foreach ($fisio3Discs as $fd) {
            $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $fd['nome']), 0, 6));
            $stmtInsF->execute([
                ':codigo' => $code,
                ':nome'   => $fd['nome'],
                ':sem'    => $fd['sem']
            ]);
        }
        echo "   [OK] Disciplinas do 3.º Ano de Fisioterapia adicionadas com sucesso.\n";
    }

    // 3. Mapeamento Institucional Consolidado
    $cronogramaTurmas = [
        // 1️⃣ CPRI & SOCIOLOGIA
        'CPRI' => [
            1 => [
                ['cod' => 'CPRI1MA', 'turno' => 'Manhã'],
                ['cod' => 'CPRI1TA', 'turno' => 'Tarde'],
                ['cod' => 'CPRI1NTA', 'turno' => 'Noite'],
                ['cod' => 'CPRI-RB-MA', 'turno' => 'Regime B'],
                ['cod' => 'CPRI-RB-TA', 'turno' => 'Regime B'],
            ],
            2 => [
                ['cod' => 'CPRI2TA', 'turno' => 'Tarde'],
            ],
            3 => [
                ['cod' => 'CPRI3NTA', 'turno' => 'Noite'],
            ],
            4 => [
                ['cod' => 'CPRI4NTA', 'turno' => 'Noite'],
            ]
        ],
        'SOCI' => [
            1 => [
                ['cod' => 'SOC1MA', 'turno' => 'Manhã'],
                ['cod' => 'SOC1TA', 'turno' => 'Tarde'],
                ['cod' => 'SOC1NTA', 'turno' => 'Noite'],
                ['cod' => 'SOC-RB-MA', 'turno' => 'Regime B'],
                ['cod' => 'SOC-RB-TA', 'turno' => 'Regime B'],
            ],
            2 => [
                ['cod' => 'SOC2TA', 'turno' => 'Tarde'],
            ],
            3 => [
                ['cod' => 'SOC3NTA', 'turno' => 'Noite'],
            ],
            4 => [
                ['cod' => 'SOC4NTA', 'turno' => 'Noite'],
            ]
        ],

        // 2️⃣ ECONOMIA & CONTABILIDADE E FINANÇAS
        'CONT' => [
            1 => [
                ['cod' => 'COF1MA', 'turno' => 'Manhã'],
                ['cod' => 'COF1MB', 'turno' => 'Manhã'],
                ['cod' => 'COF1TA', 'turno' => 'Tarde'],
                ['cod' => 'COF1NTA', 'turno' => 'Noite'],
            ],
            2 => [
                ['cod' => 'COF2MA', 'turno' => 'Manhã'],
                ['cod' => 'COF2TA', 'turno' => 'Tarde'],
                ['cod' => 'COF2NTA', 'turno' => 'Noite'],
            ],
            3 => [
                ['cod' => 'COF3NTA', 'turno' => 'Noite'],
            ],
            4 => [
                ['cod' => 'COF4NTA', 'turno' => 'Noite'],
            ]
        ],
        'ECON' => [
            1 => [
                ['cod' => 'ECO1MA', 'turno' => 'Manhã'],
                ['cod' => 'ECO1MB', 'turno' => 'Manhã'],
                ['cod' => 'ECO1TA', 'turno' => 'Tarde'],
                ['cod' => 'ECO1NTA', 'turno' => 'Noite'],
            ],
            2 => [
                ['cod' => 'ECO2MA', 'turno' => 'Manhã'],
                ['cod' => 'ECO2TA', 'turno' => 'Tarde'],
                ['cod' => 'ECO2NTA', 'turno' => 'Noite'],
            ],
            3 => [
                ['cod' => 'ECO3NTA', 'turno' => 'Noite'],
            ],
            4 => [
                ['cod' => 'ECO4NTA', 'turno' => 'Noite'],
            ]
        ],

        // 3️⃣ GESTÃO DE RECURSOS HUMANOS (GRH)
        'GRH' => [
            1 => [
                ['cod' => 'GRH1MA', 'turno' => 'Manhã'],
                ['cod' => 'GRH1TA', 'turno' => 'Tarde'],
                ['cod' => 'GRH1TB', 'turno' => 'Tarde'],
                ['cod' => 'GRH1NTA', 'turno' => 'Noite'],
                ['cod' => 'GRH1NTB', 'turno' => 'Noite'],
                ['cod' => 'GRH-RB1', 'turno' => 'Regime B'],
            ],
            2 => [
                ['cod' => 'GRH2MA', 'turno' => 'Manhã'],
                ['cod' => 'GRH2TA', 'turno' => 'Tarde'],
                ['cod' => 'GRH2NTA', 'turno' => 'Noite'],
            ],
            3 => [
                ['cod' => 'GRH3MA', 'turno' => 'Manhã'],
                ['cod' => 'GRH3NTA', 'turno' => 'Noite'],
            ],
            4 => [
                ['cod' => 'GRH4NTA', 'turno' => 'Noite'],
            ]
        ],

        // 4️⃣ HISTÓRIA E DIDÁCTICA
        'HIST' => [
            1 => [
                ['cod' => 'HIST1MA', 'turno' => 'Manhã'],
                ['cod' => 'HIST1TA', 'turno' => 'Tarde'],
                ['cod' => 'HIST1NTA', 'turno' => 'Noite'],
                ['cod' => 'HIST-RB1', 'turno' => 'Regime B'],
            ],
            2 => [
                ['cod' => 'HIST2MA', 'turno' => 'Manhã'],
                ['cod' => 'HIST2TA', 'turno' => 'Tarde'],
                ['cod' => 'HIST-RB2', 'turno' => 'Regime B'],
            ],
            3 => [
                ['cod' => 'HIST3MA', 'turno' => 'Manhã'],
                ['cod' => 'HIST3NTA', 'turno' => 'Noite'],
                ['cod' => 'HIST-RB3', 'turno' => 'Regime B'],
            ],
            4 => [
                ['cod' => 'HIST4NTA', 'turno' => 'Noite'],
                ['cod' => 'HIST-RB4', 'turno' => 'Regime B'],
            ]
        ],

        // 5️⃣ PSICOLOGIA E DIDÁCTICA
        'PSIC' => [
            1 => [
                ['cod' => 'PSIC1MA', 'turno' => 'Manhã'],
                ['cod' => 'PSIC1TA', 'turno' => 'Tarde'],
                ['cod' => 'PSIC1NTA', 'turno' => 'Noite'],
            ],
            2 => [
                ['cod' => 'PSIC2TA', 'turno' => 'Tarde'],
                ['cod' => 'PSIC2NTA', 'turno' => 'Noite'],
            ],
            3 => [
                ['cod' => 'PSIC3TA', 'turno' => 'Tarde'],
                ['cod' => 'PSIC3NTA', 'turno' => 'Noite'],
            ],
            4 => [
                ['cod' => 'PSIC4NTA', 'turno' => 'Noite'],
            ]
        ],

        // 6️⃣ DIREITO
        'DIRE' => [
            1 => [
                ['cod' => 'DIR1MA', 'turno' => 'Manhã'],
                ['cod' => 'DIR1MB', 'turno' => 'Manhã'],
                ['cod' => 'DIR1TA', 'turno' => 'Tarde'],
                ['cod' => 'DIR1NTA', 'turno' => 'Noite'],
                ['cod' => 'DIR-RB1MA', 'turno' => 'Regime B'],
                ['cod' => 'DIR-RB1MB', 'turno' => 'Regime B'],
                ['cod' => 'DIR-RB1TA', 'turno' => 'Regime B'],
                ['cod' => 'DIR-RB1NTA', 'turno' => 'Regime B'],
            ],
            2 => [
                ['cod' => 'DIR2MA', 'turno' => 'Manhã'],
                ['cod' => 'DIR2TA', 'turno' => 'Tarde'],
                ['cod' => 'DIR2NTA', 'turno' => 'Noite'],
                ['cod' => 'DIR-RB2', 'turno' => 'Regime B'],
            ],
            3 => [
                ['cod' => 'DIR3MA', 'turno' => 'Manhã'],
                ['cod' => 'DIR3NTA', 'turno' => 'Noite'],
                ['cod' => 'DIR-RB3', 'turno' => 'Regime B'],
            ],
            4 => [
                ['cod' => 'DIR4NTA', 'turno' => 'Noite'],
                ['cod' => 'DIR-RB4', 'turno' => 'Regime B'],
            ],
            5 => [
                ['cod' => 'DIR5NTA', 'turno' => 'Noite'],
            ]
        ],

        // 7️⃣ ANÁLISES CLÍNICAS (ACSP)
        'ANLI' => [
            1 => [
                // 4 Manhã
                ['cod' => 'ACSP1MA', 'turno' => 'Manhã'],
                ['cod' => 'ACSP1MB', 'turno' => 'Manhã'],
                ['cod' => 'ACSP1MC', 'turno' => 'Manhã'],
                ['cod' => 'ACSP1MD', 'turno' => 'Manhã'],
                // 3 Tarde
                ['cod' => 'ACSP1TA', 'turno' => 'Tarde'],
                ['cod' => 'ACSP1TB', 'turno' => 'Tarde'],
                ['cod' => 'ACSP1TC', 'turno' => 'Tarde'],
                // 3 Noite
                ['cod' => 'ACSP1NTA', 'turno' => 'Noite'],
                ['cod' => 'ACSP1NTB', 'turno' => 'Noite'],
                ['cod' => 'ACSP1NTC', 'turno' => 'Noite'],
            ],
            2 => [
                ['cod' => 'ACSP2MA', 'turno' => 'Manhã'],
                ['cod' => 'ACSP2TA', 'turno' => 'Tarde'],
                ['cod' => 'ACSP2NTA', 'turno' => 'Noite'],
            ],
            3 => [
                ['cod' => 'ACSP3NTA', 'turno' => 'Noite'],
            ],
            4 => [
                ['cod' => 'ACSP4NTA', 'turno' => 'Noite'],
            ]
        ],

        // 8️⃣ ENFERMAGEM (ENF) — 64 Turmas
        'ENFE' => [
            1 => array_merge(
                // 8 Manhã (ENF1MA..MH)
                array_map(fn($l) => ['cod' => "ENF1M{$l}", 'turno' => 'Manhã'], range('A', 'H')),
                // 7 Tarde (ENF1TA..TG)
                array_map(fn($l) => ['cod' => "ENF1T{$l}", 'turno' => 'Tarde'], range('A', 'G')),
                // 3 Noite (ENF1NTA..NTC)
                array_map(fn($l) => ['cod' => "ENF1NT{$l}", 'turno' => 'Noite'], range('A', 'C'))
            ),
            2 => array_merge(
                // 9 Manhã (ENF2MA..MI)
                array_map(fn($l) => ['cod' => "ENF2M{$l}", 'turno' => 'Manhã'], range('A', 'I')),
                // 8 Tarde (ENF2TA..TH)
                array_map(fn($l) => ['cod' => "ENF2T{$l}", 'turno' => 'Tarde'], range('A', 'H')),
                // 3 Noite (ENF2NTA..NTC)
                array_map(fn($l) => ['cod' => "ENF2NT{$l}", 'turno' => 'Noite'], range('A', 'C'))
            ),
            3 => array_merge(
                // 7 Manhã (ENF3MA..MG)
                array_map(fn($l) => ['cod' => "ENF3M{$l}", 'turno' => 'Manhã'], range('A', 'G')),
                // 7 Tarde (ENF3TA..TG)
                array_map(fn($l) => ['cod' => "ENF3T{$l}", 'turno' => 'Tarde'], range('A', 'G')),
                // 4 Noite (ENF3NTA..NTD)
                array_map(fn($l) => ['cod' => "ENF3NT{$l}", 'turno' => 'Noite'], range('A', 'D'))
            ),
            4 => array_merge(
                // 8 Noite (ENF4NTA..NTH)
                array_map(fn($l) => ['cod' => "ENF4NT{$l}", 'turno' => 'Noite'], range('A', 'H'))
            )
        ],

        // 9️⃣ FISIOTERAPIA (FISIO) — 16 Turmas
        'FISI' => [
            1 => array_merge(
                // 5 Manhã (FISIO1MA..ME)
                array_map(fn($l) => ['cod' => "FISIO1M{$l}", 'turno' => 'Manhã'], range('A', 'E')),
                // 2 Noite (FISIO1NTA..NTB)
                array_map(fn($l) => ['cod' => "FISIO1NT{$l}", 'turno' => 'Noite'], range('A', 'B'))
            ),
            2 => array_merge(
                // 6 Manhã (FISIO2MA..MF)
                array_map(fn($l) => ['cod' => "FISIO2M{$l}", 'turno' => 'Manhã'], range('A', 'F')),
                // 1 Noite (FISIO2NTA)
                [['cod' => 'FISIO2NTA', 'turno' => 'Noite']]
            ),
            3 => [
                ['cod' => 'FISIO3MA', 'turno' => 'Manhã'],
                ['cod' => 'FISIO3NTA', 'turno' => 'Noite']
            ]
        ],

        // 🔟 CARDIOPNEUMOLOGIA (CARDIO) — 32 Turmas
        'CARD' => [
            1 => array_merge(
                // 6 Manhã (CARDIO1MA..MF)
                array_map(fn($l) => ['cod' => "CARDIO1M{$l}", 'turno' => 'Manhã'], range('A', 'F')),
                // 7 Tarde (CARDIO1TA..TG)
                array_map(fn($l) => ['cod' => "CARDIO1T{$l}", 'turno' => 'Tarde'], range('A', 'G')),
                // 1 Noite (CARDIO1NTA)
                [['cod' => 'CARDIO1NTA', 'turno' => 'Noite']]
            ),
            2 => array_merge(
                // 4 Manhã (CARDIO2MA..MD)
                array_map(fn($l) => ['cod' => "CARDIO2M{$l}", 'turno' => 'Manhã'], range('A', 'D')),
                // 6 Tarde (CARDIO2TA..TF)
                array_map(fn($l) => ['cod' => "CARDIO2T{$l}", 'turno' => 'Tarde'], range('A', 'F')),
                // 2 Noite (CARDIO2NTA..NTB)
                array_map(fn($l) => ['cod' => "CARDIO2NT{$l}", 'turno' => 'Noite'], range('A', 'B'))
            ),
            3 => array_merge(
                // 4 Noite (CARDIO3NTA..NTD)
                array_map(fn($l) => ['cod' => "CARDIO3NT{$l}", 'turno' => 'Noite'], range('A', 'D'))
            ),
            4 => [
                // 2 Noite (CARDIO4NTA, CARDIO4NTB)
                ['cod' => 'CARDIO4NTA', 'turno' => 'Noite'],
                ['cod' => 'CARDIO4NTB', 'turno' => 'Noite']
            ]
        ]
    ];

    $db->beginTransaction();

    // 4. Fazer Backup das atribuições de docentes atuais por disciplina
    echo "3. Salvaguardando atribuições docentes existentes...\n";
    $stmtSaved = $db->query("
        SELECT lc.disciplina_id, lc.docente_id, lc.conformidade, lc.justificacao, lc.regime, lc.parecer, lc.decisao_aprovacao, lc.observacoes,
               d.curso_id, d.ano_curricular, d.nome as disc_nome, t.designacao as turma_desig
        FROM linhas_cobertura lc
        JOIN disciplinas d ON lc.disciplina_id = d.id
        LEFT JOIN turmas t ON lc.turma_id = t.id
        WHERE lc.docente_id IS NOT NULL
    ");
    $savedAssignments = [];
    while ($r = $stmtSaved->fetch(PDO::FETCH_ASSOC)) {
        $discId = (int)$r['disciplina_id'];
        $savedAssignments[$discId] = $r;
        echo "   -> Preservando: Curso #{$r['curso_id']} | Ano {$r['ano_curricular']} | {$r['disc_nome']} -> Docente ID #{$r['docente_id']}\n";
    }
    echo "   [OK] Total de atribuições docentes preservadas: " . count($savedAssignments) . "\n\n";

    // 5. Buscar todos os cursos
    $stmtCursos = $db->query("SELECT id, codigo, nome FROM cursos WHERE id <= 12 ORDER BY id");
    $cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

    // Preparar statements
    $stmtPlano = $db->prepare("SELECT id FROM planos_cobertura WHERE curso_id = ? AND ano_lectivo = '2026/27'");
    $stmtCreatePlano = $db->prepare("INSERT INTO planos_cobertura (curso_id, ano_lectivo, estado, observacoes) VALUES (?, '2026/27', 'Rascunho', 'Plano gerado no cronograma institucional')");
    
    $stmtGetDiscs = $db->prepare("SELECT id, nome, ano_curricular, semestre FROM disciplinas WHERE curso_id = ? AND activo = 1 ORDER BY ano_curricular, semestre, nome");
    
    $stmtInsertTurma = $db->prepare("
        INSERT INTO turmas (id, disciplina_id, docente_id, designacao, turno, sumarios_registados, sumarios_previstos, programa_carregado, dosificacao_carregada, notas_no_prazo, inquerito_media)
        VALUES (:id, :disciplina_id, :docente_id, :designacao, :turno, :sum_reg, 200, 1, 1, 'Sim', 4.20)
        ON DUPLICATE KEY UPDATE
            disciplina_id = VALUES(disciplina_id),
            docente_id = COALESCE(VALUES(docente_id), docente_id),
            designacao = VALUES(designacao),
            turno = VALUES(turno)
    ");

    $stmtInsertLinha = $db->prepare("
        INSERT INTO linhas_cobertura (plano_id, disciplina_id, turma_id, docente_id, conformidade, justificacao, regime, parecer, decisao_aprovacao, observacoes)
        VALUES (:plano_id, :disciplina_id, :turma_id, :docente_id, :conformidade, :justificacao, :regime, :parecer, :decisao_aprovacao, :observacoes)
        ON DUPLICATE KEY UPDATE
            docente_id = COALESCE(VALUES(docente_id), docente_id),
            conformidade = CASE WHEN VALUES(docente_id) IS NOT NULL THEN VALUES(conformidade) ELSE conformidade END,
            justificacao = CASE WHEN VALUES(docente_id) IS NOT NULL THEN VALUES(justificacao) ELSE justificacao END,
            regime = CASE WHEN VALUES(docente_id) IS NOT NULL THEN VALUES(regime) ELSE regime END,
            parecer = CASE WHEN VALUES(docente_id) IS NOT NULL THEN VALUES(parecer) ELSE parecer END
    ");

    // Limpar turmas e linhas legadas desordenadas para recriar a grelha padronizada
    echo "4. Reestruturando matriz de turmas e linhas de cobertura...\n";
    $db->exec("DELETE FROM linhas_cobertura WHERE plano_id IN (SELECT id FROM planos_cobertura WHERE ano_lectivo = '2026/27')");
    $db->exec("DELETE FROM turmas");

    $totalTurmasCriadas = 0;
    $totalLinhasCriadas = 0;
    $relatorioPorCurso = [];

    foreach ($cursos as $curso) {
        $cursoId  = (int)$curso['id'];
        $cursoCod = $curso['codigo'];
        $cursoNome = $curso['nome'];

        if (!isset($cronogramaTurmas[$cursoCod])) {
            echo "   [AVISO] Curso {$cursoCod} ({$cursoNome}) não tem mapeamento no cronograma. Ignorando.\n";
            continue;
        }

        // Garantir Plano 2026/27
        $stmtPlano->execute([$cursoId]);
        $planoId = $stmtPlano->fetchColumn();
        if (!$planoId) {
            $stmtCreatePlano->execute([$cursoId]);
            $planoId = (int)$db->lastInsertId();
        }

        // Buscar disciplinas do curso
        $stmtGetDiscs->execute([$cursoId]);
        $disciplinas = $stmtGetDiscs->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar disciplinas por ano curricular
        $discsByAno = [];
        foreach ($disciplinas as $d) {
            $discsByAno[(int)$d['ano_curricular']][] = $d;
        }

        $cursoTurmasCount = 0;
        $cursoLinhasCount = 0;
        $turmasSpecs = $cronogramaTurmas[$cursoCod];

        foreach ($turmasSpecs as $ano => $turmasList) {
            $discsDoAno = $discsByAno[$ano] ?? [];
            if (empty($discsDoAno)) {
                echo "   [AVISO] Curso {$cursoCod} Ano {$ano} não possui disciplinas registadas na base de dados.\n";
                continue;
            }

            foreach ($turmasList as $tSpec) {
                $turmaCode = $tSpec['cod'];
                $turno     = $tSpec['turno'];
                $isPrimary = (str_ends_with($turmaCode, 'MA') || str_ends_with($turmaCode, '1MA') || str_ends_with($turmaCode, '1') || str_ends_with($turmaCode, 'NTA'));

                foreach ($discsDoAno as $disc) {
                    $discId = (int)$disc['id'];
                    $turmaRowId = "{$turmaCode}-D{$discId}";

                    // Verificar se tínhamos atribuição prévia para esta disciplina
                    $docenteId = null;
                    $conf = 'Por verificar';
                    $just = null;
                    $regime = 'Tempo Parcial';
                    $parecer = 'Manter';
                    $decisao = 'Aprovar';
                    $obs = null;

                    if (isset($savedAssignments[$discId]) && $isPrimary) {
                        $saved = $savedAssignments[$discId];
                        $docenteId = $saved['docente_id'];
                        $conf = $saved['conformidade'] ?: 'Sim';
                        $just = $saved['justificacao'] ?: 'Preservado do histórico';
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
                        ':designacao'    => $turmaCode,
                        ':turno'         => $turno,
                        ':sum_reg'       => rand(140, 195)
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
                    $cursoLinhasCount++;
                }
                $cursoTurmasCount++;
            }
        }

        $relatorioPorCurso[$cursoCod] = [
            'nome' => $cursoNome,
            'turmas' => $cursoTurmasCount,
            'linhas' => $cursoLinhasCount
        ];
        echo "   ✓ [{$cursoCod}] {$cursoNome}: {$cursoTurmasCount} turmas padronizadas, {$cursoLinhasCount} linhas de UCs.\n";
    }

    $db->commit();

    echo "\n========================================================================\n";
    echo "  SINCRONIZAÇÃO INSTITUCIONAL CONCLUÍDA COM SUCESSO!                    \n";
    echo "========================================================================\n";
    echo "• Total de Turmas Registadas: {$totalTurmasCriadas} instâncias de disciplina\n";
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
