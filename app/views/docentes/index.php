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
    <?php if ($canEditDoc): ?>
        <span class="pill ok" style="font-size:12px; padding:5px 14px; font-weight:700;">✏️ Edição Permitida (GRH / Admin)</span>
    <?php else: ?>
        <span class="pill mut" style="font-size:12px; padding:5px 14px; font-weight:700;">🔒 Só Leitura (Consulta)</span>
    <?php endif; ?>
</h2>
<p class="sub" style="margin-bottom:14px;">Repositório documental de cada docente. Suporta CV, certificados, diplomas, BI, INAAREES e agregação pedagógica.</p>

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
                docsMap[item.tipo] = item;
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
        if (docsMap[dbKey]) loadedCount++;
    });

    const badgeClass = loadedCount >= 5 ? 'ok' : (loadedCount >= 3 ? 'warn' : 'bad');

    const cardsHtml = DOCTYPES.map(([k, title, desc]) => {
        const dbKey = typeToKeyMap[k] || k;
        const item = docsMap[dbKey];
        const done = !!item;
        const fileName = item ? (item.caminho_ficheiro.split('/').pop()) : 'Nenhum ficheiro';
        const fileUrl = item ? item.caminho_ficheiro : '#';

        const openBtn = done ? `<a href="${fileUrl}" target="_blank" onclick="event.stopPropagation();" style="display:inline-block; margin-top:6px; font-size:11px; color:var(--blue); font-weight:700;">👁️ Ver / Descarregar Documento</a>` : '';

        return `
            <div class="doccard">
                <div class="t">${title}</div>
                <div class="s">${desc}</div>
                <div class="drop ${done ? 'done' : ''}" onclick="window.triggerFilePicker(${docenteId}, '${name.replace(/'/g, "\\'")}', '${k}')" style="cursor:pointer;" title="Clique para selecionar e enviar um ficheiro do seu computador">
                    ${done ? '✔ ' + fileName + ' — no servidor' : '📁 Clicar para selecionar documento do computador'}
                </div>
                ${openBtn}
            </div>
        `;
    }).join('');

    const html = `
        <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:10px; overflow:hidden;">
            <div class="hd" style="font-weight:700; padding:12px 18px; border-bottom:1px solid var(--line); background:#faf9f5; color:var(--blue); font-size:14px; display:flex; justify-content:space-between; align-items:center;">
                <span>${name} · ficha documental</span>
                <span class="pill ${badgeClass}">${loadedCount}/6 documentos armazenados</span>
            </div>
            <div class="bd" style="padding:16px;">
                <table style="margin-bottom:14px; width:100%; border-collapse:collapse;">
                    <tbody>
                        <tr><td style="color:var(--mut); width:180px; padding:4px 0;">Grau académico</td><td><b>${d.grau_academico || 'Licenciado'}</b></td></tr>
                        <tr><td style="color:var(--mut); padding:4px 0;">Especialidade</td><td>${d.especialidade || 'Não identificada'}</td></tr>
                        <tr><td style="color:var(--mut); padding:4px 0;">INAAREES</td><td>${d.tem_inaarees || 'Não'}</td></tr>
                        <tr><td style="color:var(--mut); padding:4px 0;">Capacitação pedagógica</td><td>${d.tem_agregacao_pedag || 'Não'}</td></tr>
                        <tr><td style="color:var(--mut); padding:4px 0;">Cursos em 2025/26</td><td>1 ${d.nc >= 3 ? '<span class="pill bad">sobrecarga</span>' : ''}</td></tr>
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

document.addEventListener('DOMContentLoaded', () => {
    const firstRow = document.querySelector('.docente-row');
    if (firstRow) {
        firstRow.click();
    }
});
</script>
