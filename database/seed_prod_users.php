<?php
/**
 * Script Oficial de Seeding e Ativação em Produção
 * ISPSN 2026/27 — Chefes de Departamento e Coordenadores de Curso
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $db->exec("SET NAMES utf8mb4");

    echo "=========================================================\n";
    echo "  SEEDING OFICIAL DE UTILIZADORES DE PRODUÇÃO (ISPSN)    \n";
    echo "=========================================================\n\n";

    // 1. CHEFES DE DEPARTAMENTO
    $chefes = [
        ['nome' => 'Boaventura Feuerbach Fernando', 'email' => 'boaventura.fernando@ispsn.org'],
        ['nome' => 'Edmundo da Costa Francisco', 'email' => 'edmundo.francisco@ispsn.org'],
        ['nome' => 'Kianguembeni Teófilo Canania', 'email' => 'kianguenbeni.canania@ispsn.org'],
        ['nome' => 'Kianguembeni Teófilo Canania (Alias)', 'email' => 'kianguembeni.canania@ispsn.org']
    ];

    foreach ($chefes as $c) {
        $email = strtolower(trim($c['email']));
        $nome  = trim($c['nome']);

        $stmtCheck = $db->prepare("SELECT id FROM utilizadores WHERE LOWER(email) = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        $user = $stmtCheck->fetch();

        if ($user) {
            $stmtUp = $db->prepare("UPDATE utilizadores SET nome = ?, perfil = 'chefe_departamento', activo = 1 WHERE id = ?");
            $stmtUp->execute([$nome, $user['id']]);
            echo "[CHEFE DEPTO - OK] {$nome} ({$email})\n";
        } else {
            $stmtIns = $db->prepare("INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo) VALUES (?, ?, NULL, 'chefe_departamento', NULL, 1)");
            $stmtIns->execute([$nome, $email]);
            echo "[CHEFE DEPTO - NOVO] {$nome} ({$email})\n";
        }
    }

    // 2. COORDENADORES DE CURSO
    $coordenadores = [
        ['nome' => 'Dânia Castro Estupiña', 'curso' => 'Enfermagem', 'email' => 'dania.castro@ispsn.org'],
        ['nome' => 'Deoladeu Joaquim Ferramenta', 'curso' => 'História', 'email' => 'deuladeu.ferramenta@ispsn.org'],
        ['nome' => 'Domingos João Pedro Bernardo', 'curso' => 'Fisioterapia', 'email' => 'domingos.bernardo@ispsn.org'],
        ['nome' => 'Fernando Macedo', 'curso' => 'Direito', 'email' => 'fernando.macedo@ispsn.org'],
        ['nome' => 'Isata Gomes Cabaça', 'curso' => 'GRH', 'email' => 'isata.cabaca@ispsn.org'],
        ['nome' => 'João Miguel Catombela', 'curso' => 'Economia', 'email' => 'joao.miguel@ispsn.org'],
        ['nome' => 'Jorge Alberto Montane', 'curso' => 'Psicologia e Didáctica', 'email' => 'jorge.montane@ispsn.org'],
        ['nome' => 'Maria de Fátima Luis Falso Kessongo', 'curso' => 'Coordenadora do Regime B', 'email' => 'maria.falso@ispsn.org'],
        ['nome' => 'Miriam Ovideo Herrera', 'curso' => 'Análises Clínicas', 'email' => 'miriam.herrera@ispsn.org'],
        ['nome' => 'Nelson Garcia Sungo', 'curso' => 'Contabilidade e Finanças', 'email' => 'nelson.sungo@ispsn.org'],
        ['nome' => 'Sebastião Gonçalo Joaquim', 'curso' => 'Sociologia', 'email' => 'sebastao.joaquim@ispsn.org'],
        ['nome' => 'Silvia Catarina Adolfo Chitangua', 'curso' => 'Cardiopneumologia', 'email' => 'silvia.chitangua@ispsn.org'],
        ['nome' => 'Valeriano Mangandi', 'curso' => 'CPRI', 'email' => 'valeriano.mangandi@ispsn.org']
    ];

    foreach ($coordenadores as $c) {
        $email     = strtolower(trim($c['email']));
        $nome      = trim($c['nome']);
        $cursoNome = trim($c['curso']);

        // Garantir curso na tabela cursos
        $stmtCurso = $db->prepare("SELECT id FROM cursos WHERE UPPER(TRIM(codigo)) = UPPER(?) OR UPPER(TRIM(nome)) = UPPER(?) OR LOWER(nome) LIKE ? OR nome LIKE ? ORDER BY (UPPER(TRIM(nome)) = UPPER(?)) DESC LIMIT 1");
        $stmtCurso->execute([$cursoNome, $cursoNome, "%{$cursoNome}%", "%{$cursoNome}%", $cursoNome]);
        $cursoObj = $stmtCurso->fetch();

        if ($cursoObj) {
            $cursoId = (int)$cursoObj['id'];
        } else {
            $codigo = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $cursoNome), 0, 4));
            $stmtInsCurso = $db->prepare("INSERT INTO cursos (codigo, nome, grau, duracao_anos, activo) VALUES (?, ?, 'Licenciatura', 4, 1)");
            $stmtInsCurso->execute([$codigo, $cursoNome]);
            $cursoId = (int)$db->lastInsertId();
        }

        // Utilizador
        $stmtCheck = $db->prepare("SELECT id FROM utilizadores WHERE LOWER(email) = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        $user = $stmtCheck->fetch();

        if ($user) {
            $stmtUp = $db->prepare("UPDATE utilizadores SET nome = ?, perfil = 'coordenador', curso_id = ?, activo = 1 WHERE id = ?");
            $stmtUp->execute([$nome, $cursoId, $user['id']]);
            echo "[COORDENADOR - OK] {$nome} ({$email}) -> Curso: {$cursoNome}\n";
        } else {
            $stmtIns = $db->prepare("INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo) VALUES (?, ?, NULL, 'coordenador', ?, 1)");
            $stmtIns->execute([$nome, $email, $cursoId]);
            echo "[COORDENADOR - NOVO] {$nome} ({$email}) -> Curso: {$cursoNome}\n";
        }
    }

    echo "\n✅ BASE DE DADOS DE PRODUÇÃO POPULADA E ATIVADA COM SUCESSO!\n";

} catch (Throwable $e) {
    echo "ERRO AO EXECUTAR SEED DE PRODUÇÃO: " . $e->getMessage() . "\n";
}
