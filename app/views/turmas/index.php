<?php
/**
 * View: Gestão de Turmas & Indicadores Operacionais Standalone
 * sftcoordenacao — ISPSN 2026/27
 */
$userPerfil = $_SESSION['user']['perfil'] ?? 'coordenador';
$userCursoId = $_SESSION['user']['curso_id'] ?? null;
?>
<script>
window.CURRENT_USER_PERFIL = "<?= $userPerfil ?>";
window.CURRENT_USER_CURSO_ID = <?= $userCursoId ? (int)$userCursoId : 'null' ?>;
</script>

<!-- CABEÇALHO DA PÁGINA -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; flex-wrap:wrap; gap:12px;">
    <div>
        <h2 class="page" style="margin:0; display:flex; align-items:center; gap:10px;">
            🏫 Gestão de Turmas &amp; Indicadores Operacionais
        </h2>
        <div class="sub" style="margin:4px 0 0;">
            Preenchimento dos indicadores operacionais das turmas (sumários lecionados, programas, dosificação, cumprimento de prazos e inquéritos pedagógicos).
        </div>
    </div>
    <div>
        <span id="badge-perm-turmas" class="pill ok" style="font-size:12px; padding:6px 14px; font-weight:700;">Modo de Edição</span>
    </div>
</div>

