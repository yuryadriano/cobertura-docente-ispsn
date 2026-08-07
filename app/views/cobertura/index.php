<?php
/**
 * View BackOffice: Cobertura Docente por Curso — Arquitetura Enterprise por Abas
 * sftcoordenacao — ISPSN 2026/27
 */
?>
<script>
window.CURRENT_USER_ROLE = "<?= Auth::user()['perfil'] ?? 'coordenador' ?>";
window.CURRENT_ANO_LECTIVO = "<?= get_ano_lectivo_activo() ?>";
</script>

<!-- CABEÇALHO SUPERIOR DA PÁGINA -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; flex-wrap:wrap; gap:12px;">
    <div>
        <h2 class="page" style="margin:0; display:flex; align-items:center; gap:10px;">
            📚 Cobertura Docente por Curso
            <span id="badge-estado" class="b b-ni" style="font-size:12px; padding:4px 12px; border-radius:12px; font-weight:700;">Rascunho</span>
        </h2>
        <div class="sub" style="margin:4px 0 0;">Gestão da Matriz Curricular: <b>Curso → Turma → Atribuição Docente</b> (Sincronizado com o Gestão Escolar).</div>
    </div>
    
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <button onclick="window.exportarPDFOficial()" class="btn ghost" style="font-weight:700; border-color:var(--blue); color:var(--blue); padding:6px 12px; font-size:12px;" title="Relatório Oficial em PDF com Assinaturas">📄 PDF Oficial</button>
        <button onclick="window.exportarExcelOficial()" class="btn ghost" style="font-weight:700; border-color:#1E8449; color:#1E8449; padding:6px 12px; font-size:12px;" title="Descarregar Ficheiro Excel/CSV Estruturado">📊 Excel (CSV)</button>
        <button id="btn-submeter" class="btn btn-p" style="display:none; font-size:12px; padding:6px 14px;">📤 Submeter ao Chefe de Depto</button>
        <button id="btn-aprovar-depto" class="btn btn-ok" style="display:none; font-size:12px; padding:6px 14px; background:#1E8449;">✅ Aprovar (Chefe de Depto)</button>
        <button id="btn-recusar-depto" class="btn btn-bad" style="display:none; font-size:12px; padding:6px 14px; background:#C0392B;">❌ Recusar (Chefe de Depto)</button>
        <button id="btn-validar-pr" class="btn btn-ok" style="display:none; font-size:12px; padding:6px 14px; background:#1F4E79;">🛡️ Validar Plano (Presidência)</button>
    </div>
</div>

<!-- Banner de Alerta para Planos Devolvidos -->
<div id="banner-devolucao" style="display:none; opacity:1; transition: opacity 0.6s ease; background:#FBEAE8; border:1.5px solid #F5C6CB; border-radius:10px; padding:14px 18px; margin-bottom:18px; color:#C0392B;">
    <div style="font-weight:800; font-size:14px; display:flex; align-items:center; gap:8px;">
        <span>↩️</span> Plano Devolvido pela Presidência para Retificação
    </div>
    <div id="banner-devolucao-obs" style="font-size:12.5px; margin-top:4px; color:#721C24; font-style:italic;"></div>
</div>

<!-- Banner de Alerta para Modo Só Leitura / Estado do Plano -->
<div id="banner-readonly" style="display:none; background:#F4F2EC; border:1.5px solid #CDCBC4; border-radius:10px; padding:12px 16px; margin-bottom:18px; color:#4A4843; font-size:13px;">
</div>

