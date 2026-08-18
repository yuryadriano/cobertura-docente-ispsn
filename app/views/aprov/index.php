<?php
/**
 * View: Painel de Aprovações da Cobertura Docente (ISPSN 2026/27)
 * Suporte a fluxo RBAC em 2 Etapas: Chefe de Departamento & Presidência
 */
$canApprove = Auth::canApprove();
$currentUser = Auth::user();
$userRole = $currentUser['perfil'] ?? 'coordenador';
$userEmail = strtolower($currentUser['email'] ?? '');

// Helper institucional para classificar cursos por Departamento no ISPSN
function get_depto_curso(string $cursoNome): array {
    $c = mb_strtolower($cursoNome);
    if (strpos($c, 'enfermagem') !== false || strpos($c, 'análises') !== false || strpos($c, 'analises') !== false || strpos($c, 'fisioterapia') !== false || strpos($c, 'cardiopneumologia') !== false) {
        return [
            'id'     => 'saude',
            'nome'   => 'Ciências da Saúde',
            'chefe'  => 'Prof. Kianguembeni Canania',
            'cor'    => '#0E7490',
            'bg'     => '#E0F2FE',
            'border' => '#BAE6FD'
        ];
    }
    if (strpos($c, 'regime b') !== false || strpos($c, 'pedagógica') !== false || strpos($c, 'pedagogica') !== false) {
        return [
            'id'     => 'academicos',
            'nome'   => 'Assuntos Académicos',
            'chefe'  => 'Prof. Edmundo Francisco',
            'cor'    => '#6B21A8',
            'bg'     => '#F3E8FF',
            'border' => '#E9D5FF'
        ];
    }
    return [
        'id'     => 'sociais',
        'nome'   => 'Ciências Sociais e Humanas',
        'chefe'  => 'Prof. Boaventura Fernando',
        'cor'    => '#1D4ED8',
        'bg'     => '#DBEAFE',
        'border' => '#BFDBFE'
    ];
}

// Determinar aba padrão com base no Chefe autenticado
$defaultTab = 'all';
if ($userRole === 'chefe_departamento') {
    if (strpos($userEmail, 'boaventura') !== false) {
        $defaultTab = 'sociais';
    } elseif (strpos($userEmail, 'canania') !== false) {
        $defaultTab = 'saude';
    } elseif (strpos($userEmail, 'edmundo') !== false) {
        $defaultTab = 'academicos';
    }
}

// Contabilização de métricas por departamento
$deptCounts = [
    'all'        => ['total' => count($stats), 'pendentes' => 0, 'aprov_depto' => 0, 'validados' => 0],
    'sociais'    => ['total' => 0, 'pendentes' => 0, 'aprov_depto' => 0, 'validados' => 0],
    'saude'      => ['total' => 0, 'pendentes' => 0, 'aprov_depto' => 0, 'validados' => 0],
    'academicos' => ['total' => 0, 'pendentes' => 0, 'aprov_depto' => 0, 'validados' => 0]
];

foreach ($stats as $s) {
    $dep = get_depto_curso($s['curso_nome'] ?? '')['id'];
    $st = $s['estado'] ?? 'Rascunho';
    
    if (isset($deptCounts[$dep])) {
        $deptCounts[$dep]['total']++;
        if (in_array($st, ['Submetido', 'Em Elaboração', 'Rascunho'])) {
            $deptCounts[$dep]['pendentes']++;
            $deptCounts['all']['pendentes']++;
        } elseif ($st === 'Aprovado pelo Departamento') {
            $deptCounts[$dep]['aprov_depto']++;
            $deptCounts['all']['aprov_depto']++;
        } elseif ($st === 'Validado' || $st === 'Aprovado') {
            $deptCounts[$dep]['validados']++;
            $deptCounts['all']['validados']++;
        }
    }
}
?>

<div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
    <div>
        <h2 class="page" style="margin:0 0 4px 0; display:flex; align-items:center; gap:10px;">
            <span>🛡️</span> Painel de Aprovação de Planos de Cobertura
            <span class="pill ok" style="font-size:12px; font-weight:700;">Ano Letivo <?= htmlspecialchars(get_ano_lectivo_activo()) ?></span>
        </h2>
        <div class="sub">Apreciação, homologação e devolução de planos submetidos pelos Coordenadores de Curso (Fluxo Departamental & Presidencial).</div>
    </div>
    <div style="display:flex; gap:10px; align-items:center;">
        <span style="font-size:12px; color:var(--mut); font-weight:600;">Perfil Ativo:</span>
        <span class="pill" style="background:#1F4E79; color:#fff; font-weight:700; font-size:12px;"><?= htmlspecialchars(Auth::roleInfo($userRole)['nome']) ?></span>
    </div>
