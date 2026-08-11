<?php
/**
 * View: Administração & Configuração do Sistema — Arquitetura por Abas Enterprise
 * sftcoordenacao — ISPSN 2026/27
 */
$currentUser = Auth::user();
$isAdmin = ($currentUser['perfil'] ?? '') === 'admin';
$anoActivo = defined('ANO_LECTIVO_ACTIVO') ? ANO_LECTIVO_ACTIVO : '2026/27';
?>

<!-- CABEÇALHO DA PÁGINA -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
    <div>
        <h2 class="page" style="margin:0; display:flex; align-items:center; gap:10px;">
            ⚙️ Administração &amp; Configurações
            <span style="background:var(--blue); color:#fff; border-radius:6px; padding:3px 10px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Painel de Controlo</span>
        </h2>
        <div class="sub" style="margin:4px 0 0;">Gestão centralizada de utilizadores, perfis RBAC, ciclo lectivo e integração com o Gestão Escolar.</div>
    </div>
    <div>
        <span class="b b-sim" style="font-size:12px; padding:6px 14px; border-radius:20px; font-weight:600;">Engine MySQL / RBAC Ativo</span>
    </div>
</div>

<!-- BANNER DE KPIS EXECUTIVOS -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px;">
    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px; border-left:4px solid var(--blue);">
        <div style="font-size:12px; font-weight:700; color:var(--mut); text-transform:uppercase; letter-spacing:0.5px;">Utilizadores Cadastrados</div>
        <div style="font-size:26px; font-weight:800; color:var(--blue); margin-top:4px;"><?= count($utilizadores) ?></div>
        <div style="font-size:11.5px; color:var(--ink); margin-top:2px;">Contas de acesso ao portal</div>
    </div>

    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px; border-left:4px solid var(--gold);">
        <div style="font-size:12px; font-weight:700; color:var(--mut); text-transform:uppercase; letter-spacing:0.5px;">Ano Lectivo em Curso</div>
        <div style="font-size:26px; font-weight:800; color:var(--gold-d); margin-top:4px;"><?= htmlspecialchars($anoActivo) ?></div>
        <div style="font-size:11.5px; color:var(--ink); margin-top:2px;">Ano ativo para lançamentos</div>
    </div>

    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px; border-left:4px solid #10b981;">
        <div style="font-size:12px; font-weight:700; color:var(--mut); text-transform:uppercase; letter-spacing:0.5px;">Cursos Sincronizados</div>
        <div style="font-size:26px; font-weight:800; color:#047857; margin-top:4px;"><?= count($cursos) ?></div>
        <div style="font-size:11.5px; color:var(--ink); margin-top:2px;">Integração Gestão Escolar</div>
    </div>

    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px; border-left:4px solid #6366f1;">
        <div style="font-size:12px; font-weight:700; color:var(--mut); text-transform:uppercase; letter-spacing:0.5px;">Perfis de Acesso (RBAC)</div>
        <div style="font-size:26px; font-weight:800; color:#4338ca; margin-top:4px;">6 Perfis</div>
        <div style="font-size:11.5px; color:var(--ink); margin-top:2px;">Níveis de autorização definidos</div>
    </div>
</div>

<!-- NAVEGAÇÃO POR ABAS ENTERPRISE -->
<div style="display:flex; border-bottom:2px solid var(--line); margin-bottom:24px; gap:8px; flex-wrap:wrap;">
    <button id="tab-btn-users" class="tab-nav-btn active" onclick="window.switchConfigTab('users')" style="padding:10px 18px; font-weight:700; font-size:13.5px; border:none; background:none; border-bottom:3px solid var(--blue); color:var(--blue); cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s;">
        👤 Utilizadores &amp; Perfis (RBAC)
    </button>
    <button id="tab-btn-anos" class="tab-nav-btn" onclick="window.switchConfigTab('anos')" style="padding:10px 18px; font-weight:600; font-size:13.5px; border:none; background:none; border-bottom:3px solid transparent; color:var(--mut); cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s;">
        📅 Anos Lectivos &amp; Roll-Over
    </button>
    <button id="tab-btn-curriculo" class="tab-nav-btn" onclick="window.switchConfigTab('curriculo')" style="padding:10px 18px; font-weight:600; font-size:13.5px; border:none; background:none; border-bottom:3px solid transparent; color:var(--mut); cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s;">
        📚 API &amp; Gestão Escolar
    </button>
    <button id="tab-btn-listas" class="tab-nav-btn" onclick="window.switchConfigTab('listas')" style="padding:10px 18px; font-weight:600; font-size:13.5px; border:none; background:none; border-bottom:3px solid transparent; color:var(--mut); cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s;">
        📋 Dicionários &amp; Regras
    </button>
</div>