<!-- BARRA DE SELEÇÃO E CONTROLO DE CURSO E FILTROS -->
<div class="ctrls" style="display:flex; gap:16px; align-items:center; flex-wrap:wrap; margin-bottom:20px; background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px 20px; box-shadow:0 2px 6px rgba(0,0,0,0.02);">
    <div>
        <label style="font-size:11.5px; color:var(--mut); font-weight:700; text-transform:uppercase; display:block; margin-bottom:4px; letter-spacing:0.5px;">Curso Académico</label>
        <select id="turmas-select-curso" style="min-width:260px; font-weight:700; padding:8px 12px; border-radius:8px; border:1px solid #cdcbc4; background:#fcfbf9; font-size:13.5px;" onchange="window.carregarTurmas(this.value)">
            <?php foreach ($cursos as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($userCursoId == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label style="font-size:11.5px; color:var(--mut); font-weight:700; text-transform:uppercase; display:block; margin-bottom:4px; letter-spacing:0.5px;">Ano Curricular</label>
        <select id="turmas-select-ano" style="min-width:160px; font-weight:700; padding:8px 12px; border-radius:8px; border:1px solid #cdcbc4; background:#fcfbf9; font-size:13.5px;" onchange="window.filtrarTurmasPorAno(this.value)">
            <option value="">Todos os Anos</option>
            <option value="1">1.º Ano</option>
            <option value="2">2.º Ano</option>
            <option value="3">3.º Ano</option>
            <option value="4">4.º Ano</option>
            <option value="5">5.º Ano (Direito)</option>
        </select>
    </div>

    <div style="flex:1;"></div>

    <div style="text-align:right;">
        <span id="badge-total-turmas" class="pill mut" style="font-size:12px; padding:6px 12px; font-weight:700;">0 turmas carregadas</span>
    </div>
</div>

<!-- CARTÃO DA TABELA PRINCIPAL DE TURMAS -->
<div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
    <div class="hd" style="font-size:14px; font-weight:700; padding:14px 20px; border-bottom:1px solid var(--line); background:#faf9f5; color:var(--blue); display:flex; justify-content:space-between; align-items:center;">
        <span id="turmas-card-title">Indicadores Operacionais por Turma</span>
        <span style="font-size:11.5px; color:var(--mut); font-weight:normal;">💾 As alterações são salvas automaticamente em tempo real</span>
    </div>
    <div class="bd" style="padding:0; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
            <thead>
                <tr style="background:#f4f2ec; border-bottom:1px solid var(--line); text-align:left;">
                    <th style="padding:10px 12px; min-width:140px;">Turma</th>
                    <th style="padding:10px 12px; min-width:180px;">Unidade Curricular</th>
                    <th style="padding:10px 12px; min-width:160px;">Docente Atribuído</th>
                    <th style="padding:10px 12px; width:130px; text-align:center;">Sumários (Dadas / Prev.)</th>
                    <th style="padding:10px 12px; width:110px; text-align:center;">Assiduidade (%)</th>
                    <th style="padding:10px 12px; width:90px; text-align:center;">Programa</th>
                    <th style="padding:10px 12px; width:90px; text-align:center;">Dosificação</th>
                    <th style="padding:10px 12px; width:110px; text-align:center;">Notas no Prazo</th>
                    <th style="padding:10px 12px; width:120px; text-align:center;">Inquérito (⭐)</th>
                </tr>
            </thead>
            <tbody id="tbody-turmas">
                <tr>
                    <td colspan="9" style="text-align:center; padding:30px; color:var(--mut);">Carregando turmas...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.t-input {
    padding: 6px 8px;
    border-radius: 6px;
    border: 1px solid #cdcbc4;
    background: #fcfbf9;
    font-size: 12.5px;
    font-family: inherit;
    box-sizing: border-box;
}
.t-input:focus {
    outline: none;
    border-color: var(--blue);
}
.pct-badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 11.5px;
    display: inline-block;
}
.pct-good { background: #E8F5E9; color: #1B5E20; }
.pct-warn { background: #FEF3C7; color: #78350F; }
.pct-bad  { background: #FBEAE8; color: #C0392B; }

.badge-turno {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}
.turno-manha { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
.turno-tarde { background: #FFEDD5; color: #9A3412; border: 1px solid #FED7AA; }
.turno-noite { background: #E0E7FF; color: #3730A3; border: 1px solid #C7D2FE; }
.turno-regimeb { background: #F3E8FF; color: #6B21A8; border: 1px solid #E9D5FF; }
</style>

<script>
window.TODAS_TURMAS = [];

window.formatTurmaRotulo = (t) => {
    const rawCod = t.designacao || '';
    const rawTurno = t.turno || 'Manhã';
    let s = String(rawCod).trim();

    // 1. Detectar turno
    let turno = rawTurno.trim();
    let icon = '🟡';
    let badgeClass = 'turno-manha';

    if (/Pós-Laboral|Pos-Laboral/i.test(s) || turno === 'Pós-Laboral') {
        turno = 'Pós-Laboral';
        icon = '🟣';
        badgeClass = 'turno-regimeb';
    } else if (/Regime\s*B|\-RB/i.test(s) || turno === 'Regime B') {
        turno = 'Regime B';
        icon = '🟣';
        badgeClass = 'turno-regimeb';
    } else if (/Noite|\bNT\b/i.test(s) || turno === 'Noite') {
        turno = 'Noite';
        icon = '🔵';
        badgeClass = 'turno-noite';
    } else if (/Tarde|\bT\b/i.test(s) || turno === 'Tarde') {
        turno = 'Tarde';
        icon = '🟠';
        badgeClass = 'turno-tarde';
    } else {
        turno = 'Manhã';
        icon = '🟡';
        badgeClass = 'turno-manha';
    }

    // 2. Extrair código oficial limpo
    let codOficial = '';
    const codeMatch = s.match(/\(([^)]+)\)/);
    if (codeMatch) {
        codOficial = codeMatch[1].trim();
    } else {
        codOficial = s.replace(/\s*\([^)]*\)/g, '').trim();
    }

    // 3. Extrair letra da turma (ex: "Turma A", "Turma B")
    let rotuloNome = 'Turma A';
    const turmaMatch = s.match(/Turma\s+([A-Z])/i);
    if (turmaMatch) {
        rotuloNome = `Turma ${turmaMatch[1].toUpperCase()}`;
    } else {
        rotuloNome = s;
    }

    return { nome: rotuloNome, tag: turno, icon: icon, badgeClass: badgeClass, codOficial: codOficial || s };
};

window.carregarTurmas = async (cursoId) => {
    const tbody = document.getElementById('tbody-turmas');
    if (!tbody || !cursoId) return;

    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:30px; color:var(--mut);">Carregando turmas do curso...</td></tr>`;

    try {
        const res = await fetch(`index.php?api=turmas&curso_id=${cursoId}`);
        const data = await res.json();

        if (data.success) {
            window.TODAS_TURMAS = data.data || [];
            const anoFiltro = document.getElementById('turmas-select-ano').value;
            window.renderizarTurmas(anoFiltro);
        } else {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:20px; color:var(--bad); font-weight:700;">${data.error || 'Erro ao carregar turmas.'}</td></tr>`;
        }
    } catch (err) {
        console.error('Erro ao carregar turmas:', err);
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:20px; color:var(--bad);">Erro de comunicação com o servidor.</td></tr>`;
    }
};

window.filtrarTurmasPorAno = (ano) => {
    window.renderizarTurmas(ano);
};

window.renderizarTurmas = (anoFiltro) => {
    const tbody = document.getElementById('tbody-turmas');
    const badgeTotal = document.getElementById('badge-total-turmas');
    const badgePerm = document.getElementById('badge-perm-turmas');
    if (!tbody) return;

    const cursoIdSelecionado = parseInt(document.getElementById('turmas-select-curso').value || 0);
    const perfil = window.CURRENT_USER_PERFIL || 'coordenador';
    const userCursoId = window.CURRENT_USER_CURSO_ID;

    // Lógica RBAC: Admin e Gestão Académica (se autorizado) editam tudo; Coordenador só edita o seu curso; outros são Só Leitura
    let podeEditar = false;
    if (['admin', 'gestor_academico'].includes(perfil)) {
        podeEditar = true;
    } else if (perfil === 'coordenador' && userCursoId === cursoIdSelecionado) {
        podeEditar = true;
    }

    const disabledAttr = podeEditar ? '' : 'disabled';

    if (badgePerm) {
        if (podeEditar) {
            badgePerm.textContent = '✏️ Modo de Edição Permitido';
            badgePerm.className = 'pill ok';
        } else {
            badgePerm.textContent = '🔒 Modo Só Leitura';
            badgePerm.className = 'pill mut';
        }
    }

    let lista = window.TODAS_TURMAS;
    if (anoFiltro) {
        lista = lista.filter(t => t.ano_curricular == anoFiltro);
    }

    if (badgeTotal) {
        badgeTotal.textContent = `${lista.length} de ${window.TODAS_TURMAS.length} turmas`;
    }

    if (lista.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:30px; color:var(--mut);">Nenhuma turma encontrada com os filtros selecionados.</td></tr>`;
        return;
    }

    const turnoOrder = { 'Manhã': 1, 'Tarde': 2, 'Noite': 3, 'Regime B': 4, 'Pós-Laboral': 5 };

    // Ordenar de forma canónica por Ano -> Turno -> Letra / Designação
    lista.sort((a, b) => {
        if (a.ano_curricular !== b.ano_curricular) return a.ano_curricular - b.ano_curricular;
        const rotA = window.formatTurmaRotulo(a);
        const rotB = window.formatTurmaRotulo(b);
        const ordA = turnoOrder[rotA.tag] || 99;
        const ordB = turnoOrder[rotB.tag] || 99;
        if (ordA !== ordB) return ordA - ordB;
        return (a.designacao || '').localeCompare(b.designacao || '', 'pt', { numeric: true });
    });

    let html = '';
    lista.forEach(t => {
        const reg = parseInt(t.sumarios_registados || 0);
        const prev = parseInt(t.sumarios_previstos || 200);
        const pct = prev > 0 ? Math.round((reg / prev) * 100) : 0;
        const pctClass = pct >= 75 ? 'pct-good' : (pct >= 50 ? 'pct-warn' : 'pct-bad');

        const progChecked = parseInt(t.programa_carregado || 0) === 1 ? 'checked' : '';
        const dosiChecked = parseInt(t.dosificacao_carregada || 0) === 1 ? 'checked' : '';

        const notasSim = (t.notas_no_prazo === 'Sim' || !t.notas_no_prazo) ? 'selected' : '';
        const notasNao = t.notas_no_prazo === 'Não' ? 'selected' : '';

        const docNome = t.docente_nome ? `<b>${t.docente_nome}</b>` : `<span style="color:var(--mut); font-style:italic;">Não atribuído</span>`;
        const rotulo = window.formatTurmaRotulo(t);

        html += `
            <tr style="border-bottom:1px solid var(--line); ${!podeEditar ? 'opacity:0.85;' : ''}">
                <td style="padding:10px 12px;">
                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:3px;">
                        <span style="font-weight:800; font-size:13px; color:var(--blue);">${rotulo.nome}</span>
                        <span class="badge-turno ${rotulo.badgeClass}">${rotulo.icon} ${rotulo.tag}</span>
                    </div>
                    <div style="font-size:11px; color:var(--mut); font-weight:600;">
                        ${t.ano_curricular}.º Ano · <code style="font-size:10.5px; background:#f0eeea; padding:1px 5px; border-radius:4px; font-weight:700; color:#333;">${rotulo.codOficial}</code> · Semestre ${t.semestre}
                    </div>
                </td>
                <td style="padding:10px 12px;"><b>${t.disciplina_nome}</b></td>
                <td style="padding:10px 12px;">${docNome}</td>
                <td style="padding:10px 12px; text-align:center;">
                    <div style="display:flex; align-items:center; justify-content:center; gap:4px;">
                        <input type="number" ${disabledAttr} class="t-input" style="width:60px; text-align:center;" value="${reg}" min="0" max="300" onchange="window.salvarCampoTurma('${t.id}', 'sumarios_registados', this.value)">
                        <span>/</span>
                        <input type="number" ${disabledAttr} class="t-input" style="width:60px; text-align:center;" value="${prev}" min="1" max="300" onchange="window.salvarCampoTurma('${t.id}', 'sumarios_previstos', this.value)">
                    </div>
                </td>
                <td style="padding:10px 12px; text-align:center;">
                    <span id="pct-badge-${t.id}" class="pct-badge ${pctClass}">${pct}%</span>
                </td>
                <td style="padding:10px 12px; text-align:center;">
                    <input type="checkbox" ${disabledAttr} ${progChecked} style="transform:scale(1.2); cursor:pointer;" onchange="window.salvarCampoTurma('${t.id}', 'programa_carregado', this.checked ? 1 : 0)">
                </td>
                <td style="padding:10px 12px; text-align:center;">
                    <input type="checkbox" ${disabledAttr} ${dosiChecked} style="transform:scale(1.2); cursor:pointer;" onchange="window.salvarCampoTurma('${t.id}', 'dosificacao_carregada', this.checked ? 1 : 0)">
                </td>
                <td style="padding:10px 12px; text-align:center;">
                    <select class="t-input" ${disabledAttr} style="font-weight:700;" onchange="window.salvarCampoTurma('${t.id}', 'notas_no_prazo', this.value)">
                        <option value="Sim" ${notasSim}>Sim</option>
                        <option value="Não" ${notasNao}>Não</option>
                    </select>
                </td>
                <td style="padding:10px 12px; text-align:center;">
                    <div style="display:flex; align-items:center; justify-content:center; gap:4px;">
                        <input type="number" ${disabledAttr} step="0.10" min="1.00" max="5.00" class="t-input" style="width:65px; text-align:center; font-weight:700;" value="${parseFloat(t.inquerito_media || 4.00).toFixed(2)}" onchange="window.salvarCampoTurma('${t.id}', 'inquerito_media', this.value)">
                        <span style="color:#D97706; font-size:12px;">⭐</span>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
};

window.salvarCampoTurma = async (turmaId, campo, valor) => {
    try {
        const body = { turma_id: turmaId };
        body[campo] = valor;

        const res = await fetch('index.php?api=turma_salvar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        const data = await res.json();
        if (data.success) {
            // Atualiza array local em memória
            const turmaObj = window.TODAS_TURMAS.find(t => t.id === turmaId);
            if (turmaObj) {
                turmaObj[campo] = valor;
                if (campo === 'sumarios_registados' || campo === 'sumarios_previstos') {
                    const reg = parseInt(turmaObj.sumarios_registados || 0);
                    const prev = parseInt(turmaObj.sumarios_previstos || 200);
                    const pct = prev > 0 ? Math.round((reg / prev) * 100) : 0;
                    const badge = document.getElementById(`pct-badge-${turmaId}`);
                    if (badge) {
                        badge.textContent = `${pct}%`;
                        badge.className = `pct-badge ${pct >= 75 ? 'pct-good' : (pct >= 50 ? 'pct-warn' : 'pct-bad')}`;
                    }
                }
            }

            // Toast discreto de confirmação
            const toast = document.createElement('div');
            toast.style.cssText = 'position:fixed; bottom:20px; right:20px; background:#10B981; color:#fff; padding:10px 16px; border-radius:6px; font-weight:700; z-index:9999; font-size:12px; box-shadow:0 4px 10px rgba(0,0,0,0.15);';
            toast.textContent = '✅ Indicador atualizado com sucesso!';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        } else {
            alert('Erro ao atualizar indicador da turma: ' + (data.error || 'Falha no servidor'));
        }
    } catch (e) {
        console.error('Erro ao salvar indicador da turma:', e);
        alert('Erro de comunicação ao atualizar indicador.');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const selCurso = document.getElementById('turmas-select-curso');
    if (selCurso && selCurso.value) {
        window.carregarTurmas(selCurso.value);
    }
});
</script>