</div>

<!-- Abas de Filtro por Departamento -->
<div style="display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; border-bottom:2px solid var(--line); padding-bottom:12px;">
    <button class="dept-tab-btn <?= $defaultTab === 'all' ? 'active' : '' ?>" onclick="window.filtrarDepartamento('all', this)" style="padding:8px 16px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; transition:all 0.2s ease; border:1px solid var(--line); background:#fff; display:flex; align-items:center; gap:8px;">
        <span>🏢 Todos os Cursos</span>
        <span class="pill" style="font-size:11px; padding:2px 8px;"><?= $deptCounts['all']['total'] ?></span>
    </button>
    <button class="dept-tab-btn <?= $defaultTab === 'sociais' ? 'active' : '' ?>" onclick="window.filtrarDepartamento('sociais', this)" style="padding:8px 16px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; transition:all 0.2s ease; border:1px solid #BFDBFE; background:#EFF6FF; color:#1D4ED8; display:flex; align-items:center; gap:8px;">
        <span>⚖️ Ciências Sociais e Humanas</span>
        <span class="pill" style="background:#DBEAFE; color:#1D4ED8; font-size:11px; padding:2px 8px;"><?= $deptCounts['sociais']['total'] ?></span>
    </button>
    <button class="dept-tab-btn <?= $defaultTab === 'saude' ? 'active' : '' ?>" onclick="window.filtrarDepartamento('saude', this)" style="padding:8px 16px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; transition:all 0.2s ease; border:1px solid #BAE6FD; background:#F0F9FF; color:#0E7490; display:flex; align-items:center; gap:8px;">
        <span>🩺 Ciências da Saúde</span>
        <span class="pill" style="background:#E0F2FE; color:#0E7490; font-size:11px; padding:2px 8px;"><?= $deptCounts['saude']['total'] ?></span>
    </button>
    <button class="dept-tab-btn <?= $defaultTab === 'academicos' ? 'active' : '' ?>" onclick="window.filtrarDepartamento('academicos', this)" style="padding:8px 16px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; transition:all 0.2s ease; border:1px solid #E9D5FF; background:#FAF5FF; color:#6B21A8; display:flex; align-items:center; gap:8px;">
        <span>🎓 Assuntos Académicos</span>
        <span class="pill" style="background:#F3E8FF; color:#6B21A8; font-size:11px; padding:2px 8px;"><?= $deptCounts['academicos']['total'] ?></span>
    </button>
</div>

<style>
.dept-tab-btn.active {
    box-shadow: 0 0 0 2px var(--blue), 0 4px 12px rgba(31,78,121,0.15) !important;
    background: #1F4E79 !important;
    color: #fff !important;
    border-color: #1F4E79 !important;
}
.dept-tab-btn.active .pill {
    background: rgba(255,255,255,0.25) !important;
    color: #fff !important;
}
</style>