<!-- ==================================================================== -->
<!-- ABA 1: UTILIZADORES E PERFIS (RBAC) -->
<!-- ==================================================================== -->
<div id="tab-content-users" class="tab-pane" style="display:block;">
    <div style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:22px; margin-bottom:24px; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid var(--line); padding-bottom:14px; flex-wrap:wrap; gap:12px;">
            <div>
                <h3 style="color:var(--blue); margin:0; font-size:16px; display:flex; align-items:center; gap:8px;">
                    👤 Gestão de Utilizadores e Matriz de Perfis (RBAC)
                </h3>
                <div style="font-size:12px; color:var(--mut); margin-top:2px;">Atribuição de papéis funcionais e permissões por curso ou âmbito global</div>
            </div>
            <?php if ($isAdmin): ?>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button class="btn sm ghost" style="border-color:var(--blue); color:var(--blue); font-weight:700;" onclick="window.abrirModalPermissoes()">🛡️ Matriz de Permissões (RBAC)</button>
                    <button class="btn sm btn-ok" style="background:#1e8449; color:#fff; font-weight:700;" onclick="window.toggleFormAtivarDocente()">🎓 Ativar Perfil de Docente</button>
                    <button class="btn sm gold" onclick="window.toggleFormImportarExcel()">📥 Importar Lista (CSV)</button>
                    <button class="btn sm btn-p" onclick="window.toggleFormNovoUser()">+ Novo Utilizador</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- FORMULÁRIO DE ATIVAÇÃO DE PERFIL DE DOCENTE DA INSTITUIÇÃO -->
        <div id="box-ativar-docente" style="display:none; background:#F0FDF4; border:1.5px solid #86EFAC; border-radius:10px; padding:18px; margin-bottom:20px;">
            <h4 style="margin:0 0 8px; color:#166534; font-size:14px; display:flex; align-items:center; gap:8px;">
                🎓 Ativar Perfil &amp; Acesso para CORPO DOCENTE (Professores de Curso)
            </h4>
            <p style="font-size:12.5px; color:#15803D; margin-bottom:14px; line-height:1.5;">
                <b>Utilize esta opção exclusivamente para professores lecionantes cadastrados no catálogo docente.</b> Selecione o docente da lista para lhe conceder acesso ao sistema com o e-mail corporativo.
            </p>
            <form onsubmit="window.ativarPerfilDocente(event)">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-bottom:14px;">
                    <div>
                        <label style="font-weight:700; font-size:12px; color:#166534;">Selecionar Docente:</label>
                        <select id="ad-docente" required class="form-control" style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #86EFAC; font-weight:600;" onchange="window.preencherEmailDocente(this.value)">
                            <option value="">-- Selecionar Docente --</option>
                            <?php foreach ($docentes as $d): ?>
                                <option value="<?= $d['id'] ?>" data-nome="<?= htmlspecialchars($d['nome']) ?>" data-email="<?= htmlspecialchars($d['email'] ?? '') ?>"><?= htmlspecialchars($d['nome']) ?> (<?= htmlspecialchars($d['grau_academico'] ?? 'Docente') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:700; font-size:12px; color:#166534;">E-mail Corporativo:</label>
                        <input type="email" id="ad-email" required placeholder="ex: nome.sobrenome@ispsn.org" class="form-control" style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #86EFAC; font-weight:600;">
                    </div>
                    <div>
                        <label style="font-weight:700; font-size:12px; color:#166534;">Perfil de Acesso (RBAC):</label>
                        <select id="ad-perfil" required class="form-control" style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #86EFAC; font-weight:700;" onchange="window.toggleCursoDocenteField(this.value)">
                            <option value="coordenador">Coordenador de Curso</option>
                            <option value="chefe_departamento">Chefe de Departamento</option>
                            <option value="secretario_geral">Secretário-Geral</option>
                            <option value="presidente">Presidência</option>
                            <option value="gestor_academico">Gestão Académica</option>
                            <option value="grh">GRH (Recursos Humanos)</option>
                            <option value="admin">Administração</option>
                        </select>
                    </div>
                    <div id="wrap-ad-curso" style="display:block;">
                        <label style="font-weight:700; font-size:12px; color:#166534;">Curso Atribuído (se Coordenador):</label>
                        <select id="ad-curso" class="form-control" style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #86EFAC; font-weight:600;">
                            <option value="">-- Todos os Cursos --</option>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="wrap-ad-depto" style="display:none;">
                        <label style="font-weight:700; font-size:12px; color:#166534;">Departamento Atribuído (se Chefe de Depto):</label>
                        <select id="ad-depto" class="form-control" style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #86EFAC; font-weight:700;">
                            <option value="">-- Selecionar Departamento --</option>
                            <option value="1">Departamento de Ciências Sociais e Humanas</option>
                            <option value="2">Departamento dos Assuntos Académicos</option>
                            <option value="3">Departamento de Ciências da Saúde</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="btn sm ghost" onclick="window.toggleFormAtivarDocente()">Cancelar</button>
                    <button type="submit" class="btn sm btn-ok" style="background:#166534; color:#fff; font-weight:700;">⚡ Ativar Perfil &amp; Conceder Acesso</button>
                </div>
            </form>
        </div>

        <!-- FORMULÁRIO DE IMPORTAÇÃO DE UTILIZADORES VIA EXCEL/CSV -->
        <div id="box-importar-excel" style="display:none; background:#FFF9E6; border:1px solid var(--gold); border-radius:10px; padding:18px; margin-bottom:20px;">
            <h4 style="margin:0 0 8px; color:var(--gold-d); font-size:14px; display:flex; align-items:center; gap:6px;">
                📥 Importação em Lote de Utilizadores (Excel / CSV)
            </h4>
            <p style="font-size:12.5px; color:var(--mut); margin-bottom:14px; line-height:1.5;">
                Carregue um ficheiro contendo os funcionários/coordenadores e seus e-mails corporativos (<code>@ispsn.org</code>). Os utilizadores serão criados com estado pendente de <b>Primeiro Acesso</b>.
            </p>
            <form onsubmit="window.importarUtilizadoresCSV(event)">
                <div style="margin-bottom:14px;">
                    <label style="font-weight:600; font-size:12px; display:block; margin-bottom:4px;">Selecione o Ficheiro (.csv ou .txt):</label>
                    <input type="file" id="file-csv-users" accept=".csv, .txt" required style="background:#FFF; padding:8px; border:1px solid var(--line); border-radius:6px; width:100%;">
                </div>
                <div style="font-size:11.5px; color:var(--ink); margin-bottom:14px; background:#FFF; padding:10px 12px; border-radius:6px; border:1px dashed #D49E00; line-height:1.5;">
                    <b>Formato esperado das colunas (separado por vírgulas):</b><br>
                    <code>nome, email, perfil, curso_codigo</code><br>
                    <i>Exemplo:</i> <code>Evaristo Adriano, evaristo.adriano@ispsn.org, coordenador, DIR</code>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="btn sm ghost" onclick="window.toggleFormImportarExcel()">Cancelar</button>
                    <button type="submit" class="btn sm gold">Processar &amp; Importar Lista</button>
                </div>
            </form>
        </div>

        <!-- FORMULÁRIO RÁPIDO DE NOVO UTILIZADOR INSTITUCIONAL -->
        <div id="box-novo-utilizador" style="display:none; background:#f0f5fb; border:1px solid #cbd5e1; border-radius:10px; padding:18px; margin-bottom:20px;">
            <h4 style="margin:0 0 8px; color:var(--blue); font-size:14px; display:flex; align-items:center; gap:8px;">
                🔑 Cadastrar Novo Utilizador de Sistema (Funcionários Institucionais &amp; Perfis Globais: GRH, Sec. Geral, Presidência, Admin)
            </h4>
            <p style="font-size:12.5px; color:var(--mut); margin-bottom:14px; line-height:1.5;">
                <b>Utilize esta opção para cadastrar novos funcionários administrativos e contas corporativas sem vínculo a um curso específico.</b> O utilizador será pré-cadastrado com estado de Primeiro Acesso.
            </p>
            <form onsubmit="window.criarUtilizador(event)">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:14px;">
                    <div>
                        <label style="font-weight:600; font-size:12px;">Nome Completo:</label>
                        <input type="text" id="nu-nome" required placeholder="Ex.: Prof. Carlos Viana" class="form-control" style="width:100%; padding:6px 10px; border-radius:6px; border:1px solid var(--line);">
                    </div>
                    <div>
                        <label style="font-weight:600; font-size:12px;">Email Corporativo:</label>
                        <input type="email" id="nu-email" required placeholder="carlos.viana@ispsn.org" class="form-control" style="width:100%; padding:6px 10px; border-radius:6px; border:1px solid var(--line);">
                    </div>
                    <div>
                        <label style="font-weight:600; font-size:12px;">Perfil de Acesso:</label>
                        <select id="nu-perfil" required class="form-control" style="width:100%; padding:6px 10px; border-radius:6px; border:1px solid var(--line);">
                            <option value="coordenador">Coordenador de Curso</option>
                            <option value="chefe_departamento">Chefe de Departamento</option>
                            <option value="gestor_academico">Gestão Académica</option>
                            <option value="grh">GRH</option>
                            <option value="presidente">Presidente</option>
                            <option value="secretario_geral">Secretário-Geral</option>
                            <option value="admin">Administração</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:600; font-size:12px;">Curso Atribuído (se Coordenador):</label>
                        <select id="nu-curso" class="form-control" style="width:100%; padding:6px 10px; border-radius:6px; border:1px solid var(--line);">
                            <option value="">-- Todos os Cursos --</option>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="btn sm ghost" onclick="window.toggleFormNovoUser()">Cancelar</button>
                    <button type="submit" class="btn sm btn-ok">Confirmar Pré-Registo</button>
                </div>
            </form>
        </div>

        <!-- TABELA DE UTILIZADORES -->
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Nome do Utilizador</th>
                        <th>Email Corporativo</th>
                        <th style="width:200px;">Perfil Atribuído (RBAC)</th>
                        <th style="width:220px;">Curso (Âmbito)</th>
                        <th style="width:90px;">Estado</th>
                        <?php if ($isAdmin): ?>
                            <th style="width:110px; text-align:center;">Ação</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilizadores as $u): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['nome']) ?></strong></td>
                        <td><span style="font-size:12.5px; color:var(--mut);"><?= htmlspecialchars($u['email']) ?></span></td>
                        <td>
                            <select id="usr-perfil-<?= $u['id'] ?>" style="padding:4px 8px; border-radius:6px; font-weight:600; font-size:12.5px; border:1px solid var(--line); width:100%;" <?= !$isAdmin ? 'disabled' : '' ?>>
                                <option value="coordenador" <?= $u['perfil'] === 'coordenador' ? 'selected' : '' ?>>Coordenador de Curso</option>
                                <option value="chefe_departamento" <?= $u['perfil'] === 'chefe_departamento' ? 'selected' : '' ?>>Chefe de Departamento</option>
                                <option value="gestor_academico" <?= $u['perfil'] === 'gestor_academico' ? 'selected' : '' ?>>Gestão Académica</option>
                                <option value="grh" <?= $u['perfil'] === 'grh' ? 'selected' : '' ?>>GRH</option>
                                <option value="presidente" <?= $u['perfil'] === 'presidente' ? 'selected' : '' ?>>Presidente</option>
                                <option value="secretario_geral" <?= $u['perfil'] === 'secretario_geral' ? 'selected' : '' ?>>Secretário-Geral</option>
                                <option value="admin" <?= $u['perfil'] === 'admin' ? 'selected' : '' ?>>Administração</option>
                            </select>
                        </td>
                        <td>
                            <select id="usr-curso-<?= $u['id'] ?>" style="padding:4px 8px; border-radius:6px; font-size:12px; border:1px solid var(--line); width:100%;" <?= !$isAdmin ? 'disabled' : '' ?>>
                                <option value="">-- Todos os Cursos --</option>
                                <?php foreach ($cursos as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (int)$u['curso_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select id="usr-activo-<?= $u['id'] ?>" style="padding:4px 8px; border-radius:6px; font-weight:700; font-size:12px; border:1px solid var(--line); width:100%; color:<?= $u['activo'] ? '#047857' : '#b91c1c' ?>; background:<?= $u['activo'] ? '#ecfdf5' : '#fef2f2' ?>;" <?= !$isAdmin ? 'disabled' : '' ?> onchange="this.style.color = this.value == '1' ? '#047857' : '#b91c1c'; this.style.background = this.value == '1' ? '#ecfdf5' : '#fef2f2';">
                                <option value="1" <?= $u['activo'] ? 'selected' : '' ?>>✅ Ativo</option>
                                <option value="0" <?= !$u['activo'] ? 'selected' : '' ?>>❌ Inativo</option>
                            </select>
                        </td>
                        <?php if ($isAdmin): ?>
                            <td style="text-align:center;">
                                <div style="display:flex; gap:4px; justify-content:center;">
                                    <button class="btn sm btn-ok" style="font-size:11px; padding:4px 8px;" onclick="window.salvarUser(<?= $u['id'] ?>)" title="Guardar Alterações do Utilizador">Guardar</button>
                                    <button class="btn sm ghost" style="font-size:11px; padding:4px 8px; border-color:var(--line);" onclick="window.alternarEstadoUser(<?= $u['id'] ?>, <?= $u['activo'] ? 0 : 1 ?>)" title="Alternar Estado Ativo/Inativo">⚡</button>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==================================================================== -->
<!-- ABA 2: ANOS LECTIVOS & ROLL-OVER -->
<!-- ==================================================================== -->
<div id="tab-content-anos" class="tab-pane" style="display:none;">
    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:22px; margin-bottom:24px; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
        <h3 style="color:var(--blue); margin-top:0; margin-bottom:14px; font-size:16px; display:flex; align-items:center; gap:8px;">
            📅 Controlo de Ciclo Lectivo &amp; Transição Institucional (Roll-Over)
        </h3>
        <p style="font-size:13px; color:var(--ink); line-height:1.6; margin-bottom:20px;">
            Defina o ano lectivo ativo para a edição e submissão dos Planos de Cobertura Docente. O processo de <b>Roll-Over</b> permite transitar e replicar atribuições docentes para o próximo ano lectivo de forma automatizada.
        </p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:20px; align-items:start;">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <label style="font-weight:700; font-size:13px; color:var(--blue); display:block; margin-bottom:8px;">Ano Lectivo Em Curso:</label>
                <?php $anoActivoConfig = get_ano_lectivo_activo(); ?>
                <select id="cfg-ano-sel" style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--line); font-weight:700; font-size:14px; background:#fff;" onchange="window.salvarAnoLectivo(this.value)">
                    <option value="2025/26" <?= $anoActivoConfig === '2025/26' ? 'selected' : '' ?>>2025/2026 (Histórico · Só Leitura)</option>
                    <option value="2026/27" <?= $anoActivoConfig === '2026/27' ? 'selected' : '' ?>>2026/2027 (Ano Ativo em Curso)</option>
                    <option value="2027/28" <?= $anoActivoConfig === '2027/28' ? 'selected' : '' ?>>2027/2028 (Planeamento Futuro)</option>
                </select>
                <div style="font-size:11.5px; color:var(--mut); margin-top:8px;">Altera o contexto global de pré-carregamento dos planos de cobertura.</div>
            </div>

            <div style="background:#fff9e6; border:1px solid #fcd34d; border-radius:10px; padding:18px;">
                <h4 style="margin:0 0 8px; color:#b45309; font-size:14px;">⚡ Transição Automática de Atribuições</h4>
                <p style="font-size:12px; color:var(--ink); margin-bottom:14px; line-height:1.5;">
                    Permite copiar a estrutura das turmas e docentes do ano ativo (2026/27) para o planeamento de 2027/28.
                </p>
                <button class="btn sm gold" style="width:100%; padding:8px 14px; font-weight:700;" onclick="window.executarRollOver()">⚡ Executar Transição de Atribuições (Roll-Over)</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================================================================== -->
<!-- ABA 3: CURRÍCULO & GESTÃO ESCOLAR -->
<!-- ==================================================================== -->
<div id="tab-content-curriculo" class="tab-pane" style="display:none;">
    <div class="card" style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:22px; margin-bottom:24px; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
            <div>
                <h3 style="color:var(--blue); margin:0; font-size:16px; display:flex; align-items:center; gap:8px;">
                    📚 Integração por API com o Sistema Gestão Escolar
                </h3>
                <div style="font-size:12px; color:var(--mut); margin-top:2px;">Sincronização de cursos, disciplinas e turmas oficiais do ISPSN por ID interno</div>
            </div>
            <button class="btn sm ghost" onclick="window.sincronizarGestaoEscolar()" style="font-weight:700;">🔄 Sincronizar por API Agora</button>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; background:#faf9f5; border:1px solid var(--line); border-radius:10px; padding:16px;">
            <div>
                <strong style="color:var(--blue); font-size:15px; display:block;"><?= count($cursos) ?> Cursos Oficiais Carregados</strong>
                <span style="font-size:12px; color:var(--mut);">Direito, Psicologia, Gestão de Empresas, Contabilidade, Engenharia Informática e Cardiopneumologia.</span>
            </div>
            <span class="b b-sim" style="font-size:12px; padding:6px 12px;">Conexão OK</span>
        </div>
    </div>
</div>

<!-- ==================================================================== -->
<!-- ABA 4: LISTAS DE VALORES & REGRA DE NEGÓCIO -->
<!-- ==================================================================== -->
<div id="tab-content-listas" class="tab-pane" style="display:none;">
    <div style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:22px; margin-bottom:24px; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
        <h3 style="color:var(--blue); margin-top:0; margin-bottom:14px; font-size:16px; display:flex; align-items:center; gap:8px;">
            📋 Dicionários Globais e Listas de Opções do Sistema
        </h3>
        <p style="font-size:12.5px; color:var(--mut); margin-bottom:18px;">Valores padronizados para preenchimento de conformidade e avaliações do plano de cobertura.</p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:16px;">
            <div style="background:#faf9f5; border:1px solid var(--line); border-radius:8px; padding:14px;">
                <div style="font-weight:700; color:var(--blue); font-size:13px; margin-bottom:8px;">Conformidade Pedagógica</div>
                <ul style="margin:0; padding-left:18px; font-size:12.5px; color:var(--ink);">
                    <li>Sim (Conforme)</li>
                    <li>Parcial</li>
                    <li>Não</li>
                    <li>Por verificar</li>
                </ul>
            </div>

            <div style="background:#faf9f5; border:1px solid var(--line); border-radius:8px; padding:14px;">
                <div style="font-weight:700; color:var(--blue); font-size:13px; margin-bottom:8px;">Justificação da Conformidade</div>
                <ul style="margin:0; padding-left:18px; font-size:12.5px; color:var(--ink);">
                    <li>Licenciatura</li>
                    <li>Mestrado</li>
                    <li>Doutoramento</li>
                    <li>Especializações</li>
                    <li>Experiência Profissional</li>
                </ul>
            </div>

            <div style="background:#faf9f5; border:1px solid var(--line); border-radius:8px; padding:14px;">
                <div style="font-weight:700; color:var(--blue); font-size:13px; margin-bottom:8px;">Regime Contratual</div>
                <ul style="margin:0; padding-left:18px; font-size:12.5px; color:var(--ink);">
                    <li>Tempo Integral</li>
                    <li>Tempo Parcial</li>
                    <li>Colaborador</li>
                </ul>
            </div>

            <div style="background:#faf9f5; border:1px solid var(--line); border-radius:8px; padding:14px;">
                <div style="font-weight:700; color:var(--blue); font-size:13px; margin-bottom:8px;">Parecer Final do Plano</div>
                <ul style="margin:0; padding-left:18px; font-size:12.5px; color:var(--ink);">
                    <li>Manter</li>
                    <li>Manter c/ acompanhamento</li>
                    <li>Substituir</li>
                    <li>Recrutar</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
window.switchConfigTab = (tabKey) => {
    const tabs = ['users', 'anos', 'curriculo', 'listas'];
    tabs.forEach(t => {
        const pane = document.getElementById(`tab-content-${t}`);
        const btn  = document.getElementById(`tab-btn-${t}`);
        if (pane) pane.style.display = (t === tabKey) ? 'block' : 'none';
        if (btn) {
            if (t === tabKey) {
                btn.style.borderBottomColor = 'var(--blue)';
                btn.style.color = 'var(--blue)';
                btn.style.fontWeight = '700';
            } else {
                btn.style.borderBottomColor = 'transparent';
                btn.style.color = 'var(--mut)';
                btn.style.fontWeight = '600';
            }
        }
    });
};

window.toggleFormNovoUser = () => {
    const box = document.getElementById('box-novo-utilizador');
    if (box) box.style.display = box.style.display === 'none' ? 'block' : 'none';
};

window.toggleFormImportarExcel = () => {
    const box = document.getElementById('box-importar-excel');
    if (box) box.style.display = box.style.display === 'none' ? 'block' : 'none';
};

window.salvarAnoLectivo = (ano) => {
    if (window.switchAnoLectivo) {
        window.switchAnoLectivo(ano);
    }
};

window.executarRollOver = async () => {
    if (!confirm('Deseja executar a transição relacional (Roll-Over) de atribuições do ano letivo 2026/27 para 2027/28 no banco de dados?')) {
        return;
    }

    try {
        const res = await fetch('index.php?api=executar_rollover', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ ano_origem: '2026/27', ano_destino: '2027/28' })
        });
        const data = await res.json();

        if (data.success) {
            alert(`⚡ ROLL-OVER EXECUTADO COM SUCESSO!\n\n• Planos de Cobertura Criados: ${data.planos_criados}\n• Atribuições Replicadas: ${data.linhas_replicadas}\n• Transição: ${data.ano_origem} ➔ ${data.ano_destino}`);
            location.reload();
        } else {
            alert(`Erro no Roll-Over: ${data.message || data.error}`);
        }
    } catch (err) {
        alert('Erro de comunicação ao executar Roll-Over.');
    }
};

