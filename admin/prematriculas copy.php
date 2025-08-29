<?php
// Verificar se o usuário está logado como administrador (implementar autenticação adequada)
// Para este exemplo, usaremos uma senha simples em URL
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
// Removido para corrigir erro 500: require_once('../// Removido para corrigir erro 500: // Removido para corrigir erro 500: email_approval_functions.php');

// Processar ações
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $id = (int)$_POST['id'];
            
            if ($_POST['action'] === 'approve') {
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
                        $_POST['payment_method'],
                        $_POST['payment_details'],
                        $_POST['admin_notes'],
                        $id
                    ]);
                    
                    // Criar usuário no Moodle e matricular nos cursos
                    $createUser = createMoodleUserAndEnroll($prematricula, $POLO_CONFIG);
                    
                    if ($createUser['success']) {
                        $message = "Pré-matrícula #$id aprovada com sucesso! O aluno foi matriculado no Moodle.";
                        
                        // Enviar email para o aluno com as credenciais
                        sendApprovalEmail(
                            $prematricula['email'],
                            $prematricula['first_name'],
                            $prematricula['category_name'],
                            $prematricula['polo_name'],
                            $createUser['username'],
                            $createUser['password'],
                            $createUser['moodle_url'],
                            $createUser['courses_count']
                        );
                    } else {
                        $message = "Pré-matrícula #$id aprovada, mas ocorreu um erro ao criar usuário no Moodle: " . $createUser['message'];
                    }
                } else {
                    $message = "Pré-matrícula #$id não encontrada.";
                }
            } elseif ($_POST['action'] === 'reject') {
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
                    $_POST['admin_notes'],
                    $id
                ]);
                
                $message = "Pré-matrícula #$id rejeitada.";
                
                // Enviar email de rejeição
                if ($prematricula) {
                    sendRejectionEmail(
                        $prematricula['email'],
                        $prematricula['first_name'],
                        $prematricula['category_name'],
                        $prematricula['polo_name'],
                        $_POST['admin_notes']
                    );
                }
            }
} catch (PDOException $e) {
            $message = 'Erro ao processar ação: ' . $e->getMessage();
        }
    }
}

// Função para criar usuário no Moodle e matricular nos cursos
function createMoodleUserAndEnroll($prematricula, $POLO_CONFIG) {
    try {
        // Verificar se o polo existe na configuração
        if (!isset($POLO_CONFIG[$prematricula['polo_id']])) {
            return ['success' => false, 'message' => 'Polo não encontrado na configuração'];
        }
        
        // Obter configuração do polo
        $poloConfig = $POLO_CONFIG[$prematricula['polo_id']];
        $MOODLE_URL = $poloConfig['moodle_url'];
        $API_TOKEN = $poloConfig['api_token'];
        
        // Criar nome de usuário a partir do email (parte antes do @)
        $usernameBase = strtolower(explode('@', $prematricula['email'])[0]);
        // Remover caracteres especiais e substituir espaços
        $username = preg_replace('/[^a-z0-9]/', '', $usernameBase);
        
        // Gerar senha aleatória
        $password = generatePassword(12);
        
        // Criar usuário no Moodle
        $userId = createMoodleUser($username, $password, $prematricula['first_name'], $prematricula['last_name'], $prematricula['email'], [
            'phone' => $prematricula['phone'],
            'cpf' => $prematricula['cpf'],
            'polo' => $prematricula['polo_name'],
            'category' => $prematricula['category_name']
        ], $MOODLE_URL, $API_TOKEN);
        
        if (!$userId) {
            return ['success' => false, 'message' => 'Erro ao criar usuário no Moodle'];
        }
        
        // Obter todos os cursos da categoria e subcategorias
        $courses = getAllCoursesInCategory($prematricula['category_id'], $MOODLE_URL, $API_TOKEN);
        
        if (empty($courses)) {
            return ['success' => false, 'message' => 'Não foram encontrados cursos na categoria selecionada'];
        }
        
        // Inscrever usuário em todos os cursos
        $enrolledCourses = [];
        foreach ($courses as $course) {
            $enrolled = enrollUserInCourse($userId, $course['id'], $MOODLE_URL, $API_TOKEN);
            if ($enrolled) {
                $enrolledCourses[] = $course['name'];
            }
        }
        
        if (empty($enrolledCourses)) {
            return ['success' => false, 'message' => 'Erro ao inscrever usuário nos cursos'];
        }
        
        return [
            'success' => true,
            'username' => $username,
            'password' => $password,
            'moodle_url' => $MOODLE_URL,
            'courses_count' => count($enrolledCourses),
            'user_id' => $userId
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Função para obter todos os cursos de uma categoria e suas subcategorias
 */
function getAllCoursesInCategory($categoryId, $moodleUrl, $apiToken) {
    // Primeiro, obter todas as categorias
    $url = $moodleUrl . '/webservice/rest/server.php';
    $params = [
        'wstoken' => $apiToken,
        'wsfunction' => 'core_course_get_categories',
        'moodlewsrestformat' => 'json'
    ];
    
    $response = callMoodleAPI($url, $params);
    
    // Encontrar subcategorias da categoria selecionada
    $categoryIds = [$categoryId]; // Começar com a categoria principal
    foreach ($response as $category) {
        if ($category['parent'] == $categoryId) {
            $categoryIds[] = $category['id'];
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
                        'shortname' => $course['shortname']
                    ];
                }
            }
        }
    }
    
    return $allCourses;
}

