/**
 * Lógica do Módulo de Cobertura Docente (100% Fiel ao Protótipo Original ISPSN + Backend PHP)
 * sftcoordenacao — ISPSN 2026/27
 */

document.addEventListener('DOMContentLoaded', () => {
    let currentCursoId = 1;
    let currentCursoNome = 'Direito';
    let currentAnoLectivo = window.CURRENT_ANO_LECTIVO || '2026/27';
    let selectedTurmaCod = '';
    let docentesList = [];
    let docentesMap = {};
    let planoData = null;
    let linhasData = [];
    let turmasData = [];

    const cursoSelect = document.getElementById('select-curso');
    const turmaSelect = document.getElementById('select-turma');
    const tbodyLinhas = document.getElementById('tbody-linhas');
    const btnSubmeter = document.getElementById('btn-submeter');
    const btnAprovar  = document.getElementById('btn-aprovar');
    const badgeEstado = document.getElementById('badge-estado');
    const badgeTurmaInfo = document.getElementById('badge-turma-info');
    const pillConfPct = document.getElementById('pill-conf-pct');
    const pillAtribStat = document.getElementById('pill-atrib-stat');
    const cardTurmaHeader = document.getElementById('card-turma-header');

    init();

    async function init() {
        await Promise.all([fetchCursos(), fetchDocentes()]);
        await loadPlano(currentCursoId);
    }

    async function fetchCursos() {
        try {
            const res = await fetch('index.php?api=cursos');
            const data = await res.json();
            if (data.success && data.data.length > 0) {
                if (cursoSelect) {
                    const userCursoId = window.CURRENT_USER_CURSO_ID;
                    const matchedCurso = userCursoId ? data.data.find(c => parseInt(c.id) === parseInt(userCursoId)) : null;
                    const selectedCurso = matchedCurso || data.data[0];

                    cursoSelect.innerHTML = data.data.map(c => {
                        const isSel = (parseInt(c.id) === parseInt(selectedCurso.id)) ? 'selected' : '';
                        return `<option value="${c.id}" data-nome="${c.nome}" ${isSel}>${c.nome}</option>`;
                    }).join('');

                    currentCursoId = parseInt(selectedCurso.id);
                    currentCursoNome = selectedCurso.nome;

                    cursoSelect.addEventListener('change', (e) => {
                        currentCursoId = parseInt(e.target.value);
                        const opt = e.target.options[e.target.selectedIndex];
                        currentCursoNome = opt ? opt.getAttribute('data-nome') : 'Curso';
                        selectedTurmaCod = '';
                        loadPlano(currentCursoId);
                    });
                }
            }
        } catch (err) {
            console.error('Erro ao carregar cursos:', err);
        }
    }

    async function fetchDocentes() {
        try {
            const res = await fetch('index.php?api=docentes');
            const data = await res.json();
            if (data.success) {
                docentesList = data.data;
                docentesMap = {};
                docentesList.forEach(d => {
                    docentesMap[d.id] = d;
                    if (d.nome) docentesMap[d.nome] = d;
                });
            }
        } catch (err) {
            console.error('Erro ao carregar docentes:', err);
        }
    }

    async function loadPlano(cursoId) {
        if (!tbodyLinhas) return;
        tbodyLinhas.innerHTML = `<tr><td colspan="12" style="text-align:center; padding:30px; color:var(--mut);">Carregando cobertura docente (${currentAnoLectivo})...</td></tr>`;

        try {
            const res = await fetch(`index.php?api=plano&curso_id=${cursoId}&ano_lectivo=${encodeURIComponent(currentAnoLectivo)}`);
            const data = await res.json();

            if (data.success) {
                planoData  = data.plano;
                linhasData = data.linhas || [];
                
                extractTurmas();
                populateTurmaSelect();
                updateKPIsAndHeader();
                renderPlano();
            } else {
                tbodyLinhas.innerHTML = `<tr><td colspan="12" style="text-align:center; color:var(--bad); font-weight:700;">${data.error}</td></tr>`;
            }
        } catch (err) {
            console.error('Erro ao carregar plano:', err);
            tbodyLinhas.innerHTML = `<tr><td colspan="12" style="text-align:center; color:var(--bad);">Erro de comunicação com o servidor PHP.</td></tr>`;
        }
    }

    function extractTurmas() {
        const setMap = new Map();
        linhasData.forEach(l => {
            const cod = l.turma_nome || `TURMA-${l.ano_curricular}A`;
            if (!setMap.has(cod)) {
                let turno = l.turno;
                if (!turno) {
                    console.warn(`[cobertura.js] Turma sem turno explícito vindo da BD: ${cod}. Usando fallback 'Manhã'.`);
                    turno = 'Manhã';
                }
                setMap.set(cod, {
                    cod: cod,
                    ano: parseInt(l.ano_curricular) || 1,
                    turno: turno
                });
            }
        });
        turmasData = Array.from(setMap.values()).sort((a, b) => a.ano - b.ano || a.cod.localeCompare(b.cod));

        const exists = turmasData.some(t => t.cod === selectedTurmaCod);
        if ((!selectedTurmaCod || !exists) && turmasData.length > 0) {
            selectedTurmaCod = turmasData[0].cod;
        }
    }

    function formatTurmaRotulo(rawCod, rawTurno, ano) {
        if (!rawCod) return 'Turma';
        let s = String(rawCod).trim();

        // 1. Detectar turno real a partir do texto ou do parâmetro
        let turno = (rawTurno || '').trim();
        if (/Pós-Laboral|Pos-Laboral/i.test(s) || /TURMA-\d+P/i.test(s)) {
            turno = 'Pós-Laboral';
        } else if (/Regime\s*B|\-RB|RB/i.test(s)) {
            turno = 'Regime B';
        } else if (/Noite|NT/i.test(s) || /TURMA-\d+N/i.test(s)) {
            turno = 'Noite';
        } else if (/Tarde|\bT\b|1T|2T|3T|4T|5T|TURMA-\d+T/i.test(s)) {
            turno = 'Tarde';
        } else if (/Manh[aã]|\bM\b|1M|2M|3M|4M|5M|TURMA-\d+M/i.test(s)) {
            turno = 'Manhã';
        }
        if (!turno) turno = 'Manhã';

        // 2. Extrair código limpo entre parênteses ou a sigla principal (ex.: ENF1MA, DIR-RB1MA, TURMA-1M)
        let codOficial = '';
        const codeMatch = s.match(/\(([A-Za-z0-9\-_]+)\)/);
        if (codeMatch && !/Manh[aã]|Tarde|Noite|P[oó]s/i.test(codeMatch[1])) {
            codOficial = codeMatch[1];
        } else {
            codOficial = s.replace(/\s*\([^)]*\)/g, '').trim();
        }

        // 3. Extrair letra da turma (A, B, C, D, E, F, G, H, I...)
        let letra = '';
        const turmaLetraMatch = s.match(/Turma\s+([A-Z])/i);
        if (turmaLetraMatch) {
            letra = turmaLetraMatch[1].toUpperCase();
        } else {
            const letterSuffixMatch = codOficial.match(/(?:[0-9]|RB[0-9]?|TURMA-\d+)([A-Z])$/i);
            if (letterSuffixMatch) {
                const l = letterSuffixMatch[1].toUpperCase();
                if (['M', 'T', 'N', 'P'].includes(l) && codOficial.startsWith('TURMA-')) {
                    letra = 'A';
                } else {
                    letra = l;
                }
            }
        }

        const rotuloTurma = letra ? `Turma ${letra}` : `Turma Única`;
        return `${rotuloTurma} · ${turno} (${codOficial || s})`;
    }

    function populateTurmaSelect() {
        if (!turmaSelect) return;

        if (turmasData.length === 0) {
            turmaSelect.innerHTML = `<option value="">-- Sem Turmas --</option>`;
            return;
        }

        const byAno = {};
        turmasData.forEach(t => {
            byAno[t.ano] = byAno[t.ano] || [];
            byAno[t.ano].push(t);
        });

        let html = '';
        Object.keys(byAno).sort().forEach(ano => {
            const countTurmasAno = byAno[ano].length;
            html += `<optgroup label="${ano}.º Ano (${countTurmasAno} ${countTurmasAno === 1 ? 'Turma' : 'Turmas'})">`;
            byAno[ano].forEach(t => {
                const countTotal = linhasData.filter(l => (l.turma_nome || `TURMA-${l.ano_curricular}A`) === t.cod).length;
                const countAtrib = linhasData.filter(l => (l.turma_nome || `TURMA-${l.ano_curricular}A`) === t.cod && l.docente_id).length;
                const countInfo = countTotal > 0 ? ` [${countAtrib}/${countTotal} UCs]` : '';
                const rotulo = formatTurmaRotulo(t.cod, t.turno, t.ano);
                html += `<option value="${t.cod}">${rotulo}${countInfo}</option>`;
            });
            html += `</optgroup>`;
        });

        // Só atualiza o HTML se a estrutura de opções mudou
        if (turmaSelect.innerHTML !== html) {
            turmaSelect.innerHTML = html;
        }

        if (selectedTurmaCod) {
            turmaSelect.value = selectedTurmaCod;
        }

        turmaSelect.onchange = (e) => {
            selectedTurmaCod = e.target.value;
            updateKPIsAndHeader();
            renderPlano();
        };
    }

    function updateKPIsAndHeader() {
        const turmaLinhas = linhasData.filter(l => (l.turma_nome || `TURMA-${l.ano_curricular}A`) === selectedTurmaCod);
        const total = turmaLinhas.length || linhasData.length;
        const atrib = (turmaLinhas.length ? turmaLinhas : linhasData).filter(l => l.docente_id).length;
        const conf  = (turmaLinhas.length ? turmaLinhas : linhasData).filter(l => l.conformidade === 'Sim').length;
        const pctConf = total ? Math.round((conf / total) * 100) : 0;

        const currentTurma = turmasData.find(t => t.cod === selectedTurmaCod) || turmasData[0];

        if (badgeTurmaInfo && currentTurma) {
            const rotulo = formatTurmaRotulo(currentTurma.cod, currentTurma.turno, currentTurma.ano);
            badgeTurmaInfo.textContent = `${currentTurma.ano}.º Ano · ${rotulo} · ${atrib}/${total} UCs`;
        }
        if (cardTurmaHeader && currentTurma) {
            const rotulo = formatTurmaRotulo(currentTurma.cod, currentTurma.turno, currentTurma.ano);
            cardTurmaHeader.textContent = `${currentTurma.ano}.º Ano — ${rotulo} — Disciplinas e Atribuições Docentes (${atrib}/${total} Atribuídas)`;
        }

        if (pillConfPct) {
            pillConfPct.textContent = `Conf. ${pctConf}%`;
            pillConfPct.className = `pill ${pctConf >= 70 ? 'ok' : pctConf >= 60 ? 'warn' : 'bad'}`;
        }

        if (pillAtribStat) {
            pillAtribStat.textContent = `${atrib}/${total} atribuições`;
        }
    }

    function renderPlano() {
        const userRole = window.CURRENT_USER_ROLE || 'coordenador';
        const isCoordOrAdmin = ['coordenador', 'admin'].includes(userRole);
        const isChefeDeptoOrAdmin = ['chefe_departamento', 'admin'].includes(userRole);
        const isPresidenteOrAdmin = ['presidente', 'admin'].includes(userRole);

        const btnSubmeter = document.getElementById('btn-submeter');
        const btnAprovarDepto = document.getElementById('btn-aprovar-depto');
        const btnRecusarDepto = document.getElementById('btn-recusar-depto');
        const btnValidarPR = document.getElementById('btn-validar-pr');

        if (badgeEstado && planoData) {
            badgeEstado.textContent = planoData.estado;
            if (['Validado', 'Aprovado'].includes(planoData.estado)) {
                badgeEstado.className = 'b b-sim';
            } else if (['Submetido', 'Aprovado pelo Departamento'].includes(planoData.estado)) {
                badgeEstado.className = 'b b-ni';
            } else {
                badgeEstado.className = 'b b-nao';
            }

            if (btnSubmeter) {
                btnSubmeter.style.display = (isCoordOrAdmin && ['Rascunho', 'Devolvido', 'Em Elaboração'].includes(planoData.estado)) ? 'inline-block' : 'none';
            }
            if (btnAprovarDepto) {
                btnAprovarDepto.style.display = (isChefeDeptoOrAdmin && ['Submetido', 'Rascunho', 'Em Elaboração'].includes(planoData.estado)) ? 'inline-block' : 'none';
            }
            if (btnRecusarDepto) {
                btnRecusarDepto.style.display = (isChefeDeptoOrAdmin && ['Submetido', 'Rascunho', 'Em Elaboração'].includes(planoData.estado)) ? 'inline-block' : 'none';
            }
            if (btnValidarPR) {
                btnValidarPR.style.display = (isPresidenteOrAdmin && ['Submetido', 'Aprovado pelo Departamento', 'Aprovado'].includes(planoData.estado)) ? 'inline-block' : 'none';
            }

            const bannerDev = document.getElementById('banner-devolucao');
            const bannerObs = document.getElementById('banner-devolucao-obs');
            if (bannerDev && bannerObs) {
                if (window._bannerDevTimer) clearTimeout(window._bannerDevTimer);
                if (planoData.estado === 'Devolvido') {
                    bannerObs.textContent = planoData.observacoes ? `Motivo/Parecer: "${planoData.observacoes}"` : 'Motivo não especificado. Consulte o histórico de auditoria.';
                    bannerDev.style.display = 'block';
                    bannerDev.style.opacity = '1';

                    // O aviso de devolução fica visível ao abrir e desaparece automaticamente após 6 segundos
                    window._bannerDevTimer = setTimeout(() => {
                        bannerDev.style.opacity = '0';
                        setTimeout(() => {
                            bannerDev.style.display = 'none';
                        }, 600);
                    }, 6000);
                } else {
                    bannerDev.style.display = 'none';
                }
            }
        }

        const userCursoId = window.CURRENT_USER_CURSO_ID;

        let canEditCoverage = false;
        if (['admin', 'chefe_departamento', 'presidente'].includes(userRole)) {
            canEditCoverage = true;
        } else if (userRole === 'coordenador') {
            canEditCoverage = (!userCursoId || parseInt(userCursoId) === parseInt(currentCursoId));
        }

        const isPlanLocked = planoData ? ['Validado'].includes(planoData.estado) : false;
        // O plano fica bloqueado se o perfil não tiver permissão para este curso ou se já estiver Validado (exceto Admin)
        const isLocked = !canEditCoverage || (isPlanLocked && userRole !== 'admin');
        const disabledAttr = isLocked ? 'disabled' : '';

        const bannerReadOnly = document.getElementById('banner-readonly');
        if (bannerReadOnly) {
            if (isLocked && planoData) {
                bannerReadOnly.style.display = 'block';
                if (userRole === 'coordenador' && userCursoId && parseInt(userCursoId) !== parseInt(currentCursoId)) {
                    bannerReadOnly.innerHTML = `<b>🔒 Modo Só Leitura</b> — O seu perfil de <b>Coordenador de Curso</b> tem permissão de edição apenas no seu curso atribuído. Ao consultar este curso, os campos encontram-se bloqueados para alteração.`;
                } else if (!canEditCoverage) {
                    bannerReadOnly.innerHTML = `<b>🔒 Modo Só Leitura</b> — O perfil «<b>${userRole}</b>» apenas consulta a cobertura docente. O preenchimento é da responsabilidade exclusiva do <b>Coordenador de Curso</b>.`;
                } else {
                    bannerReadOnly.innerHTML = `<b>🔒 Plano ${planoData.estado}</b> — Este plano de cobertura já foi <b>${planoData.estado.toLowerCase()}</b> e encontra-se bloqueado para alterações pelo Coordenador de Curso.`;
                }
            } else {
                bannerReadOnly.style.display = 'none';
            }
        }

        const turmaLinhas = linhasData.filter(l => (l.turma_nome || `TURMA-${l.ano_curricular}A`) === selectedTurmaCod);

        if (!turmaLinhas || turmaLinhas.length === 0) {
            tbodyLinhas.innerHTML = `<tr><td colspan="12" style="text-align:center; padding:30px; color:var(--mut);">Nenhuma disciplina encontrada para a turma ${selectedTurmaCod}.</td></tr>`;
            return;
        }

        const isSem1 = s => {
            const str = String(s || '').trim().toUpperCase();
            return str === 'I' || str === '1' || str === '1.º' || str === '1º';
        };
        const isSem2 = s => {
            const str = String(s || '').trim().toUpperCase();
            return str === 'II' || str === '2' || str === '2.º' || str === '2º';
        };

        const sem1 = turmaLinhas.filter(l => isSem1(l.semestre));
        const sem2 = turmaLinhas.filter(l => isSem2(l.semestre));
        const outrosSem = turmaLinhas.filter(l => !isSem1(l.semestre) && !isSem2(l.semestre));

        let html = '';
        let rowCounter = 1;
        const baseDocentesOpts = docentesList.map(d => `<option value="${d.id}">${d.nome}</option>`).join('');

        const renderGroup = (linhasGroup, semTitle) => {
            if (linhasGroup.length === 0) return '';
            let out = `<tr class="semhd"><td colspan="14">${semTitle}</td></tr>`;

            linhasGroup.forEach(l => {
                const doc = docentesMap[l.docente_id] || (l.docente_nome ? docentesMap[l.docente_nome] : null);

                let opts = '<option value="">— escolher —</option>';
                if (l.docente_id) {
                    opts += baseDocentesOpts.replace(`value="${l.docente_id}"`, `value="${l.docente_id}" selected`);
                } else {
                    opts += baseDocentesOpts;
                }

                const sug2025 = l.sugestao_2025 || l.docente_anterior || '';
                const sugHtml = (sug2025 && !l.docente_id) 
                    ? `<div style="font-size:10.5px;color:var(--mut);margin-top:2px">2025/26: <a href="#" onclick="window.atribuirSugestao(${l.id}, '${sug2025.replace(/'/g, "\\'")}'); return false;" style="color:var(--blue2)">${sug2025}</a></div>` 
                    : '';

                const btnAiMatch = '';

                const auto = (val) => val ? `<span class="auto">${val}</span>` : `<span class="auto empty">—</span>`;

                const ncVal = doc ? (doc.num_cursos || doc.nc || 1) : (l.num_cursos || '—');
                const ncWarn = (typeof ncVal === 'number' && ncVal >= 3) ? ' style="color:var(--bad);font-weight:700;"' : '';

                const confSel = ['', 'Sim', 'Parcial', 'Não', 'Por verificar'].map(o => 
                    `<option value="${o}" ${o === (l.conformidade || '') ? 'selected' : ''}>${o || '—'}</option>`
                ).join('');

                const justSel = ['', 'Licenciatura', 'Mestrado', 'Doutoramento', 'Especializações', 'Experiência'].map(o => 
                    `<option value="${o}" ${o === (l.justificacao || '') ? 'selected' : ''}>${o || '—'}</option>`
                ).join('');

                const regSel = ['', 'Tempo Integral', 'Tempo Parcial', 'Colaborador'].map(o => 
                    `<option value="${o}" ${o === (l.regime || '') ? 'selected' : ''}>${o || '—'}</option>`
                ).join('');

                const parSel = ['', 'Manter', 'Manter c/ acompanhamento', 'Substituir', 'Recrutar'].map(o => 
                    `<option value="${o}" ${o === (l.parecer || 'Manter') ? 'selected' : ''}>${o || '—'}</option>`
                ).join('');

                const decVal = l.decisao_aprovacao || 'Aprovar';
                const decSelOptions = [
                    'Aprovar',
                    'Recusar',
                    'Solicitar substituição'
                ];
                const decSel = decSelOptions.map(o => 
                    `<option value="${o}" ${o === decVal ? 'selected' : ''}>${o}</option>`
                ).join('');

                const decColor = decVal === 'Aprovar' ? '#166534' : '#C0392B';

                const btnReplicar = (l.docente_id && !isLocked) 
                    ? `<button class="btn sm ghost" title="Atribuir a esta disciplina em todas as turmas do ano" onclick="window.applyAllTurmas(${l.plano_id}, ${l.disciplina_id}, ${l.docente_id})">↳ todas as turmas</button>` 
                    : '';

                out += `
                    <tr ${isLocked ? 'style="opacity:0.85;"' : ''}>
                        <td class="rownum">${rowCounter++}</td>
                        <td style="min-width:150px"><b>${l.disciplina_nome}</b></td>
                        <td class="docsel">
                            <div style="display:flex; align-items:center;">
                                <select ${disabledAttr} onchange="window.updateLinhaDocente(${l.id}, this.value)">
                                    ${opts}
                                </select>
                                ${btnAiMatch}
                            </div>
                            ${sugHtml}
                        </td>
                        <td>${auto(doc ? doc.grau_academico || doc.grau : l.grau_academico)}</td>
                        <td>${auto(doc ? doc.especialidade || doc.esp : l.especialidade)}</td>
                        <td>${auto(doc ? doc.tem_inaarees || doc.inaarees || doc.ina : l.tem_inaarees)}</td>
                        <td>${auto(doc ? doc.tem_agregacao_pedag || doc.capacitacao_pedagogica || doc.cap : l.tem_agregacao_pedag)}</td>
                        <td ${ncWarn}>${ncVal}</td>
                        <td style="min-width:100px"><select ${disabledAttr} onchange="window.updateLinhaField(${l.id}, 'conformidade', this.value)">${confSel}</select></td>
                        <td style="min-width:110px"><select ${disabledAttr} onchange="window.updateLinhaField(${l.id}, 'justificacao', this.value)">${justSel}</select></td>
                        <td style="min-width:105px"><select ${disabledAttr} onchange="window.updateLinhaField(${l.id}, 'regime', this.value)">${regSel}</select></td>
                        <td style="min-width:120px"><select ${disabledAttr} onchange="window.updateLinhaField(${l.id}, 'parecer', this.value)">${parSel}</select></td>
                        <td style="width:110px">${btnReplicar}</td>
                        <td style="min-width:165px">
                            <select ${disabledAttr} style="font-weight:700; color:${decColor}; background:${decVal==='Aprovar'?'#F0FDF4':'#FEF2F2'}; border:1px solid ${decVal==='Aprovar'?'#86EFAC':'#FCA5A5'}; border-radius:6px; padding:4px 8px; font-size:12px;" onchange="window.updateLinhaField(${l.id}, 'decisao_aprovacao', this.value, this)">
                                ${decSel}
                            </select>
                        </td>
                    </tr>
                `;
            });
            return out;
        };

        html += renderGroup(sem1, 'I.º / 1º Semestre');
        html += renderGroup(sem2, 'II.º / 2º Semestre');
        if (outrosSem.length > 0) {
            html += renderGroup(outrosSem, 'Disciplinas Anuais / Outros Regimes');
        }

        tbodyLinhas.innerHTML = html;
    }

    // Handlers Globais
    window.abrirModalMatchmaking = async (linhaId, disciplinaId, disciplinaNome) => {
        const modal = document.getElementById('modal-matchmaking');
        const target = document.getElementById('modal-matchmaking-target');
        const body = document.getElementById('modal-matchmaking-body');
        if (!modal || !body) return;

        target.textContent = `Recomendações para a disciplina: ${disciplinaNome}`;
        body.innerHTML = `<div style="text-align:center; padding:20px; color:var(--mut);">Calculando compatibilidade e disponibilidade de docentes...</div>`;
        modal.classList.remove('hidden');

        try {
            const res = await fetch(`index.php?api=sugerir_docentes&disciplina_id=${disciplinaId}`);
            const data = await res.json();
            if (data.success && data.data.length > 0) {
                body.innerHTML = `
                    <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                        <thead>
                            <tr style="background:#f4f2ec; text-align:left;">
                                <th style="padding:6px 8px;">Docente Recomendado</th>
                                <th style="padding:6px 8px;">Grau / INAAREES</th>
                                <th style="padding:6px 8px;">Compatibilidade</th>
                                <th style="padding:6px 8px;">Carga Semanal</th>
                                <th style="padding:6px 8px; text-align:right;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.data.map((item, idx) => `
                                <tr style="border-bottom:1px solid var(--line); ${idx===0 ? 'background:#f0f9f4;' : ''}">
                                    <td style="padding:8px;">
                                        <b>${item.docente_nome}</b>
                                        <div style="font-size:11px; color:var(--mut);">${item.especialidade}</div>
                                    </td>
                                    <td style="padding:8px;">
                                        ${item.grau_academico} 
                                        ${item.tem_inaarees === 'Sim' ? '<span class="pill ok" style="font-size:10px; padding:1px 5px;">INAAREES</span>' : ''}
                                    </td>
                                    <td style="padding:8px;">
                                        <div style="font-weight:700; color:var(--blue2);">${item.pontuacao_compatibilidade}%</div>
                                        <div style="font-size:10px; color:var(--mut);">${idx===0 ? '★ Melhor Opção' : 'Recomendado'}</div>
                                    </td>
                                    <td style="padding:8px;">
                                        ${item.soma_horas_semanais}h / sem
                                        <span class="pill ${item.estado_capacidade === 'Disponível' ? 'ok' : item.estado_capacidade === 'No Limite' ? 'warn' : 'bad'}" style="font-size:10px;">
                                            ${item.estado_capacidade}
                                        </span>
                                    </td>
                                    <td style="padding:8px; text-align:right;">
                                        <button class="btn sm btn-p" onclick="window.atribuirViaMatchmaking(${linhaId}, ${item.docente_id})">
                                            Atribuir
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } else {
                body.innerHTML = `<div style="text-align:center; padding:20px; color:var(--mut);">Nenhum docente disponível encontrado para recomendação.</div>`;
            }
        } catch (err) {
            body.innerHTML = `<div style="text-align:center; padding:20px; color:var(--bad);">Erro ao buscar sugestões inteligentes.</div>`;
        }
    };

    window.atribuirViaMatchmaking = async (linhaId, docenteId) => {
        const modal = document.getElementById('modal-matchmaking');
        if (modal) modal.classList.add('hidden');
        await window.updateLinhaDocente(linhaId, docenteId);
    };

    window.salvarPlanoLocal = () => {
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed; bottom:24px; right:24px; background:#0F2537; color:#fff; padding:14px 22px; border-radius:8px; font-weight:700; z-index:9999; box-shadow:0 6px 16px rgba(0,0,0,0.2); font-size:13.5px; border-left:4px solid #C9970A;';
        toast.innerHTML = `💾 Rascunho do Plano (Turma <b>${selectedTurmaCod || 'Geral'}</b>) sincronizado e seguro na base de dados!`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    };

    window.atribuirSugestao = (linhaId, docenteNome) => {
        const doc = docentesList.find(d => d.nome === docenteNome);
        if (doc) {
            window.updateLinhaDocente(linhaId, doc.id);
        } else {
            alert(`Docente ${docenteNome} não encontrado.`);
        }
    };

    window.updateLinhaDocente = async (linhaId, docenteId) => {
        try {
            const res = await fetch('index.php?api=linha_salvar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    linha_id: linhaId,
                    docente_id: docenteId || null,
                    propagate_sequential: true
                })
            });
            const data = await res.json();
            if (data.success) {
                // Actualizar apenas a linha em memória — sem recarregar toda a tabela
                const idx = linhasData.findIndex(l => l.id == linhaId);
                if (idx !== -1 && data.linha) {
                    linhasData[idx] = data.linha;
                } else if (idx !== -1) {
                    // Fallback: actualizar só o docente_id localmente
                    linhasData[idx].docente_id = docenteId ? parseInt(docenteId) : null;
                    linhasData[idx].docente_nome = docenteId ? (docentesMap[parseInt(docenteId)]?.nome || null) : null;
                }

                // Se houver par sequencial sincronizado, atualizar também em memória
                if (data.pair_linha) {
                    const pIdx = linhasData.findIndex(l => l.id == data.pair_linha.id || l.linha_id == data.pair_linha.id);
                    if (pIdx !== -1) {
                        linhasData[pIdx] = data.pair_linha;
                    }
                }

                // Re-renderizar a tabela SEM fetch ao servidor
                renderPlano();

                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed; bottom:24px; right:24px; background:#0F2537; color:#fff; padding:12px 20px; border-radius:8px; font-weight:700; z-index:9999; box-shadow:0 6px 16px rgba(0,0,0,0.2); font-size:13px; border-left:4px solid #1baf7a;';
                if (data.pair_nome) {
                    toast.innerHTML = `✅ Atribuição atualizada e <b>sincronizada com ${data.pair_nome}</b> (continuidade semestral)!`;
                } else {
                    toast.innerHTML = '✅ Atribuição docente atualizada com sucesso!';
                }
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3200);

                // Atualizar KPIs sem recarregar tudo
                updateKPIsAndHeader();
            } else {
                alert('Erro: ' + (data.error || 'Falha ao atualizar docente.'));
            }
        } catch (err) {
            alert('Erro ao atualizar docente da linha.');
        }
    };

    window.applyAllTurmas = async (planoId, disciplinaId, docenteId) => {
        try {
            const res = await fetch('index.php?api=linha_replicar_turmas', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ plano_id: planoId, disciplina_id: disciplinaId, docente_id: docenteId })
            });
            const data = await res.json();
            if (data.success) {
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed; bottom:24px; right:24px; background:#0F2537; color:#fff; padding:12px 20px; border-radius:8px; font-weight:700; z-index:9999; box-shadow:0 6px 16px rgba(0,0,0,0.2); font-size:13px; border-left:4px solid #1baf7a;';
                toast.innerHTML = `✅ ${data.message || 'Docente atribuído a todas as turmas do ano!'}`;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3500);

                await loadPlano(currentCursoId);
            } else {
                alert('Erro: ' + (data.error || data.message || 'Falha ao replicar atribuição por turmas.'));
            }
        } catch (err) {
            alert('Erro ao replicar atribuição por turmas.');
        }
    };

    window.updateLinhaField = async (linhaId, field, val, elem = null) => {
        try {
            const body = { linha_id: linhaId };
            body[field] = val;

            if (field === 'decisao_aprovacao' && ['Recusar', 'Solicitar substituição'].includes(val)) {
                const motivo = prompt(`Motivo / Justificação para [${val}] nesta disciplina (opcional):`);
                if (motivo !== null && motivo.trim() !== '') {
                    body['observacoes'] = motivo;
                }
            }

            const res = await fetch('index.php?api=linha_salvar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await res.json();

            if (data.success) {
                // Atualizar modelo em memória
                const idx = linhasData.findIndex(l => l.id == linhaId);
                if (idx !== -1) {
                    linhasData[idx][field] = val;
                    if (body['observacoes']) linhasData[idx]['observacoes'] = body['observacoes'];
                }

                // Atualizar estilo e cor do elemento dinamicamente
                if (elem && field === 'decisao_aprovacao') {
                    if (val === 'Aprovar') {
                        elem.style.color = '#166534';
                        elem.style.background = '#F0FDF4';
                        elem.style.borderColor = '#86EFAC';
                    } else {
                        elem.style.color = '#C0392B';
                        elem.style.background = '#FEF2F2';
                        elem.style.borderColor = '#FCA5A5';
                    }
                }

                if (window.showToast) {
                    const msg = field === 'decisao_aprovacao' ? `Decisão atualizada: ${val}` : 'Campo atualizado com sucesso';
                    window.showToast(data.message || msg, true);
                }
            } else {
                if (window.showToast) {
                    window.showToast(data.error || data.message || 'Falha ao atualizar campo.', false);
                } else {
                    alert('Erro: ' + (data.error || data.message || 'Falha ao atualizar campo.'));
                }
                // Reverter na interface (re-renderiza com os dados originais mantidos na memória)
                renderPlano();
            }
        } catch (err) {
            if (window.showToast) {
                window.showToast('Erro ao atualizar deliberação.', false);
            } else {
                alert('Erro ao atualizar deliberação.');
            }
            // Reverter em caso de erro de rede
            renderPlano();
        }
    };

    if (btnSubmeter) {
        btnSubmeter.addEventListener('click', async () => {
            if (!planoData) return;

            const semJustificacao = linhasData.filter(l => l.docente_id && l.conformidade !== 'Sim' && (!l.justificacao || l.justificacao === '—' || l.justificacao.trim() === ''));
            if (semJustificacao.length > 0) {
                alert(`Atenção: Existem ${semJustificacao.length} disciplina(s) com conformidade Parcial/Não sem justificativa. Preencha a justificação antes de submeter.`);
                return;
            }

            if (confirm('Deseja submeter este plano para a apreciação e aprovação do Chefe de Departamento?')) {
                const res = await fetch('index.php?api=plano_estado', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ plano_id: planoData.id, estado: 'Submetido' })
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message || 'Plano submetido com sucesso para o Chefe de Departamento!');
                    loadPlano(currentCursoId);
                } else {
                    alert('Erro: ' + (data.error || data.message || 'Falha ao submeter plano.'));
                }
            }
        });
    }

    const btnAprovarDepto = document.getElementById('btn-aprovar-depto');
    if (btnAprovarDepto) {
        btnAprovarDepto.addEventListener('click', async () => {
            if (!planoData) return;
            const obs = prompt('Como Chefe de Departamento, insira o seu parecer/observações para a Presidência (opcional):');
            if (obs !== null) {
                const res = await fetch('index.php?api=plano_estado', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ plano_id: planoData.id, estado: 'Aprovado pelo Departamento', observacoes: obs })
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message || 'Plano Aprovado pelo Chefe de Departamento!');
                    loadPlano(currentCursoId);
                } else {
                    alert('Erro: ' + (data.error || data.message || 'Falha ao aprovar plano.'));
                }
            }
        });
    }

    const btnRecusarDepto = document.getElementById('btn-recusar-depto');
    if (btnRecusarDepto) {
        btnRecusarDepto.addEventListener('click', async () => {
            if (!planoData) return;
            const obs = prompt('Como Chefe de Departamento, insira o motivo da recusa do plano:');
            if (obs !== null && obs.trim() !== '') {
                const res = await fetch('index.php?api=plano_estado', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ plano_id: planoData.id, estado: 'Devolvido', observacoes: obs })
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message || 'Plano Recusado pelo Chefe de Departamento.');
                    loadPlano(currentCursoId);
                } else {
                    alert('Erro: ' + (data.error || data.message || 'Falha ao recusar plano.'));
                }
            }
        });
    }

    const btnValidarPR = document.getElementById('btn-validar-pr');
    if (btnValidarPR) {
        btnValidarPR.addEventListener('click', async () => {
            if (!planoData) return;
            if (confirm('Como Presidência, deseja VALIDAR definitivamente este plano de cobertura docente?')) {
                const res = await fetch('index.php?api=plano_estado', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ plano_id: planoData.id, estado: 'Validado' })
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message || 'Plano Validado com sucesso pela Presidência!');
                    loadPlano(currentCursoId);
                } else {
                    alert('Erro: ' + (data.error || data.message || 'Falha ao validar plano.'));
                }
            }
        });
    }

    window.exportarPDFOficial = () => {
        const sel = document.getElementById('select-curso');
        const cId = currentCursoId || (sel ? sel.value : null);
        if (!cId) {
            alert('Por favor selecione um curso para gerar o relatório em PDF.');
            return;
        }
        window.open(`index.php?page=relatorio_plano&curso_id=${cId}&ano_lectivo=${encodeURIComponent(currentAnoLectivo)}`, '_blank');
    };

    window.exportarExcelOficial = () => {
        const sel = document.getElementById('select-curso');
        const cId = currentCursoId || (sel ? sel.value : null);
        if (!cId) {
            alert('Por favor selecione um curso para descarregar o ficheiro Excel/CSV.');
            return;
        }
        window.location.href = `index.php?api=exportar_excel&curso_id=${cId}&ano_lectivo=${encodeURIComponent(currentAnoLectivo)}`;
    };

    window.abrirModalHistorico = async (targetCursoId) => {
        const sel = document.getElementById('select-curso');
        const cId = targetCursoId || currentCursoId || (sel ? sel.value : null);
        const modal = document.getElementById('modal-historico');
        const body = document.getElementById('modal-historico-body');
        if (!modal || !body) return;

        body.innerHTML = `<div style="text-align:center; padding:20px; color:var(--mut);">Carregando histórico do plano...</div>`;
        modal.classList.remove('hidden');
        modal.style.display = 'flex';

        try {
            const res = await fetch(`index.php?api=plano_historico&curso_id=${cId}&ano_lectivo=${encodeURIComponent(currentAnoLectivo)}`);
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

    window.fecharModalHistorico = () => {
        const modal = document.getElementById('modal-historico');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    };
});