window.sincronizarGestaoEscolar = async () => {
    try {
        const res = await fetch('index.php?api=sincronizar_gestao_escolar');
        const data = await res.json();

        if (data.success) {
            alert(`🔄 SINCRONIZAÇÃO CONCLUÍDA COM SUCESSO!\n\n• Fonte de Dados: ${data.fonte}\n• Docentes Sincronizados: ${data.docentes_sync}\n• Cursos Atualizados: ${data.cursos_sync}\n• Disciplinas Atualizadas: ${data.disciplinas_sync}\n• Turmas Sincronizadas: ${data.turmas_sync}`);
            location.reload();
        } else {
            alert(`Erro na sincronização: ${data.message || data.error}`);
        }
    } catch (err) {
        alert('Erro de comunicação ao sincronizar com o Gestão Escolar.');
    }
};

window.salvarUser = async (userId) => {
    const perfilSelect = document.getElementById(`usr-perfil-${userId}`);
    const cursoSelect  = document.getElementById(`usr-curso-${userId}`);
    const activoSelect = document.getElementById(`usr-activo-${userId}`);
    if (!perfilSelect) return;

    try {
        const res = await fetch('index.php?api=utilizador_salvar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                id: userId,
                perfil: perfilSelect.value,
                curso_id: cursoSelect ? cursoSelect.value : null,
                activo: activoSelect ? parseInt(activoSelect.value) : 1
            })
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message || 'Utilizador atualizado!');
            location.reload();
        } else {
            alert('Erro: ' + (data.error || data.message || 'Falha ao atualizar utilizador.'));
        }
    } catch (err) {
        alert('Erro ao atualizar utilizador.');
    }
};

