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
        return $_SESSION['user'] ?? null;
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

        $_SESSION['user'] = [
            'id'       => $user['id'],
            'nome'     => $user['nome'],
            'email'    => $user['email'],
            'perfil'   => $user['perfil'],
            'curso_id' => $user['curso_id']
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

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM utilizadores WHERE email = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'E-mail corporativo não encontrado ou conta inativa na instituição.'];
        }

        // Se a conta existe mas a palavra-passe ainda é NULL ou vazia (Primeiro Acesso)
        if (is_null($user['senha_hash']) || $user['senha_hash'] === '') {
            return [
                'success' => false,
                'is_first_access' => true,
                'message' => 'Conta ativa por pré-registo. Por favor defina a sua palavra-passe de Primeiro Acesso.'
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

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM utilizadores WHERE email = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
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
            return ['success' => false, 'message' => 'E-mail corporativo não encontrado na lista de pré-cadastro da instituição.'];
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
                'nav' => ['painel', 'cobertura', 'turmas', 'curriculo', 'docentes'],
                'desc' => 'Preenche a Cobertura Docente, gere as Turmas e o Currículo do seu curso'
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
        if (self::isSuperAdmin()) return true;

        $userRole = $_SESSION['user']['perfil'] ?? '';
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        return $userRole === $roles;
    }

    public static function isAllowedPage(string $page): bool {
        if (!self::check()) return false;
        if (self::isSuperAdmin()) return true;

        $info = self::roleInfo();
        return in_array($page, $info['nav']);
    }

    public static function canEditCourse(int $cursoId): bool {
        if (!self::check()) return false;
        if (self::isSuperAdmin()) return true;

        $user = $_SESSION['user'];
        if (in_array($user['perfil'], ['admin', 'gestor_academico'])) {
            return true;
        }
        if ($user['perfil'] === 'coordenador') {
            return (int)($user['curso_id'] ?? 0) === $cursoId;
        }
        return false;
    }

    public static function canApprove(): bool {
        if (!self::check()) return false;
        if (self::isSuperAdmin()) return true;

        return in_array($_SESSION['user']['perfil'] ?? '', ['presidente', 'admin']);
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
