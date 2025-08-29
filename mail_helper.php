<?php
/**
 * Helper para envio de emails com garantia de uso SMTP
 * Esta versão modificada prioriza o uso de SMTP para todos os envios
 */

if (!function_exists('sendEmail')) {
    /**
     * Envia email usando SMTP (prioridade) ou mail() como fallback
     * 
     * @param string $to Email do destinatário
     * @param string $subject Assunto do email
     * @param string $htmlMessage Corpo do email em HTML
     * @param string $fromEmail Email do remetente (opcional)
     * @param string $fromName Nome do remetente (opcional)
     * @return bool Retorna true se o email foi enviado com sucesso
     */
    function sendEmail($to, $subject, $htmlMessage, $fromEmail = 'noreply@imepedu.com.br', $fromName = 'IMEPE EAD') {
        // MODIFICAÇÃO IMPORTANTE: Sempre verificar se o arquivo SMTP está disponível e priorizar SMTP
        $useSMTP = file_exists(__DIR__ . '/smtp_config.php');
        
        // Registrar a tentativa em log para diagnóstico
        $emailTryLog = "[" . date('Y-m-d H:i:s') . "] Tentando enviar email para $to | SMTP Disponível: " . ($useSMTP ? "SIM" : "NÃO") . "\n";
        file_put_contents(__DIR__ . '/email_attempt.log', $emailTryLog, FILE_APPEND);
        
        if ($useSMTP) {
            // Carregar configurações SMTP
            include_once(__DIR__ . '/smtp_config.php');
            
            // Verificar se a configuração é válida
            if (isset($EMAIL_CONFIG) && !empty($EMAIL_CONFIG['smtp_host']) && !empty($EMAIL_CONFIG['smtp_username'])) {
                // Tentar usar sendEmailWithSMTP diretamente
                if (function_exists('sendEmailWithSMTP')) {
                    try {
                        $result = sendEmailWithSMTP(
                            $to, 
                            $subject, 
                            $htmlMessage, 
                            $EMAIL_CONFIG['from_email'] ?? $fromEmail, 
                            $EMAIL_CONFIG['from_name'] ?? $fromName, 
                            $EMAIL_CONFIG
                        );
                        
                        // Registrar o resultado
                        logEmailSend($to, $subject, $result, 'smtp');
                        
                        // Se bem-sucedido, retornar
                        if ($result) {
                            return true;
                        }
                    } catch (Exception $e) {
                        // Registrar o erro
                        logEmailError($to, $subject, 'Erro SMTP direto: ' . $e->getMessage());
                    }
                }
            }
        }
        
        // Se SMTP falhou ou não estiver disponível, tentar mail() como fallback
        try {
            $result = sendEmailWithMail($to, $subject, $htmlMessage, $fromEmail, $fromName);
            logEmailSend($to, $subject, $result, 'mail');
            return $result;
        } catch (Exception $e) {
            logEmailError($to, $subject, 'Erro mail(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Função para envio de email via SMTP
     */
    function sendEmailWithSMTP($to, $subject, $htmlMessage, $fromEmail, $fromName, $config) {
        // Verificar se o email do destinatário é válido
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            logEmailError($to, $subject, 'Email de destinatário inválido');
            return false;
        }
        
        // Verificar se a extensão cURL está disponível (necessária para algumas operações SMTP)
        if (!extension_loaded('curl')) {
            logEmailError($to, $subject, 'Extensão cURL não está disponível no servidor');
            return false;
        }
        
        // Usar a configuração fornecida
        $host = $config['smtp_host'];
        $port = $config['smtp_port'];
        $username = $config['smtp_username'];
        $password = $config['smtp_password'];
        $secure = $config['smtp_secure'];
        
        // Usar valores do config se não fornecidos
        $fromEmail = $fromEmail ?: $config['from_email'];
        $fromName = $fromName ?: $config['from_name'];
        
        // Tentar enviar o email via SMTP
        try {
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
            
            $smtp = @stream_socket_client($socket, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
            
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
            
            // Registrar sucesso
            logEmailSend($to, $subject, true, 'smtp');
            
            return true;
        } catch (Exception $e) {
            // Registrar erro
            logEmailError($to, $subject, "Erro SMTP: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Função que usa mail() nativo do PHP
     */
    function sendEmailWithMail($to, $subject, $htmlMessage, $fromEmail = 'noreply@imepedu.com.br', $fromName = 'IMEPE EAD') {
        // Verificar se o email do destinatário é válido
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            logEmailError($to, $subject, 'Email de destinatário inválido');
            return false;
        }
        
        // Criar cabeçalhos para o email
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Converter array de cabeçalhos em string
        $headersStr = implode("\r\n", $headers) . "\r\n";
        
        // Tentar enviar o email
        try {
            // Verificar se a função mail() está disponível
            if (!function_exists('mail')) {
                logEmailError($to, $subject, 'Função mail() não está disponível no servidor');
                return false;
            }
            
            // Enviar o email
            $result = @mail($to, $subject, $htmlMessage, $headersStr);
            
            // Registrar o resultado no log
            logEmailSend($to, $subject, $result, 'mail');
            
            return $result;
        } catch (Exception $e) {
            // Registrar o erro no log
            logEmailError($to, $subject, 'Erro ao enviar email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar em log o envio de email
     */
    function logEmailSend($to, $subject, $result, $method = 'mail') {
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