# Revisão da Documentação & Regras de Negócio ISPSN 2026/27
## Módulo de Cobertura Docente — Especificação de Atribuições e Aprovações

---

### 1. Modelo de Permissões e Fluxo de Aprovação (RBAC)

De acordo com o levantamento dos protótipos e especificação funcional do ISPSN, o fluxo de vida do Plano de Cobertura Docente é estritamente hierárquico:

```
[Coordenador de Curso] ──(Preenche/Edita)──> [Rascunho]
         │
         └──(Clica "Submeter")─────────────> [Submetido]
                                                 │
                                                 ▼
                                        [Aprovação Presidencial]
                                       ┌─────────┴─────────┐
                                       ▼                   ▼
                                 [Aprovado]           [Devolvido]
                              (Presidência)      (Retificação/Ajuste)
```

#### Perfis e Responsabilidades:

| Perfil | Escopo | Ações Permitidas | Aprovação |
|---|---|---|---|
| **Coordenador de Curso** | O seu curso | Preenche a cobertura docente, escolhe docentes por turma, guarda rascunho e **submete para aprovação**. | ❌ Não aprova |
| **Gestão Académica** | Todos os cursos | Acompanha a execução global, visualiza indicadores de assiduidade e conformidade pedagógica. | ❌ Não aprova (Apenas consulta) |
| **Secretário-Geral** | Todos os cursos | Acompanha relatórios institucionais e conformidade global dos quadros. | ❌ Não aprova (Apenas consulta) |
| **GRH** | Todos os docentes | Valida ficheiros documentais (INAAREES, BI, Certificados) e preenche o CV Estruturado. | ❌ Não aprova |
| **Presidente** | Todos os cursos | **APROVA** soberanamente ou **RECUSA/DEVOLVE** o plano com observações de retificação. | **YES (Exclusivo da Presidência)** |
| **Administrador** | Todos os cursos | Acesso total ao sistema, configurações, gestão de utilizadores e backup de aprovação. | **YES (Suporte ao Sistema)** |

---

### 2. Modelo de Atribuição por Turma e Disciplina

No sistema ISPSN, cada Unidade Curricular (UC) é ministrada no âmbito de **Turmas específicas** (ex.: `DIR1M1` - Direito 1º Ano Manhã, `DIR1P1` - Direito 1º Ano Pós-Laboral):

1. **Relação**: `Curso ➔ Turma ➔ Disciplina ➔ Docente`
2. **Propagação `↳ todas as turmas`**: O coordenador pode atribuir o docente a uma turma e aplicar a mesma atribuição para as restantes turmas do mesmo ano curricular.
3. **Métricas de Desempenho Operacional**: Derivam diretamente da atividade da turma:
   - **Sumários Registados** (ex.: `180 / 200` sumários).
   - **Lançamento de Notas no Prazo** (`Sim` / `Não`).
   - **Avaliação dos Estudantes / Inquérito Pedagógico** (média `1 a 5 estrelas`).

---

### 3. Integração Automática com CV Estruturado & Gestão Documental

* **Propagação em Tempo Real**: As colunas `(auto)` do plano (Grau Académico, Especialidade Científica, Declaração INAAREES e Agregação Pedagógica) são alimentadas diretamente pelo CV Estruturado do docente.
* **Sobrecarga (Alerta ≥ 3 Cursos)**: O sistema calcula automaticamente em quantos cursos o docente leciona no ano letivo. Caso atinja 3 ou mais cursos, a tag de alerta é acionada.