<!-- BARRA DE SELEÇÃO E CONTROLO DE CURSO E TURMA -->
<div class="ctrls" style="display:flex; gap:16px; align-items:center; flex-wrap:wrap; margin-bottom:20px; background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px 20px; box-shadow:0 2px 6px rgba(0,0,0,0.02);">
    <div>
        <label style="font-size:11.5px; color:var(--mut); font-weight:700; text-transform:uppercase; display:block; margin-bottom:4px; letter-spacing:0.5px;">Curso Académico</label>
        <select id="select-curso" style="min-width:240px; font-weight:700; padding:8px 12px; border-radius:8px; border:1px solid #cdcbc4; background:#fcfbf9; font-size:13.5px;">
            <option value="">Carregando cursos...</option>
        </select>
    </div>

    <div>
        <label style="font-size:11.5px; color:var(--mut); font-weight:700; text-transform:uppercase; display:block; margin-bottom:4px; letter-spacing:0.5px;">Turma Ativa</label>
        <select id="select-turma" style="min-width:220px; font-weight:700; padding:8px 12px; border-radius:8px; border:1px solid #cdcbc4; background:#fcfbf9; font-size:13.5px;">
            <option value="">-- Selecionar Turma --</option>
        </select>
    </div>

    <div>
        <label style="font-size:11.5px; color:transparent; display:block; margin-bottom:4px;">&nbsp;</label>
        <span id="badge-turma-info" class="pill mut" style="font-size:12px; padding:7px 14px; font-weight:700; border-radius:8px;">1.º Ano · Manhã</span>
    </div>

    <div style="flex:1;"></div>

    <div style="text-align:right; display:flex; align-items:center; gap:10px;">
        <div>
            <span id="pill-conf-pct" class="pill ok" style="font-size:12px; padding:6px 12px; font-weight:700;">Conf. 0%</span>
            <span id="pill-atrib-stat" class="pill mut" style="font-size:12px; padding:6px 12px; font-weight:600;">0/0 atribuições</span>
        </div>
        <button class="btn sm gold" style="padding:8px 16px; font-weight:700;" onclick="window.salvarPlanoLocal()">💾 Guardar Rascunho</button>
    </div>
</div>

<!-- NAVEGAÇÃO POR ABAS ENTERPRISE DO MÓDULO -->
<div style="display:flex; border-bottom:2px solid var(--line); margin-bottom:20px; gap:8px; flex-wrap:wrap;">
    <button id="tab-cob-matriz" class="tab-cob-btn active" onclick="window.switchCoberturaTab('matriz')" style="padding:10px 18px; font-weight:700; font-size:13.5px; border:none; background:none; border-bottom:3px solid var(--blue); color:var(--blue); cursor:pointer; display:flex; align-items:center; gap:8px;">
        📋 Matriz de Cobertura (Por Turma)
    </button>
    <button id="tab-cob-risco" class="tab-cob-btn" onclick="window.switchCoberturaTab('risco')" style="padding:10px 18px; font-weight:600; font-size:13.5px; border:none; background:none; border-bottom:3px solid transparent; color:var(--mut); cursor:pointer; display:flex; align-items:center; gap:8px;">
        ⚡ Diagnóstico de Risco Académico &amp; Alertas
    </button>
</div>

