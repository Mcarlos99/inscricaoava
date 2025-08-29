<?php
/**
 * Script para examinar e consertar as funções de e-mail no painel de administração
 * Este script vai analisar o admin/prematriculas.php para identificar problemas específicos
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
    <title>Diagnóstico das Funções de E-mail do Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 2rem;
            padding-bottom: 2rem;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 900px;
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
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">Diagnóstico das Funções de E-mail do Admin</h1>';

// Verificar se o modo de correção está ativado
$fix_mode = isset($_GET['fix']) && $_GET['fix'] == 'true';
$show_code = isset($_GET['show_code']) && $_GET['show_code'] == 'true';

// Verificar se o arquivo existe
$admin_file = 'admin/prematriculas.php';
$admin_file_exists = file_exists($admin_file);

echo '<div class="card">
    <div class="card-header bg-primary text-white">Diagnóstico do Arquivo</div>
    <div class="card-body">';

if ($admin_file_exists) {
    echo '<p>O arquivo <code>admin/prematriculas.php</code> foi encontrado.</p>';
    
    // Carregar o conteúdo do arquivo
    $admin_file_content = file_get_contents($admin_file);
    
    // Função para extrair funções específicas de um arquivo PHP
    function extractFunction($content, $functionName) {
        $pattern = '/function\s+' . preg_quote($functionName, '/') . '\s*\([^)]*\)\s*{.*?}/s';
        
        if (preg_match($pattern, $content, $matches)) {
            return $matches[0];
        }
        
        return null;
    }
    
    // Verificar se as funções de e-mail existem
    $sendApprovalEmailExists = strpos($admin_file_content, 'function sendApprovalEmail') !== false;
    $sendRejectionEmailExists = strpos($admin_file_content, 'function sendRejectionEmail') !== false;
    
    if ($sendApprovalEmailExists && $sendRejectionEmailExists) {
        echo '<div class="alert alert-success">
                <h4><span class="success-icon">✓</span> Funções de E-mail Encontradas</h4>
                <p>As funções <code>sendApprovalEmail</code> e <code>sendRejectionEmail</code> foram encontradas no arquivo.</p>
              </div>';
        
        // Extrair código das funções
        $approvalFunction = extractFunction($admin_file_content, 'sendApprovalEmail');
        $rejectionFunction = extractFunction($admin_file_content, 'sendRejectionEmail');
        
        // Analisar problemas nas funções
        $analysisResults = [];
        
        // Verificar se usa mail_helper.php
        if (strpos($approvalFunction, 'mail_helper.php') === false) {
            $analysisResults[] = 'A função sendApprovalEmail não parece incluir o arquivo mail_helper.php';
        }
        
        // Verificar se tem return
        if (strpos($approvalFunction, 'return') === false) {
            $analysisResults[] = 'A função sendApprovalEmail não parece retornar um valor (falta "return")';
        }
        
        // Verificar se chama sendEmail
        if (strpos($approvalFunction, 'sendEmail') === false) {
            $analysisResults[] = 'A função sendApprovalEmail não parece chamar a função sendEmail';
        }
        
        // Verificar se tem log
        if (strpos($approvalFunction, 'log') === false && strpos($approvalFunction, 'Log') === false) {
            $analysisResults[] = 'A função sendApprovalEmail não parece registrar logs para diagnóstico';
        }
        
        if (!empty($analysisResults)) {
            echo '<div class="alert alert-warning">
                    <h4><span class="warning-icon">⚠</span> Problemas Potenciais Encontrados</h4>
                    <ul>';
            
            foreach ($analysisResults as $result) {
                echo '<li>' . $result . '</li>';
            }
            
            echo '</ul>
                  </div>';
        } else {
            echo '<div class="alert alert-info">
                    <p>Nenhum problema óbvio foi detectado nas funções.</p>
                  </div>';
        }
        
        // Mostrar código das funções se solicitado
        if ($show_code) {
            echo '<div class="mt-4">
                    <h5>Código da Função sendApprovalEmail</h5>
                    <pre>' . htmlspecialchars($approvalFunction) . '</pre>
                    
                    <h5>Código da Função sendRejectionEmail</h5>
                    <pre>' . htmlspecialchars($rejectionFunction) . '</pre>
                  </div>';
        } else {
            echo '<div class="mt-3">
                    <a href="?show_code=true" class="btn btn-sm btn-info">Mostrar Código das Funções</a>
                  </div>';
        }
        
        // Opção para substituir as funções
        if ($fix_mode) {
            // Fazer backup do arquivo
            $backup_file = $admin_file . '.bak.' . date('YmdHis');
            copy($admin_file, $backup_file);
            
            // Carregar funções corrigidas
            $new_functions = file_get_contents('email_functions_standalone.php');
            
            // Remover a abertura e fechamento de tags PHP e comentários iniciais
            $new_functions = preg_replace('/^<\?php.*?\*\//s', '', $new_functions);
            $new_functions = str_replace('?>', '', $new_functions);
            
            // Substituir as funções antigas pelas novas
            $pattern_approval = '/function\s+sendApprovalEmail\s*\([^)]*\)\s*{.*?}/s';
            $pattern_rejection = '/function\s+sendRejectionEmail\s*\([^)]*\)\s*{.*?}/s';
            
            // Extrair os novos conteúdos das funções
            $new_approval_function = extractFunction($new_functions, 'sendApprovalEmail');
            $new_rejection_function = extractFunction($new_functions, 'sendRejectionEmail');
            
            if ($new_approval_function && $new_rejection_function) {
                // Substituir as funções antigas
                $fixed_content = preg_replace($pattern_approval, $new_approval_function, $admin_file_content);
                $fixed_content = preg_replace($pattern_rejection, $new_rejection_function, $fixed_content);
                
                // Verificar se algo foi substituído
                if ($fixed_content !== $admin_file_content) {
                    // Salvar o arquivo corrigido
                    file_put_contents($admin_file, $fixed_content);
                    
                    echo '<div class="alert alert-success mt-3">
                            <h4>Funções Substituídas com Sucesso</h4>
                            <p>As funções de e-mail foram substituídas pelas versões corrigidas.</p>
                            <p>Um backup do arquivo original foi criado em: ' . htmlspecialchars($backup_file) . '</p>
                          </div>';
                } else {
                    echo '<div class="alert alert-danger mt-3">
                            <h4>Falha na Substituição</h4>
                            <p>Não foi possível substituir as funções. Os padrões não corresponderam.</p>
                          </div>';
                }
            } else {
                echo '<div class="alert alert-danger mt-3">
                        <h4>Erro ao Extrair Funções Corrigidas</h4>
                        <p>Não foi possível extrair as funções do arquivo email_functions_standalone.php.</p>
                      </div>';
            }
        } else {
            echo '<div class="alert alert-info mt-3">
                    <p>Você pode substituir as funções existentes pelas versões corrigidas clicando no botão abaixo:</p>
                    <a href="?fix=true" class="btn btn-warning">Substituir Funções</a>
                  </div>';
        }
    } else {
        echo '<div class="alert alert-danger">
                <h4><span class="error-icon">✗</span> Funções de E-mail Não Encontradas</h4>
                <p>As funções <code>sendApprovalEmail</code> e/ou <code>sendRejectionEmail</code> não foram encontradas no arquivo.</p>
                <p>Isso indica que o arquivo pode ter sido modificado ou substituído incorretamente.</p>
              </div>';
        
        // Oferecer opção para adicionar as funções
        if ($fix_mode) {
            // Fazer backup do arquivo
            $backup_file = $admin_file . '.bak.' . date('YmdHis');
            copy($admin_file, $backup_file);
            
            // Carregar funções corrigidas
            $new_functions = file_get_contents('email_functions_standalone.php');
            
            // Remover a abertura e fechamento de tags PHP e comentários iniciais
            $new_functions = preg_replace('/^<\?php.*?\*\//s', '', $new_functions);
            $new_functions = str_replace('?>', '', $new_functions);
            
            // Procurar um bom local para inserir as funções (antes da função main ou no final do arquivo)
            $insert_pos = strrpos($admin_file_content, '?>');
            if ($insert_pos === false) {
                // Se não houver tag de fechamento, adicionar no final
                $fixed_content = $admin_file_content . "\n\n" . $new_functions;
            } else {
                // Inserir antes do fechamento
                $fixed_content = substr($admin_file_content, 0, $insert_pos) . "\n\n" . $new_functions . "\n\n" . substr($admin_file_content, $insert_pos);
            }
            
            // Salvar o arquivo modificado
            file_put_contents($admin_file, $fixed_content);
            
            echo '<div class="alert alert-success mt-3">
                    <h4>Funções Adicionadas com Sucesso</h4>
                    <p>As funções de e-mail foram adicionadas ao arquivo.</p>
                    <p>Um backup do arquivo original foi criado em: ' . htmlspecialchars($backup_file) . '</p>
                  </div>';
        } else {
            echo '<div class="alert alert-info mt-3">
                    <p>Você pode adicionar as funções corrigidas ao arquivo clicando no botão abaixo:</p>
                    <a href="?fix=true" class="btn btn-warning">Adicionar Funções</a>
                  </div>';
        }
    }
} else {
    echo '<div class="alert alert-danger">
            <h4>Arquivo não encontrado</h4>
            <p>O arquivo admin/prematriculas.php não foi encontrado. Verifique se a pasta admin existe e contém o arquivo de aprovação de pré-matrículas.</p>
          </div>';
}

echo '</div>
</div>';

// Verificar logs de e-mail existentes
echo '<div class="card">
    <div class="card-header bg-primary text-white">Logs de E-mail</div>
    <div class="card-body">';

$log_files = [
    'approval_email_log.txt' => 'Log de E-mails de Aprovação',
    'email_log.txt' => 'Log Geral de E-mails',
    'email_error_log.txt' => 'Log de Erros de E-mail'
];

$found_logs = false;

foreach ($log_files as $file => $description) {
    if (file_exists($file)) {
        $found_logs = true;
        $log_content = file_get_contents($file);
        $last_lines = implode("\n", array_slice(explode("\n", $log_content), -20));
        
        echo '<h5>' . $description . '</h5>';
        echo '<pre>' . htmlspecialchars($last_lines) . '</pre>';
    }
}

if (!$found_logs) {
    echo '<div class="alert alert-warning">
            <p>Nenhum arquivo de log foi encontrado. Isso pode indicar que os e-mails não estão sendo registrados corretamente.</p>
          </div>';
}

echo '</div>
</div>';

// Verificar mail_helper.php
echo '<div class="card">
    <div class="card-header bg-primary text-white">Verificação de mail_helper.php</div>
    <div class="card-body">';

$mail_helper_file = 'mail_helper.php';
$mail_helper_exists = file_exists($mail_helper_file);

if ($mail_helper_exists) {
    $mail_helper_content = file_get_contents($mail_helper_file);
    
    // Verificar se contém a função sendEmail
    $sendEmailExists = strpos($mail_helper_content, 'function sendEmail') !== false;
    
    if ($sendEmailExists) {
        echo '<div class="alert alert-success">
                <h4><span class="success-icon">✓</span> Arquivo mail_helper.php válido</h4>
                <p>O arquivo mail_helper.php foi encontrado e contém a função sendEmail.</p>
              </div>';
        
        // Verificar se prioriza SMTP
        if (strpos($mail_helper_content, 'smtp_config.php') !== false) {
            echo '<div class="alert alert-success">
                    <p>O arquivo mail_helper.php parece estar configurado para usar SMTP quando disponível.</p>
                  </div>';
        } else {
            echo '<div class="alert alert-warning">
                    <p>O arquivo mail_helper.php não parece verificar a existência do arquivo smtp_config.php.</p>
                    <p>Isso pode fazer com que os e-mails sejam enviados usando a função mail() do PHP em vez de SMTP.</p>
                  </div>';
            
            // Oferecer opção para atualizar o mail_helper.php
            if ($fix_mode) {
                // Verificar se o arquivo simple_mail_helper.php existe
                if (file_exists('simple_mail_helper.php')) {
                    // Fazer backup do arquivo existente
                    $backup_file = $mail_helper_file . '.bak.' . date('YmdHis');
                    copy($mail_helper_file, $backup_file);
                    
                    // Copiar o arquivo simple_mail_helper.php para mail_helper.php
                    copy('simple_mail_helper.php', $mail_helper_file);
                    
                    echo '<div class="alert alert-success mt-3">
                            <h4>Arquivo mail_helper.php Atualizado</h4>
                            <p>O arquivo mail_helper.php foi atualizado para usar SMTP quando disponível.</p>
                            <p>Um backup do arquivo original foi criado em: ' . htmlspecialchars($backup_file) . '</p>
                          </div>';
                } else {
                    echo '<div class="alert alert-danger mt-3">
                            <h4>Arquivo simple_mail_helper.php Não Encontrado</h4>
                            <p>Não foi possível encontrar o arquivo simple_mail_helper.php para usar como substituto.</p>
                          </div>';
                }
            } else {
                echo '<div class="alert alert-info mt-3">
                        <p>Você pode atualizar o arquivo mail_helper.php para usar SMTP clicando no botão abaixo:</p>
                        <a href="?fix=true" class="btn btn-warning">Atualizar mail_helper.php</a>
                      </div>';
            }
        }
    } else {
        echo '<div class="alert alert-danger">
                <h4><span class="error-icon">✗</span> Função sendEmail não encontrada</h4>
                <p>O arquivo mail_helper.php não contém a função sendEmail que é necessária para o envio de e-mails.</p>
              </div>';
    }
} else {
    echo '<div class="alert alert-danger">
            <h4><span class="error-icon">✗</span> Arquivo mail_helper.php não encontrado</h4>
            <p>O arquivo mail_helper.php não foi encontrado. Este arquivo é necessário para o envio de e-mails.</p>
          </div>';
}

echo '</div>
</div>';

// Botões de ação
echo '<div class="mt-4 mb-5 text-center">
    <a href="index.html" class="btn btn-secondary">Voltar para o formulário de pré-matrícula</a>
    <a href="test_approval_email.php" class="btn btn-info">Testar E-mail de Aprovação</a>
    ' . (!$fix_mode ? '<a href="?fix=true" class="btn btn-primary">Corrigir Problemas</a>' : '') . '
</div>';

echo '</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
?>