<?php
/**
 * Configurações Globais da Aplicação
 * sftcoordenacao — Módulo de Cobertura Docente ISPSN 2026/27
 */

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . $host . '/sftcoordenacao/public';
    define('BASE_URL', $baseUrl);
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Módulo de Cobertura Docente — ISPSN 2026/27');
}

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Ano Lectivo Ativo no Sistema (Dinâmico via Sessão ou Padrão)
if (!defined('ANO_LECTIVO_ACTIVO')) {
    $sessionAno = $_SESSION['ano_lectivo_activo'] ?? '2026/27';
    define('ANO_LECTIVO_ACTIVO', $sessionAno);
}

if (!function_exists('get_ano_lectivo_activo')) {
    function get_ano_lectivo_activo(): string {
        return $_SESSION['ano_lectivo_activo'] ?? (defined('ANO_LECTIVO_ACTIVO') ? ANO_LECTIVO_ACTIVO : '2026/27');
    }
}

// E-mails de utilizadores com privilégios soberanos (Super Admin)
if (!defined('SUPER_ADMIN_EMAILS')) {
    define('SUPER_ADMIN_EMAILS', [
        'evaristo.adriano@ispsn.org',
        'david.boio@ispsn.org'
    ]);
}

// Expiração do Código PIN de Redefinição de Palavra-Passe (15 Minutos = 900 Segundos)
if (!defined('PIN_RESET_EXPIRE_SECONDS')) {
    define('PIN_RESET_EXPIRE_SECONDS', 900);
}

// Limite Máximo para Carregamento de Documentos de Docentes (em MB)
if (!defined('MAX_UPLOAD_SIZE_MB')) {
    define('MAX_UPLOAD_SIZE_MB', 10);
}

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'sftcoordenacao_db');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));

// Perfis de Acesso do Sistema (RBAC)
define('PERFIS_ACESSO', [
    'coordenador'      => 'Coordenador de Curso',
    'gestor_academico' => 'Gestão Académica',
    'grh'              => 'Recursos Humanos (GRH)',
    'presidente'       => 'Presidente / Direção',
    'secretario_geral' => 'Secretário-Geral',
    'admin'            => 'Administrador do Sistema'
]);

// Configurações do Servidor SMTP Institucional ISPSN (E-mail Autenticado)
if (!defined('SMTP_HOST'))      define('SMTP_HOST', 'mail.ispsn.org');       // ex: mail.ispsn.org, smtp.office365.com, smtp.gmail.com
if (!defined('SMTP_PORT'))      define('SMTP_PORT', 587);                    // 587 (TLS/STARTTLS) ou 465 (SSL)
if (!defined('SMTP_USER'))      define('SMTP_USER', 'suporte.ti@ispsn.org');  // E-mail da conta remetente institucional
if (!defined('SMTP_PASS'))      define('SMTP_PASS', '');                     // Palavra-passe da conta de e-mail institucional (Preencher para ativar envio real)
if (!defined('SMTP_SECURE'))    define('SMTP_SECURE', 'tls');                // 'tls' ou 'ssl'
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Direção de TI — ISPSN');


