<?php
// Configuração SMTP para envio de emails
// Coloque este arquivo na raiz do seu site

$EMAIL_CONFIG = [
    'smtp_host' => 'smtp.gmail.com', // Host SMTP (altere conforme seu provedor de email)
    'smtp_port' => 587, // Porta SMTP
    'smtp_username' => 'magalhaeseducacao.aedu@gmail.com', // Seu email
    'smtp_password' => 'ojms ehos qnei ywka', // Sua senha de app (não sua senha normal do Gmail)
    'smtp_secure' => 'tls', // tls ou ssl
    'from_email' => 'magalhaeseducacao.aedu@gmail.com', // Email de remetente padrão
    'from_name' => 'IMEP EDU' // Nome do remetente padrão
];

// Nota: Para Gmail, você deve criar uma "Senha de App" em:
// https://myaccount.google.com/security > "Senhas de app"
// Isso requer a verificação em duas etapas ativada
?>