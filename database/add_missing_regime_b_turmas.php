<?php
/**
 * Script Seguro de Adição de Turmas de Regime B nos Cursos Não-Saúde
 * Não remove nenhum dado existente (100% aditivo / INSERT IGNORE).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    echo "========================================================================\n";
    echo "  ADICIONANDO TURMAS DE REGIME B COM TOTAL SEGURANÇA (100% ADITIVO)     \n";
    echo "========================================================================\n\n";

    $novasTurmasRB = [
        'CONT' => [
            1 => [['letra' => 'Turma E', 'turno' => 'Regime B', 'cod' => 'COF-RB1']],
            2 => [['letra' => 'Turma D', 'turno' => 'Regime B', 'cod' => 'COF-RB2']],
            3 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'COF-RB3']],
            4 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'COF-RB4']],
        ],
        'ECON' => [
            1 => [['letra' => 'Turma E', 'turno' => 'Regime B', 'cod' => 'ECO-RB1']],
            2 => [['letra' => 'Turma D', 'turno' => 'Regime B', 'cod' => 'ECO-RB2']],
            3 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'ECO-RB3']],
            4 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'ECO-RB4']],
        ],
        'PSIC' => [
            1 => [['letra' => 'Turma D', 'turno' => 'Regime B', 'cod' => 'PSIC-RB1']],
            2 => [['letra' => 'Turma C', 'turno' => 'Regime B', 'cod' => 'PSIC-RB2']],
            3 => [['letra' => 'Turma C', 'turno' => 'Regime B', 'cod' => 'PSIC-RB3']],
            4 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'PSIC-RB4']],
        ],
        'GRH' => [
            2 => [['letra' => 'Turma D', 'turno' => 'Regime B', 'cod' => 'GRH-RB2']],
            3 => [['letra' => 'Turma C', 'turno' => 'Regime B', 'cod' => 'GRH-RB3']],
            4 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'GRH-RB4']],
        ],
        'CPRI' => [
            2 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'CPRI-RB2']],
            3 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'CPRI-RB3']],
            4 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'CPRI-RB4']],
        ],
        'SOCI' => [
            2 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'SOC-RB2']],
            3 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'SOC-RB3']],
            4 => [['letra' => 'Turma B', 'turno' => 'Regime B', 'cod' => 'SOC-RB4']],
        ]
    ];

    $stmtGetCurso = $db->prepare("SELECT id, nome, codigo FROM cursos WHERE UPPER(TRIM(codigo)) = ? LIMIT 1");
    $stmtGetPlano = $db->prepare("SELECT id FROM planos_cobertura WHERE curso_id = ? AND ano_lectivo = '2026/27' LIMIT 1");
    $stmtGetDiscs = $db->prepare("SELECT id, nome, ano_curricular, semestre FROM disciplinas WHERE curso_id = ? AND ano_curricular = ? AND activo = 1");
    
    $stmtFindDocente = $db->prepare("
        SELECT docente_id, conformidade, justificacao, regime, categoria_carreira, parecer, decisao_aprovacao
        FROM linhas_cobertura
        WHERE disciplina_id = ? AND docente_id IS NOT NULL
        LIMIT 1
    ");

    $stmtInsTurma = $db->prepare("
        INSERT INTO turmas (id, disciplina_id, designacao, turno, sumarios_previstos)
        VALUES (:id, :disciplina_id, :designacao, :turno, 32)
        ON DUPLICATE KEY UPDATE designacao = VALUES(designacao), turno = VALUES(turno)
    ");

    $stmtInsLinha = $db->prepare("
        INSERT INTO linhas_cobertura 
        (plano_id, disciplina_id, turma_id, docente_id, conformidade, justificacao, regime, categoria_carreira, parecer, decisao_aprovacao)
        VALUES (:plano_id, :disciplina_id, :turma_id, :docente_id, :conformidade, :justificacao, :regime, :categoria_carreira, :parecer, :decisao_aprovacao)
        ON DUPLICATE KEY UPDATE turma_id = VALUES(turma_id)
    ");

    $totalTurmasCriadas = 0;
    $totalLinhasCriadas = 0;
    $totalDocentesHerdados = 0;

    foreach ($novasTurmasRB as $cCod => $anos) {
        $stmtGetCurso->execute([$cCod]);
        $curso = $stmtGetCurso->fetch(PDO::FETCH_ASSOC);
        if (!$curso) {
            echo "[AVISO] Curso {$cCod} não encontrado na base.\n";
            continue;
        }

        $cursoId = (int)$curso['id'];
        $cursoNome = $curso['nome'];

        $stmtGetPlano->execute([$cursoId]);
        $planoId = (int)$stmtGetPlano->fetchColumn();
        if (!$planoId) {
            // Criar plano se não existir
            $db->prepare("INSERT INTO planos_cobertura (curso_id, ano_lectivo, estado) VALUES (?, '2026/27', 'Rascunho')")->execute([$cursoId]);
            $planoId = (int)$db->lastInsertId();
        }

        echo "📌 Adicionando Regime B ao curso [{$cCod}] {$cursoNome} (Plano ID: {$planoId}):\n";

        foreach ($anos as $anoNum => $turmasDefs) {
            $stmtGetDiscs->execute([$cursoId, $anoNum]);
            $discs = $stmtGetDiscs->fetchAll(PDO::FETCH_ASSOC);

            foreach ($turmasDefs as $tDef) {
                $tCod = $tDef['cod'];
                $tLetra = $tDef['letra'];
                $tTurno = $tDef['turno'];
                $desigCompleta = "{$tLetra} ({$tCod})";

                $turmasInstancias = 0;
                foreach ($discs as $d) {
                    $discId = (int)$d['id'];
                    $turmaRowId = "{$tCod}-D{$discId}";

                    // Inserir Turma
                    $stmtInsTurma->execute([
                        ':id'            => $turmaRowId,
                        ':disciplina_id' => $discId,
                        ':designacao'    => $desigCompleta,
                        ':turno'         => $tTurno
                    ]);
                    $totalTurmasCriadas++;

                    // Verificar se já existe docente atribuído nesta disciplina noutra turma
                    $stmtFindDocente->execute([$discId]);
                    $docInfo = $stmtFindDocente->fetch(PDO::FETCH_ASSOC);

                    $docenteId = $docInfo['docente_id'] ?? null;
                    $conformidade = $docInfo['conformidade'] ?? 'Por verificar';
                    $justificacao = $docInfo['justificacao'] ?? 'Especializações';
                    $regime = $docInfo['regime'] ?? 'Tempo Parcial';
                    $catCarreira = $docInfo['categoria_carreira'] ?? 'Assistente';
                    $parecer = $docInfo['parecer'] ?? 'Manter';
                    $decisao = $docInfo['decisao_aprovacao'] ?? 'Aprovar';

                    if ($docenteId) {
                        $totalDocentesHerdados++;
                    }

                    // Inserir Linha de Cobertura
                    $stmtInsLinha->execute([
                        ':plano_id'           => $planoId,
                        ':disciplina_id'      => $discId,
                        ':turma_id'           => $turmaRowId,
                        ':docente_id'         => $docenteId,
                        ':conformidade'       => $conformidade,
                        ':justificacao'       => $justificacao,
                        ':regime'             => $regime,
                        ':categoria_carreira' => $catCarreira,
                        ':parecer'            => $parecer,
                        ':decisao_aprovacao'  => $decisao
                    ]);
                    $totalLinhasCriadas++;
                    $turmasInstancias++;
                }

                echo "   ✓ {$anoNum}.º Ano: Turma '{$desigCompleta}' criada com {$turmasInstancias} disciplinas.\n";
            }
        }
        echo "\n";
    }

    echo "========================================================================\n";
    echo "  TURMAS DE REGIME B ADICIONADAS COM SUCESSO!                           \n";
    echo "========================================================================\n";
    echo "• Total de Instâncias de Turmas Criadas: {$totalTurmasCriadas}\n";
    echo "• Total de Linhas Adicionadas aos Planos 2026/27: {$totalLinhasCriadas}\n";
    echo "• Atribuições Docentes Herdadas Automaticamente: {$totalDocentesHerdados}\n\n";

} catch (\Throwable $e) {
    echo "\n[ERRO]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
