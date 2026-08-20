# 📘 Manual de Integração e Contrato de API REST (v1.0)
## Módulo de Cobertura Docente ↔ Sistema de Gestão Escolar (ISPSN)

---

## 1. Visão Geral da Integração

Este documento destina-se à equipa de desenvolvimento do **Sistema de Gestão Escolar (`gestaoescolar.ispsn.org`)**. O objetivo é sincronizar dados em tempo real com o **Módulo de Cobertura Docente (`docentes.ispsn.app`)**, garantindo que as turmas, unidades curriculares oficiais de 2026/27 e docentes aprovados sejam refletidos no sistema central.

---

## 2. Autenticação e Segurança

Todas as chamadas à API de integração exigem um cabeçalho HTTP com token de serviço:

```http
Authorization: Bearer ISPSN_INTEGRATION_KEY_2026_SECRET_TOKEN
Content-Type: application/json
Accept: application/json
```

> 🔒 **Nota de Segurança**: Para ambientes de produção, a chave pode ser alterada no ficheiro `.env` ou variável de ambiente `INTEGRATION_API_KEY`.

---

## 3. Endpoints Disponíveis

### 1️⃣ Healthcheck e Estado do Serviço
Verifica se a API de Cobertura está online e retorna estatísticas globais de dados.

* **Método**: `GET`
* **URL**: `https://docentes.ispsn.app/index.php?api=v1_integracao_status`
* **Exemplo de Resposta (200 OK)**:
```json
{
  "success": true,
  "data": {
    "status": "ONLINE",
    "versao_api": "1.0.0-Enterprise",
    "ambiente": "ISPSN Production/Staging",
    "total_docentes": 272,
    "total_cursos": 13,
    "total_turmas": 3003,
    "total_linhas": 3606,
    "ultimo_sync": "2026-08-20 13:04:11",
    "timestamp": "2026-08-20T13:04:11+02:00"
  }
}
```

---

### 2️⃣ Exportar Matriz Homologada e Atribuições Docentes
Utilizado pelo Gestão Escolar para ler a matriz oficial (com turmas canónicas e docentes atribuídos) para gerar horários escolares e pautas.

* **Método**: `GET`
* **URL**: `https://docentes.ispsn.app/index.php?api=v1_integracao_plano_export&curso_id={ID_CURSO}&ano={ANO_LECTIVO}`
* **Parâmetros de Query**:
  - `curso_id` *(obrigatório, inteiro)*: ID do curso no sistema (Ex: `8` para Fisioterapia, `2` para Direito, etc.).
  - `ano` *(opcional, string)*: Ano lectivo pretendido (Padrão: `2026/27`).

* **Exemplo de Resposta (200 OK)**:
```json
{
  "success": true,
  "meta": {
    "timestamp": "2026-08-20T13:04:11+02:00",
    "tempo_ms": 12.4,
    "versao_contrato": "v1.0"
  },
  "data": {
    "curso": {
      "id": 8,
      "codigo": "FISI",
      "nome": "Fisioterapia",
      "grau": "Licenciatura",
      "duracao_anos": 4
    },
    "plano": {
      "id": 8,
      "curso_id": 8,
      "ano_lectivo": "2026/27",
      "estado": "Aprovado pelo Departamento"
    },
    "total_linhas": 224,
    "atribuicoes": [
      {
        "linha_id": 46073,
        "disciplina_id": 1232,
        "disciplina_nome": "Fisioterapia Dermatofuncional",
        "ano_curricular": 3,
        "semestre": "I",
        "carga_horaria_semanal": 4,
        "creditos": 4,
        "turma_id": "FISIO3MA-D1232",
        "turma_nome": "Turma A (FISIO3MA)",
        "docente_id": 207,
        "docente_nome": "Dr. Manuel dos Santos",
        "docente_grau": "Mestre",
        "docente_especialidade": "Fisioterapia Dermatofuncional",
        "conformidade": "Sim",
        "regime": "Tempo Parcial",
        "decisao_aprovacao": "Aprovar"
      }
    ]
  }
}
```

---

### 3️⃣ Ingestão / Sincronização de Docentes
Envia novos docentes ou alterações cadastrais do Gestão Escolar para o Módulo de Cobertura. Operação **idempotente** (cria novos ou atualiza existentes sem duplicar).

