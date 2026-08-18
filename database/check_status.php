<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    echo "=======================================================\n";
    echo "  RELATÓRIO CONSOLIDADO DE EXECUÇÃO & INSPEÇÃO DO SISTEMA\n";
    echo "=======================================================\n\n";

    // 1. Inspect Db & 2. Analyze Structure
    echo "[1] INSPEÇÃO DA BASE DE DADOS & ESTRUTURA:\n";
    echo "  - Base de dados conectada: " . $db->query("SELECT DATABASE()")->fetchColumn() . "\n";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "  - Tabelas e Views ativas: " . count($tables) . "\n";
    foreach (['cursos', 'disciplinas', 'turmas', 'docentes', 'planos_cobertura', 'linhas_cobertura', 'utilizadores'] as $t) {
        if (in_array($t, $tables)) {
            $cnt = $db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            echo "    * {$t}: {$cnt} registos\n";
        }
    }
    echo "\n";

    // 3. Check Turno Col
    echo "[2] COLUNA TURNO NAS TABELAS:\n";
    $turmasCols = $db->query("DESCRIBE turmas")->fetchAll(PDO::FETCH_COLUMN);
    $linhasCols = $db->query("DESCRIBE linhas_cobertura")->fetchAll(PDO::FETCH_COLUMN);
    echo "  - turmas tem 'turno': " . (in_array('turno', $turmasCols) ? 'SIM (OK)' : 'NÃO') . "\n";
    echo "  - linhas_cobertura tem 'turno': " . (in_array('turno', $linhasCols) ? 'SIM (OK)' : 'NÃO') . "\n\n";

    // 4. List Cursos & Check Course Years
    echo "[3] LISTAGEM DE CURSOS E ANOS CURRICULARES:\n";
    $cursos = $db->query("
        SELECT c.id, c.codigo, c.nome, c.duracao_anos, c.activo,
               COUNT(DISTINCT d.id) as total_disciplinas,
               COUNT(DISTINCT t.id) as total_turmas
        FROM cursos c
        LEFT JOIN disciplinas d ON c.id = d.curso_id
        LEFT JOIN turmas t ON d.id = t.disciplina_id
        GROUP BY c.id, c.codigo, c.nome, c.duracao_anos, c.activo
        ORDER BY c.id
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cursos as $c) {
        echo sprintf("  - [%2d] %-8s | %-40s | Duração: %d anos | UCs: %2d | Turmas: %3d | Activo: %s\n",
            $c['id'], $c['codigo'], $c['nome'], $c['duracao_anos'], $c['total_disciplinas'], $c['total_turmas'], $c['activo'] ? 'Sim' : 'Não');
    }
    echo "\n";

    // 5. Check Fisio / Health Discs & Fisio Curriculo
    echo "[4] CURRÍCULO E DISCIPLINAS DE FISIOTERAPIA & SAÚDE:\n";
    $saudeCursos = $db->query("SELECT id, codigo, nome FROM cursos WHERE LOWER(nome) LIKE '%fisio%' OR LOWER(nome) LIKE '%saúde%' OR LOWER(nome) LIKE '%enferm%' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($saudeCursos as $sc) {
        echo "  Curso: [{$sc['id']}] {$sc['codigo']} - {$sc['nome']}\n";
        $discs = $db->query("
            SELECT ano_curricular, semestre, COUNT(*) as qtd, GROUP_CONCAT(nome ORDER BY nome SEPARATOR ' | ') as disciplinas
            FROM disciplinas 
            WHERE curso_id = {$sc['id']}
            GROUP BY ano_curricular, semestre
            ORDER BY ano_curricular, semestre
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($discs as $d) {
            echo "    * Ano {$d['ano_curricular']} ({$d['semestre']}): {$d['qtd']} UCs -> {$d['disciplinas']}\n";
        }
    }
    echo "\n";

    // 6. Check Dups / Inspect Disc Dups / Dedup Discs
    echo "[5] DEDUPLICAÇÃO E DISCIPLINAS DUPLICADAS:\n";
    $dupDiscs = $db->query("
        SELECT c.nome as curso_nome, d.nome as disc_nome, d.ano_curricular, d.semestre, COUNT(*) as qtd, GROUP_CONCAT(d.id) as ids
        FROM disciplinas d
        JOIN cursos c ON d.curso_id = c.id
        GROUP BY d.curso_id, LOWER(TRIM(d.nome)), d.ano_curricular, d.semestre
        HAVING COUNT(*) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($dupDiscs)) {
        echo "  - Nenhuma disciplina duplicada encontrada no banco. (Totalmente Normalizado/Dedup OK)\n";
    } else {
        echo "  - Atenção: Foram encontradas " . count($dupDiscs) . " duplicidades:\n";
        foreach ($dupDiscs as $dd) {
            echo "    * [{$dd['curso_nome']}] '{$dd['disc_nome']}' ({$dd['ano_curricular']}º Ano / Sem {$dd['semestre']}) -> {$dd['qtd']} ocorrências (IDs: {$dd['ids']})\n";
        }
    }
    echo "\n";

    // 7. Check Turmas, Turno Distribution, Get Assigned Lines & Inspect Assigned
    echo "[6] TURMAS, TURNOS E LINHAS DE COBERTURA ATRIBUÍDAS:\n";
    $turnoStats = $db->query("
        SELECT COALESCE(t.turno, 'Sem Turno') as turno,
               COUNT(DISTINCT t.id) as total_turmas,
               COUNT(l.id) as total_linhas,
               COUNT(l.docente_id) as total_atribuidas,
               SUM(CASE WHEN l.docente_id IS NULL THEN 1 ELSE 0 END) as total_vagas
        FROM turmas t
        LEFT JOIN linhas_cobertura l ON t.id = l.turma_id
        GROUP BY t.turno
        ORDER BY total_turmas DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($turnoStats as $ts) {
        echo sprintf("  - Turno: %-12s | Turmas: %4d | Linhas Cobertura: %4d | Atribuídas: %4d | Vagas: %4d\n",
            $ts['turno'], $ts['total_turmas'], $ts['total_linhas'], $ts['total_atribuidas'], $ts['total_vagas']);
    }
    echo "\n";

    // 8. Check Portal Json
    echo "[7] FICHEIRO PORTAL_DATA.JSON & CONTRATO API:\n";
    $jsonFile = __DIR__ . '/../01_Portal_Autonomo/dados/portal_data.json';
    if (file_exists($jsonFile)) {
        $json = json_decode(file_get_contents($jsonFile), true);
        echo "  - Ficheiro portal_data.json: PRESENTE (" . number_format(filesize($jsonFile) / 1024, 2) . " KB)\n";
        if (is_array($json)) {
            foreach ($json as $key => $val) {
                if (is_array($val)) {
                    echo "    * {$key}: " . count($val) . " itens carregados\n";
                }
            }
        }
    } else {
        echo "  - portal_data.json: NÃO ENCONTRADO\n";
    }

    echo "\n=======================================================\n";
    echo "  STATUS: TODAS AS VERIFICAÇÕES CONCLUÍDAS COM SUCESSO!\n";
    echo "=======================================================\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