window.alternarEstadoUser = async (userId, novoEstado) => {
    const estadoTexto = novoEstado === 1 ? 'ATIVAR' : 'DESATIVAR';
    if (!confirm(`Tem a certeza que deseja ${estadoTexto} a conta deste utilizador?`)) return;

    const perfilSelect = document.getElementById(`usr-perfil-${userId}`);
    const cursoSelect  = document.getElementById(`usr-curso-${userId}`);

    try {
        const res = await fetch('index.php?api=utilizador_salvar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                id: userId,
                perfil: perfilSelect ? perfilSelect.value : 'coordenador',
                curso_id: cursoSelect ? cursoSelect.value : null,
                activo: novoEstado
            })
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message || `Utilizador ${novoEstado === 1 ? 'ativado' : 'desativado'} com sucesso!`);
            location.reload();
        } else {
            alert('Erro: ' + (data.error || data.message || 'Falha ao alterar estado do utilizador.'));
        }
    } catch (err) {
        alert('Erro ao alterar estado do utilizador.');
    }
};

window.toggleFormAtivarDocente = () => {
    const box = document.getElementById('box-ativar-docente');
    if (box) box.style.display = box.style.display === 'none' ? 'block' : 'none';
};

window.preencherEmailDocente = (docenteId) => {
    const select = document.getElementById('ad-docente');
    const emailInput = document.getElementById('ad-email');
    if (!select || !emailInput) return;

    const opt = select.options[select.selectedIndex];
    const email = opt ? opt.getAttribute('data-email') : '';
    const nome  = opt ? opt.getAttribute('data-nome') : '';

    if (email) {
        emailInput.value = email;
    } else if (nome) {
        // Gerar sugestão de e-mail corporativo: nome.sobrenome@ispsn.org
        const partes = nome.trim().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").split(/\s+/);
        if (partes.length >= 2) {
            emailInput.value = `${partes[0]}.${partes[partes.length - 1]}@ispsn.org`;
        } else if (partes.length === 1) {
            emailInput.value = `${partes[0]}@ispsn.org`;
        }
    }
};