* **Método**: `POST`
* **URL**: `https://docentes.ispsn.app/index.php?api=v1_integracao_sync_docentes`
* **Corpo da Requisição (JSON)**:
```json
{
  "docentes": [
    {
      "id": 1107,
      "nome": "Abel Hossi Chissingui",
      "email": "abel.chissingui@ispsn.org",
      "grau_academico": "Mestre",
      "especialidade": "Fisioterapia Respiratória",
      "tem_inaarees": true,
      "tem_agregacao_pedag": true,
      "categoria_carreira": "Assistente",
      "anos_experiencia_es": 6,
      "producao_cientifica_3a": 2,
      "activo": 1
    }
  ]
}
```

* **Exemplo de Resposta (200 OK / 207 Multi-Status)**:
```json
{
  "success": true,
  "meta": {
    "timestamp": "2026-08-20T13:04:11+02:00",
    "tempo_ms": 18.2
  },
  "data": {
    "total": 1,
    "inseridos": 0,
    "atualizados": 1,
    "erros": []
  }
}
```

---

### 4️⃣ Ingestão de Métricas Operacionais das Turmas
Atualiza o progresso real das turmas (sumários registados no ERP, carregamento de programas, pautas no prazo e inquéritos pedagógicos).

* **Método**: `POST`
* **URL**: `https://docentes.ispsn.app/index.php?api=v1_integracao_sync_metricas`
* **Corpo da Requisição (JSON)**:
```json
{
  "metricas": [
    {
      "turma_id": "FISIO3MA-D1232",
      "sumarios_registados": 160,
      "sumarios_previstos": 200,
      "programa_carregado": 1,
      "dosificacao_carregada": 1,
      "notas_no_prazo": "Sim",
      "inquerito_media": 4.75
    }
  ]
}
```

* **Exemplo de Resposta (200 OK)**:
```json
{
  "success": true,
  "meta": {
    "timestamp": "2026-08-20T13:04:11+02:00",
    "tempo_ms": 14.5
  },
  "data": {
    "total": 1,
    "atualizados": 1,
    "erros": []
  }
}
```

---

### 5️⃣ Consulta de Auditoria e Logs de Sincronização
Permite aos administradores auditar as chamadas e tempos de resposta.

* **Método**: `GET`
* **URL**: `https://docentes.ispsn.app/index.php?api=v1_integracao_logs&limit=50`

---

## 4. Exemplos Práticos de Implementação

### 🟢 Exemplo em PHP (cURL nativo)

```php
<?php
$token = 'ISPSN_INTEGRATION_KEY_2026_SECRET_TOKEN';
$cursoId = 8; // Fisioterapia

$ch = curl_init("https://docentes.ispsn.app/index.php?api=v1_integracao_plano_export&curso_id={$cursoId}&ano=2026/27");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$token}",
    "Accept: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "Sucesso! Total de UCs recebidas: " . $data['data']['total_linhas'];
} else {
    echo "Erro na comunicação: Código HTTP {$httpCode}";
}
```

---

### 🟡 Exemplo em JavaScript / Node.js (Fetch)

```javascript
const token = 'ISPSN_INTEGRATION_KEY_2026_SECRET_TOKEN';

async function sincronizarDocentes() {
  const response = await fetch('https://docentes.ispsn.app/index.php?api=v1_integracao_sync_docentes', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      docentes: [
        {
          nome: "Dr. Exemplo",
          email: "exemplo@ispsn.org",
          grau_academico: "Doutor",
          especialidade: "Fisioterapia",
          activo: 1
        }
      ]
    })
  });

  const result = await response.json();
  console.log('Resultado do Sync:', result);
}
```

---

## 5. Tabela de Códigos de Resposta HTTP

| Código | Significado | Descrição |
|---|---|---|
| `200 OK` | Sucesso | A operação foi concluída com êxito. |
| `207 Multi-Status` | Sucesso Parcial | Alguns itens foram gravados, mas outros continham erros de validação (detalhes no JSON). |
| `400 Bad Request` | Parâmetros Inválidos | Falta de parâmetro obrigatório ou JSON mal formatado. |
| `401 Unauthorized` | Não Autorizado | Token ausente, inválido ou expirado. |
| `404 Not Found` | Não Encontrado | Curso ou recurso especificado não existe. |
| `405 Method Not Allowed` | Método Inválido | Tentativa de usar GET em endpoint de POST ou vice-versa. |
| `500 Server Error` | Erro de Servidor | Falha inesperada de base de dados (transação revertida automaticamente). |
