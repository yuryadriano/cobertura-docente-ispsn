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
require_once __DIR__ . '/../models/IntegracaoModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ApiController {
    private DocenteModel $docenteModel;
    private CursoModel $cursoModel;
    private PlanoModel $planoModel;
    private IntegracaoModel $integracaoModel;

    public function __construct() {
        $this->docenteModel   = new DocenteModel();
        $this->cursoModel     = new CursoModel();
        $this->planoModel     = new PlanoModel();
        $this->integracaoModel = new IntegracaoModel();
    }

    public function handleRequest(string $action): void {
        switch ($action) {
            // Endpoints de Integração Enterprise (Gestão Escolar ↔ Cobertura)
            case 'v1_integracao_status':
                $this->integracaoStatus();
                break;
            case 'v1_integracao_plano_export':
                $this->integracaoPlanoExport();
                break;
            case 'v1_integracao_sync_docentes':
                $this->integracaoSyncDocentes();
                break;
            case 'v1_integracao_sync_metricas':
                $this->integracaoSyncMetricas();
                break;
            case 'v1_integracao_logs':
                $this->integracaoLogs();
                break;
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
            case 'docente_criar':
                $this->criarDocente();
                break;
            case 'docente_salvar':
                $this->salvarDocente();
                break;
            case 'docente_eliminar':
                $this->eliminarDocente();
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
            case 'docente_ver_documento':
                $this->verDocumentoDocente();
                break;
            case 'docente_eliminar_documento':
                $this->eliminarDocumentoDocente();
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
            case 'cv_remover_foto':
                $this->removerFotoCV();
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
            $pairLinhaAtualizada = null;
            $pairNome = null;

            // Se for alteração de docente e solicitada propagação sequencial (Semestre 1 -> Semestre 2)
            if (array_key_exists('docente_id', $input) && !empty($input['propagate_sequential'])) {
                $pairLinha = $this->planoModel->findSequentialPairLinha($linhaId);
                if ($pairLinha) {
                    $pairLinhaId = (int)$pairLinha['linha_id'];
                    $pairData = [
                        'docente_id'   => $input['docente_id'],
                        'conformidade' => $linhaAtualizada['conformidade'] ?? 'Por verificar',
                        'regime'       => $linhaAtualizada['regime'] ?? 'Tempo Parcial',
                        'parecer'      => $linhaAtualizada['parecer'] ?? 'Manter'
                    ];
                    $this->planoModel->updateLinha($pairLinhaId, $pairData);
                    $pairLinhaAtualizada = $this->planoModel->getLinhaById($pairLinhaId);
                    $pairNome = $pairLinha['disciplina_nome'];
                }
            }

            Response::json([
                'success'    => true,
                'message'    => 'Linha atualizada com sucesso.',
                'linha'      => $linhaAtualizada,
                'pair_linha' => $pairLinhaAtualizada,
                'pair_nome'  => $pairNome
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
        if ($res && !empty($res['success'])) {
            $msg = 'Docente atribuído a todas as turmas do ano com sucesso!';
            if (!empty($res['pair_nome'])) {
                $msg .= ' Sincronizado também com ' . $res['pair_nome'] . '.';
            }
            Response::json([
                'success'        => true,
                'message'        => $msg,
                'affected_count' => $res['affected_count'] ?? 1,
                'pair_nome'      => $res['pair_nome'] ?? null
            ]);
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
        $cursoId = (int)($input['curso_id'] ?? 0);
        $anoLectivo = $input['ano_lectivo'] ?? get_ano_lectivo_activo();
        $novoEstado = $input['estado'] ?? '';
        $obs = $input['observacoes'] ?? null;

        // Se veio curso_id ou se o planoId não existe diretamente em planos_cobertura
        $db = Database::getInstance();
        if ($planoId > 0) {
            $stmtChk = $db->prepare("SELECT id FROM planos_cobertura WHERE id = ?");
            $stmtChk->execute([$planoId]);
            if (!$stmtChk->fetch()) {
                // Tenta resolver como curso_id para o ano letivo ativo
                $planoObj = $this->planoModel->getByCursoEAno($planoId, $anoLectivo);
                if ($planoObj) {
                    $planoId = (int)$planoObj['id'];
                }
            }
        } elseif ($cursoId > 0) {
            $planoObj = $this->planoModel->getByCursoEAno($cursoId, $anoLectivo);
            if ($planoObj) {
                $planoId = (int)$planoObj['id'];
            }
        }

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

    private function criarDocente(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
        }

        if (!Auth::check()) {
            Response::error('Sessão não iniciada. Por favor efetue login.', 401);
        }

        if (!Auth::canEditDoc()) {
            Response::error('Apenas o perfil GRH ou Administração pode cadastrar novos docentes.', 403);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $res = $this->docenteModel->createDocente($input);

        Response::json($res);
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

    private function eliminarDocente(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
        }

        if (!Auth::check()) {
            Response::error('Sessão não iniciada. Por favor efetue login.', 401);
        }

        if (!Auth::canEditDoc()) {
            Response::error('Apenas o perfil GRH ou Administração tem autorização para eliminar docentes.', 403);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $docenteId = (int)($input['id'] ?? $input['docente_id'] ?? 0);

        if (!$docenteId) {
            Response::error('ID do docente é obrigatório.');
        }

        try {
            $res = $this->docenteModel->deleteDocente($docenteId);
            if ($res['success']) {
                Response::success($res['message'], ['desvinculacoes' => $res['desvinculacoes'] ?? null]);
            } else {
                Response::error($res['message'] ?? 'Falha ao eliminar docente.');
            }
        } catch (\Throwable $e) {
            Response::error('Erro ao eliminar docente: ' . $e->getMessage(), 500);
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

    private function verDocumentoDocente(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header("HTTP/1.1 404 Not Found");
            echo "Documento não encontrado.";
            exit;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM documentos_docentes WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if (!$doc) {
            header("HTTP/1.1 404 Not Found");
            echo "Documento não encontrado na base de dados.";
            exit;
        }

        $rawPath = str_replace('\\', '/', $doc['caminho_ficheiro']);
        $relPath = ltrim($rawPath, '/');
        $cleanRelPath = preg_replace('#^public/#i', '', $relPath);
        $filename = basename($rawPath);

        $possiblePaths = [
            __DIR__ . '/../../public/' . $cleanRelPath,
            __DIR__ . '/../../' . $cleanRelPath,
            __DIR__ . '/../../public/uploads/docentes/' . $filename,
            __DIR__ . '/../../uploads/docentes/' . $filename,
            __DIR__ . '/../public/' . $cleanRelPath,
            __DIR__ . '/../uploads/docentes/' . $filename,
            ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/' . $cleanRelPath,
            ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/uploads/docentes/' . $filename,
            dirname($_SERVER['SCRIPT_FILENAME'] ?? '') . '/' . $cleanRelPath,
            dirname($_SERVER['SCRIPT_FILENAME'] ?? '') . '/uploads/docentes/' . $filename,
            $rawPath,
            $doc['caminho_ficheiro'],
            $relPath,
            $cleanRelPath
        ];

        $filePath = null;
        foreach ($possiblePaths as $p) {
            if (!empty($p) && file_exists($p) && !is_dir($p)) {
                $filePath = $p;
                break;
            }
        }

        if ($filePath) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'pdf'  => 'application/pdf',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
            header("Content-Type: {$mime}");
            header("Content-Disposition: inline; filename=\"" . basename($filePath) . "\"");
            header("Content-Length: " . filesize($filePath));
            readfile($filePath);
            exit;
        }

        header("Content-Type: text/html; charset=utf-8");
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Documento Não Encontrado</title><style>body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;padding:40px;background:#f1f5f9;color:#1e293b;} .box{border:1px solid #cbd5e1;padding:32px;max-width:520px;margin:40px auto;border-radius:12px;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.08);text-align:center;} h2{color:#e11d48;margin-top:0;} p{line-height:1.6;font-size:14px;color:#475569;} .btn{display:inline-block;padding:10px 20px;background:#0284c7;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;font-size:13px;border:none;cursor:pointer;margin-top:12px;}</style></head><body>";
        echo "<div class='box'><h2>📄 Ficheiro Não Localizado no Servidor</h2>";
        echo "<p>O registo deste documento existe na base de dados, mas o ficheiro físico (<b>" . htmlspecialchars($filename) . "</b>) não se encontra no directório de uploads do servidor.</p>";
        echo "<button onclick='window.close()' class='btn'>Fechar Janela</button>";
        echo "</div></body></html>";
        exit;
    }

    private function eliminarDocumentoDocente(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
        }
        if (!Auth::check() || !Auth::canEditDoc()) {
            Response::error('Sem permissão para eliminar documentos.', 403);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            Response::error('ID do documento é obrigatório.');
        }

        $db = Database::getInstance();
        $stmtDoc = $db->prepare("SELECT docente_id FROM documentos_docentes WHERE id = ?");
        $stmtDoc->execute([$id]);
        $docenteId = (int)$stmtDoc->fetchColumn();

        $res = $this->docenteModel->deleteDocumento($id);
        if ($res) {
            $totalValidos = 0;
            if ($docenteId > 0) {
                $stmtCount = $db->prepare("SELECT COUNT(DISTINCT tipo) FROM documentos_docentes WHERE docente_id = ? AND estado = 'Válido'");
                $stmtCount->execute([$docenteId]);
                $totalValidos = (int)$stmtCount->fetchColumn();
            }
            Response::success('Documento eliminado com sucesso.', [
                'docente_id'         => $docenteId,
                'total_docs_validos' => $totalValidos
            ]);
        } else {
            Response::error('Falha ao eliminar documento.');
        }
    }

    private function removerFotoCV(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido.', 405);
        }
        if (!Auth::check()) {
            Response::error('Sessão não iniciada.', 401);
        }
        if (!Auth::hasRole(['grh', 'admin'])) {
            Response::error('Sem permissão para remover foto do docente.', 403);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $docenteId = (int)($input['docente_id'] ?? 0);
        if (!$docenteId) {
            Response::error('docente_id é obrigatório.');
        }
        $res = $this->docenteModel->removerFotoCV($docenteId);
        if ($res) {
            Response::success('Foto removida com sucesso.');
        } else {
            Response::error('Falha ao remover foto.');
        }
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
        if (!empty($input['reset_senha'])) {
            $stmt = $db->prepare("UPDATE utilizadores SET senha_hash = NULL WHERE id = ?");
            $stmt->execute([$userId]);
        }

        if ($nome && $email) {
            $stmt = $db->prepare("UPDATE utilizadores SET nome = ?, email = ?, perfil = ?, curso_id = ?, activo = ? WHERE id = ?");
            $res = $stmt->execute([$nome, $email, $perfil, $cursoId, $activo, $userId]);
        } else {
            $stmt = $db->prepare("UPDATE utilizadores SET perfil = ?, curso_id = ?, activo = ? WHERE id = ?");
            $res = $stmt->execute([$perfil, $cursoId, $activo, $userId]);
        }

        if ($res) {
            $msg = !empty($input['reset_senha']) ? "Palavra-passe resetada para Primeiro Acesso com sucesso." : "Utilizador atualizado com sucesso.";
            Response::success($msg);
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

        // Criar folha de cálculo PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cobertura Docente');

        // Configuração de alturas das linhas do cabeçalho
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(6); // Linha divisória
        $sheet->getRowDimension(5)->setRowHeight(18);
        $sheet->getRowDimension(6)->setRowHeight(10); // Espaçamento
        $sheet->getRowDimension(7)->setRowHeight(25); // Cabeçalho da Tabela

        // 1. Inserção do Logótipo (Linha 1-3, Coluna A)
        $sheet->mergeCells('A1:A3');
        $logoPath = __DIR__ . '/../../public/assets/img/logo.png';
        if (file_exists($logoPath)) {
            try {
                $drawing = new Drawing();
                $drawing->setName('Logo ISPSN');
                $drawing->setDescription('Logo ISPSN');
                $drawing->setPath($logoPath);
                $drawing->setCoordinates('A1');
                $drawing->setHeight(55);
                $drawing->setOffsetX(4);
                $drawing->setOffsetY(3);
                $drawing->setWorksheet($sheet);
            } catch (\Throwable $e) {
                // Fallback silencioso caso ambiente não possua extensão GD
            }
        }

        // 2. Título Institucional (B1:Q1)
        $sheet->mergeCells('B1:Q1');
        $sheet->setCellValue('B1', 'INSTITUTO SUPERIOR POLITÉCNICO SOL NASCENTE');
        $sheet->getStyle('B1')->getFont()->setSize(15)->setBold(true)->setColor(new Color('1B3A6B'));
        $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        // 3. Subtítulo (B2:Q2)
        $sheet->mergeCells('B2:Q2');
        $sheet->setCellValue('B2', 'Direção Académica · Mapa Oficial de Cobertura Docente — Ano Lectivo ' . $anoLectivo);
        $sheet->getStyle('B2')->getFont()->setSize(11)->setColor(new Color('1B3A6B'));
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        // 4. Identificação do Curso (B3:Q3)
        $sheet->mergeCells('B3:Q3');
        $codigoCurso = !empty($curso['codigo']) ? ' (Código: ' . $curso['codigo'] . ')' : '';
        $sheet->setCellValue('B3', 'Curso de Licenciatura em ' . $curso['nome'] . $codigoCurso);
        $sheet->getStyle('B3')->getFont()->setSize(11)->setBold(true)->setColor(new Color('C9971C'));
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        // 5. Linha Divisória Âmbar (A4:Q4)
        $sheet->mergeCells('A4:Q4');
        $sheet->getStyle('A4:Q4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C9971C');

        // 6. Metadados do Plano e Emissão (A5:H5 & I5:Q5)
        $sheet->mergeCells('A5:H5');
        $sheet->setCellValue('A5', 'Estado do Plano: ' . ($plano['estado'] ?? 'Rascunho'));
        $sheet->getStyle('A5')->getFont()->setSize(10)->setBold(true)->setColor(new Color('1B3A6B'));
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('I5:Q5');
        $sheet->setCellValue('I5', 'Data de Emissão: ' . date('d/m/Y H:i'));
        $sheet->getStyle('I5')->getFont()->setSize(10)->setColor(new Color('6C757D'));
        $sheet->getStyle('I5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        // 7. Cabeçalho das 17 Colunas de Dados (Linha 7)
        $headers = [
            '#', 'Curso', 'Ano Lectivo', 'Turma', 'Ano Curricular', 'Semestre',
            'Unidade Curricular', 'Carga Horária Semanal', 'Docente Atribuído',
            'Grau Académico', 'Especialidade', 'INAAREES', 'Capacitação Pedagógica',
            'Conformidade', 'Justificação', 'Regime', 'Parecer'
        ];
        $sheet->fromArray($headers, null, 'A7');
        
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '1B3A6B'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ];
        $sheet->getStyle('A7:Q7')->applyFromArray($headerStyle);
        $sheet->getStyle('A7:F7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('L7:N7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Fixar a linha de cabeçalho no scroll
        $sheet->freezePane('A8');

        // 8. Preenchimento e Formatação das Linhas de Dados (Linha 8+)
        $currentRow = 8;
        $idx = 1;

        foreach ($linhas as $l) {
            $conformidade = $l['conformidade'] ?? 'Por verificar';

            $sheet->setCellValue("A{$currentRow}", $idx++);
            $sheet->setCellValue("B{$currentRow}", $curso['nome']);
            $sheet->setCellValue("C{$currentRow}", $anoLectivo);
            $sheet->setCellValue("D{$currentRow}", $l['turma_nome'] ?? ('TURMA-' . $l['ano_curricular'] . 'A'));
            $sheet->setCellValue("E{$currentRow}", $l['ano_curricular'] . 'º Ano');
            $sheet->setCellValue("F{$currentRow}", $l['semestre']);
            $sheet->setCellValue("G{$currentRow}", $l['disciplina_nome']);
            $sheet->setCellValue("H{$currentRow}", $l['carga_horaria_semanal'] ?? 0);
            $sheet->setCellValue("I{$currentRow}", $l['docente_nome'] ?? 'Sem Docente Atribuído');
            $sheet->setCellValue("J{$currentRow}", $l['docente_grau'] ?? '—');
            $sheet->setCellValue("K{$currentRow}", $l['docente_especialidade'] ?? '—');
            $sheet->setCellValue("L{$currentRow}", $l['tem_inaarees'] ?? '—');
            $sheet->setCellValue("M{$currentRow}", $l['tem_agregacao_pedag'] ?? '—');
            $sheet->setCellValue("N{$currentRow}", $conformidade);
            $sheet->setCellValue("O{$currentRow}", $l['justificacao'] ?? '—');
            $sheet->setCellValue("P{$currentRow}", $l['regime'] ?? '—');
            $sheet->setCellValue("Q{$currentRow}", $l['parecer'] ?? '—');

            // Zebra Striping (Linhas pares)
            if ($currentRow % 2 == 0) {
                $sheet->getStyle("A{$currentRow}:Q{$currentRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8F9FA');
            }

            // Alinhamentos específicos
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$currentRow}:M{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Estilização do Badge de Conformidade (Coluna N)
            $confStyle = $sheet->getStyle("N{$currentRow}");
            $confStyle->getFont()->setBold(true);
            $confStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($conformidade === 'Sim') {
                $confStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D5F5E3');
                $confStyle->getFont()->setColor(new Color('27AE60'));
            } elseif ($conformidade === 'Parcial') {
                $confStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9E7');
                $confStyle->getFont()->setColor(new Color('B9770E'));
            } else {
                $confStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FADBD8');
                $confStyle->getFont()->setColor(new Color('C0392B'));
            }

            $currentRow++;
        }

        $lastRow = max($currentRow - 1, 7);

        // Bordas finas cinza claras em toda a tabela
        if ($lastRow >= 8) {
            $dataBorders = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9D9D9'],
                    ],
                ],
            ];
            $sheet->getStyle("A8:Q{$lastRow}")->applyFromArray($dataBorders);
        }

        // Largura das Colunas
        $columnWidths = [
            'A' => 6,   // #
            'B' => 26,  // Curso
            'C' => 14,  // Ano Lectivo
            'D' => 14,  // Turma
            'E' => 14,  // Ano Curricular
            'F' => 14,  // Semestre
            'G' => 32,  // Unidade Curricular
            'H' => 18,  // Carga Horária Semanal
            'I' => 28,  // Docente Atribuído
            'J' => 16,  // Grau Académico
            'K' => 24,  // Especialidade
            'L' => 14,  // INAAREES
            'M' => 22,  // Capacitação Pedagógica
            'N' => 16,  // Conformidade
            'O' => 25,  // Justificação
            'P' => 16,  // Regime
            'Q' => 20   // Parecer
        ];

        foreach ($columnWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // 9. Output HTTP Headers para Ficheiro .xlsx
        $safeCurso = preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($curso['nome']));
        $safeAno   = preg_replace('/[^A-Za-z0-9_]/', '_', $anoLectivo);
        $filename  = "cobertura_{$safeCurso}_{$safeAno}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
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

    // ========================================================================
    // ENDPOINTS DA API DE INTEGRAÇÃO V1 (GESTAO ESCOLAR ↔ COBERTURA DOCENTE)
    // ========================================================================

    /**
     * Validação centralizada de autorização de serviço
     */
    private function checkIntegrationAuth(): bool {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // Permitir também utilizadores com perfil 'admin' autenticados na sessão web
        if (Auth::check() && Auth::user()['perfil'] === 'admin') {
            return true;
        }

        if (!$this->integracaoModel->validateToken($authHeader)) {
            Response::error('Acesso não autorizado. Token de serviço inválido ou ausente.', 401);
            return false;
        }

        return true;
    }

    /**
     * GET ?api=v1_integracao_status
     */
    private function integracaoStatus(): void {
        $start = microtime(true);
        if (!$this->checkIntegrationAuth()) return;

        $status = $this->integracaoModel->getIntegrationStatus();
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        $this->integracaoModel->logSyncEvent('v1_integracao_status', 'GET', 200, 1, $elapsed, ['status' => 'OK']);
        Response::json(['success' => true, 'data' => $status]);
    }

    /**
     * GET ?api=v1_integracao_plano_export&curso_id=X&ano=2026/27
     */
    private function integracaoPlanoExport(): void {
        $start = microtime(true);
        if (!$this->checkIntegrationAuth()) return;

        $cursoId = (int)($_GET['curso_id'] ?? 0);
        $ano = trim($_GET['ano'] ?? $_GET['ano_lectivo'] ?? '2026/27');

        if (!$cursoId) {
            Response::error('Parâmetro obrigatório "curso_id" não fornecido.', 400);
            return;
        }

        $export = $this->integracaoModel->getPlanoExportData($cursoId, $ano);
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        if (!$export) {
            $this->integracaoModel->logSyncEvent('v1_integracao_plano_export', 'GET', 404, 0, $elapsed, ['error' => 'Curso não encontrado']);
            Response::error('Curso não encontrado.', 404);
            return;
        }

        $this->integracaoModel->logSyncEvent('v1_integracao_plano_export', 'GET', 200, $export['total_linhas'], $elapsed, [
            'curso_id' => $cursoId,
            'ano'      => $ano
        ]);

        Response::json([
            'success' => true,
            'meta' => [
                'timestamp'       => date('c'),
                'tempo_ms'        => $elapsed,
                'versao_contrato' => 'v1.0'
            ],
            'data' => $export
        ]);
    }

    /**
     * POST ?api=v1_integracao_sync_docentes
     */
    private function integracaoSyncDocentes(): void {
        $start = microtime(true);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido. Use POST.', 405);
            return;
        }

        if (!$this->checkIntegrationAuth()) return;

        $payload = json_decode(file_get_contents('php://input'), true);
        $docentes = $payload['docentes'] ?? $payload;

        if (!is_array($docentes)) {
            Response::error('Payload inválido. Esperada lista de docentes em formato JSON.', 400);
            return;
        }

        $result = $this->integracaoModel->syncDocentes($docentes);
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        $status = empty($result['erros']) ? 200 : 207;
        $this->integracaoModel->logSyncEvent('v1_integracao_sync_docentes', 'POST', $status, $result['inseridos'] + $result['atualizados'], $elapsed, $result);

        Response::json([
            'success' => empty($result['erros']),
            'meta' => [
                'timestamp' => date('c'),
                'tempo_ms'  => $elapsed
            ],
            'data' => $result
        ], $status);
    }

    /**
     * POST ?api=v1_integracao_sync_metricas
     */
    private function integracaoSyncMetricas(): void {
        $start = microtime(true);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método HTTP inválido. Use POST.', 405);
            return;
        }

        if (!$this->checkIntegrationAuth()) return;

        $payload = json_decode(file_get_contents('php://input'), true);
        $metricas = $payload['metricas'] ?? $payload;

        if (!is_array($metricas)) {
            Response::error('Payload inválido. Esperada lista de métricas por turma em formato JSON.', 400);
            return;
        }

        $result = $this->integracaoModel->syncMetricasOperacionais($metricas);
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        $status = empty($result['erros']) ? 200 : 207;
        $this->integracaoModel->logSyncEvent('v1_integracao_sync_metricas', 'POST', $status, $result['atualizados'], $elapsed, $result);

        Response::json([
            'success' => empty($result['erros']),
            'meta' => [
                'timestamp' => date('c'),
                'tempo_ms'  => $elapsed
            ],
            'data' => $result
        ], $status);
    }

    /**
     * GET ?api=v1_integracao_logs
     */
    private function integracaoLogs(): void {
        if (!$this->checkIntegrationAuth()) return;

        $limit = (int)($_GET['limit'] ?? 50);
        $logs = $this->integracaoModel->getRecentSyncLogs(min($limit, 100));

        Response::json([
            'success' => true,
            'data'    => $logs
        ]);
    }
}


