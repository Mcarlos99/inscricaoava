<?php
// Incluir arquivo de configuração dos polos
require_once('polo_config.php');

header('Content-Type: application/json');

// Receber polo selecionado
$polo = isset($_GET['polo']) ? $_GET['polo'] : '';

if (empty($polo)) {
    echo json_encode(['success' => false, 'message' => 'Polo não especificado']);
    exit;
}

// Verificar se o polo existe na configuração
if (!isset($POLO_CONFIG[$polo])) {
    echo json_encode(['success' => false, 'message' => 'Polo não encontrado na configuração']);
    exit;
}

// Obter configuração do polo selecionado
$poloConfig = $POLO_CONFIG[$polo];

try {
    // Chamar API do Moodle para obter categorias
    $moodleUrl = $poloConfig['moodle_url'];
    $apiToken = $poloConfig['api_token'];
    
    $serverurl = $moodleUrl . '/webservice/rest/server.php';
    $params = [
        'wstoken' => $apiToken,
        'wsfunction' => 'core_course_get_categories',
        'moodlewsrestformat' => 'json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $serverurl . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Erro de conexão: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    $categories = json_decode($response, true);
    
    if (isset($categories['exception'])) {
        throw new Exception($categories['message']);
    }
    
    // Filtrar apenas categorias principais (parent = 0) e visíveis
    $mainCategories = [];
    foreach ($categories as $category) {
        if ($category['parent'] == 0 && $category['visible'] == 1) {
            // Obter informações de preço da categoria para este polo específico
            $price_info = null;
            
            // Verificar se o polo tem configuração de preços e se existe configuração específica para esta categoria
            if (isset($poloConfig['course_prices']) && isset($poloConfig['course_prices'][$category['id']])) {
                $price_info = $poloConfig['course_prices'][$category['id']];
            } 
            // Senão, verificar se existe um valor padrão para o polo
            else if (isset($poloConfig['course_prices']) && isset($poloConfig['course_prices']['default'])) {
                $price_info = $poloConfig['course_prices']['default'];
            }
            // Se tudo falhar, usar o valor padrão global
            else {
                $price_info = $DEFAULT_COURSE_PRICES['default'];
            }
            
            $mainCategories[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'description' => strip_tags($category['description'] ?? ''),
                'coursecount' => $category['coursecount'],
                'price' => $price_info['price'],
                'duration' => $price_info['duration'],
                'installments' => $price_info['installments']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'polo' => $polo,
        'polo_name' => $poloConfig['name'],
        'moodle_url' => $moodleUrl,
        'categories' => $mainCategories
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>