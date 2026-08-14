<?php
/**
 * View: Docentes & Documentos (100% Fiel ao Protótipo vDocentes() de 01_Portal_Autonomo/backoffice/index.html)
 * sftcoordenacao — ISPSN 2026/27
 */
$canEditDoc = Auth::canEditDoc();
$currentUserRole = $_SESSION['user']['perfil'] ?? 'coordenador';
?>

<h2 class="page" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:4px;">
    <span>📁 Repositório de Docentes &amp; Documentos</span>
    <div style="display:flex; gap:8px; align-items:center;">
        <?php if ($canEditDoc): ?>
            <button class="btn sm btn-ok" style="background:#1e8449; color:#fff; font-weight:700; font-size:12.5px; padding:6px 14px;" onclick="window.toggleModalNovoDocente()">➕ Novo Docente</button>
            <span class="pill ok" style="font-size:12px; padding:5px 14px; font-weight:700;">✏️ Edição Permitida (GRH / Admin)</span>
        <?php else: ?>
            <span class="pill mut" style="font-size:12px; padding:5px 14px; font-weight:700;">🔒 Só Leitura (Consulta)</span>
        <?php endif; ?>
    </div>
</h2>
<p class="sub" style="margin-bottom:14px;">Repositório documental de cada docente. Suporta CV, certificados, diplomas, BI, INAAREES e agregação pedagógica.</p>

<!-- Modal de Cadastro de Novo Docente no Catálogo Académico -->
<div id="modal-novo-docente" class="hidden" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.55); z-index:999; display:flex; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; max-width:580px; width:90%; padding:24px; box-shadow:0 8px 30px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:12px; margin-bottom:16px;">
            <h3 style="margin:0; color:var(--blue); font-size:16px;">➕ Cadastrar Novo Docente no Catálogo Académico</h3>
            <button onclick="window.toggleModalNovoDocente()" style="background:none; border:none; font-size:18px; font-weight:700; cursor:pointer;">✕</button>
        </div>
        <form onsubmit="window.criarDocente(event)">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                <div style="grid-column: span 2;">
                    <label style="font-weight:700; font-size:12px; color:var(--navy);">Nome Completo *</label>
                    <input type="text" id="nd-nome" required class="form-control" placeholder="ex: Prof. Dr. António Silva" style="width:100%; padding:8px; border-radius:6px; border:1px solid var(--line);">
                </div>
                <div>
                    <label style="font-weight:700; font-size:12px; color:var(--navy);">E-mail Corporativo (Opcional)</label>
                    <input type="email" id="nd-email" class="form-control" placeholder="antonio.silva@ispsn.org" style="width:100%; padding:8px; border-radius:6px; border:1px solid var(--line);">
                </div>
                <div>
                    <label style="font-weight:700; font-size:12px; color:var(--navy);">Grau Académico *</label>
                    <select id="nd-grau" required class="form-control" style="width:100%; padding:8px; border-radius:6px; border:1px solid var(--line); font-weight:600;">
                        <option value="Licenciado">Licenciado</option>
                        <option value="Mestre">Mestre</option>
                        <option value="Doutor">Doutor</option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <label style="font-weight:700; font-size:12px; color:var(--navy);">Especialidade / Área de Conhecimento</label>
                    <input type="text" id="nd-especialidade" class="form-control" placeholder="ex: Direito Constitucional, Economia Monetária" style="width:100%; padding:8px; border-radius:6px; border:1px solid var(--line);">
                </div>
                <div>
                    <label style="font-weight:700; font-size:12px; color:var(--navy);">Homologação INAAREES *</label>
                    <select id="nd-inaarees" required class="form-control" style="width:100%; padding:8px; border-radius:6px; border:1px solid var(--line);">
                        <option value="Não">Não</option>
                        <option value="Sim">Sim</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight:700; font-size:12px; color:var(--navy);">Capacitação Pedagógica *</label>
                    <select id="nd-pedag" required class="form-control" style="width:100%; padding:8px; border-radius:6px; border:1px solid var(--line);">
                        <option value="Não">Não</option>
                        <option value="Sim">Sim</option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <label style="font-weight:700; font-size:12px; color:var(--navy);">Categoria da Carreira Docente *</label>
                    <select id="nd-categoria" required class="form-control" style="width:100%; padding:8px; border-radius:6px; border:1px solid var(--line); font-weight:600;">
                        <option value="Assistente">Assistente</option>
                        <option value="Auxiliar">Professor Auxiliar</option>
                        <option value="Associado">Professor Associado</option>
                        <option value="Catedrático">Professor Catedrático</option>
                        <option value="Colaborador">Colaborador</option>
                        <option value="Convidado">Convidado</option>
                    </select>
                </div>
            </div>
            <div style="text-align:right; border-top:1px solid var(--line); padding-top:14px; margin-top:16px; display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" onclick="window.toggleModalNovoDocente()" class="btn ghost">Cancelar</button>
                <button type="submit" class="btn btn-ok" style="background:#1e8449; color:#fff; font-weight:700;">💾 Gravar Novo Docente</button>
            </div>
        </form>
    </div>
