<?php
/**
 * SCRIPT DE DEBUG ATUALIZADO - TESTE MÍNIMO
 * Substitua o conteúdo do debug_moodle.php por este
 */

// Verificar autenticação
$admin_key = $_GET['key'] ?? '';
if ($admin_key !== 'admin123') {
    die('Acesso não autorizado');
}

require_once('../polo_config.php');

$polo = $_GET['polo'] ?? 'ava';

if (!isset($POLO_CONFIG[$polo])) {
    die("Polo '$polo' não encontrado");
}

$poloConfig = $POLO_CONFIG[$polo];
$moodleUrl = $poloConfig['moodle_url'];
$apiToken = $poloConfig['api_token'];

echo "<h1>Debug Detalhado - Polo: " . $poloConfig['name'] . "</h1>";

// Teste 1: Conexão básica
function testConnection($moodleUrl, $apiToken) {
    echo "<h2>1. Teste de Conectividade</h2>";
    
    try {
        $url = $moodleUrl . '/webservice/rest/server.php';
        $params = [
            'wstoken' => $apiToken,
            'wsfunction' => 'core_webservice_get_site_info',
            'moodlewsrestformat' => 'json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['sitename'])) {
            echo "✅ Conectado: " . $data['sitename'] . " (Versão: " . ($data['release'] ?? 'N/A') . ")<br>";
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        echo "❌ Erro: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Teste 2: Criação com APENAS parâmetros obrigatórios
function testMinimalUser($moodleUrl, $apiToken) {
    echo "<h2>2. Teste com Parâmetros MÍNIMOS</h2>";
    
    $username = 'test' . time();
    $email = $username . '@exemplo.com';
    
    $url = $moodleUrl . '/webservice/rest/server.php';
    $params = [
        'wstoken' => $apiToken,
        'wsfunction' => 'core_user_create_users',
        'moodlewsrestformat' => 'json',
        'users[0][username]' => $username,
        'users[0][password]' => 'MinhaSenh@123',
        'users[0][firstname]' => 'Nome',
        'users[0][lastname]' => 'Sobrenome',
        'users[0][email]' => $email
    ];
    
    echo "<strong>Enviando apenas:</strong><br>";
    foreach ($params as $key => $val) {
        if ($key != 'wstoken') echo "$key = $val<br>";
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo "<br><strong>Resposta completa:</strong><br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    
    if (isset($data[0]['id'])) {
        echo "<div style='color: green; font-weight: bold;'>✅ SUCESSO! Usuário criado com ID: " . $data[0]['id'] . "</div>";
        
        // Tentar deletar o usuário de teste
        deleteTestUser($moodleUrl, $apiToken, $data[0]['id']);
        return true;
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ FALHOU</div>";
        return false;
    }
}

// Teste 3: Diferentes variações de parâmetros
function testParameterVariations($moodleUrl, $apiToken) {
    echo "<h2>3. Testando Variações de Parâmetros</h2>";
    
    $variations = [
        'Com auth=manual' => [
            'users[0][auth]' => 'manual'
        ],
        'Com confirmed=1' => [
            'users[0][confirmed]' => 1
        ],
        'Com lang=en' => [
            'users[0][lang]' => 'en'
        ],
        'Com lang=pt_br' => [
            'users[0][lang]' => 'pt_br'
        ]
    ];
    
    foreach ($variations as $name => $extraParams) {
        echo "<h3>Teste: $name</h3>";
        
        $username = 'var' . time() . rand(100, 999);
        $email = $username . '@teste.com';
        
        $baseParams = [
            'wstoken' => $apiToken,
            'wsfunction' => 'core_user_create_users',
            'moodlewsrestformat' => 'json',
            'users[0][username]' => $username,
            'users[0][password]' => 'Teste123!@#',
            'users[0][firstname]' => 'Teste',
            'users[0][lastname]' => 'Variacao',
            'users[0][email]' => $email
        ];
        
        $params = array_merge($baseParams, $extraParams);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $moodleUrl . '/webservice/rest/server.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data[0]['id'])) {
            echo "✅ Funcionou! ID: " . $data[0]['id'] . "<br>";
            deleteTestUser($moodleUrl, $apiToken, $data[0]['id']);
        } else {
            echo "❌ Falhou: " . ($data['message'] ?? 'Erro desconhecido') . "<br>";
        }
        
        echo "<br>";
    }
}

// Função para deletar usuário de teste
function deleteTestUser($moodleUrl, $apiToken, $userId) {
    try {
        $url = $moodleUrl . '/webservice/rest/server.php';
        $params = [
            'wstoken' => $apiToken,
            'wsfunction' => 'core_user_delete_users',
            'moodlewsrestformat' => 'json',
            'userids[0]' => $userId
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        curl_exec($ch);
        curl_close($ch);
        
        echo "<small style='color: blue;'>ℹ️ Usuário de teste removido</small><br>";
        
    } catch (Exception $e) {
        echo "<small style='color: orange;'>⚠️ Não foi possível remover usuário de teste</small><br>";
    }
}

// Teste 4: Verificar configurações do Moodle
function checkMoodleSettings($moodleUrl, $apiToken) {
    echo "<h2>4. Configurações do Moodle</h2>";
    
    try {
        $url = $moodleUrl . '/webservice/rest/server.php';
        $params = [
            'wstoken' => $apiToken,
            'wsfunction' => 'core_webservice_get_site_info',
            'moodlewsrestformat' => 'json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['functions'])) {
            $hasCreateUsers = false;
            $hasEnrolUsers = false;
            
            foreach ($data['functions'] as $func) {
                if ($func['name'] == 'core_user_create_users') {
                    $hasCreateUsers = true;
                }
                if ($func['name'] == 'enrol_manual_enrol_users') {
                    $hasEnrolUsers = true;
                }
            }
            
            echo "Função core_user_create_users: " . ($hasCreateUsers ? "✅ Disponível" : "❌ Não disponível") . "<br>";
            echo "Função enrol_manual_enrol_users: " . ($hasEnrolUsers ? "✅ Disponível" : "❌ Não disponível") . "<br>";
        }
        
        echo "Idioma padrão: " . ($data['lang'] ?? 'N/A') . "<br>";
        echo "Versão Moodle: " . ($data['release'] ?? 'N/A') . "<br>";
        
    } catch (Exception $e) {
        echo "❌ Erro ao obter informações: " . $e->getMessage() . "<br>";
    }
}

// Executar todos os testes
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { background: #f0f0f0; padding: 10px; border-radius: 5px; }
    h2 { color: #333; border-bottom: 2px solid #ddd; }
    h3 { color: #666; }
</style>";

if (testConnection($moodleUrl, $apiToken)) {
    testMinimalUser($moodleUrl, $apiToken);
    testParameterVariations($moodleUrl, $apiToken);
    checkMoodleSettings($moodleUrl, $apiToken);
}

echo "<hr><p><a href='prematriculas.php?key=admin123'>← Voltar para Pré-matrículas</a></p>";
?>