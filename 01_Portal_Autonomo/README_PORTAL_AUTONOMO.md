# Portal Autónomo — Guia do Desenvolvedor
## Módulo de Cobertura Docente ISPSN 2026/27

Esta pasta contém tudo o que é necessário para desenvolver a solução como **aplicação web autónoma** (base de dados própria), que **sincroniza** com o Gestão Escolar por API. Se a decisão for antes integrar no sistema atual, usar a pasta `02_Gestao_Escolar`.

---

## 1. Conteúdo da pasta

```
01_Portal_Autonomo/
├── README_PORTAL_AUTONOMO.md          (este ficheiro)
├── backoffice/
│   └── index.html                     protótipo navegável do BackOffice (coordenadores/gestores)
├── frontoffice-dashboard/
│   └── index.html                     protótipo do Dashboard institucional (Direção/stakeholders)
├── dados/
│   ├── portal_data.json               dados reais (258 docentes, currículo dos 12 cursos, conformidade)
│   └── contrato_api.md                contrato da API de sincronização com o Gestão Escolar
└── docs/
    └── Especificacao_Modulo_Cobertura_Docente.docx   especificação funcional e técnica completa
```

Os dois `index.html` são **protótipos de referência de UI e comportamento** — abra-os num browser (basta duplo-clique). Não são a aplicação final; servem para replicar ecrãs, fluxos e regras.

---

## 2. Arquitetura sugerida

- **Frontend:** SPA (React/Vue/Angular) ou server-rendered (o protótipo é HTML+JS puro e pode ser portado para qualquer stack).
- **Backend:** API REST própria (Node/Express, Laravel, Django, etc.) + base de dados relacional.
- **Autenticação:** SSO com o Portal ISPSN se possível; caso contrário, login próprio com os seis perfis: Coordenador de Curso, Gestão Académica, GRH, Presidente, Secretário-Geral, Administração.
- **Sincronização com o Gestão Escolar:** ver `dados/contrato_api.md`. A regra de ouro: a chave de ligação é sempre o **ID interno** (docente, disciplina, turma), nunca o nome.

## 3. Dois produtos, uma base de dados

| Produto | Utilizadores | Baseado em |
|---|---|---|
| **BackOffice** (`backoffice/index.html`) | Coordenadores e Gestores Académicos | Preenchimento/edição do plano, atribuição de docentes, upload de documentos, CV estruturado, aprovações |
| **FrontOffice / Dashboard** (`frontoffice-dashboard/index.html`) | Direção, órgãos de gestão, acreditação | Leitura: conformidade, qualificações, regularização, sobrecarga, ranking por curso |

## 4. Modelo de dados (resumo — detalhe na especificação)

Entidades novas: `PlanoCobertura`, `LinhaCobertura`, `CVEstruturado`, `DocumentoDocente`.
Entidades sincronizadas (só leitura, do Gestão Escolar): `Docente`, `Curso`, `Disciplina`, `Turma`, e os indicadores de atividade (sumários, notas, inquéritos).

## 5. Regras de auto-preenchimento (o ponto central)

O CV estruturado alimenta as colunas `(auto)` do plano:
`grau_academico → Grau`, `especialidade → Especialidade`, documento INAAREES → `INAAREES`, documento de agregação → `Capacitação pedagógica`, `anos_experiencia`/`producao_cientifica` → colunas de desempenho.
Editar o CV de um docente **atualiza todas as linhas** onde ele está atribuído, em qualquer curso.
Assiduidade, % de sumários, conteúdos e notas no prazo **derivam da atividade das turmas** obtida do Gestão Escolar (não são lançados à mão).

## 6. Formato dos dados de arranque

`dados/portal_data.json` já traz dados reais para semear o protótipo/ambiente de testes:
- `docentes[]`: `{n, grau, esp, ina, cap, car, nc}` — 258 docentes.
- `curriculo{curso}{ano}{semestre}[]`: disciplinas oficiais dos 12 cursos.
- `ref{curso}{disciplina_norm}`: docente que leccionou em 2025/26 (para a sugestão automática).
- `confdetail{curso}: [sim, nao, ni]` e `consol{curso}: [total, conf]` — para o dashboard.

## 7. Ordem de implementação sugerida

1. Autenticação + perfis + sincronização de docentes/cursos/disciplinas/turmas.
2. BackOffice: plano por curso/ano com currículo pré-carregado, atribuição de docente, colunas automáticas, Guardar/Submeter/Aprovar.
3. CV estruturado + gestão documental.
4. Indicadores de desempenho a partir da atividade das turmas.
5. FrontOffice/Dashboard e exportações.
