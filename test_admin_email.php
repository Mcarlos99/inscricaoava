<?php
/**
 * Script para testar especificamente o envio de e-mail para o administrador
 */

// Exibir erros para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar se a requisição foi feita
$emailSent = false;
$emailError = '';
$testResult = [];

// Email do administrador - Configure aqui
$ADMIN_EMAIL = 'magalhaeseducacao.aedu@gmail.com';

if (isset($_POST['send_test'])) {
    // Carregar configurações SMTP se existirem
    $useSMTP = file_exists('smtp_config.php');
    if ($useSMTP) {
        include_once('smtp_config.php');
        $testResult['smtp_config_loaded'] = true;
    } else {
        $testResult['smtp_config_loaded'] = false;
    }
    
    // Verificar se o helper de e-mail existe
    if (!file_exists('simple_mail_helper.php')) {
        $emailError = 'Arquivo simple_mail_helper.php não encontrado!';
        $testResult['helper_exists'] = false;
    } else {
        $testResult['helper_exists'] = true;
        require_once('simple_mail_helper.php');
        
        // Construir e-mail de teste para o administrador
        $subject = 'TESTE - Notificação de Pré-matrícula para Administrador';
        
        $htmlMessage = "
        <html>
        <head>
            <title>Teste de Notificação para Administrador</title>
        </head>
        <body>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                <div style='background-color: #3498db; color: white; padding: 15px; text-align: center;'>
                    <h2>TESTE - Notificação para Administrador</h2>
                </div>
                <div style='padding: 20px;'>
                    <p style='color: red; font-weight: bold;'>ESTE É APENAS UM TESTE</p>
                    <p>Este é um teste do sistema de notificação por e-mail para administradores do sistema de pré-matrícula.</p>
                    
                    <div style='background-color: #f9f9f9; padding: 15px; margin: 20px 0;'>
                        <h3>Informações de Teste:</h3>
                        <p><strong>Data e hora:</strong> " . date('d/m/Y H:i:s') . "</p>
                        <p><strong>Método:</strong> " . ($useSMTP ? 'SMTP' : 'PHP mail()') . "</p>
                        <p><strong>Servidor:</strong> " . $_SERVER['SERVER_NAME'] . "</p>
                    </div>
                    
                    <p>Se você está vendo este e-mail, significa que o sistema de notificação para administradores está funcionando corretamente.</p>
                </div>
                <div style='text-align: center; margin-top: 30px; font-size: 12px; color: #888;'>
                    <p>Este é um e-mail automático de teste.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        try {
            // Tentar enviar o e-mail usando SMTP diretamente (se disponível)
            if ($useSMTP && function_exists('sendEmailWithSMTP')) {
                $result = sendEmailWithSMTP(
                    $ADMIN_EMAIL, 
                    $subject, 
                    $htmlMessage, 
                    $EMAIL_CONFIG['from_email'], 
                    $EMAIL_CONFIG['from_name'],
                    $EMAIL_CONFIG
                );
                $testResult['method_used'] = 'SMTP direto';
            } else {
                // Usar a função genérica sendEmail
                $result = sendEmail($ADMIN_EMAIL, $subject, $htmlMessage);
                $testResult['method_used'] = 'sendEmail (helper)';
            }
            
            if ($result) {
                $emailSent = true;
                $testResult['result'] = 'Sucesso';
            } else {
                $emailError = 'Falha ao enviar o e-mail. Verifique os logs para mais detalhes.';
                $testResult['result'] = 'Falha';
            }
            
        } catch (Exception $e) {
            $emailError = 'Erro ao enviar e-mail: ' . $e->getMessage();
            $testResult['exception'] = $e->getMessage();
        }
    }
    
    // Registrar a tentativa em um arquivo de log
    $logFile = 'admin_email_test.log';
    $logMessage = date('Y-m-d H:i:s') . " | Para: $ADMIN_EMAIL | Método: " . $testResult['method_used'] . " | Resultado: " . ($emailSent ? 'SUCESSO' : 'FALHA') . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Notificação para Administradores</title>
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
        .alert-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
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
            <h1 class="mb-4 text-center">Teste de Notificação para Administradores</h1>
            
            <?php if ($emailSent): ?>
                <div class="alert alert-success text-center">
                    <div class="alert-icon">✅</div>
                    <h4>Sucesso!</h4>
                    <p>O e-mail de teste foi enviado para o administrador (<strong><?php echo htmlspecialchars($ADMIN_EMAIL); ?></strong>).</p>
                    <p>Verifique a caixa de entrada ou spam do endereço de e-mail do administrador.</p>
                </div>
            <?php endif; ?>
            
            <?php if ($emailError): ?>
                <div class="alert alert-danger text-center">
                    <div class="alert-icon">❌</div>
                    <h4>Erro!</h4>
                    <p><?php echo htmlspecialchars($emailError); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h5>Informações do Sistema</h5>
                <ul>
                    <li><strong>E-mail do Administrador:</strong> <?php echo htmlspecialchars($ADMIN_EMAIL); ?></li>
                    <li><strong>Arquivo smtp_config.php:</strong> <?php echo file_exists('smtp_config.php') ? '<span class="text-success">Encontrado</span>' : '<span class="text-danger">Não encontrado</span>'; ?></li>
                    <li><strong>Arquivo simple_mail_helper.php:</strong> <?php echo file_exists('simple_mail_helper.php') ? '<span class="text-success">Encontrado</span>' : '<span class="text-danger">Não encontrado</span>'; ?></li>
                </ul>
                <div class="alert alert-warning">
                    <p><strong>Nota importante:</strong> Este teste envia um e-mail de teste para o endereço do administrador configurado acima. Certifique-se de que ele está correto antes de prosseguir.</p>
                </div>
            </div>
            
            <form method="post" action="">
                <div class="d-grid">
                    <button type="submit" name="send_test" value="1" class="btn btn-primary btn-lg">
                        Enviar E-mail de Teste para o Administrador
                    </button>
                </div>
            </form>
            
            <?php if (!empty($testResult)): ?>
                <div class="diagnostic-section mt-4">
                    <h5>Diagnóstico Detalhado</h5>
                    <pre><?php echo htmlspecialchars(print_r($testResult, true)); ?></pre>
                </div>
            <?php endif; ?>
            
            <?php
            // Exibir os últimos logs se o arquivo existir
            $logFile = 'admin_email_test.log';
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
                <div class="btn-group">
                    <a href="smtp_test.php" class="btn btn-outline-primary">Testar Configuração SMTP</a>
                    <a href="index.html" class="btn btn-outline-secondary">Voltar para o formulário de pré-matrícula</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>