/**
 * Função para criar usuário no Moodle via API REST
 */
function createMoodleUser($username, $password, $firstName, $lastName, $email, $customFields, $moodleUrl, $apiToken) {
    $url = $moodleUrl . '/webservice/rest/server.php';
    
    $params = [
        'wstoken' => $apiToken,
        'wsfunction' => 'core_user_create_users',
        'moodlewsrestformat' => 'json',
        'users[0][username]' => $username,
        'users[0][password]' => $password,
        'users[0][firstname]' => $firstName,
        'users[0][lastname]' => $lastName,
        'users[0][email]' => $email
    ];
    
    // Adicionar campos personalizados se existirem no Moodle
    $fieldIndex = 0;
    foreach ($customFields as $key => $value) {
        if (!empty($value)) {
            $params["users[0][customfields][$fieldIndex][type]"] = $key;
            $params["users[0][customfields][$fieldIndex][value]"] = $value;
            $fieldIndex++;
        }
    }
    
    $response = callMoodleAPI($url, $params);
    
    if (isset($response[0]['id'])) {
        return $response[0]['id'];
    }
    
    if (isset($response['exception'])) {
        throw new Exception($response['message'] . (isset($response['debuginfo']) ? "<div>" . $response['debuginfo'] . "</div>" : ""));
    }
    
    return null;
}

/**
 * Função para inscrever usuário em um curso no Moodle via API REST
 */
function enrollUserInCourse($userId, $courseId, $moodleUrl, $apiToken) {
    $url = $moodleUrl . '/webservice/rest/server.php';
    
    $params = [
        'wstoken' => $apiToken,
        'wsfunction' => 'enrol_manual_enrol_users',
        'moodlewsrestformat' => 'json',
        'enrolments[0][roleid]' => 5, // 5 = student
        'enrolments[0][userid]' => $userId,
        'enrolments[0][courseid]' => $courseId
    ];
    
    $response = callMoodleAPI($url, $params);
    
    // Para esta função específica, uma resposta vazia ou nula significa sucesso
    return ($response === null || $response === '' || $response === [] || $response === '{}');
}

/**
 * Função genérica para chamar a API do Moodle
 */
function callMoodleAPI($url, $params) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Erro de conexão: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Tentar decodificar o JSON
    $decodedResponse = json_decode($response, true);
    
    // Se a resposta for um JSON válido, retornar o array decodificado
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decodedResponse;
    }
    
    // Caso contrário, retornar a resposta como string
    return $response;
}

/**
 * Gerar senha que atenda aos requisitos de segurança do Moodle
 */
