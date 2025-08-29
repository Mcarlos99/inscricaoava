<?php
// admin_polo_config.php
// Página de administração para gerenciar configurações de polos e preços dos cursos

// Verificar autenticação de administrador
$admin_key = $_GET['key'] ?? '';
if ($admin_key !== 'admin123') {
    die('Acesso não autorizado');
}

// Carregar configuração atual
$configFile = '../polo_config.php';
$configExists = file_exists($configFile);

// Carregar categorias de cursos globais (para uso de fallback)
$categories = array();
if ($configExists) {
    include_once($configFile);
    
    // Categorias padrão para uso em qualquer lugar do código
    $categories = array(
        '1' => 'Técnico em Informática',
        '2' => 'Técnico em Enfermagem',
        '6' => 'Técnico em Segurança do trabalho'
    );
    
    // Vamos percorrer todos os polos configurados e adicionar
    // qualquer categoria que já esteja em uso em algum polo
    if (!empty($POLO_CONFIG)) {
        foreach ($POLO_CONFIG as $polo) {
            if (isset($polo['course_prices']) && is_array($polo['course_prices'])) {
                foreach ($polo['course_prices'] as $courseId => $courseData) {
                    // Pular a configuração "default" pois não é uma categoria real
                    if ($courseId !== 'default' && !isset($categories[$courseId])) {
                        $categories[$courseId] = "Curso ID: $courseId";
                    }
                }
            }
        }
    }
}

// Processar formulário se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    // Validar e processar dados do formulário
    $polos = [];
    
    foreach ($_POST['polo'] as $poloId => $poloData) {
        if (!empty($poloData['name']) && !empty($poloData['moodle_url']) && !empty($poloData['api_token'])) {
            $polos[$poloId] = [
                'name' => $poloData['name'],
                'moodle_url' => $poloData['moodle_url'],
                'api_token' => $poloData['api_token'],
                'description' => $poloData['description'] ?? '',
                'address' => $poloData['address'] ?? '',
                // IMPORTANTE: Preservar configuração de navegação hierárquica
                'hierarchical_navigation' => isset($_POST['hierarchical_navigation'][$poloId]) ? 
                    ($_POST['hierarchical_navigation'][$poloId] === '1' ? true : false) : 
                    (isset($POLO_CONFIG[$poloId]['hierarchical_navigation']) ? $POLO_CONFIG[$poloId]['hierarchical_navigation'] : false),
                'course_prices' => []
            ];
            
            // Processar preços de cursos para este polo
            if (isset($_POST['polo_courses'][$poloId]) && is_array($_POST['polo_courses'][$poloId])) {
                foreach ($_POST['polo_courses'][$poloId] as $courseId => $courseData) {
                    // Verificar se o curso tem preço e outras informações
                    if (!empty($courseData['price'])) {
                        $polos[$poloId]['course_prices'][$courseId] = [
                            'price' => (float)$courseData['price'],
                            'duration' => $courseData['duration'],
                            'installments' => $courseData['installments']
                        ];
                    }
                }
            }
            
            // Adicionar valor padrão para o polo (para cursos sem preço específico)
            if (isset($_POST['polo_default'][$poloId]) && !empty($_POST['polo_default'][$poloId]['price'])) {
                $polos[$poloId]['course_prices']['default'] = [
                    'price' => (float)$_POST['polo_default'][$poloId]['price'],
                    'duration' => $_POST['polo_default'][$poloId]['duration'],
                    'installments' => $_POST['polo_default'][$poloId]['installments']
                ];
            }
        }
    }
    
    // Adicionar novo polo se dados fornecidos
    if (!empty($_POST['new_polo_id']) && !empty($_POST['new_polo_name']) && 
        !empty($_POST['new_polo_moodle_url']) && !empty($_POST['new_polo_api_token'])) {
        
        $newPoloId = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['new_polo_id']));
        
        if (!empty($newPoloId)) {
            $polos[$newPoloId] = [
                'name' => $_POST['new_polo_name'],
                'moodle_url' => $_POST['new_polo_moodle_url'],
                'api_token' => $_POST['new_polo_api_token'],
                'description' => $_POST['new_polo_description'] ?? '',
                'address' => $_POST['new_polo_address'] ?? '',
                'hierarchical_navigation' => isset($_POST['new_polo_hierarchical']) ? 
                    ($_POST['new_polo_hierarchical'] === '1' ? true : false) : false,
                'course_prices' => []
            ];
            
            // Adicionar valor padrão para o novo polo
            if (!empty($_POST['new_polo_default_price'])) {
                $polos[$newPoloId]['course_prices']['default'] = [
                    'price' => (float)$_POST['new_polo_default_price'],
                    'duration' => $_POST['new_polo_default_duration'] ?? '6 meses',
                    'installments' => $_POST['new_polo_default_installments'] ?? '6x de R$ ' . (number_format((float)$_POST['new_polo_default_price'] / 6, 2, ',', '.'))
                ];
            }
        }
    }
    
    // Gerar arquivo de configuração
    $configContent = "<?php\n// Configuração dos polos e suas respectivas instâncias Moodle\n";
    $configContent .= "\$POLO_CONFIG = " . var_export($polos, true) . ";\n\n";
    $configContent .= "// Configuração padrão para qualquer polo que não tenha preços específicos\n";
    $configContent .= "\$DEFAULT_COURSE_PRICES = array(\n";
    $configContent .= "  // Valor padrão para todos os cursos em todos os polos que não têm configuração específica\n";
    $configContent .= "  'default' => array(\n";
    $configContent .= "    'price' => 297.00,\n";
    $configContent .= "    'duration' => '6 meses',\n";
    $configContent .= "    'installments' => '6x de R$ 49,50'\n";
    $configContent .= "  )\n";
    $configContent .= ");\n?>";
    
    if (file_put_contents($configFile, $configContent)) {
        $successMessage = 'Configuração salva com sucesso!';
        $configExists = true;
        // Recarregar configuração
        include($configFile);
    } else {
        $errorMessage = 'Erro ao salvar configuração. Verifique as permissões de escrita.';
    }
}