<!-- ==================================================================== -->
<!-- ABA 1: MATRIZ DE COBERTURA (TABELA PRINCIPAL) -->
<!-- ==================================================================== -->
<div id="tab-cob-content-matriz" style="display:block;">
    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; overflow:hidden; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
        <div class="hd" id="card-turma-header" style="font-size:14px; font-weight:700; padding:14px 20px; border-bottom:1px solid var(--line); background:#faf9f5; color:var(--blue); display:flex; justify-content:space-between; align-items:center;">
            <span>Disciplinas e Atribuições Docentes da Turma</span>
        </div>
        <div class="bd" style="padding:0; overflow-x:auto;">
            <table class="plan" style="width:100%; border-collapse:collapse; font-size:12.5px;">
                <thead>
                    <tr style="background:#f4f2ec; border-bottom:1px solid var(--line); text-align:left;">
                        <th style="padding:10px 8px; font-size:11px; width:30px;">#</th>
                        <th style="padding:10px 8px; font-size:11px; min-width:170px;">Unidade Curricular</th>
                        <th style="padding:10px 8px; font-size:11px; min-width:200px;">Docente da turma</th>
                        <th style="padding:10px 8px; font-size:11px;">Grau (auto)</th>
                        <th style="padding:10px 8px; font-size:11px;">Especialidade (auto)</th>
                        <th style="padding:10px 8px; font-size:11px;">INAAREES</th>
                        <th style="padding:10px 8px; font-size:11px;">Cap. pedag.</th>
                        <th style="padding:10px 8px; font-size:11px;">N.º cursos</th>
                        <th style="padding:10px 8px; font-size:11px; min-width:110px;">Conformidade</th>
                        <th style="padding:10px 8px; font-size:11px; min-width:130px;">Justificação</th>
                        <th style="padding:10px 8px; font-size:11px; min-width:110px;">Regime</th>
                        <th style="padding:10px 8px; font-size:11px;">Parecer</th>
                        <th style="padding:10px 8px; font-size:11px; width:110px;">Replicar</th>
                        <th style="padding:10px 8px; font-size:11px; min-width:160px;">Aprovação</th>
                    </tr>
                </thead>
                <tbody id="tbody-linhas">
                    <tr>
                        <td colspan="13" style="text-align:center; padding:30px; color:var(--mut);">Carregando cobertura docente...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="note" style="background:#f0f5fb; border-left:4px solid var(--blue); padding:12px 16px; border-radius:0 8px 8px 0; font-size:12.5px; color:#333; margin-top:14px;">
        💡 <b>Dica Operacional:</b> Cada turma possui as suas disciplinas específicas do ano curricular. Ao selecionar o docente para uma disciplina, pode utilizar o botão <b>«↳ todas as turmas»</b> para copiar automaticamente a atribuição para as restantes turmas do mesmo ano.
    </div>
</div>

<!-- ==================================================================== -->
<!-- ABA 2: DIAGNÓSTICO DE RISCO ACADÉMICO -->
<!-- ==================================================================== -->
<div id="tab-cob-content-risco" style="display:none;">
    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:22px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
        <h3 style="color:var(--blue); margin-top:0; margin-bottom:12px; font-size:16px;">
            ⚡ Diagnóstico de Risco Académico e Conformidade Legal
        </h3>
        <p style="font-size:13px; color:var(--mut); margin-bottom:18px; line-height:1.5;">
            Análise automatizada de pendências, disciplinas sem docente atribuído, sobrecarga de carga horária semanal e conformidade pedagógica INAAREES.
        </p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
            <div style="background:#FAF5FF; border:1px solid #E9D5FF; border-radius:10px; padding:16px;">
                <div style="font-weight:700; color:#6B21A8; font-size:13px; margin-bottom:6px;">🤖 Sugestão Inteligente (AI Matchmaking)</div>
                <p style="font-size:12px; color:var(--ink); margin-bottom:12px;">Encontre o docente ideal com base no grau académico, especialidade e disponibilidade de horas.</p>
                <button class="btn sm ghost" style="border-color:#9333EA; color:#9333EA; font-weight:700; width:100%;" onclick="alert('Selecione um docente na tabela para acionar a sugestão automática.')">Testar AI Matchmaking</button>
            </div>

            <div style="background:#FEF2F2; border:1px solid #FCA5A5; border-radius:10px; padding:16px;">
                <div style="font-weight:700; color:#991B1B; font-size:13px; margin-bottom:6px;">⚠️ Verificação de Sobrecarga Docente</div>
                <p style="font-size:12px; color:var(--ink); margin-bottom:12px;">Docentes lecionando em mais de 3 cursos ou com carga superior a 20 horas semanais.</p>
                <span class="pill warn" style="font-weight:700;">Monitorização Ativa</span>
            </div>
        </div>
    </div>
</div>

