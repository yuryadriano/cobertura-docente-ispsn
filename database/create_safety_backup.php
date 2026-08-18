<?php
/**
 * ISPSN 2026/27 — Fase 1: Backup Integral de Segurança e Snapshot de Atribuições
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$timestamp = date('Ymd_His');
$backupDir = __DIR__ . '/backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$sqlBackupFile  = "{$backupDir}/backup_seguranca_{$timestamp}.sql";
$jsonBackupFile = "{$backupDir}/assignments_safety_snapshot_{$timestamp}.json";

echo "========================================================================\n";
echo "  FASE 1: BACKUP INTEGRAL DE SEGURANÇA & SNAPSHOT DE ATRIBUIÇÕES        \n";
echo "========================================================================\n\n";

try {
    $db = Database::getInstance();
    $db->exec("SET NAMES utf8mb4");

    // 1. Executar mysqldump via comando ou dump direto PDO
    echo "1. Gerando Dump SQL completo da base de dados...\n";
    $mysqldumpPath = 'c:\\xampp\\mysql\\bin\\mysqldump.exe';
    if (file_exists($mysqldumpPath)) {
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbName = defined('DB_NAME') ? DB_NAME : 'sftcoordenacao_db';
        
        $cmd = "\"{$mysqldumpPath}\" -u {$dbUser} --default-character-set=utf8mb4 --routines --triggers {$dbName} > \"{$sqlBackupFile}\"";
        exec($cmd, $output, $returnVar);
        
        if ($returnVar !== 0 || !file_exists($sqlBackupFile) || filesize($sqlBackupFile) === 0) {
            throw new Exception("Falha ao gerar mysqldump. Código de retorno: {$returnVar}");
        }
    } else {
        // Fallback para dump via PDO
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $fp = fopen($sqlBackupFile, 'w');
        fwrite($fp, "-- ISPSN 2026/27 BACKUP DE SEGURANÇA {$timestamp}\n\n");
        foreach ($tables as $t) {
            $create = $db->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_ASSOC);
            fwrite($fp, "DROP TABLE IF EXISTS `{$t}`;\n" . $create['Create Table'] . ";\n\n");
            $rows = $db->query("SELECT * FROM `{$t}`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = array_keys($row);
                $vals = array_map(function($v) use ($db) {
                    return $v === null ? 'NULL' : $db->quote($v);
                }, array_values($row));
                fwrite($fp, "INSERT INTO `{$t}` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ");\n");
            }
            fwrite($fp, "\n");
        }
        fclose($fp);
    }

    $sqlSizeKB = round(filesize($sqlBackupFile) / 1024, 2);
    echo "   [OK] Dump SQL gerado com sucesso: {$sqlBackupFile} ({$sqlSizeKB} KB)\n\n";

    // 2. Exportar Snapshot Estruturado de Todas as Atribuições e Histórico Docente
    echo "2. Gerando Snapshot JSON de atribuições docentes...\n";
    $query = "
        SELECT 
            lc.id AS linha_id,
            lc.plano_id,
            lc.disciplina_id,
            lc.turma_id,
            lc.docente_id,
            lc.conformidade,
            lc.justificacao,
            lc.regime,
            lc.parecer,
            lc.decisao_aprovacao,
            lc.observacoes,
            d.curso_id,
            c.codigo AS curso_codigo,
            c.nome AS curso_nome,
            d.nome AS disciplina_nome,
            d.ano_curricular,
            d.semestre,
            t.designacao AS turma_designacao,
            t.turno AS turma_turno,
            doc.nome AS docente_nome,
            doc.grau_academico AS docente_grau,
            doc.especialidade AS docente_especialidade
        FROM linhas_cobertura lc
        JOIN disciplinas d ON lc.disciplina_id = d.id
        JOIN cursos c ON d.curso_id = c.id
        LEFT JOIN turmas t ON lc.turma_id = t.id
        LEFT JOIN docentes doc ON lc.docente_id = doc.id
        ORDER BY c.id, d.ano_curricular, d.semestre, d.nome
    ";
    $snapshotRows = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

    $jsonPayload = [
        'generated_at' => date('c'),
        'total_linhas' => count($snapshotRows),
        'linhas'       => $snapshotRows
    ];

    file_put_contents($jsonBackupFile, json_encode($jsonPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $jsonSizeKB = round(filesize($jsonBackupFile) / 1024, 2);
    echo "   [OK] Snapshot JSON salvo com sucesso: {$jsonBackupFile} ({$jsonSizeKB} KB | " . count($snapshotRows) . " linhas gravadas)\n\n";

    echo "========================================================================\n";
    echo "  FASE 1 CONCLUÍDA COM SUCESSO! DADOS TOTALMENTE SALVAGUARDADOS.         \n";
    echo "========================================================================\n";

} catch (\Throwable $e) {
    echo "\n[ERRO CRÍTICO NA FASE 1]: " . $e->getMessage() . "\n";
    exit(1);
}
