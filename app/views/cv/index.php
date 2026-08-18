<?php
/**
 * View: CV Estruturado — Modelo MESCTI (4 Blocos Completos)
 * GRH preenche por campos; gravação propaga automaticamente para todos os planos.
 * sftcoordenacao — ISPSN 2026/27
 */
$preDocId = (int)($_GET['docente_id'] ?? 0);
?>

<!-- Cabeçalho da página -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; flex-wrap:wrap; gap:12px;">
    <div>
        <h2 class="page" style="margin:0; display:flex; align-items:center; gap:10px;">
            📄 CV Estruturado — Modelo MESCTI
        </h2>
        <div class="sub" style="margin:4px 0 0;">
            Preenchimento por campos estruturados (modelo oficial MESCTI).
            Os campos marcados com <span style="background:#E8F5E9; color:#1B5E20; border-radius:4px; padding:1px 6px; font-size:11px; font-weight:700;">↳ plano</span> alimentam automaticamente todas as linhas de cobertura onde o docente está atribuído.
        </div>
    </div>
    <div id="cv-status-badge" style="display:none;">
        <span class="pill ok" style="font-size:12px; padding:6px 14px; font-weight:700;">✅ CV Guardado e Planos Atualizados</span>
    </div>
</div>

<!-- Seletor de Docente -->
<div style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px 20px; margin-bottom:20px; box-shadow:0 2px 6px rgba(0,0,0,0.02);">
    <label style="font-weight:700; color:var(--blue); display:block; margin-bottom:8px; font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">Selecionar Docente</label>
    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <select id="cv-docente-select" style="flex:1; min-width:280px; font-weight:700; padding:10px 14px; border-radius:8px; border:1px solid #cdcbc4; background:#fcfbf9; font-size:14px;" onchange="window.carregarCV(this.value)">
            <option value="">— Selecionar docente —</option>
            <?php foreach ($docentes as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $preDocId == $d['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['nome']) ?> — <?= $d['grau_academico'] ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span id="cv-load-indicator" style="display:none; font-size:13px; color:var(--mut);">⏳ A carregar...</span>
    </div>
</div>

<!-- Formulário Principal -->
<form id="form-cv" onsubmit="window.guardarCV(event)" style="display:none;">
    <input type="hidden" id="cv-docente-id" value="">

    <!-- ================================================================ -->
    <!-- BLOCO 1 — IDENTIFICAÇÃO                                          -->
    <!-- ================================================================ -->
    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; margin-bottom:16px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
        <div class="hd" style="padding:14px 20px; border-bottom:1px solid var(--line); background:#f4f8ff; color:var(--blue); font-weight:700; font-size:14px; display:flex; align-items:center; gap:10px;">
            <span>📋</span> Bloco 1 — Identificação Pessoal e Contactos
        </div>
        <div class="bd" style="padding:20px;">
            <div style="display:grid; grid-template-columns:1fr auto; gap:20px; align-items:start;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div style="grid-column:1/-1;">
                        <label class="cv-label">Nome Completo</label>
                        <input type="text" id="cv-nome" class="cv-input" placeholder="Nome completo do docente">
                    </div>
                    <div>
                        <label class="cv-label">Email Institucional</label>
                        <input type="email" id="cv-email" class="cv-input" placeholder="docente@ispsn.org">
                    </div>
                    <div>
                        <label class="cv-label">Telefone / Telemóvel</label>
                        <input type="text" id="cv-telefone" class="cv-input" placeholder="+244 9XX XXX XXX">
                    </div>
                    <div>
                        <label class="cv-label">Bilhete de Identidade (BI)</label>
                        <input type="text" id="cv-bi" class="cv-input" placeholder="Nº do BI">
                    </div>
                    <div>
                        <label class="cv-label">Instituição de Ensino Superior Atual</label>
                        <input type="text" id="cv-instituicao" class="cv-input" placeholder="ex.: ISPSN, UJES, Universidade do Porto">
                    </div>
                </div>
                <!-- Foto do Docente -->
                <div style="text-align:center; min-width:130px;">
                    <div id="cv-foto-preview" style="width:110px; height:130px; border:2px dashed var(--line); border-radius:8px; display:flex; align-items:center; justify-content:center; background:#f8fafc; color:var(--mut); font-size:11px; cursor:pointer; overflow:hidden; margin:0 auto 8px;" onclick="document.getElementById('cv-foto-input').click()" title="Clique para selecionar foto">
                        <span style="text-align:center; padding:10px;">👤<br>Sem foto</span>
                    </div>
                    <input type="file" id="cv-foto-input" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="window.previewFoto(event)">
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <button type="button" class="btn sm ghost" onclick="document.getElementById('cv-foto-input').click()" style="font-size:11px;">📷 Alterar foto</button>
                        <button type="button" id="cv-btn-remover-foto" class="btn sm ghost" onclick="window.removerFoto()" style="font-size:11px; color:var(--bad); border-color:var(--bad); display:none;">🗑️ Remover foto</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- BLOCO 2 — FORMAÇÃO ACADÉMICA E TÍTULOS                           -->
    <!-- ================================================================ -->
    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; margin-bottom:16px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
        <div class="hd" style="padding:14px 20px; border-bottom:1px solid var(--line); background:#f4fff8; color:#1B5E20; font-weight:700; font-size:14px; display:flex; align-items:center; gap:10px;">
            <span>🎓</span> Bloco 2 — Formação Académica e Títulos
            <span style="margin-left:auto; font-size:11px; background:#E8F5E9; color:#1B5E20; border-radius:4px; padding:2px 8px; font-weight:700;">Alimenta o Plano ↳</span>
        </div>
        <div class="bd" style="padding:20px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px;">
                <div>
                    <label class="cv-label">Grau Académico Principal <span class="badge-plano">↳ plano</span></label>
                    <select id="cv-grau" class="cv-input" style="font-weight:700;">
                        <option value="Licenciado">Licenciado</option>
                        <option value="Mestre">Mestre</option>
                        <option value="Doutor">Doutor</option>
                    </select>
                </div>
                <div>
                    <label class="cv-label">Especialidade / Área Científica <span class="badge-plano">↳ plano</span></label>
                    <input type="text" id="cv-especialidade" class="cv-input" placeholder="ex.: Direito Público, Cardiologia, Gestão">
                </div>
                <div>
                    <label class="cv-label">Declaração INAAREES <span class="badge-plano">↳ plano</span></label>
                    <select id="cv-inaarees" class="cv-input">
                        <option value="Não">Não (Pendente / Sem declaração)</option>
                        <option value="Sim">Sim (Homologado / Reconhecido)</option>
                    </select>
                </div>
                <div>
                    <label class="cv-label">Capacitação Psicopedagógica <span class="badge-plano">↳ plano</span></label>
                    <select id="cv-agregacao" class="cv-input">
                        <option value="Não">Não</option>
                        <option value="Sim">Sim (Comprovada)</option>
                    </select>
                </div>
                <div>
                    <label class="cv-label">Categoria na Carreira Docente (CEDS) <span class="badge-plano">↳ plano</span></label>
                    <select id="cv-categoria" class="cv-input">
                        <option value="Não está na CEDS">Não está na CEDS</option>
                        <option value="Assistente">Assistente</option>
                        <option value="Assistente do 1.º Escalão">Assistente do 1.º Escalão</option>
                        <option value="Professor Auxiliar">Professor Auxiliar</option>
                        <option value="Professor Associado">Professor Associado</option>
                        <option value="Professor Titular">Professor Titular</option>
                        <option value="Professor Catedrático">Professor Catedrático</option>
                        <option value="Professor Convidado">Professor Convidado</option>
                        <option value="Colaborador">Colaborador</option>
                    </select>
                </div>
            </div>

            <!-- Formações Académicas Dinâmicas -->
            <div style="border-top:1px solid var(--line); padding-top:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div style="font-weight:700; font-size:13px; color:var(--ink);">📚 Historial Académico (Licenciatura / Mestrado / Doutoramento / Especializações)</div>
                    <button type="button" class="btn sm btn-p" onclick="window.adicionarFormacao()" style="font-size:12px; padding:5px 12px;">+ Adicionar</button>
                </div>
                <div id="lista-formacoes" style="display:flex; flex-direction:column; gap:10px;">
                    <!-- Preenchido dinamicamente pelo JS -->
                    <div style="text-align:center; padding:20px; color:var(--mut); font-size:13px; border:1px dashed var(--line); border-radius:8px;" id="formacoes-empty">
                        Sem formações registadas. Clique em «+ Adicionar» para inserir.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- BLOCO 3 — SITUAÇÃO PROFISSIONAL                                  -->
    <!-- ================================================================ -->
    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; margin-bottom:16px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
        <div class="hd" style="padding:14px 20px; border-bottom:1px solid var(--line); background:#fffbf0; color:#78350F; font-weight:700; font-size:14px; display:flex; align-items:center; gap:10px;">
            <span>💼</span> Bloco 3 — Situação Profissional e Carreira
            <span style="margin-left:auto; font-size:11px; background:#FEF3C7; color:#78350F; border-radius:4px; padding:2px 8px; font-weight:700;">Alimenta o Plano ↳</span>
        </div>
        <div class="bd" style="padding:20px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div>
                    <label class="cv-label">Regime Contratual com o ISPSN <span class="badge-plano">↳ plano</span></label>
                    <select id="cv-regime-contratual" class="cv-input">
                        <option value="">— Selecionar —</option>
                        <option value="Tempo Integral">Tempo Integral</option>
                        <option value="Tempo Parcial">Tempo Parcial</option>
                        <option value="Colaborador">Colaborador</option>
                        <option value="Convidado">Convidado</option>
                    </select>
                </div>
                <div>
                    <label class="cv-label">Anos de Experiência no Ensino Superior <span class="badge-plano">↳ plano</span></label>
                    <input type="number" id="cv-exp" class="cv-input" min="0" max="60" value="0">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="cv-label">Trabalho que Desempenha / Cargo Atual</label>
                    <input type="text" id="cv-cargo-atual" class="cv-input" placeholder="ex.: Professor de Direito Civil, Coordenador de Saúde Pública">
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- BLOCO 4 — INVESTIGAÇÃO, DOCÊNCIA E PRODUÇÃO                     -->
    <!-- ================================================================ -->
    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; margin-bottom:16px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
        <div class="hd" style="padding:14px 20px; border-bottom:1px solid var(--line); background:#faf0ff; color:#4C1D95; font-weight:700; font-size:14px; display:flex; align-items:center; gap:10px;">
            <span>🔬</span> Bloco 4 — Investigação, Docência e Produção Científica
            <span style="margin-left:auto; font-size:11px; background:#EDE9FE; color:#4C1D95; border-radius:4px; padding:2px 8px; font-weight:700;">Alimenta o Plano ↳</span>
        </div>
        <div class="bd" style="padding:20px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px;">
                <div>
                    <label class="cv-label">N.º de Publicações / Comunicações (últimos 3 anos) <span class="badge-plano">↳ plano</span></label>
                    <input type="number" id="cv-prod" class="cv-input" min="0" value="0">
                </div>
                <div>
                    <label class="cv-label">Linhas de Pesquisa / Projetos Ativos</label>
                    <input type="text" id="cv-linhas-pesquisa" class="cv-input" placeholder="ex.: Direito Constitucional Comparado, Saúde Pública">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="cv-label">Cursos que Ministra (Graduação e Pós-Graduação)</label>
                    <textarea id="cv-cursos-ministra" class="cv-input" rows="2" placeholder="ex.: Direito Civil I (Licenciatura), Gestão Hospitalar (Mestrado)"></textarea>
                </div>
                <div style="grid-column:1/-1;">
                    <label class="cv-label">Outras Atividades Académicas Relevantes</label>
                    <textarea id="cv-outras-atividades" class="cv-input" rows="2" placeholder="ex.: Membro de júris, comités científicos, professor convidado, conselhos editoriais"></textarea>
                </div>
            </div>

            <!-- Publicações Dinâmicas (Top 3) -->
            <div style="border-top:1px solid var(--line); padding-top:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div style="font-weight:700; font-size:13px; color:var(--ink);">📖 Últimas Publicações / Patentes / Trabalhos (últimas 3 mais relevantes)</div>
                    <button type="button" class="btn sm" onclick="window.adicionarPublicacao()" style="font-size:12px; padding:5px 12px; background:#EDE9FE; color:#4C1D95; border:1px solid #C4B5FD; border-radius:6px;">+ Adicionar</button>
                </div>
                <div id="lista-publicacoes" style="display:flex; flex-direction:column; gap:10px;">
                    <div style="text-align:center; padding:20px; color:var(--mut); font-size:13px; border:1px dashed var(--line); border-radius:8px;" id="publicacoes-empty">
                        Sem publicações registadas. Clique em «+ Adicionar» para inserir.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botão de Guardar -->
    <div style="display:flex; justify-content:flex-end; align-items:center; gap:14px; margin-bottom:40px;">
        <div id="cv-save-info" style="font-size:12.5px; color:var(--mut);"></div>
        <button type="submit" id="btn-guardar-cv" class="btn btn-p" style="padding:12px 28px; font-weight:700; font-size:14px; display:flex; align-items:center; gap:8px;">
            <span>💾</span> Guardar CV e Propagar aos Planos
        </button>
    </div>
</form>

<!-- Placeholder quando nenhum docente está selecionado -->
<div id="cv-placeholder" style="text-align:center; padding:60px 20px; color:var(--mut);">
    <div style="font-size:48px; margin-bottom:16px;">📄</div>
    <div style="font-size:15px; font-weight:600;">Selecione um docente acima para carregar o seu CV Estruturado.</div>
    <div style="font-size:13px; margin-top:8px;">Os dados são carregados automaticamente da base de dados.</div>
</div>

<style>
.cv-label {
    font-weight: 600;
    font-size: 12.5px;
    color: var(--ink);
    display: block;
    margin-bottom: 5px;
}
.cv-input {
    width: 100%;
    padding: 9px 12px;
    border-radius: 7px;
    border: 1px solid #cdcbc4;
    background: #fcfbf9;
    font-size: 13.5px;
    font-family: inherit;
    box-sizing: border-box;
    transition: border-color 0.15s;
}
.cv-input:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 2px rgba(var(--blue-rgb, 30,90,160), 0.08);
}
textarea.cv-input { resize: vertical; }
.badge-plano {
    background: #E8F5E9;
    color: #1B5E20;
    border-radius: 4px;
    padding: 1px 5px;
    font-size: 10.5px;
    font-weight: 700;
    margin-left: 4px;
}
.formacao-card, .publicacao-card {
    background: #f8fafc;
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 14px 16px;
    position: relative;
}
.formacao-card .remove-btn, .publicacao-card .remove-btn {
    position: absolute;
    top: 10px;
    right: 12px;
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    color: var(--bad);
    line-height: 1;
    padding: 2px 6px;
    border-radius: 4px;
}
.formacao-card .remove-btn:hover, .publicacao-card .remove-btn:hover {
    background: #FBEAE8;
}
</style>

<script>
// Estado local
let cvFormacoes   = [];
let cvPublicacoes = [];

// ──────────────────────────────────────────────────────
// Carregamento do CV via API
// ──────────────────────────────────────────────────────
window.carregarCV = async (docenteId) => {
    if (!docenteId) {
        document.getElementById('form-cv').style.display = 'none';
        document.getElementById('cv-placeholder').style.display = 'block';
        return;
    }

    const indicator = document.getElementById('cv-load-indicator');
    indicator.style.display = 'inline';

    try {
        const res  = await fetch(`index.php?api=cv_carregar&docente_id=${docenteId}`);
        const data = await res.json();

        if (!data.success) {
            alert('Erro ao carregar CV: ' + (data.error || 'Erro desconhecido.'));
            indicator.style.display = 'none';
            return;
        }

        const cv = data.data;
        preencherFormulario(cv);

        document.getElementById('cv-placeholder').style.display = 'none';
        document.getElementById('form-cv').style.display  = 'block';
        document.getElementById('cv-status-badge').style.display = 'none';
    } catch (e) {
        alert('Erro de comunicação ao carregar o CV.');
        console.error(e);
    } finally {
        indicator.style.display = 'none';
    }
};

// ──────────────────────────────────────────────────────
// Preenchimento do formulário com os dados do CV
// ──────────────────────────────────────────────────────
function preencherFormulario(cv) {
    document.getElementById('cv-docente-id').value           = cv.id || '';
    document.getElementById('cv-nome').value                 = cv.nome || '';
    document.getElementById('cv-email').value                = cv.email || '';
    document.getElementById('cv-telefone').value             = cv.telefone || '';
    document.getElementById('cv-bi').value                   = cv.bilhete_identidade || '';
    document.getElementById('cv-instituicao').value          = cv.instituicao_atual || '';
    document.getElementById('cv-grau').value                 = cv.grau_academico || 'Licenciado';
    document.getElementById('cv-especialidade').value        = cv.especialidade || '';
    document.getElementById('cv-inaarees').value             = cv.tem_inaarees === 'Sim' ? 'Sim' : 'Não';
    document.getElementById('cv-agregacao').value            = cv.tem_agregacao_pedag === 'Sim' ? 'Sim' : 'Não';
    document.getElementById('cv-categoria').value            = cv.categoria_carreira || 'Assistente';
    document.getElementById('cv-regime-contratual').value    = cv.regime_contratual || '';
    document.getElementById('cv-exp').value                  = cv.anos_experiencia_es || 0;
    document.getElementById('cv-cargo-atual').value          = cv.cargo_atual || '';
    document.getElementById('cv-prod').value                 = cv.producao_cientifica_3a || 0;
    document.getElementById('cv-linhas-pesquisa').value      = cv.linhas_pesquisa || '';
    document.getElementById('cv-cursos-ministra').value      = cv.cursos_ministrados || '';
    document.getElementById('cv-outras-atividades').value    = cv.outras_atividades || '';

    // Foto
    const prev = document.getElementById('cv-foto-preview');
    const btnRemoverFoto = document.getElementById('cv-btn-remover-foto');
    if (cv.foto_path) {
        prev.innerHTML = `<img src="${cv.foto_path}" style="width:100%;height:100%;object-fit:cover;">`;
        if (btnRemoverFoto) btnRemoverFoto.style.display = 'block';
    } else {
        prev.innerHTML = `<span style="text-align:center; padding:10px;">👤<br>Sem foto</span>`;
        if (btnRemoverFoto) btnRemoverFoto.style.display = 'none';
    }

    // Formações dinâmicas
    cvFormacoes = Array.isArray(cv.formacoes) ? cv.formacoes : [];
    renderFormacoes();

    // Publicações dinâmicas
    cvPublicacoes = Array.isArray(cv.publicacoes) ? cv.publicacoes : [];
    renderPublicacoes();
}

// ──────────────────────────────────────────────────────
// Formações Académicas — Dinâmicas
// ──────────────────────────────────────────────────────
window.adicionarFormacao = () => {
    cvFormacoes.push({ tipo: 'Licenciatura', area: '', instituicao: '', ano: '', nota: '' });
    renderFormacoes();
};

window.removerFormacao = (idx) => {
    cvFormacoes.splice(idx, 1);
    renderFormacoes();
};

function lerFormacoes() {
    const cards = document.querySelectorAll('.formacao-card');
    cvFormacoes = Array.from(cards).map((card, i) => ({
        tipo:        card.querySelector(`[data-f="tipo-${i}"]`)?.value || '',
        area:        card.querySelector(`[data-f="area-${i}"]`)?.value || '',
        instituicao: card.querySelector(`[data-f="inst-${i}"]`)?.value || '',
        ano:         card.querySelector(`[data-f="ano-${i}"]`)?.value || '',
        nota:        card.querySelector(`[data-f="nota-${i}"]`)?.value || '',
    }));
}

function renderFormacoes() {
    const container = document.getElementById('lista-formacoes');
    const empty     = document.getElementById('formacoes-empty');

    if (!cvFormacoes.length) {
        container.innerHTML = '';
        container.appendChild(empty);
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';

    container.innerHTML = cvFormacoes.map((f, i) => `
        <div class="formacao-card">
            <button type="button" class="remove-btn" onclick="window.removerFormacao(${i})" title="Remover">✕</button>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr 80px 80px; gap:10px; padding-right:32px;">
                <div>
                    <label class="cv-label">Tipo de Formação</label>
                    <select class="cv-input" data-f="tipo-${i}">
                        ${['Licenciatura','Mestrado','Doutoramento','Especialização','Pós-Graduação','Outro'].map(o =>
                            `<option ${f.tipo===o?'selected':''}>${o}</option>`
                        ).join('')}
                    </select>
                </div>
                <div>
                    <label class="cv-label">Área / Especialização</label>
                    <input type="text" class="cv-input" data-f="area-${i}" value="${escHtml(f.area)}" placeholder="ex.: Direito Civil">
                </div>
                <div>
                    <label class="cv-label">Instituição</label>
                    <input type="text" class="cv-input" data-f="inst-${i}" value="${escHtml(f.instituicao)}" placeholder="ex.: UJES, Universidade de Lisboa">
                </div>
                <div>
                    <label class="cv-label">Ano</label>
                    <input type="number" class="cv-input" data-f="ano-${i}" value="${escHtml(f.ano)}" min="1970" max="2030" placeholder="2020">
                </div>
                <div>
                    <label class="cv-label">Média</label>
                    <input type="text" class="cv-input" data-f="nota-${i}" value="${escHtml(f.nota)}" placeholder="14,2">
                </div>
            </div>
        </div>
    `).join('');
}

// ──────────────────────────────────────────────────────
// Publicações — Dinâmicas (Top 3)
// ──────────────────────────────────────────────────────
window.adicionarPublicacao = () => {
    if (cvPublicacoes.length >= 3) {
        alert('Máximo de 3 publicações conforme o modelo MESCTI.');
        return;
    }
    cvPublicacoes.push({ titulo: '', revista: '', ano: '', pais: '' });
    renderPublicacoes();
};

window.removerPublicacao = (idx) => {
    cvPublicacoes.splice(idx, 1);
    renderPublicacoes();
};

function lerPublicacoes() {
    const cards = document.querySelectorAll('.publicacao-card');
    cvPublicacoes = Array.from(cards).map((card, i) => ({
        titulo:  card.querySelector(`[data-p="titulo-${i}"]`)?.value || '',
        revista: card.querySelector(`[data-p="revista-${i}"]`)?.value || '',
        ano:     card.querySelector(`[data-p="ano-${i}"]`)?.value || '',
        pais:    card.querySelector(`[data-p="pais-${i}"]`)?.value || '',
    }));
}

function renderPublicacoes() {
    const container = document.getElementById('lista-publicacoes');
    const empty     = document.getElementById('publicacoes-empty');

    if (!cvPublicacoes.length) {
        container.innerHTML = '';
        container.appendChild(empty);
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';

    container.innerHTML = cvPublicacoes.map((p, i) => `
        <div class="publicacao-card">
            <button type="button" class="remove-btn" onclick="window.removerPublicacao(${i})" title="Remover">✕</button>
            <div style="display:grid; grid-template-columns:2fr 1fr 70px 90px; gap:10px; padding-right:32px;">
                <div>
                    <label class="cv-label">Título da Publicação / Trabalho</label>
                    <input type="text" class="cv-input" data-p="titulo-${i}" value="${escHtml(p.titulo)}" placeholder="Título completo da publicação">
                </div>
                <div>
                    <label class="cv-label">Revista / Evento / Editorial</label>
                    <input type="text" class="cv-input" data-p="revista-${i}" value="${escHtml(p.revista)}" placeholder="ex.: Revista de Direito">
                </div>
                <div>
                    <label class="cv-label">Ano</label>
                    <input type="number" class="cv-input" data-p="ano-${i}" value="${escHtml(p.ano)}" min="2000" max="2030">
                </div>
                <div>
                    <label class="cv-label">País</label>
                    <input type="text" class="cv-input" data-p="pais-${i}" value="${escHtml(p.pais)}" placeholder="Angola">
                </div>
            </div>
        </div>
    `).join('');
}

// ──────────────────────────────────────────────────────
// Foto — Preview
// ──────────────────────────────────────────────────────
window.previewFoto = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('cv-foto-preview').innerHTML =
            `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
        const btnRemoverFoto = document.getElementById('cv-btn-remover-foto');
        if (btnRemoverFoto) btnRemoverFoto.style.display = 'block';
    };
    reader.readAsDataURL(file);
};

window.removerFoto = async () => {
    const docenteId = document.getElementById('cv-docente-id').value;
    if (!docenteId) return;
    if (!confirm('Tem a certeza que deseja remover a foto deste docente?')) return;

    try {
        const res = await fetch('index.php?api=cv_remover_foto', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ docente_id: parseInt(docenteId) })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('cv-foto-preview').innerHTML = `<span style="text-align:center; padding:10px;">👤<br>Sem foto</span>`;
            document.getElementById('cv-foto-input').value = '';
            const btnRemoverFoto = document.getElementById('cv-btn-remover-foto');
            if (btnRemoverFoto) btnRemoverFoto.style.display = 'none';
            alert('✅ Foto removida com sucesso.');
        } else {
            alert('⚠️ ' + (data.error || 'Erro ao remover foto.'));
        }
    } catch (err) {
        alert('Erro de comunicação ao remover foto.');
    }
};

// ──────────────────────────────────────────────────────
// Guardar CV
// ──────────────────────────────────────────────────────
window.guardarCV = async (e) => {
    e.preventDefault();

    const docenteId = document.getElementById('cv-docente-id').value;
    if (!docenteId) { alert('Selecione um docente antes de guardar.'); return; }

    // Ler listas dinâmicas antes de serializar
    lerFormacoes();
    lerPublicacoes();

    const btn  = document.getElementById('btn-guardar-cv');
    const info = document.getElementById('cv-save-info');
    btn.disabled = true;
    btn.innerHTML = '⏳ A guardar...';
    info.textContent = '';

    const payload = {
        docente_id:             parseInt(docenteId),
        nome:                   document.getElementById('cv-nome').value.trim(),
        email:                  document.getElementById('cv-email').value.trim(),
        telefone:               document.getElementById('cv-telefone').value.trim(),
        bilhete_identidade:     document.getElementById('cv-bi').value.trim(),
        instituicao_atual:      document.getElementById('cv-instituicao').value.trim(),
        grau_academico:         document.getElementById('cv-grau').value,
        especialidade:          document.getElementById('cv-especialidade').value.trim(),
        tem_inaarees:           document.getElementById('cv-inaarees').value,
        tem_agregacao_pedag:    document.getElementById('cv-agregacao').value,
        categoria_carreira:     document.getElementById('cv-categoria').value,
        regime_contratual:      document.getElementById('cv-regime-contratual').value,
        anos_experiencia_es:    parseInt(document.getElementById('cv-exp').value) || 0,
        producao_cientifica_3a: parseInt(document.getElementById('cv-prod').value) || 0,
        linhas_pesquisa:        document.getElementById('cv-linhas-pesquisa').value.trim(),
        cursos_ministrados:     document.getElementById('cv-cursos-ministra').value.trim(),
        outras_atividades:      document.getElementById('cv-outras-atividades').value.trim(),
        formacoes:              cvFormacoes,
        publicacoes:            cvPublicacoes,
        experiencias:           [],
    };

    try {
        const res  = await fetch('index.php?api=cv_salvar', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('cv-status-badge').style.display = 'block';
            info.innerHTML = `<span style="color:#1baf7a; font-weight:700;">✅ ${data.message}</span>`;

            // Toast
            const toast = document.createElement('div');
            toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#1baf7a;color:#fff;padding:14px 22px;border-radius:8px;font-weight:700;z-index:9999;box-shadow:0 4px 14px rgba(0,0,0,0.18);font-size:13.5px;';
            toast.innerHTML = `✅ ${data.message}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        } else {
            info.innerHTML = `<span style="color:var(--bad);">⚠️ ${data.error || 'Erro ao guardar.'}</span>`;
        }
    } catch (err) {
        info.innerHTML = `<span style="color:var(--bad);">❌ Erro de comunicação.</span>`;
        console.error(err);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '💾 Guardar CV e Propagar aos Planos';
    }
};

// ──────────────────────────────────────────────────────
// Utilitário: escape HTML
// ──────────────────────────────────────────────────────
function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ──────────────────────────────────────────────────────
// Auto-carregar se docente_id vier por URL
// ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('cv-docente-select');
    if (sel && sel.value) {
        window.carregarCV(sel.value);
    }
});
</script>
