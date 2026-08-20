# 📘 Manual de Integração e Contrato de API REST (v1.1)
## Módulo de Cobertura Docente ↔ Sistema de Gestão Escolar (ISPSN)

---

## 1. Visão Geral da Integração

Este documento destina-se à equipa de desenvolvimento do **Sistema de Gestão Escolar (`gestaoescolar.ispsn.org`)**. O objetivo é sincronizar dados em tempo real com o **Módulo de Cobertura Docente (`docentes.ispsn.app`)**, utilizando **IDs Numéricos e Códigos Canónicos** para comparação e reconciliação exata.

---

## 2. Autenticação e Segurança

Todas as chamadas à API de integração exigem o cabeçalho HTTP com token de serviço:

```http
Authorization: Bearer ISPSN_INTEGRATION_KEY_2026_SECRET_TOKEN
Content-Type: application/json
Accept: application/json
```

---

## 3. Endpoints de Consulta de IDs Mestres (Para Comparação)

### 🔹 1. Listar Cursos com IDs (`curso_id`, `curso_codigo`)
Permite ao Gestão Escolar obter o mapeamento de IDs de todos os cursos.
* **URL**: `https://docentes.ispsn.app/index.php?api=v1_integracao_cursos`
* **Exemplo de Resposta**:
```json
{
  "success": true,
  "data": [
    { "curso_id": 8, "curso_codigo": "FISI", "curso_nome": "Fisioterapia", "grau": "Licenciatura", "duracao_anos": 4, "activo": 1 },
    { "curso_id": 7, "curso_codigo": "ENFE", "curso_nome": "Enfermagem", "grau": "Licenciatura", "duracao_anos": 4, "activo": 1 },
    { "curso_id": 5, "curso_codigo": "DIRE", "curso_nome": "Direito", "grau": "Licenciatura", "duracao_anos": 5, "activo": 1 }
  ]
}
```

---

### 🔹 2. Listar Disciplinas com IDs (`disciplina_id`, `curso_id`)
Lista todas as unidades curriculares oficiais, anos e semestres.
* **URL**: `https://docentes.ispsn.app/index.php?api=v1_integracao_disciplinas&curso_id=8`
* **Exemplo de Resposta**:
```json
{
  "success": true,
  "data": [
    { "disciplina_id": 1232, "curso_id": 8, "curso_codigo": "FISI", "disciplina_nome": "Fisioterapia Dermatofuncional", "ano_curricular": 3, "semestre": "I", "carga_horaria_semanal": 4, "creditos": 4 },
    { "disciplina_id": 1233, "curso_id": 8, "curso_codigo": "FISI", "disciplina_nome": "Recursos Naturais em Fisioterapia", "ano_curricular": 3, "semestre": "I", "carga_horaria_semanal": 3, "creditos": 3 }
  ]
}
```

---

### 🔹 3. Listar Docentes com IDs (`docente_id`)
Lista todos os docentes com seus IDs internos e dados de conformidade INAAREES.
* **URL**: `https://docentes.ispsn.app/index.php?api=v1_integracao_docentes`
* **Exemplo de Resposta**:
```json
{
  "success": true,
  "data": [
    { "docente_id": 1107, "docente_nome": "Abel Hossi Chissingui", "docente_email": "abel.chissingui@ispsn.org", "grau_academico": "Mestre", "especialidade": "Fisioterapia Respiratória", "tem_inaarees": "Sim" }
  ]
}
```

---

## 4. Endpoint de Exportação da Matriz com Todos os IDs

### 🔹 4. Exportar Plano Homologado com Todos os IDs Vinculados
* **URL**: `https://docentes.ispsn.app/index.php?api=v1_integracao_plano_export&curso_id=8&ano=2026/27`
* **Exemplo de Resposta Completa com IDs**:
```json
{
  "success": true,
  "data": {
    "curso": { "id": 8, "codigo": "FISI", "nome": "Fisioterapia" },
    "plano": { "id": 8, "curso_id": 8, "ano_lectivo": "2026/27", "estado": "Aprovado pelo Departamento" },
    "total_linhas": 224,
    "atribuicoes": [
      {
        "linha_id": 46073,
        "curso_id": 8,
        "plano_id": 8,
        "disciplina_id": 1232,
        "disciplina_codigo": null,
        "disciplina_nome": "Fisioterapia Dermatofuncional",
        "ano_curricular": 3,
        "semestre": "I",
        "turma_id": "FISIO3MA-D1232",
        "turma_codigo": "FISIO3MA",
        "turma_nome": "Turma A (FISIO3MA)",
        "docente_id": 207,
        "docente_nome": "Dr. Manuel dos Santos",
        "docente_email": "manuel.santos@ispsn.org",
        "conformidade": "Sim",
        "regime": "Tempo Parcial",
        "decisao_aprovacao": "Aprovar"
      }
    ]
  }
}
```

---

## 5. Endpoints de Sincronização / Ingestão (ERP ➔ Cobertura)

* **`POST ?api=v1_integracao_sync_docentes`**: Ingestão de docentes em lote.
* **`POST ?api=v1_integracao_sync_metricas`**: Ingestão de sumários e médias de inquéritos por turma.
* **`GET ?api=v1_integracao_logs`**: Auditoria e logs de sincronização.
