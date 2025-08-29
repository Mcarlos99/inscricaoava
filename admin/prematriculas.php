<?php
/**
 * ADMIN/PREMATRICULAS.PHP - VERSÃO COMPLETA COM GERENCIAMENTO DE CREDENCIAIS
 * Parte 1/4: Configurações, Conexão, Funções Auxiliares e Processamento Base
 * 
 * Funcionalidades desta versão:
 * - Armazenamento automático de credenciais do Moodle
 * - Visualização de credenciais nos detalhes
 * - Reenvio de credenciais por email
 * - Regeneração de credenciais para matrículas antigas
 * - Interface melhorada com indicadores visuais
 * - Logs detalhados de todas as ações
 */

// Verificar se o usuário está logado como administrador
$admin_key = $_GET['key'] ?? '';
if ($admin_key !== 'admin123') {
    die('Acesso não autorizado');
}

// Configurações do banco de dados - ajuste conforme seu ambiente
$db_host = 'localhost';
$db_name = 'inscricaoavadb';
$db_user = 'inscricaoavauser';
$db_pass = '05hsqwjG8vLsIVBvQ7Iu';

// Incluir arquivo de configuração dos polos
require_once('../polo_config.php');

// Variáveis globais para mensagens e controle
$message = '';
$messageType = 'info'; // success, danger, warning, info

// ============================================================================
// FUNÇÕES AUXILIARES BÁSICAS
// ============================================================================

/**
 * Função genérica para chamar a API do Moodle com melhor tratamento de erros
 */
function callMoodleAPI($url, $params) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'IMEP-EDU-Admin/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Erro de conexão cURL: ' . $error);
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Erro HTTP: $httpCode - Resposta: " . substr($response, 0, 200));
    }
    
    // Tentar decodificar o JSON
    $decodedResponse = json_decode($response, true);
    
    // Se a resposta for um JSON válido, retornar o array decodificado
    if (json_last_error() === JSON_ERROR_NONE) {
        // Verificar se há erro na resposta da API do Moodle
        if (isset($decodedResponse['exception'])) {
            throw new Exception('Erro da API Moodle: ' . $decodedResponse['message'] . 
                              (isset($decodedResponse['debuginfo']) ? ' - ' . $decodedResponse['debuginfo'] : ''));
        }
        return $decodedResponse;
    }
    
    // Se não conseguir decodificar JSON, retornar como string
    return $response;
}

/**
 * Gerar senha segura que atenda aos requisitos do Moodle
 */
function generatePassword($length = 12) {
    // Definir conjuntos de caracteres
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $special = '!@#$%&*-_=+?';
    
    // Garantir comprimento mínimo de 8 caracteres
    $length = max(8, $length);
    
    // Inicializar a senha com pelo menos um caractere de cada tipo
    $password = '';
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    // Adicionar mais um caractere especial para garantir robustez
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    // Completar com caracteres aleatórios
    $allChars = $lowercase . $uppercase . $numbers . $special;
    for ($i = strlen($password); $i < $length; $i++) {
        $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }
    
    // Embaralhar para distribuir os tipos de caracteres
    $password = str_shuffle($password);
    
    // Verificação final dos requisitos
    if (!preg_match('/[a-z]/', $password) || 
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[0-9]/', $password) || 
        !preg_match('/[^a-zA-Z0-9]/', $password)) {
        // Se não atender aos requisitos, tentar novamente
        return generatePassword($length);
    }
    
    return $password;
}

/**
 * Registrar ações em log para auditoria
 */
