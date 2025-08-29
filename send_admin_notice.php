<?php
/**
 * Script para enviar uma notificação de teste para o administrador
 * diretamente a partir de uma pré-matrícula existente
 */

// Exibir erros para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar se o ID da pré-matrícula foi fornecido
if (empty($_GET['id'])) {
    die("Por favor, forneça um ID de pré-matrícula, ex: send_admin_notice.php?id=123");
}

$prematriculaId = (int)$_GET['id'];

// Configurações do banco de dados - ajuste conforme seu ambiente
$db_host = 'localhost';
$db_name = 'inscricaoavadb';
$db_user = 'inscricaoavauser';
$db_pass = '05hsqwjG8vLsIVBvQ7Iu';

try {
    // Conectar ao banco de dados
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar a pré-matrícula pelo ID
    $stmt = $pdo->prepare("SELECT * FROM prematriculas WHERE id = ?");
    $stmt->execute([$prematriculaId]);
    $prematricula = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$prematricula) {
        die("Pré-matrícula com ID $prematriculaId não encontrada.");
    }
    
    // Extrair os dados da pré-matrícula
    $firstName = $prematricula['first_name'];
    $lastName = $prematricula['last_name'];
    $email = $prematricula['email'];
    $phone = $prematricula['phone'];
    $categoryName = $prematricula['category_name'];
    $poloName = $prematricula['polo_name'];
    
    // Incluir o helper de email
    if (file_exists('simple_mail_helper.php')) {
        require_once('simple_mail_helper.php');
    } else {
        die("Arquivo simple_mail_helper.php não encontrado.");
    }
    
    // Email do administrador (altere aqui)
    $adminEmail = "magalhaeseducacao.aedu@gmail.com"; // SUBSTITUA PELO EMAIL REAL
    
    // Montar o email de notificação manualmente
    $subject = "Teste de Notificação: Nova Pré-matrícula #$prematriculaId";
    
    $htmlMessage = "
    <html>
    <head>
        <title>Teste de Notificação de Pré-matrícula</title>
    </head>
    <body>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
            <div style='background-color: #3498db; color: white; padding: 15px; text-align: center;'>
                <h2>TESTE - Nova Pré-matrícula Recebida</h2>
            </div>
            <div style='padding: 20px;'>
                <p style='color: red; font-weight: bold;'>ESTE É APENAS UM TESTE DE NOTIFICAÇÃO</p>
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
                
                <div style='background-color: #f2dede; border-left: 4px solid #b94a48; padding: 15px; margin-top: 30px;'>
                    <p style='margin: 0;'><strong>Nota de teste:</strong> Este é um email de teste enviado manualmente para verificar o sistema de notificação do administrador.</p>
                    <p style='margin-top: 10px;'>Data e hora do teste: " . date('d/m/Y H:i:s') . "</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Enviar o email diretamente usando sendEmail
    $result = sendEmail($adminEmail, $subject, $htmlMessage);
    
    // Exibir o resultado
    if ($result) {
        echo "<div style='max-width: 600px; margin: 50px auto; padding: 20px; background-color: #dff0d8; border: 1px solid #d6e9c6; border-radius: 4px;'>";
        echo "<h2 style='color: #3c763d;'>Email enviado com sucesso!</h2>";
        echo "<p>O email de teste foi enviado para: <strong>$adminEmail</strong></p>";
        echo "<p>Verifique sua caixa de entrada (e pasta de spam) para confirmar o recebimento.</p>";
        echo "<p>Detalhes da pré-matrícula:</p>";
        echo "<ul>";
        echo "<li>ID: $prematriculaId</li>";
        echo "<li>Aluno: $firstName $lastName</li>";
        echo "<li>Curso: $categoryName</li>";
        echo "<li>Polo: $poloName</li>";
        echo "</ul>";
        echo "<p><a href='index.html'>Voltar para o início</a></p>";
        echo "</div>";
    } else {
        echo "<div style='max-width: 600px; margin: 50px auto; padding: 20px; background-color: #f2dede; border: 1px solid #ebccd1; border-radius: 4px;'>";
        echo "<h2 style='color: #a94442;'>Erro ao enviar email!</h2>";
        echo "<p>Não foi possível enviar o email para: <strong>$adminEmail</strong></p>";
        echo "<p>Verifique o arquivo de log para mais detalhes.</p>";
        echo "<p><a href='test_admin_email.php'>Executar diagnóstico de email</a></p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}
?>