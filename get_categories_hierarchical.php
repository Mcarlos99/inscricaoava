<?php
// Incluir arquivo de configuração dos polos
require_once('polo_config.php');

header('Content-Type: application/json');

// Receber parâmetros
$polo = isset($_GET['polo']) ? $_GET['polo'] : '';
$parentId = isset($_GET['parent']) ? (int)$_GET['parent'] : 0;

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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Erro de conexão: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    $categories = json_decode($response, true);
    
    if (isset($categories['exception'])) {
        throw new Exception($categories['message']);
    }
    
    // Filtrar categorias com o parent especificado
    $filteredCategories = [];
    
    foreach ($categories as $category) {
        if ($category['parent'] == $parentId && $category['visible'] == 1) {
            // Verificar se esta categoria tem subcategorias (cursos)
            $hasSubcategories = false;
            foreach ($categories as $subcat) {
                if ($subcat['parent'] == $category['id'] && $subcat['visible'] == 1) {
                    $hasSubcategories = true;
                    break;
                }
            }
            
            // Obter informações de preço
            $price_info = null;
            if (isset($poloConfig['course_prices']) && isset($poloConfig['course_prices'][$category['id']])) {
                $price_info = $poloConfig['course_prices'][$category['id']];
            } else if (isset($poloConfig['course_prices']) && isset($poloConfig['course_prices']['default'])) {
                $price_info = $poloConfig['course_prices']['default'];
            } else {
                $price_info = $DEFAULT_COURSE_PRICES['default'];
            }
            
            $filteredCategories[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'description' => strip_tags($category['description'] ?? ''),
                'coursecount' => $category['coursecount'],
                'parent' => $category['parent'],
                'has_subcategories' => $hasSubcategories,
                'is_course' => !$hasSubcategories, // Se não tem subcategorias, é um curso
                'price' => $price_info['price'],
                'duration' => $price_info['duration'],
                'installments' => $price_info['installments']
            ];
        }
    }
    
    // Construir breadcrumb para navegação
    $breadcrumb = [];
    if ($parentId > 0) {
        $currentParent = $parentId;
        while ($currentParent > 0) {
            foreach ($categories as $cat) {
                if ($cat['id'] == $currentParent) {
                    array_unshift($breadcrumb, [
                        'id' => $cat['id'],
                        'name' => $cat['name'],
                        'parent' => $cat['parent']
                    ]);
                    $currentParent = $cat['parent'];
                    break;
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'polo' => $polo,
        'polo_name' => $poloConfig['name'],
        'moodle_url' => $moodleUrl,
        'parent_id' => $parentId,
        'breadcrumb' => $breadcrumb,
        'categories' => $filteredCategories,
        'is_root' => ($parentId == 0)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>