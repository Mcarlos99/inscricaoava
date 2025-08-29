<?php
/**
 * Monitor de envio de e-mails para verificar o método utilizado
 * Este script verifica os logs para determinar como os e-mails estão sendo enviados
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
    <title>Monitor de Envio de E-mails</title>
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
        .method-smtp {
            color: #198754;
            font-weight: bold;
        }
        .method-mail {
            color: #dc3545;
            font-weight: bold;
        }
        .filter-controls {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">Monitor de Envio de E-mails</h1>';

// Verificar se devemos atualizar os logs
$refresh_logs = isset($_GET['refresh']) && $_GET['refresh'] == 'true';
$filter_email = isset($_GET['email']) ? $_GET['email'] : '';
$filter_method = isset($_GET['method']) ? $_GET['method'] : '';

echo '<div class="card">
    <div class="card-header bg-primary text-white">Análise dos Logs de E-mail</div>
    <div class="card-body">';

// Lista de arquivos de log para analisar
$log_files = [
    'email_log.txt' => 'Log Geral de E-mails',
    'approval_email_log.txt' => 'Log de E-mails de Aprovação',
    'admin/email_log.txt' => 'Log de E-mails da Área Admin',
    'admin_email.log' => 'Log de E-mails de Admin Alternativo',
    'smtp_test_log.txt' => 'Log de Testes SMTP'
];

// Verificar quais arquivos de log existem
$existing_logs = [];
foreach ($log_files as $file => $description) {
    if (file_exists($file)) {
        $existing_logs[$file] = $description;
    }
}

if (empty($existing_logs)) {
    echo '<div class="alert alert-warning">
            <h4><span class="warning-icon">⚠</span> Nenhum Log Encontrado</h4>
            <p>Não foi possível encontrar arquivos de log para analisar.</p>
            <p>Isso pode significar que:</p>
            <ul>
                <li>Nenhum e-mail foi enviado ainda</li>
                <li>Os logs não estão sendo gravados corretamente</li>
                <li>Os arquivos de log estão em um local diferente</li>
            </ul>
          </div>';
    
    // Criar arquivos de log vazios para futuros registros
    if ($refresh_logs) {
        foreach ($log_files as $file => $description) {
            if (!file_exists($file)) {
                $dir = dirname($file);
                if ($dir != '.' && !is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($file, "# Log de e-mail criado em " . date('Y-m-d H:i:s') . "\n");
                echo '<div class="alert alert-info">
                        <p>Arquivo de log criado: ' . htmlspecialchars($file) . '</p>
                      </div>';
            }
        }
    } else {
        echo '<div class="mt-3">
                <a href="?refresh=true" class="btn btn-primary">Criar Arquivos de Log</a>
              </div>';
    }
} else {
    // Controles de filtro
    echo '<div class="filter-controls">
            <form method="get" class="row g-3">
                <div class="col-md-5">
                    <label for="email" class="form-label">Filtrar por e-mail:</label>
                    <input type="text" class="form-control" id="email" name="email" value="' . htmlspecialchars($filter_email) . '">
                </div>
                <div class="col-md-5">
                    <label for="method" class="form-label">Filtrar por método:</label>
                    <select class="form-select" id="method" name="method">
                        <option value="" ' . ($filter_method == '' ? 'selected' : '') . '>Todos</option>
                        <option value="smtp" ' . ($filter_method == 'smtp' ? 'selected' : '') . '>SMTP</option>
                        <option value="mail" ' . ($filter_method == 'mail' ? 'selected' : '') . '>PHP mail()</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>';
    
    // Analisar os logs existentes
    $email_entries = [];
    $smtp_count = 0;
    $mail_count = 0;
    $unknown_count = 0;
    
    foreach ($existing_logs as $file => $description) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            
            foreach ($lines as $line) {
                // Verificar se a linha contém informações de e-mail
                if (empty($line)) continue;
                
                // Filtrar por e-mail se especificado
                if (!empty($filter_email) && strpos($line, $filter_email) === false) {
                    continue;
                }
                
                // Identificar o método de envio (SMTP ou mail)
                $method = 'unknown';
                if (strpos($line, '[smtp]') !== false || strpos($line, '[SMTP]') !== false) {
                    $method = 'smtp';
                    $smtp_count++;
                } elseif (strpos($line, '[mail]') !== false || strpos($line, '[PHP mail]') !== false) {
                    $method = 'mail';
                    $mail_count++;
                } else {
                    $unknown_count++;
                }
                
                // Filtrar por método se especificado
                if (!empty($filter_method) && $method != $filter_method) {
                    continue;
                }
                
                // Adicionar a entrada à lista
                $email_entries[] = [
                    'line' => $line,
                    'file' => $file,
                    'method' => $method
                ];
            }
        }
    }
    
    // Exibir estatísticas
    echo '<div class="alert alert-info">
            <h4>Estatísticas de Envio</h4>
            <p>Total de registros de e-mail: <strong>' . count($email_entries) . '</strong></p>
            <p>E-mails enviados via SMTP: <strong>' . $smtp_count . '</strong></p>
            <p>E-mails enviados via PHP mail(): <strong>' . $mail_count . '</strong></p>
            <p>E-mails com método desconhecido: <strong>' . $unknown_count . '</strong></p>
          </div>';
    
    // Exibir entradas de log
    if (!empty($email_entries)) {
        echo '<h4>Entradas de Log de E-mail</h4>';
        echo '<div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Arquivo</th>
                            <th>Método</th>
                            <th>Entrada</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($email_entries as $entry) {
            $method_class = 'method-unknown';
            $method_label = 'Desconhecido';
            
            if ($entry['method'] == 'smtp') {
                $method_class = 'method-smtp';
                $method_label = 'SMTP';
            } elseif ($entry['method'] == 'mail') {
                $method_class = 'method-mail';
                $method_label = 'PHP mail()';
            }
            
            echo '<tr>
                    <td>' . htmlspecialchars(basename($entry['file'])) . '</td>
                    <td class="' . $method_class . '">' . $method_label . '</td>
                    <td>' . htmlspecialchars($entry['line']) . '</td>
                  </tr>';
        }
        
        echo '</tbody>
              </table>
              </div>';
    } else {
        echo '<div class="alert alert-warning">
                <p>Nenhuma entrada de log corresponde aos filtros aplicados.</p>
              </div>';
    }
}

echo '</div>
</div>';

// Adicionar um rastreador para mail_helper.php
echo '<div class="card">
    <div class="card-header bg-primary text-white">Rastreador de Funções de E-mail</div>
    <div class="card-body">';

if (file_exists('mail_helper.php')) {
    $mail_helper_content = file_get_contents('mail_helper.php');
    
    // Verificar se o arquivo contém a função sendEmail
    if (strpos($mail_helper_content, 'function sendEmail') !== false) {
        echo '<div class="alert alert-success">
                <h4><span class="success-icon">✓</span> Arquivo mail_helper.php válido</h4>
                <p>O arquivo mail_helper.php contém a função sendEmail.</p>
              </div>';
        
        // Verificar se a função sendEmail já registra o método utilizado
        $has_method_logging = strpos($mail_helper_content, '[smtp]') !== false || 
                              strpos($mail_helper_content, '[mail]') !== false;
        
        if (!$has_method_logging) {
            echo '<div class="alert alert-warning">
                    <h4><span class="warning-icon">⚠</span> Sem registro de método</h4>
                    <p>O arquivo mail_helper.php não parece registrar explicitamente qual método (SMTP ou mail) está sendo usado.</p>
                    <p>Isso dificulta a identificação de como os e-mails estão sendo enviados.</p>
                  </div>';
            
            if (isset($_GET['patch_logger']) && $_GET['patch_logger'] == 'true') {
                // Fazer backup do arquivo
                $backup_file = 'mail_helper.php.bak.' . date('YmdHis');
                copy('mail_helper.php', $backup_file);
                
                // Modificar a função logEmailSend para incluir o método no log
                $new_content = preg_replace(
                    '/function logEmailSend\(\$to, \$subject, \$result, \$method = \'mail\'\) {/',
                    'function logEmailSend($to, $subject, $result, $method = \'mail\') {
        // Converter método para minúsculas para consistência
        $method = strtolower($method);',
                    $mail_helper_content
                );
                
                $new_content = preg_replace(
                    '/\$logMessage = "\[\{\$timestamp\}\] \[\{\$status\}\] \[\{\$method\}\] To: \{\$to\} \| Subject: \{\$subject\}\\n";/',
                    '$logMessage = "[{$timestamp}] [{$status}] [METHOD:{$method}] To: {$to} | Subject: {$subject}\\n";',
                    $new_content
                );
                
                if ($new_content !== $mail_helper_content) {
                    file_put_contents('mail_helper.php', $new_content);
                    echo '<div class="alert alert-success">
                            <h4>Registro de método melhorado</h4>
                            <p>O arquivo mail_helper.php foi modificado para registrar mais claramente o método de envio.</p>
                            <p>Um backup foi criado em: ' . htmlspecialchars($backup_file) . '</p>
                          </div>';
                } else {
                    echo '<div class="alert alert-danger">
                            <h4>Falha na modificação</h4>
                            <p>Não foi possível modificar o arquivo mail_helper.php. O padrão de busca não corresponde.</p>
                          </div>';
                }
            } else {
                echo '<div class="mt-3">
                        <a href="?patch_logger=true" class="btn btn-warning">Melhorar Registro de Método</a>
                      </div>';
            }
        } else {
            echo '<div class="alert alert-success">
                    <p>O arquivo mail_helper.php já registra o método de envio (SMTP ou mail).</p>
                  </div>';
        }
        
        // Verificar se o mail_helper prioriza SMTP
        if (strpos($mail_helper_content, 'smtp_config.php') !== false) {
            echo '<div class="alert alert-success">
                    <p>O arquivo mail_helper.php está configurado para usar SMTP quando disponível.</p>
                  </div>';
        } else {
            echo '<div class="alert alert-warning">
                    <p>O arquivo mail_helper.php não parece verificar a existência do arquivo smtp_config.php.</p>
                    <p>Isso pode fazer com que os e-mails sejam enviados usando a função mail() do PHP em vez de SMTP.</p>
                  </div>';
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

// Adicionar um monitor para admin/prematriculas.php
echo '<div class="card">
    <div class="card-header bg-primary text-white">Análise do Painel de Administração</div>
    <div class="card-body">';

if (file_exists('admin/prematriculas.php')) {
    echo '<div class="alert alert-success">
            <h4><span class="success-icon">✓</span> Arquivo admin/prematriculas.php encontrado</h4>
          </div>';
    
    // Verificar se o arquivo contém as funções de envio de e-mail
    $admin_content = file_get_contents('admin/prematriculas.php');
    
    $has_approval_email = strpos($admin_content, 'function sendApprovalEmail') !== false;
    $has_rejection_email = strpos($admin_content, 'function sendRejectionEmail') !== false;
    
    if ($has_approval_email && $has_rejection_email) {
        echo '<div class="alert alert-success">
                <p>O arquivo contém as funções sendApprovalEmail e sendRejectionEmail.</p>
              </div>';
        
        // Verificar se as funções têm log
        $has_approval_log = strpos($admin_content, 'approval_email_log') !== false || 
                           strpos($admin_content, 'log') !== false && strpos(strtolower($admin_content), 'approval') !== false;
        
        $has_rejection_log = strpos($admin_content, 'rejection_email_log') !== false || 
                            strpos($admin_content, 'log') !== false && strpos(strtolower($admin_content), 'reject') !== false;
        
        if (!$has_approval_log) {
            echo '<div class="alert alert-warning">
                    <p>A função sendApprovalEmail não parece ter registro de log, o que dificulta o diagnóstico.</p>
                  </div>';
        }
        
        if (!$has_rejection_log) {
            echo '<div class="alert alert-warning">
                    <p>A função sendRejectionEmail não parece ter registro de log, o que dificulta o diagnóstico.</p>
                  </div>';
        }
        
        // Adicionar logs ao admin/prematriculas.php
        if (isset($_GET['add_admin_logs']) && $_GET['add_admin_logs'] == 'true') {
            // Fazer backup do arquivo
            $backup_file = 'admin/prematriculas.php.bak.' . date('YmdHis');
            copy('admin/prematriculas.php', $backup_file);
            
            // Carregar as funções de e-mail corrigidas
            if (file_exists('admin_email_functions.txt')) {
                $new_functions = file_get_contents('admin_email_functions.txt');
                
                // Substituir as funções antigas pelas novas
                $pattern_approval = '/function\s+sendApprovalEmail\s*\([^)]*\)\s*{.*?}/s';
                $pattern_rejection = '/function\s+sendRejectionEmail\s*\([^)]*\)\s*{.*?}/s';
                
                // Extrair os novos conteúdos das funções
                preg_match('/function sendApprovalEmail.*?{.*?}/s', $new_functions, $approval_match);
                preg_match('/function sendRejectionEmail.*?{.*?}/s', $new_functions, $rejection_match);
                
                if (!empty($approval_match[0]) && !empty($rejection_match[0])) {
                    // Substituir as funções antigas
                    $fixed_content = preg_replace($pattern_approval, $approval_match[0], $admin_content);
                    $fixed_content = preg_replace($pattern_rejection, $rejection_match[0], $fixed_content);
                    
                    // Verificar se algo foi substituído
                    if ($fixed_content !== $admin_content) {
                        // Salvar o arquivo corrigido
                        file_put_contents('admin/prematriculas.php', $fixed_content);
                        
                        echo '<div class="alert alert-success">
                                <h4>Funções de E-mail Atualizadas</h4>
                                <p>As funções de e-mail no arquivo admin/prematriculas.php foram atualizadas para incluir logs.</p>
                                <p>Um backup do arquivo original foi criado em: ' . htmlspecialchars($backup_file) . '</p>
                              </div>';
                    } else {
                        echo '<div class="alert alert-danger">
                                <h4>Falha na Atualização</h4>
                                <p>Não foi possível atualizar as funções de e-mail no arquivo admin/prematriculas.php.</p>
                              </div>';
                    }
                } else {
                    echo '<div class="alert alert-danger">
                            <h4>Funções Não Encontradas</h4>
                            <p>Não foi possível encontrar as funções de e-mail no arquivo admin_email_functions.txt.</p>
                          </div>';
                }
            } else {
                echo '<div class="alert alert-danger">
                        <h4>Arquivo admin_email_functions.txt Não Encontrado</h4>
                        <p>O arquivo com as funções de e-mail corrigidas não foi encontrado.</p>
                      </div>';
            }
        } else {
            echo '<div class="mt-3">
                    <a href="?add_admin_logs=true" class="btn btn-warning">Adicionar Logs ao Admin</a>
                  </div>';
        }
    } else {
        echo '<div class="alert alert-danger">
                <h4><span class="error-icon">✗</span> Funções de E-mail Não Encontradas</h4>
                <p>O arquivo admin/prematriculas.php não contém uma ou ambas as funções de envio de e-mail.</p>
              </div>';
    }
} else {
    echo '<div class="alert alert-danger">
            <h4><span class="error-icon">✗</span> Arquivo admin/prematriculas.php não encontrado</h4>
            <p>O arquivo admin/prematriculas.php não foi encontrado.</p>
          </div>';
}

echo '</div>
</div>';

// Gerar atividade de e-mail de teste
echo '<div class="card">
    <div class="card-header bg-primary text-white">Gerar Atividade de E-mail</div>
    <div class="card-body">
        <p>Você pode enviar e-mails de teste para verificar se eles são registrados corretamente nos logs:</p>
        
        <div class="row mt-4">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Teste SMTP</h5>
                        <p class="card-text">Teste a configuração SMTP geral</p>
                        ' . (file_exists('smtp_test.php') ? '<a href="smtp_test.php" class="btn btn-primary">Executar</a>' : '<button class="btn btn-secondary" disabled>Indisponível</button>') . '
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">E-mail de Aprovação</h5>
                        <p class="card-text">Teste o envio de e-mails de aprovação de matrícula</p>
                        ' . (file_exists('test_approval_email.php') ? '<a href="test_approval_email.php" class="btn btn-primary">Executar</a>' : '<button class="btn btn-secondary" disabled>Indisponível</button>') . '
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

// Botões de ação
echo '<div class="mt-4 mb-5 text-center">
    <a href="index.html" class="btn btn-secondary">Voltar para o formulário de pré-matrícula</a>
    <a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="btn btn-info">Atualizar Página</a>
</div>';

echo '</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
?>