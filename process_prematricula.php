<?php
/**
 * Versão final do process_prematricula.php que garante que tanto alunos quanto
 * administradores recebam e-mails pelo mesmo método SMTP
 */

// Permitir acesso de qualquer origem (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Configurações do banco de dados - ajuste conforme seu ambiente
$db_host = 'localhost';
$db_name = 'inscricaoavadb';
$db_user = 'inscricaoavauser';
$db_pass = '05hsqwjG8vLsIVBvQ7Iu';

// EMAIL DO ADMINISTRADOR - CONFIGURE AQUI
$ADMIN_EMAIL = 'magalhaeseducacao.aedu@gmail.com';

// Receber dados do formulário
$firstName = $_POST['firstName'] ?? '';
$lastName = $_POST['lastName'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$zipCode = $_POST['zipCode'] ?? '';
$educationLevel = $_POST['educationLevel'] ?? '';
$categoryId = (int)$_POST['categoryId'] ?? 0;
$categoryName = $_POST['categoryName'] ?? '';
$poloId = $_POST['poloId'] ?? '';
$poloName = $_POST['poloName'] ?? '';

// Registrar os dados recebidos no log (para diagnóstico)
$requestLog = "==== REQUISIÇÃO (" . date('Y-m-d H:i:s') . ") ====\n";
foreach ($_POST as $key => $value) {
    $requestLog .= "$key: $value\n";
}
$requestLog .= "==============================\n";
file_put_contents('prematricula_request.log', $requestLog, FILE_APPEND);

// Validação básica
if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || 
    empty($cpf) || empty($categoryId) || empty($poloId)) {
    sendResponse(false, 'Campos obrigatórios não preenchidos');
    exit;
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, 'Email inválido');
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
    $emailType = 'new';
    
    if ($existingPrematricula) {
        // Se já existe e está pendente, atualizar os dados
        if ($existingPrematricula['status'] === 'pending') {
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
                $existingPrematricula['id']
            ]);
            
            $prematriculaId = $existingPrematricula['id'];
            $message = 'Pré-matrícula atualizada com sucesso';
            $emailType = 'update';
            
        } elseif ($existingPrematricula['status'] === 'approved') {
            // Se já está aprovada, informar o usuário
            sendResponse(false, 'Você já está matriculado neste curso. Por favor, entre em contato com o suporte se precisar de ajuda para acessar sua conta.');
            exit;
            
        } else {
            // Se foi rejeitada, permitir nova solicitação
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
                    status = 'pending',
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
                $existingPrematricula['id']
            ]);
            
            $prematriculaId = $existingPrematricula['id'];
            $message = 'Nova solicitação de pré-matrícula enviada com sucesso';
            $emailType = 'renew';
        }
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
                'pending', NOW(), NOW()
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
        $message = 'Pré-matrícula enviada com sucesso';
        $emailType = 'new';
    }
    
    // Log de sucesso no banco
    $dbLog = "==== BD SUCESSO (" . date('Y-m-d H:i:s') . ") ====\n";
    $dbLog .= "ID: $prematriculaId | Email: $email | Tipo: $emailType\n";
    $dbLog .= "==============================\n";
    file_put_contents('prematricula_db.log', $dbLog, FILE_APPEND);
    
    // Preparar para enviar e-mails - verificar e carregar configuração SMTP
    require_once('simple_mail_helper.php');
    
    // Verificar se o arquivo smtp_config.php existe e carregá-lo
    $useSMTP = file_exists('smtp_config.php');
    $smtpConfig = null;
    
    if ($useSMTP) {
        // Carregar configurações SMTP
        include_once('smtp_config.php');
        if (isset($EMAIL_CONFIG) && !empty($EMAIL_CONFIG['smtp_host'])) {
            $smtpConfig = $EMAIL_CONFIG;
            
            // Registrar em log
            $smtpLog = "==== SMTP CONFIG (" . date('Y-m-d H:i:s') . ") ====\n";
            $smtpLog .= "Host: " . $smtpConfig['smtp_host'] . "\n";
            $smtpLog .= "Usuário: " . $smtpConfig['smtp_username'] . "\n";
            $smtpLog .= "==============================\n";
            file_put_contents('smtp_config.log', $smtpLog, FILE_APPEND);
        } else {
            $useSMTP = false;
        }
    }
    
    // Enviar email para o aluno
    $emailSent = false;
    try {
        // Preparar o e-mail do aluno
        $subject = 'Confirmação de Pré-matrícula - ' . $categoryName . ' - Polo ' . $poloName;
        
        // Conteúdo do e-mail para o aluno
        $htmlMessage = "
        <html>
        <head>
            <title>Confirmação de Pré-matrícula</title>
        </head>
        <body>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                <div style='background-color: #3498db; color: white; padding: 15px; text-align: center;'>
                    <h2>Pré-matrícula Recebida!</h2>
                </div>
                <div style='padding: 20px;'>
                    <p>Olá <strong>{$firstName}</strong>,</p>
                    <p>Sua pré-matrícula foi recebida com sucesso. Nossos atendentes entrarão em contato com você em breve.</p>
                    
                    <div style='background-color: #e8f4fc; padding: 15px; margin: 20px 0;'>
                        <h3>Informações da Pré-matrícula:</h3>
                        <p><strong>Polo:</strong> {$poloName}</p>
                        <p><strong>Curso:</strong> {$categoryName}</p>
                    </div>
                    
                    <p>Caso tenha alguma dúvida, sinta-se à vontade para entrar em contato.</p>
                    
                    <p>
                    Atenciosamente,<br>
                    Equipe de Matrículas - Polo {$poloName}
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Usar diretamente sendEmailWithSMTP se disponível
        if ($useSMTP && function_exists('sendEmailWithSMTP')) {
            $emailSent = sendEmailWithSMTP(
                $email, 
                $subject, 
                $htmlMessage, 
                $smtpConfig['from_email'], 
                $smtpConfig['from_name'],
                $smtpConfig
            );
            
            $method = "smtp";
        } else {
            // Fallback para sendEmail
            $emailSent = sendEmail($email, $subject, $htmlMessage);
            $method = "mail";
        }
        
        // Registrar o resultado
        $emailLog = "==== EMAIL ALUNO (" . date('Y-m-d H:i:s') . ") ====\n";
        $emailLog .= "Método: $method | Para: $email | Resultado: " . ($emailSent ? "ENVIADO" : "FALHA") . "\n";
        $emailLog .= "==============================\n";
        file_put_contents('prematricula_email.log', $emailLog, FILE_APPEND);
        
    } catch (Exception $e) {
        $errorLog = "==== EMAIL ALUNO ERRO (" . date('Y-m-d H:i:s') . ") ====\n";
        $errorLog .= "Para: $email | Erro: " . $e->getMessage() . "\n";
        $errorLog .= "==============================\n";
        file_put_contents('prematricula_email_error.log', $errorLog, FILE_APPEND);
    }
    
    // Enviar notificação para o administrador
    $adminEmailSent = false;
    try {
        // Preparar o e-mail do administrador
        $subject = 'Nova Pré-matrícula: ' . $firstName . ' ' . $lastName . ' - ' . $categoryName;
        
        // Conteúdo do e-mail para o administrador
        $htmlMessage = "
        <html>
        <head>
            <title>Nova Pré-matrícula</title>
        </head>
        <body>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                <div style='background-color: #3498db; color: white; padding: 15px; text-align: center;'>
                    <h2>Nova Pré-matrícula Recebida</h2>
                </div>
                <div style='padding: 20px;'>
                    <p>Uma nova solicitação de pré-matrícula foi recebida.</p>
                    
                    <div style='background-color: #f9f9f9; padding: 15px; margin: 20px 0;'>
                        <h3>Informações do Aluno:</h3>
                        <p><strong>Nome:</strong> {$firstName} {$lastName}</p>
                        <p><strong>Email:</strong> {$email}</p>
                        <p><strong>Telefone:</strong> {$phone}</p>
                        <p><strong>Curso:</strong> {$categoryName}</p>
                        <p><strong>Polo:</strong> {$poloName}</p>
                        <p><strong>ID da Pré-matrícula:</strong> {$prematriculaId}</p>
                    </div>
                    
                    <p>Por favor, entre em contato com o aluno para discutir os detalhes de pagamento e finalizar o processo de matrícula.</p>
                    
                    <div style='margin: 30px auto; text-align: center;'>
                        <a href='https://inscricaoava.imepedu.com.br/admin/prematriculas.php?key=admin123' style='display: inline-block; padding: 10px 20px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px;'>
                            Gerenciar Pré-matrículas
                        </a>
                    </div>
                </div>
                <div style='text-align: center; margin-top: 30px; font-size: 12px; color: #888;'>
                    <p>Este é um email automático enviado pelo sistema de pré-matrículas.</p>
                    <p>Data e hora: " . date('d/m/Y H:i:s') . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Usar o mesmo método que foi usado para o aluno
        if ($useSMTP && function_exists('sendEmailWithSMTP')) {
            $adminEmailSent = sendEmailWithSMTP(
                $ADMIN_EMAIL, 
                $subject, 
                $htmlMessage, 
                $smtpConfig['from_email'], 
                $smtpConfig['from_name'],
                $smtpConfig
            );
            
            $method = "smtp";
        } else {
            // Fallback para sendEmail
            $adminEmailSent = sendEmail($ADMIN_EMAIL, $subject, $htmlMessage);
            $method = "mail";
        }
        
        // Registrar o resultado
        $adminLog = "==== EMAIL ADMIN (" . date('Y-m-d H:i:s') . ") ====\n";
        $adminLog .= "Método: $method | Para: $ADMIN_EMAIL | Resultado: " . ($adminEmailSent ? "ENVIADO" : "FALHA") . "\n";
        $adminLog .= "==============================\n";
        file_put_contents('prematricula_admin_email.log', $adminLog, FILE_APPEND);
        
    } catch (Exception $e) {
        $errorLog = "==== EMAIL ADMIN ERRO (" . date('Y-m-d H:i:s') . ") ====\n";
        $errorLog .= "Para: $ADMIN_EMAIL | Erro: " . $e->getMessage() . "\n";
        $errorLog .= "==============================\n";
        file_put_contents('prematricula_admin_error.log', $errorLog, FILE_APPEND);
    }
    
    // Log final de sucesso
    $successLog = "==== SUCESSO FINAL (" . date('Y-m-d H:i:s') . ") ====\n";
    $successLog .= "ID: $prematriculaId | Aluno: $firstName $lastName\n";
    $successLog .= "Email Aluno: " . ($emailSent ? "ENVIADO ($method)" : "FALHA") . "\n";
    $successLog .= "Email Admin: " . ($adminEmailSent ? "ENVIADO ($method)" : "FALHA") . "\n";
    $successLog .= "==============================\n";
    file_put_contents('prematricula_success.log', $successLog, FILE_APPEND);
    
    // Enviar resposta de sucesso
    sendResponse(true, $message, [
        'prematricula_id' => $prematriculaId,
        'email_sent' => $emailSent
    ]);
    
} catch (PDOException $e) {
    // Log do erro
    $errorLog = "==== ERRO PDO (" . date('Y-m-d H:i:s') . ") ====\n";
    $errorLog .= "Mensagem: " . $e->getMessage() . "\n";
    $errorLog .= "Arquivo: " . $e->getFile() . " (Linha: " . $e->getLine() . ")\n";
    $errorLog .= "==============================\n";
    file_put_contents('prematricula_error.log', $errorLog, FILE_APPEND);
    
    sendResponse(false, 'Erro ao processar pré-matrícula. Por favor, tente novamente.');
} catch (Exception $e) {
    // Log do erro
    $errorLog = "==== ERRO GERAL (" . date('Y-m-d H:i:s') . ") ====\n";
    $errorLog .= "Mensagem: " . $e->getMessage() . "\n";
    $errorLog .= "Arquivo: " . $e->getFile() . " (Linha: " . $e->getLine() . ")\n";
    $errorLog .= "==============================\n";
    file_put_contents('prematricula_error.log', $errorLog, FILE_APPEND);
    
    sendResponse(false, 'Erro ao processar pré-matrícula. Por favor, tente novamente.');
}

/**
 * Enviar resposta em formato JSON
 */
function sendResponse($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>