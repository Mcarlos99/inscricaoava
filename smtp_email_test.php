<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir o arquivo de configuração SMTP
require_once('smtp_config.php');

// Verificar se o formulário foi enviado
$emailSent = false;
$emailError = '';

if (isset($_POST['send_test'])) {
    $to = $_POST['email'];
    $subject = 'Teste de Email SMTP - Sistema IMEPE EAD';
    $message = $_POST['message'];
    
    // Tentar enviar o email usando SMTP configurado
    try {
        // Usar a função SMTP do nosso arquivo de configuração
        $result = sendEmailWithSMTP(
            $to, 
            $subject, 
            $message, 
            $_POST['from_email'] ?? null, 
            $_POST['from_name'] ?? null
        );
        
        if ($result) {
            $emailSent = true;
        } else {
            $emailError = 'Falha ao enviar o email. Verifique o log de erros para mais detalhes.';
        }
    } catch (Exception $e) {
        $emailError = 'Erro ao enviar email: ' . $e->getMessage();
    }
    
    // Registrar a tentativa em um arquivo de log
    $logFile = 'smtp_test_log.txt';
    $logMessage = date('Y-m-d H:i:s') . " | Para: $to | Resultado: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Obter informações da configuração SMTP atual
$smtpInfo = [
    'provider' => $EMAIL_CONFIG['provider'] ?? 'não configurado',
    'host' => $EMAIL_CONFIG['smtp_host'] ?? 'não configurado',
    'port' => $EMAIL_CONFIG['smtp_port'] ?? 'não configurado',
    'secure' => $EMAIL_CONFIG['smtp_secure'] ?? 'não configurado',
    'username' => $EMAIL_CONFIG['smtp_username'] ?? 'não configurado',
    'from_email' => $EMAIL_CONFIG['from_email'] ?? 'não configurado',
    'from_name' => $EMAIL_CONFIG['from_name'] ?? 'IMEPE EAD'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Email SMTP</title>
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
        .config-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h1 class="mb-4 text-center">Teste de Email SMTP</h1>
            
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
                <h5>Configuração SMTP Atual</h5>
                <p>Para alterar estas configurações, edite o arquivo <code>smtp_config.php</code>.</p>
                <ul>
                    <li><strong>Provedor:</strong> <?php echo htmlspecialchars($smtpInfo['provider']); ?></li>
                    <li><strong>Servidor SMTP:</strong> <?php echo htmlspecialchars($smtpInfo['host']); ?></li>
                    <li><strong>Porta:</strong> <?php echo htmlspecialchars($smtpInfo['port']); ?></li>
                    <li><strong>Segurança:</strong> <?php echo htmlspecialchars($smtpInfo['secure']); ?></li>
                    <li><strong>Usuário:</strong> <?php echo htmlspecialchars($smtpInfo['username']); ?></li>
                    <li><strong>Email de Origem:</strong> <?php echo htmlspecialchars($smtpInfo['from_email']); ?></li>
                </ul>
            </div>
            
            <?php if ($smtpInfo['host'] === 'smtp.gmail.com' && strpos($smtpInfo['username'], '@gmail.com') !== false && (strpos($smtpInfo['username'], 'seu.email') !== false || strpos($smtpInfo['smtp_password'], 'sua_senha') !== false)): ?>
                <div class="info-box config-warning mb-4">
                    <h5>⚠️ Configuração Incompleta!</h5>
                    <p>Parece que você ainda não configurou seus dados reais no arquivo <code>smtp_config.php</code>. Por favor, edite o arquivo e insira suas credenciais do Gmail.</p>
                    <p><strong>Dica para Gmail:</strong> Você precisa criar uma "Senha de App" no Google. <a href="https://support.google.com/accounts/answer/185833" target="_blank">Saiba como aqui</a>.</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="from_name" class="form-label">Nome do Remetente:</label>
                        <input type="text" class="form-control" id="from_name" name="from_name" value="<?php echo htmlspecialchars($_POST['from_name'] ?? $smtpInfo['from_name']); ?>">
                        <div class="form-text">Deixe em branco para usar o configurado no smtp_config.php</div>
                    </div>
                    <div class="col-md-6">
                        <label for="from_email" class="form-label">Email do Remetente:</label>
                        <input type="email" class="form-control" id="from_email" name="from_email" value="<?php echo htmlspecialchars($_POST['from_email'] ?? ''); ?>">
                        <div class="form-text">Deixe em branco para usar o configurado no smtp_config.php</div>
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
            <p>Este é um email de teste enviado via SMTP do sistema IMEPE EAD.</p>
            <p>Se você está vendo este email, significa que a configuração SMTP está funcionando corretamente.</p>
            <p>Data e hora do envio: ' . date('d/m/Y H:i:s') . '</p>
            <p><strong>Informações técnicas:</strong></p>
            <ul>
                <li>Servidor SMTP: ' . $smtpInfo['host'] . '</li>
                <li>Porta: ' . $smtpInfo['port'] . '</li>
                <li>Segurança: ' . $smtpInfo['secure'] . '</li>
            </ul>
            <p>Atenciosamente,<br>Equipe IMEPE EAD</p>
        </div>
    </div>
</body>
</html>'); ?></textarea>
                </div>
                
                <button type="submit" name="send_test" value="1" class="btn btn-primary w-100 btn-lg">Enviar Email de Teste via SMTP</button>
            </form>
            
            <?php
            // Exibir os últimos logs se o arquivo existir
            $logFile = 'smtp_test_log.txt';
            if (file_exists($logFile)) {
                $logs = file($logFile);
                $lastLogs = array_slice($logs, -10); // últimas 10 entradas
                if (!empty($lastLogs)) {
                    echo '<div class="mt-4"><h5>Últimos Testes</h5><pre class="p-3 bg-light">';
                    foreach ($lastLogs as $log) {
                        echo htmlspecialchars($log);
                    }
                    echo '</pre></div>';
                }
            }
            
            // Exibir erros detalhados se o arquivo existir
            $errorLogFile = 'email_error_log.txt';
            if (file_exists($errorLogFile) && filesize($errorLogFile) > 0) {
                $errorLogs = file($errorLogFile);
                $lastErrorLogs = array_slice($errorLogs, -10); // últimos 10 erros
                if (!empty($lastErrorLogs)) {
                    echo '<div class="mt-4"><h5>Últimos Erros</h5><pre class="p-3 bg-light text-danger">';
                    foreach ($lastErrorLogs as $log) {
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