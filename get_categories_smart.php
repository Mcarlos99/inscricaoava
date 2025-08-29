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

// Verificar se este polo usa navegação hierárquica
$useHierarchical = isset($poloConfig['hierarchical_navigation']) ? 
                   $poloConfig['hierarchical_navigation'] : false;

// NOVA CONFIGURAÇÃO: IDs de categorias que devem ser tratadas como cursos finais
// mesmo quando estão em modo hierárquico
// Primeiro tentar obter do polo específico, senão usar a lista global
$finalCourseCategories = [];
if (isset($poloConfig['final_course_categories']) && is_array($poloConfig['final_course_categories'])) {
    $finalCourseCategories = $poloConfig['final_course_categories'];
} elseif (isset($GLOBAL_FINAL_COURSE_CATEGORIES)) {
    $finalCourseCategories = $GLOBAL_FINAL_COURSE_CATEGORIES;
} else {
    // Fallback para os IDs conhecidos dos cursos técnicos
    $finalCourseCategories = [26, 27, 28, 29, 33];
}

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
    
    if ($useHierarchical) {
        // ========== MODO HIERÁRQUICO CORRIGIDO ==========
        // Filtrar categorias com o parent especificado
        $filteredCategories = [];
        
        foreach ($categories as $category) {
            if ($category['parent'] == $parentId && $category['visible'] == 1) {
                // CORREÇÃO: Verificar se esta categoria está na lista de cursos finais
                $isFinalCourse = in_array($category['id'], $finalCourseCategories);
                
                if ($isFinalCourse) {
                    // É um curso técnico final - tratar como curso, não como categoria pai
                    $hasSubcategories = false;
                    $isCourse = true;
                } else {
                    // Verificar se esta categoria tem subcategorias normalmente
                    $hasSubcategories = false;
                    foreach ($categories as $subcat) {
                        if ($subcat['parent'] == $category['id'] && $subcat['visible'] == 1) {
                            $hasSubcategories = true;
                            break;
                        }
                    }
                    $isCourse = !$hasSubcategories;
                }
                
                // Obter informações de preço
                $price_info = getPriceInfo($category['id'], $poloConfig);
                
                $filteredCategories[] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'description' => strip_tags($category['description'] ?? ''),
                    'coursecount' => $category['coursecount'],
                    'parent' => $category['parent'],
                    'has_subcategories' => $hasSubcategories,
                    'is_course' => $isCourse,
                    'is_final_course' => $isFinalCourse, // Flag adicional para debug
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
            'navigation_mode' => 'hierarchical',
            'parent_id' => $parentId,
            'breadcrumb' => $breadcrumb,
            'categories' => $filteredCategories,
            'is_root' => ($parentId == 0),
            'final_course_categories' => $finalCourseCategories // Para debug
        ]);
        
    } else {
        // ========== MODO SIMPLES (ORIGINAL) ==========
        // Filtrar apenas categorias principais (parent = 0) e visíveis
        $mainCategories = [];
        foreach ($categories as $category) {
            if ($category['parent'] == 0 && $category['visible'] == 1) {
                // Obter informações de preço da categoria para este polo específico
                $price_info = getPriceInfo($category['id'], $poloConfig);
                
                $mainCategories[] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'description' => strip_tags($category['description'] ?? ''),
                    'coursecount' => $category['coursecount'],
                    'parent' => $category['parent'],
                    'has_subcategories' => false,
                    'is_course' => true, // No modo simples, todas são tratadas como curso
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
            'navigation_mode' => 'simple',
            'parent_id' => 0,
            'breadcrumb' => [],
            'categories' => $mainCategories,
            'is_root' => true
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Função para obter informações de preço de uma categoria
 */
function getPriceInfo($categoryId, $poloConfig) {
    global $DEFAULT_COURSE_PRICES;
    
    // Verificar se o polo tem configuração de preços e se existe configuração específica para esta categoria
    if (isset($poloConfig['course_prices']) && isset($poloConfig['course_prices'][$categoryId])) {
        return $poloConfig['course_prices'][$categoryId];
    } 
    // Senão, verificar se existe um valor padrão para o polo
    else if (isset($poloConfig['course_prices']) && isset($poloConfig['course_prices']['default'])) {
        return $poloConfig['course_prices']['default'];
    }
    // Se tudo falhar, usar o valor padrão global
    else {
        return $DEFAULT_COURSE_PRICES['default'];
    }
}
?>