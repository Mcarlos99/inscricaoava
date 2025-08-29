<?php
/**
 * Função para enviar e-mail de pré-matrícula para o aluno
 */
function sendPreMatriculaEmail($email, $name, $categoryName, $poloName, $type = 'new') {
    // Incluir o helper de email
    require_once(__DIR__ . '/mail_helper.php');
    
    switch ($type) {
        case 'update':
            $subject = 'Atualização de Pré-matrícula - ' . $categoryName . ' - Polo ' . $poloName;
            $title = 'Pré-matrícula Atualizada!';
            $message = "Sua pré-matrícula foi atualizada com sucesso. Nossos atendentes entrarão em contato com você em breve para discutir os detalhes de pagamento e finalizar o processo de matrícula.";
            break;
            
        case 'renew':
            $subject = 'Nova Solicitação de Pré-matrícula - ' . $categoryName . ' - Polo ' . $poloName;
            $title = 'Nova Solicitação Enviada!';
            $message = "Sua nova solicitação de pré-matrícula foi enviada com sucesso. Nossos atendentes entrarão em contato com você em breve para discutir os detalhes de pagamento e finalizar o processo de matrícula.";
            break;
            
        default: // new
            $subject = 'Confirmação de Pré-matrícula - ' . $categoryName . ' - Polo ' . $poloName;
            $title = 'Pré-matrícula Recebida!';
            $message = "Sua pré-matrícula foi recebida com sucesso. Nossos atendentes entrarão em contato com você em breve para discutir os detalhes de pagamento e finalizar o processo de matrícula.";
    }
    
    $htmlMessage = "
    <html>
    <head>
        <title>Confirmação de Pré-matrícula</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #3498db; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; }
            .course-info { background-color: #e8f4fc; padding: 15px; margin: 20px 0; }
            .next-steps { background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #3498db; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #888; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>{$title}</h2>
            </div>
            <div class='content'>
                <p>Olá <strong>{$name}</strong>,</p>
                <p>{$message}</p>
                
                <div class='course-info'>
                    <h3>Informações da Pré-matrícula:</h3>
                    <p><strong>Polo:</strong> {$poloName}</p>
                    <p><strong>Curso:</strong> {$categoryName}</p>
                </div>
                
                <div class='next-steps'>
                    <h3>Próximos Passos:</h3>
                    <ol>
                        <li>Nossa equipe entrará em contato com você em até 48 horas úteis.</li>
                        <li>Você poderá escolher a forma de pagamento e discutir os valores com nosso atendente.</li>
                        <li>Após confirmação do pagamento, sua matrícula será ativada.</li>
                        <li>Você receberá um email com as credenciais de acesso à plataforma.</li>
                    </ol>
                </div>
                
                <p>Caso tenha alguma dúvida, sinta-se à vontade para entrar em contato pelo telefone (94) 98409-8666 ou responder a este email.</p>
                
                <p>
                Atenciosamente,<br>
                Equipe de Matrículas - Polo {$poloName}
                </p>
            </div>
            <div class='footer'>
                <p>Este é um email automático enviado após sua solicitação de pré-matrícula.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Usar a função melhorada de envio de email
    return sendEmail($email, $subject, $htmlMessage);
}

/**
 * Função para enviar e-mail de notificação para o administrador
 */
function sendAdminNotificationEmail($firstName, $lastName, $email, $phone, $categoryName, $poloName, $prematriculaId) {
    // Incluir o helper de email
    require_once(__DIR__ . '/mail_helper.php');
    
    // Email do administrador - ajuste conforme necessário
    $adminEmail = 'admin@imepedu.com.br';
    
    $subject = 'Nova Pré-matrícula: ' . $firstName . ' ' . $lastName . ' - ' . $categoryName;
    
    $htmlMessage = "
    <html>
    <head>
        <title>Nova Pré-matrícula</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #3498db; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; }
            .student-info { background-color: #f9f9f9; padding: 15px; margin: 20px 0; }
            .action-button { display: block; width: 200px; margin: 20px auto; padding: 10px; background-color: #3498db; color: white; text-align: center; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Nova Pré-matrícula Recebida</h2>
            </div>
            <div class='content'>
                <p>Uma nova solicitação de pré-matrícula foi recebida.</p>
                
                <div class='student-info'>
                    <h3>Informações do Aluno:</h3>
                    <p><strong>Nome:</strong> {$firstName} {$lastName}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Telefone:</strong> {$phone}</p>
                    <p><strong>Curso:</strong> {$categoryName}</p>
                    <p><strong>Polo:</strong> {$poloName}</p>
                    <p><strong>ID da Pré-matrícula:</strong> {$prematriculaId}</p>
                </div>
                
                <p>Por favor, entre em contato com o aluno para discutir os detalhes de pagamento e finalizar o processo de matrícula.</p>
                
                <a href='https://inscricaoava.imepedu.com.br/admin/prematriculas.php?key=admin123' class='action-button'>Gerenciar Pré-matrículas</a>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Usar a função melhorada de envio de email
    return sendEmail($adminEmail, $subject, $htmlMessage);
}