<?php
/**
 * Header Layout Template — Fidelidade Total ao Design ISPSN
 * sftcoordenacao — Módulo de Cobertura Docente 2026/27
 */
$currentUser = Auth::user();
$currentRole = $currentUser['perfil'] ?? 'coordenador';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'Portal ISPSN · BackOffice — Cobertura Docente 2026/27') ?></title>
<link rel="stylesheet" href="assets/css/style.css?v=2.0">
</head>
<body>
<div class="top">
  <img src="assets/img/logo.png" alt="Logo ISPSN" class="logo">
  <h1>Portal ISPSN · BackOffice</h1>
<?php $currentAnoLectivo = get_ano_lectivo_activo(); ?>
  <span class="yr"><?= htmlspecialchars($currentAnoLectivo) ?></span>
  <span class="demo-tag">PRODUÇÃO PHP</span>
  <div class="sp"></div>
  <div class="rolebox">
    <span style="font-size:12px;font-weight:600">Ano lectivo:</span>
    <select id="year-select" onchange="window.switchAnoLectivo(this.value)">
      <option value="2025/26" <?= $currentAnoLectivo === '2025/26' ? 'selected' : '' ?>>2025/2026</option>
      <option value="2026/27" <?= $currentAnoLectivo === '2026/27' ? 'selected' : '' ?>>2026/2027</option>
      <option value="2027/28" <?= $currentAnoLectivo === '2027/28' ? 'selected' : '' ?>>2027/2028</option>
    </select>
  </div>
  <?php if (Auth::isSuperAdmin()): ?>
  <div class="rolebox">
    <span style="font-size:12px;font-weight:600">Perfil Ativo:</span>
    <select id="role-select" onchange="window.switchRole(this.value)">
      <option value="coordenador" <?= $currentRole === 'coordenador' ? 'selected' : '' ?>>Coordenador de Curso</option>
      <option value="chefe_departamento" <?= $currentRole === 'chefe_departamento' ? 'selected' : '' ?>>Chefe de Departamento</option>
      <option value="gestor_academico" <?= $currentRole === 'gestor_academico' ? 'selected' : '' ?>>Gestão Académica</option>
      <option value="grh" <?= $currentRole === 'grh' ? 'selected' : '' ?>>GRH</option>
      <option value="presidente" <?= $currentRole === 'presidente' ? 'selected' : '' ?>>Presidência</option>
      <option value="secretario_geral" <?= $currentRole === 'secretario_geral' ? 'selected' : '' ?>>Secretário-Geral</option>
      <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Administração</option>
    </select>
  </div>
  <?php else: ?>
  <div class="rolebox" style="background:#1F4E79; color:#fff; padding:6px 12px; border-radius:6px; font-weight:700; font-size:12px;">
    👤 Perfil: <?= htmlspecialchars(Auth::roleInfo()['nome']) ?>
  </div>
  <?php endif; ?>
<?php
require_once __DIR__ . '/../../helpers/Notification.php';
$notificationsList = Notification::getNotifications();
$notifCount = count($notificationsList);
?>
<div class="rolebox" style="position:relative;">
  <button type="button" onclick="const p = document.getElementById('notif-popup'); p.style.display = p.style.display==='block'?'none':'block';" style="background:none; border:none; color:#FFF; cursor:pointer; font-weight:700; display:flex; align-items:center; gap:5px; font-size:13px;">
    <span>🔔 Alertas</span>
    <?php if ($notifCount > 0): ?>
      <span style="background:var(--gold); color:#1A1A1A; border-radius:50%; width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; font-size:10.5px; font-weight:800;"><?= $notifCount ?></span>
    <?php endif; ?>
  </button>
  <div id="notif-popup" style="display:none; position:absolute; right:0; top:35px; background:#FFF; color:#1A1D20; border:1px solid #CBD5E1; border-radius:8px; width:320px; box-shadow:0 8px 24px rgba(0,0,0,0.2); z-index:9999; padding:12px; font-size:12px;">
    <div style="font-weight:800; color:var(--blue); border-bottom:1px solid #E2E8F0; padding-bottom:6px; margin-bottom:8px; display:flex; justify-content:space-between;">
      <span>Alertas &amp; Notificações</span>
      <span style="font-weight:normal; font-size:11px; color:#64748B;"><?= $notifCount ?> ativas</span>
    </div>
    <div style="max-height:240px; overflow-y:auto;">
      <?php if ($notifCount === 0): ?>
        <div style="text-align:center; padding:12px; color:#94A3B8;">Sem novas notificações no momento.</div>
      <?php else: ?>
        <?php foreach ($notificationsList as $n): ?>
          <div style="margin-bottom:8px; border-bottom:1px solid #F1F5F9; padding-bottom:6px;">
            <div style="display:flex; justify-content:space-between; font-weight:700;">
              <span style="color:<?= $n['novo_estado']==='Aprovado'?'#1E8449':($n['novo_estado']==='Devolvido'?'#C0392B':'#1F4E79') ?>;">
                <?= $n['novo_estado']==='Aprovado'?'✅ Aprovado':($n['novo_estado']==='Devolvido'?'↩️ Devolvido':'📤 Submetido') ?>
              </span>
              <span style="font-size:10px; color:#94A3B8;"><?= date('d/m H:i', strtotime($n['timestamp'])) ?></span>
            </div>
            <div style="font-size:11.5px; margin-top:2px;">Por <b><?= htmlspecialchars($n['actor_nome']) ?></b> (<?= htmlspecialchars($n['actor_perfil']) ?>)</div>
            <?php if (!empty($n['observacoes'])): ?>
              <div style="font-size:11px; color:#475569; font-style:italic; margin-top:2px;">"<?= htmlspecialchars($n['observacoes']) ?>"</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
  <span class="user" id="user-label"><?= htmlspecialchars($currentUser['nome'] ?? 'Utilizador ISPSN') ?></span>
</div>
<div class="wrap">