window.toggleCursoDocenteField = (perfil) => {
    const wrap = document.getElementById('wrap-ad-curso');
    if (wrap) {
        wrap.style.display = (perfil === 'coordenador') ? 'block' : 'none';
    }
};

window.ativarPerfilDocente = async (e) => {
    e.preventDefault();
    const docente_id = document.getElementById('ad-docente').value;
    const email      = document.getElementById('ad-email').value;
    const perfil     = document.getElementById('ad-perfil').value;
    const curso_id   = document.getElementById('ad-curso').value;

    if (!docente_id || !email) {
        alert('Por favor selecione um docente e introduza o e-mail corporativo.');
        return;
    }

    try {
        const res = await fetch('index.php?api=utilizador_ativar_docente', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ docente_id, email, perfil, curso_id })
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message || 'Perfil do docente ativado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || data.error || 'Falha ao ativar perfil.'));
        }
    } catch (err) {
        alert('Erro de comunicação ao ativar perfil do docente.');
    }
};

window.abrirModalPermissoes = () => {
    const el = document.getElementById('modal-permissoes-rbac');
    if (el) el.style.display = 'flex';
};

window.fecharModalPermissoes = () => {
    const el = document.getElementById('modal-permissoes-rbac');
    if (el) el.style.display = 'none';
};

