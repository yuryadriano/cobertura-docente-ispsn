<?php
/**
 * Script de Povoamento Inicial de Demonstração (Seeder)
 * Módulo de Cobertura Docente & CV MESCTI — ISPSN
 * @author Evaristo Adriano
 */

require_once __DIR__ . '/../config/config.php';

echo "=== INICIANDO SEEDER DE DEMONSTRAÇÃO DO MÓDULO DE COBERTURA DOCENTE ===\n";

try {
    $dsnNoDb = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $pdoInit = new PDO($dsnNoDb, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "1. Garantindo a criação da base de dados sftcoordenacao_db...\n";
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `sftcoordenacao_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance();

    echo "2. Limpando registos acumulados e recriando tabelas SQL...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec("TRUNCATE TABLE `linhas_cobertura`;");
    $db->exec("TRUNCATE TABLE `turmas`;");
    $db->exec("TRUNCATE TABLE `disciplinas`;");
    $db->exec("TRUNCATE TABLE `planos_cobertura`;");
    $db->exec("TRUNCATE TABLE `docentes`;");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $sqlSchema = file_get_contents(__DIR__ . '/schema.sql');
    $queries = array_filter(array_map('trim', explode(';', $sqlSchema)));
    foreach ($queries as $q) {
        if (!empty($q)) {
            $db->exec($q);
        }
    }
    echo "   [OK] Tabelas limpas e verificadas no MySQL.\n";

    // Docentes fictícios de demonstração para testes da equipa técnica
    $docentesDemo = [
        ['nome' => 'Docente Exemplo 1 (Doutor)',     'grau' => 'Doutor',     'esp' => 'Direito Constitucional', 'ina' => 'Sim', 'cap' => 'Sim', 'car' => 'Professor Auxiliar'],
        ['nome' => 'Docente Exemplo 2 (Mestre)',     'grau' => 'Mestre',     'esp' => 'Ensino da Psicologia',   'ina' => 'Sim', 'cap' => 'Sim', 'car' => 'Assistente'],
        ['nome' => 'Docente Exemplo 3 (Licenciado)', 'grau' => 'Licenciado', 'esp' => 'Gestão de Empresas',    'ina' => 'Não', 'cap' => 'Não', 'car' => 'Assistente'],
        ['nome' => 'Docente Exemplo 4 (Mestre)',     'grau' => 'Mestre',     'esp' => 'Saúde Pública',          'ina' => 'Não', 'cap' => 'Sim', 'car' => 'Assistente'],
        ['nome' => 'Docente Exemplo 5 (Doutor)',     'grau' => 'Doutor',     'esp' => 'Engenharia de Software', 'ina' => 'Sim', 'cap' => 'Sim', 'car' => 'Professor Titular'],
        ['nome' => 'Docente Exemplo 6 (Licenciado)', 'grau' => 'Licenciado', 'esp' => 'Ciência Política',       'ina' => 'Não', 'cap' => 'Sim', 'car' => 'Assistente']
    ];

    echo "3. Importando 6 Docentes de Demonstração...\n";
    $stmtDocente = $db->prepare("
        INSERT INTO docentes (nome, grau_academico, especialidade, tem_inaarees, tem_agregacao_pedag, categoria_carreira, activo)
        VALUES (:nome, :grau, :esp, :ina, :cap, :car, 1)
    ");

    foreach ($docentesDemo as $doc) {
        $stmtDocente->execute([
            ':nome' => $doc['nome'],
            ':grau' => $doc['grau'],
            ':esp'  => $doc['esp'],
            ':ina'  => $doc['ina'],
            ':cap'  => $doc['cap'],
            ':car'  => $doc['car']
        ]);
    }

    echo "   [OK] Docentes de demonstração carregados com sucesso.\n";
    echo "=== SEEDER DE DEMONSTRAÇÃO CONCLUÍDO COM SUCESSO ===\n";

} catch (\Throwable $e) {
    echo "\n[ERRO CRÍTICO NO SEEDER] " . $e->getMessage() . "\n";
}
