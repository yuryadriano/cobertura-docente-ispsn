<?php
/**
 * View: Gestão da Matriz Curricular & Disciplinas
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
            📚 Gestão da Matriz Curricular &amp; Disciplinas
        </h2>
        <div class="sub" style="margin:4px 0 0;">
            Consulta e edição das disciplinas oficiais por curso, ano curricular, semestre e carga horária semanal.
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:10px;">
        <span id="badge-perm-curriculo" class="pill ok" style="font-size:12px; padding:6px 14px; font-weight:700;">Modo de Edição</span>
        <button id="btn-nova-disciplina" class="btn btn-p" style="font-size:12px; padding:7px 16px; font-weight:700;" onclick="window.abrirModalDisciplina()">+ Nova Disciplina</button>
    </div>
</div>

<!-- CONTROLO DE SELEÇÃO DE CURSO -->
<div class="ctrls" style="display:flex; gap:16px; align-items:center; flex-wrap:wrap; margin-bottom:20px; background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px 20px; box-shadow:0 2px 6px rgba(0,0,0,0.02);">
    <div>
        <label style="font-size:11.5px; color:var(--mut); font-weight:700; text-transform:uppercase; display:block; margin-bottom:4px; letter-spacing:0.5px;">Curso Académico</label>
        <select id="curriculo-select-curso" style="min-width:280px; font-weight:700; padding:8px 12px; border-radius:8px; border:1px solid #cdcbc4; background:#fcfbf9; font-size:13.5px;" onchange="window.carregarDisciplinas(this.value)">
            <?php foreach ($cursos as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($userCursoId == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label style="font-size:11.5px; color:var(--mut); font-weight:700; text-transform:uppercase; display:block; margin-bottom:4px; letter-spacing:0.5px;">Ano Curricular</label>
        <select id="curriculo-select-ano" style="min-width:160px; font-weight:700; padding:8px 12px; border-radius:8px; border:1px solid #cdcbc4; background:#fcfbf9; font-size:13.5px;" onchange="window.filtrarPorAno(this.value)">
            <option value="">Todos os Anos</option>
            <option value="1">1.º Ano</option>
            <option value="2">2.º Ano</option>
            <option value="3">3.º Ano</option>
            <option value="4">4.º Ano</option>
            <option value="5">5.º Ano (Direito)</option>
        </select>
    </div>

    <div style="flex:1;"></div>

    <div>
        <span id="badge-total-disc" class="pill mut" style="font-size:12px; padding:6px 12px; font-weight:700;">0 disciplinas</span>
    </div>
</div>

<!-- CARTÃO DE TABELA DE DISCIPLINAS -->
<div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
    <div class="hd" style="font-size:14px; font-weight:700; padding:14px 20px; border-bottom:1px solid var(--line); background:#faf9f5; color:var(--blue); display:flex; justify-content:space-between; align-items:center;">
        <span>Disciplinas Oficiais do Plano de Estudos</span>
    </div>
    <div class="bd" style="padding:0; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
            <thead>
                <tr style="background:#f4f2ec; border-bottom:1px solid var(--line); text-align:left;">
                    <th style="padding:10px 12px; width:40px;">#</th>
                    <th style="padding:10px 12px; min-width:220px;">Unidade Curricular</th>
                    <th style="padding:10px 12px; width:100px; text-align:center;">Ano</th>
                    <th style="padding:10px 12px; width:100px; text-align:center;">Semestre</th>
                    <th style="padding:10px 12px; width:140px; text-align:center;">Carga Horária / Sem.</th>
                    <th style="padding:10px 12px; width:100px; text-align:center;">Créditos (ECTS)</th>
                    <th style="padding:10px 12px; width:100px; text-align:center;">Ações</th>
                </tr>
            </thead>
            <tbody id="tbody-disciplinas">
                <tr>
                    <td colspan="7" style="text-align:center; padding:30px; color:var(--mut);">Carregando disciplinas...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PARA ADICIONAR / EDITAR DISCIPLINA -->
<div id="modal-disciplina" class="hidden" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; max-width:520px; width:90%; padding:22px 26px; box-shadow:0 8px 30px rgba(0,0,0,0.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:12px; margin-bottom:16px;">
            <h3 id="modal-disciplina-title" style="margin:0; color:var(--blue); font-size:16px;">📖 Disciplina Curricular</h3>
            <button onclick="window.fecharModalDisciplina()" style="background:none; border:none; font-size:18px; font-weight:700; cursor:pointer;">✕</button>
        </div>
        <form id="form-disciplina" onsubmit="window.salvarDisciplinaSubmit(event)">
            <input type="hidden" id="disc-id" value="">
            <div style="margin-bottom:14px;">
                <label style="font-weight:600; font-size:12.5px; display:block; margin-bottom:4px;">Nome da Unidade Curricular:</label>
                <input type="text" id="disc-nome" required style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #cdcbc4; font-size:13.5px;" placeholder="ex.: Direito Constitucional I">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                <div>
                    <label style="font-weight:600; font-size:12.5px; display:block; margin-bottom:4px;">Ano Curricular:</label>
                    <select id="disc-ano" style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #cdcbc4; font-size:13.5px;">
                        <option value="1">1.º Ano</option>
                        <option value="2">2.º Ano</option>
                        <option value="3">3.º Ano</option>
                        <option value="4">4.º Ano</option>
                        <option value="5">5.º Ano</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight:600; font-size:12.5px; display:block; margin-bottom:4px;">Semestre:</label>
                    <select id="disc-semestre" style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #cdcbc4; font-size:13.5px;">
                        <option value="I">I Semestre</option>
                        <option value="II">II Semestre</option>
                    </select>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px;">
                <div>
                    <label style="font-weight:600; font-size:12.5px; display:block; margin-bottom:4px;">Carga Horária (h/sem):</label>
                    <input type="number" id="disc-carga" value="4" min="1" max="20" style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #cdcbc4; font-size:13.5px;">
                </div>
                <div>
                    <label style="font-weight:600; font-size:12.5px; display:block; margin-bottom:4px;">Créditos (ECTS):</label>
                    <input type="number" id="disc-creditos" value="6" min="1" max="30" style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #cdcbc4; font-size:13.5px;">
                </div>
            </div>
            <div style="text-align:right; border-top:1px solid var(--line); padding-top:14px;">
                <button type="button" onclick="window.fecharModalDisciplina()" class="btn" style="margin-right:8px;">Cancelar</button>
                <button type="submit" class="btn btn-p" style="font-weight:700;">💾 Guardar Disciplina</button>
            </div>
        </form>
    </div>
</div>

<script>
window.TODAS_DISCIPLINAS = [];

window.carregarDisciplinas = async (cursoId) => {
    const tbody = document.getElementById('tbody-disciplinas');
    if (!tbody || !cursoId) return;

    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:30px; color:var(--mut);">Carregando disciplinas da matriz curricular...</td></tr>`;

    try {
        const res = await fetch(`index.php?api=disciplinas&curso_id=${cursoId}`);
        const data = await res.json();

        if (data.success) {
            window.TODAS_DISCIPLINAS = data.data || [];
            const ano = document.getElementById('curriculo-select-ano').value;
            window.renderizarDisciplinas(ano);
        } else {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:20px; color:var(--bad); font-weight:700;">${data.error || 'Erro ao carregar disciplinas.'}</td></tr>`;
        }
    } catch (e) {
        console.error('Erro ao carregar disciplinas:', e);
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:20px; color:var(--bad);">Erro de comunicação com o servidor.</td></tr>`;
    }
};

window.filtrarPorAno = (ano) => {
    window.renderizarDisciplinas(ano);
};

window.renderizarDisciplinas = (anoFiltro) => {
    const tbody = document.getElementById('tbody-disciplinas');
    const badge = document.getElementById('badge-total-disc');
    const badgePerm = document.getElementById('badge-perm-curriculo');
    const btnNova = document.getElementById('btn-nova-disciplina');
    if (!tbody) return;

    const cursoIdSelecionado = parseInt(document.getElementById('curriculo-select-curso').value || 0);
    const perfil = window.CURRENT_USER_PERFIL || 'coordenador';
    const userCursoId = window.CURRENT_USER_CURSO_ID;

    // RBAC: Admin e Gestão Académica (se autorizado) editam tudo; Coordenador só edita o seu curso
    let podeEditar = false;
    if (['admin', 'gestor_academico'].includes(perfil)) {
        podeEditar = true;
    } else if (perfil === 'coordenador' && userCursoId === cursoIdSelecionado) {
        podeEditar = true;
    }

    if (badgePerm) {
        if (podeEditar) {
            badgePerm.textContent = '✏️ Modo de Edição Permitido';
            badgePerm.className = 'pill ok';
        } else {
            badgePerm.textContent = '🔒 Modo Só Leitura';
            badgePerm.className = 'pill mut';
        }
    }

    if (btnNova) {
        btnNova.style.display = podeEditar ? 'inline-block' : 'none';
    }

    let lista = window.TODAS_DISCIPLINAS;
    if (anoFiltro) {
        lista = lista.filter(d => d.ano_curricular == anoFiltro);
    }

    if (badge) badge.textContent = `${lista.length} de ${window.TODAS_DISCIPLINAS.length} disciplinas`;

    if (lista.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:30px; color:var(--mut);">Nenhuma disciplina encontrada na matriz para este filtro.</td></tr>`;
        return;
    }

    let html = '';
    lista.forEach((d, idx) => {
        const btnAcao = podeEditar 
            ? `<button class="btn sm ghost" style="padding:3px 8px; font-size:11px;" onclick="window.abrirModalDisciplina(${d.id})">✏️ Editar</button>`
            : `<span style="font-size:11px; color:var(--mut);">🔒 Só leitura</span>`;

        html += `
            <tr style="border-bottom:1px solid var(--line); ${!podeEditar ? 'opacity:0.85;' : ''}">
                <td style="padding:10px 12px; color:var(--mut); font-weight:700;">${idx + 1}</td>
                <td style="padding:10px 12px;"><b>${d.nome}</b></td>
                <td style="padding:10px 12px; text-align:center;">${d.ano_curricular}.º Ano</td>
                <td style="padding:10px 12px; text-align:center;">Semestre ${d.semestre}</td>
                <td style="padding:10px 12px; text-align:center;">${d.carga_horaria_semanal || 4} h/sem</td>
                <td style="padding:10px 12px; text-align:center;">${d.creditos || 6} ECTS</td>
                <td style="padding:10px 12px; text-align:center;">
                    ${btnAcao}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
};

window.abrirModalDisciplina = (discId = null) => {
    const modal = document.getElementById('modal-disciplina');
    const title = document.getElementById('modal-disciplina-title');
    const form = document.getElementById('form-disciplina');
    if (!modal || !form) return;

    form.reset();
    document.getElementById('disc-id').value = '';

    if (discId) {
        const d = window.TODAS_DISCIPLINAS.find(item => item.id == discId);
        if (d) {
            title.textContent = '✏️ Editar Disciplina Curricular';
            document.getElementById('disc-id').value = d.id;
            document.getElementById('disc-nome').value = d.nome;
            document.getElementById('disc-ano').value = d.ano_curricular;
            document.getElementById('disc-semestre').value = d.semestre;
            document.getElementById('disc-carga').value = d.carga_horaria_semanal || 4;
            document.getElementById('disc-creditos').value = d.creditos || 6;
        }
    } else {
        title.textContent = '+ Nova Disciplina Curricular';
    }

    modal.classList.remove('hidden');
    modal.style.display = 'flex';
};

window.fecharModalDisciplina = () => {
    const modal = document.getElementById('modal-disciplina');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
};

window.salvarDisciplinaSubmit = async (e) => {
    e.preventDefault();
    const cursoId = document.getElementById('curriculo-select-curso').value;
    const discId  = document.getElementById('disc-id').value;

    const payload = {
        disciplina_id: discId ? parseInt(discId) : null,
        curso_id: parseInt(cursoId),
        nome: document.getElementById('disc-nome').value.trim(),
        ano_curricular: parseInt(document.getElementById('disc-ano').value),
        semestre: document.getElementById('disc-semestre').value,
        carga_horaria_semanal: parseInt(document.getElementById('disc-carga').value),
        creditos: parseInt(document.getElementById('disc-creditos').value)
    };

    try {
        const res = await fetch('index.php?api=disciplina_salvar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            window.fecharModalDisciplina();
            window.carregarDisciplinas(cursoId);
        } else {
            alert('Erro ao guardar disciplina: ' + (data.error || 'Falha no servidor.'));
        }
    } catch (err) {
        console.error('Erro ao guardar disciplina:', err);
        alert('Erro de comunicação.');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('curriculo-select-curso');
    if (sel && sel.value) {
        window.carregarDisciplinas(sel.value);
    }
});
</script>
