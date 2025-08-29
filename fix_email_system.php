<?php
/**
 * Script para corrigir o erro 500 na página de administração
 * Este arquivo detecta o problema e oferece uma solução
 */

// Exibir erros para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir cabeçalho HTML
echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correção da Página de Administração</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 2rem;
            padding-bottom: 2rem;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            font-weight: bold;
        }
        .success-icon {
            color: #198754;
            font-size: 1.5rem;
        }
        .warning-icon {
            color: #ffc107;
            font-size: 1.5rem;
        }
        .error-icon {
            color: #dc3545;
            font-size: 1.5rem;
        }
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 4px;
            color: #d63384;
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
        <h1 class="text-center mb-4">Correção da Página de Administração</h1>';

// Verificar se o modo de correção está ativado
$fix_mode = isset($_GET['fix']) && $_GET['fix'] == 'true';

// Verificar se o arquivo existe
$admin_file = 'admin/prematriculas.php';
$admin_file_exists = file_exists($admin_file);

echo '<div class="card">
    <div class="card-header bg-primary text-white">Diagnóstico do Problema</div>
    <div class="card-body">';

if ($admin_file_exists) {
    // Verificar o conteúdo do arquivo
    $admin_file_content = file_get_contents($admin_file);
    
    // Verificar se o arquivo foi modificado incorretamente
    $possible_issues = [
        'require_once(\'../email_approval_functions.php\');' => 'Inclusão incorreta do arquivo email_approval_functions.php',
        'email_approval_functions.php' => 'Referência ao arquivo email_approval_functions.php está causando problemas'
    ];
    
    $found_issues = [];
    foreach ($possible_issues as $pattern => $description) {
        if (strpos($admin_file_content, $pattern) !== false) {
            $found_issues[$pattern] = $description;
        }
    }
    
    if (!empty($found_issues)) {
        echo '<div class="alert alert-danger">
                <h4>Problema Detectado</h4>
                <p>Foi encontrado um problema no arquivo admin/prematriculas.php que está causando o erro 500:</p>
                <ul>';
        
        foreach ($found_issues as $pattern => $description) {
            echo '<li><strong>' . htmlspecialchars($description) . '</strong>: A linha <code>' . htmlspecialchars($pattern) . '</code> está causando problemas.</li>';
        }
        
        echo '</ul>
              </div>';
        
        if ($fix_mode) {
            // Criar backup do arquivo
            $backup_file = $admin_file . '.bak.' . date('YmdHis');
            copy($admin_file, $backup_file);
            
            // Remover as inclusões problemáticas
            $fixed_content = $admin_file_content;
            foreach (array_keys($found_issues) as $pattern) {
                $fixed_content = str_replace($pattern, '// Removido para corrigir erro 500: ' . $pattern, $fixed_content);
            }
            
            // Salvar o arquivo corrigido
            file_put_contents($admin_file, $fixed_content);
            
            echo '<div class="alert alert-success">
                    <h4>Arquivo Corrigido</h4>
                    <p>O arquivo admin/prematriculas.php foi corrigido removendo as linhas problemáticas.</p>
                    <p>Um backup foi criado em: ' . htmlspecialchars($backup_file) . '</p>
                  </div>';
        } else {
            echo '<div class="alert alert-info">
                    <p>Clique no botão abaixo para corrigir automaticamente o problema:</p>
                    <a href="?fix=true" class="btn btn-primary">Corrigir Problema</a>
                  </div>';
        }
    } else {
        echo '<div class="alert alert-warning">
                <h4>Nenhum problema óbvio detectado</h4>
                <p>Não foi possível identificar automaticamente o problema no arquivo admin/prematriculas.php.</p>
                <p>Recomendamos restaurar o arquivo para uma versão anterior que funcionava corretamente.</p>
              </div>';
    }
} else {
    echo '<div class="alert alert-danger">
            <h4>Arquivo não encontrado</h4>
            <p>O arquivo admin/prematriculas.php não foi encontrado. Verifique se a pasta admin existe e contém o arquivo de aprovação de pré-matrículas.</p>
          </div>';
}

echo '</div>
</div>';

// Oferecer solução alternativa
echo '<div class="card">
    <div class="card-header bg-primary text-white">Solução Alternativa</div>
    <div class="card-body">
        <p>Se a correção automática não resolver o problema, você pode aplicar diretamente as funções necessárias para envio de e-mail sem modificar a estrutura original do arquivo:</p>
        
        <div class="alert alert-info">
            <h5>Método de Integração Recomendado</h5>
            <p>Em vez de incluir o arquivo <code>email_approval_functions.php</code>, você pode copiar diretamente o código das funções para o arquivo <code>admin/prematriculas.php</code>.</p>
            <p>Localize as funções <code>sendApprovalEmail</code> e <code>sendRejectionEmail</code> no arquivo existente e substitua-as pelas versões atualizadas que utilizam SMTP.</p>
        </div>
        
        <div class="mt-4">
            <h5>Passos Manuais para Correção:</h5>
            <ol>
                <li>Restaure o arquivo admin/prematriculas.php para a versão original (se necessário)</li>
                <li>Abra o arquivo e localize as funções <code>sendApprovalEmail</code> e <code>sendRejectionEmail</code></li>
                <li>Certifique-se de que a função <code>sendEmail</code> está sendo usado para o envio (não diretamente SMTP)</li>
                <li>Verifique se o arquivo <code>simple_mail_helper.php</code> está corretamente configurado para usar SMTP</li>
                <li>Teste enviando uma aprovação de pré-matrícula</li>
            </ol>
        </div>
    </div>
</div>';

// Explicar como testar o e-mail de aprovação independentemente
echo '<div class="card">
    <div class="card-header bg-primary text-white">Testando E-mail de Aprovação</div>
    <div class="card-body">
        <p>Você pode testar o e-mail de aprovação independentemente do painel de administração utilizando o arquivo <code>test_approval_email.php</code>:</p>
        
        <div class="mt-3 mb-3">
            ' . (file_exists('test_approval_email.php') ? 
                '<a href="test_approval_email.php" class="btn btn-success">Testar E-mail de Aprovação</a>' : 
                '<button class="btn btn-secondary" disabled>Teste de E-mail não disponível</button>') . '
        </div>
        
        <p>Este teste ajudará a identificar se o problema está no envio do e-mail ou na integração com o painel de administração.</p>
    </div>
</div>';

// Botões de ação
echo '<div class="mt-4 mb-5 text-center">
    <a href="index.html" class="btn btn-secondary">Voltar para o formulário de pré-matrícula</a>
    <a href="smtp_test.php" class="btn btn-info">Testar Configuração SMTP</a>
    ' . (!$fix_mode && $admin_file_exists ? '<a href="?fix=true" class="btn btn-primary">Corrigir Automaticamente</a>' : '') . '
</div>';

echo '</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
?>