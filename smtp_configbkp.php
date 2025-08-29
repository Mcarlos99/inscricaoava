<?php
/**
 * Configurações de email para o sistema de pré-matrícula
 * 
 * Este arquivo contém as configurações de email usadas em todo o sistema.
 * Edite as informações abaixo para configurar seu servidor de email.
 */

// Configuração SMTP - Escolha um dos exemplos abaixo e configure com seus dados
$EMAIL_CONFIG = [
    // Use apenas UMA das configurações abaixo

    // Exemplo para Gmail (recomendado usar uma Senha de App gerada nas configurações de segurança)
    'provider' => 'gmail',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'magalhaeseducacao.aedu@gmail.com', // Substitua pelo seu email
    'smtp_password' => 'ojms ehos qnei ywka', // Crie uma "Senha de App" nas configurações de segurança do Google
    'smtp_secure' => 'tls',
    'from_email' => 'magalhaeseducacao.aedu@gmail.com', // Mesmo que smtp_username
    'from_name' => 'IMEP EDU',

   // Exemplo para Outlook/Hotmail
    /*
    'provider' => 'outlook',
    'smtp_host' => 'smtp.office365.com',
    'smtp_port' => 587,
    'smtp_username' => 'seu.email@outlook.com', // Substitua pelo seu email
    'smtp_password' => 'sua_senha', // Senha do seu email
    'smtp_secure' => 'tls',
    'from_email' => 'seu.email@outlook.com', // Mesmo que smtp_username
    'from_name' => 'IMEPE EAD',
    */

    // Exemplo para SendGrid (requer criar uma conta gratuita em sendgrid.com)
    /*
    'provider' => 'sendgrid',
    'smtp_host' => 'smtp.sendgrid.net',
    'smtp_port' => 587,
    'smtp_username' => 'apikey', // Sempre 'apikey' para SendGrid
    'smtp_password' => 'SG.seu_api_key_aqui', // API Key gerada no painel do SendGrid
    'smtp_secure' => 'tls',
    'from_email' => 'noreply@seudominio.com', // Email verificado no SendGrid
    'from_name' => 'IMEPE EAD',
    */

    // Exemplo para servidor SMTP do seu provedor de hospedagem
    /*
    'provider' => 'hosting',
    'smtp_host' => 'smtp.seudominio.com.br', // Informado pelo seu provedor de hospedagem
    'smtp_port' => 587, // Porta informada pelo seu provedor
    'smtp_username' => 'contato@seudominio.com.br', // Email criado no seu painel de hospedagem
    'smtp_password' => 'sua_senha', // Senha do email
    'smtp_secure' => 'tls', // tls ou ssl, conforme informado pelo provedor
    'from_email' => 'contato@seudominio.com.br', // Mesmo que smtp_username
    'from_name' => 'IMEPE EAD',
    */
];

// NÃO MODIFIQUE ABAIXO DESTA LINHA
// =================================

