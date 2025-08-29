<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar se o formulário foi enviado
$emailSent = false;
$emailError = '';

if (isset($_POST['send_test'])) {
    $to = $_POST['email'];
    $subject = 'Teste de Email - Sistema IMEPE EAD';
    $message = $_POST['message'];
    
    // Headers para enviar email em HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $_POST['from_name'] . " <" . $_POST['from_email'] . ">\r\n";
    
    // Tentar enviar o email
    try {
        $result = mail($to, $subject, $message, $headers);
        
        if ($result) {
            $emailSent = true;
        } else {
            $emailError = 'Falha ao enviar o email. Verifique o log de erros do servidor.';
        }
    } catch (Exception $e) {
        $emailError = 'Erro ao enviar email: ' . $e->getMessage();
    }
    
    // Registrar a tentativa em um arquivo de log
    $logFile = 'email_test_log.txt';
    $logMessage = date('Y-m-d H:i:s') . " | Para: $to | Resultado: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Email Simples</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h1 class="mb-4 text-center">Teste de Email Simples</h1>
            
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
                    <li><strong>função mail():</strong> <?php echo function_exists('mail') ? 'Disponível' : 'Não disponível'; ?></li>
                    <li><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></li>
                    <li><strong>SMTP:</strong> <?php echo ini_get('SMTP') ?: 'Não configurado'; ?></li>
                    <li><strong>Porta SMTP:</strong> <?php echo ini_get('smtp_port') ?: 'Não configurada'; ?></li>
                </ul>
            </div>
            
            <form method="post" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="from_name" class="form-label">Nome do Remetente:</label>
                        <input type="text" class="form-control" id="from_name" name="from_name" value="<?php echo htmlspecialchars($_POST['from_name'] ?? 'IMEPE EAD'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="from_email" class="form-label">Email do Remetente:</label>
                        <input type="email" class="form-control" id="from_email" name="from_email" value="<?php echo htmlspecialchars($_POST['from_email'] ?? 'noreply@imepedu.com.br'); ?>" required>
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
    <title>Teste de Email</title>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
        <div style="background-color: #3498db; color: white; padding: 15px; text-align: center;">
            <h2>Teste de Email</h2>
        </div>
        <div style="padding: 20px; border: 1px solid #ddd; border-top: none;">
            <p>Olá!</p>
            <p>Este é um email de teste do sistema IMEPE EAD.</p>
            <p>Se você está vendo este email, significa que o envio de emails está funcionando corretamente.</p>
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
            
            <?php
            // Exibir os últimos logs se o arquivo existir
            $logFile = 'email_test_log.txt';
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
            ?>
            
            <div class="mt-4 text-center">
                <a href="index.html" class="btn btn-outline-secondary">Voltar para o formulário de pré-matrícula</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>