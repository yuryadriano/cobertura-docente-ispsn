<?php
/**
 * Controller API REST JSON
 * sftcoordenacao — Módulo de Cobertura Docente ISPSN 2026/27
 */

require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Notification.php';
require_once __DIR__ . '/../models/DocenteModel.php';
require_once __DIR__ . '/../models/CursoModel.php';
require_once __DIR__ . '/../models/PlanoModel.php';

class ApiController {
    private DocenteModel $docenteModel;
    private CursoModel $cursoModel;
    private PlanoModel $planoModel;

    public function __construct() {
        $this->docenteModel = new DocenteModel();
        $this->cursoModel   = new CursoModel();
        $this->planoModel   = new PlanoModel();
    }

    public function handleRequest(string $action): void {
        switch ($action) {
            case 'docentes':
                $this->getDocentes();
                break;
            case 'cursos':
                $this->getCursos();
                break;
            case 'plano':
                $this->getPlano();
                break;
            case 'linha_salvar':
                $this->salvarLinha();
                break;
            case 'linha_replicar_turmas':
                $this->replicarLinhaTurmas();
                break;
            case 'plano_estado':
                $this->alterarEstadoPlano();
                break;
            case 'docente_salvar':
                $this->salvarDocente();
                break;
            case 'stats':
                $this->getStats();
                break;
            case 'dashboard_stats':
                $this->getDashboardStats();
                break;
            case 'switch_role':
                $this->switchRole();
                break;
            case 'utilizador_salvar':
                $this->salvarUtilizador();
                break;
            case 'utilizador_criar':
                $this->criarUtilizador();
                break;
            case 'utilizadores_importar':
                $this->importarUtilizadores();
                break;
            case 'utilizador_ativar_docente':
                $this->ativarDocentePerfil();
                break;
            case 'sugerir_docentes':
                $this->sugerirDocentes();
                break;
            case 'diagnostico_risco':
                $this->diagnosticoRisco();
                break;
            case 'recalcular_conformidades':
                $this->recalcularConformidades();
                break;
            case 'exportar_excel':
                $this->exportarExcel();
                break;
            case 'docente_upload_documento':
                $this->uploadDocumentoDocente();
                break;
            case 'docente_documentos':
                $this->getDocumentosDocente();
                break;
            case 'plano_historico':
                $this->getHistoricoPlano();
                break;
            case 'config_parametros_salvar':
                $this->salvarParametrosConfig();
                break;
            case 'salvar_ano_lectivo':
                $this->salvarAnoLectivo();
                break;
            case 'executar_rollover':
                $this->executarRollOver();
                break;
            case 'sincronizar_gestao_escolar':
                $this->sincronizarGestaoEscolar();
                break;
            case 'cv_carregar':
                $this->carregarCV();
                break;
            case 'cv_salvar':
                $this->salvarCV();
                break;
            case 'turmas':
                $this->getTurmas();
                break;
            case 'turma_salvar':
                $this->salvarTurma();
                break;
            case 'disciplinas':
                $this->getDisciplinas();
                break;
            case 'disciplina_salvar':
                $this->salvarDisciplina();
                break;
            default:
                Response::error('Ação inválida ou não especificada.', 404);

        }
    }

    private function getDocentes(): void {
        if (!empty($_GET['q']) || !empty($_GET['grau']) || !empty($_GET['inaarees']) || !empty($_GET['capacidade'])) {
            $docentes = $this->docenteModel->searchAndFilter($_GET);
        } else {
            $docentes = $this->docenteModel->getAll();
        }
        Response::json(['success' => true, 'data' => $docentes]);
    }

    private function sugerirDocentes(): void {
        $disciplinaId = (int)($_GET['disciplina_id'] ?? 0);
        if (!$disciplinaId) {
            Response::error('ID da disciplina é obrigatório.');
        }

        $sugestoes = $this->planoModel->sugerirDocentesParaDisciplina($disciplinaId);
        Response::json(['success' => true, 'data' => $sugestoes]);
    }