function logAction($action, $details, $prematriculaId = null, $success = true) {
    $logFile = 'admin_actions.log';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'ERROR';
    $userId = $_SESSION['admin_id'] ?? 'admin';
    
    $logEntry = "[$timestamp] [$status] [$action] User: $userId";
    if ($prematriculaId) {
        $logEntry .= " | Pré-matrícula ID: $prematriculaId";
    }
    $logEntry .= " | Details: $details\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Verificar se nome de usuário já existe no Moodle
 */
function checkUsernameExists($username, $moodleUrl, $apiToken) {
    try {
        $serverurl = $moodleUrl . '/webservice/rest/server.php';
        $params = [
            'wstoken' => $apiToken,
            'wsfunction' => 'core_user_get_users',
            'moodlewsrestformat' => 'json',
            'criteria[0][key]' => 'username',
            'criteria[0][value]' => $username
        ];
        
        $response = callMoodleAPI($serverurl, $params);
        
        return isset($response['users']) && !empty($response['users']);
    } catch (Exception $e) {
        logAction('check_username', "Erro ao verificar usuário $username: " . $e->getMessage(), null, false);
        return false;
    }
}

/**
 * Verificar se usuário existe no Moodle por email
 */
function checkExistingMoodleUser($email, $moodleUrl, $apiToken) {
    try {
        $serverurl = $moodleUrl . '/webservice/rest/server.php';
        $params = [
            'wstoken' => $apiToken,
            'wsfunction' => 'core_user_get_users',
            'moodlewsrestformat' => 'json',
            'criteria[0][key]' => 'email',
            'criteria[0][value]' => $email
        ];
        
        $response = callMoodleAPI($serverurl, $params);
        
        if (isset($response['users']) && !empty($response['users'])) {
            return [
                'exists' => true,
                'user_data' => $response['users'][0]
            ];
        } else {
            return ['exists' => false];
        }
    } catch (Exception $e) {
        logAction('check_existing_user', "Erro ao verificar usuário por email $email: " . $e->getMessage(), null, false);
        return ['exists' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Atualizar senha do usuário no Moodle
 */
function updateMoodleUserPassword($userId, $newPassword, $moodleUrl, $apiToken) {
    try {
        $serverurl = $moodleUrl . '/webservice/rest/server.php';
        $params = [
            'wstoken' => $apiToken,
            'wsfunction' => 'core_user_update_users',
            'moodlewsrestformat' => 'json',
            'users[0][id]' => $userId,
            'users[0][password]' => $newPassword
        ];
        
        $response = callMoodleAPI($serverurl, $params);
        
        // Se chegou até aqui sem exception, assumir sucesso
        logAction('update_password', "Senha atualizada para usuário ID $userId", null, true);
        return true;
        
    } catch (Exception $e) {
        logAction('update_password', "Erro ao atualizar senha para usuário ID $userId: " . $e->getMessage(), null, false);
        return false;
    }
}

/**
 * Obter estatísticas rápidas sobre credenciais
 */
function getCredentialsStats($pdo) {
    try {
        // Total de matrículas aprovadas
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prematriculas WHERE status = 'approved'");
        $stmt->execute();
        $totalApproved = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Matrículas com credenciais
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM prematriculas 
            WHERE status = 'approved' 
            AND moodle_username IS NOT NULL 
            AND moodle_password IS NOT NULL
        ");
        $stmt->execute();
        $withCredentials = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Reenvios nas últimas 24h
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM prematriculas 
            WHERE last_credentials_resend >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        $recentResends = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'total_approved' => $totalApproved,
            'with_credentials' => $withCredentials,
            'without_credentials' => $totalApproved - $withCredentials,
            'recent_resends' => $recentResends,
            'percentage_with_credentials' => $totalApproved > 0 ? round(($withCredentials / $totalApproved) * 100, 1) : 0
        ];
        
    } catch (Exception $e) {
        return [
            'total_approved' => 0,
            'with_credentials' => 0,
            'without_credentials' => 0,
            'recent_resends' => 0,
            'percentage_with_credentials' => 0,
            'error' => $e->getMessage()
        ];
    }
}

// ============================================================================
// PROCESSAMENTO DE AÇÕES ESPECIAIS
// ============================================================================

// Ação para exportar dados (GET)
if (isset($_GET['action']) && $_GET['action'] === 'export_credentials') {
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        exportStudentsWithCredentials('csv', $pdo);
    } catch (Exception $e) {
        $message = "Erro ao exportar dados: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Ação para obter estatísticas em JSON (GET)
if (isset($_GET['action']) && $_GET['action'] === 'get_stats') {
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stats = getCredentialsStats($pdo);
        header('Content-Type: application/json');
        echo json_encode($stats);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// ============================================================================
// INICIALIZAÇÃO DA CONEXÃO COM BANCO
// ============================================================================

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erro de conexão com o banco de dados: ' . $e->getMessage());
}

/**
 * ADMIN/PREMATRICULAS.PHP - PARTE 2/4
 * Funções de Criação e Gerenciamento de Usuários no Moodle
 */

// ============================================================================
// FUNÇÕES DE INTEGRAÇÃO COM MOODLE
// ============================================================================

/**
 * Função principal melhorada - createMoodleUserAndEnroll
 */
function createMoodleUserAndEnroll($prematricula, $POLO_CONFIG, $pdo) {
    try {
        // Verificar configuração do polo
        if (!isset($POLO_CONFIG[$prematricula['polo_id']])) {
            return ['success' => false, 'message' => 'Polo não encontrado na configuração'];
        }
        
        $poloConfig = $POLO_CONFIG[$prematricula['polo_id']];
        $MOODLE_URL = $poloConfig['moodle_url'];
        $API_TOKEN = $poloConfig['api_token'];
        
        logAction('create_user_start', "Iniciando para {$prematricula['email']} no polo {$prematricula['polo_id']}", $prematricula['id']);
        
        // Verificar se usuário já existe
        $existingUser = checkExistingMoodleUser($prematricula['email'], $MOODLE_URL, $API_TOKEN);
        
        if ($existingUser['exists']) {
            // Usuário existe - usar credenciais existentes
            $username = $existingUser['user_data']['username'];
            $userId = $existingUser['user_data']['id'];
            $password = generatePassword(12);
            
            // Tentar atualizar senha
            updateMoodleUserPassword($userId, $password, $MOODLE_URL, $API_TOKEN);
            
            logAction('user_exists', "Reutilizando usuário: $username (ID: $userId)", $prematricula['id']);
            $userAction = 'updated';
            
        } else {
            // Criar novo usuário
            $username = createUniqueUsername($prematricula['email'], $MOODLE_URL, $API_TOKEN);
            $password = generatePassword(12);
            
            logAction('creating_new_user', "Criando usuário: $username", $prematricula['id']);
            
            $userId = createMoodleUser(
                $username, 
                $password, 
                $prematricula['first_name'], 
                $prematricula['last_name'], 
                $prematricula['email'], 
                [], // Sem campos customizados por enquanto
                $MOODLE_URL, 
                $API_TOKEN
            );
            
            if (!$userId) {
                return ['success' => false, 'message' => 'Falha ao criar usuário no Moodle'];
            }
            
            $userAction = 'created';
        }
        
        // Tentar matricular nos cursos
        $enrolledCourses = [];
        $enrollmentErrors = [];
        
        try {
            $courses = getAllCoursesInCategory($prematricula['category_id'], $MOODLE_URL, $API_TOKEN);
            
            foreach ($courses as $course) {
                if (enrollUserInCourse($userId, $course['id'], $MOODLE_URL, $API_TOKEN)) {
                    $enrolledCourses[] = $course['name'];
                } else {
                    $enrollmentErrors[] = $course['name'];
                }
            }
        } catch (Exception $e) {
            logAction('enrollment_category_error', "Erro ao obter cursos da categoria: " . $e->getMessage(), $prematricula['id'], false);
            // Continuar mesmo se não conseguir matricular em cursos
        }

        // Salvar credenciais no banco
        try {
            $stmt = $pdo->prepare("
                UPDATE prematriculas SET 
                    moodle_username = ?,
                    moodle_password = ?,
                    moodle_user_id = ?,
                    moodle_url = ?,
                    credentials_sent_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $username,
                $password,
                $userId,
                $MOODLE_URL,
                $prematricula['id']
            ]);
            
            logAction('credentials_saved', "Credenciais salvas: $username", $prematricula['id']);
            
        } catch (PDOException $e) {
            logAction('credentials_save_error', $e->getMessage(), $prematricula['id'], false);
        }
        
        return [
            'success' => true,
            'username' => $username,
            'password' => $password,
            'moodle_url' => $MOODLE_URL,
            'courses_count' => count($enrolledCourses),
            'user_id' => $userId,
            'existing_user' => $existingUser['exists'] ?? false,
            'enrolled_courses' => $enrolledCourses,
            'enrollment_errors' => $enrollmentErrors,
            'user_action' => $userAction
        ];
        
    } catch (Exception $e) {
        logAction('user_creation_error', $e->getMessage(), $prematricula['id'] ?? null, false);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Criar usuário no Moodle - VERSÃO QUE FUNCIONA NO SEU SISTEMA
 */
function createMoodleUser($username, $password, $firstName, $lastName, $email, $customFields, $moodleUrl, $apiToken) {
    try {
        // Validação dos dados obrigatórios
        if (empty($username) || empty($password) || empty($firstName) || empty($lastName) || empty($email)) {
            throw new Exception('Dados obrigatórios não fornecidos para criação do usuário');
        }
        
        // Sanitização rigorosa
        $username = strtolower(trim($username));
        $username = preg_replace('/[^a-z0-9]/', '', $username);
        
        // Garantir tamanho adequado
        if (strlen($username) < 3) {
            $username = $username . 'user';
        }
        if (strlen($username) > 30) {
            $username = substr($username, 0, 30);
        }
        
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $email = trim(strtolower($email));
        
        // Validações
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido: ' . $email);
        }
        
        if (strlen($username) < 3 || strlen($username) > 30) {
            throw new Exception('Username deve ter entre 3 e 30 caracteres');
        }
        
        $url = $moodleUrl . '/webservice/rest/server.php';
        
        // USAR APENAS PARÂMETROS QUE SEU MOODLE ACEITA
        $params = [
            'wstoken' => $apiToken,
            'wsfunction' => 'core_user_create_users',
            'moodlewsrestformat' => 'json',
            'users[0][username]' => $username,
            'users[0][password]' => $password,
            'users[0][firstname]' => $firstName,
            'users[0][lastname]' => $lastName,
            'users[0][email]' => $email,
            'users[0][auth]' => 'manual',  // ✅ Este funciona
            'users[0][lang]' => 'pt_br'    // ✅ Este funciona
            // ❌ NÃO usar 'confirmed' - causa o erro!
        ];
        
        // Adicionar telefone se fornecido
        if (isset($customFields['phone']) && !empty($customFields['phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $customFields['phone']);
            if (strlen($phone) >= 10) {
                $params['users[0][phone1]'] = $phone;
            }
        }
        
        // Log da tentativa
        logAction('moodle_create_user_attempt', "Criando usuário: $username para email: $email", null);
        
        // Fazer a chamada para a API
        $response = callMoodleAPI($url, $params);
        
        // Verificar resposta
        if (isset($response[0]['id']) && is_numeric($response[0]['id'])) {
            $userId = $response[0]['id'];
            logAction('moodle_create_user_success', "Usuário criado com sucesso: $username (ID: $userId)", null);
            return $userId;
        }
        
        // Verificar warnings
        if (isset($response[0]['warnings']) && !empty($response[0]['warnings'])) {
            $warnings = [];
            foreach ($response[0]['warnings'] as $warning) {
                $warnings[] = isset($warning['message']) ? $warning['message'] : json_encode($warning);
            }
            throw new Exception('Avisos do Moodle: ' . implode(', ', $warnings));
        }
        
        throw new Exception('Resposta inesperada da API: ' . json_encode($response));
        
    } catch (Exception $e) {
        // Log detalhado do erro
        logAction('moodle_create_user_error', $e->getMessage() . " | Username: $username | Email: $email", null, false);
        throw new Exception('Erro ao criar usuário no Moodle: ' . $e->getMessage());
    }
}

/**
 * Gerar username único de forma mais eficiente
 */
function createUniqueUsername($email, $moodleUrl, $apiToken) {
    // Extrair parte antes do @
    $base = strtolower(explode('@', $email)[0]);
    
    // Remover tudo que não é letra ou número
    $base = preg_replace('/[^a-z0-9]/', '', $base);
    
    // Garantir tamanho mínimo
    if (strlen($base) < 3) {
        $base = $base . 'user';
    }
    
    // Limitar tamanho
    if (strlen($base) > 25) {
        $base = substr($base, 0, 25);
    }
    
    $username = $base;
    $counter = 1;
    
    // Tentar variações até encontrar um livre
    while (checkUsernameExists($username, $moodleUrl, $apiToken)) {
        if ($counter <= 99) {
            $username = $base . $counter;
        } else {
            // Se passou de 99, usar timestamp
            $username = $base . substr(time(), -4);
            break;
        }
        $counter++;
    }
    
    return $username;
}

/**
 * Versão de debug do script para testar apenas parâmetros mínimos
 */
function testMinimalUserCreation($moodleUrl, $apiToken) {
    echo "<h2>Teste com Parâmetros Mínimos</h2>";
    
    $testUsername = 'test' . substr(time(), -6);
    $testEmail = $testUsername . '@test.local';
    
    try {
        $url = $moodleUrl . '/webservice/rest/server.php';
        
        // APENAS parâmetros absolutamente obrigatórios
        $params = [
            'wstoken' => $apiToken,
            'wsfunction' => 'core_user_create_users',
            'moodlewsrestformat' => 'json',
            'users[0][username]' => $testUsername,
            'users[0][password]' => 'Test123456!',
            'users[0][firstname]' => 'Test',
            'users[0][lastname]' => 'User',
            'users[0][email]' => $testEmail
        ];
        
        echo "<strong>Parâmetros mínimos:</strong><br>";
        foreach ($params as $key => $value) {
            if ($key !== 'wstoken') {
                echo "$key = $value<br>";
            }
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
        
        $data = json_decode($response, true);
        
        echo "<br><strong>Resposta:</strong><br>";
        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
        
        if (isset($data[0]['id'])) {
            echo "<div style='color: green;'>✅ Sucesso! Usuário criado com ID: " . $data[0]['id'] . "</div>";
            return true;
        } else {
            echo "<div style='color: red;'>❌ Falhou</div>";
            return false;
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Exceção: " . $e->getMessage() . "</div>";
        return false;
    }
}

function sanitizeUsername($email) {
    // Obter parte antes do @
    $username = strtolower(explode('@', $email)[0]);
    
    // Remover caracteres especiais, manter apenas letras e números
    $username = preg_replace('/[^a-z0-9]/', '', $username);
    
    // Garantir comprimento mínimo
    if (strlen($username) < 3) {
        $username = $username . 'user';
    }
    
    // Garantir comprimento máximo
    if (strlen($username) > 30) {
        $username = substr($username, 0, 30);
    }
    
    return $username;
}

/**
 * Obter todos os cursos de uma categoria e suas subcategorias
 */
function getAllCoursesInCategory($categoryId, $moodleUrl, $apiToken) {
    try {
        // Primeiro, obter todas as categorias para mapear a hierarquia
        $url = $moodleUrl . '/webservice/rest/server.php';
        $params = [
            'wstoken' => $apiToken,
            'wsfunction' => 'core_course_get_categories',
            'moodlewsrestformat' => 'json'
        ];
        
        $categories = callMoodleAPI($url, $params);
        
        // Encontrar subcategorias da categoria selecionada
        $categoryIds = [$categoryId]; // Começar com a categoria principal
        
        if (is_array($categories)) {
            foreach ($categories as $category) {
                if (isset($category['parent']) && $category['parent'] == $categoryId && $category['visible'] == 1) {
                    $categoryIds[] = $category['id'];
                }
            }
        }
        
        // Obter cursos de todas as categorias
        $allCourses = [];
        foreach ($categoryIds as $catId) {
            $params = [
                'wstoken' => $apiToken,
                'wsfunction' => 'core_course_get_courses_by_field',
                'moodlewsrestformat' => 'json',
                'field' => 'category',
                'value' => $catId
            ];
            
            $result = callMoodleAPI($url, $params);
            
            if (isset($result['courses']) && is_array($result['courses'])) {
                foreach ($result['courses'] as $course) {
                    if ($course['visible'] == 1) {
                        $allCourses[] = [
                            'id' => $course['id'],
                            'name' => $course['fullname'],
                            'shortname' => $course['shortname'],
                            'category' => $catId
                        ];
                    }
                }
            }
        }
        
        return $allCourses;
        
    } catch (Exception $e) {
        throw new Exception('Erro ao obter cursos da categoria: ' . $e->getMessage());
    }
}

/**
 * Função para inscrição em cursos - VERSÃO CORRIGIDA
 */
function enrollUserInCourse($userId, $courseId, $moodleUrl, $apiToken) {
    try {
        $url = $moodleUrl . '/webservice/rest/server.php';
        
        // Garantir que são inteiros
        $userId = (int)$userId;
        $courseId = (int)$courseId;
        
        $params = [
            'wstoken' => $apiToken,
            'wsfunction' => 'enrol_manual_enrol_users',
            'moodlewsrestformat' => 'json',
            'enrolments[0][roleid]' => 5, // 5 = Student role
            'enrolments[0][userid]' => $userId,
            'enrolments[0][courseid]' => $courseId
        ];
        
        $response = callMoodleAPI($url, $params);
        
        // Para enrolment, resposta vazia/null/array vazio significa sucesso
        if (empty($response) || $response === null || $response === '' || $response === []) {
            logAction('enrollment_success', "Usuário $userId matriculado no curso $courseId", null);
            return true;
        }
        
        // Se retornou algo, pode ser erro ou sucesso - verificar
        if (is_array($response) && empty($response)) {
            return true; // Array vazio = sucesso
        }
        
        // Se tem exceção, é erro
        if (is_array($response) && isset($response['exception'])) {
            throw new Exception($response['message']);
        }
        
        // Log de resposta inesperada mas considerar sucesso se não há exceção
        logAction('enrollment_unexpected_response', "Resposta inesperada para matrícula: " . json_encode($response), null);
        return true; // Assumir sucesso se não há exceção explícita
        
    } catch (Exception $e) {
        logAction('enrollment_error', "Erro ao matricular usuário $userId no curso $courseId: " . $e->getMessage(), null, false);
        return false;
    }
}

/**
 * Regenerar credenciais para matrículas antigas que não têm credenciais salvas
 */
function regenerateCredentials($prematriculaId, $pdo, $POLO_CONFIG) {
    try {
        // Obter informações da pré-matrícula
        $stmt = $pdo->prepare("SELECT * FROM prematriculas WHERE id = ? AND status = 'approved'");
        $stmt->execute([$prematriculaId]);
        $prematricula = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$prematricula) {
            return ['success' => false, 'message' => 'Pré-matrícula não encontrada ou não aprovada'];
        }
        
        logAction('regenerate_start', "Iniciando regeneração de credenciais", $prematriculaId);
        
        // Verificar se o polo existe na configuração
        if (!isset($POLO_CONFIG[$prematricula['polo_id']])) {
            return ['success' => false, 'message' => 'Polo não encontrado na configuração'];
        }
        
        // Obter configuração do polo
        $poloConfig = $POLO_CONFIG[$prematricula['polo_id']];
        $MOODLE_URL = $poloConfig['moodle_url'];
        $API_TOKEN = $poloConfig['api_token'];
        
        // Verificar se o usuário já existe no Moodle pelo email
        $existingUser = checkExistingMoodleUser($prematricula['email'], $MOODLE_URL, $API_TOKEN);
        
        if ($existingUser['exists']) {
            // ===== USUÁRIO EXISTE - REGENERAR SENHA =====
            
            $username = $existingUser['user_data']['username'];
            $userId = $existingUser['user_data']['id'];
            $newPassword = generatePassword(12);
            
            // Atualizar senha no Moodle
            $passwordUpdated = updateMoodleUserPassword($userId, $newPassword, $MOODLE_URL, $API_TOKEN);
            
            if (!$passwordUpdated) {
                return ['success' => false, 'message' => 'Erro ao atualizar senha no Moodle'];
            }
            
            // Garantir que está matriculado nos cursos
            $courses = getAllCoursesInCategory($prematricula['category_id'], $MOODLE_URL, $API_TOKEN);
            $enrolledCount = 0;
            
            foreach ($courses as $course) {
                if (enrollUserInCourse($userId, $course['id'], $MOODLE_URL, $API_TOKEN)) {
                    $enrolledCount++;
                }
            }
            
            // Salvar credenciais no banco
            $stmt = $pdo->prepare("
                UPDATE prematriculas SET 
                    moodle_username = ?,
                    moodle_password = ?,
                    moodle_user_id = ?,
                    moodle_url = ?,
                    credentials_sent_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$username, $newPassword, $userId, $MOODLE_URL, $prematriculaId]);
            
            logAction('regenerate_success', "Credenciais regeneradas para usuário existente: $username", $prematriculaId);
            
            return [
                'success' => true, 
                'message' => "Credenciais regeneradas para usuário existente ($enrolledCount cursos)",
                'username' => $username,
                'user_id' => $userId
            ];
            
        } else {
            // ===== USUÁRIO NÃO EXISTE - CRIAR NOVO =====
            
            $result = createMoodleUserAndEnroll($prematricula, $POLO_CONFIG, $pdo);
            
            if ($result['success']) {
                logAction('regenerate_created', "Novo usuário criado durante regeneração: " . $result['username'], $prematriculaId);
                return [
                    'success' => true, 
                    'message' => "Novo usuário criado com sucesso (" . $result['courses_count'] . " cursos)",
                    'username' => $result['username'],
                    'user_id' => $result['user_id']
                ];
            } else {
                return ['success' => false, 'message' => 'Erro ao criar usuário: ' . $result['message']];
            }
        }
        
    } catch (Exception $e) {
        logAction('regenerate_error', $e->getMessage(), $prematriculaId, false);
        return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
    }
}

/**
 * ADMIN/PREMATRICULAS.PHP - PARTE 3/4
 * Funções de Email, Reenvio de Credenciais e Processamento de Ações
 */

// ============================================================================
// FUNÇÕES DE EMAIL MELHORADAS
// ============================================================================

/**
 * Enviar e-mail de aprovação para o aluno - Versão melhorada
 */
function sendApprovalEmail($email, $name, $categoryName, $poloName, $username, $password, $moodleUrl, $coursesCount) {
    // Incluir o helper de email
    require_once(__DIR__ . '/../simple_mail_helper.php');
    
    // Log para diagnóstico
    $logFile = __DIR__ . '/approval_email_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] Tentando enviar e-mail de aprovação para: {$email} | Nome: {$name} | Login: {$username}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    $subject = 'Matrícula Aprovada - ' . $categoryName . ' - Polo ' . $poloName;
    
    $htmlMessage = "
    <!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Matrícula Aprovada</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2ecc71; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { padding: 30px 20px; background-color: #ffffff; }
            .course-info { background-color: #e8f4fc; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #3498db; }
            .credentials { background-color: #f9f9f9; padding: 20px; margin: 20px 0; border-left: 4px solid #2ecc71; border-radius: 8px; }
            .credentials h3 { color: #27ae60; margin-top: 0; }
            .credential-item { background-color: #ffffff; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ddd; }
            .credential-label { font-weight: bold; color: #2c3e50; }
            .credential-value { font-family: 'Courier New', monospace; background-color: #ecf0f1; padding: 8px; border-radius: 4px; margin-top: 5px; word-break: break-all; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #888; padding: 20px; background-color: #f8f9fa; border-radius: 0 0 8px 8px; }
            .btn { display: inline-block; padding: 12px 24px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
            .btn:hover { background-color: #2980b9; }
            .warning { background-color: #fff3cd; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Matrícula Aprovada!</h1>
                <p>Parabéns! Sua jornada educacional começa agora.</p>
            </div>
            <div class='content'>
                <p>Olá <strong>{$name}</strong>,</p>
                <p>Temos o prazer de informar que sua matrícula foi aprovada e você já pode acessar o ambiente virtual de aprendizagem!</p>
                
                <div class='course-info'>
                    <h3>📚 Informações da Matrícula</h3>
                    <p><strong>Polo:</strong> {$poloName}</p>
                    <p><strong>Curso:</strong> {$categoryName}</p>
                    <p><strong>Disciplinas disponíveis:</strong> {$coursesCount} curso(s)</p>
                    <p><strong>Data de ativação:</strong> " . date('d/m/Y H:i') . "</p>
                </div>
                
                <div class='credentials'>
                    <h3>🔐 Suas Credenciais de Acesso</h3>
                    <p>Guarde estas informações com segurança:</p>
                    
                    <div class='credential-item'>
                        <div class='credential-label'>🌐 URL do Moodle:</div>
                        <div class='credential-value'>{$moodleUrl}</div>
                    </div>
                    
                    <div class='credential-item'>
                        <div class='credential-label'>👤 Nome de usuário:</div>
                        <div class='credential-value'>{$username}</div>
                    </div>
                    
                    <div class='credential-item'>
                        <div class='credential-label'>🔑 Senha:</div>
                        <div class='credential-value'>{$password}</div>
                    </div>
                    
                    <div style='text-align: center; margin-top: 20px;'>
                        <a href='{$moodleUrl}' class='btn'>🚀 Acessar Plataforma Agora</a>
                    </div>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Importante:</strong>
                    <ul>
                        <li>Recomendamos que você altere sua senha no primeiro acesso</li>
                        <li>Mantenha suas credenciais em local seguro</li>
                        <li>Em caso de problemas, entre em contato com o suporte</li>
                    </ul>
                </div>
                
                <p>Desejamos muito sucesso em seus estudos!</p>
                
                <p>
                Atenciosamente,<br>
                <strong>Equipe de Matrículas - Polo {$poloName}</strong><br>
                IMEP EDU
                </p>
            </div>
            <div class='footer'>
                <p>Este é um email automático enviado após a aprovação da sua matrícula.</p>
                <p>Se você não solicitou esta matrícula, entre em contato conosco imediatamente.</p>
                <p>© " . date('Y') . " IMEP EDU - Todos os direitos reservados.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Usar a função de envio de email
    try {
        $result = sendEmail($email, $subject, $htmlMessage);
        
        // Log do resultado
        $resultMessage = "[{$timestamp}] Resultado do envio para {$email}: " . ($result ? "SUCESSO" : "FALHA") . "\n";
        file_put_contents($logFile, $resultMessage, FILE_APPEND);
        
        return $result;
    } catch (Exception $e) {
        // Log de erro
        $errorMessage = "[{$timestamp}] ERRO ao enviar para {$email}: " . $e->getMessage() . "\n";
        file_put_contents($logFile, $errorMessage, FILE_APPEND);
        
        return false;
    }
}

/**
 * Enviar e-mail de rejeição para o aluno - Versão melhorada
 */
function sendRejectionEmail($email, $name, $categoryName, $poloName, $reason) {
    // Incluir o helper de email
    require_once(__DIR__ . '/../simple_mail_helper.php');
    
    // Log para diagnóstico
    $logFile = __DIR__ . '/rejection_email_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] Tentando enviar e-mail de rejeição para: {$email} | Motivo: " . substr($reason, 0, 50) . "...\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    $subject = 'Informação Sobre Pré-matrícula - ' . $categoryName . ' - Polo ' . $poloName;
    
    $htmlMessage = "
    <!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Informações Sobre Pré-matrícula</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #3498db; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { padding: 30px 20px; background-color: #ffffff; }
            .note { background-color: #f9f9f9; padding: 20px; margin: 20px 0; border-left: 4px solid #3498db; border-radius: 8px; }
            .contact-info { background-color: #e8f4fc; padding: 20px; margin: 20px 0; border-radius: 8px; text-align: center; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #888; padding: 20px; background-color: #f8f9fa; border-radius: 0 0 8px 8px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>📋 Informações Sobre Sua Pré-matrícula</h2>
            </div>
            <div class='content'>
                <p>Olá <strong>{$name}</strong>,</p>
                <p>Agradecemos pelo seu interesse em nossos cursos. Infelizmente, não foi possível aprovar sua pré-matrícula neste momento.</p>
                
                <div class='note'>
                    <h3>📝 Observações:</h3>
                    <p>{$reason}</p>
                </div>
                
                <div class='contact-info'>
                    <h3>💬 Precisa de mais informações?</h3>
                    <p>Nossa equipe está à disposição para esclarecer dúvidas e discutir outras opções:</p>
                    <p><strong>📞 Telefone:</strong> (94) 98409-8666</p>
                    <p><strong>📧 Email:</strong> Responda a esta mensagem</p>
                    <p><strong>💼 Polo:</strong> {$poloName}</p>
                </div>
                
                <p>Não desista de seus objetivos educacionais! Estamos aqui para ajudá-lo a encontrar a melhor solução.</p>
                
                <p>
                Atenciosamente,<br>
                <strong>Equipe de Matrículas - Polo {$poloName}</strong><br>
                IMEP EDU
                </p>
            </div>
            <div class='footer'>
                <p>Este é um email automático enviado em relação à sua solicitação de pré-matrícula.</p>
                <p>© " . date('Y') . " IMEP EDU - Todos os direitos reservados.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Tentar enviar o e-mail
    try {
        $result = sendEmail($email, $subject, $htmlMessage);
        
        // Log do resultado
        $resultMessage = "[{$timestamp}] Resultado do envio para {$email}: " . ($result ? "SUCESSO" : "FALHA") . "\n";
        file_put_contents($logFile, $resultMessage, FILE_APPEND);
        
        return $result;
    } catch (Exception $e) {
        // Log de erro
        $errorMessage = "[{$timestamp}] ERRO ao enviar para {$email}: " . $e->getMessage() . "\n";
        file_put_contents($logFile, $errorMessage, FILE_APPEND);
        
        return false;
    }
}

// ============================================================================
// FUNÇÕES DE REENVIO E EXPORTAÇÃO
// ============================================================================

/**
 * Reenviar credenciais por email - Versão melhorada
 */
function resendCredentialsEmail($prematriculaId, $pdo) {
    global $POLO_CONFIG;
    
    try {
        // Obter informações da pré-matrícula
        $stmt = $pdo->prepare("SELECT * FROM prematriculas WHERE id = ? AND status = 'approved'");
        $stmt->execute([$prematriculaId]);
        $prematricula = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$prematricula) {
            return ['success' => false, 'message' => 'Pré-matrícula não encontrada ou não aprovada'];
        }
        
        // Verificar se há credenciais salvas
        if (empty($prematricula['moodle_username']) || empty($prematricula['moodle_password'])) {
            return ['success' => false, 'message' => 'Credenciais não encontradas no banco de dados'];
        }
        
        // Contar cursos (estimativa baseada na categoria)
        $coursesCount = 1; // Default
        try {
            if (isset($POLO_CONFIG[$prematricula['polo_id']])) {
                $poloConfig = $POLO_CONFIG[$prematricula['polo_id']];
                $courses = getAllCoursesInCategory($prematricula['category_id'], $poloConfig['moodle_url'], $poloConfig['api_token']);
                $coursesCount = count($courses);
            }
        } catch (Exception $e) {
            // Ignorar erro na contagem de cursos
        }
        
        // Enviar email com as credenciais
        $emailSent = sendApprovalEmail(
            $prematricula['email'],
            $prematricula['first_name'],
            $prematricula['category_name'],
            $prematricula['polo_name'],
            $prematricula['moodle_username'],
            $prematricula['moodle_password'],
            $prematricula['moodle_url'],
            $coursesCount
        );
        
        if ($emailSent) {
            // Atualizar timestamp do último reenvio
            $stmt = $pdo->prepare("UPDATE prematriculas SET last_credentials_resend = NOW() WHERE id = ?");
            $stmt->execute([$prematriculaId]);
            
            // Registrar o reenvio no log da tabela (se existir)
            try {
                $stmt = $pdo->prepare("INSERT INTO credentials_resend_log (prematricula_id, email, success, resent_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$prematriculaId, $prematricula['email'], 1]);
            } catch (PDOException $e) {
                // Tabela de log pode não existir, apenas continuar
            }
            
            // Registrar no arquivo de log
            logAction('credentials_resent', "Credenciais reenviadas para {$prematricula['email']}", $prematriculaId);
            
            return ['success' => true, 'message' => 'Credenciais reenviadas com sucesso'];
        } else {
            // Registrar falha no log
            try {
                $stmt = $pdo->prepare("INSERT INTO credentials_resend_log (prematricula_id, email, success, error_message, resent_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$prematriculaId, $prematricula['email'], 0, 'Erro ao enviar email']);
            } catch (PDOException $e) {
                // Ignorar se a tabela não existe
            }
            
            return ['success' => false, 'message' => 'Erro ao enviar email'];
        }
        
    } catch (Exception $e) {
        logAction('resend_error', $e->getMessage(), $prematriculaId, false);
        return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
    }
}

/**
 * Exportar dados dos alunos com credenciais em CSV
 */
function exportStudentsWithCredentials($format = 'csv', $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                first_name,
                last_name,
                email,
                phone,
                cpf,
                category_name,
                polo_name,
                moodle_username,
                moodle_password,
                moodle_url,
                moodle_user_id,
                credentials_sent_at,
                last_credentials_resend,
                created_at,
                updated_at
            FROM prematriculas 
            WHERE status = 'approved' 
            AND moodle_username IS NOT NULL 
            AND moodle_password IS NOT NULL
            ORDER BY created_at DESC
        ");
        
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($format === 'csv') {
            // Gerar CSV
            $filename = 'alunos_credenciais_' . date('Y-m-d_H-i-s') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            
            $output = fopen('php://output', 'w');
            
            // BOM para UTF-8 (para Excel reconhecer acentos)
            fputs($output, "\xEF\xBB\xBF");
            
            // Cabeçalho
            fputcsv($output, [
                'ID',
                'Nome',
                'Sobrenome',
                'Email',
                'Telefone',
                'CPF',
                'Curso',
                'Polo',
                'Usuário Moodle',
                'Senha Moodle',
                'URL Moodle',
                'ID Usuário Moodle',
                'Credenciais Enviadas',
                'Último Reenvio',
                'Data Matrícula',
                'Última Atualização'
            ], ';');
            
            // Dados
            foreach ($students as $student) {
                fputcsv($output, [
                    $student['id'],
                    $student['first_name'],
                    $student['last_name'],
                    $student['email'],
                    $student['phone'],
                    $student['cpf'],
                    $student['category_name'],
                    $student['polo_name'],
                    $student['moodle_username'],
                    $student['moodle_password'],
                    $student['moodle_url'],
                    $student['moodle_user_id'],
                    $student['credentials_sent_at'] ? date('d/m/Y H:i', strtotime($student['credentials_sent_at'])) : '',
                    $student['last_credentials_resend'] ? date('d/m/Y H:i', strtotime($student['last_credentials_resend'])) : '',
                    date('d/m/Y H:i', strtotime($student['created_at'])),
                    date('d/m/Y H:i', strtotime($student['updated_at']))
                ], ';');
            }
            
            fclose($output);
            logAction('export_credentials', "Exportados " . count($students) . " registros de credenciais");
            exit;
            
        } else {
            // Retornar array para outros formatos
            return $students;
        }
        
    } catch (Exception $e) {
        logAction('export_error', $e->getMessage(), null, false);
        return false;
    }
}

// ============================================================================
// PROCESSAMENTO PRINCIPAL DE AÇÕES POST
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        if ($_POST['action'] === 'approve') {
            // ===== APROVAÇÃO DE PRÉ-MATRÍCULA =====
            
            // Obter informações da pré-matrícula
            $stmt = $pdo->prepare("SELECT * FROM prematriculas WHERE id = ?");
            $stmt->execute([$id]);
            $prematricula = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($prematricula) {
                // Atualizar status e registrar método de pagamento
                $stmt = $pdo->prepare("
                    UPDATE prematriculas SET 
                        status = 'approved',
                        payment_method = ?,
                        payment_details = ?,
                        admin_notes = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $_POST['payment_method'] ?? '',
                    $_POST['payment_details'] ?? '',
                    $_POST['admin_notes'] ?? '',
                    $id
                ]);
                
                // Criar usuário no Moodle e matricular nos cursos
                $createUser = createMoodleUserAndEnroll($prematricula, $POLO_CONFIG, $pdo);
                
                if ($createUser['success']) {
                    $message = "Pré-matrícula #$id aprovada com sucesso! O aluno foi matriculado no Moodle com " . $createUser['courses_count'] . " curso(s).";
                    $messageType = 'success';
                    
                    // Enviar email para o aluno com as credenciais
                    $emailSent = sendApprovalEmail(
                        $prematricula['email'],
                        $prematricula['first_name'],
                        $prematricula['category_name'],
                        $prematricula['polo_name'],
                        $createUser['username'],
                        $createUser['password'],
                        $createUser['moodle_url'],
                        $createUser['courses_count']
                    );
                    
                    if (!$emailSent) {
                        $message .= " Porém, houve um problema ao enviar o email de confirmação.";
                        $messageType = 'warning';
                    }
                    
                    logAction('approval_complete', "Pré-matrícula aprovada e usuário criado: " . $createUser['username'], $id);
                } else {
                    $message = "Pré-matrícula #$id aprovada, mas ocorreu um erro ao criar usuário no Moodle: " . $createUser['message'];
                    $messageType = 'warning';
                    logAction('approval_partial', "Aprovação com erro no Moodle: " . $createUser['message'], $id, false);
                }
            } else {
                $message = "Pré-matrícula #$id não encontrada.";
                $messageType = 'danger';
            }
            
        } elseif ($_POST['action'] === 'reject') {
            // ===== REJEIÇÃO DE PRÉ-MATRÍCULA =====
            
            // Obter informações da pré-matrícula
            $stmt = $pdo->prepare("SELECT * FROM prematriculas WHERE id = ?");
            $stmt->execute([$id]);
            $prematricula = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Atualizar status
            $stmt = $pdo->prepare("
                UPDATE prematriculas SET 
                    status = 'rejected',
                    admin_notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['admin_notes'] ?? '',
                $id
            ]);
            
            $message = "Pré-matrícula #$id rejeitada.";
            $messageType = 'info';
            
            // Enviar email de rejeição
            if ($prematricula) {
                $emailSent = sendRejectionEmail(
                    $prematricula['email'],
                    $prematricula['first_name'],
                    $prematricula['category_name'],
                    $prematricula['polo_name'],
                    $_POST['admin_notes'] ?? 'Não foi possível aprovar sua pré-matrícula no momento.'
                );
                
                if (!$emailSent) {
                    $message .= " Email de notificação não pôde ser enviado.";
                    $messageType = 'warning';
                }
            }
            
            logAction('rejection', "Pré-matrícula rejeitada: " . ($_POST['admin_notes'] ?? ''), $id);
            
        } elseif ($_POST['action'] === 'resend_credentials') {
            // ===== REENVIO DE CREDENCIAIS =====
            
            $result = resendCredentialsEmail($id, $pdo);
            
            if ($result['success']) {
                $message = "Credenciais reenviadas com sucesso para a pré-matrícula #$id";
                $messageType = 'success';
            } else {
                $message = "Erro ao reenviar credenciais para a pré-matrícula #$id: " . $result['message'];
                $messageType = 'danger';
            }
            
        } elseif ($_POST['action'] === 'regenerate_credentials') {
            // ===== REGENERAÇÃO DE CREDENCIAIS =====
            
            $result = regenerateCredentials($id, $pdo, $POLO_CONFIG);
            
            if ($result['success']) {
                $message = "Credenciais regeneradas com sucesso para a pré-matrícula #$id: " . $result['message'];
                $messageType = 'success';
            } else {
                $message = "Erro ao regenerar credenciais para a pré-matrícula #$id: " . $result['message'];
                $messageType = 'danger';
            }
        }
    }
}
/**
 * ADMIN/PREMATRICULAS.PHP - PARTE 4/4
 * Interface HTML Completa com Gerenciamento de Credenciais
 */

// ============================================================================
// OBTER LISTA DE PRÉ-MATRÍCULAS COM FILTROS
// ============================================================================

// Filtragem por status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$valid_statuses = ['pending', 'approved', 'rejected', 'all'];

if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'pending';
}

// Configurar a consulta SQL com base no filtro
$sql = "SELECT *, 
               CASE 
                   WHEN moodle_username IS NOT NULL AND moodle_password IS NOT NULL THEN 1 
                   ELSE 0 
               END as has_credentials
        FROM prematriculas";

$params = [];

if ($status_filter !== 'all') {
    $sql .= " WHERE status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$prematriculas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter estatísticas
$stats = getCredentialsStats($pdo);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Pré-matrículas - IMEP EDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-header {
            background: linear-gradient(135deg, var(--primary-color), #2980b9);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .has-credentials {
            background-color: rgba(46, 204, 113, 0.05);
            border-left: 4px solid var(--success-color);
        }

        .credentials-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 12px;
        }

        .credentials-available {
            background-color: var(--success-color);
            color: white;
        }

        .credentials-missing {
            background-color: var(--warning-color);
            color: white;
        }

        .credentials-box {
            background-color: #e8f5e8;
            border: 2px solid var(--success-color);
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
        }

        .credential-item {
            background-color: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .credential-value {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            font-weight: bold;
            color: #495057;
        }

        .password-hidden {
            -webkit-text-security: disc;
        }

        .no-credentials {
            background-color: #fff3cd;
            border: 2px solid var(--warning-color);
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--primary-color);
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-xl {
            max-width: 1200px;
        }

        .copy-success {
            background-color: var(--success-color) !important;
            color: white !important;
            transition: all 0.3s ease;
        }

        .filter-tabs {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-pills .nav-link {
            border-radius: 20px;
            padding: 0.75rem 1.5rem;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Principal -->
    <div class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-graduation-cap me-3"></i>Administração de Pré-matrículas</h1>
                    <p class="mb-0">Gerencie matrículas, credenciais e acesso ao Moodle</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="admin_polo_config.php?key=<?php echo htmlspecialchars($admin_key); ?>" class="btn btn-light me-2">
                        <i class="fas fa-cogs"></i> Configurar Polos
                    </a>
                    <a href="?key=<?php echo htmlspecialchars($admin_key); ?>&action=export_credentials" class="btn btn-success">
                        <i class="fas fa-download"></i> Exportar CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Mensagem de Status -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['total_approved']; ?></div>
                            <div class="text-muted">Total Aprovadas</div>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['with_credentials']; ?></div>
                            <div class="text-muted">Com Credenciais</div>
                        </div>
                        <i class="fas fa-key fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['without_credentials']; ?></div>
                            <div class="text-muted">Sem Credenciais</div>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['percentage_with_credentials']; ?>%</div>
                            <div class="text-muted">Taxa de Sucesso</div>
                        </div>
                        <i class="fas fa-chart-pie fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filter-tabs">
            <ul class="nav nav-pills justify-content-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" 
                       href="?key=<?php echo htmlspecialchars($admin_key); ?>&status=pending">
                        <i class="fas fa-clock me-2"></i>Pendentes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" 
                       href="?key=<?php echo htmlspecialchars($admin_key); ?>&status=approved">
                        <i class="fas fa-check me-2"></i>Aprovadas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" 
                       href="?key=<?php echo htmlspecialchars($admin_key); ?>&status=rejected">
                        <i class="fas fa-times me-2"></i>Rejeitadas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>" 
                       href="?key=<?php echo htmlspecialchars($admin_key); ?>&status=all">
                        <i class="fas fa-list me-2"></i>Todas
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tabela Principal -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="m-0">
                    <i class="fas fa-table me-2"></i>
                    Lista de Pré-matrículas - <?php echo ucfirst($status_filter === 'all' ? 'Todas' : $status_filter); ?>
                    <span class="badge bg-light text-primary ms-2"><?php echo count($prematriculas); ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($prematriculas)): ?>
                    <div class="text-center p-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Nenhuma pré-matrícula encontrada</h4>
                        <p class="text-muted">Não há registros para exibir no momento.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Telefone</th>
                                    <th>Curso</th>
                                    <th>Polo</th>
                                    <th>Status</th>
                                    <th>Credenciais</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prematriculas as $p): ?>
                                    <tr class="<?php echo ($p['status'] === 'approved' && $p['has_credentials']) ? 'has-credentials' : ''; ?>">
                                        <td>
                                            <strong>#<?php echo $p['id']; ?></strong>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></div>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($p['email']); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($p['phone']); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($p['category_name']); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($p['polo_name']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($p['status'] === 'pending'): ?>
                                                <span class="status-badge status-pending">
                                                    <i class="fas fa-clock me-1"></i>Pendente
                                                </span>
                                            <?php elseif ($p['status'] === 'approved'): ?>
                                                <span class="status-badge status-approved">
                                                    <i class="fas fa-check me-1"></i>Aprovada
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-rejected">
                                                    <i class="fas fa-times me-1"></i>Rejeitada
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($p['status'] === 'approved'): ?>
                                                <?php if ($p['has_credentials']): ?>
                                                    <span class="badge credentials-available">
                                                        <i class="fas fa-key me-1"></i>Disponíveis
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge credentials-missing">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Ausentes
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <!-- Botão de detalhes (sempre presente) -->
                                                <button type="button" class="btn btn-info btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#detailsModal<?php echo $p['id']; ?>"
                                                        title="Ver Detalhes">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($p['status'] === 'pending'): ?>
                                                    <!-- Botões para pré-matrículas pendentes -->
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#approveModal<?php echo $p['id']; ?>"
                                                            title="Aprovar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#rejectModal<?php echo $p['id']; ?>"
                                                            title="Rejeitar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($p['status'] === 'approved'): ?>
                                                    <?php if ($p['has_credentials']): ?>
                                                        <!-- Botões para pré-matrículas aprovadas com credenciais -->
                                                        <button type="button" class="btn btn-warning btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#resendModal<?php echo $p['id']; ?>"
                                                                title="Reenviar Credenciais">
                                                            <i class="fas fa-envelope"></i>
                                                        </button>
                                                        
                                                        <button type="button" class="btn btn-primary btn-sm" 
                                                                onclick="openMoodleLogin('<?php echo htmlspecialchars($p['moodle_url']); ?>')"
                                                                title="Abrir Moodle">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <!-- Botão para regenerar credenciais -->
                                                        <button type="button" class="btn btn-secondary btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#regenerateModal<?php echo $p['id']; ?>"
                                                                title="Regenerar Credenciais">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- MODAL DE DETALHES -->
                                    <div class="modal fade" id="detailsModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-xl">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-user-graduate me-2"></i>
                                                        Detalhes da Pré-matrícula #<?php echo $p['id']; ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <!-- Coluna 1: Informações Pessoais -->
                                                        <div class="col-md-4">
                                                            <h6><i class="fas fa-user me-2"></i>Informações Pessoais</h6>
                                                            <div class="mb-3">
                                                                <p><strong>Nome:</strong> <?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></p>
                                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($p['email']); ?></p>
                                                                <p><strong>Telefone:</strong> <?php echo htmlspecialchars($p['phone']); ?></p>
                                                                <p><strong>CPF:</strong> <?php echo htmlspecialchars($p['cpf']); ?></p>
                                                                <p><strong>Endereço:</strong> <?php echo htmlspecialchars($p['address'] ?? 'Não informado'); ?></p>
                                                                <p><strong>Cidade/Estado:</strong> <?php echo htmlspecialchars(($p['city'] ?? 'Não informado') . '/' . ($p['state'] ?? '')); ?></p>
                                                                <p><strong>CEP:</strong> <?php echo htmlspecialchars($p['zipcode'] ?? 'Não informado'); ?></p>
                                                                <p><strong>Escolaridade:</strong> <?php echo htmlspecialchars($p['education_level'] ?? 'Não informado'); ?></p>
                                                            
                                                            <!-- NOVO CAMPO: Localização do Aluno -->
                                                            <p><strong>Polo do Aluno:</strong> <?php echo htmlspecialchars($p['student_polo'] ?? 'Não informado'); ?></p>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Coluna 2: Informações da Matrícula -->
                                                        <div class="col-md-4">
                                                            <h6><i class="fas fa-graduation-cap me-2"></i>Informações da Matrícula</h6>
                                                            <div class="mb-3">
                                                                <p><strong>Curso:</strong> <?php echo htmlspecialchars($p['category_name']); ?></p>
                                                                <p><strong>Polo:</strong> <?php echo htmlspecialchars($p['polo_name']); ?></p>
                                                                <p><strong>Status:</strong> 
                                                                    <?php if ($p['status'] === 'pending'): ?>
                                                                        <span class="status-badge status-pending">Pendente</span>
                                                                    <?php elseif ($p['status'] === 'approved'): ?>
                                                                        <span class="status-badge status-approved">Aprovada</span>
                                                                    <?php else: ?>
                                                                        <span class="status-badge status-rejected">Rejeitada</span>
                                                                    <?php endif; ?>
                                                                </p>
                                                                <p><strong>Data de Solicitação:</strong> <?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></p>
                                                                <p><strong>Última Atualização:</strong> <?php echo date('d/m/Y H:i', strtotime($p['updated_at'])); ?></p>
                                                                
                                                                <?php if ($p['status'] === 'approved'): ?>
                                                                    <hr>
                                                                    <h6>Informações de Pagamento</h6>
                                                                    <p><strong>Método:</strong> <?php echo htmlspecialchars($p['payment_method'] ?? 'Não informado'); ?></p>
                                                                    <p><strong>Detalhes:</strong> <?php echo nl2br(htmlspecialchars($p['payment_details'] ?? 'Não informado')); ?></p>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (!empty($p['admin_notes'])): ?>
                                                                    <hr>
                                                                    <h6>Observações</h6>
                                                                    <div class="alert alert-secondary">
                                                                        <?php echo nl2br(htmlspecialchars($p['admin_notes'])); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Coluna 3: Credenciais do Moodle -->
                                                        <div class="col-md-4">
                                                            <?php if ($p['status'] === 'approved'): ?>
                                                                <h6><i class="fas fa-key me-2"></i>Credenciais do Moodle</h6>
                                                                
                                                                <?php if ($p['has_credentials']): ?>
                                                                    <div class="credentials-box">
                                                                        <div class="credential-item">
                                                                            <div>
                                                                                <strong>URL do Moodle:</strong><br>
                                                                                <a href="<?php echo htmlspecialchars($p['moodle_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                                                                    <i class="fas fa-external-link-alt"></i> Acessar
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="credential-item">
                                                                            <div class="flex-grow-1">
                                                                                <strong>Usuário:</strong><br>
                                                                                <span class="credential-value" id="username<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['moodle_username']); ?></span>
                                                                            </div>
                                                                            <button class="btn btn-sm btn-outline-secondary ms-2" 
                                                                                    onclick="copyToClipboard('username<?php echo $p['id']; ?>')" 
                                                                                    title="Copiar usuário">
                                                                                <i class="fas fa-copy"></i>
                                                                            </button>
                                                                        </div>
                                                                        
                                                                        <div class="credential-item">
                                                                            <div class="flex-grow-1">
                                                                                <strong>Senha:</strong><br>
                                                                                <span class="credential-value password-hidden" id="password<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['moodle_password']); ?></span>
                                                                            </div>
                                                                            <div class="ms-2">
                                                                                <button class="btn btn-sm btn-outline-secondary me-1" 
                                                                                        onclick="copyToClipboard('password<?php echo $p['id']; ?>')" 
                                                                                        title="Copiar senha">
                                                                                    <i class="fas fa-copy"></i>
                                                                                </button>
                                                                                <button class="btn btn-sm btn-outline-info" 
                                                                                        onclick="togglePasswordVisibility('password<?php echo $p['id']; ?>')" 
                                                                                        title="Mostrar/Ocultar">
                                                                                    <i class="fas fa-eye"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <?php if (!empty($p['moodle_user_id'])): ?>
                                                                            <div class="credential-item">
                                                                                <div>
                                                                                    <strong>ID do Usuário no Moodle:</strong><br>
                                                                                    <span class="credential-value"><?php echo $p['moodle_user_id']; ?></span>
                                                                                </div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        
                                                                        <?php if (!empty($p['credentials_sent_at'])): ?>
                                                                            <div class="mt-2">
                                                                                <small class="text-muted">
                                                                                    <strong>Enviado em:</strong> <?php echo date('d/m/Y H:i', strtotime($p['credentials_sent_at'])); ?>
                                                                                </small>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        
                                                                        <?php if (!empty($p['last_credentials_resend'])): ?>
                                                                            <div class="mt-1">
                                                                                <small class="text-muted">
                                                                                    <strong>Último reenvio:</strong> <?php echo date('d/m/Y H:i', strtotime($p['last_credentials_resend'])); ?>
                                                                                </small>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        
                                                                        <div class="mt-3 text-center">
                                                                            <button type="button" class="btn btn-warning btn-sm me-2" 
                                                                                    data-bs-toggle="modal" 
                                                                                    data-bs-target="#resendModal<?php echo $p['id']; ?>">
                                                                                <i class="fas fa-envelope me-1"></i> Reenviar Credenciais
                                                                            </button>
                                                                            <button type="button" class="btn btn-primary btn-sm" 
                                                                                    onclick="openMoodleLogin('<?php echo htmlspecialchars($p['moodle_url']); ?>')">
                                                                                <i class="fas fa-sign-in-alt me-1"></i> Abrir Moodle
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="no-credentials">
                                                                        <div class="text-center">
                                                                            <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                                                                            <h6>Credenciais não encontradas</h6>
                                                                            <p class="mb-3">Isso pode acontecer com matrículas antigas ou se houve erro na criação do usuário no Moodle.</p>
                                                                            <button type="button" class="btn btn-warning" 
                                                                                    data-bs-toggle="modal" 
                                                                                    data-bs-target="#regenerateModal<?php echo $p['id']; ?>">
                                                                                <i class="fas fa-redo me-1"></i> Tentar Regenerar
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <div class="alert alert-info">
                                                                    <i class="fas fa-info-circle me-2"></i>
                                                                    <strong>Aguardando Aprovação</strong><br>
                                                                    As credenciais serão geradas automaticamente após a aprovação da pré-matrícula.
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="fas fa-times me-1"></i> Fechar
                                                    </button>
                                                    <?php if ($p['status'] === 'approved' && $p['has_credentials']): ?>
                                                        <a href="<?php echo htmlspecialchars($p['moodle_url']); ?>" target="_blank" class="btn btn-primary">
                                                            <i class="fas fa-external-link-alt me-1"></i> Abrir Moodle
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MODAL DE APROVAÇÃO -->
                                    <?php if ($p['status'] === 'pending'): ?>
                                        <div class="modal fade" id="approveModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-success text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-check-circle me-2"></i>
                                                            Aprovar Pré-matrícula #<?php echo $p['id']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <div class="alert alert-success">
                                                                <i class="fas fa-info-circle me-2"></i>
                                                                <strong>Ação:</strong> Aprovar matrícula e criar usuário no Moodle automaticamente.
                                                            </div>
                                                            
                                                            <p>Você está prestes a aprovar a pré-matrícula de <strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong> no curso <strong><?php echo htmlspecialchars($p['category_name']); ?></strong>.</p>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Método de Pagamento *</label>
                                                                <select class="form-select" name="payment_method" required>
                                                                    <option value="">Selecione...</option>
                                                                    <option value="Boleto">Boleto Bancário</option>
                                                                    <option value="Cartão de Crédito">Cartão de Crédito</option>
                                                                    <option value="Cartão de Débito">Cartão de Débito</option>
                                                                    <option value="PIX">PIX</option>
                                                                    <option value="Transferência">Transferência Bancária</option>
                                                                    <option value="Dinheiro">Dinheiro</option>
                                                                    <option value="Outro">Outro</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Detalhes do Pagamento</label>
                                                                <textarea class="form-control" name="payment_details" rows="3" 
                                                                          placeholder="Ex: Valor total R$ 2.500,00 - Parcelado em 18x de R$ 138,89"></textarea>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Observações Administrativas</label>
                                                                <textarea class="form-control" name="admin_notes" rows="2" 
                                                                          placeholder="Observações internas (não serão enviadas para o aluno)"></textarea>
                                                            </div>
                                                            
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-robot me-2"></i>
                                                                <strong>Processo Automático:</strong><br>
                                                                • Usuário será criado no Moodle<br>
                                                                • Aluno será matriculado nos cursos<br>
                                                                • Email com credenciais será enviado<br>
                                                                • Credenciais serão salvas no sistema
                                                            </div>
                                                            
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-1"></i> Cancelar
                                                            </button>
                                                            <button type="submit" class="btn btn-success">
                                                                <i class="fas fa-check me-1"></i> Aprovar e Criar Usuário
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- MODAL DE REJEIÇÃO -->
                                        <div class="modal fade" id="rejectModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-times-circle me-2"></i>
                                                            Rejeitar Pré-matrícula #<?php echo $p['id']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <div class="alert alert-danger">
                                                                <i class="fas fa-info-circle me-2"></i>
                                                                <strong>Ação:</strong> Rejeitar pré-matrícula e enviar notificação ao aluno.
                                                            </div>
                                                            
                                                            <p>Você está prestes a rejeitar a pré-matrícula de <strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong> no curso <strong><?php echo htmlspecialchars($p['category_name']); ?></strong>.</p>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Motivo da Rejeição *</label>
                                                                <textarea class="form-control" name="admin_notes" rows="4" 
                                                                          placeholder="Descreva o motivo da rejeição (será enviado para o aluno)" 
                                                                          required></textarea>
                                                                <div class="form-text">Esta mensagem será incluída no email enviado ao aluno.</div>
                                                            </div>
                                                            
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-1"></i> Cancelar
                                                            </button>
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-times me-1"></i> Rejeitar Matrícula
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- MODAL DE REENVIO DE CREDENCIAIS -->
                                    <?php if ($p['status'] === 'approved' && $p['has_credentials']): ?>
                                        <div class="modal fade" id="resendModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-warning text-dark">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-envelope me-2"></i>
                                                            Reenviar Credenciais - #<?php echo $p['id']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <div class="alert alert-info">
                                                                <i class="fas fa-info-circle me-2"></i>
                                                                <strong>Ação:</strong> Reenviar email com credenciais de acesso para o aluno.
                                                            </div>
                                                            
                                                            <p>Deseja reenviar as credenciais de acesso para <strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong>?</p>
                                                            
                                                            <div class="card">
                                                                <div class="card-header">
                                                                    <h6 class="mb-0">Credenciais que serão enviadas:</h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <p><strong>Destinatário:</strong> <?php echo htmlspecialchars($p['email']); ?></p>
                                                                    <p><strong>URL do Moodle:</strong> <?php echo htmlspecialchars($p['moodle_url']); ?></p>
                                                                    <p><strong>Nome de usuário:</strong> <?php echo htmlspecialchars($p['moodle_username']); ?></p>
                                                                    <p><strong>Senha:</strong> <?php echo str_repeat('•', strlen($p['moodle_password'])); ?></p>
                                                                </div>
                                                            </div>
                                                            
                                                            <?php if (!empty($p['last_credentials_resend'])): ?>
                                                                <div class="alert alert-warning mt-3">
                                                                    <i class="fas fa-clock me-2"></i>
                                                                    <strong>Último reenvio:</strong> <?php echo date('d/m/Y H:i', strtotime($p['last_credentials_resend'])); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <div class="form-check mt-3">
                                                                <input class="form-check-input" type="checkbox" id="confirmResend<?php echo $p['id']; ?>" required>
                                                                <label class="form-check-label" for="confirmResend<?php echo $p['id']; ?>">
                                                                    Confirmo que desejo reenviar as credenciais
                                                                </label>
                                                            </div>
                                                            
                                                            <input type="hidden" name="action" value="resend_credentials">
                                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-1"></i> Cancelar
                                                            </button>
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="fas fa-envelope me-1"></i> Reenviar Credenciais
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- MODAL DE REGENERAÇÃO DE CREDENCIAIS -->
                                    <?php if ($p['status'] === 'approved' && !$p['has_credentials']): ?>
                                        <div class="modal fade" id="regenerateModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-secondary text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-redo me-2"></i>
                                                            Regenerar Credenciais - #<?php echo $p['id']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                <strong>Ação:</strong> Tentar recriar ou recuperar usuário no Moodle.
                                                            </div>
                                                            
                                                            <p>Esta ação tentará regenerar as credenciais para <strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong>.</p>
                                                            
                                                            <div class="alert alert-info">
                                                                <h6>O que será feito:</h6>
                                                                <ul class="mb-0">
                                                                    <li>Verificar se o usuário já existe no Moodle</li>
                                                                    <li>Se existir: atualizar senha e garantir matrícula nos cursos</li>
                                                                    <li>Se não existir: criar novo usuário e matricular</li>
                                                                    <li>Salvar as credenciais no sistema</li>
                                                                </ul>
                                                            </div>
                                                            
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="confirmRegenerate<?php echo $p['id']; ?>" required>
                                                                <label class="form-check-label" for="confirmRegenerate<?php echo $p['id']; ?>">
                                                                    Confirmo que desejo regenerar as credenciais
                                                                </label>
                                                            </div>
                                                            
                                                            <input type="hidden" name="action" value="regenerate_credentials">
                                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-1"></i> Cancelar
                                                            </button>
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="fas fa-redo me-1"></i> Regenerar Credenciais
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer com informações -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle me-2"></i>Informações do Sistema</h6>
                        <p class="mb-1"><strong>Total de registros:</strong> <?php echo count($prematriculas); ?></p>
                        <p class="mb-1"><strong>Filtro atual:</strong> <?php echo ucfirst($status_filter === 'all' ? 'Todas' : $status_filter); ?></p>
                        <p class="mb-0"><strong>Última atualização:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6><i class="fas fa-tools me-2"></i>Ações Rápidas</h6>
                        <a href="admin_polo_config.php?key=<?php echo htmlspecialchars($admin_key); ?>" class="btn btn-outline-primary btn-sm me-2">
                            <i class="fas fa-cogs"></i> Configurar Polos
                        </a>
                        <a href="?key=<?php echo htmlspecialchars($admin_key); ?>&action=export_credentials" class="btn btn-outline-success btn-sm me-2">
                            <i class="fas fa-download"></i> Exportar CSV
                        </a>
                        <button class="btn btn-outline-info btn-sm" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Atualizar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ============================================================================
        // JAVASCRIPT PARA FUNCIONALIDADES INTERATIVAS
        // ============================================================================

        // Função para copiar texto para a área de transferência
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopySuccess(element);
                }).catch(err => {
                    fallbackCopyToClipboard(text, element);
                });
            } else {
                fallbackCopyToClipboard(text, element);
            }
        }

        // Método alternativo para copiar texto
        function fallbackCopyToClipboard(text, element) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.top = "0";
            textArea.style.left = "0";
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showCopySuccess(element);
            } catch (err) {
                console.error('Erro ao copiar:', err);
                alert('Não foi possível copiar automaticamente. Texto: ' + text);
            }
            
            document.body.removeChild(textArea);
        }

        // Mostrar feedback visual de cópia bem-sucedida
        function showCopySuccess(element) {
            const button = element.parentNode.parentNode.querySelector('.btn-outline-secondary');
            if (button) {
                const originalClass = button.className;
                const originalHTML = button.innerHTML;
                
                button.className = 'btn btn-sm btn-success ms-2';
                button.innerHTML = '<i class="fas fa-check"></i>';
                
                setTimeout(() => {
                    button.className = originalClass;
                    button.innerHTML = originalHTML;
                }, 1500);
            }
        }

        // Função para alternar visibilidade da senha
        function togglePasswordVisibility(passwordElementId) {
            const passwordElement = document.getElementById(passwordElementId);
            const button = passwordElement.parentNode.parentNode.querySelector('.btn-outline-info');
            const icon = button.querySelector('i');
            
            if (passwordElement.classList.contains('password-hidden')) {
                passwordElement.classList.remove('password-hidden');
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordElement.classList.add('password-hidden');
                icon.className = 'fas fa-eye';
            }
        }

        // Função para abrir login no Moodle
        function openMoodleLogin(moodleUrl) {
            const loginUrl = moodleUrl + '/login/index.php';
            window.open(loginUrl, 'moodle_login', 'width=1000,height=700,scrollbars=yes,resizable=yes');
        }

        // Inicialização quando o documento estiver carregado
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips do Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-refresh a cada 5 minutos (opcional)
            // setInterval(function() {
            //     location.reload();
            // }, 300000);
            
            console.log('Sistema de Pré-matrículas carregado com sucesso!');
        });
    </script>
</body>
</html>