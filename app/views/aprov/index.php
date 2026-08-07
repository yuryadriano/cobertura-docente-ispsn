<?php
/**
 * View: Aprovações da Cobertura Docente (Presidente / Consulta)
 */
$canApprove = Auth::canApprove();
?>
<div style="margin-bottom: 20px;">
    <h2 class="page">Painel de Aprovação de Planos de Cobertura</h2>
    <div class="sub">Revisão, homologação e devolução de planos submetidos pelos Coordenadores de Curso</div>
</div>


<div style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:20px;">
    <div class="tbl-wrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Estado Atual</th>
                    <th>Submetido em</th>
                    <th>Total UCs</th>
                    <th>Conformidade</th>
                    <th>Ações de Aprovação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $s): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s['curso_nome']) ?></strong></td>
                    <td>
                        <?php 
                            $st = $s['estado'] ?? 'Rascunho';
                            $bClass = ($st === 'Validado' || $st === 'Aprovado') ? 'b-sim' : (in_array($st, ['Submetido', 'Aprovado pelo Departamento']) ? 'b-ni' : 'b-nao');
                        ?>
                        <span class="b <?= $bClass ?>"><?= htmlspecialchars($st) ?></span>
                    </td>
                    <td><?= date('d/m/Y') ?></td>
                    <td><?= $s['total_uc'] ?></td>
                    <td><span style="color:var(--ok); font-weight:700;"><?= $s['conf_sim'] ?> conformes</span></td>
                    <td>
                        <button onclick="window.verHistoricoAprovacao(<?= $s['curso_id'] ?>)" class="btn sm ghost" style="color:#8e44ad; border-color:#8e44ad;" title="Ver Linha do Tempo de Auditoria">📜 Histórico</button>
                        <a href="index.php?page=relatorio_plano&curso_id=<?= $s['curso_id'] ?>" target="_blank" class="btn sm ghost" style="color:var(--blue); border-color:var(--blue);" title="Imprimir / PDF Oficial">📄 PDF</a>
                        <a href="index.php?api=exportar_excel&curso_id=<?= $s['curso_id'] ?>" class="btn sm ghost" style="color:#1E8449; border-color:#1E8449;" title="Descarregar Excel">📊 Excel</a>
                        
                        <?php if (Auth::hasRole(['presidente', 'admin']) && $st !== 'Validado'): ?>
                            <button class="btn sm btn-ok" style="background:#1F4E79;" onclick="window.aprovarCurso(<?= $s['curso_id'] ?>, 'Validado')">🛡️ Validar (Presidência)</button>
                            <button class="btn sm" style="background:var(--bad); color:#fff;" onclick="window.aprovarCurso(<?= $s['curso_id'] ?>, 'Devolvido')">↩️ Devolver</button>
                        <?php elseif (Auth::hasRole(['chefe_departamento']) && in_array($st, ['Submetido', 'Em Elaboração', 'Rascunho'])): ?>
                            <button class="btn sm btn-ok" style="background:#1E8449;" onclick="window.aprovarCurso(<?= $s['curso_id'] ?>, 'Aprovado pelo Departamento')">✅ Aprovar pelo Depto</button>
                            <button class="btn sm" style="background:var(--bad); color:#fff;" onclick="window.aprovarCurso(<?= $s['curso_id'] ?>, 'Devolvido')">↩️ Devolver</button>
                        <?php endif; ?>
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
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await fetch('index.php?api=diagnostico_risco');
        const data = await res.json();
        const container = document.getElementById('container-diagnostico-riscos');
        const badge = document.getElementById('badge-total-riscos');

        if (data.success && data.data.length > 0) {
            badge.textContent = `${data.data.length} alertas identificados`;
            badge.className = 'pill bad';

            // Mostrar top 10 maiores riscos
            const topRiscos = data.data.slice(0, 10);
            container.innerHTML = `
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#faf9f5; text-align:left;">
                            <th style="padding:6px;">Disciplina / Turma</th>
                            <th style="padding:6px;">Docente Atribuído</th>
                            <th style="padding:6px;">Alerta de Risco</th>
                            <th style="padding:6px; text-align:center;">Gravidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${topRiscos.map(r => `
                            <tr style="border-bottom:1px solid var(--line);">
                                <td style="padding:6px;"><b>${r.disciplina_nome}</b> <span style="color:var(--mut); font-size:11px;">(${r.turma_nome || 'Geral'})</span></td>
                                <td style="padding:6px;">${r.docente_nome || '<span style="color:var(--bad); font-weight:700;">Sem Docente</span>'}</td>
                                <td style="padding:6px;"><span class="pill ${r.gravidade_risco >= 3 ? 'bad' : 'warn'}">${r.nivel_risco}</span></td>
                                <td style="padding:6px; text-align:center; font-weight:700; color:${r.gravidade_risco >= 3 ? 'var(--bad)' : 'var(--warn)'};">Nível ${r.gravidade_risco}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } else {
            badge.textContent = '0 riscos pendentes';
            badge.className = 'pill ok';
            container.innerHTML = `<div style="text-align:center; padding:15px; color:var(--ok); font-weight:600;">Nenhum risco elevado identificado nos planos ativos!</div>`;
        }
    } catch (e) {
        console.error('Erro ao carregar riscos:', e);
    }
});

window.aprovarCurso = async (cursoId, estado) => {
    const obs = prompt(`Insira o parecer/comentário para alterar o estado do curso para ${estado}:`);
    if (obs !== null) {
        const res = await fetch('index.php?api=plano_estado', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ plano_id: cursoId, estado: estado, observacoes: obs })
        });
        const data = await res.json();
        alert(data.message || 'Estado alterado com sucesso!');
        location.reload();
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
                        const icon = h.acao === 'Aprovado' ? '✅' : (h.acao === 'Devolvido' ? '↩️' : '📤');
                        const badgeColor = h.acao === 'Aprovado' ? 'b-sim' : (h.acao === 'Devolvido' ? 'b-nao' : 'b-ni');
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