</div>

<?php if (!$canEditDoc): ?>
    <div class="note" style="border-left:4px solid var(--gold); background:#FFF9E6; border:1px solid var(--gold); border-radius:8px; padding:12px 16px; font-size:13px; color:#1A1A1A; margin-bottom:18px; box-shadow:0 2px 6px rgba(0,0,0,0.02);">
        <b>🔒 Acesso Restrito em Modo Só Leitura</b> — De acordo com a especificação funcional do ISPSN, o carregamento e a validação de documentos oficiais (BI, Certificados, Diplomas, INAAREES e Agregação Pedagógica) são de <b>responsabilidade exclusiva do perfil GRH (Recursos Humanos)</b>.
    </div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:340px 1fr; gap:18px; align-items:start;">
    <!-- Lista de Docentes à Esquerda -->
    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:10px; overflow:hidden;">
        <div class="hd" style="font-weight:700; padding:12px 18px; border-bottom:1px solid var(--line); background:#faf9f5; color:var(--blue); font-size:14px;">
            Docentes (<?= count($docentes) ?>)
        </div>
        <div class="bd" style="padding:10px;">
            <input type="text" id="search-docente" onkeyup="window.filterDocentes(this.value)" placeholder="Procurar docente…" style="width:100%; margin-bottom:8px;">
        </div>
        <div style="max-height:480px; overflow-y:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <tbody id="tbody-docentes-list">
                    <?php foreach ($docentes as $idx => $d): ?>
                        <tr class="docente-row" data-id="<?= $d['id'] ?>" data-name="<?= strtolower(htmlspecialchars($d['nome'])) ?>" style="border-bottom:1px solid #eee; cursor:pointer;" onclick="window.selectDocente(<?= $d['id'] ?>, this)">
                            <td style="padding:8px 10px;"><b><?= htmlspecialchars($d['nome']) ?></b></td>
                            <td style="padding:8px 6px;">
                                <?php 
                                    $grau = $d['grau_academico'];
                                    $gClass = str_starts_with($grau, 'Doutor') ? 'ok' : (str_starts_with($grau, 'Mestre') ? 'warn' : 'mut');
                                ?>
                                <span class="pill <?= $gClass ?>"><?= htmlspecialchars($grau) ?></span>
                            </td>
                            <td style="padding:8px 10px; text-align:right;">
                                <?php 
                                    $numDocsValidos = (int)($d['total_docs_validos'] ?? 0);
                                    $bColorClass = ($numDocsValidos >= 5) ? 'ok' : (($numDocsValidos >= 3) ? 'warn' : 'bad');
                                ?>
                                <span class="pill <?= $bColorClass ?>" id="mini-badge-<?= $d['id'] ?>">
                                    <?= $numDocsValidos ?>/6
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Painel de Ficha Documental e Cards de Documentos à Direita -->
    <div id="detail-container">
        <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:10px; padding:20px;">
            <div style="color:var(--mut);">Selecione um docente na lista para ver e carregar os seus documentos.</div>
        </div>
    </div>