function generatePassword($length = 12) {
    // Definir conjuntos de caracteres
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    // Vamos garantir caracteres especiais suportados pelo Moodle
    $special = '!@#$%^&*()-_=+,.?';
    
    // Usar um comprimento mínimo de 8 caracteres (padrão do Moodle)
    $length = max(8, $length);
    
    // Inicializar a senha com pelo menos um caractere de cada tipo
    $password = '';
    $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
    $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
    $password .= $numbers[rand(0, strlen($numbers) - 1)];
    
    // Adicionar pelo menos dois caracteres especiais para garantir
    $password .= $special[rand(0, strlen($special) - 1)];
    $password .= $special[rand(0, strlen($special) - 1)];
    
    // Caracteres combinados para o resto da senha
    $allChars = $lowercase . $uppercase . $numbers . $special;
    
    // Adicionar caracteres aleatórios até atingir o tamanho desejado
    for ($i = strlen($password); $i < $length; $i++) {
        $password .= $allChars[rand(0, strlen($allChars) - 1)];
    }
    
    // Embaralhar a senha para que os caracteres específicos não fiquem sempre no início
    $password = str_shuffle($password);
    
    // Adicionar verificação básica de requisitos para garantir
    $hasLower = preg_match('/[a-z]/', $password);
    $hasUpper = preg_match('/[A-Z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
    
    // Se faltar algum requisito, tente gerar novamente (recursivamente)
    if (!$hasLower || !$hasUpper || !$hasNumber || !$hasSpecial) {
        return generatePassword($length);
    }
    
    return $password;
}




/**
 * Função para enviar e-mail de aprovação para o aluno - Versão corrigida
 */
function sendApprovalEmail($email, $name, $categoryName, $poloName, $username, $password, $moodleUrl, $coursesCount) {
    // Incluir o helper de email
    require_once(__DIR__ . '/../mail_helper.php');
    
    // Log para diagnóstico
    $logFile = __DIR__ . '/../approval_email_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] Tentando enviar e-mail de aprovação para: {$email} | Nome: {$name} | Login: {$username}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    $subject = 'Matrícula Aprovada - ' . $categoryName . ' - Polo ' . $poloName;
    
    $htmlMessage = "
    <html>
    <head>
        <title>Matrícula Aprovada</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2ecc71; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; }
            .course-info { background-color: #e8f4fc; padding: 15px; margin: 20px 0; }
            .credentials { background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #2ecc71; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #888; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Matrícula Aprovada!</h2>
            </div>
            <div class='content'>
                <p>Olá <strong>{$name}</strong>,</p>
                <p>Temos o prazer de informar que sua matrícula foi aprovada e você já pode acessar o ambiente virtual de aprendizagem!</p>
                
                <div class='course-info'>
                    <h3>Informações da Matrícula:</h3>
                    <p><strong>Polo:</strong> {$poloName}</p>
                    <p><strong>Curso:</strong> {$categoryName}</p>
                    <p><strong>Disciplinas:</strong> Você foi matriculado em {$coursesCount} disciplina(s)</p>
                </div>
                
                <p>Abaixo estão suas credenciais de acesso à plataforma:</p>
                
                <div class='credentials'>
                    <p><strong>URL do Moodle:</strong> {$moodleUrl}</p>
                    <p><strong>Nome de usuário:</strong> {$username}</p>
                    <p><strong>Senha:</strong> {$password}</p>
                </div>
                
                <p>Recomendamos que você altere sua senha no primeiro acesso.</p>
                
                <p>
                Atenciosamente,<br>
                Equipe de Matrículas - Polo {$poloName}
                </p>
            </div>
            <div class='footer'>
                <p>Este é um email automático enviado após a aprovação da sua matrícula.</p>
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

/**
 * Função para enviar e-mail de rejeição para o aluno - Versão corrigida
 */
function sendRejectionEmail($email, $name, $categoryName, $poloName, $reason) {
    // Incluir o helper de email
    require_once(__DIR__ . '/../mail_helper.php');
    
    // Log para diagnóstico
    $logFile = __DIR__ . '/../rejection_email_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] Tentando enviar e-mail de rejeição para: {$email} | Motivo: " . substr($reason, 0, 30) . "...\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    $subject = 'Informação Sobre Pré-matrícula - ' . $categoryName . ' - Polo ' . $poloName;
    
    $htmlMessage = "
    <html>
    <head>
        <title>Informações Sobre Pré-matrícula</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #3498db; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; }
            .note { background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #3498db; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #888; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Informações Sobre Sua Pré-matrícula</h2>
            </div>
            <div class='content'>
                <p>Olá <strong>{$name}</strong>,</p>
                <p>Agradecemos pelo seu interesse em nossos cursos. Infelizmente, não foi possível aprovar sua pré-matrícula neste momento.</p>
                
                <div class='note'>
                    <h3>Observações:</h3>
                    <p>{$reason}</p>
                </div>
                
                <p>Se desejar obter mais informações ou discutir outras opções, por favor entre em contato pelo telefone (91) 3456-7890 ou responda a este email.</p>
                
                <p>
                Atenciosamente,<br>
                Equipe de Matrículas - Polo {$poloName}
                </p>
            </div>
            <div class='footer'>
                <p>Este é um email automático enviado em relação à sua solicitação de pré-matrícula.</p>
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

// Obter lista de pré-matrículas
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Filtragem por status
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
    $valid_statuses = ['pending', 'approved', 'rejected', 'all'];
    
    if (!in_array($status_filter, $valid_statuses)) {
        $status_filter = 'pending';
    }
    
    // Configurar a consulta SQL com base no filtro
    $sql = "SELECT * FROM prematriculas";
    $params = [];
    
    if ($status_filter !== 'all') {
        $sql .= " WHERE status = ?";
        $params[] = $status_filter;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $prematriculas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Erro ao carregar pré-matrículas: ' . $e->getMessage();
    $prematriculas = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Pré-matrículas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Administração de Pré-matrículas</h1>
            <div>
                <a href="?key=<?php echo htmlspecialchars($admin_key); ?>&status=pending" class="btn btn-outline-warning me-2">Pendentes</a>
                <a href="?key=<?php echo htmlspecialchars($admin_key); ?>&status=approved" class="btn btn-outline-success me-2">Aprovadas</a>
                <a href="?key=<?php echo htmlspecialchars($admin_key); ?>&status=rejected" class="btn btn-outline-danger me-2">Rejeitadas</a>
                <a href="?key=<?php echo htmlspecialchars($admin_key); ?>&status=all" class="btn btn-outline-primary">Todas</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
			<h5 class="m-0">Lista de Pré-matrículas <?php echo ucfirst(isset($status_filter) ? $status_filter : 'pending'); ?></h5>
          </div>
            <div class="card-body">
                <?php if (empty($prematriculas)): ?>
                    <div class="alert alert-info">Nenhuma pré-matrícula encontrada.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Telefone</th>
                                    <th>Curso</th>
                                    <th>Polo</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prematriculas as $p): ?>
                                    <tr>
                                        <td><?php echo $p['id']; ?></td>
                                        <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($p['email']); ?></td>
                                        <td><?php echo htmlspecialchars($p['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($p['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($p['polo_name']); ?></td>
                                        <td>
                                            <?php if ($p['status'] === 'pending'): ?>
                                                <span class="status-badge status-pending">Pendente</span>
                                            <?php elseif ($p['status'] === 'approved'): ?>
                                                <span class="status-badge status-approved">Aprovada</span>
                                            <?php else: ?>
                                                <span class="status-badge status-rejected">Rejeitada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailsModal<?php echo $p['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($p['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#approveModal<?php echo $p['id']; ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#rejectModal<?php echo $p['id']; ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal de Detalhes -->
                                    <div class="modal fade" id="detailsModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detalhes da Pré-matrícula #<?php echo $p['id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Informações Pessoais</h6>
                                                            <p><strong>Nome:</strong> <?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></p>
                                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($p['email']); ?></p>
                                                            <p><strong>Telefone:</strong> <?php echo htmlspecialchars($p['phone']); ?></p>
                                                            <p><strong>CPF:</strong> <?php echo htmlspecialchars($p['cpf']); ?></p>
                                                            <p><strong>Endereço:</strong> <?php echo htmlspecialchars($p['address'] ?? 'Não informado'); ?></p>
                                                            <p><strong>Cidade/Estado:</strong> <?php echo htmlspecialchars(($p['city'] ?? 'Não informado') . '/' . ($p['state'] ?? '')); ?></p>
                                                            <p><strong>CEP:</strong> <?php echo htmlspecialchars($p['zipcode'] ?? 'Não informado'); ?></p>
                                                            <p><strong>Escolaridade:</strong> <?php echo htmlspecialchars($p['education_level'] ?? 'Não informado'); ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Informações da Matrícula</h6>
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
                                                                <h6 class="mt-3">Informações de Pagamento</h6>
                                                                <p><strong>Método:</strong> <?php echo htmlspecialchars($p['payment_method'] ?? 'Não informado'); ?></p>
                                                                <p><strong>Detalhes:</strong> <?php echo nl2br(htmlspecialchars($p['payment_details'] ?? 'Não informado')); ?></p>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($p['admin_notes'])): ?>
                                                                <h6 class="mt-3">Observações</h6>
                                                                <div class="alert alert-secondary">
                                                                    <?php echo nl2br(htmlspecialchars($p['admin_notes'])); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal de Aprovação -->
                                    <?php if ($p['status'] === 'pending'): ?>
                                        <div class="modal fade" id="approveModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-success text-white">
                                                        <h5 class="modal-title">Aprovar Pré-matrícula #<?php echo $p['id']; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <p>Você está prestes a aprovar a pré-matrícula de <strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong> no curso <strong><?php echo htmlspecialchars($p['category_name']); ?></strong>.</p>
                                                            <p>Após a aprovação, o aluno será automaticamente matriculado no Moodle e receberá um email com as credenciais de acesso.</p>
                                                            
                                                            <div class="mb-3">
                                                                <label for="payment_method<?php echo $p['id']; ?>" class="form-label">Método de Pagamento</label>
                                                                <select class="form-select" id="payment_method<?php echo $p['id']; ?>" name="payment_method" required>
                                                                    <option value="">Selecione...</option>
                                                                    <option value="Boleto">Boleto Bancário</option>
                                                                    <option value="Cartão de Crédito">Cartão de Crédito</option>
                                                                    <option value="Cartão de Débito">Cartão de Débito</option>
                                                                    <option value="PIX">PIX</option>
                                                                    <option value="Transferência">Transferência Bancária</option>
                                                                    <option value="Outro">Outro</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="payment_details<?php echo $p['id']; ?>" class="form-label">Detalhes do Pagamento</label>
                                                                <textarea class="form-control" id="payment_details<?php echo $p['id']; ?>" name="payment_details" rows="3" placeholder="Informações sobre valor, parcelas, prazo, etc."></textarea>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="admin_notes<?php echo $p['id']; ?>" class="form-label">Observações Administrativas</label>
                                                                <textarea class="form-control" id="admin_notes<?php echo $p['id']; ?>" name="admin_notes" rows="3" placeholder="Observações internas (não serão enviadas para o aluno)"></textarea>
                                                            </div>
                                                            
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-success">Aprovar Matrícula</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Modal de Rejeição -->
                                        <div class="modal fade" id="rejectModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Rejeitar Pré-matrícula #<?php echo $p['id']; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <p>Você está prestes a rejeitar a pré-matrícula de <strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong> no curso <strong><?php echo htmlspecialchars($p['category_name']); ?></strong>.</p>
                                                            <p>O aluno será notificado por email sobre a rejeição da pré-matrícula.</p>
                                                            
                                                            <div class="mb-3">
                                                                <label for="reject_notes<?php echo $p['id']; ?>" class="form-label">Motivo da Rejeição</label>
                                                                <textarea class="form-control" id="reject_notes<?php echo $p['id']; ?>" name="admin_notes" rows="3" placeholder="Informe o motivo da rejeição (será enviado para o aluno)" required></textarea>
                                                            </div>
                                                            
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-danger">Rejeitar Matrícula</button>
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
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>