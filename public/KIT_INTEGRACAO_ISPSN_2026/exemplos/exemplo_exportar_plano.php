<?php
/**
 * EXEMPLO DE INTEGRAÇÃO: Exportar Plano Homologado (Gestão Escolar ➔ Módulo Cobertura)
 * Linguagem: PHP (cURL nativo)
 */

$apiBaseUrl = 'https://docentes.ispsn.app/index.php';
$token      = 'ISPSN_INTEGRATION_KEY_2026_SECRET_TOKEN';
$cursoId    = 8; // ID de Fisioterapia (Exemplo)
$anoLectivo = '2026/27';

$endpoint = "{$apiBaseUrl}?api=v1_integracao_plano_export&curso_id={$cursoId}&ano=" . urlencode($anoLectivo);

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$token}",
        "Accept: application/json"
    ],
    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $resultado = json_decode($response, true);
    $data = $resultado['data'];

    echo "========================================================\n";
    echo " CURSO: {$data['curso']['nome']} ({$data['curso']['codigo']})\n";
    echo " ANO LECTIVO: {$data['ano_lectivo']}\n";
    echo " ESTADO: {$data['plano']['estado']}\n";
    echo " TOTAL DE ATRIBUIÇÕES: {$data['total_linhas']}\n";
    echo "========================================================\n\n";

    foreach ($data['atribuicoes'] as $atrib) {
        $docente = $atrib['docente_nome'] ?: 'Sem Docente';
        echo "[Ano {$atrib['ano_curricular']} · Semestre {$atrib['semestre']}] ";
        echo "{$atrib['disciplina_nome']} -> {$atrib['turma_nome']} -> {$docente}\n";
    }
} else {
    echo "Erro ao consultar API. Código HTTP: {$httpCode}\n";
    echo "Resposta: {$response}\n";
}