</div>

<!-- Input Nativo para Seleção de Ficheiros do Computador -->
<input type="file" id="native-file-picker" style="display:none;" accept=".pdf,.doc,.docx,.png,.jpg" onchange="window.handleFilePicked(event)">

<!-- Modal de PDF do Documento -->
<div id="modal-pdf-viewer" class="hidden" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:9999; justify-content:center; align-items:center; display:none;">
    <div style="background:#fff; width:92%; max-width:840px; height:88vh; border-radius:12px; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 12px 36px rgba(0,0,0,0.35);">
        <div style="background:var(--blue); color:#fff; padding:14px 20px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:16px;" id="pdf-modal-title">Visualizador de Documento</h3>
                <div style="font-size:12px; opacity:0.85;">Instituto Superior Politécnico Sol Nascente (ISPSN)</div>
            </div>
            <button class="btn sm ghost" style="color:#fff; border-color:#fff;" onclick="window.closePdfModal()">✕ Fechar</button>
        </div>
        <div style="flex:1; padding:24px; overflow-y:auto; background:#f0f2f5;" id="pdf-modal-body"></div>
    </div>
</div>

<script>
window.ALL_DOCENTES = <?= json_encode($docentes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.CAN_EDIT_DOC = <?= $canEditDoc ? 'true' : 'false' ?>;
window.DOC_STATE = {};
window.CURRENT_UPLOAD_TARGET = null;

const DOCTYPES = [
    ['cv', 'Curriculum Vitae', 'PDF do CV completo'],
    ['cert', 'Certificados', 'Habilitações (Lic./Mest./Dout.)'],
    ['dip', 'Diplomas', 'Diplomas oficiais'],
    ['bi', 'Bilhete de Identidade', 'Documento de identificação'],
    ['ina', 'Reconhecimento INAAREES', 'Homologação de estudos'],
    ['ped', 'Agregação Pedagógica', 'Capacitação psicopedagógica']
];

window.selectDocente = async (docenteInput, rowElement) => {
    let d = null;
    if (typeof docenteInput === 'object' && docenteInput !== null) {
        d = docenteInput;
    } else {
        d = window.ALL_DOCENTES.find(item => item.id == docenteInput);
    }
    if (!d) return;

    window.SELECTED_DOCENTE = d;
    const docenteId = d.id;
    const name = d.nome;

    // Destaque visual na lista de docentes
    document.querySelectorAll('.docente-row').forEach(r => {
        r.style.background = '';
        r.style.borderLeft = '';
    });
    const targetRow = rowElement || document.querySelector(`.docente-row[data-id="${docenteId}"]`);
    if (targetRow) {
        targetRow.style.background = '#f0f5fb';
        targetRow.style.borderLeft = '4px solid var(--gold)';
    }

    let docsMap = {};
    try {
        const res = await fetch(`index.php?api=docente_documentos&docente_id=${docenteId}`);
        const data = await res.json();
        if (data.success && Array.isArray(data.data)) {
            data.data.forEach(item => {
                if (!docsMap[item.tipo]) docsMap[item.tipo] = [];
                docsMap[item.tipo].push(item);
            });
        }
    } catch (e) {
        console.error('Erro ao carregar documentos:', e);
    }

    const typeToKeyMap = {
        'cv': 'cv',
        'cert': 'certificados',
        'dip': 'diplomas',
        'bi': 'bi',
        'ina': 'inaarees',
        'ped': 'agregacao_pedag'
    };

    let loadedCount = 0;
    DOCTYPES.forEach(([k]) => {
        const dbKey = typeToKeyMap[k] || k;
        if (docsMap[dbKey] && docsMap[dbKey].length > 0) loadedCount++;
    });

    const badgeClass = loadedCount >= 5 ? 'ok' : (loadedCount >= 3 ? 'warn' : 'bad');

    const cardsHtml = DOCTYPES.map(([k, title, desc]) => {
        const dbKey = typeToKeyMap[k] || k;
        const items = docsMap[dbKey] || [];
        const done = items.length > 0;

        let fileListHtml = '';
        if (done) {
            fileListHtml = `
                <div style="margin-top:8px; display:flex; flex-direction:column; gap:6px;">
                    ${items.map(item => {
                        const fileName = item.caminho_ficheiro.split('/').pop();
                        const rawUrl = item.caminho_ficheiro || '#';
                        const viewUrl = rawUrl.replace(/^(\/)?public\//i, '');
                        const dateStr = item.created_at ? new Date(item.created_at).toLocaleDateString('pt-PT') : '';
                        return `
                            <div style="background:#f8fafc; border:1px solid var(--line); border-radius:6px; padding:6px 10px; font-size:12px; display:flex; justify-content:space-between; align-items:center;">
                                <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:70%;">
                                    <span style="font-weight:700; color:var(--navy);" title="${fileName}">📄 ${fileName}</span>
                                    <span style="font-size:10.5px; color:var(--mut); display:block;">${dateStr ? 'Carregado a ' + dateStr : ''}</span>
                                </div>
                                <div style="display:flex; gap:6px; align-items:center; flex-shrink:0;">
                                    <a href="${viewUrl}" target="_blank" onclick="event.stopPropagation();" class="btn sm ghost" style="padding:2px 8px; font-size:11px; color:var(--blue); border-color:var(--blue); text-decoration:none;" title="Ver / Descarregar Documento">👁️ Ver</a>
                                    ${window.CAN_EDIT_DOC ? `<button onclick="event.stopPropagation(); window.eliminarDoc(${item.id}, ${docenteId})" class="btn sm ghost" style="padding:2px 6px; font-size:11px; color:var(--bad); border-color:var(--bad);" title="Eliminar Documento">🗑️</button>` : ''}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        return `
            <div class="doccard">
                <div class="t">${title} ${done ? `<span class="pill ok" style="font-size:10.5px; padding:2px 6px; font-weight:700;">${items.length} ficheiro(s)</span>` : ''}</div>
                <div class="s">${desc}</div>
                ${fileListHtml}
                ${window.CAN_EDIT_DOC ? `
                    <div class="drop ${done ? 'done' : ''}" onclick="window.triggerFilePicker(${docenteId}, '${name.replace(/'/g, "\\'")}', '${k}')" style="cursor:pointer; margin-top:8px;" title="Clique para selecionar e enviar um ficheiro do computador">
                        ${done ? '➕ Adicionar outro ficheiro' : '📁 Clicar para selecionar documento do computador'}
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');

    const html = `
        <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:10px; overflow:hidden;">
            <div class="hd" style="font-weight:700; padding:12px 18px; border-bottom:1px solid var(--line); background:#faf9f5; color:var(--blue); font-size:14px; display:flex; justify-content:space-between; align-items:center;">
                <span>${name} · ficha documental</span>
                <span class="pill ${badgeClass}">${loadedCount}/6 categorias com documentos</span>
            </div>
            <div class="bd" style="padding:16px;">
                <table style="margin-bottom:14px; width:100%; border-collapse:collapse;">
                    <tbody>
                        <tr><td style="color:var(--mut); width:180px; padding:4px 0;">Grau académico</td><td><b>${d.grau_academico || 'Licenciado'}</b></td></tr>
                        <tr><td style="color:var(--mut); padding:4px 0;">Especialidade</td><td>${d.especialidade || 'Não identificada'}</td></tr>
                        <tr><td style="color:var(--mut); padding:4px 0;">INAAREES</td><td>${d.tem_inaarees || 'Não'}</td></tr>
                        <tr><td style="color:var(--mut); padding:4px 0;">Capacitação pedagógica</td><td>${d.tem_agregacao_pedag || 'Não'}</td></tr>
                    </tbody>
                </table>
                <div class="docgrid">${cardsHtml}</div>
                <div style="margin-top:14px;">
                    <a href="index.php?page=cv&docente_id=${d.id}" class="btn" style="text-decoration:none; display:inline-block;">Preencher CV estruturado →</a>
                </div>
            </div>
        </div>
    `;

    document.getElementById('detail-container').innerHTML = html;
};

window.eliminarDoc = async (docId, docenteId) => {
    if (!confirm('Tem a certeza que deseja eliminar este documento?')) return;
    try {
        const res = await fetch('index.php?api=docente_eliminar_documento', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: docId })
        });
        const data = await res.json();
        if (data.success) {
            alert('✅ Documento eliminado com sucesso.');
            if (window.SELECTED_DOCENTE && window.SELECTED_DOCENTE.id == docenteId) {
                window.selectDocente(window.SELECTED_DOCENTE);
            }
        } else {
            alert('⚠️ Erro ao eliminar documento: ' + (data.error || data.message || 'Erro desconhecido.'));
        }
    } catch (err) {
        alert('Erro de comunicação ao eliminar documento.');
    }
};