    private function diagnosticoRisco(): void {
        $cursoId = !empty($_GET['curso_id']) ? (int)$_GET['curso_id'] : null;
        $riscos = $this->planoModel->getDiagnosticoRisco($cursoId);
        Response::json(['success' => true, 'data' => $riscos]);
    }

    private function recalcularConformidades(): void {
        $planoId = (int)($_GET['plano_id'] ?? 0);
        if (!$planoId) {
            Response::error('ID do plano é obrigatório.');
        }

        $atualizados = $this->planoModel->recalcularConformidadesEmLote($planoId);
        Response::success("Conformidades recalculadas com sucesso ($atualizados linhas atualizadas).");
    }


    private function getCursos(): void {
        $cursos = $this->cursoModel->getAll();
        Response::json(['success' => true, 'data' => $cursos]);
    }

    private function getPlano(): void {
        $cursoId = (int)($_GET['curso_id'] ?? 0);
        $anoLectivo = $_GET['ano_lectivo'] ?? '2026/27';

        if (!$cursoId) {
            Response::error('ID do curso é obrigatório.');
        }

        $plano = $this->planoModel->getByCursoEAno($cursoId, $anoLectivo);
        if (!$plano) {
            Response::error('Plano não encontrado.', 404);
        }

        $linhas = $this->planoModel->getLinhasPlano($plano['id'], $anoLectivo);
        Response::json([
            'success' => true,
            'plano'   => $plano,
            'linhas'  => $linhas
        ]);
    }

    private function salvarLinha(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
        }

        if (!Auth::check()) {
            Response::error('Sessão não iniciada. Por favor efetue login.', 401);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $linhaId = (int)($input['linha_id'] ?? 0);

        if (!$linhaId) {
            Response::error('ID da linha é obrigatório.');
        }

        // Validação RBAC por curso
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT pc.curso_id FROM linhas_cobertura lc JOIN planos_cobertura pc ON lc.plano_id = pc.id WHERE lc.id = ? LIMIT 1");
        $stmt->execute([$linhaId]);
        $linhaInfo = $stmt->fetch();
        if ($linhaInfo && !Auth::canEditCourse((int)$linhaInfo['curso_id'])) {
            Response::error('Não tem permissão para alterar linhas de cobertura deste curso.', 403);
        }

        $res = $this->planoModel->updateLinha($linhaId, $input);
        if ($res) {
            // Retornar a linha atualizada para o JS actualizar apenas em memória
            $linhaAtualizada = $this->planoModel->getLinhaById($linhaId);
            Response::json([
                'success' => true,
                'message' => 'Linha atualizada com sucesso.',
                'linha'   => $linhaAtualizada
            ]);
        } else {
            Response::error('Falha ao atualizar linha.');
        }
    }

    private function replicarLinhaTurmas(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $planoId = (int)($input['plano_id'] ?? 0);
        $disciplinaId = (int)($input['disciplina_id'] ?? 0);
        $docenteId = !empty($input['docente_id']) ? (int)$input['docente_id'] : null;

        if (!$planoId || !$disciplinaId) {
            Response::error('Dados incompletos para replicação por turma.');
        }

        $res = $this->planoModel->applyDocenteToAllTurmasSameYear($planoId, $disciplinaId, $docenteId);
        if ($res) {
            Response::success('Docente atribuído a esta disciplina em todas as turmas do ano!');
        } else {
            Response::error('Falha ao replicar docente nas turmas.');
        }
    }

