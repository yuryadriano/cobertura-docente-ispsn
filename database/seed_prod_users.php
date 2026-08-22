<?php
/**
 * Script Oficial de Seeding e Ativação em Produção
 * ISPSN 2026/27 — Super Admins, Chefes de Departamento e Coordenadores de Curso
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $db->exec("SET NAMES utf8mb4");

    echo "=========================================================\n";
    echo "  SEEDING OFICIAL DE UTILIZADORES DE PRODUÇÃO (ISPSN)    \n";
    echo "=========================================================\n\n";

    // 0. DESATIVAR FALSOS CURSOS (ex: Turnos cadastrados como cursos)
    $db->exec("UPDATE cursos SET activo = 0 WHERE id = 134 OR UPPER(codigo) = 'COOR' OR nome LIKE '%Regime B%'");

    // 1. SUPER ADMINS (Acesso Total Soberano)
    $superAdmins = [
        ['nome' => 'Evaristo Adriano (Admin)', 'email' => 'evaristo.adriano@ispsn.org'],
        ['nome' => 'David Boio (Admin)', 'email' => 'david.boio@ispsn.org']
    ];

    foreach ($superAdmins as $sa) {
        $email = strtolower(trim($sa['email']));
        $nome  = trim($sa['nome']);

        $stmtCheck = $db->prepare("SELECT id FROM utilizadores WHERE LOWER(email) = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        $user = $stmtCheck->fetch();

        if ($user) {
            $stmtUp = $db->prepare("UPDATE utilizadores SET nome = ?, perfil = 'admin', curso_id = NULL, activo = 1 WHERE id = ?");
            $stmtUp->execute([$nome, $user['id']]);
            echo "[SUPER ADMIN - OK] {$nome} ({$email})\n";
        } else {
            $stmtIns = $db->prepare("INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo) VALUES (?, ?, NULL, 'admin', NULL, 1)");
            $stmtIns->execute([$nome, $email]);
            echo "[SUPER ADMIN - NOVO] {$nome} ({$email})\n";
        }
    }

    // 2. CHEFES DE DEPARTAMENTO E GESTÃO ACADÉMICA
    $chefes = [
        ['nome' => 'Boaventura Feuerbach Fernando', 'email' => 'boaventura.fernando@ispsn.org', 'perfil' => 'chefe_departamento'],
        ['nome' => 'Edmundo da Costa Francisco', 'email' => 'edmundo.francisco@ispsn.org', 'perfil' => 'chefe_departamento'],
        ['nome' => 'Kianguembeni Teófilo Canania', 'email' => 'kianguenbeni.canania@ispsn.org', 'perfil' => 'chefe_departamento'],
        ['nome' => 'Kianguembeni Teófilo Canania (Alias)', 'email' => 'kianguembeni.canania@ispsn.org', 'perfil' => 'chefe_departamento'],
        ['nome' => 'Maria de Fátima Luis Falso Kessongo', 'email' => 'maria.falso@ispsn.org', 'perfil' => 'gestor_academico']
    ];

    foreach ($chefes as $c) {
        $email  = strtolower(trim($c['email']));
        $nome   = trim($c['nome']);
        $perfil = $c['perfil'] ?? 'chefe_departamento';

        $stmtCheck = $db->prepare("SELECT id FROM utilizadores WHERE LOWER(email) = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        $user = $stmtCheck->fetch();

        if ($user) {
            $stmtUp = $db->prepare("UPDATE utilizadores SET nome = ?, perfil = ?, curso_id = NULL, activo = 1 WHERE id = ?");
            $stmtUp->execute([$nome, $perfil, $user['id']]);
            echo "[{$perfil} - OK] {$nome} ({$email})\n";
        } else {
            $stmtIns = $db->prepare("INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo) VALUES (?, ?, NULL, ?, NULL, 1)");
            $stmtIns->execute([$nome, $email, $perfil]);
            echo "[{$perfil} - NOVO] {$nome} ({$email})\n";
        }
    }

    // 3. COORDENADORES DE CURSO (12 Cursos Oficiais)
    $coordenadores = [
        ['nome' => 'Dânia Castro Estupiña', 'curso' => 'Enfermagem', 'email' => 'dania.castro@ispsn.org'],
        ['nome' => 'Deoladeu Joaquim Ferramenta', 'curso' => 'História', 'email' => 'deuladeu.ferramenta@ispsn.org'],
        ['nome' => 'Domingos João Pedro Bernardo', 'curso' => 'Fisioterapia', 'email' => 'domingos.bernardo@ispsn.org'],
        ['nome' => 'Fernando Macedo', 'curso' => 'Direito', 'email' => 'fernando.macedo@ispsn.org'],
        ['nome' => 'Isata Gomes Cabaça', 'curso' => 'GRH', 'email' => 'isata.cabaca@ispsn.org'],
        ['nome' => 'João Miguel Catombela', 'curso' => 'Economia', 'email' => 'joao.miguel@ispsn.org'],
        ['nome' => 'Jorge Alberto Montane', 'curso' => 'Psicologia e Didáctica', 'email' => 'jorge.montane@ispsn.org'],
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

        // Obter ID do curso canónico
        $stmtCurso = $db->prepare("SELECT id FROM cursos WHERE UPPER(TRIM(codigo)) = UPPER(?) OR UPPER(TRIM(nome)) = UPPER(?) OR LOWER(nome) LIKE ? OR nome LIKE ? ORDER BY (UPPER(TRIM(nome)) = UPPER(?)) DESC LIMIT 1");
        $stmtCurso->execute([$cursoNome, $cursoNome, "%{$cursoNome}%", "%{$cursoNome}%", $cursoNome]);
        $cursoObj = $stmtCurso->fetch();

        if (!$cursoObj) {
            echo "[AVISO] Curso {$cursoNome} não encontrado.\n";
            continue;
        }
        $cursoId = (int)$cursoObj['id'];

        $stmtCheck = $db->prepare("SELECT id FROM utilizadores WHERE LOWER(email) = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        $user = $stmtCheck->fetch();

        if ($user) {
            $stmtUp = $db->prepare("UPDATE utilizadores SET nome = ?, perfil = 'coordenador', curso_id = ?, activo = 1 WHERE id = ?");
            $stmtUp->execute([$nome, $cursoId, $user['id']]);
            echo "[COORDENADOR - OK] {$nome} ({$email}) -> Curso: {$cursoNome} (ID: {$cursoId})\n";
        } else {
            $stmtIns = $db->prepare("INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo) VALUES (?, ?, NULL, 'coordenador', ?, 1)");
            $stmtIns->execute([$nome, $email, $cursoId]);
            echo "[COORDENADOR - NOVO] {$nome} ({$email}) -> Curso: {$cursoNome} (ID: {$cursoId})\n";
        }
    }

    echo "\n✅ BASE DE DADOS DE PRODUÇÃO 100% REGULARIZADA E ATIVADA!\n";

} catch (Throwable $e) {
    echo "ERRO AO EXECUTAR SEED DE PRODUÇÃO: " . $e->getMessage() . "\n";
}
