<?php
/**
 * Front Controller e Roteador Principal (PHP Nativo / MVC)
 * sftcoordenacao — Módulo de Cobertura Docente ISPSN 2026/27
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/helpers/Response.php';

// 1. Processar Ações POST / GET de Autenticação (login, activate, logout)
$action = $_GET['action'] ?? null;

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $result = Auth::attempt($email, $password);
    if ($result['success']) {
        $perfil = $result['user']['perfil'] ?? 'coordenador';
        $navPages = Auth::roleInfo($perfil)['nav'] ?? ['painel'];
        $landingPage = $navPages[0] ?? 'painel';
        header('Location: index.php?page=' . $landingPage);
        exit;
    } else {
        if (!empty($result['is_first_access'])) {
            $_SESSION['flash_info'] = $result['message'];
            $_SESSION['reset_email'] = $email;
            header('Location: index.php?page=login&mode=activate&email=' . urlencode($email));
            exit;
        }
        $_SESSION['flash_error'] = $result['message'];
        header('Location: index.php?page=login');
        exit;
    }
}

if ($action === 'activate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $result = Auth::activateAccount($email, $newPassword);
    if ($result['success']) {
        $_SESSION['flash_success'] = $result['message'];
        $perfil = $result['user']['perfil'] ?? 'coordenador';
        $navPages = Auth::roleInfo($perfil)['nav'] ?? ['painel'];
        $landingPage = $navPages[0] ?? 'painel';
        header('Location: index.php?page=' . $landingPage);
        exit;
    } else {
        $_SESSION['flash_error'] = $result['message'];
        header('Location: index.php?page=login');
        exit;
    }
}

if ($action === 'forgot_request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $result = Auth::requestPasswordReset($email);
    if ($result['success']) {
        $_SESSION['flash_info'] = $result['message'];
        $_SESSION['reset_step'] = 2;
        header('Location: index.php?page=login&tab=forgot');
        exit;
    } else {
        $_SESSION['flash_error'] = $result['message'];
        header('Location: index.php?page=login&tab=forgot');
        exit;
    }
}

if ($action === 'forgot_reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = $_POST['email'] ?? '';
    $pin         = $_POST['pin'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $result      = Auth::resetPassword($email, $pin, $newPassword);
    if ($result['success']) {
        $_SESSION['flash_success'] = $result['message'];
        $perfil = $result['user']['perfil'] ?? 'coordenador';
        $navPages = Auth::roleInfo($perfil)['nav'] ?? ['painel'];
        $landingPage = $navPages[0] ?? 'painel';
        header('Location: index.php?page=' . $landingPage);
        exit;
    } else {
        $_SESSION['flash_error'] = $result['message'];
        header('Location: index.php?page=login&tab=forgot');
        exit;
    }
}

if ($action === 'logout') {
    Auth::logout();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_info'] = 'Sessão encerrada com sucesso.';
    header('Location: index.php?page=login');
    exit;
}

// 2. Permite Troca Rápida de Perfil via Header (Apenas para Super Admin em Testes de Auditoria)
if (isset($_GET['role']) && Auth::check()) {
    if (!Auth::isSuperAdmin()) {
        $_SESSION['flash_error'] = 'Apenas a Administração tem autorização para alterar perfis.';
        header('Location: index.php?page=cobertura');
        exit;
    }
    $targetRole = $_GET['role'];
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM utilizadores WHERE perfil = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$targetRole]);
    $usrRole = $stmt->fetch();
    if (!$usrRole) {
        $currUser = Auth::user();
        $usrRole = [
            'id'       => $currUser['id'] ?? 1,
            'nome'     => $currUser['nome'] ?? 'Utilizador ISPSN',
            'email'    => $currUser['email'] ?? 'admin.ti@ispsn.org',
            'perfil'   => $targetRole,
            'curso_id' => $currUser['curso_id'] ?? 1
        ];
    }
    // Limpar flags de bypass de super admin para testar as permissões reais do perfil selecionado
    unset($_SESSION['super_admin_logged_in'], $_SESSION['master_admin_session'], $_SESSION['is_super_admin']);
    Auth::login($usrRole, true);
}

// 3. Processar Requisições de API REST (JSON)
if (isset($_GET['api'])) {
    require_once __DIR__ . '/../app/controllers/ApiController.php';
    $apiController = new ApiController();
    $apiController->handleRequest($_GET['api']);
    exit;
}

// 4. Verificação de Autenticação e Roteamento de Páginas
$requestedPage = $_GET['page'] ?? 'painel';

if ($requestedPage === 'login') {
    if (Auth::check()) {
        header('Location: index.php?page=painel');
        exit;
    }
    require_once __DIR__ . '/../app/views/auth/login.php';
    exit;
}

// Se não estiver autenticado, redireciona para a Tela de Login
if (!Auth::check()) {
    header('Location: index.php?page=login');
    exit;
}

// Se a página não for permitida para o perfil ativo, vai para a primeira página permitida
if (!Auth::isAllowedPage($requestedPage)) {
    $allowed = Auth::roleInfo()['nav'];
    $page = $allowed[0] ?? 'painel';
} else {
    $page = $requestedPage;
}

$currentPage = $page;

// Carregar Dados Consoante a Página
require_once __DIR__ . '/../app/models/PlanoModel.php';
require_once __DIR__ . '/../app/models/DocenteModel.php';
require_once __DIR__ . '/../app/models/CursoModel.php';

$planoModel   = new PlanoModel();
$docenteModel = new DocenteModel();
$cursoModel   = new CursoModel();

// Renderizar Layouts & Views
switch ($page) {
    case 'painel':
        $title = 'Portal ISPSN · Painel de Controlo';
        $stats = $planoModel->getConsolidadosStats();
        $docs3Count = $planoModel->getDocentesSobrecargaCount();
        require_once __DIR__ . '/../app/views/layouts/header.php';
        require_once __DIR__ . '/../app/views/layouts/sidebar.php';
        require_once __DIR__ . '/../app/views/painel/index.php';
        require_once __DIR__ . '/../app/views/layouts/footer.php';
        break;

    case 'cobertura':
        $title = 'Portal ISPSN · Cobertura Docente';
        $cursos = $cursoModel->getAll();
        require_once __DIR__ . '/../app/views/layouts/header.php';
        require_once __DIR__ . '/../app/views/layouts/sidebar.php';
        require_once __DIR__ . '/../app/views/cobertura/index.php';
        require_once __DIR__ . '/../app/views/layouts/footer.php';
        break;

    case 'turmas':
        $title = 'Portal ISPSN · Gestão de Turmas & Indicadores';
        $cursos = $cursoModel->getAll();
        require_once __DIR__ . '/../app/views/layouts/header.php';
        require_once __DIR__ . '/../app/views/layouts/sidebar.php';
        require_once __DIR__ . '/../app/views/turmas/index.php';
        require_once __DIR__ . '/../app/views/layouts/footer.php';
        break;

    case 'curriculo':
        $title = 'Portal ISPSN · Gestão da Matriz Curricular';
        $cursos = $cursoModel->getAll();
        require_once __DIR__ . '/../app/views/layouts/header.php';
        require_once __DIR__ . '/../app/views/layouts/sidebar.php';
        require_once __DIR__ . '/../app/views/curriculo/index.php';
        require_once __DIR__ . '/../app/views/layouts/footer.php';
        break;

    case 'docentes':
        $title = 'Portal ISPSN · Docentes & Documentos';
        $docentes = $docenteModel->getAll();
        require_once __DIR__ . '/../app/views/layouts/header.php';
        require_once __DIR__ . '/../app/views/layouts/sidebar.php';
        require_once __DIR__ . '/../app/views/docentes/index.php';
        require_once __DIR__ . '/../app/views/layouts/footer.php';
        break;

    case 'cv':
        $title    = 'Portal ISPSN · CV Estruturado (Modelo MESCTI)';
        $docentes = $docenteModel->getAll();
        require_once __DIR__ . '/../app/views/layouts/header.php';
        require_once __DIR__ . '/../app/views/layouts/sidebar.php';
        require_once __DIR__ . '/../app/views/cv/index.php';
        require_once __DIR__ . '/../app/views/layouts/footer.php';
        break;

    case 'aprov':
        $title = 'Portal ISPSN · Aprovações';
        $stats = $planoModel->getConsolidadosStats();
        require_once __DIR__ . '/../app/views/layouts/header.php';
        require_once __DIR__ . '/../app/views/layouts/sidebar.php';
        require_once __DIR__ . '/../app/views/aprov/index.php';
        require_once __DIR__ . '/../app/views/layouts/footer.php';
        break;

    case 'config':
        $title = 'Portal ISPSN · Configurações do Sistema';
        $db = Database::getInstance();
        // Limpar utilizadores de teste fictícios da base de dados e assegurar conta do Super Admin David Boio
        $db->exec("DELETE FROM utilizadores WHERE email IN ('bernardo.domingos@ispsn.org', 'maria.eugenia@ispsn.org', 'joao.silva@ispsn.org', 'antonio.costa@ispsn.org', 'manuel.ferreira@ispsn.org')");
        $db->exec("INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo) VALUES ('David Boio', 'david.boio@ispsn.org', NULL, 'admin', NULL, 1) ON DUPLICATE KEY UPDATE perfil = 'admin', activo = 1");
        
        // Assegurar colunas e VIEW SQL do novo workflow de aprovação em 2 etapas
        try {
            $db->exec("ALTER TABLE linhas_cobertura ADD COLUMN decisao_aprovacao VARCHAR(100) DEFAULT 'Aprovar'");
        } catch (\Throwable $e) {}

        try {
            $db->exec("ALTER TABLE planos_cobertura ADD COLUMN chefe_depto_id INT DEFAULT NULL");
            $db->exec("ALTER TABLE planos_cobertura ADD COLUMN data_aprovacao_depto DATETIME DEFAULT NULL");
            $db->exec("ALTER TABLE planos_cobertura ADD COLUMN parecer_depto TEXT DEFAULT NULL");
            $db->exec("ALTER TABLE planos_cobertura ADD COLUMN presidente_id INT DEFAULT NULL");
            $db->exec("ALTER TABLE planos_cobertura ADD COLUMN data_validacao_pr DATETIME DEFAULT NULL");
            $db->exec("ALTER TABLE planos_cobertura ADD COLUMN parecer_pr TEXT DEFAULT NULL");
            $db->exec("ALTER TABLE planos_cobertura MODIFY COLUMN estado VARCHAR(50) DEFAULT 'Rascunho'");
            $db->exec("ALTER TABLE utilizadores MODIFY COLUMN perfil VARCHAR(50) NOT NULL");
        } catch (\Throwable $e) {}

        try {
            $db->exec("CREATE OR REPLACE VIEW `vw_linhas_cobertura_detalhada` AS SELECT lc.id AS id, lc.id AS linha_id, lc.plano_id, lc.disciplina_id, lc.turma_id, lc.docente_id, lc.conformidade, lc.justificacao, lc.regime, lc.categoria_carreira, lc.parecer, COALESCE(lc.decisao_aprovacao, 'Aprovar') AS decisao_aprovacao, lc.observacoes, lc.updated_at, pc.curso_id, pc.ano_lectivo, pc.estado AS estado_plano, d.nome AS disciplina_nome, d.ano_curricular, d.semestre, d.carga_horaria_semanal, d.creditos, t.designacao AS turma_nome, t.sumarios_registados, t.sumarios_previstos, t.programa_carregado, t.dosificacao_carregada, t.notas_no_prazo, t.inquerito_media, doc.nome AS docente_nome, doc.grau_academico AS docente_grau, doc.especialidade AS docente_especialidade, doc.tem_inaarees, doc.tem_agregacao_pedag FROM `linhas_cobertura` lc JOIN `planos_cobertura` pc ON lc.plano_id = pc.id JOIN `disciplinas` d ON lc.disciplina_id = d.id LEFT JOIN `turmas` t ON lc.turma_id = t.id LEFT JOIN `docentes` doc ON lc.docente_id = doc.id");
        } catch (\Throwable $e) {}

        $utilizadores = $db->query("SELECT u.*, c.nome as curso_nome FROM utilizadores u LEFT JOIN cursos c ON u.curso_id = c.id ORDER BY u.id ASC")->fetchAll();
        $cursos = $cursoModel->getAll();
        $docentes = $docenteModel->getAll();
        $historico = $db->query("SELECT h.*, p.ano_lectivo, c.nome as curso_nome, u.nome as utilizador_nome FROM historico_aprovacoes h JOIN planos_cobertura p ON h.plano_id = p.id JOIN cursos c ON p.curso_id = c.id JOIN utilizadores u ON h.utilizador_id = u.id ORDER BY h.created_at DESC LIMIT 10")->fetchAll();
        require_once __DIR__ . '/../app/views/layouts/header.php';
        require_once __DIR__ . '/../app/views/layouts/sidebar.php';
        require_once __DIR__ . '/../app/views/config/index.php';
        require_once __DIR__ . '/../app/views/layouts/footer.php';
        break;

    case 'dashboard':
        $title = 'Portal ISPSN · Dashboard Institucional Executivo';
        $anoLectivo       = $_GET['ano_lectivo'] ?? '2026/27';
        $qualificacoes    = $docenteModel->getDashboardQualificacoes();
        $pilares          = $docenteModel->getDashboardPilares();
        $sobrecarga       = $docenteModel->getDashboardSobrecargaPartilha();
        $cursosStats      = $planoModel->getDashboardConformidadeCursos($anoLectivo);
        $comparativoAnual = $planoModel->getComparativoAnualStats('2025/26', '2026/27');
        require_once __DIR__ . '/../app/views/layouts/header.php';
        require_once __DIR__ . '/../app/views/layouts/sidebar.php';
        require_once __DIR__ . '/../app/views/dashboard/institucional.php';
        require_once __DIR__ . '/../app/views/layouts/footer.php';
        break;

    case 'relatorio_plano':
        require_once __DIR__ . '/../app/views/cobertura/relatorio.php';
        break;

    default:
        header('Location: index.php?page=painel');
        exit;
}
