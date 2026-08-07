<?php
/**
 * Script de Povoamento Inicial (Seeder)
 * Lê 01_Portal_Autonomo/dados/portal_data.json e insere os dados na MySQL sftcoordenacao_db
 */

require_once __DIR__ . '/../config/config.php';

echo "=== INICIANDO SEEDER DO MÓDULO DE COBERTURA DOCENTE ===\n";

try {
    // 1. Connect without db name to ensure creation
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
    
    // Split queries by semicolon
    $queries = array_filter(array_map('trim', explode(';', $sqlSchema)));
    foreach ($queries as $q) {
        if (!empty($q)) {
            $db->exec($q);
        }
    }
    echo "   [OK] Tabelas limpas e verificadas no MySQL.\n";

    // 3. Carregar portal_data.json
    $jsonPath = __DIR__ . '/../01_Portal_Autonomo/dados/portal_data.json';
    if (!file_exists($jsonPath)) {
        die("   [ERRO] Ficheiro portal_data.json não encontrado em: $jsonPath\n");
    }

    $jsonContent = file_get_contents($jsonPath);
    $data = json_decode($jsonContent, true);

    if (!$data) {
        die("   [ERRO] Falha ao descodificar portal_data.json.\n");
    }

    // 4. Importar Docentes (258 docentes)
    echo "3. Importando 258 Docentes...\n";
    $stmtDocente = $db->prepare("
        INSERT INTO docentes (nome, grau_academico, especialidade, tem_inaarees, tem_agregacao_pedag, categoria_carreira, activo)
        VALUES (:nome, :grau, :esp, :ina, :cap, :car, 1)
        ON DUPLICATE KEY UPDATE 
            grau_academico = VALUES(grau_academico),
            especialidade = VALUES(especialidade)
    ");

    $docentesMap = []; // Nome -> ID
    foreach ($data['docentes'] as $doc) {
        $nome = trim($doc['n']);
        $grau = in_array($doc['grau'], ['Licenciado', 'Mestre', 'Doutor']) ? $doc['grau'] : 'Licenciado';
        $esp = $doc['esp'] ?? 'Não identificada';
        $ina = ($doc['ina'] && $doc['ina'] !== 'Não') ? 'Sim' : 'Não';
        $cap = ($doc['cap'] === 'Sim') ? 'Sim' : 'Não';
        $car = $doc['car'] ?? 'Assistente';

        $stmtDocente->execute([
            ':nome' => $nome,
            ':grau' => $grau,
            ':esp'  => $esp,
            ':ina'  => $ina,
            ':cap'  => $cap,
            ':car'  => $car
        ]);

        $docId = $db->lastInsertId();
        if (!$docId) {
            $stmtFind = $db->prepare("SELECT id FROM docentes WHERE nome = ?");
            $stmtFind->execute([$nome]);
            $docId = $stmtFind->fetchColumn();
        }
        $docentesMap[$nome] = $docId;
    }
    echo "   [OK] " . count($docentesMap) . " docentes inseridos na base de dados.\n";

    // 5. Importar Cursos e Disciplinas
    echo "4. Importando Cursos e Currículos...\n";
    $stmtCurso = $db->prepare("INSERT INTO cursos (codigo, nome, grau) VALUES (:codigo, :nome, 'Licenciatura') ON DUPLICATE KEY UPDATE nome=VALUES(nome)");
    $stmtDisc = $db->prepare("INSERT INTO disciplinas (curso_id, nome, ano_curricular, semestre, carga_horaria_semanal, creditos) VALUES (:curso_id, :nome, :ano, :semestre, :ch, :cred)");

    $cursosMap = []; // Nome do Curso -> ID
    if (isset($data['curriculo']) && is_array($data['curriculo'])) {
        foreach ($data['curriculo'] as $nomeCurso => $anos) {
            $codigoCurso = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $nomeCurso), 0, 4));
            $stmtCurso->execute([':codigo' => $codigoCurso, ':nome' => $nomeCurso]);
            $cursoId = $db->lastInsertId();
            if (!$cursoId) {
                $stmtFindC = $db->prepare("SELECT id FROM cursos WHERE nome = ?");
                $stmtFindC->execute([$nomeCurso]);
                $cursoId = $stmtFindC->fetchColumn();
            }
            $cursosMap[$nomeCurso] = $cursoId;

            // Inserir Disciplinas do Curso
            foreach ($anos as $anoNum => $semestres) {
                foreach ($semestres as $semNum => $disciplinas) {
                    $semestreLabel = ($semNum == '1') ? 'I' : 'II';
                    foreach ($disciplinas as $nomeDisc) {
                        $stmtDisc->execute([
                            ':curso_id' => $cursoId,
                            ':nome'     => trim($nomeDisc),
                            ':ano'      => (int)$anoNum,
                            ':semestre' => $semestreLabel,
                            ':ch'       => 4,
                            ':cred'     => 6
                        ]);
                    }
                }
            }
        }
    }
    echo "   [OK] " . count($cursosMap) . " cursos e as respetivas disciplinas oficiais foram inseridos.\n";

    // 6. Criar Plano de Cobertura Inicial para cada Curso (2026/27)
    echo "5. Gerando Planos de Cobertura 2026/27 por Curso com Turmas e Métricas...\n";
    $stmtPlano = $db->prepare("INSERT INTO planos_cobertura (curso_id, ano_lectivo, estado) VALUES (:curso_id, '2026/27', 'Rascunho') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
    $stmtTurma = $db->prepare("
        INSERT INTO turmas (id, disciplina_id, docente_id, designacao, sumarios_registados, sumarios_previstos, programa_carregado, dosificacao_carregada, notas_no_prazo, inquerito_media)
        VALUES (:id, :disciplina_id, :docente_id, :designacao, :sum_reg, 200, 1, 1, :notas_prazo, :inq_media)
        ON DUPLICATE KEY UPDATE docente_id = VALUES(docente_id), sumarios_registados = VALUES(sumarios_registados)
    ");
    $stmtLinha = $db->prepare("
        INSERT INTO linhas_cobertura (plano_id, disciplina_id, turma_id, docente_id, conformidade, justificacao, regime, parecer)
        VALUES (:plano_id, :disciplina_id, :turma_id, :docente_id, :conf, :justificacao, 'Tempo Parcial', 'Manter')
    ");

    $stmtGetDiscs = $db->prepare("SELECT id, nome, ano_curricular FROM disciplinas WHERE curso_id = ?");

    foreach ($cursosMap as $nomeCurso => $cursoId) {
        $stmtPlano->execute([':curso_id' => $cursoId]);
        $planoId = $db->lastInsertId();

        $stmtGetDiscs->execute([$cursoId]);
        $discs = $stmtGetDiscs->fetchAll();

        foreach ($discs as $d) {
            $discId = $d['id'];
            $discNome = $d['nome'];
            $codigoCurso = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $nomeCurso), 0, 3));
            $turmaId = "{$codigoCurso}{$d['ano_curricular']}MA";

            // Tentar encontrar docente de referência no JSON ref
            $docenteId = null;
            $conf = 'Por verificar';
            $justificacao = 'Especializações';
            if (isset($data['ref'][$nomeCurso][$discNome])) {
                $nomeDocRef = $data['ref'][$nomeCurso][$discNome];
                if (isset($docentesMap[$nomeDocRef])) {
                    $docenteId = $docentesMap[$nomeDocRef];
                    $conf = 'Sim';
                    $justificacao = 'Mestrado';
                }
            }

            // Criar Turma associada
            $stmtTurma->execute([
                ':id'            => $turmaId . "-D" . $discId,
                ':disciplina_id' => $discId,
                ':docente_id'    => $docenteId,
                ':designacao'    => "Turma A ({$turmaId})",
                ':sum_reg'       => rand(140, 195),
                ':notas_prazo'   => (rand(0, 10) > 2) ? 'Sim' : 'Não',
                ':inq_media'     => number_format(rand(35, 50) / 10, 2)
            ]);

            $stmtLinha->execute([
                ':plano_id'      => $planoId,
                ':disciplina_id' => $discId,
                ':turma_id'      => $turmaId . "-D" . $discId,
                ':docente_id'    => $docenteId,
                ':conf'          => $conf,
                ':justificacao'  => $justificacao
            ]);
        }
    }
    echo "   [OK] Planos, Turmas e Linhas de Cobertura pré-carregados para todos os cursos.\n";

    // 7. Criar Utilizadores de Teste para os 6 Perfis
    echo "6. Criando Utilizadores Corporativos (@ispsn.org) com Suporte a Ativação e Login...\n";
    $db->exec("ALTER TABLE utilizadores MODIFY COLUMN senha_hash VARCHAR(255) DEFAULT NULL");

    $users = [
        ['nome' => 'Administrador TI', 'email' => 'admin.ti@ispsn.org', 'perfil' => 'admin', 'curso_id' => null, 'senha' => '123456'],
        ['nome' => 'Evaristo Adriano', 'email' => 'evaristo.adriano@ispsn.org', 'perfil' => 'coordenador', 'curso_id' => $cursosMap['Direito'] ?? null, 'senha' => '123456'],
    ];

    $stmtUser = $db->prepare("
        INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id)
        VALUES (:nome, :email, :senha, :perfil, :curso_id)
        ON DUPLICATE KEY UPDATE 
            nome = VALUES(nome),
            senha_hash = VALUES(senha_hash),
            perfil = VALUES(perfil),
            curso_id = VALUES(curso_id)
    ");

    foreach ($users as $u) {
        $passHash = (!empty($u['senha'])) ? password_hash($u['senha'], PASSWORD_DEFAULT) : null;
        $stmtUser->execute([
            ':nome'     => $u['nome'],
            ':email'    => $u['email'],
            ':senha'    => $passHash,
            ':perfil'   => $u['perfil'],
            ':curso_id' => $u['curso_id']
        ]);
    }
    echo "   [OK] Utilizadores corporativos criados com sucesso (com suporte a Primeiro Acesso e Senha 123456).\n";

    echo "\n=== POVOAMENTO CONCLUÍDO COM SUCESSO E SEM ERROS! ===\n";

} catch (Exception $e) {
    echo "\n[ERRO FATAL NO SEEDER]: " . $e->getMessage() . "\n";
    exit(1);
}
