<?php
/**
 * Script para descobrir os IDs das categorias e subcategorias
 */

// Incluir arquivo de configuração dos polos
require_once('polo_config.php');

// Escolha o polo para debug
$polo = isset($_GET['polo']) ? $_GET['polo'] : 'breu-branco';

if (!isset($POLO_CONFIG[$polo])) {
    die("Polo '$polo' não encontrado na configuração");
}

$poloConfig = $POLO_CONFIG[$polo];
$moodleUrl = $poloConfig['moodle_url'];
$apiToken = $poloConfig['api_token'];

echo "<h1>Debug das Categorias - Polo: " . $poloConfig['name'] . "</h1>";

try {
    // Chamar API do Moodle para obter categorias
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
    
    // Organizar em hierarquia
    $mainCategories = [];
    $subcategories = [];
    
    foreach ($categories as $category) {
        if ($category['visible'] == 1) {
            if ($category['parent'] == 0) {
                $mainCategories[] = $category;
            } else {
                if (!isset($subcategories[$category['parent']])) {
                    $subcategories[$category['parent']] = [];
                }
                $subcategories[$category['parent']][] = $category;
            }
        }
    }
    
    echo "<style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .main-category { background-color: #e7f3ff; }
        .subcategory { background-color: #fff3cd; }
        .price-info { background-color: #d4edda; }
    </style>";
    
    echo "<h2>Estrutura Hierárquica das Categorias</h2>";
    
    foreach ($mainCategories as $mainCat) {
        echo "<table>";
        echo "<tr class='main-category'>";
        echo "<th>Tipo</th><th>ID</th><th>Nome</th><th>Descrição</th><th>Preço Atual</th>";
        echo "</tr>";
        
        // Categoria principal
        echo "<tr class='main-category'>";
        echo "<td><strong>CATEGORIA PRINCIPAL</strong></td>";
        echo "<td><strong>{$mainCat['id']}</strong></td>";
        echo "<td><strong>{$mainCat['name']}</strong></td>";
        echo "<td>" . strip_tags($mainCat['description']) . "</td>";
        
        // Verificar preço atual
        $currentPrice = 'Valor padrão';
        if (isset($poloConfig['course_prices'][$mainCat['id']])) {
            $priceInfo = $poloConfig['course_prices'][$mainCat['id']];
            $currentPrice = "R$ " . number_format($priceInfo['price'], 2, ',', '.') . " - " . $priceInfo['installments'];
        }
        echo "<td>$currentPrice</td>";
        echo "</tr>";
        
        // Subcategorias (cursos)
        if (isset($subcategories[$mainCat['id']])) {
            foreach ($subcategories[$mainCat['id']] as $subCat) {
                echo "<tr class='subcategory'>";
                echo "<td>📚 CURSO/SUBCATEGORIA</td>";
                echo "<td><strong>{$subCat['id']}</strong></td>";
                echo "<td>{$subCat['name']}</td>";
                echo "<td>" . strip_tags($subCat['description']) . "</td>";
                
                // Verificar preço atual da subcategoria
                $currentPrice = 'Valor padrão do polo';
                if (isset($poloConfig['course_prices'][$subCat['id']])) {
                    $priceInfo = $poloConfig['course_prices'][$subCat['id']];
                    $currentPrice = "R$ " . number_format($priceInfo['price'], 2, ',', '.') . " - " . $priceInfo['installments'];
                }
                echo "<td class='price-info'>$currentPrice</td>";
                echo "</tr>";
            }
        }
        
        echo "</table>";
        echo "<br>";
    }
    
    // Mostrar exemplo de configuração
    echo "<h2>Exemplo de Configuração no polo_config.php</h2>";
    echo "<pre style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "// Para alterar preços, adicione no polo_config.php:\n";
    echo "'course_prices' => array(\n";
    
    foreach ($mainCategories as $mainCat) {
        if (isset($subcategories[$mainCat['id']])) {
            foreach ($subcategories[$mainCat['id']] as $subCat) {
                echo "    {$subCat['id']} => array( // {$subCat['name']}\n";
                echo "        'price' => 2500.00,\n";
                echo "        'duration' => '18 meses',\n";
                echo "        'installments' => '18x de R\$ 138,89',\n";
                echo "    ),\n";
            }
        }
    }
    
    echo "    'default' => array(\n";
    echo "        'price' => 1500.00,\n";
    echo "        'duration' => '12 meses',\n";
    echo "        'installments' => '12x de R\$ 125,00',\n";
    echo "    ),\n";
    echo "),";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Erro: " . $e->getMessage() . "</div>";
}
?>

<p><a href="index.html">← Voltar para o formulário</a></p>