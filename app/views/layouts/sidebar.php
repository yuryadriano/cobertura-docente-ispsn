<?php
/**
 * Sidebar Navigation Template — Design Profissional com Ícones SVG e Card de Perfil
 * sftcoordenacao — ISPSN
 */
$info = Auth::roleInfo();
$currentUser = Auth::user();
$allowedNav = $info['nav'];
$currentPage = $currentPage ?? 'painel';

$svgIcons = [
    'painel'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
    'dashboard'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    'cobertura'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
    'turmas'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>',
    'curriculo'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    'docentes'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'cv'            => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
    'aprov'         => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>',
    'config'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>'
];

$labels = [
    'painel'        => 'Painel de Controlo',
    'dashboard'     => 'Dashboard Executivo',
    'cobertura'     => 'Cobertura Docente',
    'turmas'        => 'Gestão de Turmas',
    'curriculo'     => 'Matriz Curricular',
    'docentes'      => 'Docentes & Documentos',
    'cv'            => 'CV Estruturado',
    'aprov'         => 'Aprovações',
    'config'        => 'Configuração'
];

$userName = $currentUser['nome'] ?? 'Utilizador ISPSN';
$userInitials = strtoupper(substr($userName, 0, 2));
?>
<nav class="nav">
  <div class="nav-group">
    <div class="scope-badge">
      <span style="font-size:14px;">🎯</span>
      <div>
        <div style="font-size:10px; color:var(--mut); text-transform:uppercase;">Âmbito de Acesso</div>
        <?php
        $scopeDisplay = 'Todos os Cursos';
        if ($info['scope'] === 'curso' && !empty($currentUser['curso_id'])) {
            $dbScope = Database::getInstance();
            $stmtScope = $dbScope->prepare("SELECT nome FROM cursos WHERE id = ? LIMIT 1");
            $stmtScope->execute([$currentUser['curso_id']]);
            $cursoNome = $stmtScope->fetchColumn();
            if ($cursoNome) {
                $scopeDisplay = 'Curso: ' . $cursoNome;
            }
        }
        ?>
        <div><?= htmlspecialchars($scopeDisplay) ?></div>
      </div>
    </div>
    
    <div class="sec">Módulos do Sistema</div>

    <?php foreach ($allowedNav as $item): ?>
      <?php if (isset($labels[$item])): ?>
        <a href="index.php?page=<?= $item ?>" class="<?= $currentPage === $item ? 'on' : '' ?>">
          <span class="ic"><?= $svgIcons[$item] ?? '' ?></span> 
          <span><?= htmlspecialchars($labels[$item]) ?></span>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div class="user-card">
    <div class="u-head">
      <div class="avatar"><?= $userInitials ?></div>
      <div>
        <div class="u-name"><?= htmlspecialchars($userName) ?></div>
        <div class="u-role"><?= htmlspecialchars($info['nome']) ?></div>
      </div>
    </div>
    <div class="u-desc">
      <?= htmlspecialchars($info['desc']) ?>
    </div>
    <a href="index.php?action=logout" class="btn-logout" style="margin-top:10px; display:flex; align-items:center; justify-content:center; gap:8px; padding:8px 12px; background:#FBEAE8; color:#C0392B; border:1px solid #F5C6CB; border-radius:6px; font-weight:700; text-decoration:none; font-size:12px; transition:all 0.2s ease;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      <span>Terminar Sessão</span>
    </a>
  </div>
</nav>
<main class="main">
