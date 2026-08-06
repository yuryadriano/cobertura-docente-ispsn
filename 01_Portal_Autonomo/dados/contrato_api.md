# Contrato de API — Sincronização Portal Autónomo ↔ Gestão Escolar

Aplicável **apenas à opção de Portal Autónomo**. Define o que o portal **lê** do Gestão Escolar e o que **escreve** de volta. Todos os identificadores são os IDs internos do Gestão Escolar. Formato: JSON; autenticação por token de serviço.

> Nota: os caminhos abaixo são uma **proposta de contrato**. Os endpoints de leitura devem espelhar as entidades já observadas no Gestão Escolar (`/teacher`, `/course`, `/subject`, turmas, sumários, notas). A equipa do Gestão Escolar confirma/ajusta os caminhos reais.

## 1. Leitura (Gestão Escolar → Portal)

### GET /api/docentes
```json
[{ "id": 1107, "nome": "Abel Hossi Chissingui", "email": "abel.chissingui@ispsn.org",
   "grau_academico": "Licenciado", "activo": true,
   "documentos": { "cv": true, "certificados": true, "inaarees": true, "bi": true, "agregacao_pedag": false } }]
```

### GET /api/cursos
```json
[{ "id": 224, "nome": "Direito", "activo": true }]
```

### GET /api/cursos/{id}/disciplinas
```json
[{ "id": 9826, "nome": "Direito Romano", "ano_curricular": 1, "semestre": "I",
   "carga_horaria_semanal": 0, "creditos": 1 }]
```

### GET /api/disciplinas/{id}/turmas
```json
[{ "id": "ACSP1MA", "designacao": "ACSP1MA - Manhã", "docente_id": 1107,
   "sumarios_registados": 120, "sumarios_previstos": 200,
   "programa_carregado": true, "dosificacao_carregada": true,
   "notas_publicadas_em": "2026-02-14", "prazo_publicacao_notas": "2026-02-20",
   "inquerito_media": 4.2 }]
```
Campos derivados no portal: `% sumários`, `assiduidade estimada`, `conteúdos disponibilizados`, `notas no prazo (Sim/Não)`, `avaliação dos estudantes`.

## 2. Escrita (Portal → Gestão Escolar)

### PUT /api/planos/{curso_id}
Persiste/atualiza o plano de cobertura de um curso.
```json
{ "curso_id": 224, "ano_lectivo": "2026/27", "estado": "Submetido",
  "linhas": [
    { "disciplina_id": 9826, "turma_id": "ACSP1MA", "docente_id": 1107,
      "conformidade": "Sim", "justificacao": "Área de formação coincide",
      "regime": "Tempo Parcial", "categoria_carreira": "Assistente",
      "parecer": "Manter", "observacoes": "" }
  ] }
```

### POST /api/docentes/{id}/cv
Grava o CV estruturado (alimenta as colunas automáticas do plano).
```json
{ "grau_academico": "Doutor", "especialidade": "Direito Público",
  "tem_inaarees": true, "tem_agregacao_pedag": true,
  "categoria_carreira": "Professor Auxiliar",
  "anos_experiencia_es": 8, "producao_cientifica_3a": 4 }
```

### POST /api/docentes/{id}/documentos
Upload de documento (multipart): `tipo` ∈ {cv, certificados, diplomas, bi, inaarees, agregacao_pedag}, `ficheiro`, `validade?`.

## 3. Regras
- Sincronização de docentes/cursos/disciplinas/turmas: **pull** periódico (ex.: diário) + on-demand.
- Escrita de planos/CV/documentos: imediata, com idempotência por `(curso_id, ano_lectivo)` e `(docente_id)`.
- Conflitos: o Gestão Escolar é a fonte de verdade para docente/currículo; o Portal é a fonte de verdade para o plano de cobertura e o CV estruturado.
- Chave em todas as ligações: **ID interno**, nunca o nome.