// Função para enviar email usando SMTP com PHPMailer
if (!function_exists('sendEmailWithSMTP')) {
    function sendEmailWithSMTP($to, $subject, $htmlMessage, $fromEmail = null, $fromName = null) {
        global $EMAIL_CONFIG;
        
        // Usar valores padrão se não fornecidos
        $fromEmail = $fromEmail ?? $EMAIL_CONFIG['from_email'];
        $fromName = $fromName ?? $EMAIL_CONFIG['from_name'];
        
        // Verificar se a extensão cURL está disponível (necessária para envio SMTP)
        if (!extension_loaded('curl')) {
            logEmailError($to, $subject, 'Extensão cURL não está disponível no servidor');
            return false;
        }
        
        // Usar a conexão SMTP para enviar o email
        try {
            $result = sendRawSMTP(
                $EMAIL_CONFIG['smtp_host'],
                $EMAIL_CONFIG['smtp_port'],
                $EMAIL_CONFIG['smtp_username'],
                $EMAIL_CONFIG['smtp_password'],
                $EMAIL_CONFIG['smtp_secure'],
                $fromEmail,
                $fromName,
                $to,
                $subject,
                $htmlMessage
            );
            
            // Registrar o resultado no log
            logEmailSend($to, $subject, $result, 'smtp');
            
            return $result;
        } catch (Exception $e) {
            // Registrar o erro no log
            logEmailError($to, $subject, 'Erro SMTP: ' . $e->getMessage());
            return false;
        }
    }
    
    // Implementação simples de cliente SMTP usando sockets
    function sendRawSMTP($host, $port, $username, $password, $secure, $fromEmail, $fromName, $to, $subject, $htmlMessage) {
        // Criar um ID único para esta mensagem
        $messageId = '<' . md5(uniqid(time())) . '@' . $_SERVER['SERVER_NAME'] . '>';
        
        // Definir os headers do email
        $headers = [
            'Date: ' . date('r'),
            'To: ' . $to,
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'Subject: ' . $subject,
            'Message-ID: ' . $messageId,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64'
        ];
        
        // Preparar a mensagem
        $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlMessage));
        
        // Conectar ao servidor SMTP
        $context = stream_context_create();
        
        if ($secure === 'ssl') {
            $socket = 'ssl://' . $host . ':' . $port;
        } else {
            $socket = 'tcp://' . $host . ':' . $port;
        }
        
        $smtp = stream_socket_client($socket, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        
        if (!$smtp) {
            throw new Exception("Não foi possível conectar ao servidor SMTP: $errstr ($errno)");
        }
        
        // Ler a saudação do servidor
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) !== '220') {
            throw new Exception("Erro na conexão SMTP: " . trim($response));
        }
        
        // Apresentação EHLO
        fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("Erro no comando EHLO: " . trim($response));
        }
        
        // Limpar buffer de leitura
        while (substr($response, 3, 1) === '-') {
            $response = fgets($smtp, 515);
        }
        
        // Iniciar TLS se necessário
        if ($secure === 'tls') {
            fputs($smtp, "STARTTLS\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) !== '220') {
                throw new Exception("Erro ao iniciar TLS: " . trim($response));
            }
            
            // Atualizar o stream para usar TLS
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // Novo EHLO após TLS
            fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) !== '250') {
                throw new Exception("Erro no comando EHLO após TLS: " . trim($response));
            }
            
            // Limpar buffer de leitura
            while (substr($response, 3, 1) === '-') {
                $response = fgets($smtp, 515);
            }
        }
        
        // Autenticação
        fputs($smtp, "AUTH LOGIN\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) !== '334') {
            throw new Exception("Erro no comando AUTH LOGIN: " . trim($response));
        }
        
        fputs($smtp, base64_encode($username) . "\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) !== '334') {
            throw new Exception("Erro na autenticação de usuário: " . trim($response));
        }
        
        fputs($smtp, base64_encode($password) . "\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) !== '235') {
            throw new Exception("Erro na autenticação de senha: " . trim($response));
        }
        
        // Comandos MAIL FROM e RCPT TO
        fputs($smtp, "MAIL FROM:<" . $fromEmail . ">\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("Erro no comando MAIL FROM: " . trim($response));
        }
        
        fputs($smtp, "RCPT TO:<" . $to . ">\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("Erro no comando RCPT TO: " . trim($response));
        }
        
        // Comando DATA
        fputs($smtp, "DATA\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) !== '354') {
            throw new Exception("Erro no comando DATA: " . trim($response));
        }
        
        // Enviar o conteúdo do email
        fputs($smtp, $message . "\r\n.\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("Erro ao enviar os dados do email: " . trim($response));
        }
        
        // Encerrar conexão
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        
        return true;
    }
    
    /**
     * Registrar em log o envio de email
     */
    function logEmailSend($to, $subject, $result, $method = 'smtp') {
        $logFile = __DIR__ . '/email_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $status = $result ? 'SUCCESS' : 'FAILED';
        $logMessage = "[{$timestamp}] [{$status}] [{$method}] To: {$to} | Subject: {$subject}\n";
        
        try {
            if (!file_exists(dirname($logFile))) {
                mkdir(dirname($logFile), 0755, true);
            }
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            // Se não conseguir gravar o log, apenas ignore
        }
    }
    
    /**
     * Registrar em log erros de envio de email
     */
    function logEmailError($to, $subject, $errorMessage) {
        $logFile = __DIR__ . '/email_error_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] To: {$to} | Subject: {$subject} | Error: {$errorMessage}\n";
        
        try {
            if (!file_exists(dirname($logFile))) {
                mkdir(dirname($logFile), 0755, true);
            }
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            // Se não conseguir gravar o log, apenas ignore
        }
    }
}
?>