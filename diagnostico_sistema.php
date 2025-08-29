<?php
/**
 * Script de diagnóstico para o sistema de pré-matrícula
 * Este arquivo verifica problemas comuns que podem estar causando falhas
 */

// Habilitar exibição detalhada de erros para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cabeçalho para formatação
echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico do Sistema de Pré-Matrícula</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .success { color: #198754; }
        .warning { color: #fd7e14; }
        .error { color: #dc3545; }
        .info { color: #0d6efd; }
        pre { background-color: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .code-block { font-family: monospace; background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1>Diagnóstico do Sistema de Pré-Matrícula</h1>
        <p class="lead">Esta ferramenta verifica problemas comuns que podem estar causando falhas no processamento de pré-matrículas.</p>
        <hr>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Informações do Sistema</h5>
            </div>
            <div class="card-body">
';

// 1. Verificar versão do PHP e extensões necessárias
echo '<h4>Ambiente PHP</h4>';
echo '<ul>';
echo '<li>Versão do PHP: <strong>' . phpversion() . '</strong></li>';
echo '<li>Extensão PDO: ' . (extension_loaded('pdo') ? '<span class="success">Disponível</span>' : '<span class="error">Não disponível</span>') . '</li>';
echo '<li>Extensão PDO MySQL: ' . (extension_loaded('pdo_mysql') ? '<span class="success">Disponível</span>' : '<span class="error">Não disponível</span>') . '</li>';
echo '<li>Extensão cURL: ' . (extension_loaded('curl') ? '<span class="success">Disponível</span>' : '<span class="warning">Não disponível</span>') . '</li>';
echo '<li>Memória Limite: ' . ini_get('memory_limit') . '</li>';
echo '<li>Tempo Máximo de Execução: ' . ini_get('max_execution_time') . ' segundos</li>';
echo '<li>Tamanho Máximo de Upload: ' . ini_get('upload_max_filesize') . '</li>';
echo '</ul>';

echo '</div></div>';

// 2. Verificar arquivos críticos do sistema
echo '<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Verificação de Arquivos</h5>
    </div>
    <div class="card-body">';

echo '<h4>Verificando arquivos críticos</h4>';
echo '<ul>';

$requiredFiles = [
    'index.html',
    'process_prematricula.php',
    'simple_mail_helper.php',
    'get_categories_by_polo.php',
    'polo_config.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo '<li>' . $file . ': <span class="success">Encontrado</span></li>';
    } else {
        echo '<li>' . $file . ': <span class="error">Não encontrado</span></li>';
    }
}

echo '</ul>';

echo '</div></div>';

// 3. Testar conexão com o banco de dados
echo '<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Conexão com o Banco de Dados</h5>
    </div>
    <div class="card-body">';

// Verificar se o arquivo process_prematricula.php existe
if (file_exists('process_prematricula.php')) {
    // Extrair as configurações do banco de dados
    $dbConfig = extractDatabaseConfig('process_prematricula.php');
    
    if ($dbConfig) {
        echo '<h4>Configurações do Banco de Dados Encontradas</h4>';
        echo '<ul>';
        echo '<li>Host: <strong>' . htmlspecialchars($dbConfig['db_host']) . '</strong></li>';
        echo '<li>Nome do Banco: <strong>' . htmlspecialchars($dbConfig['db_name']) . '</strong></li>';
        echo '<li>Usuário: <strong>' . htmlspecialchars($dbConfig['db_user']) . '</strong></li>';
        echo '</ul>';
        
        // Testar a conexão
        echo '<h4>Testando Conexão com o Banco de Dados</h4>';
        
        try {
            $pdo = new PDO("mysql:host={$dbConfig['db_host']};dbname={$dbConfig['db_name']};charset=utf8mb4", 
                           $dbConfig['db_user'], 
                           $dbConfig['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo '<div class="alert alert-success">Conexão com o banco de dados estabelecida com sucesso!</div>';
            
            // Verificar se a tabela prematriculas existe
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'prematriculas'");
                if ($stmt->rowCount() > 0) {
                    echo '<div class="alert alert-success">Tabela "prematriculas" encontrada.</div>';
                    
                    // Verificar estrutura da tabela
                    $stmt = $pdo->query("DESCRIBE prematriculas");
                    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    echo '<h5>Colunas da tabela "prematriculas":</h5>';
                    echo '<ul>';
                    foreach ($columns as $column) {
                        echo '<li>' . htmlspecialchars($column) . '</li>';
                    }
                    echo '</ul>';
                    
                    // Verificar se há registros na tabela
                    $stmt = $pdo->query("SELECT COUNT(*) FROM prematriculas");
                    $count = $stmt->fetchColumn();
                    
                    echo '<p>Total de registros na tabela: <strong>' . $count . '</strong></p>';
                    
                } else {
                    echo '<div class="alert alert-danger">Tabela "prematriculas" não encontrada!</div>';
                    
                    // Verificar se o arquivo de criação da tabela existe
                    if (file_exists('setup_database.php')) {
                        echo '<p>O arquivo <strong>setup_database.php</strong> foi encontrado. Você pode executá-lo para criar a tabela.</p>';
                    } else {
                        // Mostrar SQL para criar a tabela
                        echo '<h5>SQL para criar a tabela:</h5>';
                        echo '<pre class="code-block">' . getCreateTableSQL() . '</pre>';
                    }
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Erro ao verificar tabela: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Erro de conexão com o banco de dados: ' . htmlspecialchars($e->getMessage()) . '</div>';
            
            // Sugestões com base no erro
            if (strpos($e->getMessage(), "Access denied") !== false) {
                echo '<div class="alert alert-warning">
                <h5>Possível solução:</h5>
                <p>O usuário ou senha do banco de dados estão incorretos. Verifique as credenciais no arquivo process_prematricula.php.</p>
                </div>';
            } elseif (strpos($e->getMessage(), "Unknown database") !== false) {
                echo '<div class="alert alert-warning">
                <h5>Possível solução:</h5>
                <p>O banco de dados não existe. Você precisa criar o banco de dados "' . htmlspecialchars($dbConfig['db_name']) . '" primeiro.</p>
                </div>';
            }
        }
    } else {
        echo '<div class="alert alert-danger">Não foi possível extrair as configurações do banco de dados do arquivo process_prematricula.php</div>';
    }
} else {
    echo '<div class="alert alert-danger">Arquivo process_prematricula.php não encontrado!</div>';
}

echo '</div></div>';

// 4. Verificar logs e erros
echo '<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Logs e Erros</h5>
    </div>
    <div class="card-body">';

// Verificar se há logs de erro do PHP
$error_log_file = ini_get('error_log');
if ($error_log_file && file_exists($error_log_file) && is_readable($error_log_file)) {
    echo '<h4>Últimos erros do PHP</h4>';
    
    // Obter as últimas 20 linhas do log
    $errors = shell_exec('tail -n 20 ' . escapeshellarg($error_log_file));
    
    if ($errors) {
        echo '<pre>' . htmlspecialchars($errors) . '</pre>';
    } else {
        echo '<p>Não foi possível ler o arquivo de log de erros.</p>';
    }
} else {
    echo '<p>Arquivo de log de erros do PHP não encontrado ou não acessível.</p>';
}

// Verificar logs personalizados
$custom_logs = [
    'error_log.txt',
    'email_error_log.txt',
    'email_log.txt',
    'smtp_test_log.txt'
];

echo '<h4>Logs personalizados</h4>';
echo '<ul>';

foreach ($custom_logs as $log_file) {
    if (file_exists($log_file) && is_readable($log_file)) {
        echo '<li>' . $log_file . ': <span class="success">Encontrado</span> ';
        
        // Mostrar tamanho e última modificação
        echo '(' . formatFileSize(filesize($log_file)) . ', última modificação: ' . date('d/m/Y H:i:s', filemtime($log_file)) . ')</li>';
        
        // Mostrar as últimas linhas se o arquivo não estiver vazio
        if (filesize($log_file) > 0) {
            echo '<div class="mb-3">';
            echo '<button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#log-' . md5($log_file) . '">Mostrar/Ocultar conteúdo</button>';
            echo '<div class="collapse" id="log-' . md5($log_file) . '">';
            
            // Obter as últimas 10 linhas
            $log_content = '';
            $handle = fopen($log_file, 'r');
            if ($handle) {
                $lines = [];
                while (($line = fgets($handle)) !== false) {
                    $lines[] = $line;
                    if (count($lines) > 10) {
                        array_shift($lines);
                    }
                }
                fclose($handle);
                $log_content = implode('', $lines);
            }
            
            echo '<pre class="mt-2">' . htmlspecialchars($log_content) . '</pre>';
            echo '</div></div>';
        }
    } else {
        echo '<li>' . $log_file . ': <span class="warning">Não encontrado ou não acessível</span></li>';
    }
}

echo '</ul>';

echo '</div></div>';

// 5. Recomendações e próximos passos
echo '<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Recomendações</h5>
    </div>
    <div class="card-body">';

echo '<h4>Próximos passos</h4>';
echo '<ol>';
echo '<li>Verifique se todos os arquivos necessários estão presentes no servidor.</li>';
echo '<li>Certifique-se de que as configurações do banco de dados estão corretas.</li>';
echo '<li>Se a tabela "prematriculas" não existir, execute o arquivo setup_database.php ou crie a tabela manualmente.</li>';
echo '<li>Certifique-se de que o usuário do banco de dados tem permissões suficientes.</li>';
echo '<li>Verifique os logs de erro para identificar problemas específicos.</li>';
echo '</ol>';

// Fornecer uma versão simplificada do arquivo process_prematricula.php
echo '<h4>Solução alternativa</h4>';
echo '<p>Se você não conseguir resolver o problema, considere usar esta versão simplificada do arquivo process_prematricula.php:</p>';
echo '<button class="btn btn-outline-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#simpleScript">Mostrar/Ocultar código</button>';
echo '<div class="collapse" id="simpleScript">';
echo '<pre class="code-block">' . htmlspecialchars(getSimpleProcessScript()) . '</pre>';
echo '</div>';

echo '</div></div>';

// Footer
echo '<div class="text-center mt-5">
    <a href="index.html" class="btn btn-outline-primary">Voltar para o formulário de pré-matrícula</a>
</div>';

echo '
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';

// Função para extrair configurações do banco de dados
function extractDatabaseConfig($file) {
    if (!file_exists($file)) {
        return false;
    }
    
    $content = file_get_contents($file);
    
    $config = [
        'db_host' => '',
        'db_name' => '',
        'db_user' => '',
        'db_pass' => ''
    ];
    
    // Procurar por variáveis de configuração
    preg_match('/\$db_host\s*=\s*[\'"](.+?)[\'"]/', $content, $matches);
    if (!empty($matches[1])) {
        $config['db_host'] = $matches[1];
    }
    
    preg_match('/\$db_name\s*=\s*[\'"](.+?)[\'"]/', $content, $matches);
    if (!empty($matches[1])) {
        $config['db_name'] = $matches[1];
    }
    
    preg_match('/\$db_user\s*=\s*[\'"](.+?)[\'"]/', $content, $matches);
    if (!empty($matches[1])) {
        $config['db_user'] = $matches[1];
    }
    
    preg_match('/\$db_pass\s*=\s*[\'"](.+?)[\'"]/', $content, $matches);
    if (!empty($matches[1])) {
        $config['db_pass'] = $matches[1];
    }
    
    return !empty($config['db_host']) ? $config : false;
}

// Função para formatar o tamanho do arquivo
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Função para retornar o SQL para criar a tabela
function getCreateTableSQL() {
    return "CREATE TABLE `prematriculas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `polo_id` varchar(50) NOT NULL,
  `polo_name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `cpf` varchar(20) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `education_level` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_details` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_category` (`email`, `category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
}

// Função para fornecer uma versão simplificada do script de processamento
function getSimpleProcessScript() {
    return '<?php
// Permitir acesso de qualquer origem (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Configurações do banco de dados - ajuste conforme seu ambiente
$db_host = "localhost";
$db_name = "inscricaoavadb";
$db_user = "inscricaoavauser";
$db_pass = "05hsqwjG8vLsIVBvQ7Iu";

// Receber dados do formulário
$firstName = $_POST["firstName"] ?? "";
$lastName = $_POST["lastName"] ?? "";
$email = $_POST["email"] ?? "";
$phone = $_POST["phone"] ?? "";
$cpf = $_POST["cpf"] ?? "";
$address = $_POST["address"] ?? "";
$city = $_POST["city"] ?? "";
$state = $_POST["state"] ?? "";
$zipCode = $_POST["zipCode"] ?? "";
$educationLevel = $_POST["educationLevel"] ?? "";
$categoryId = (int)$_POST["categoryId"] ?? 0;
$categoryName = $_POST["categoryName"] ?? "";
$poloId = $_POST["poloId"] ?? "";
$poloName = $_POST["poloName"] ?? "";

// Log dos dados recebidos para diagnóstico
$logData = "DATA RECEBIDA: " . date("Y-m-d H:i:s") . "\n";
foreach ($_POST as $key => $value) {
    $logData .= "$key: $value\n";
}
$logData .= "------------------------\n";
file_put_contents("prematricula_request_log.txt", $logData, FILE_APPEND);

// Validação básica
if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || 
    empty($cpf) || empty($categoryId) || empty($poloId)) {
    sendResponse(false, "Campos obrigatórios não preenchidos");
    exit;
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, "Email inválido");
    exit;
}

try {
    // Conectar ao banco de dados
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se já existe uma pré-matrícula para este email e curso
    $stmt = $pdo->prepare("SELECT * FROM prematriculas WHERE email = ? AND category_id = ?");
    $stmt->execute([$email, $categoryId]);
    $existingPrematricula = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $prematriculaId = 0;
    
    if ($existingPrematricula) {
        // Se já existe, atualizar os dados
        $stmt = $pdo->prepare("
            UPDATE prematriculas SET 
                first_name = ?,
                last_name = ?,
                phone = ?,
                cpf = ?,
                address = ?,
                city = ?,
                state = ?,
                zipcode = ?,
                education_level = ?,
                status = \"pending\",
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $firstName,
            $lastName,
            $phone,
            $cpf,
            $address,
            $city,
            $state,
            $zipCode,
            $educationLevel,
            $existingPrematricula["id"]
        ]);
        
        $prematriculaId = $existingPrematricula["id"];
        $message = "Pré-matrícula atualizada com sucesso";
    } else {
        // Inserir nova pré-matrícula
        $stmt = $pdo->prepare("
            INSERT INTO prematriculas (
                polo_id, polo_name, category_id, category_name, 
                first_name, last_name, email, phone, cpf,
                address, city, state, zipcode, education_level,
                status, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, 
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                \"pending\", NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            $poloId,
            $poloName,
            $categoryId,
            $categoryName,
            $firstName,
            $lastName,
            $email,
            $phone,
            $cpf,
            $address,
            $city,
            $state,
            $zipCode,
            $educationLevel
        ]);
        
        $prematriculaId = $pdo->lastInsertId();
        $message = "Pré-matrícula enviada com sucesso";
    }
    
    // Log do sucesso
    $successLog = "SUCESSO: " . date("Y-m-d H:i:s") . "\n";
    $successLog .= "Email: $email | ID: $prematriculaId\n";
    $successLog .= "------------------------\n";
    file_put_contents("prematricula_success_log.txt", $successLog, FILE_APPEND);
    
    // Enviar resposta de sucesso
    sendResponse(true, $message, [
        "prematricula_id" => $prematriculaId
    ]);
    
} catch (PDOException $e) {
    // Log do erro
    $errorLog = "ERRO: " . date("Y-m-d H:i:s") . "\n";
    $errorLog .= "Mensagem: " . $e->getMessage() . "\n";
    $errorLog .= "------------------------\n";
    file_put_contents("prematricula_error_log.txt", $errorLog, FILE_APPEND);
    
    sendResponse(false, "Erro ao processar pré-matrícula. Por favor, tente novamente.");
}

/**
 * Enviar resposta em formato JSON
 */
function sendResponse($success, $message, $data = []) {
    $response = [
        "success" => $success,
        "message" => $message
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    header("Content-Type: application/json");
    echo json_encode($response);
    exit;
}
?>';
}
?>