    private function alterarEstadoPlano(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $planoId = (int)($input['plano_id'] ?? 0);
        $novoEstado = $input['estado'] ?? '';
        $obs = $input['observacoes'] ?? null;

        if (!$planoId || !in_array($novoEstado, ['Rascunho', 'Em Elaboração', 'Submetido', 'Aprovado pelo Departamento', 'Validado', 'Devolvido'])) {
            Response::error('Dados inválidos para alteração de estado.');
        }

        $user = Auth::user();
        $perfil = $user['perfil'] ?? 'coordenador';

        // Validação de permissão RBAC por etapa
        if ($novoEstado === 'Aprovado pelo Departamento' && !in_array($perfil, ['chefe_departamento', 'admin'])) {
            Response::error('Apenas o Chefe de Departamento tem autorização para aprovar este plano nesta etapa.', 403);
        }

        if ($novoEstado === 'Validado' && !in_array($perfil, ['presidente', 'admin'])) {
            Response::error('Apenas a Presidência tem autorização soberana para validar este plano.', 403);
        }

        if ($novoEstado === 'Devolvido' && !in_array($perfil, ['chefe_departamento', 'presidente', 'admin'])) {
            Response::error('Apenas o Chefe de Departamento ou a Presidência podem devolver planos para retificação.', 403);
        }

        if ($novoEstado === 'Aprovado pelo Departamento') {
            $res = $this->planoModel->aprovarPeloDepartamento($planoId, $user['id'] ?? 1, $obs);
        } elseif ($novoEstado === 'Validado') {
            $res = $this->planoModel->validarPelaPresidencia($planoId, $user['id'] ?? 1, $obs);
        } elseif ($novoEstado === 'Submetido') {
            $res = $this->planoModel->submeterPlano($planoId, $user['id'] ?? 1);
        } elseif ($novoEstado === 'Devolvido') {
            $res = $this->planoModel->devolverPlano($planoId, $user['id'] ?? 1, $obs);
        } else {
            $res = $this->planoModel->updateEstadoPlano($planoId, $novoEstado, $obs, $user['id'] ?? null);
        }

        if ($res) {
            $planoObj = $this->planoModel->getLinhasPlano($planoId);
            $planoDb = ['id' => $planoId, 'curso_id' => $planoObj[0]['curso_id'] ?? 1, 'ano_lectivo' => '2026/27'];
            Notification::notifyStateChange($planoDb, $novoEstado, $obs, $user);

            Response::success("Estado do plano alterado para '$novoEstado' com sucesso.");
        } else {
            Response::error('Falha ao alterar estado do plano.');
        }
    }

    private function salvarDocente(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
        }

        if (!Auth::check()) {
            Response::error('Sessão não iniciada. Por favor efetue login.', 401);
        }

        if (!Auth::canEditDoc() && !Auth::canEditCV()) {
            Response::error('Apenas o perfil GRH ou Administração pode alterar os dados do docente.', 403);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $docenteId = (int)($input['docente_id'] ?? 0);

        if (!$docenteId) {
            Response::error('ID do docente é obrigatório.');
        }

        $res = $this->docenteModel->updatePerfilDocente($docenteId, $input);
        if ($res) {
            Response::success('Perfil do docente atualizado com sucesso.');
        } else {
            Response::error('Falha ao atualizar perfil do docente.');
        }
    }

    private function uploadDocumentoDocente(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
        }

        if (!Auth::check()) {
            Response::error('Sessão não iniciada. Por favor efetue login.', 401);
        }

        if (!Auth::canEditDoc()) {
            Response::error('Docentes/Documentos é preenchido pelo perfil GRH.', 403);
        }

        $docenteId = (int)($_POST['docente_id'] ?? 0);
        $tipo      = trim($_POST['tipo'] ?? 'cv');

        if (!$docenteId) {
            Response::error('ID do docente é obrigatório.');
        }

        if (empty($_FILES['ficheiro']) || $_FILES['ficheiro']['error'] !== UPLOAD_ERR_OK) {
            Response::error('Nenhum ficheiro válido enviado.');
        }

        $file = $_FILES['ficheiro'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'png'];