window.criarUtilizador = async (e) => {
    e.preventDefault();
    const nome = document.getElementById('nu-nome').value;
    const email = document.getElementById('nu-email').value;
    const perfil = document.getElementById('nu-perfil').value;
    const curso_id = document.getElementById('nu-curso').value;

    try {
        const res = await fetch('index.php?api=utilizador_criar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ nome, email, perfil, curso_id })
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message || 'Utilizador criado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.error || data.message || 'Falha ao criar utilizador.'));
        }
    } catch (err) {
        alert('Erro de comunicação ao registar novo utilizador.');
    }
};

window.importarUtilizadoresCSV = async (e) => {
    e.preventDefault();
    const fileInput = document.getElementById('file-csv-users');
    if (!fileInput || !fileInput.files.length) return;

    const file = fileInput.files[0];
    const reader = new FileReader();

    reader.onload = async (event) => {
        const text = event.target.result;
        const lines = text.split(/\r\n|\n/);
        const utilizadores = [];

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (!line) continue;
            if (i === 0 && line.toLowerCase().includes('email')) continue;

            const parts = line.split(',');
            if (parts.length >= 2) {
                utilizadores.push({
                    nome: parts[0].trim(),
                    email: parts[1].trim(),
                    perfil: parts[2] ? parts[2].trim() : 'coordenador',
                    curso_codigo: parts[3] ? parts[3].trim() : ''
                });
            }
        }

        if (utilizadores.length === 0) {
            alert('Nenhum utilizador válido encontrado no ficheiro.');
            return;
        }

        try {
            const res = await fetch('index.php?api=utilizadores_importar', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ utilizadores })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message || `${utilizadores.length} utilizadores importados!`);
                location.reload();
            } else {
                alert('Erro: ' + (data.error || data.message || 'Falha ao importar utilizadores.'));
            }
        } catch (err) {
            alert('Erro ao importar lista de utilizadores.');
        }
    };

    reader.readAsText(file);
};