window.triggerFilePicker = (docenteId, docName, key) => {
    if (!window.CAN_EDIT_DOC) {
        alert('Docentes/Documentos é preenchido pelo perfil GRH');
        return;
    }

    window.CURRENT_UPLOAD_TARGET = { docenteId, docName, key };
    const picker = document.getElementById('native-file-picker');
    picker.value = ''; // Reset
    picker.click();   // Abre o seletor nativo do sistema operativo
};

window.handleFilePicked = async (event) => {
    const file = event.target.files[0];
    if (!file || !window.CURRENT_UPLOAD_TARGET) return;

    const { docenteId, docName, key } = window.CURRENT_UPLOAD_TARGET;

    const formData = new FormData();
    formData.append('docente_id', docenteId);
    formData.append('tipo', key);
    formData.append('ficheiro', file);

    try {
        const res = await fetch('index.php?api=docente_upload_documento', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            alert(`✅ ${data.message}`);
            if (window.SELECTED_DOCENTE && window.SELECTED_DOCENTE.id == docenteId) {
                window.selectDocente(window.SELECTED_DOCENTE);
            }
        } else {
            alert(`⚠️ Erro ao enviar ficheiro: ${data.error || 'Erro desconhecido.'}`);
        }
    } catch (err) {
        alert('Erro de comunicação ao carregar ficheiro.');
    }
};

