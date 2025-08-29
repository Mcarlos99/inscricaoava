<?php
/**
 * Script para testar a configuração SMTP e diagnosticar problemas de email
 */

// Exibir erros para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar se o formulário foi enviado
$emailSent = false;
$emailError = '';
$testResult = [];

if (isset($_POST['send_test'])) {
    $to = $_POST['email'];
    $subject = 'TESTE SMTP: Sistema IMEPE EAD';
    $message = $_POST['message'];
    $method = $_POST['method'];
    
    // Verificar se existe o arquivo de configuração SMTP
    $smtpConfigExists = file_exists('smtp_config.php');
    $testResult['smtp_config_exists'] = $smtpConfigExists;
    
    if ($smtpConfigExists) {
        include_once('smtp_config.php');
        $testResult['smtp_config'] = [
            'host' => $EMAIL_CONFIG['smtp_host'] ?? 'Não configurado',
            'port' => $EMAIL_CONFIG['smtp_port'] ?? 'Não configurado',
            'username' => $EMAIL_CONFIG['smtp_username'] ?? 'Não configurado',
            'secure' => $EMAIL_CONFIG['smtp_secure'] ?? 'Não configurado',
        ];
    }
    
    try {
        // Incluir o helper de email
        if (file_exists('simple_mail_helper.php')) {
            require_once('simple_mail_helper.php');
            
            // Verificar qual método usar
            if ($method === 'smtp' && function_exists('sendEmailWithSMTP') && isset($EMAIL_CONFIG)) {
                // Usar SMTP diretamente
                $result = sendEmailWithSMTP(
                    $to, 
                    $subject, 
                    $message, 
                    $_POST['from_email'], 
                    $_POST['from_name'],
                    $EMAIL_CONFIG
                );
                
                $testResult['method_used'] = 'SMTP direto';
            } 
            elseif ($method === 'mail' && function_exists('sendEmailWithMail')) {
                // Usar mail() diretamente
                $result = sendEmailWithMail(
                    $to, 
                    $subject, 
                    $message, 
                    $_POST['from_email'], 
                    $_POST['from_name']
                );
                
                $testResult['method_used'] = 'PHP mail()';
            }
            else {
                // Usar a função sendEmail que escolhe automaticamente
                $result = sendEmail(
                    $to, 
                    $subject, 
                    $message, 
                    $_POST['from_email'], 
                    $_POST['from_name']
                );
                
                $testResult['method_used'] = 'Automático (helper)';
            }
            
            if ($result) {
                $emailSent = true;
                $testResult['result'] = 'Sucesso';
            } else {
                $emailError = 'Falha ao enviar o email. Verifique os logs para mais detalhes.';
                $testResult['result'] = 'Falha';
            }
        } else {
            $emailError = 'Arquivo simple_mail_helper.php não encontrado.';
            $testResult['helper_exists'] = false;
        }
    } catch (Exception $e) {
        $emailError = 'Erro ao enviar email: ' . $e->getMessage();
        $testResult['exception'] = $e->getMessage();
    }
    
    // Registrar a tentativa em um arquivo de log
    $logFile = 'smtp_test_log.txt';
    $logMessage = date('Y-m-d H:i:s') . " | Para: $to | Método: $method | Resultado: " . ($emailSent ? 'SUCESSO' : 'FALHA') . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Verificar logs de erro
    $emailLogExists = file_exists('email_log.txt');
    $emailErrorLogExists = file_exists('email_error_log.txt');
    
    $testResult['logs'] = [
        'email_log_exists' => $emailLogExists,
        'email_error_log_exists' => $emailErrorLogExists
    ];
    
    if ($emailErrorLogExists) {
        $testResult['error_log_content'] = file_get_contents('email_error_log.txt');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Configuração SMTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 2rem;
            padding-bottom: 2rem;
            background-color: #f5f5f5;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #17a2b8;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .diagnostic-section {
            background-color: #f3f3f3;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h1 class="mb-4 text-center">Teste de Configuração SMTP</h1>
            
            <?php if ($emailSent): ?>
                <div class="alert alert-success">
                    <strong>Sucesso!</strong> O email foi enviado para <?php echo htmlspecialchars($to); ?>. Verifique a caixa de entrada ou spam.
                </div>
            <?php endif; ?>
            
            <?php if ($emailError): ?>
                <div class="alert alert-danger">
                    <strong>Erro!</strong> <?php echo htmlspecialchars($emailError); ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h5>Informações do Servidor</h5>
                <ul>
                    <li><strong>Versão do PHP:</strong> <?php echo phpversion(); ?></li>
                    <li><strong>Função mail():</strong> <?php echo function_exists('mail') ? 'Disponível' : 'Não disponível'; ?></li>
                    <li><strong>Extensão cURL:</strong> <?php echo extension_loaded('curl') ? 'Disponível' : 'Não disponível'; ?></li>
                    <li><strong>Arquivo smtp_config.php:</strong> <?php echo file_exists('smtp_config.php') ? 'Encontrado' : 'Não encontrado'; ?></li>
                    <li><strong>Arquivo simple_mail_helper.php:</strong> <?php echo file_exists('simple_mail_helper.php') ? 'Encontrado' : 'Não encontrado'; ?></li>
                </ul>
            </div>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label class="form-label">Método de envio:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="method" id="methodSmtp" value="smtp" checked>
                        <label class="form-check-label" for="methodSmtp">
                            SMTP (Recomendado)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="method" id="methodAuto" value="auto">
                        <label class="form-check-label" for="methodAuto">
                            Automático (helper)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="method" id="methodMail" value="mail">
                        <label class="form-check-label" for="methodMail">
                            PHP mail() (menos confiável)
                        </label>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="from_name" class="form-label">Nome do Remetente:</label>
                        <input type="text" class="form-control" id="from_name" name="from_name" value="<?php echo htmlspecialchars($_POST['from_name'] ?? 'IMEPE EAD'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="from_email" class="form-label">Email do Remetente:</label>
                        <input type="email" class="form-control" id="from_email" name="from_email" value="<?php echo htmlspecialchars($_POST['from_email'] ?? 'magalhaeseducacao.aedu@gmail.com'); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Enviar para (seu email):</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label">Conteúdo do Email (HTML):</label>
                    <textarea class="form-control" id="message" name="message" rows="8" required><?php echo htmlspecialchars($_POST['message'] ?? '<!DOCTYPE html>
<html>
<head>
    <title>Teste de Email SMTP</title>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
        <div style="background-color: #3498db; color: white; padding: 15px; text-align: center;">
            <h2>Teste de Email SMTP</h2>
        </div>
        <div style="padding: 20px; border: 1px solid #ddd; border-top: none;">
            <p>Olá!</p>
            <p>Este é um email de teste do sistema IMEPE EAD usando configuração SMTP.</p>
            <p>Se você está vendo este email, significa que a configuração SMTP está funcionando corretamente.</p>
            <p>Data e hora do envio: ' . date('d/m/Y H:i:s') . '</p>
            <p><strong>Informações técnicas:</strong></p>
            <ul>
                <li>Servidor: ' . $_SERVER['SERVER_SOFTWARE'] . '</li>
                <li>PHP: ' . phpversion() . '</li>
            </ul>
            <p>Atenciosamente,<br>Equipe IMEPE EAD</p>
        </div>
    </div>
</body>
</html>'); ?></textarea>
                </div>
                
                <button type="submit" name="send_test" value="1" class="btn btn-primary w-100 btn-lg">Enviar Email de Teste</button>
            </form>
            
            <?php if (!empty($testResult)): ?>
                <div class="diagnostic-section mt-4">
                    <h5>Diagnóstico Detalhado</h5>
                    <pre><?php echo htmlspecialchars(print_r($testResult, true)); ?></pre>
                </div>
            <?php endif; ?>
            
            <?php
            // Exibir os últimos logs se o arquivo existir
            $logFile = 'smtp_test_log.txt';
            if (file_exists($logFile)) {
                $logs = file($logFile);
                $lastLogs = array_slice($logs, -10); // últimas 10 entradas
                if (!empty($lastLogs)) {
                    echo '<div class="mt-4"><h5>Últimos Testes</h5><pre>';
                    foreach ($lastLogs as $log) {
                        echo htmlspecialchars($log);
                    }
                    echo '</pre></div>';
                }
            }
            
            // Exibir os últimos erros se o arquivo existir
            $errorLogFile = 'email_error_log.txt';
            if (file_exists($errorLogFile)) {
                $logs = file($errorLogFile);
                $lastLogs = array_slice($logs, -10); // últimas 10 entradas
                if (!empty($lastLogs)) {
                    echo '<div class="mt-4"><h5>Últimos Erros</h5><pre class="text-danger">';
                    foreach ($lastLogs as $log) {
                        echo htmlspecialchars($log);
                    }
                    echo '</pre></div>';
                }
            }
            ?>
            
            <div class="mt-4 text-center">
                <a href="index.html" class="btn btn-outline-secondary">Voltar para o formulário de pré-matrícula</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>