<!-- Tabela de Planos e Ações -->
<div style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.03);">
    <div class="tbl-wrap">
        <table class="tbl" id="tabela-aprovacoes">
            <thead>
                <tr>
                    <th style="min-width:200px;">Curso</th>
                    <th>Departamento Responsável</th>
                    <th>Estado Atual</th>
                    <th>Submetido em</th>
                    <th>Total UCs</th>
                    <th>Conformidade</th>
                    <th style="min-width:260px; text-align:right;">Ações de Aprovação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $s): ?>
                <?php 
                    $depInfo = get_depto_curso($s['curso_nome'] ?? '');
                    $st = $s['estado'] ?? 'Rascunho';
                    $bClass = ($st === 'Validado' || $st === 'Aprovado') ? 'b-sim' : (in_array($st, ['Submetido', 'Aprovado pelo Departamento']) ? 'b-ni' : 'b-nao');
                    $planoId = !empty($s['plano_id']) ? (int)$s['plano_id'] : 0;
                    $cursoId = (int)$s['curso_id'];
                ?>
                <tr class="linha-curso-aprov" data-dept="<?= $depInfo['id'] ?>">
                    <td>
                        <strong style="color:var(--blue); font-size:13.5px;"><?= htmlspecialchars($s['curso_nome']) ?></strong>
                        <div style="font-size:11px; color:var(--mut); margin-top:2px;">ID Curso: #<?= $cursoId ?> <?= $planoId ? "· Plano #{$planoId}" : "" ?></div>
                    </td>
                    <td>
                        <span style="display:inline-block; font-size:11.5px; font-weight:700; color:<?= $depInfo['cor'] ?>; background:<?= $depInfo['bg'] ?>; border:1px solid <?= $depInfo['border'] ?>; padding:3px 10px; border-radius:6px;">
                            <?= htmlspecialchars($depInfo['nome']) ?>
                        </span>
                        <div style="font-size:10.5px; color:var(--mut); margin-top:2px;"><?= htmlspecialchars($depInfo['chefe']) ?></div>
                    </td>
                    <td>
                        <span class="b <?= $bClass ?>"><?= htmlspecialchars($st) ?></span>
                    </td>
                    <td><?= !empty($s['data_submissao']) ? date('d/m/Y H:i', strtotime($s['data_submissao'])) : '<span style="color:var(--mut);">—</span>' ?></td>
                    <td><strong><?= (int)$s['total_uc'] ?></strong> <span style="font-size:11px; color:var(--mut);">UCs</span></td>
                    <td>
                        <span style="color:var(--ok); font-weight:700;"><?= (int)$s['conf_sim'] ?> conformes</span>
                        <?php if ((int)$s['conf_nao'] > 0): ?>
                            <span style="color:var(--bad); font-size:11px; display:block;"><?= (int)$s['conf_nao'] ?> não conformes</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <div style="display:inline-flex; gap:5px; flex-wrap:wrap; justify-content:flex-end;">
                            <button onclick="window.verHistoricoAprovacao(<?= $cursoId ?>)" class="btn sm ghost" style="color:#8e44ad; border-color:#8e44ad;" title="Ver Linha do Tempo de Auditoria">📜 Histórico</button>
                            <a href="index.php?page=relatorio_plano&curso_id=<?= $cursoId ?>&ano_lectivo=<?= urlencode(get_ano_lectivo_activo()) ?>" target="_blank" class="btn sm ghost" style="color:var(--blue); border-color:var(--blue);" title="Imprimir / PDF Oficial">📄 PDF</a>
                            <a href="index.php?api=exportar_excel&curso_id=<?= $cursoId ?>&ano_lectivo=<?= urlencode(get_ano_lectivo_activo()) ?>" class="btn sm ghost" style="color:#1E8449; border-color:#1E8449;" title="Descarregar Excel">📊 Excel</a>
                            
                            <?php if (Auth::hasRole(['presidente', 'admin'])): ?>
                                <?php if ($st !== 'Validado'): ?>
                                    <button class="btn sm btn-ok" style="background:#1F4E79; color:#fff;" onclick="window.aprovarCurso(<?= $cursoId ?>, 'Validado', <?= $planoId ?>)">🛡️ Validar (Presidência)</button>
                                <?php endif; ?>
                                <button class="btn sm" style="background:var(--bad); color:#fff;" onclick="window.aprovarCurso(<?= $cursoId ?>, 'Devolvido', <?= $planoId ?>)">↩️ Devolver</button>
                            <?php elseif (Auth::hasRole(['chefe_departamento']) && in_array($st, ['Submetido', 'Em Elaboração', 'Rascunho'])): ?>
                                <button class="btn sm btn-ok" style="background:#1E8449; color:#fff;" onclick="window.aprovarCurso(<?= $cursoId ?>, 'Aprovado pelo Departamento', <?= $planoId ?>)">✅ Aprovar (Depto)</button>
                                <button class="btn sm" style="background:var(--bad); color:#fff;" onclick="window.aprovarCurso(<?= $cursoId ?>, 'Devolvido', <?= $planoId ?>)">↩️ Devolver</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Visual de Histórico de Auditoria & Linha do Tempo -->
<div id="modal-historico-aprov" class="hidden" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.55); z-index:999; display:flex; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; max-width:680px; width:90%; padding:22px 26px; box-shadow:0 8px 30px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:12px; margin-bottom:16px;">
            <h3 style="margin:0; color:var(--blue); font-size:17px; display:flex; align-items:center; gap:8px;">
                <span>📜</span> Linha do Tempo e Histórico de Auditoria
            </h3>
            <button onclick="document.getElementById('modal-historico-aprov').classList.add('hidden')" style="background:none; border:none; font-size:18px; font-weight:700; cursor:pointer;">✕</button>
        </div>
        <div id="modal-historico-aprov-body" style="max-height:400px; overflow-y:auto; padding-right:6px;">
            <div style="text-align:center; padding:20px; color:var(--mut);">Carregando histórico do plano...</div>
        </div>
        <div style="text-align:right; border-top:1px solid var(--line); padding-top:14px; margin-top:16px;">
            <button onclick="document.getElementById('modal-historico-aprov').classList.add('hidden')" class="btn">Fechar</button>
        </div>
    </div>
