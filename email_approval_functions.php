<?php
/**
 * Correção para o envio de e-mail de aprovação
 * Esta atualização modifica a função de e-mail de aprovação para usar SMTP
 */

/**
 * Função para enviar e-mail de aprovação para o aluno - Versão corrigida
 */
function sendApprovalEmail($email, $name, $categoryName, $poloName, $username, $password, $moodleUrl, $coursesCount) {
    // Log para diagnóstico
    $logFile = __DIR__ . '/approval_email_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] Tentando enviar e-mail de aprovação para: {$email}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Incluir o helper de email
    require_once(__DIR__ . '/simple_mail_helper.php');
    
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
    
    // Verificar se o arquivo smtp_config.php existe e usá-lo
    $useSMTP = file_exists(__DIR__ . '/smtp_config.php');
    
    if ($useSMTP) {
        // Carregar configurações SMTP
        include_once(__DIR__ . '/smtp_config.php');
        
        // Verificar se a configuração é válida
        if (isset($EMAIL_CONFIG) && !empty($EMAIL_CONFIG['smtp_host']) && function_exists('sendEmailWithSMTP')) {
            try {
                // Usar SMTP diretamente
                $result = sendEmailWithSMTP(
                    $email, 
                    $subject, 
                    $htmlMessage, 
                    $EMAIL_CONFIG['from_email'], 
                    $EMAIL_CONFIG['from_name'],
                    $EMAIL_CONFIG
                );
                
                // Log de sucesso
                $logMessage = "[{$timestamp}] E-mail de aprovação enviado via SMTP: " . ($result ? "SUCESSO" : "FALHA") . "\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND);
                
                return $result;
            } catch (Exception $e) {
                // Log de erro
                $logMessage = "[{$timestamp}] Erro SMTP ao enviar e-mail de aprovação: " . $e->getMessage() . "\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND);
            }
        }
    }
    
    // Se não puder usar SMTP, tentar com a função sendEmail normal
    try {
        $result = sendEmail($email, $subject, $htmlMessage);
        
        // Log de resultado
        $logMessage = "[{$timestamp}] E-mail de aprovação enviado via sendEmail: " . ($result ? "SUCESSO" : "FALHA") . "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        return $result;
    } catch (Exception $e) {
        // Log de erro
        $logMessage = "[{$timestamp}] Erro sendEmail ao enviar e-mail de aprovação: " . $e->getMessage() . "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        return false;
    }
}

/**
 * Função para enviar e-mail de rejeição para o aluno - Versão corrigida
 */
function sendRejectionEmail($email, $name, $categoryName, $poloName, $reason) {
    // Log para diagnóstico
    $logFile = __DIR__ . '/rejection_email_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] Tentando enviar e-mail de rejeição para: {$email}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Incluir o helper de email
    require_once(__DIR__ . '/simple_mail_helper.php');
    
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
                
                <p>Se desejar obter mais informações ou discutir outras opções, por favor entre em contato pelo telefone (94) 98409-8666 ou responda a este email.</p>
                
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
    
    // Verificar se o arquivo smtp_config.php existe e usá-lo
    $useSMTP = file_exists(__DIR__ . '/smtp_config.php');
    
    if ($useSMTP) {
        // Carregar configurações SMTP
        include_once(__DIR__ . '/smtp_config.php');
        
        // Verificar se a configuração é válida
        if (isset($EMAIL_CONFIG) && !empty($EMAIL_CONFIG['smtp_host']) && function_exists('sendEmailWithSMTP')) {
            try {
                // Usar SMTP diretamente
                $result = sendEmailWithSMTP(
                    $email, 
                    $subject, 
                    $htmlMessage, 
                    $EMAIL_CONFIG['from_email'], 
                    $EMAIL_CONFIG['from_name'],
                    $EMAIL_CONFIG
                );
                
                // Log de sucesso
                $logMessage = "[{$timestamp}] E-mail de rejeição enviado via SMTP: " . ($result ? "SUCESSO" : "FALHA") . "\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND);
                
                return $result;
            } catch (Exception $e) {
                // Log de erro
                $logMessage = "[{$timestamp}] Erro SMTP ao enviar e-mail de rejeição: " . $e->getMessage() . "\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND);
            }
        }
    }
    
    // Se não puder usar SMTP, tentar com a função sendEmail normal
    try {
        $result = sendEmail($email, $subject, $htmlMessage);
        
        // Log de resultado
        $logMessage = "[{$timestamp}] E-mail de rejeição enviado via sendEmail: " . ($result ? "SUCESSO" : "FALHA") . "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        return $result;
    } catch (Exception $e) {
        // Log de erro
        $logMessage = "[{$timestamp}] Erro sendEmail ao enviar e-mail de rejeição: " . $e->getMessage() . "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        return false;
    }
}