window.filterDocentes = (term) => {
    const q = term.toLowerCase();
    const rows = document.querySelectorAll('.docente-row');
    rows.forEach(r => {
        const name = r.getAttribute('data-name');
        r.style.display = name.includes(q) ? '' : 'none';
    });
};

window.toggleModalNovoDocente = () => {
    const modal = document.getElementById('modal-novo-docente');
    if (modal) modal.classList.toggle('hidden');
};

window.criarDocente = async (event, confirmDup = false) => {
    if (event) event.preventDefault();

    const nome = document.getElementById('nd-nome').value.trim();
    const email = document.getElementById('nd-email').value.trim();
    const grau = document.getElementById('nd-grau').value;
    const especialidade = document.getElementById('nd-especialidade').value.trim();
    const tem_inaarees = document.getElementById('nd-inaarees').value;
    const tem_agregacao_pedag = document.getElementById('nd-pedag').value;
    const categoria_carreira = document.getElementById('nd-categoria').value;

    try {
        const res = await fetch('index.php?api=docente_criar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                nome, email, grau_academico: grau, especialidade,
                tem_inaarees, tem_agregacao_pedag, categoria_carreira,
                confirm_dup: confirmDup
            })
        });
        const data = await res.json();

        if (data.dup_warning) {
            if (confirm(data.message)) {
                return window.criarDocente(null, true);
            }
            return;
        }

        if (data.success) {
            alert(`✅ ${data.message}`);
            window.toggleModalNovoDocente();
            location.reload();
        } else {
            alert(`⚠️ Erro: ${data.error || data.message || 'Falha ao cadastrar docente.'}`);
        }
    } catch (err) {
        alert('Erro de comunicação ao registar docente.');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const firstRow = document.querySelector('.docente-row');
    if (firstRow) {
        firstRow.click();
    }
});
</script>
