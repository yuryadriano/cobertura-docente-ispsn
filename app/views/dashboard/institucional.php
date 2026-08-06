<?php
/**
 * View Dashboard Institucional Executivo — FrontOffice BI
 * sftcoordenacao — ISPSN 2026/27
 * Organizado em sistema de 4 abas temáticas com KPI Tiles fixos no topo
 */

$totDocentes = $qualificacoes['total'] ?? 258;
$doutores    = $qualificacoes['doutores'] ?? 0;
$mestres     = $qualificacoes['mestres'] ?? 0;
$licMest     = $qualificacoes['lic_mest_em_curso'] ?? 0;
$licenciados = $qualificacoes['licenciados'] ?? 0;

$pctMestresDoutores = ($totDocentes > 0) ? round((($doutores + $mestres) / $totDocentes) * 100, 1) : 0;

$inaSim = $pilares['inaarees_sim'] ?? 0;
$inaNao = $pilares['inaarees_nao'] ?? 0;
$inaNi  = $pilares['inaarees_ni'] ?? 0;
$capSim = $pilares['capacitação_sim'] ?? $pilares['cap_sim'] ?? 0;
$capNao = $pilares['capacitação_nao'] ?? $pilares['cap_nao'] ?? 0;
$capNi  = $pilares['capacitação_ni']  ?? $pilares['cap_ni']  ?? 0;
$carSim = $pilares['carreira_sim'] ?? 0;
$carNao = $pilares['carreira_nao'] ?? 0;
$carNi  = $pilares['carreira_ni'] ?? 0;

$pctCarreira = ($totDocentes > 0) ? round(($carSim / $totDocentes) * 100, 1) : 0;

$c0 = $sobrecarga['c0'] ?? 0;
$c1 = $sobrecarga['c1'] ?? 0;
$c2 = $sobrecarga['c2'] ?? 0;
$c3 = $sobrecarga['c3'] ?? 0;
$c4 = $sobrecarga['c4'] ?? 0;
$c5plus = $sobrecarga['c5_plus'] ?? 0;
$docs3  = $sobrecarga['em_sobrecarga'] ?? 0;

$totalUCsGlobal = 0;
$confSimGlobal  = 0;
foreach ($cursosStats as $cs) {
    $totalUCsGlobal += (int)$cs['total_uc'];
    $confSimGlobal  += (int)$cs['conf_sim'];
}
$pctConfGlobal = ($totalUCsGlobal > 0) ? round(($confSimGlobal / $totalUCsGlobal) * 100, 1) : 0;

// Ranking ordenado por % decrescente
$ranking = $cursosStats;
usort($ranking, fn($a, $b) => $b['pct_conf'] <=> $a['pct_conf']);
?>