</div>

<script>
window.filtrarDepartamento = function(deptId, btnElement) {
    document.querySelectorAll('.dept-tab-btn').forEach(b => b.classList.remove('active'));
    if (btnElement) {
        btnElement.classList.add('active');
    }

    const rows = document.querySelectorAll('.linha-curso-aprov');
    rows.forEach(row => {
        if (deptId === 'all' || row.dataset.dept === deptId) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
};

// Executar filtro padrão ao carregar a página
document.addEventListener('DOMContentLoaded', () => {
    const defaultDept = '<?= $defaultTab ?>';
    if (defaultDept !== 'all') {
        const btn = document.querySelector(`.dept-tab-btn[onclick*="${defaultDept}"]`);
        if (btn) window.filtrarDepartamento(defaultDept, btn);
    }
});

window.aprovarCurso = async (cursoId, estado, planoId = 0) => {
    const promptMsg = estado === 'Aprovado pelo Departamento' 
        ? 'Insira o parecer/comentário para homologação do plano pelo Chefe de Departamento:'
        : (estado === 'Devolvido' ? 'Insira o motivo detalhado para a devolução do plano ao Coordenador:' : `Insira o parecer para alterar o plano para "${estado}":`);
        
    const obs = prompt(promptMsg);
    if (obs !== null) {
        try {
            const res = await fetch('index.php?api=plano_estado', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ 
                    curso_id: cursoId, 
                    plano_id: planoId, 
                    estado: estado, 
                    observacoes: obs,
                    ano_lectivo: '2026/27'
                })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Estado alterado com sucesso!');
                location.reload();
            } else {
                alert('Erro: ' + (data.error || data.message || 'Falha ao alterar estado do curso.'));
            }
        } catch (e) {
            alert('Erro de comunicação com o servidor: ' + e.message);
        }
    }
};

window.verHistoricoAprovacao = async (cursoId) => {
    const modal = document.getElementById('modal-historico-aprov');
    const body = document.getElementById('modal-historico-aprov-body');
    if (!modal || !body) return;

    body.innerHTML = `<div style="text-align:center; padding:20px; color:var(--mut);">Carregando histórico do plano...</div>`;
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`index.php?api=plano_historico&curso_id=${cursoId}&ano_lectivo=2026/27`);
        const data = await res.json();

        if (data.success && data.data.length > 0) {
            body.innerHTML = `
                <div style="position:relative; padding-left:20px; border-left:2px solid var(--line); margin-top:10px;">
                    ${data.data.map(h => {
                        const icon = (h.acao === 'Aprovado' || h.acao === 'Validado' || h.acao === 'Aprovado pelo Departamento') ? '✅' : (h.acao === 'Devolvido' ? '↩️' : '📤');
                        const badgeColor = (h.acao === 'Aprovado' || h.acao === 'Validado') ? 'b-sim' : (h.acao === 'Devolvido' ? 'b-nao' : 'b-ni');
                        const dateStr = new Date(h.created_at).toLocaleString('pt-PT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                        return `
                            <div style="margin-bottom:20px; position:relative;">
                                <div style="position:absolute; left:-31px; top:2px; background:#fff; border:1px solid var(--line); border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; font-size:11px;">
                                    ${icon}
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <span class="b ${badgeColor}">${h.acao}</span>
                                    <span style="font-size:11.5px; color:var(--mut);">${dateStr}</span>
                                </div>
                                <div style="font-size:13px; font-weight:700; color:var(--blue); margin-bottom:2px;">
                                    ${h.utilizador_nome || 'Utilizador'} <span style="font-size:11px; font-weight:normal; color:var(--mut);">(${h.utilizador_perfil || 'Perfil'})</span>
                                </div>
                                ${h.comentario ? `
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:8px 12px; font-size:12px; color:#334155; margin-top:6px; font-style:italic;">
                                        "${h.comentario}"
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        } else {
            body.innerHTML = `<div style="text-align:center; padding:25px; color:var(--mut);">Ainda não existem registos de submissão ou homologação para este plano.</div>`;
        }
    } catch (e) {
        body.innerHTML = `<div style="text-align:center; padding:20px; color:var(--bad);">Erro ao carregar o histórico de auditoria.</div>`;
    }
};
</script>