// Carregar configuração atual se existir
$POLO_CONFIG = [];
if ($configExists) {
    include($configFile);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração - Configuração de Polos e Preços</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .course-price-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .price-header {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .default-price-section {
            border-left: 3px solid #0d6efd;
            padding-left: 15px;
            margin-top: 20px;
        }
        .navigation-config {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .hierarchical-badge {
            background-color: #ffc107;
            color: #212529;
        }
        .simple-badge {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Administração - Configuração de Polos e Preços</h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Gerenciar Polos, Instâncias Moodle, Preços dos Cursos e Navegação
            </div>
            <div class="card-body">
                <form method="post">
                    <?php if (!empty($POLO_CONFIG)): ?>
                        <h5 class="mb-3">Polos Existentes</h5>
                        
                        <?php foreach ($POLO_CONFIG as $poloId => $polo): ?>
                            <?php $isHierarchical = isset($polo['hierarchical_navigation']) ? $polo['hierarchical_navigation'] : false; ?>
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="m-0"><?php echo $polo['name']; ?> (<?php echo $poloId; ?>)</h6>
                                    <div>
                                        <span class="badge <?php echo $isHierarchical ? 'hierarchical-badge' : 'simple-badge'; ?> me-2">
                                            <?php echo $isHierarchical ? 'Navegação Hierárquica' : 'Navegação Simples'; ?>
                                        </span>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removePolo('<?php echo $poloId; ?>')">
                                            <i class="fas fa-trash"></i> Remover
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Configuração de Navegação -->
                                    <div class="navigation-config">
                                        <h6><i class="fas fa-sitemap me-2"></i>Tipo de Navegação</h6>
                                        <p class="small text-muted mb-3">Escolha como os alunos navegarão pelas categorias de cursos neste polo.</p>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" 
                                                           name="hierarchical_navigation[<?php echo $poloId; ?>]" 
                                                           id="simple_<?php echo $poloId; ?>" 
                                                           value="0" 
                                                           <?php echo !$isHierarchical ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="simple_<?php echo $poloId; ?>">
                                                        <strong>Navegação Simples</strong><br>
                                                        <small>Categorias principais = Cursos diretos</small>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" 
                                                           name="hierarchical_navigation[<?php echo $poloId; ?>]" 
                                                           id="hierarchical_<?php echo $poloId; ?>" 
                                                           value="1" 
                                                           <?php echo $isHierarchical ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="hierarchical_<?php echo $poloId; ?>">
                                                        <strong>Navegação Hierárquica</strong><br>
                                                        <small>Categoria → Subcategoria/Curso</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nome do Polo:</label>
                                            <input type="text" class="form-control" name="polo[<?php echo $poloId; ?>][name]" value="<?php echo $polo['name']; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">ID do Polo:</label>
                                            <input type="text" class="form-control" value="<?php echo $poloId; ?>" disabled>
                                            <small class="text-muted">O ID não pode ser alterado</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">URL do Moodle:</label>
                                            <input type="url" class="form-control" name="polo[<?php echo $poloId; ?>][moodle_url]" value="<?php echo $polo['moodle_url']; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Token de API:</label>
                                            <input type="text" class="form-control" name="polo[<?php echo $poloId; ?>][api_token]" value="<?php echo $polo['api_token']; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Descrição:</label>
                                        <input type="text" class="form-control" name="polo[<?php echo $poloId; ?>][description]" value="<?php echo $polo['description'] ?? ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Endereço:</label>
                                        <input type="text" class="form-control" name="polo[<?php echo $poloId; ?>][address]" value="<?php echo $polo['address'] ?? ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="testConnection('<?php echo $poloId; ?>', '<?php echo $polo['moodle_url']; ?>', '<?php echo $polo['api_token']; ?>')">
                                            <i class="fas fa-plug"></i> Testar Conexão
                                        </button>
                                        <a href="../debug_categories.php?polo=<?php echo $poloId; ?>" class="btn btn-outline-info btn-sm" target="_blank">
                                            <i class="fas fa-search"></i> Ver IDs das Categorias
                                        </a>
                                        <span id="connection-status-<?php echo $poloId; ?>"></span>
                                    </div>
                                    
                                    <!-- Seção de preços dos cursos -->
                                    <div class="course-price-section">
                                        <div class="price-header">
                                            <h5 class="m-0"><i class="fas fa-tags me-2"></i> Preços dos Cursos para o Polo <?php echo $polo['name']; ?></h5>
                                        </div>
                                        
                                        <!-- Valor padrão para o polo -->
                                        <div class="default-price-section">
                                            <h6>Valor padrão para cursos sem preço específico</h6>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Preço (R$):</label>
                                                    <input type="number" step="0.01" min="0" class="form-control" 
                                                           name="polo_default[<?php echo $poloId; ?>][price]" 
                                                           value="<?php echo isset($polo['course_prices']['default']) ? $polo['course_prices']['default']['price'] : '297.00'; ?>">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Duração:</label>
                                                    <input type="text" class="form-control" 
                                                           name="polo_default[<?php echo $poloId; ?>][duration]" 
                                                           value="<?php echo isset($polo['course_prices']['default']) ? $polo['course_prices']['default']['duration'] : '6 meses'; ?>">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Parcelamento:</label>
                                                    <input type="text" class="form-control" 
                                                           name="polo_default[<?php echo $poloId; ?>][installments]" 
                                                           value="<?php echo isset($polo['course_prices']['default']) ? $polo['course_prices']['default']['installments'] : '6x de R$ 49,50'; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Preços específicos por curso -->
                                        <?php
                                        // Obter categorias específicas para este polo
                                        $polo_categories = array();
                                        
                                        // Primeiro define as categorias padrão caso a API falhe
                                        $polo_categories = array(
                                            '1' => 'Técnico em Informática',
                                            '2' => 'Técnico em Enfermagem',
                                            '6' => 'Técnico em Segurança do trabalho'
                                        );
                                        
                                        // Tenta buscar categorias da API do Moodle para este polo específico
                                        if (!empty($polo['moodle_url']) && !empty($polo['api_token'])) {
                                            $moodleUrl = $polo['moodle_url'];
                                            $apiToken = $polo['api_token'];
                                            
                                            $serverurl = $moodleUrl . '/webservice/rest/server.php';
                                            $params = [
                                                'wstoken' => $apiToken,
                                                'wsfunction' => 'core_course_get_categories',
                                                'moodlewsrestformat' => 'json'
                                            ];
                                            
                                            try {
                                                $ch = curl_init();
                                                curl_setopt($ch, CURLOPT_URL, $serverurl . '?' . http_build_query($params));
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                                                curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout de 5 segundos
                                                $response = curl_exec($ch);
                                                
                                                if (!curl_errno($ch)) {
                                                    $categoriesData = json_decode($response, true);
                                                    
                                                    if (is_array($categoriesData) && !isset($categoriesData['exception'])) {
                                                        // Limpa as categorias padrão se encontramos algumas na API
                                                        $api_categories = array();
                                                        
                                                        foreach ($categoriesData as $category) {
                                                            if ($category['visible'] == 1) {
                                                                $api_categories[$category['id']] = $category['name'];
                                                            }
                                                        }
                                                        
                                                        // Só substitui se encontrou categorias na API
                                                        if (!empty($api_categories)) {
                                                            $polo_categories = $api_categories;
                                                        }
                                                    }
                                                }
                                                
                                                curl_close($ch);
                                            } catch (Exception $e) {
                                                // Em caso de erro, mantém as categorias padrão
                                            }
                                        }
                                        
                                        // Se já houver preços configurados para categorias que não estão na lista atual,
                                        // devemos incluir essas categorias para não perder as configurações
                                        if (isset($polo['course_prices']) && is_array($polo['course_prices'])) {
                                            foreach ($polo['course_prices'] as $courseId => $courseData) {
                                                // Ignorar a categoria "default" pois não é uma categoria real
                                                if ($courseId !== 'default' && !isset($polo_categories[$courseId])) {
                                                    // Tenta encontrar o nome da categoria nas categorias globais
                                                    if (isset($categories[$courseId])) {
                                                        $polo_categories[$courseId] = $categories[$courseId];
                                                    } else {
                                                        // Se não encontrou, usa um nome genérico
                                                        $polo_categories[$courseId] = "Curso ID: $courseId";
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if (!empty($polo_categories)):
                                        ?>
                                            <h6 class="mt-4">Preços específicos por curso/categoria</h6>
                                            <p class="text-muted">Deixe o preço em branco para usar o valor padrão do polo. Use o botão "Ver IDs das Categorias" para descobrir os IDs corretos.</p>
                                            
                                            <?php foreach ($polo_categories as $categoryId => $categoryName): ?>
                                                <div class="card mb-3">
                                                    <div class="card-header bg-light">
                                                        <strong><?php echo htmlspecialchars($categoryName); ?> (ID: <?php echo $categoryId; ?>)</strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Preço (R$):</label>
                                                                <input type="number" step="0.01" min="0" class="form-control price-input" 
                                                                       name="polo_courses[<?php echo $poloId; ?>][<?php echo $categoryId; ?>][price]" 
                                                                       value="<?php echo isset($polo['course_prices'][$categoryId]) ? $polo['course_prices'][$categoryId]['price'] : ''; ?>">
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Duração:</label>
                                                                <input type="text" class="form-control duration-input" 
                                                                       name="polo_courses[<?php echo $poloId; ?>][<?php echo $categoryId; ?>][duration]" 
                                                                       value="<?php echo isset($polo['course_prices'][$categoryId]) ? $polo['course_prices'][$categoryId]['duration'] : '6 meses'; ?>">
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Parcelamento:</label>
                                                                <input type="text" class="form-control installments-input" 
                                                                       name="polo_courses[<?php echo $poloId; ?>][<?php echo $categoryId; ?>][installments]" 
                                                                       value="<?php echo isset($polo['course_prices'][$categoryId]) ? $polo['course_prices'][$categoryId]['installments'] : ''; ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="alert alert-warning mt-3">
                                                <i class="fas fa-info-circle me-2"></i> Não foi possível obter a lista de cursos para este polo. Use o botão "Ver IDs das Categorias" para descobrir os IDs.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3">Adicionar Novo Polo</h5>
                    <div class="card">
                        <div class="card-body">
                            <!-- Configuração de navegação para novo polo -->
                            <div class="navigation-config">
                                <h6><i class="fas fa-sitemap me-2"></i>Tipo de Navegação</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" 
                                                   name="new_polo_hierarchical" 
                                                   id="new_simple" 
                                                   value="0" checked>
                                            <label class="form-check-label" for="new_simple">
                                                <strong>Navegação Simples</strong>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" 
                                                   name="new_polo_hierarchical" 
                                                   id="new_hierarchical" 
                                                   value="1">
                                            <label class="form-check-label" for="new_hierarchical">
                                                <strong>Navegação Hierárquica</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nome do Polo:</label>
                                    <input type="text" class="form-control" name="new_polo_name" placeholder="Ex: Nova Cidade">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ID do Polo (somente letras, números e hífen):</label>
                                    <input type="text" class="form-control" name="new_polo_id" placeholder="Ex: nova-cidade">
                                    <small class="text-muted">Este ID será usado nas URLs e no sistema</small>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">URL do Moodle:</label>
                                    <input type="url" class="form-control" name="new_polo_moodle_url" placeholder="https://novacidade.imepedu.com.br">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Token de API:</label>
                                    <input type="text" class="form-control" name="new_polo_api_token" placeholder="Token de API do Moodle">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Descrição:</label>
                                <input type="text" class="form-control" name="new_polo_description" placeholder="Polo de Educação Superior de Nova Cidade">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Endereço:</label>
                                <input type="text" class="form-control" name="new_polo_address" placeholder="Rua Principal, 123 - Centro, Nova Cidade - PA">
                            </div>
                            
                            <!-- Preço padrão para o novo polo -->
                            <div class="default-price-section">
                                <h6>Valor padrão para cursos deste polo</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Preço padrão (R$):</label>
                                        <input type="number" step="0.01" min="0" class="form-control" 
                                               name="new_polo_default_price" value="297.00">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Duração padrão:</label>
                                        <input type="text" class="form-control" 
                                               name="new_polo_default_duration" value="6 meses">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Parcelamento padrão:</label>
                                        <input type="text" class="form-control" 
                                               name="new_polo_default_installments" value="6x de R$ 49,50">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" name="save_config" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Configurações
                        </button>
                        <a href="configure_navigation.php?key=<?php echo htmlspecialchars($admin_key); ?>" class="btn btn-outline-info">
                            <i class="fas fa-sitemap"></i> Configurar Navegação Avançada
                        </a>
                        <a href="prematriculas.php?key=<?php echo htmlspecialchars($admin_key); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-users"></i> Ver Pré-matrículas
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Função para remover polo (apenas na interface, confirmação necessária)
        function removePolo(poloId) {
            if (confirm('Tem certeza que deseja remover este polo? Esta ação não pode ser desfeita.')) {
                // Usar jQuery para simplificar a seleção
                $(".card-header:contains('" + poloId + "')").closest('.card').remove();
            }
        }
        
        // Função para testar conexão com o Moodle
        function testConnection(poloId, moodleUrl, apiToken) {
            const statusElement = document.getElementById(`connection-status-${poloId}`);
            statusElement.innerHTML = '<span class="text-warning">Testando conexão...</span>';
            
            // Criar um formulário temporário para enviar a solicitação
            const formData = new FormData();
            formData.append('moodle_url', moodleUrl);
            formData.append('api_token', apiToken);
            
            fetch('../test_moodle_connection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusElement.innerHTML = '<span class="text-success">Conexão bem-sucedida!</span>';
                } else {
                    statusElement.innerHTML = `<span class="text-danger">Falha na conexão: ${data.message}</span>`;
                }
            })
            .catch(error => {
                statusElement.innerHTML = '<span class="text-danger">Erro ao testar conexão</span>';
                console.error('Erro:', error);
            });
        }
        
        // Função para calcular o valor da parcela automaticamente
        function calcInstallments(priceInput, installmentsInput, numInstallments) {
            const price = parseFloat(priceInput.value) || 0;
            if (price > 0) {
                const installmentValue = (price / numInstallments).toFixed(2).replace('.', ',');
                installmentsInput.value = `${numInstallments}x de R$ ${installmentValue}`;
            }
        }
        
        // Adicionar eventos de alteração de preço
        document.addEventListener('DOMContentLoaded', function() {
            // Para cada input de preço, adicionar um evento para calcular parcelas
            document.querySelectorAll('.price-input').forEach(input => {
                input.addEventListener('change', function() {
                    const container = this.closest('.row');
                    if (container) {
                        const installmentsInput = container.querySelector('.installments-input');
                        if (installmentsInput) {
                            // Obter o número de parcelas do texto do input de duração
                            const durationInput = container.querySelector('.duration-input');
                            const durationText = durationInput.value;
                            const numMonths = parseInt(durationText) || 6;
                            
                            calcInstallments(this, installmentsInput, numMonths);
                        }
                    }
                });
            });
            
            // Para cada input de duração, adicionar um evento para recalcular parcelas
            document.querySelectorAll('.duration-input').forEach(input => {
                input.addEventListener('change', function() {
                    const container = this.closest('.row');
                    if (container) {
                        const priceInput = container.querySelector('.price-input');
                        const installmentsInput = container.querySelector('.installments-input');
                        if (priceInput && installmentsInput) {
                            // Extrair o número de meses da duração
                            const durationText = this.value;
                            const numMonths = parseInt(durationText) || 6;
                            
                            calcInstallments(priceInput, installmentsInput, numMonths);
                        }
                    }
                });
            });
            
            // Atualizar badges quando a navegação mudar
            document.querySelectorAll('input[name^="hierarchical_navigation"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const badge = this.closest('.card').querySelector('.badge');
                    if (this.value === '1') {
                        badge.textContent = 'Navegação Hierárquica';
                        badge.className = 'badge hierarchical-badge me-2';
                    } else {
                        badge.textContent = 'Navegação Simples';
                        badge.className = 'badge simple-badge me-2';
                    }
                });
            });
        });
    </script>
</body>
</html>