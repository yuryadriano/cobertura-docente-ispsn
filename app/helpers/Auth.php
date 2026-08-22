<?php
/**
 * Helper de Autenticação e Permissões (RBAC) com suporte completo a perfis ISPSN
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    public static function check(): bool {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array {
        if (!isset($_SESSION['user'])) return null;

        // Auto-sincronização de curso_id para Coordenadores de Curso (ex: Isata Gomes Cabaça -> GRH)
        $email = strtolower($_SESSION['user']['email'] ?? '');
        if (($email === 'isata.cabaca@ispsn.org' || strpos($email, 'isata.cabaca') !== false) && empty($_SESSION['user']['_grh_synced'])) {
            try {
                $db = Database::getInstance();
                $stmt = $db->query("SELECT id FROM cursos WHERE UPPER(TRIM(codigo)) = 'GRH' OR UPPER(TRIM(nome)) = 'GRH' OR LOWER(nome) LIKE '%recursos humanos%' ORDER BY (UPPER(TRIM(nome)) = 'GRH') DESC, id ASC LIMIT 1");
                $grhId = (int)$stmt->fetchColumn();
                if ($grhId) {
                    $_SESSION['user']['curso_id'] = $grhId;
                    $_SESSION['user']['_grh_synced'] = true;
                    if (!empty($_SESSION['user']['id'])) {
                        $db->prepare("UPDATE utilizadores SET curso_id = ?, perfil = 'coordenador', activo = 1 WHERE id = ?")->execute([$grhId, $_SESSION['user']['id']]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        return $_SESSION['user'];
    }

    public static function login(array $user, bool $isRoleSwitch = false): void {
        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }
        
        $superAdmins = defined('SUPER_ADMIN_EMAILS') ? SUPER_ADMIN_EMAILS : ['evaristo.adriano@ispsn.org'];
        $userEmail = strtolower($user['email'] ?? '');
        if (in_array($userEmail, array_map('strtolower', $superAdmins)) || ($user['perfil'] ?? '') === 'admin') {
            $_SESSION['master_user_email'] = $user['email'];
            $_SESSION['super_admin_logged_in'] = true;
            $_SESSION['master_admin_session']  = true;
            $_SESSION['is_super_admin']        = true;
        } elseif (!$isRoleSwitch) {
            unset($_SESSION['master_user_email'], $_SESSION['super_admin_logged_in'], $_SESSION['master_admin_session'], $_SESSION['is_super_admin']);
        }

        $cursoId = $user['curso_id'];
        if ($userEmail === 'isata.cabaca@ispsn.org' || strpos($userEmail, 'isata.cabaca') !== false) {
            try {
                $db = Database::getInstance();
                $stmt = $db->query("SELECT id FROM cursos WHERE UPPER(TRIM(codigo)) = 'GRH' OR UPPER(TRIM(nome)) = 'GRH' OR LOWER(nome) LIKE '%recursos humanos%' ORDER BY (UPPER(TRIM(nome)) = 'GRH') DESC, id ASC LIMIT 1");
                $grhId = (int)$stmt->fetchColumn();
                if ($grhId) {
                    $cursoId = $grhId;
                    if (!empty($user['id'])) {
                        $db->prepare("UPDATE utilizadores SET curso_id = ?, perfil = 'coordenador', activo = 1 WHERE id = ?")->execute([$grhId, $user['id']]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        $_SESSION['user'] = [
            'id'       => $user['id'],
            'nome'     => $user['nome'],
            'email'    => $user['email'],
            'perfil'   => $user['perfil'],
            'curso_id' => $cursoId
        ];
    }

    public static function logout(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
        }
    }

    /**
     * Tenta autenticar um utilizador com email corporativo e palavra-passe
     */
    public static function attempt(string $email, string $password): array {
        $email = trim(strtolower($email));
        if (empty($email)) {
            return ['success' => false, 'message' => 'Por favor introduza o seu e-mail corporativo.'];
        }

        $user = self::findUserByEmail($email);

        if (!$user) {
            return ['success' => false, 'message' => 'Acesso não autorizado. O seu e-mail ainda não possui um perfil ou área atribuída pelo Administrador.'];
        }

        $superAdmins = defined('SUPER_ADMIN_EMAILS') ? SUPER_ADMIN_EMAILS : ['evaristo.adriano@ispsn.org', 'david.boio@ispsn.org'];
        $isSuperAdmin = in_array(strtolower($user['email'] ?? ''), array_map('strtolower', $superAdmins));

        if (!$isSuperAdmin && empty($user['activo'])) {
            if (empty($user['curso_id'])) {
                return ['success' => false, 'message' => 'Conta criada. Aguarde ativação e atribuição de curso pelo Administrador.'];
            }
            return ['success' => false, 'message' => 'Acesso suspenso. Esta conta encontra-se inativa no sistema.'];
        }

        // Se a conta existe e foi atribuída pelo Admin, mas a palavra-passe ainda é NULL (Primeiro Acesso)
        if (is_null($user['senha_hash']) || $user['senha_hash'] === '') {
            return [
                'success' => false,
                'is_first_access' => true,
                'user' => $user,
                'message' => 'Primeiro Acesso ao Portal ISPSN: Por favor defina a sua palavra-passe de acesso.'
            ];
        }

        if (empty($password)) {
            return ['success' => false, 'message' => 'Por favor introduza a sua palavra-passe.'];
        }

        if (password_verify($password, $user['senha_hash'])) {
            self::login($user);
            return ['success' => true, 'message' => 'Login efetuado com sucesso!', 'user' => $user];
        }

        return ['success' => false, 'message' => 'Palavra-passe incorreta. Tente novamente.'];
    }

    /**
     * Procura um utilizador pré-cadastrado pelo email corporativo
     */
    public static function findUserByEmail(string $email): ?array {
        $email = trim(strtolower($email));
        if (empty($email)) return null;

        $superAdmins = defined('SUPER_ADMIN_EMAILS') ? SUPER_ADMIN_EMAILS : ['evaristo.adriano@ispsn.org', 'david.boio@ispsn.org'];
        $superAdminsLower = array_map('strtolower', $superAdmins);

        // Mapeamento de Aliases e Grafias Institucionais Conhecidas
        $aliasMap = [
            'kianguembeni.canania@ispsn.org' => 'kianguenbeni.canania@ispsn.org',
            'kianguenbeni.canania@ispsn.org' => 'kianguenbeni.canania@ispsn.org',
            'kianguembeni.canania'          => 'kianguenbeni.canania@ispsn.org',
            'kianguenbeni.canania'          => 'kianguenbeni.canania@ispsn.org',
            'deuladeu.ferramenta@ispsn.org' => 'deuladeu.ferramenta@ispsn.org',
            'deoladeu.ferramenta@ispsn.org' => 'deuladeu.ferramenta@ispsn.org',
            'sebastiao.joaquim@ispsn.org'   => 'sebastao.joaquim@ispsn.org',
            'sebastao.joaquim@ispsn.org'    => 'sebastao.joaquim@ispsn.org',
            'coord.direito@ispsn.org'       => 'fernando.macedo@ispsn.org'
        ];

        // Se o utilizador não digitou '@', acrescenta automaticamente '@ispsn.org'
        $fullEmail = (strpos($email, '@') === false) ? ($email . '@ispsn.org') : $email;

        // Se houver alias conhecido, verificar pelo e-mail primário
        if (isset($aliasMap[$fullEmail])) {
            $fullEmail = $aliasMap[$fullEmail];
        } elseif (isset($aliasMap[$email])) {
            $fullEmail = $aliasMap[$email];
        }

        $db = Database::getInstance();
        // 1. Pesquisa exata por e-mail completo
        $stmt = $db->prepare("SELECT * FROM utilizadores WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([$fullEmail]);
        $user = $stmt->fetch();

        // 2. Se não encontrar por e-mail exato, tenta por prefixo de e-mail ou nome
        if (!$user) {
            $prefix = explode('@', $email)[0];
            $stmtPrefix = $db->prepare("SELECT * FROM utilizadores WHERE (LOWER(email) LIKE ? OR LOWER(nome) LIKE ?) LIMIT 1");
            $stmtPrefix->execute(["%{$prefix}%", "%{$prefix}%"]);
            $user = $stmtPrefix->fetch();
        }

        // 3. Super Admins têm criação soberana imediata se não existirem
        if (in_array($fullEmail, $superAdminsLower) || in_array($email, $superAdminsLower)) {
            $nomeSuper = ($fullEmail === 'evaristo.adriano@ispsn.org') ? 'Evaristo Adriano (Admin)' : 'David Boio (Admin)';
            if (!$user) {
                try {
                    $stmtIns = $db->prepare("INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo) VALUES (?, ?, NULL, 'admin', NULL, 1)");
                    $stmtIns->execute([$nomeSuper, $fullEmail]);
                    $stmt->execute([$fullEmail]);
                    $user = $stmt->fetch();
                } catch (\Throwable $e) {}
            } else {
                $user['perfil'] = 'admin';
                $user['activo'] = 1;
                $user['curso_id'] = null;
                try {
                    $db->prepare("UPDATE utilizadores SET perfil = 'admin', activo = 1, curso_id = NULL WHERE id = ?")->execute([$user['id']]);
                } catch (\Throwable $e) {}
            }
            return $user ?: null;
        }

        // 4. Auto-provisionar e-mail @ispsn.org desconhecido apenas se for de domínio institucional
        if (!$user && (strpos($fullEmail, '@ispsn.org') !== false || strpos($email, '@') === false)) {
            $prefix = explode('@', $fullEmail)[0];
            $nomePartes = explode('.', $prefix);
            $nomeFormat = implode(' ', array_map('ucfirst', $nomePartes));
            try {
                $stmtIns = $db->prepare("INSERT INTO utilizadores (nome, email, senha_hash, perfil, curso_id, activo) VALUES (?, ?, NULL, 'coordenador', NULL, 0)");
                $stmtIns->execute([$nomeFormat, $fullEmail]);
                $stmt->execute([$fullEmail]);
                $user = $stmt->fetch();
            } catch (\Exception $e) {
                $stmt->execute([$fullEmail]);
                $user = $stmt->fetch();
            }
        }

        if ($user) {
            $userEmail = strtolower($user['email'] ?? '');
            // Auto-resolução canónica para Direito (Fernando Macedo)
            if ($userEmail === 'fernando.macedo@ispsn.org' || $userEmail === 'coord.direito@ispsn.org') {
                try {
                    $stmtDir = $db->query("SELECT id FROM cursos WHERE UPPER(TRIM(codigo)) = 'DIRE' OR UPPER(TRIM(nome)) = 'DIREITO' LIMIT 1");
                    $dirId = (int)$stmtDir->fetchColumn();
                    if ($dirId) {
                        $user['curso_id'] = $dirId;
                        $user['perfil'] = 'coordenador';
                        $user['activo'] = 1;
                        if (!empty($user['id'])) {
                            $db->prepare("UPDATE utilizadores SET curso_id = ?, perfil = 'coordenador', activo = 1 WHERE id = ?")->execute([$dirId, $user['id']]);
                        }
                    }
                } catch (\Throwable $e) {}
            }

            // Auto-resolução canónica para GRH (Isata Cabaça)
            if ($userEmail === 'isata.cabaca@ispsn.org' || strpos($userEmail, 'isata.cabaca') !== false) {
                try {
                    $stmtGrh = $db->query("SELECT id FROM cursos WHERE UPPER(TRIM(codigo)) = 'GRH' OR UPPER(TRIM(nome)) = 'GRH' OR LOWER(nome) LIKE '%recursos humanos%' ORDER BY (UPPER(TRIM(nome)) = 'GRH') DESC, id ASC LIMIT 1");
                    $grhId = (int)$stmtGrh->fetchColumn();
                    if ($grhId) {
                        $user['curso_id'] = $grhId;
                        $user['perfil'] = 'coordenador';
                        $user['activo'] = 1;
                        if (!empty($user['id'])) {
                            $db->prepare("UPDATE utilizadores SET curso_id = ?, perfil = 'coordenador', activo = 1 WHERE id = ?")->execute([$grhId, $user['id']]);
                        }
                    }
                } catch (\Throwable $e) {}
            }
        }

        return $user ?: null;
    }

    /**
     * Ativa a conta no Primeiro Acesso definindo a nova palavra-passe
     */
    public static function activateAccount(string $email, string $newPassword): array {
        $email = trim(strtolower($email));
        if (empty($email) || empty($newPassword)) {
            return ['success' => false, 'message' => 'Por favor preencha todos os campos.'];
        }

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'A palavra-passe deve ter no mínimo 6 caracteres.'];
        }

        $user = self::findUserByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'Acesso não autorizado. O seu e-mail ainda não possui um perfil ou área atribuída pelo Administrador.'];
        }

        if (!empty($user['senha_hash'])) {
            return ['success' => false, 'message' => 'A sua palavra-passe já foi registrada anteriormente. Por favor introduza a sua senha no login normal.'];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE utilizadores SET senha_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);

        // Efetua login automático após definir a palavra-passe
        $user['senha_hash'] = $hash;
        self::login($user);

        return ['success' => true, 'message' => 'Conta ativada com sucesso! Bem-vindo ao Portal ISPSN.', 'user' => $user];
    }

    /**
     * Solicita redefinição de palavra-passe por e-mail corporativo
     */
    public static function requestPasswordReset(string $email): array {
        $email = trim(strtolower($email));
        if (empty($email)) {
            return ['success' => false, 'message' => 'Por favor introduza o seu e-mail corporativo.'];
        }

        $user = self::findUserByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'E-mail corporativo não encontrado na instituição.'];
        }

        // Gerar código secreto de validação de 6 dígitos
        $pin = (string)rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_pin']   = $pin;
        $_SESSION['reset_time']  = time();

        // Enviar e-mail com o código corporativo via MailHelper (SMTP Institucional)
        require_once __DIR__ . '/MailHelper.php';
        $subject  = "Código de Validação — Redefinição de Palavra-Passe ISPSN";
        $bodyHtml = "
            <div style='font-family: Arial, sans-serif; color: #111; max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
                <h2 style='color: #0F2537; margin-top: 0;'>Instituto Superior Politécnico Sol Nascente</h2>
                <p>Olá,</p>
                <p>Recebemos uma solicitação de redefinição de palavra-passe para a sua conta corporativa (<strong>" . htmlspecialchars($email) . "</strong>).</p>
                <div style='background: #F8FAFC; border: 1px solid #E2E8F0; padding: 14px; border-radius: 8px; text-align: center; margin: 20px 0;'>
                    <span style='font-size: 12px; color: #64748B; display: block;'>O SEU CÓDIGO DE SEGURANÇA É:</span>
                    <strong style='font-size: 24px; color: #C9970A; letter-spacing: 4px;'>$pin</strong>
                </div>
                <p style='font-size: 12px; color: #64748B;'>Este código é válido por 15 minutos. Caso não tenha solicitado este código, por favor ignore esta mensagem.</p>
                <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                <p style='font-size: 11px; color: #94a3b8; text-align: center;'>Direção de Tecnologias de Informação — ISPSN 2026/27</p>
            </div>
        ";

        $mailResult = MailHelper::send($email, $subject, $bodyHtml);

        $msg = "Enviámos o código de validação para o e-mail corporativo ($email). Por favor verifique a sua caixa de entrada.";
        if (isset($mailResult['is_dev_mode']) && $mailResult['is_dev_mode']) {
            $msg .= " (Nota de Infraestrutura: Para envio real via SMTP institucional em produção, preencha as credenciais SMTP_PASS em config/config.php).";
        }

        return [
            'success' => true,
            'message' => $msg,
            'email'   => $email,
            'mail_status' => $mailResult
        ];
    }

    /**
     * Redefine a palavra-passe com o código PIN de confirmação
     */
    public static function resetPassword(string $email, string $pin, string $newPassword): array {
        $email = trim(strtolower($email));
        $pin   = trim($pin);

        if (empty($email) || empty($pin) || empty($newPassword)) {
            return ['success' => false, 'message' => 'Por favor preencha todos os campos do formulário.'];
        }

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'A nova palavra-passe deve ter no mínimo 6 caracteres.'];
        }

        $sessionEmail = $_SESSION['reset_email'] ?? null;
        $sessionPin   = $_SESSION['reset_pin'] ?? null;
        $sessionTime  = $_SESSION['reset_time'] ?? 0;
        $expireSeconds = defined('PIN_RESET_EXPIRE_SECONDS') ? PIN_RESET_EXPIRE_SECONDS : 900;

        if ($sessionTime > 0 && (time() - $sessionTime) > $expireSeconds) {
            unset($_SESSION['reset_email'], $_SESSION['reset_pin'], $_SESSION['reset_time'], $_SESSION['reset_step']);
            return ['success' => false, 'message' => 'O código de validação expirou (válido por 15 minutos). Por favor solicite um novo código.'];
        }

        if ($sessionEmail !== $email || $sessionPin !== $pin) {
            return ['success' => false, 'message' => 'Código de verificação incorreto ou e-mail divergente.'];
        }

        $user = self::findUserByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'Utilizador não encontrado.'];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE utilizadores SET senha_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);

        // Limpa estado de reset e efetua login automático
        unset($_SESSION['reset_email'], $_SESSION['reset_pin'], $_SESSION['reset_time'], $_SESSION['reset_step']);
        $user['senha_hash'] = $hash;
        self::login($user);

        return ['success' => true, 'message' => 'Palavra-passe redefinida com sucesso! Sessão iniciada.', 'user' => $user];
    }

    public static function roleInfo(?string $role = null): array {
        $role = $role ?? (self::user()['perfil'] ?? 'coordenador');
        $map = [
            'coordenador' => [
                'nome' => 'Coordenador de Curso',
                'user' => 'Coord. (Direito)',
                'scope' => 'curso',
                'nav' => ['cobertura', 'painel', 'turmas', 'curriculo', 'docentes'],
                'desc' => 'Preenche a Cobertura Docente do seu curso'
            ],
            'chefe_departamento' => [
                'nome' => 'Chefe de Departamento',
                'user' => 'Chefe de Depto.',
                'scope' => 'geral',
                'nav' => ['aprov', 'dashboard', 'painel', 'cobertura', 'turmas', 'curriculo', 'docentes', 'cv'],
                'desc' => 'Apreciação, Parecer e Aprovação dos Planos de Cobertura Docente do Departamento'
            ],
            'gestor_academico' => [
                'nome' => 'Gestão Académica',
                'user' => 'Gestão Académica',
                'scope' => 'geral',
                'nav' => ['painel', 'dashboard', 'cobertura', 'turmas', 'curriculo', 'docentes', 'cv', 'aprov'],
                'desc' => 'Acesso geral (consulta de todos os módulos)'
            ],
            'grh' => [
                'nome' => 'GRH',
                'user' => 'GRH',
                'scope' => 'geral',
                'nav' => ['painel', 'docentes', 'cv'],
                'desc' => 'Preenche o CV Estruturado e Docentes/Documentos'
            ],
            'presidente' => [
                'nome' => 'Presidência',
                'user' => 'Presidência',
                'scope' => 'geral',
                'nav' => ['painel', 'dashboard', 'cobertura', 'turmas', 'curriculo', 'docentes', 'cv', 'aprov'],
                'desc' => 'Acesso geral e aprovação da Cobertura Docente'
            ],
            'secretario_geral' => [
                'nome' => 'Secretário-Geral',
                'user' => 'Secretário-Geral',
                'scope' => 'geral',
                'nav' => ['painel', 'dashboard', 'cobertura', 'turmas', 'curriculo', 'docentes', 'cv', 'aprov'],
                'desc' => 'Acesso geral (consulta de todos os módulos)'
            ],
            'docente' => [
                'nome' => 'Corpo Docente',
                'user' => 'Docente',
                'scope' => 'proprio',
                'nav' => ['cv', 'docentes'],
                'desc' => 'Consulta e atualização do próprio CV Estruturado e Repositório Documental'
            ],
            'admin' => [
                'nome' => 'Administração',
                'user' => 'Administração',
                'scope' => 'geral',
                'nav' => ['painel', 'dashboard', 'cobertura', 'turmas', 'curriculo', 'docentes', 'cv', 'aprov', 'config'],
                'desc' => 'Acesso total a todos os módulos, aprovações, configurações e gestão do sistema'
            ]
        ];
        return $map[$role] ?? $map['coordenador'];
    }

    public static function isSuperAdmin(?array $user = null): bool {
        if (!empty($_SESSION['super_admin_logged_in']) || !empty($_SESSION['master_admin_session']) || !empty($_SESSION['is_super_admin'])) {
            return true;
        }
        $u = $user ?? self::user();
        if (!$u) return false;
        
        $email = strtolower($u['email'] ?? '');
        $superAdmins = defined('SUPER_ADMIN_EMAILS') ? SUPER_ADMIN_EMAILS : ['evaristo.adriano@ispsn.org'];
        return in_array($email, array_map('strtolower', $superAdmins)) || ($u['perfil'] ?? '') === 'admin';
    }

    public static function hasRole($roles): bool {
        if (!self::check()) return false;
        $userRole = $_SESSION['user']['perfil'] ?? '';
        if ($userRole === 'admin') return true;

        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        return $userRole === $roles;
    }

    public static function isAllowedPage(string $page): bool {
        if (!self::check()) return false;
        $userRole = $_SESSION['user']['perfil'] ?? '';
        if ($userRole === 'admin') return true;

        if (in_array($page, ['api_tester', 'api_docs', 'relatorio_plano'])) return true;

        $info = self::roleInfo();
        return in_array($page, $info['nav']);
    }

    public static function canEditCourse(int $cursoId): bool {
        if (!self::check()) return false;
        $user = self::user();
        if (in_array($user['perfil'], ['admin', 'gestor_academico', 'chefe_departamento', 'presidente'])) {
            return true;
        }
        if ($user['perfil'] === 'coordenador') {
            $userCursoId = (int)($user['curso_id'] ?? 0);
            if ($userCursoId && $userCursoId === $cursoId) return true;

            // Verificação de segurança adicional para coordenadores conhecidos por curso
            $email = strtolower($user['email'] ?? '');
            $coordCursoMap = [
                'fernando.macedo'     => 'DIRE',
                'coord.direito'       => 'DIRE',
                'isata.cabaca'        => 'GRH',
                'valeriano.mangandi'  => 'CPRI',
                'joao.miguel'         => 'ECON',
                'nelson.sungo'        => 'CONT',
                'dania.castro'        => 'ENFE',
                'silvia.chitangua'    => 'CARD',
                'domingos.bernardo'   => 'FISI',
                'miriam.herrera'      => 'ANLI',
                'deuladeu.ferramenta' => 'HIST',
                'deoladeu.ferramenta' => 'HIST',
                'jorge.montane'       => 'PSIC',
                'sebastao.joaquim'    => 'SOCI',
                'sebastiao.joaquim'   => 'SOCI'
            ];

            foreach ($coordCursoMap as $prefix => $codCurso) {
                if (strpos($email, $prefix) !== false) {
                    $db = Database::getInstance();
                    $stmt = $db->prepare("SELECT id FROM cursos WHERE UPPER(TRIM(codigo)) = ? LIMIT 1");
                    $stmt->execute([$codCurso]);
                    $targetCursoId = (int)$stmt->fetchColumn();
                    if ($targetCursoId && $targetCursoId === $cursoId) {
                        $_SESSION['user']['curso_id'] = $targetCursoId;
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function canApprove(): bool {
        if (!self::check()) return false;
        $userRole = $_SESSION['user']['perfil'] ?? '';
        return in_array($userRole, ['presidente', 'chefe_departamento', 'admin']);
    }

    public static function canEditDoc(): bool {
        if (!self::check()) return false;
        $perfil = $_SESSION['user']['perfil'] ?? '';
        return in_array($perfil, ['grh', 'admin']);
    }

    public static function canEditCV(): bool {
        if (!self::check()) return false;
        $perfil = $_SESSION['user']['perfil'] ?? '';
        return in_array($perfil, ['grh', 'admin']);
    }
}
