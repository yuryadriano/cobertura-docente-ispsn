# Gestão Escolar — Guia do Desenvolvedor
## Módulo de Cobertura Docente ISPSN 2026/27 (integração no sistema existente)

Esta pasta contém tudo o que é necessário para desenvolver a solução como **novo módulo dentro do Gestão Escolar** (gestaoescolar.ispsn.org), reaproveitando entidades, uploads e logins já existentes. **Esta é a abordagem recomendada** (menor esforço e sem sincronização). Se a decisão for uma aplicação separada, usar a pasta `01_Portal_Autonomo`.

---

## 1. Conteúdo da pasta

```
02_Gestao_Escolar/
├── README_GESTAO_ESCOLAR.md           (este ficheiro)
├── backoffice/
│   └── index.html                     protótipo navegável do BackOffice (coordenadores/gestores)
├── frontoffice-dashboard/
│   └── index.html                     protótipo do Dashboard institucional (Direção/stakeholders)
├── docs/
    └── Especificacao_Modulo_Cobertura_Docente.docx   especificação funcional e técnica completa
```

Os `index.html` são **protótipos de referência de UI e comportamento**. Abra-os num browser para ver os ecrãs a replicar dentro do Gestão Escolar.

---

## 2. O que já existe no sistema (confirmado por levantamento)

O módulo aproveita a base já presente — **não é preciso criar do zero** o registo de docentes nem o currículo:

| Já existe | Onde | Reutilizar para |
|---|---|---|
| Ficha de docente + grau académico + uploads (BI, Certificados, INAAREES, CV, Agregação Pedagógica) | `/teacher/{id}/edit` | Perfil e documentos do docente |
| Disciplinas por Ano (1.º–4.º) e Semestre | `/course/{id}/manage` | Currículo pré-carregado do plano |
| Turmas por disciplina; Sumários contados por turma (ex.: 0/200); tabs Notas, Programa, Dosificação | por disciplina/turma | Indicadores automáticos de desempenho |
| Data de início de inquéritos | `/subject/{id}/edit` | Avaliação dos estudantes |
| Perfis/login (base para: Coordenador de Curso, Gestão Académica, GRH, Presidente, Secretário-Geral, Administração) | sistema | Permissões do módulo |

## 3. O que é preciso acrescentar

- **Tabelas novas:** `plano_cobertura`, `linha_cobertura`, `cv_estruturado` (1:1 com docente), e reforço da gestão de estados dos documentos. Ver modelo de dados na especificação.
- **Ecrãs novos** (replicar os do protótipo, dentro da UI do Gestão Escolar):
  - **Cobertura Docente** — separador por curso/ano, UCs oficiais pré-carregadas, escolha de docente, colunas `(auto)`, conformidade/regime/parecer, Guardar/Submeter.
  - **CV Estruturado** — formulário por campos que alimenta a ficha e o plano.
  - **Aprovações** — vista do Gestor.
  - **Painel** e **Dashboard institucional** — reaproveitar o FrontOffice.

## 4. Modelo de atribuição

Sem figura de «regente»: cada docente gere a disciplina da **sua turma**. A `linha_cobertura` liga **docente ↔ turma ↔ disciplina**. Os sumários por turma são a fonte da assiduidade e da % de sumários.

## 5. Regras de auto-preenchimento

CV/ficha do docente → colunas `(auto)` do plano (grau, especialidade, INAAREES, capacitação, n.º de cursos). Atividade das turmas → indicadores de desempenho (sumários, conteúdos, notas no prazo, inquéritos). Editar o CV propaga a todas as linhas do docente. Detalhe e tabelas de mapeamento na especificação (secção 6).

## 6. Chave de integração

Usar sempre o **ID interno** já existente: docente `/teacher/{id}` (ex.: 1107), disciplina `/subject/{id}` (ex.: 9826), turma (ex.: ACSP1MA). Nunca ligar por nome — as grafias divergentes do mesmo docente foram a principal fonte de erro no mapa de 2025/26.

## 7. Ordem de implementação sugerida

1. Tabelas + ecrã de Cobertura por curso/ano com currículo pré-carregado e colunas automáticas a partir da ficha existente; Guardar/Submeter/Aprovar; Painel.
2. CV estruturado + estados dos documentos.
3. Indicadores automáticos a partir das turmas (sumários, notas no prazo, inquéritos).
4. Dashboard institucional, exportações e comparação entre anos.
