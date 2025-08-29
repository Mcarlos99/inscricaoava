<?php
/**
 * Função para enviar e-mail de pré-matrícula para o aluno - Versão simplificada
 */
function sendPreMatriculaEmail($email, $name, $categoryName, $poloName, $type = 'new') {
    // Incluir o helper de email
    require_once('simple_mail_helper.php');
    
    switch ($type) {
        case 'update':
            $subject = 'Atualização de Pré-matrícula - ' . $categoryName . ' - Polo ' . $poloName;
            $title = 'Pré-matrícula Atualizada!';
            $message = "Sua pré-matrícula foi atualizada com sucesso. Nossos atendentes entrarão em contato com você em breve.";
            break;
            
        case 'renew':
            $subject = 'Nova Solicitação de Pré-matrícula - ' . $categoryName . ' - Polo ' . $poloName;
            $title = 'Nova Solicitação Enviada!';
            $message = "Sua nova solicitação de pré-matrícula foi enviada com sucesso. Nossos atendentes entrarão em contato com você em breve.";
            break;
            
        default: // new
            $subject = 'Confirmação de Pré-matrícula - ' . $categoryName . ' - Polo ' . $poloName;
            $title = 'Pré-matrícula Recebida!';
            $message = "Sua pré-matrícula foi recebida com sucesso. Nossos atendentes entrarão em contato com você em breve.";
    }
    
    $htmlMessage = "
    <html>
    <head>
        <title>Confirmação de Pré-matrícula</title>
    </head>
    <body>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
            <div style='background-color: #3498db; color: white; padding: 15px; text-align: center;'>
                <h2>{$title}</h2>
            </div>
            <div style='padding: 20px;'>
                <p>Olá <strong>{$name}</strong>,</p>
                <p>{$message}</p>
                
                <div style='background-color: #e8f4fc; padding: 15px; margin: 20px 0;'>
                    <h3>Informações da Pré-matrícula:</h3>
                    <p><strong>Polo:</strong> {$poloName}</p>
                    <p><strong>Curso:</strong> {$categoryName}</p>
                </div>
                
                <p>Caso tenha alguma dúvida, sinta-se à vontade para entrar em contato pelo telefone (94) 98409-8666.</p>
                
                <p>
                Atenciosamente,<br>
                Equipe de Matrículas - Polo {$poloName}
                </p>
            </div>
            <div style='text-align: center; margin-top: 30px; font-size: 12px; color: #888;'>
                <p>Este é um email automático enviado após sua solicitação de pré-matrícula.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Usar a função simplificada de envio de email
    return sendEmail($email, $subject, $htmlMessage);
}

/**
 * Função para enviar e-mail de notificação para o administrador
 */
function sendAdminNotificationEmail($firstName, $lastName, $email, $phone, $categoryName, $poloName, $prematriculaId) {
    // Incluir o helper de email
    require_once('simple_mail_helper.php');
    
    // ===== ALTERE O EMAIL DO ADMINISTRADOR AQUI =====
    // Email do administrador - IMPORTANTE: substitua pelo email correto
    // Este email receberá as notificações de novas pré-matrículas
    $adminEmail = 'magalhaeseducacao.aedu@gmail.com'; // SUBSTITUA PELO EMAIL REAL DO ADMINISTRADOR
    // ================================================
    
    // Registrar o email usado para diagnóstico
    error_log("Enviando notificação para administrador: $adminEmail");
    
    $subject = 'Nova Pré-matrícula: ' . $firstName . ' ' . $lastName . ' - ' . $categoryName;
    
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
    
    // Tentar enviar o email para o administrador
    $result = sendEmail($adminEmail, $subject, $htmlMessage);
    
    // Registrar o resultado no log
    $logMessage = date('Y-m-d H:i:s') . " - Notificação para admin ($adminEmail): " . ($result ? "Sucesso" : "Falha") . "\n";
    file_put_contents('admin_notification_log.txt', $logMessage, FILE_APPEND);
    
    // Retornar o resultado
    return $result;
}

/**
 * Função para enviar e-mail de aprovação para o aluno - Versão simplificada
 */
function sendApprovalEmail($email, $name, $categoryName, $poloName, $username, $password, $moodleUrl, $coursesCount) {
    // Incluir o helper de email
    require_once('simple_mail_helper.php');
    
    $subject = 'Matrícula Aprovada - ' . $categoryName . ' - Polo ' . $poloName;
    
    $htmlMessage = "
    <html>
    <head>
        <title>Matrícula Aprovada</title>
    </head>
    <body>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
            <div style='background-color: #2ecc71; color: white; padding: 15px; text-align: center;'>
                <h2>Matrícula Aprovada!</h2>
            </div>
            <div style='padding: 20px;'>
                <p>Olá <strong>{$name}</strong>,</p>
                <p>Temos o prazer de informar que sua matrícula foi aprovada e você já pode acessar o ambiente virtual de aprendizagem!</p>
                
                <div style='background-color: #e8f4fc; padding: 15px; margin: 20px 0;'>
                    <h3>Informações da Matrícula:</h3>
                    <p><strong>Polo:</strong> {$poloName}</p>
                    <p><strong>Curso:</strong> {$categoryName}</p>
                    <p><strong>Disciplinas:</strong> Você foi matriculado em {$coursesCount} disciplina(s)</p>
                </div>
                
                <p>Abaixo estão suas credenciais de acesso à plataforma:</p>
                
                <div style='background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #2ecc71;'>
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
            <div style='text-align: center; margin-top: 30px; font-size: 12px; color: #888;'>
                <p>Este é um email automático enviado após a aprovação da sua matrícula.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Usar a função simplificada de envio de email
    return sendEmail($email, $subject, $htmlMessage);
}

/**
 * Função para enviar e-mail de rejeição para o aluno - Versão simplificada
 */
function sendRejectionEmail($email, $name, $categoryName, $poloName, $reason) {
    // Incluir o helper de email
    require_once('simple_mail_helper.php');
    
    $subject = 'Informação Sobre Pré-matrícula - ' . $categoryName . ' - Polo ' . $poloName;
    
    $htmlMessage = "
    <html>
    <head>
        <title>Informações Sobre Pré-matrícula</title>
    </head>
    <body>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
            <div style='background-color: #3498db; color: white; padding: 15px; text-align: center;'>
                <h2>Informações Sobre Sua Pré-matrícula</h2>
            </div>
            <div style='padding: 20px;'>
                <p>Olá <strong>{$name}</strong>,</p>
                <p>Agradecemos pelo seu interesse em nossos cursos. Infelizmente, não foi possível aprovar sua pré-matrícula neste momento.</p>
                
                <div style='background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #3498db;'>
                    <h3>Observações:</h3>
                    <p>{$reason}</p>
                </div>
                
                <p>Se desejar obter mais informações ou discutir outras opções, por favor entre em contato pelo telefone (94) 98409-8666.</p>
                
                <p>
                Atenciosamente,<br>
                Equipe de Matrículas - Polo {$poloName}
                </p>
            </div>
            <div style='text-align: center; margin-top: 30px; font-size: 12px; color: #888;'>
                <p>Este é um email automático enviado em relação à sua solicitação de pré-matrícula.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Usar a função simplificada de envio de email
    return sendEmail($email, $subject, $htmlMessage);
}
?>