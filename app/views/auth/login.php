<?php
/**
 * View da Tela de Login Corporativa Minimalista e Inteligente ISPSN
 * sftcoordenacao — Módulo de Cobertura Docente ISPSN 2026/27
 */

$flashError   = $_SESSION['flash_error'] ?? ($_GET['error'] ?? null);
$flashSuccess = $_SESSION['flash_success'] ?? ($_GET['success'] ?? null);
$flashInfo    = $_SESSION['flash_info'] ?? ($_GET['info'] ?? null);

$resetStep    = $_SESSION['reset_step'] ?? 1;
$resetEmail   = $_SESSION['reset_email'] ?? '';
$mode         = $_GET['mode'] ?? ($_GET['tab'] ?? ($resetStep === 2 ? 'forgot' : 'login'));

unset($_SESSION['flash_error'], $_SESSION['flash_success'], $_SESSION['flash_info']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal ISPSN · Autenticação</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --gold: #E8B10C;
    --gold-dark: #C9970A;
    --navy: #0F2537;
    --navy-light: #1F4E79;
    --ink: #0F172A;
    --muted: #64748B;
    --bg-body: #F8FAFC;
    --surface: #FFFFFF;
    --line: #E2E8F0;
    --danger: #EF4444;
    --danger-bg: #FEF2F2;
    --success: #22C55E;
    --success-bg: #F0FDF4;
    --info: #3B82F6;
    --info-bg: #EFF6FF;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    background-color: var(--bg-body);
    color: var(--ink);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
    background-image: 
      radial-gradient(circle at 50% 0%, rgba(232, 177, 12, 0.07) 0%, transparent 60%),
      radial-gradient(circle at 50% 100%, rgba(31, 78, 121, 0.05) 0%, transparent 60%);
  }

  .login-card {
    width: 100%;
    max-width: 410px;
    background: var(--surface);
    border-radius: 20px;
    box-shadow: 0 10px 35px -5px rgba(15, 23, 42, 0.06), 0 0 1px rgba(15, 23, 42, 0.12);
    border: 1px solid var(--line);
    padding: 36px 32px;
  }

  /* Branding Header */
  .brand-header {
    text-align: center;
    margin-bottom: 28px;
  }

  .logo-img {
    width: 64px;
    height: 64px;
    object-fit: contain;
    margin-bottom: 12px;
  }

  .brand-title {
    font-size: 17px;
    font-weight: 800;
    color: var(--navy);
    letter-spacing: -0.3px;
    line-height: 1.25;
  }

  .brand-subtitle {
    font-size: 13px;
    color: var(--muted);
    margin-top: 4px;
    font-weight: 500;
  }

  /* View Modes */
  .view-mode { display: none; }
  .view-mode.active { display: block; }

  .form-group {
    margin-bottom: 18px;
  }

  .form-label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
  }

  .input-wrap {
    position: relative;
    display: flex;
    align-items: center;
  }

  .form-input {
    width: 100%;
    padding: 11px 70px 11px 14px;
    font-size: 14px;
    font-family: inherit;
    border: 1.5px solid var(--line);
    border-radius: 10px;
    background: #F8FAFC;
    color: var(--ink);
    outline: none;
    transition: all 0.2s ease;
  }

  .form-input.no-btn {
    padding-right: 14px;
  }

  .form-input:focus {
    border-color: var(--navy-light);
    background: var(--surface);
    box-shadow: 0 0 0 3px rgba(31, 78, 121, 0.12);
  }

  .toggle-btn {
    position: absolute;
    right: 10px;
    z-index: 10;
    background: #F1F5F9;
    border: 1px solid var(--line);
    border-radius: 6px;
    cursor: pointer;
    color: var(--navy-light);
    font-size: 11.5px;
    font-weight: 700;
    padding: 3px 8px;
    user-select: none;
    transition: all 0.2s ease;
  }

  .toggle-btn:hover {
    background: #E2E8F0;
    color: var(--navy);
  }

  .form-extra {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
    font-size: 12.5px;
  }

  .forgot-link {
    color: var(--navy-light);
    font-weight: 600;
    text-decoration: none;
  }

  .forgot-link:hover {
    text-decoration: underline;
  }

  .btn-submit {
    width: 100%;
    padding: 12.5px;
    background: var(--navy);
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(15, 37, 55, 0.2);
    transition: all 0.2s ease;
  }

  .btn-submit:hover {
    background: var(--navy-light);
    transform: translateY(-1px);
  }

  .btn-submit:active { transform: translateY(0); }

  /* Feedback Alerts */
  .alert {
    padding: 11px 14px;
    border-radius: 10px;
    font-size: 12.5px;
    line-height: 1.4;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .alert.danger { background: var(--danger-bg); color: var(--danger); border: 1px solid #FCA5A5; }
  .alert.success { background: var(--success-bg); color: var(--success); border: 1px solid #86EFAC; }
  .alert.info { background: var(--info-bg); color: var(--info); border: 1px solid #93C5FD; }

  .back-link-wrap {
    text-align: center;
    margin-top: 18px;
  }

  .back-link {
    color: var(--muted);
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.2s ease;
  }

  .back-link:hover {
    color: var(--navy);
    text-decoration: underline;
  }

  .footer-copy {
    margin-top: 24px;
    font-size: 12px;
    color: var(--muted);
    text-align: center;
  }
</style>
</head>
<body>

<div class="login-card">
  <!-- Branding Header -->
  <div class="brand-header">
    <img src="assets/img/logo.png" alt="ISPSN Logo" class="logo-img" onerror="this.onerror=null; this.src='https://via.placeholder.com/64/1F4E79/E8B10C?text=ISPSN';">
    <h1 class="brand-title">Instituto Superior Politécnico<br>Sol Nascente</h1>
    <p class="brand-subtitle">Módulo de Cobertura Docente 2026/27</p>
  </div>

  <!-- Messages -->
  <?php if ($flashError): ?>
    <div class="alert danger">⚠️ <?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>
  <?php if ($flashSuccess): ?>
    <div class="alert success">✅ <?= htmlspecialchars($flashSuccess) ?></div>
  <?php endif; ?>
  <?php if ($flashInfo): ?>
    <div class="alert info">ℹ️ <?= htmlspecialchars($flashInfo) ?></div>
  <?php endif; ?>

  <!-- Mode 1: Standard Login Form -->
  <div id="mode-login" class="view-mode <?= $mode !== 'forgot' ? 'active' : '' ?>">
    <form action="index.php?action=login" method="POST">
      <div class="form-group">
        <label class="form-label" for="login-email">E-mail Corporativo</label>
        <div class="input-wrap">
          <input type="email" id="login-email" name="email" class="form-input no-btn" placeholder="ex: nome@ispsn.org" value="<?= htmlspecialchars($resetEmail) ?>" required autofocus>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="login-password">Palavra-Passe</label>
        <div class="input-wrap">
          <input type="password" id="login-password" name="password" class="form-input" placeholder="••••••••" required>
          <button type="button" class="toggle-btn" onclick="togglePass('login-password', this, event)">Mostrar</button>
        </div>
      </div>

      <div class="form-extra">
        <a href="#" class="forgot-link" onclick="switchMode('forgot'); return false;">Esqueceu a senha?</a>
      </div>

      <button type="submit" class="btn-submit">Entrar no Sistema</button>
    </form>
  </div>

  <!-- Mode 2: Password Reset / Setup Form -->
  <div id="mode-forgot" class="view-mode <?= $mode === 'forgot' ? 'active' : '' ?>">
    <?php if ($resetStep === 2 && $resetEmail): ?>
      <!-- Step 2: Enter PIN received in Email and New Password -->
      <form action="index.php?action=forgot_reset" method="POST">
        <input type="hidden" name="email" value="<?= htmlspecialchars($resetEmail) ?>">
        
        <div class="form-group">
          <label class="form-label">E-mail Corporativo</label>
          <div class="input-wrap">
            <input type="email" class="form-input no-btn" value="<?= htmlspecialchars($resetEmail) ?>" disabled style="background:#E2E8F0; font-weight:600;">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="forgot-pin">Código de Validação (PIN de 6 dígitos)</label>
          <div class="input-wrap">
            <input type="text" id="forgot-pin" name="pin" class="form-input no-btn" placeholder="ex: 849201" maxlength="6" required autofocus style="letter-spacing: 2px; font-weight: 700; text-align: center;">
          </div>
          <span style="font-size: 11.5px; color: var(--muted); margin-top: 5px; display: block;">
            Introduza o código privado enviado para o seu e-mail corporativo.
          </span>
        </div>

        <div class="form-group">
          <label class="form-label" for="forgot-newpass">Nova Palavra-Passe</label>
          <div class="input-wrap">
            <input type="password" id="forgot-newpass" name="new_password" class="form-input" placeholder="Mínimo 6 caracteres" minlength="6" required>
            <button type="button" class="toggle-btn" onclick="togglePass('forgot-newpass', this, event)">Mostrar</button>
          </div>
        </div>

        <button type="submit" class="btn-submit" style="background: var(--gold-dark);">Redefinir Senha & Entrar</button>

        <div class="back-link-wrap">
          <a href="#" class="back-link" onclick="switchMode('login'); return false;">← Voltar ao Iniciar Sessão</a>
        </div>
      </form>
    <?php else: ?>
      <!-- Step 1: Request PIN by Email -->
      <form action="index.php?action=forgot_request" method="POST">
        <div class="form-group">
          <label class="form-label" for="forgot-email">E-mail Corporativo</label>
          <div class="input-wrap">
            <input type="email" id="forgot-email" name="email" class="form-input no-btn" placeholder="ex: nome@ispsn.org" value="<?= htmlspecialchars($resetEmail) ?>" required autofocus>
          </div>
          <span style="font-size: 11.5px; color: var(--muted); margin-top: 5px; display: block;">
            Introduza o seu e-mail corporativo cadastrado para gerar o código de validação.
          </span>
        </div>

        <button type="submit" class="btn-submit" style="background: var(--navy-light);">Gerar Código de Validação</button>

        <div class="back-link-wrap">
          <a href="#" class="back-link" onclick="switchMode('login'); return false;">← Voltar ao Iniciar Sessão</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="footer-copy">
  ISPSN © 2026/27 · Todos os direitos reservados.
</div>

<script>
  function switchMode(targetMode) {
    const loginEmailInput = document.getElementById('login-email');
    const forgotEmailInput = document.getElementById('forgot-email');
    if (loginEmailInput && forgotEmailInput && loginEmailInput.value) {
      forgotEmailInput.value = loginEmailInput.value;
    }

    document.querySelectorAll('.view-mode').forEach(m => m.classList.remove('active'));
    if (targetMode === 'forgot') {
      document.getElementById('mode-forgot').classList.add('active');
    } else {
      document.getElementById('mode-login').classList.add('active');
    }
  }

  function togglePass(id, btn, evt) {
    if (evt) {
      evt.preventDefault();
      evt.stopPropagation();
    }
    const el = document.getElementById(id);
    if (!el) return;
    if (el.type === 'password') {
      el.type = 'text';
      btn.textContent = 'Ocultar';
    } else {
      el.type = 'password';
      btn.textContent = 'Mostrar';
    }
  }
</script>

</body>
</html>