<!-- MODAL AI MATCHMAKING (SUGESTÃO INTELIGENTE) -->
<div id="modal-matchmaking" class="hidden" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:999; display:flex; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; max-width:650px; width:90%; padding:20px 24px; box-shadow:0 8px 30px rgba(0,0,0,0.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:12px; margin-bottom:16px;">
            <h3 style="margin:0; color:var(--blue); font-size:17px; display:flex; align-items:center; gap:8px;">
                <span>💡</span> Sugestão Inteligente de Docente (AI Matchmaking)
            </h3>
            <button onclick="document.getElementById('modal-matchmaking').classList.add('hidden')" style="background:none; border:none; font-size:18px; font-weight:700; cursor:pointer;">✕</button>
        </div>
        <div id="modal-matchmaking-target" style="font-size:13px; color:var(--mut); margin-bottom:14px; font-weight:600;"></div>
        <div id="modal-matchmaking-body" style="max-height:360px; overflow-y:auto; margin-bottom:16px;">
            <div style="text-align:center; padding:20px; color:var(--mut);">Analisando perfis docentes, grau e disponibilidade de carga horária...</div>
        </div>
        <div style="text-align:right;">
            <button onclick="document.getElementById('modal-matchmaking').classList.add('hidden')" class="btn">Fechar</button>
        </div>
    </div>
</div>

<!-- MODAL VISUAL DE HISTÓRICO DE AUDITORIA & LINHA DO TEMPO -->
<div id="modal-historico" class="hidden" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.55); z-index:999; display:flex; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; max-width:680px; width:90%; padding:22px 26px; box-shadow:0 8px 30px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:12px; margin-bottom:16px;">
            <h3 style="margin:0; color:var(--blue); font-size:17px; display:flex; align-items:center; gap:8px;">
                <span>📜</span> Linha do Tempo e Histórico de Auditoria
            </h3>
            <button onclick="window.fecharModalHistorico()" style="background:none; border:none; font-size:18px; font-weight:700; cursor:pointer;">✕</button>
        </div>
        <div id="modal-historico-body" style="max-height:400px; overflow-y:auto; padding-right:6px;">
            <div style="text-align:center; padding:20px; color:var(--mut);">Carregando histórico de submissões e homologações...</div>
        </div>
        <div style="text-align:right; border-top:1px solid var(--line); padding-top:14px; margin-top:16px;">
            <button onclick="window.fecharModalHistorico()" class="btn">Fechar</button>
        </div>
    </div>
</div>

<script>
window.switchCoberturaTab = (tabKey) => {
    const paneMatriz = document.getElementById('tab-cob-content-matriz');
    const paneRisco  = document.getElementById('tab-cob-content-risco');
    const btnMatriz  = document.getElementById('tab-cob-matriz');
    const btnRisco   = document.getElementById('tab-cob-risco');

    if (tabKey === 'matriz') {
        if (paneMatriz) paneMatriz.style.display = 'block';
        if (paneRisco)  paneRisco.style.display  = 'none';
        if (btnMatriz) { btnMatriz.style.borderBottomColor = 'var(--blue)'; btnMatriz.style.color = 'var(--blue)'; btnMatriz.style.fontWeight = '700'; }
        if (btnRisco)  { btnRisco.style.borderBottomColor = 'transparent'; btnRisco.style.color = 'var(--mut)'; btnRisco.style.fontWeight = '600'; }
    } else {
        if (paneMatriz) paneMatriz.style.display = 'none';
        if (paneRisco)  paneRisco.style.display  = 'block';
        if (btnRisco)   { btnRisco.style.borderBottomColor = 'var(--blue)'; btnRisco.style.color = 'var(--blue)'; btnRisco.style.fontWeight = '700'; }
        if (btnMatriz)  { btnMatriz.style.borderBottomColor = 'transparent'; btnMatriz.style.color = 'var(--mut)'; btnMatriz.style.fontWeight = '600'; }
    }
};
</script>