window.toggleCursoDocenteField = function(val) {
    const wrapCurso = document.getElementById('wrap-ad-curso');
    const wrapDepto = document.getElementById('wrap-ad-depto');
    if (val === 'chefe_departamento') {
        if (wrapCurso) wrapCurso.style.display = 'none';
        if (wrapDepto) wrapDepto.style.display = 'block';
    } else if (val === 'coordenador') {
        if (wrapCurso) wrapCurso.style.display = 'block';
        if (wrapDepto) wrapDepto.style.display = 'none';
    } else {
        if (wrapCurso) wrapCurso.style.display = 'none';
        if (wrapDepto) wrapDepto.style.display = 'none';
    }
};
</script>

<!-- MODAL DA MATRIZ COMPLETA DE PERMISSÕES (RBAC) -->
<div id="modal-permissoes-rbac" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.55); z-index:999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; max-width:850px; width:92%; padding:24px; box-shadow:0 8px 30px rgba(0,0,0,0.3); max-height:88vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:12px; margin-bottom:16px;">
            <h3 style="margin:0; color:var(--blue); font-size:17px; display:flex; align-items:center; gap:8px;">
                <span>🛡️</span> Matriz Institucional de Perfis e Permissões (RBAC)
            </h3>
            <button onclick="window.fecharModalPermissoes()" style="background:none; border:none; font-size:18px; font-weight:700; cursor:pointer;">✕</button>
        </div>

        <p style="font-size:12.5px; color:var(--mut); margin-bottom:18px;">
            Controlo de acessos baseado em perfis funcionais (Role-Based Access Control). Cada perfil define o nível de visibilidade e ação autorizada no sistema.
        </p>

        <div class="tbl-wrap">
            <table class="tbl" style="font-size:12.5px;">
                <thead>
                    <tr style="background:#f4f2ec;">
                        <th>Perfil ISPSN</th>
                        <th>Âmbito</th>
                        <th style="text-align:center;">Ver Cobertura</th>
                        <th style="text-align:center;">Editar Cobertura</th>
                        <th style="text-align:center;">Aprovar Planos</th>
                        <th style="text-align:center;">Gestão Docentes/CV</th>
                        <th style="text-align:center;">Configurações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Coordenador de Curso</strong></td>
                        <td><span class="pill mut">Próprio Curso</span></td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">✅ (Seu Curso)</td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">❌</td>
                    </tr>
                    <tr>
                        <td><strong>Chefe de Departamento</strong></td>
                        <td><span class="pill warn" style="background:#FFF9E6; color:#B45309; font-weight:700;">Seu Departamento</span></td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">✅ Aprovar / Recusar</td>
                        <td style="text-align:center;">Consulta</td>
                        <td style="text-align:center;">❌ (Bloqueado)</td>
                    </tr>
                    <tr>
                        <td><strong>Gestão Académica</strong></td>
                        <td><span class="pill ok">Geral</span></td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">✅ (Global)</td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">Consulta</td>
                        <td style="text-align:center;">❌</td>
                    </tr>
                    <tr>
                        <td><strong>Recursos Humanos (GRH)</strong></td>
                        <td><span class="pill ok">Geral</span></td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">✅ Total (CV &amp; Doc)</td>
                        <td style="text-align:center;">❌</td>
                    </tr>
                    <tr>
                        <td><strong>Presidente / Direção</strong></td>
                        <td><span class="pill ok">Geral</span></td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">Consulta</td>
                        <td style="text-align:center;">👑 Soberano</td>
                        <td style="text-align:center;">Consulta</td>
                        <td style="text-align:center;">❌</td>
                    </tr>
                    <tr>
                        <td><strong>Secretário-Geral</strong></td>
                        <td><span class="pill ok">Geral</span></td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">Consulta</td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">Consulta</td>
                        <td style="text-align:center;">❌</td>
                    </tr>
                    <tr>
                        <td><strong>Administração</strong></td>
                        <td><span class="pill ok">Geral</span></td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">✅ Total</td>
                        <td style="text-align:center;">✅ Total</td>
                        <td style="text-align:center;">✅ Total</td>
                        <td style="text-align:center;">⚡ Total (RBAC/Users)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="text-align:right; border-top:1px solid var(--line); padding-top:14px; margin-top:18px;">
            <button onclick="window.fecharModalPermissoes()" class="btn">Fechar</button>
        </div>
    </div>
</div>


