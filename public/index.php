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
        $_SESSION['flash_error'] = $result['message'];
        if (!empty($result['is_first_access'])) {
            $_SESSION['reset_email'] = $email;
            header('Location: index.php?page=login&mode=activate&email=' . urlencode($email));
            exit;
        }
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

// 2. Permite Troca Rápida de Perfil via Header (para testes e demonstração fiéis ao protótipo)
if (isset($_GET['role']) && Auth::check()) {
    $targetRole = $_GET['role'];
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM utilizadores WHERE perfil = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$targetRole]);
    $usrRole = $stmt->fetch();
    if ($usrRole) {
        // Limpar flags de bypass de super admin para testar as permissões reais do perfil selecionado
        unset($_SESSION['super_admin_logged_in'], $_SESSION['master_admin_session'], $_SESSION['is_super_admin']);
        Auth::login($usrRole, true);
    }
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