<style>
/* ── Dashboard BI: Tab System ── */
.bi-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px}
.bi-badge{background:linear-gradient(135deg,#1F4E79,#2E75B6);color:#fff;padding:5px 13px;border-radius:20px;font-size:11.5px;font-weight:700;letter-spacing:.02em}

.bi-tabs{display:flex;gap:4px;border-bottom:2px solid var(--line);margin:22px 0 0;flex-wrap:wrap}
.bi-tab{display:flex;align-items:center;gap:7px;padding:10px 18px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:600;color:var(--mut);border-bottom:3px solid transparent;margin-bottom:-2px;transition:color .2s,border-color .2s;border-radius:8px 8px 0 0;white-space:nowrap}
.bi-tab:hover{color:var(--blue);background:rgba(31,78,121,.04)}
.bi-tab.active{color:var(--blue);border-bottom-color:var(--gold);background:rgba(232,177,12,.05)}
.bi-tab .bi-icon{font-size:16px;line-height:1}

.bi-panel{display:none;animation:fadeIn .25s ease}
.bi-panel.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}

/* Pilar blocks */
.pilar-block{background:#fff;border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin-bottom:14px}
.pilar-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.pilar-title strong{font-size:13.5px;color:var(--blue)}
.pilar-counters{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}
.pilar-count{display:flex;flex-direction:column;align-items:center;background:#f7f9fc;border:1px solid var(--line);border-radius:8px;padding:8px 14px;flex:1;min-width:80px}
.pilar-count .pc-n{font-size:22px;font-weight:800;line-height:1}
.pilar-count .pc-l{font-size:10.5px;color:var(--mut);margin-top:3px;font-weight:600;text-align:center}
.pilar-count.ok{border-color:rgba(30,132,73,.25);background:var(--okf)}.pilar-count.ok .pc-n{color:var(--ok)}
.pilar-count.bad{border-color:rgba(192,57,43,.25);background:var(--badf)}.pilar-count.bad .pc-n{color:var(--bad)}
.pilar-count.ni{border-color:#ddd;background:#f5f5f5}.pilar-count.ni .pc-n{color:var(--mut)}

/* Sobrecarga alerta inline */
.sob-row{display:flex;align-items:center;gap:10px;margin:7px 0;font-size:12.5px}
.sob-row .sr-label{width:160px;color:var(--ink)}
.sob-row .sr-bar{flex:1;height:14px;background:#eee;border-radius:5px;overflow:hidden}
.sob-row .sr-bar>i{display:block;height:100%;transition:width .4s cubic-bezier(.4,0,.2,1)}
.sob-row .sr-val{width:54px;text-align:right;font-weight:700;color:var(--mut)}
.sob-alert{display:flex;align-items:center;gap:10px;background:var(--badf);border:1px solid rgba(192,57,43,.2);border-radius:10px;padding:11px 15px;margin-top:14px;font-size:12.5px;color:var(--bad)}
.sob-alert strong{font-size:24px;font-weight:800;margin-right:4px}
</style>

<!-- CABEÇALHO DO DASHBOARD -->
<div class="bi-header">
    <div>
        <h2 class="page" style="margin:0; display:flex; align-items:center; gap:10px;">
            📊 Dashboard Institucional Executivo (BI)
        </h2>
        <div class="sub" style="margin:4px 0 0;">Retrato executivo do corpo docente do ISPSN — conformidade pedagógica, qualificações e regularização.</div>
    </div>
    
    <div style="display:flex; align-items:center; gap:10px; background:#fff; border:1px solid var(--line); border-radius:10px; padding:6px 12px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
        <label style="font-size:12px; font-weight:700; color:var(--blue); margin:0;">📅 Ano Lectivo BI:</label>
        <select id="bi-ano-lectivo-sel" style="font-weight:700; font-size:13px; border:1px solid var(--blue); color:var(--blue); border-radius:6px; padding:4px 8px; background:#f0f5fb;" onchange="window.location.href='index.php?page=dashboard&ano_lectivo='+encodeURIComponent(this.value)">
            <option value="2025/26" <?= ($anoLectivo ?? '2026/27') === '2025/26' ? 'selected' : '' ?>>2025/2026 (Histórico Transato)</option>
            <option value="2026/27" <?= ($anoLectivo ?? '2026/27') === '2026/27' ? 'selected' : '' ?>>2026/2027 (Ano Ativo em Curso)</option>
            <option value="2027/28" <?= ($anoLectivo ?? '2026/27') === '2027/28' ? 'selected' : '' ?>>2027/2028 (Ano Roll-Over)</option>
        </select>
    </div>
</div>

<!-- 4 KPI TILES — SEMPRE VISÍVEIS -->
<div class="tiles">
    <div class="tile <?= $pctConfGlobal >= 70 ? 'good' : ($pctConfGlobal >= 50 ? 'warn' : 'bad') ?>">
        <div class="k">Conformidade disciplina–perfil</div>
        <div class="v"><?= $pctConfGlobal ?>%</div>
        <div class="d"><?= $confSimGlobal ?> de <?= $totalUCsGlobal ?> UCs avaliadas</div>
    </div>
    <div class="tile <?= $pctMestresDoutores >= 60 ? 'good' : 'warn' ?>">
        <div class="k">Mestres + Doutores</div>
        <div class="v"><?= $pctMestresDoutores ?>%</div>
        <div class="d"><?= $doutores ?> doutores · <?= $mestres ?> mestres</div>
    </div>
    <div class="tile <?= $pctCarreira >= 50 ? 'good' : 'bad' ?>">
        <div class="k">Inseridos na carreira</div>
        <div class="v"><?= $pctCarreira ?>%</div>
        <div class="d"><?= $carSim ?> de <?= $totDocentes ?> docentes</div>
    </div>
    <div class="tile <?= $docs3 > 10 ? 'bad' : ($docs3 > 0 ? 'warn' : 'good') ?>">
        <div class="k">Docentes em ≥3 cursos</div>
        <div class="v"><?= $docs3 ?></div>
        <div class="d">risco de sobrecarga pedagógica</div>
    </div>
</div>

<!-- NAVEGAÇÃO POR ABAS -->
<div class="bi-tabs">
    <button class="bi-tab active" onclick="biTab(this,'bi-conf')" id="tab-conf">
        <span class="bi-icon">📊</span> Conformidade Pedagógica
    </button>
    <button class="bi-tab" onclick="biTab(this,'bi-qual')" id="tab-qual">
        <span class="bi-icon">🎓</span> Qualificações Académicas
    </button>
    <button class="bi-tab" onclick="biTab(this,'bi-reg')" id="tab-reg">
        <span class="bi-icon">📋</span> Regularização Documental
    </button>
    <button class="bi-tab" onclick="biTab(this,'bi-sob')" id="tab-sob">
        <span class="bi-icon">⚡</span> Sobrecarga &amp; Partilha
    </button>
    <button class="bi-tab" onclick="biTab(this,'bi-evo')" id="tab-evo">
        <span class="bi-icon">📈</span> Evolução Temporal (2025/26 ➔ 2026/27)
    </button>
</div>

<!-- ════════ ABA 1: CONFORMIDADE PEDAGÓGICA ════════ -->
<div class="bi-panel active" id="bi-conf">
    <div class="card" style="margin-top:20px;">
        <h3>Conformidade disciplina–perfil por curso</h3>
        <p class="cap">Percentagem de UCs cujo docente atribuído tem perfil de especialidade alinhado à disciplina. Meta INAAREES: ≥ 70%.</p>

        <?php foreach ($cursosStats as $cs): ?>
            <?php
                $tot = max(1, (int)$cs['total_uc']);
                $sim = (int)$cs['conf_sim'];
                $nao = (int)$cs['conf_nao'] + (int)$cs['conf_parcial'];
                $ni  = (int)$cs['conf_ni'];
                $pct = $cs['pct_conf'];
                $colorPct = ($pct >= 70) ? 'var(--ok)' : (($pct >= 50) ? 'var(--warn)' : 'var(--bad)');
            ?>
            <div class="hbar">
                <div class="lbl">
                    <b><?= htmlspecialchars($cs['curso_nome']) ?></b>
                    <span style="color:<?= $colorPct ?>;font-weight:700;"><?= $pct ?>%</span>
                </div>
                <div class="track">
                    <i style="width:<?= round(($sim/$tot)*100,1) ?>%;background:var(--s1);" title="Conformes: <?= $sim ?>"></i>
                    <i style="width:<?= round(($nao/$tot)*100,1) ?>%;background:var(--bad);" title="Não conforme: <?= $nao ?>"></i>
                    <i style="width:<?= round(($ni/$tot)*100,1) ?>%;background:#ccc;" title="Pendente: <?= $ni ?>"></i>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="legend">
            <span><span class="sw" style="background:var(--s1)"></span>Em conformidade</span>
            <span><span class="sw" style="background:var(--bad)"></span>Sem conformidade</span>
            <span><span class="sw" style="background:#ccc"></span>Pendente / Não verificado</span>
        </div>
    </div>
</div>

<!-- ════════ ABA 2: QUALIFICAÇÕES ACADÉMICAS ════════ -->
<div class="bi-panel" id="bi-qual">
    <div class="card" style="margin-top:20px;">
        <h3>Qualificações académicas do corpo docente</h3>
        <p class="cap"><?= $totDocentes ?> docentes únicos cadastrados no sistema · grau académico mais elevado concluído</p>

        <div class="sbar">
            <div class="n">Doutores</div>
            <div class="t"><i style="width:<?= round(($doutores/$totDocentes)*100,1) ?>%;background:var(--violet);"></i></div>
            <div class="val" style="color:var(--violet);"><?= $doutores ?></div>
        </div>
        <div class="sbar">
            <div class="n">Mestres</div>
            <div class="t"><i style="width:<?= round(($mestres/$totDocentes)*100,1) ?>%;background:var(--s1);"></i></div>
            <div class="val" style="color:var(--s1);"><?= $mestres ?></div>
        </div>
        <div class="sbar">
            <div class="n">Lic. (mestrado em curso)</div>
            <div class="t"><i style="width:<?= round(($licMest/$totDocentes)*100,1) ?>%;background:var(--s3);"></i></div>
            <div class="val" style="color:var(--s3);"><?= $licMest ?></div>
        </div>
        <div class="sbar">
            <div class="n">Licenciados</div>
            <div class="t"><i style="width:<?= round(($licenciados/$totDocentes)*100,1) ?>%;background:var(--s2);"></i></div>
            <div class="val" style="color:var(--s2);"><?= $licenciados ?></div>
        </div>

        <div style="border-top:1px solid var(--line);margin-top:18px;padding-top:14px;display:flex;gap:18px;flex-wrap:wrap;">
            <div class="tile good" style="flex:1;min-width:160px;padding:14px">
                <div class="k">Rácio Mestres + Doutores</div>
                <div class="v"><?= $pctMestresDoutores ?>%</div>
                <div class="d">Meta INAAREES: ≥ 60%</div>
            </div>
            <div class="tile" style="flex:1;min-width:160px;padding:14px">
                <div class="k">Total Doutores</div>
                <div class="v" style="color:var(--violet)"><?= $doutores ?></div>
                <div class="d"><?= round(($doutores/$totDocentes)*100,1) ?>% do corpo docente</div>
            </div>
            <div class="tile" style="flex:1;min-width:160px;padding:14px">
                <div class="k">Licenciados sem pós-grad.</div>
                <div class="v" style="color:var(--warn)"><?= $licenciados ?></div>
                <div class="d">Em processo de melhoria</div>
            </div>
        </div>
    </div>
</div>

<!-- ════════ ABA 3: REGULARIZAÇÃO DOCUMENTAL ════════ -->
<div class="bi-panel" id="bi-reg">
    <div style="margin-top:20px;">
        <p class="lead" style="margin-bottom:16px;">Estado de regularização do corpo docente nos <strong>3 pilares obrigatórios</strong> para acreditação e homologação INAAREES.</p>

        <!-- PILAR 1 -->
        <div class="pilar-block">
            <div class="pilar-title">
                <strong>1. Declaração INAAREES</strong>
                <span class="pill <?= ($inaSim/$totDocentes) >= .7 ? 'ok' : (($inaSim/$totDocentes) >= .5 ? 'warn' : 'bad') ?>"><?= round(($inaSim/$totDocentes)*100,1) ?>% Regularizados</span>
            </div>
            <div class="hbar">
                <div class="track" style="height:20px;border-radius:8px;">
                    <i style="width:<?= round(($inaSim/$totDocentes)*100,1) ?>%;background:var(--s1);"></i>
                    <i style="width:<?= round(($inaNao/$totDocentes)*100,1) ?>%;background:var(--bad);"></i>
                    <i style="width:<?= round(($inaNi/$totDocentes)*100,1) ?>%;background:#ccc;"></i>
                </div>
            </div>
            <div class="pilar-counters">
                <div class="pilar-count ok"><span class="pc-n"><?= $inaSim ?></span><span class="pc-l">Regularizados</span></div>
                <div class="pilar-count bad"><span class="pc-n"><?= $inaNao ?></span><span class="pc-l">Pendentes</span></div>
                <div class="pilar-count ni"><span class="pc-n"><?= $inaNi ?></span><span class="pc-l">Não identificado</span></div>
            </div>
        </div>

        <!-- PILAR 2 -->
        <div class="pilar-block">
            <div class="pilar-title">
                <strong>2. Capacitação Psicopedagógica</strong>
                <span class="pill <?= ($capSim/$totDocentes) >= .7 ? 'ok' : (($capSim/$totDocentes) >= .5 ? 'warn' : 'bad') ?>"><?= round(($capSim/$totDocentes)*100,1) ?>% Regularizados</span>
            </div>
            <div class="hbar">
                <div class="track" style="height:20px;border-radius:8px;">
                    <i style="width:<?= round(($capSim/$totDocentes)*100,1) ?>%;background:var(--s1);"></i>
                    <i style="width:<?= round(($capNao/$totDocentes)*100,1) ?>%;background:var(--bad);"></i>
                    <i style="width:<?= round(($capNi/$totDocentes)*100,1) ?>%;background:#ccc;"></i>
                </div>
            </div>
            <div class="pilar-counters">
                <div class="pilar-count ok"><span class="pc-n"><?= $capSim ?></span><span class="pc-l">Regularizados</span></div>
                <div class="pilar-count bad"><span class="pc-n"><?= $capNao ?></span><span class="pc-l">Pendentes</span></div>
                <div class="pilar-count ni"><span class="pc-n"><?= $capNi ?></span><span class="pc-l">Não identificado</span></div>
            </div>
        </div>

        <!-- PILAR 3 -->
        <div class="pilar-block">
            <div class="pilar-title">
                <strong>3. Inserção na Carreira (Prova Pública)</strong>
                <span class="pill <?= ($carSim/$totDocentes) >= .5 ? 'ok' : (($carSim/$totDocentes) >= .3 ? 'warn' : 'bad') ?>"><?= round(($carSim/$totDocentes)*100,1) ?>% Regularizados</span>
            </div>
            <div class="hbar">
                <div class="track" style="height:20px;border-radius:8px;">
                    <i style="width:<?= round(($carSim/$totDocentes)*100,1) ?>%;background:var(--s1);"></i>
                    <i style="width:<?= round(($carNao/$totDocentes)*100,1) ?>%;background:var(--bad);"></i>
                    <i style="width:<?= round(($carNi/$totDocentes)*100,1) ?>%;background:#ccc;"></i>
                </div>
            </div>
            <div class="pilar-counters">
                <div class="pilar-count ok"><span class="pc-n"><?= $carSim ?></span><span class="pc-l">Regularizados</span></div>
                <div class="pilar-count bad"><span class="pc-n"><?= $carNao ?></span><span class="pc-l">Pendentes</span></div>
                <div class="pilar-count ni"><span class="pc-n"><?= $carNi ?></span><span class="pc-l">Não identificado</span></div>
            </div>
        </div>

        <div class="legend" style="margin-top:6px;">
            <span><span class="sw" style="background:var(--s1)"></span>Regularizado</span>
            <span><span class="sw" style="background:var(--bad)"></span>Pendente / Não</span>
            <span><span class="sw" style="background:#ccc"></span>Não identificado</span>
        </div>
    </div>
</div>

<!-- ════════ ABA 4: SOBRECARGA & RANKING ════════ -->
<div class="bi-panel" id="bi-sob">
    <div class="grid2" style="margin-top:20px;">
        <!-- SOBRECARGA -->
        <div class="card">
            <h3>Partilha de docentes entre cursos</h3>
            <p class="cap">Número de cursos por docente — acima de 3 há risco de sobrecarga pedagógica</p>

            <div class="sob-row">
                <div class="sr-label" style="color:var(--mut);">Sem atribuições (0 cursos)</div>
                <div class="sr-bar"><i style="width:<?= round(($c0/$totDocentes)*100,1) ?>%;background:#ddd;"></i></div>
                <div class="sr-val" style="color:var(--mut);"><?= $c0 ?></div>
            </div>
            <div class="sob-row">
                <div class="sr-label">1 curso (exclusivo)</div>
                <div class="sr-bar"><i style="width:<?= round(($c1/$totDocentes)*100,1) ?>%;background:var(--s3);"></i></div>
                <div class="sr-val"><?= $c1 ?></div>
            </div>
            <div class="sob-row">
                <div class="sr-label">2 cursos</div>
                <div class="sr-bar"><i style="width:<?= round(($c2/$totDocentes)*100,1) ?>%;background:var(--s1);"></i></div>
                <div class="sr-val"><?= $c2 ?></div>
            </div>
            <div class="sob-row">
                <div class="sr-label">3 cursos</div>
                <div class="sr-bar"><i style="width:<?= round(($c3/$totDocentes)*100,1) ?>%;background:var(--s4);"></i></div>
                <div class="sr-val"><?= $c3 ?></div>
            </div>
            <div class="sob-row">
                <div class="sr-label">4 cursos</div>
                <div class="sr-bar"><i style="width:<?= round(($c4/$totDocentes)*100,1) ?>%;background:#eb6834;"></i></div>
                <div class="sr-val"><?= $c4 ?></div>
            </div>
            <div class="sob-row">
                <div class="sr-label">5 ou mais cursos</div>
                <div class="sr-bar"><i style="width:<?= round(($c5plus/$totDocentes)*100,1) ?>%;background:var(--bad);"></i></div>
                <div class="sr-val"><?= $c5plus ?></div>
            </div>

            <?php if ($docs3 > 0): ?>
            <div class="sob-alert">
                <strong><?= $docs3 ?></strong> docente(s) em ≥3 cursos simultaneamente — risco de sobrecarga pedagógica identificado.
            </div>
            <?php else: ?>
            <div style="display:flex;align-items:center;gap:8px;background:var(--okf);border:1px solid rgba(30,132,73,.2);border-radius:10px;padding:11px 15px;margin-top:14px;font-size:12.5px;color:var(--ok);">
                ✅ Sem docentes em situação de sobrecarga detectada.
            </div>
            <?php endif; ?>
        </div>

        <!-- RANKING -->
        <div class="card">
            <h3>Ranking de conformidade por curso</h3>
            <p class="cap">Da situação mais sólida à mais crítica · ordenado por % de conformidade decrescente</p>
            <div class="tbl-wrap" style="border:none;">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Curso</th>
                            <th class="num">UCs</th>
                            <th class="num">% conf.</th>
                            <th class="num">Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ranking as $i => $r): ?>
                        <tr>
                            <td style="color:var(--mut);font-weight:700;font-size:12px;"><?= $i + 1 ?>º</td>
                            <td><strong><?= htmlspecialchars($r['curso_nome']) ?></strong></td>
                            <td class="num"><?= $r['total_uc'] ?></td>
                            <td class="num" style="font-weight:700;color:<?= $r['pct_conf'] >= 70 ? 'var(--ok)' : ($r['pct_conf'] >= 50 ? 'var(--warn)' : 'var(--bad)') ?>;">
                                <?= $r['pct_conf'] ?>%
                            </td>
                            <td class="num">
                                <span class="pill <?= $r['situacao_class'] ?>"><?= htmlspecialchars($r['situacao']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================================================================== -->
<!-- ABA 5: EVOLUÇÃO TEMPORAL (2025/26 vs 2026/27)                       -->
<!-- ==================================================================== -->
<div class="bi-panel" id="bi-evo">
    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:20px; margin-bottom:20px;">
        <h3 style="margin-top:0; color:var(--blue); font-size:16px;">📈 Análise Evolutiva e Comparativa entre Anos Lectivos</h3>
        <p style="font-size:13px; color:var(--mut); margin-bottom:18px;">
            Comparativo paralelo da conformidade pedagógica entre a linha de base histórica (2025/2026) e o ano letivo em curso (2026/2027).
        </p>

        <div class="tbl-wrap">
            <table class="tbl" style="width:100%; border-collapse:collapse; font-size:12.5px;">
                <thead>
                    <tr style="background:#f4f2ec; text-align:left;">
                        <th style="padding:10px 12px;">Curso Académico</th>
                        <th style="padding:10px 12px; text-align:center;">2025/2026 (% Conf.)</th>
                        <th style="padding:10px 12px; text-align:center;">2026/2027 (% Conf.)</th>
                        <th style="padding:10px 12px; text-align:center;">Variação (% Dif.)</th>
                        <th style="padding:10px 12px; text-align:center;">Tendência</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($comparativoAnual)): ?>
                        <?php foreach ($comparativoAnual as $cmp): ?>
                            <?php 
                                $var = $cmp['variacao_pct'];
                                $varClass = $var > 0 ? 'pct-good' : ($var < 0 ? 'pct-bad' : 'pct-warn');
                                $icon = $var > 0 ? '↗ +'.$var.'%' : ($var < 0 ? '↘ '.$var.'%' : '➔ 0%');
                            ?>
                            <tr style="border-bottom:1px solid var(--line);">
                                <td style="padding:10px 12px;"><strong><?= htmlspecialchars($cmp['curso_nome']) ?></strong></td>
                                <td style="padding:10px 12px; text-align:center;">
                                    <?= $cmp['anoA']['pct'] ?>% <span style="font-size:11px; color:var(--mut);"> (<?= $cmp['anoA']['conf_sim'] ?>/<?= $cmp['anoA']['total_uc'] ?>)</span>
                                </td>
                                <td style="padding:10px 12px; text-align:center;">
                                    <b><?= $cmp['anoB']['pct'] ?>%</b> <span style="font-size:11px; color:var(--mut);"> (<?= $cmp['anoB']['conf_sim'] ?>/<?= $cmp['anoB']['total_uc'] ?>)</span>
                                </td>
                                <td style="padding:10px 12px; text-align:center;">
                                    <span class="pill <?= $var > 0 ? 'ok' : ($var < 0 ? 'bad' : 'mut') ?>" style="font-weight:700;">
                                        <?= $icon ?>
                                    </span>
                                </td>
                                <td style="padding:10px 12px; text-align:center;">
                                    <?= $var > 0 ? '✅ Evolução Positiva' : ($var < 0 ? '⚠️ Regressão Pedagógica' : '➖ Estável') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px; color:var(--mut);">Sem dados comparativos suficientes.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function biTab(btn, panelId) {
    document.querySelectorAll('.bi-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.bi-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(panelId).classList.add('active');
}
</script>
