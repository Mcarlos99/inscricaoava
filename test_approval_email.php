<?php
/**
 * Script para testar o envio de e-mail de aprovação de matrícula
 * Este script simula o envio de um e-mail de aprovação para verificar se está funcionando corretamente
 */

// Exibir erros para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar se a requisição foi feita
$emailSent = false;
$emailError = '';
$testResult = [];

// Incluir arquivo com funções de e-mail de aprovação
require_once('email_approval_functions.php');

if (isset($_POST['send_test'])) {
    // Obter dados do formulário
    $to = $_POST['email'];
    $name = $_POST['name'];
    $categoryName = $_POST['category_name'];
    $poloName = $_POST['polo_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $moodleUrl = $_POST['moodle_url'];
    $coursesCount = (int)$_POST['courses_count'];
    
    // Tentar enviar o e-mail de aprovação
    try {
        $result = sendApprovalEmail(
            $to, 
            $name, 
            $categoryName, 
            $poloName, 
            $username, 
            $password, 
            $moodleUrl, 
            $coursesCount
        );
        
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
    
    // Registrar a tentativa em um arquivo de log
    $logFile = 'approval_email_test.log';
    $logMessage = date('Y-m-d H:i:s') . " | Para: $to | Resultado: " . ($emailSent ? 'SUCESSO' : 'FALHA') . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de E-mail de Aprovação de Matrícula</title>
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
            <h1 class="mb-4 text-center">Teste de E-mail de Aprovação de Matrícula</h1>
            
            <?php if ($emailSent): ?>
                <div class="alert alert-success text-center">
                    <div class="alert-icon">✅</div>
                    <h4>Sucesso!</h4>
                    <p>O e-mail de aprovação foi enviado para <strong><?php echo htmlspecialchars($to); ?></strong>.</p>
                    <p>Verifique a caixa de entrada ou spam do e-mail fornecido.</p>
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
                    <li><strong>Arquivo email_approval_functions.php:</strong> <?php echo file_exists('email_approval_functions.php') ? '<span class="text-success">Encontrado</span>' : '<span class="text-danger">Não encontrado</span>'; ?></li>
                    <li><strong>Arquivo simple_mail_helper.php:</strong> <?php echo file_exists('simple_mail_helper.php') ? '<span class="text-success">Encontrado</span>' : '<span class="text-danger">Não encontrado</span>'; ?></li>
                    <li><strong>Arquivo smtp_config.php:</strong> <?php echo file_exists('smtp_config.php') ? '<span class="text-success">Encontrado</span>' : '<span class="text-danger">Não encontrado</span>'; ?></li>
                </ul>
                <div class="alert alert-warning">
                    <p><strong>Nota importante:</strong> Este teste envia um e-mail de aprovação de matrícula de teste. Você receberá um e-mail com os dados de acesso simulados.</p>
                </div>
            </div>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail do destinatário:</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nome do aluno:</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? 'Aluno Teste'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="username" class="form-label">Nome de usuário do Moodle:</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? 'aluno.teste'); ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="polo_name" class="form-label">Nome do Polo:</label>
                        <input type="text" class="form-control" id="polo_name" name="polo_name" value="<?php echo htmlspecialchars($_POST['polo_name'] ?? 'Tucuruí'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="category_name" class="form-label">Nome do Curso:</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo htmlspecialchars($_POST['category_name'] ?? 'Técnico em Informática'); ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="moodle_url" class="form-label">URL do Moodle:</label>
                        <input type="url" class="form-control" id="moodle_url" name="moodle_url" value="<?php echo htmlspecialchars($_POST['moodle_url'] ?? 'https://tucurui.imepedu.com.br'); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="password" class="form-label">Senha gerada:</label>
                        <input type="text" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($_POST['password'] ?? 'Senha123!@#'); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="courses_count" class="form-label">Número de disciplinas:</label>
                        <input type="number" class="form-control" id="courses_count" name="courses_count" value="<?php echo htmlspecialchars($_POST['courses_count'] ?? '5'); ?>" required>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" name="send_test" value="1" class="btn btn-primary btn-lg">
                        Enviar E-mail de Aprovação de Teste
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
            $logFile = 'approval_email_test.log';
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
            $errorLogFile = 'approval_email_log.txt';
            if (file_exists($errorLogFile)) {
                $logs = file($errorLogFile);
                $lastLogs = array_slice($logs, -10); // últimas 10 entradas
                if (!empty($lastLogs)) {
                    echo '<div class="mt-4"><h5>Log de Envio de E-mail de Aprovação</h5><pre>';
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
                    <a href="test_admin_email.php" class="btn btn-outline-info">Testar E-mail do Administrador</a>
                    <a href="index.html" class="btn btn-outline-secondary">Voltar para o formulário de pré-matrícula</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>