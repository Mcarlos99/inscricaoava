<?php
// test_moodle_connection.php
// Script para testar a conexão com o Moodle

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir tipo de resposta como JSON
header('Content-Type: application/json');

// Verificar se os parâmetros necessários foram enviados
if (!isset($_POST['moodle_url']) || !isset($_POST['api_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parâmetros incompletos'
    ]);
    exit;
}

// Obter os parâmetros
$moodleUrl = $_POST['moodle_url'];
$apiToken = $_POST['api_token'];

// Verificar se o URL do Moodle é válido
if (!filter_var($moodleUrl, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'URL do Moodle inválido'
    ]);
    exit;
}

// Testar a conexão com o Moodle
try {
    // Tentar obter categorias como um teste simples
    $serverurl = $moodleUrl . '/webservice/rest/server.php';
    $params = [
        'wstoken' => $apiToken,
        'wsfunction' => 'core_course_get_categories',
        'moodlewsrestformat' => 'json'
    ];
    
    // Configurar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $serverurl . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout de 30 segundos
    
    // Executar a solicitação
    $response = curl_exec($ch);
    
    // Verificar erros de cURL
    if (curl_errno($ch)) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro de conexão: ' . curl_error($ch)
        ]);
        curl_close($ch);
        exit;
    }
    
    // Obter código de status HTTP
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Verificar se houve erro HTTP
    if ($httpCode != 200) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro HTTP: ' . $httpCode
        ]);
        exit;
    }
    
    // Decodificar a resposta JSON
    $data = json_decode($response, true);
    
    // Verificar se ocorreu algum erro do Moodle
    if (isset($data['exception'])) {
        echo json_encode([
            'success' => false,
            'message' => $data['message']
        ]);
        exit;
    }
    
    // Se chegou até aqui, a conexão foi bem-sucedida
    echo json_encode([
        'success' => true,
        'message' => 'Conexão bem-sucedida',
        'categories_count' => count($data)
    ]);
    
} catch (Exception $e) {
    // Capturar qualquer exceção
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>