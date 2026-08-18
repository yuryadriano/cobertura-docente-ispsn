<?php
/**
 * View: Relatório Oficial em PDF / Impressão — Plano de Cobertura Docente
 * sftcoordenacao — ISPSN 2026/27
 */

$cursoId = (int)($_GET['curso_id'] ?? 1);
$anoLectivo = $_GET['ano_lectivo'] ?? '2026/27';

$curso = $cursoModel->getById($cursoId);
if (!$curso) {
    echo "Curso não encontrado.";
    exit;
}

$plano = $planoModel->getByCursoEAno($cursoId, $anoLectivo);
$linhas = $plano ? $planoModel->getLinhasPlano($plano['id'], $anoLectivo) : [];

$totalUC = count($linhas);
$atribucaoCount = 0;
$confSimCount = 0;
foreach ($linhas as $l) {
    if (!empty($l['docente_id'])) $atribucaoCount++;
    if ($l['conformidade'] === 'Sim') $confSimCount++;
}
$pctConf = $totalUC ? round(($confSimCount / $totalUC) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Relatório Oficial de Cobertura Docente — <?= htmlspecialchars($curso['nome']) ?> (<?= $anoLectivo ?>)</title>
<style>
  @page {
    size: A4 landscape;
    margin: 12mm 15mm;
  }
  
  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    color: #1A1D20;
    background: #FFF;
    margin: 0;
    padding: 20px;
    font-size: 11px;
    line-height: 1.3;
  }

  .no-print-bar {
    background: #1F4E79;
    color: #FFF;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .btn-print {
    background: #E8B10C;
    color: #1A1A1A;
    border: none;
    padding: 8px 18px;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
    font-size: 13px;
  }

  .header-table {
    width: 100%;
    border-bottom: 2px solid #E8B10C;
    padding-bottom: 12px;
    margin-bottom: 16px;
  }

  .logo-img {
    width: 68px;
    height: 68px;
    object-fit: contain;
  }

  .title-main {
    font-size: 18px;
    font-weight: 800;
    color: #1F4E79;
    text-transform: uppercase;
  }

  .title-sub {
    font-size: 13px;
    color: #555;
    font-weight: 600;
  }

  .kpi-box {
    display: table;
    width: 100%;
    margin-bottom: 16px;
    background: #F8F9FA;
    border: 1px solid #DEE2E6;
    border-radius: 6px;
    padding: 10px;
  }

  .kpi-cell {
    display: table-cell;
    text-align: center;
    border-right: 1px solid #DEE2E6;
    padding: 0 10px;
  }

  .kpi-cell:last-child {
    border-right: none;
  }

  .kpi-val {
    font-size: 16px;
    font-weight: 800;
    color: #1F4E79;
  }

  .kpi-lbl {
    font-size: 10px;
    color: #6C757D;
    text-transform: uppercase;
    font-weight: 700;
  }

  table.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    font-size: 10px;
  }

  table.report-table th {
    background: #F4F2EC;
    color: #1F4E79;
    border: 1px solid #CBD5E1;
    padding: 6px;
    text-align: left;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 9px;
  }

  table.report-table td {
    border: 1px solid #E2E8F0;
    padding: 5px 6px;
    vertical-align: middle;
  }

  .badge-conf {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 700;
    display: inline-block;
  }

  .badge-sim { background: #E5F4EA; color: #1E8449; }
  .badge-parcial { background: #FEF9E7; color: #B9770E; }
  .badge-nao { background: #FBEAE8; color: #C0392B; }

  .signatures-container {
    margin-top: 35px;
    width: 100%;
    page-break-inside: avoid;
  }

  .sig-box {
    width: 30%;
    display: inline-block;
    vertical-align: top;
    text-align: center;
    padding: 0 1.5%;
  }

  .sig-line {
    border-top: 1.5px solid #333;
    margin-top: 40px;
    padding-top: 6px;
    font-weight: 700;
    font-size: 11px;
  }

  .sig-role {
    font-size: 10px;
    color: #666;
  }

  @media print {
    .no-print-bar { display: none !important; }
    body { padding: 0; }
  }
</style>
</head>
<body>

<div class="no-print-bar">
  <div>
    <strong>📄 Módulo de Cobertura Docente ISPSN</strong> — Vista de Impressão Oficial em PDF
  </div>
  <div>
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar em PDF</button>
    <button class="btn-print" style="background:#FFF; color:#333; margin-left:8px;" onclick="window.close()">Fechar Janela</button>
  </div>
</div>

<table class="header-table">
  <tr>
    <td style="width:80px;">
      <img src="assets/img/logo.png" class="logo-img" alt="Logo ISPSN" onerror="this.style.display='none'">
    </td>
    <td>
      <div class="title-main">Instituto Superior Politécnico Sol Nascente</div>
      <div class="title-sub">Direção Académica · Mapa Oficial de Cobertura Docente — Ano Lectivo <?= htmlspecialchars($anoLectivo) ?></div>
      <div style="font-size:11px; font-weight:700; color:#E8B10C; margin-top:2px;">
        Curso de Licenciatura em <?= htmlspecialchars($curso['nome']) ?> (Código: <?= htmlspecialchars($curso['codigo']) ?>)
      </div>
    </td>
    <td style="text-align:right; vertical-align:top; font-size:10px; color:#666;">
      <div>Data de Emissão: <?= date('d/m/Y H:i') ?></div>
      <div>Estado do Plano: <strong><?= htmlspecialchars($plano['estado'] ?? 'Rascunho') ?></strong></div>
      <div>Homologação: MESCTI / INAAREES</div>
    </td>
  </tr>
</table>

<div class="kpi-box">
  <div class="kpi-cell">
    <div class="kpi-val"><?= $totalUC ?></div>
    <div class="kpi-lbl">Total Atribuições (Turma × UC)</div>
  </div>
  <div class="kpi-cell">
    <div class="kpi-val" style="color:#1E8449;"><?= $atribucaoCount ?></div>
    <div class="kpi-lbl">Com Docente Atribuído</div>
  </div>
  <div class="kpi-cell">
    <div class="kpi-val" style="color:<?= $totalUC - $atribucaoCount > 0 ? '#C0392B' : '#1E8449' ?>;"><?= $totalUC - $atribucaoCount ?></div>
    <div class="kpi-lbl">Sem Docente</div>
  </div>
  <div class="kpi-cell">
    <div class="kpi-val" style="color:<?= $pctConf >= 70 ? '#1E8449' : '#B9770E' ?>;"><?= $pctConf ?>%</div>
    <div class="kpi-lbl">Conformidade Pedagógica Prevista</div>
  </div>
</div>

<table class="report-table">
  <thead>
    <tr>
      <th style="width:30px;">#</th>
      <th style="width:65px;">Turma</th>
      <th style="width:40px;">Ano</th>
      <th style="width:45px;">Sem.</th>
      <th>Unidade Curricular</th>
      <th>Docente Atribuído</th>
      <th style="width:80px;">Grau Académico</th>
      <th style="width:120px;">Especialidade</th>
      <th style="width:60px;">INAAREES</th>
      <th style="width:85px;">Conformidade</th>
      <th style="width:90px;">Justificação</th>
      <th style="width:85px;">Regime</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($linhas)): ?>
      <tr>
        <td colspan="12" style="text-align:center; padding:20px; color:#777;">Nenhuma disciplina registada neste plano.</td>
      </tr>
    <?php else: ?>
      <?php $idx = 1; foreach ($linhas as $l): ?>
        <?php 
          $conf = $l['conformidade'] ?? 'Por verificar';
          $bClass = ($conf === 'Sim') ? 'badge-sim' : (($conf === 'Parcial') ? 'badge-parcial' : 'badge-nao');
        ?>
        <tr>
          <td style="text-align:center; font-weight:700; color:#666;"><?= $idx++ ?></td>
          <?php 
            $turmaCod = $l['turma_nome'] ?? ('TURMA-' . $l['ano_curricular'] . 'A');
            $turno = $l['turno'] ?? 'Manhã';
            preg_match('/(?:[0-9]|RB[0-9]?)([A-Z])$/i', $turmaCod, $matches);
            $letra = !empty($matches[1]) ? strtoupper($matches[1]) : '';
            $labelLetra = $letra ? "Turma {$letra}" : "Turma Única";
            $turnoTag = (strpos($turmaCod, 'RB') !== false) ? 'Regime B' : $turno;
          ?>
          <td>
            <div style="font-weight:700; font-size:11px; color:#1F4E79;"><?= $labelLetra ?> (<?= htmlspecialchars($turnoTag) ?>)</div>
            <div style="font-size:9.5px; color:#666; font-family:monospace;"><?= htmlspecialchars($turmaCod) ?></div>
          </td>
          <td style="text-align:center;"><?= $l['ano_curricular'] ?>.º</td>
          <td style="text-align:center;"><?= htmlspecialchars($l['semestre']) ?></td>
          <td><strong><?= htmlspecialchars($l['disciplina_nome']) ?></strong></td>
          <td>
            <?= !empty($l['docente_nome']) ? htmlspecialchars($l['docente_nome']) : '<span style="color:#C0392B; font-weight:700;">Sem Docente</span>' ?>
          </td>
          <td><?= htmlspecialchars($l['docente_grau'] ?? '—') ?></td>
          <td><?= htmlspecialchars($l['docente_especialidade'] ?? '—') ?></td>
          <td style="text-align:center;"><?= htmlspecialchars($l['docente_inaarees'] ?? '—') ?></td>
          <td><span class="badge-conf <?= $bClass ?>"><?= htmlspecialchars($conf) ?></span></td>
          <td><?= htmlspecialchars($l['justificacao'] ?? '—') ?></td>
          <td><?= htmlspecialchars($l['regime'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<!-- Bloco Oficial de Assinaturas e Validação Institucional -->
<div class="signatures-container">
  <div class="sig-box">
    <div class="sig-line">O Coordenador de Curso</div>
    <div class="sig-role">Elaborado e Submetido</div>
  </div>
  <div class="sig-box">
    <div class="sig-line">Gestão Académica / GRH</div>
    <div class="sig-role">Verificado e Parecer</div>
  </div>
  <div class="sig-box">
    <div class="sig-line">O Presidente do ISPSN</div>
    <div class="sig-role">Homologado e Aprovado</div>
  </div>
</div>

</body>
</html>
