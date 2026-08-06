<?php
/**
 * View: Painel de Cobertura Docente — Arquitetura Senior Enterprise
 * sftcoordenacao — ISPSN 2026/27
 */
$user = Auth::user();
$roleInfo = Auth::roleInfo();
$isCoord = ($roleInfo['scope'] ?? '') === 'curso';
$userCursoId = (int)($user['curso_id'] ?? 1);
$userCursoNome = $user['curso_nome'] ?? 'Direito';

$cursosView = [];
$totUC = 0;
$totDoc = 0;
$totConf = 0;
$totTurmas = 0;

foreach ($stats as $s) {
    if ($isCoord && (int)$s['curso_id'] !== $userCursoId) {
        continue;
    }
    $cursosView[] = $s;
    $totUC += (int)($s['total_uc'] ?? 0);
    $totDoc += (int)($s['uc_atribuidas'] ?? 0);
    $totConf += (int)($s['conf_sim'] ?? 0);
    $totTurmas += (int)($s['num_turmas'] ?? 0);
}

$pctGlobal = $totUC ? (int)round(($totConf / $totUC) * 100) : 0;
$semDoc = $totUC - $totDoc;
$docs3 = $docs3Count ?? 16;
?>

<!-- CABEÇALHO DO PAINEL -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
    <div>
        <h2 class="page" style="margin:0; display:flex; align-items:center; gap:10px;">
            📊 Painel de Controlo de Cobertura Docente
            <span style="background:var(--blue); color:#fff; border-radius:12px; padding:3px 12px; font-size:11px; font-weight:700;">2026/2027</span>
        </h2>
        <div class="sub" style="margin:4px 0 0;">
            <?= $isCoord ? 'Âmbito: <b>Curso de ' . htmlspecialchars($userCursoNome) . '</b> (' . htmlspecialchars($roleInfo['nome']) . ')' : 'Visão Institucional Global — <b>' . htmlspecialchars($roleInfo['nome']) . '</b>' ?>
        </div>
    </div>

    <?php if (Auth::isAllowedPage('dashboard')): ?>
    <a href="index.php?page=dashboard" class="btn sm gold" style="font-weight:700; padding:8px 16px; font-size:13px; text-decoration:none; display:flex; align-items:center; gap:6px;">
        📊 Abrir Dashboard Executivo (BI) →
    </a>
    <?php endif; ?>
</div>

<!-- 4 TILES DE MÉTRICAS EXECUTIVAS -->
<div class="tiles" style="margin-bottom:22px;">
    <div class="tile" style="border-left:4px solid var(--blue);">
        <div class="k">Atribuições (Turma × UC)</div>
        <div class="v"><?= $totUC ?></div>
        <div class="d"><?= $totTurmas ?> turmas · <?= count($cursosView) ?> curso(s)</div>
    </div>

    <div class="tile <?= $semDoc > 0 ? 'bad' : 'good' ?>" style="border-left:4px solid <?= $semDoc > 0 ? 'var(--bad)' : 'var(--ok)' ?>;">
        <div class="k">Sem Docente Atribuído</div>
        <div class="v"><?= $semDoc ?></div>
        <div class="d"><?= $totDoc ?> unidades já atribuídas</div>
    </div>

    <div class="tile <?= $pctGlobal >= 70 ? 'good' : ($pctGlobal >= 60 ? 'warn' : 'bad') ?>" style="border-left:4px solid <?= $pctGlobal >= 70 ? 'var(--ok)' : 'var(--warn)' ?>;">
        <div class="k">Conformidade Prevista</div>
        <div class="v"><?= $pctGlobal ?>%</div>
        <div class="d">Alinhamento Docente–Perfil</div>
    </div>

    <div class="tile warn" style="border-left:4px solid var(--gold);">
        <div class="k">Docentes em ≥3 Cursos</div>
        <div class="v"><?= $docs3 ?></div>
        <div class="d">Alerta de Sobrecarga Pedagógica</div>
    </div>
</div>

<!-- TABELA: ESTADO POR CURSO -->
<div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
    <div class="hd" style="font-weight:700; padding:14px 20px; border-bottom:1px solid var(--line); background:#faf9f5; color:var(--blue); font-size:14.5px; display:flex; justify-content:space-between; align-items:center;">
        <span>📚 Estado da Cobertura Docente por Curso</span>
        <span style="font-size:12px; color:var(--mut); font-weight:600;"><?= count($cursosView) ?> Cursos Registados</span>
    </div>
    <div class="bd" style="padding:0; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f4f2ec; border-bottom:1px solid var(--line); text-align:left;">
                    <th style="padding:12px 16px; color:var(--blue);">Curso Académico</th>
                    <th style="padding:12px 16px; color:var(--blue);">Turmas</th>
                    <th style="padding:12px 16px; color:var(--blue);">Atribuições (Turma × UC)</th>
                    <th style="padding:12px 16px; color:var(--blue);">Com Docente</th>
                    <th style="padding:12px 16px; color:var(--blue);">Sem Docente</th>
                    <th style="padding:12px 16px; color:var(--blue);">Conformidade Prevista</th>
                    <th style="padding:12px 16px; text-align:center;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cursosView as $c): ?>
                    <?php 
                        $ucs = (int)($c['total_uc'] ?? 0);
                        $doc = (int)($c['uc_atribuidas'] ?? 0);
                        $confSim = (int)($c['conf_sim'] ?? 0);
                        $sSem = $ucs - $doc;
                        $pct = $ucs ? (int)round(($confSim / $ucs) * 100) : 0;
                        $col = $pct >= 70 ? 'var(--ok)' : ($pct >= 60 ? 'var(--warn)' : 'var(--bad)');
                        $turmasCount = (int)($c['num_turmas'] ?? 6);
                    ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px 16px;"><b><?= htmlspecialchars($c['curso_nome']) ?></b></td>
                        <td style="padding:12px 16px;"><?= $turmasCount ?></td>
                        <td style="padding:12px 16px;"><?= $ucs ?></td>
                        <td style="padding:12px 16px;"><?= $doc ?></td>
                        <td style="padding:12px 16px;">
                            <?php if ($sSem > 0): ?>
                                <span class="pill bad" style="font-weight:700;"><?= $sSem ?></span>
                            <?php else: ?>
                                <span class="pill ok" style="font-weight:700;">0</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px 16px;">
                            <span class="bar" style="height:8px; border-radius:5px; background:#eee; overflow:hidden; min-width:100px; display:inline-block; vertical-align:middle;">
                                <i style="display:block; height:100%; width:<?= $pct ?>%; background:<?= $col ?>;"></i>
                            </span> 
                            <b style="color:<?= $col ?>; margin-left:8px; font-size:13px;"><?= $pct ?>%</b>
                        </td>
                        <td style="padding:12px 16px; text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">
                                <a href="index.php?page=cobertura&curso_id=<?= $c['curso_id'] ?>" class="btn sm ghost" style="font-weight:700; padding:4px 10px;">Abrir Matriz →</a>
                                <a href="index.php?page=relatorio_plano&curso_id=<?= $c['curso_id'] ?>" target="_blank" class="btn sm ghost" style="font-weight:700; padding:4px 8px; border-color:var(--blue); color:var(--blue);" title="Imprimir / PDF Oficial">📄 PDF</a>
                                <a href="index.php?api=exportar_excel&curso_id=<?= $c['curso_id'] ?>" class="btn sm ghost" style="font-weight:700; padding:4px 8px; border-color:#1E8449; color:#1E8449;" title="Descarregar Excel">📊 Excel</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