        if (!in_array($ext, $allowedExts)) {
            Response::error('Tipo de ficheiro não permitido. Aceites: PDF, DOC, DOCX, JPG, PNG.');
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            Response::error('O ficheiro excede o tamanho máximo de 10 MB.');
        }

        $uploadDir = __DIR__ . '/../../public/uploads/docentes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = "doc_{$docenteId}_{$tipo}_" . time() . ".{$ext}";
        $destPath = $uploadDir . $fileName;
        $relPath  = "uploads/docentes/" . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $userId = Auth::user()['id'] ?? null;
            $res = $this->docenteModel->saveDocumento($docenteId, $tipo, $relPath, $userId);
            if ($res) {
                Response::success('Documento carregado com sucesso!');
            } else {
                Response::error('Falha ao registar documento na base de dados.');
            }
        } else {
            Response::error('Falha ao guardar ficheiro no servidor.');
        }
    }

    private function getDocumentosDocente(): void {
        $docenteId = (int)($_GET['docente_id'] ?? 0);
        if (!$docenteId) {
            Response::error('ID do docente é obrigatório.');
        }
        $docs = $this->docenteModel->getDocumentos($docenteId);
        Response::json(['success' => true, 'data' => $docs]);
    }

    private function getStats(): void {
        $anoLectivo = $_GET['ano_lectivo'] ?? '2026/27';
        $stats = $this->planoModel->getConsolidadosStats($anoLectivo);
        Response::json(['success' => true, 'data' => $stats]);
    }

    private function getDashboardStats(): void {
        $anoLectivo = $_GET['ano_lectivo'] ?? '2026/27';
        $data = [
            'qualificacoes' => $this->docenteModel->getDashboardQualificacoes(),
            'pilares'       => $this->docenteModel->getDashboardPilares(),
            'sobrecarga'    => $this->docenteModel->getDashboardSobrecargaPartilha(),
            'cursos'        => $this->planoModel->getDashboardConformidadeCursos($anoLectivo)
        ];
        Response::json(['success' => true, 'data' => $data]);
    }

    private function switchRole(): void {
        $role = $_GET['role'] ?? 'admin';
        $wasSuperAdmin = (!empty($_SESSION['super_admin_logged_in']) || !empty($_SESSION['master_admin_session']) || !empty($_SESSION['is_super_admin']) || ($_SESSION['user']['email'] ?? '') === 'evaristo.adriano@ispsn.org');
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM utilizadores WHERE perfil = ? LIMIT 1");
        $stmt->execute([$role]);
        $user = $stmt->fetch();
        if ($user) {
            Auth::login($user, true);
            if ($wasSuperAdmin) {
                $_SESSION['super_admin_logged_in'] = true;
                $_SESSION['master_admin_session']  = true;
                $_SESSION['is_super_admin']        = true;
            }
            Response::success("Perfil alterado para $role");
        } else {
            Response::error('Perfil não encontrado');
        }
    }

    private function salvarUtilizador(): void {
        if (!Auth::hasRole('admin')) {
            Response::error('Apenas o Administrador pode gerir utilizadores e perfis.', 403);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $userId = (int)($input['id'] ?? 0);
        $perfil = $input['perfil'] ?? 'coordenador';
        $cursoId = !empty($input['curso_id']) ? (int)$input['curso_id'] : null;
        $activo = isset($input['activo']) ? (int)$input['activo'] : 1;
        $nome = !empty($input['nome']) ? trim($input['nome']) : null;
        $email = !empty($input['email']) ? trim(strtolower($input['email'])) : null;

        if (!$userId) {
            Response::error('ID de utilizador inválido.');
        }

        $db = Database::getInstance();
        if ($nome && $email) {
            $stmt = $db->prepare("UPDATE utilizadores SET nome = ?, email = ?, perfil = ?, curso_id = ?, activo = ? WHERE id = ?");
            $res = $stmt->execute([$nome, $email, $perfil, $cursoId, $activo, $userId]);
        } else {
            $stmt = $db->prepare("UPDATE utilizadores SET perfil = ?, curso_id = ?, activo = ? WHERE id = ?");
            $res = $stmt->execute([$perfil, $cursoId, $activo, $userId]);
        }

        if ($res) {
            $statusText = $activo ? 'ativado' : 'desativado';
            Response::success("Utilizador atualizado com sucesso (Estado: {$statusText}).");
        } else {
            Response::error('Falha ao atualizar utilizador.');
        }
    }

    private function criarUtilizador(): void {
        if (!Auth::hasRole('admin')) {
            Response::error('Apenas o Administrador pode adicionar utilizadores.', 403);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $nome = trim($input['nome'] ?? '');
        $email = trim(strtolower($input['email'] ?? ''));
        $perfil = $input['perfil'] ?? 'coordenador';
        $cursoId = !empty($input['curso_id']) ? (int)$input['curso_id'] : null;

        if (empty($nome) || empty($email)) {
            Response::error('Nome e Email são obrigatórios.');
        }

        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo) VALUES (?, ?, NULL, ?, ?, 1)");
            $stmt->execute([$nome, $email, $perfil, $cursoId]);
            Response::success('Novo utilizador pré-cadastrado com sucesso! A conta fica pendente de Primeiro Acesso.');
        } catch (\Exception $e) {
            Response::error('Erro ao registar utilizador: ' . $e->getMessage());
        }
    }

    private function ativarDocentePerfil(): void {
        if (!Auth::hasRole('admin')) {
            Response::error('Apenas o Administrador pode ativar perfis de docentes.', 403);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $docenteId = (int)($input['docente_id'] ?? 0);
        $email     = trim(strtolower($input['email'] ?? ''));
        $perfil    = trim($input['perfil'] ?? 'coordenador');
        $cursoId   = !empty($input['curso_id']) ? (int)$input['curso_id'] : null;

        if (!$docenteId) {
            Response::error('Por favor selecione um docente válido.');
        }
        if (empty($email)) {
            Response::error('Por favor introduza o e-mail corporativo do docente.');
        }

        $db = Database::getInstance();
        $stmtDoc = $db->prepare("SELECT * FROM docentes WHERE id = ? LIMIT 1");
        $stmtDoc->execute([$docenteId]);
        $docente = $stmtDoc->fetch();

        if (!$docente) {
            Response::error('Docente não encontrado no sistema.');
        }

        $nomeDocente = $docente['nome'];

        // Atualizar e-mail do docente na tabela de docentes
        $stmtUpDoc = $db->prepare("UPDATE docentes SET email = ? WHERE id = ?");
        $stmtUpDoc->execute([$email, $docenteId]);

        // Verificar se já existe conta em utilizadores com este e-mail
        $stmtCheck = $db->prepare("SELECT id FROM utilizadores WHERE email = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        $userExist = $stmtCheck->fetch();

        if ($userExist) {
            // Atualizar conta existente para o novo perfil
            $stmtUp = $db->prepare("UPDATE utilizadores SET nome = ?, perfil = ?, curso_id = ?, activo = 1 WHERE id = ?");
            $stmtUp->execute([$nomeDocente, $perfil, $cursoId, $userExist['id']]);
        } else {
            // Inserir nova conta pendente de Primeiro Acesso (senha_hash = NULL)
            $stmtIns = $db->prepare("INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo) VALUES (?, ?, NULL, ?, ?, 1)");
            $stmtIns->execute([$nomeDocente, $email, $perfil, $cursoId]);
        }

        $perfilNomes = [
            'coordenador' => 'Coordenador de Curso',
            'secretario_geral' => 'Secretário-Geral',
            'presidente' => 'Presidência',
            'gestor_academico' => 'Gestão Académica',
            'grh' => 'GRH',
            'admin' => 'Administração'
        ];
        $nomePerfilFormat = $perfilNomes[$perfil] ?? $perfil;

        Response::success("Perfil de {$nomePerfilFormat} ativado com sucesso para {$nomeDocente}! O docente já pode aceder ao sistema com o e-mail {$email} no Primeiro Acesso.");
    }

    private function importarUtilizadores(): void {
        if (!Auth::hasRole('admin')) {
            Response::error('Apenas o Administrador pode importar utilizadores.', 403);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $usersList = $input['utilizadores'] ?? [];

        if (empty($usersList) || !is_array($usersList)) {
            Response::error('Lista de utilizadores para importar está vazia ou é inválida.');
        }

        $db = Database::getInstance();
        $stmtCursos = $db->query("SELECT id, codigo FROM cursos")->fetchAll();
        $cursosMap = [];
        foreach ($stmtCursos as $c) {
            $cursosMap[strtoupper($c['codigo'])] = $c['id'];
        }

        $stmtInsert = $db->prepare("
            INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo)
            VALUES (?, ?, NULL, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                nome = VALUES(nome),
                perfil = VALUES(perfil),
                curso_id = VALUES(curso_id)
        ");

        $importedCount = 0;
        foreach ($usersList as $u) {
            $nome = trim($u['nome'] ?? '');
            $email = trim(strtolower($u['email'] ?? ''));
            $perfil = trim($u['perfil'] ?? 'coordenador');
            $cursoCodigo = strtoupper(trim($u['curso_codigo'] ?? ''));
            $cursoId = $cursosMap[$cursoCodigo] ?? (!empty($u['curso_id']) ? (int)$u['curso_id'] : null);

            if (!empty($nome) && !empty($email)) {
                $stmtInsert->execute([$nome, $email, $perfil, $cursoId]);
                $importedCount++;
            }
        }

        Response::success("$importedCount utilizadores pré-cadastrados com sucesso (pendentes de Primeiro Acesso).");
    }

    private function salvarParametrosConfig(): void {
        if (!Auth::hasRole('admin')) {
            Response::error('Apenas o Administrador pode alterar os parâmetros gerais.', 403);
        }
        Response::success('Parâmetros gerais do sistema e regras de conformidade atualizados com sucesso.');
    }

    private function salvarAnoLectivo(): void {
        if (!Auth::check()) {
            Response::error('Sessão não iniciada.', 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $ano   = trim($input['ano_lectivo'] ?? ($_GET['ano_lectivo'] ?? ''));

        if (empty($ano)) {
            Response::error('Por favor especifique um ano lectivo válido.');
            return;
        }

        $_SESSION['ano_lectivo_activo'] = $ano;
        Response::json([
            'success' => true,
            'ano_lectivo' => $ano,
            'message' => "Ano lectivo ativo alterado com sucesso para {$ano}."
        ]);
    }

    private function executarRollOver(): void {
        if (!Auth::hasRole('admin')) {
            Response::error('Apenas o Administrador pode executar a transição de ano lectivo (Roll-Over).', 403);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $anoOrigem  = $input['ano_origem'] ?? '2026/27';
        $anoDestino = $input['ano_destino'] ?? '2027/28';

        $userId = Auth::user()['id'] ?? null;
        $res = $this->planoModel->executarRollOver($anoOrigem, $anoDestino, $userId);

        if (!empty($res['success'])) {
            Response::json($res);
        } else {
            Response::error($res['message'] ?? 'Falha ao executar Roll-Over.');
        }
    }

    private function sincronizarGestaoEscolar(): void {
        if (!Auth::check()) {
            Response::error('Sessão não iniciada.', 401);
        }

        require_once __DIR__ . '/../services/GestaoEscolarSyncService.php';
        $syncService = new GestaoEscolarSyncService();
        $res = $syncService->syncAll();

        if (!empty($res['success'])) {
            Response::json($res);
        } else {
            Response::error($res['message'] ?? 'Falha ao sincronizar com o Gestão Escolar.');
        }
    }

    private function exportarExcel(): void {
        $cursoId = (int)($_GET['curso_id'] ?? 1);
        $anoLectivo = $_GET['ano_lectivo'] ?? '2026/27';

        $curso = $this->cursoModel->getById($cursoId);
        $plano = $this->planoModel->getByCursoEAno($cursoId, $anoLectivo);

        if (!$curso || !$plano) {
            Response::error('Curso ou Plano de Cobertura não encontrado.', 404);
        }

        $linhas = $this->planoModel->getLinhasPlano($plano['id'], $anoLectivo);

        $filename = "Plano_Cobertura_" . preg_replace('/[^A-Za-z0-9_]/', '_', $curso['nome']) . "_{$anoLectivo}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // BOM para abrir acentos no Excel sem desformatar
        fwrite($out, "\xEF\xBB\xBF");

        // Cabeçalho CSV
        fputcsv($out, [
            '#', 'Curso', 'Ano Lectivo', 'Turma', 'Ano Curricular', 'Semestre',
            'Unidade Curricular', 'Carga Horária Semanal', 'Docente Atribuído',
            'Grau Académico', 'Especialidade', 'INAAREES', 'Capacitação Pedagógica',
            'Conformidade', 'Justificação', 'Regime', 'Parecer'
        ], ';');

        $idx = 1;
        foreach ($linhas as $l) {
            fputcsv($out, [
                $idx++,
                $curso['nome'],
                $anoLectivo,
                $l['turma_nome'] ?? ('TURMA-' . $l['ano_curricular'] . 'A'),
                $l['ano_curricular'] . 'º Ano',
                $l['semestre'],
                $l['disciplina_nome'],
                $l['carga_horaria_semanal'] ?? 0,
                $l['docente_nome'] ?? 'Sem Docente Atribuído',
                $l['docente_grau'] ?? '—',
                $l['docente_especialidade'] ?? '—',
                $l['docente_inaarees'] ?? '—',
                $l['docente_agregacao'] ?? '—',
                $l['conformidade'] ?? 'Por verificar',
                $l['justificacao'] ?? '—',
                $l['regime'] ?? '—',
                $l['parecer'] ?? '—'
            ], ';');
        }

        fclose($out);
        exit;
    }

    private function getHistoricoPlano(): void {
        $planoId = (int)($_GET['plano_id'] ?? 0);
        $cursoId = (int)($_GET['curso_id'] ?? 0);
        $anoLectivo = $_GET['ano_lectivo'] ?? '2026/27';

        if (!$planoId && $cursoId) {
            $plano = $this->planoModel->getByCursoEAno($cursoId, $anoLectivo);
            $planoId = $plano ? (int)$plano['id'] : 0;
        }

        if (!$planoId) {
            Response::error('ID do plano ou curso é obrigatório.');
        }

        $historico = $this->planoModel->getHistoricoAprovacoes($planoId);
        Response::json(['success' => true, 'data' => $historico]);
    }

    /**
     * GET ?api=cv_carregar&docente_id=X
     * Retorna o CV completo (docentes + cvs_estruturados) normalizado num único objeto JSON.
     */
    private function carregarCV(): void {
        $docenteId = (int)($_GET['docente_id'] ?? 0);
        if (!$docenteId) {
            Response::error('docente_id é obrigatório.', 400);
            return;
        }
        $cv = $this->docenteModel->getCVCompleto($docenteId);
        if (empty($cv)) {
            Response::error('Docente não encontrado.', 404);
            return;
        }
        Response::json(['success' => true, 'data' => $cv]);
    }

    /**
     * POST ?api=cv_salvar
     * Grava o CV completo em transação (docentes + cvs_estruturados) e propaga conformidade.
     */
    private function salvarCV(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
            return;
        }
        if (!Auth::check()) {
            Response::error('Sessão não iniciada. Por favor efetue login.', 401);
            return;
        }
        // RBAC: apenas GRH e Admin podem editar CV
        if (!Auth::hasRole(['grh', 'admin'])) {
            Response::error('Apenas o perfil GRH ou Admin pode guardar o CV estruturado.', 403);
            return;
        }

        $input     = json_decode(file_get_contents('php://input'), true) ?? [];
        $docenteId = (int)($input['docente_id'] ?? 0);
        if (!$docenteId) {
            Response::error('docente_id é obrigatório.', 400);
            return;
        }

        $result = $this->docenteModel->saveCVCompleto($docenteId, $input);

        if ($result['success']) {
            $msg = 'CV guardado com sucesso.';
            if ($result['linhas_atualizadas'] > 0) {
                $msg .= " {$result['linhas_atualizadas']} linha(s) de planos de cobertura atualizadas automaticamente.";
            }
            Response::json(['success' => true, 'message' => $msg, 'linhas_atualizadas' => $result['linhas_atualizadas']]);
        } else {
            Response::error('Erro ao guardar o CV: ' . ($result['error'] ?? 'Erro desconhecido.'), 500);
        }
    }

    /**
     * GET ?api=turmas&curso_id=X&ano_curricular=Y
     */
    private function getTurmas(): void {
        $cursoId = (int)($_GET['curso_id'] ?? 0);
        $anoCurricular = !empty($_GET['ano_curricular']) ? (int)$_GET['ano_curricular'] : null;

        if (!$cursoId) {
            Response::error('curso_id é obrigatório.');
            return;
        }

        $turmas = $this->cursoModel->getTurmasDetalhadas($cursoId, $anoCurricular);
        Response::json(['success' => true, 'data' => $turmas]);
    }

    /**
     * POST ?api=turma_salvar
     */
    private function salvarTurma(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
            return;
        }

        if (!Auth::check()) {
            Response::error('Sessão não iniciada. Por favor efetue login.', 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $turmaId = trim($input['turma_id'] ?? '');

        if (!$turmaId) {
            Response::error('turma_id é obrigatório.');
            return;
        }

        // Validação RBAC por curso
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT d.curso_id FROM turmas t JOIN disciplinas d ON t.disciplina_id = d.id WHERE t.id = ? LIMIT 1");
        $stmt->execute([$turmaId]);
        $cursoId = (int)$stmt->fetchColumn();

        if ($cursoId && !Auth::canEditCourse($cursoId)) {
            Response::error('Não tem permissão para alterar os indicadores das turmas deste curso.', 403);
            return;
        }

        $res = $this->cursoModel->updateTurmaIndicadores($turmaId, $input);
        if ($res) {
            Response::success('Indicadores da turma atualizados com sucesso.');
        } else {
            Response::error('Falha ao atualizar indicadores da turma.');
        }
    }

    /**
     * GET ?api=disciplinas&curso_id=X
     */
    private function getDisciplinas(): void {
        $cursoId = (int)($_GET['curso_id'] ?? 0);
        if (!$cursoId) {
            Response::error('curso_id é obrigatório.');
            return;
        }

        $disciplinas = $this->cursoModel->getDisciplinasByCurso($cursoId);
        Response::json(['success' => true, 'data' => $disciplinas]);
    }

    /**
     * POST ?api=disciplina_salvar
     */
    private function salvarDisciplina(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
            return;
        }

        if (!Auth::check()) {
            Response::error('Sessão não iniciada. Por favor efetue login.', 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $cursoId = (int)($input['curso_id'] ?? 0);

        if (!$cursoId) {
            Response::error('curso_id é obrigatório.');
            return;
        }

        if (!Auth::canEditCourse($cursoId)) {
            Response::error('Não tem permissão para alterar a matriz curricular deste curso.', 403);
            return;
        }

        $res = $this->cursoModel->saveDisciplina($input);
        if ($res) {
            Response::success('Disciplina da matriz curricular guardada com sucesso.');
        } else {
            Response::error('Falha ao guardar disciplina.');
        }
    }
}

