<?php
/**
 * Script para atualizar o arquivo polo_config.php 
 * adicionando a configuração de cursos técnicos finais
 */

// Exibir erros para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar se foi solicitada a atualização
$update_requested = isset($_GET['update']) && $_GET['update'] === 'confirm';

echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Configuração dos Polos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Atualizar Configuração dos Polos</h1>';

if ($update_requested) {
    // Fazer a atualização
    echo '<div class="alert alert-info">
            <h4>Processando atualização...</h4>
          </div>';
    
    // Fazer backup do arquivo atual
    $configFile = 'polo_config.php';
    $backupFile = 'polo_config.php.bak.' . date('YmdHis');
    
    if (file_exists($configFile)) {
        copy($configFile, $backupFile);
        echo '<div class="alert alert-success">
                <p>✅ Backup criado: ' . htmlspecialchars($backupFile) . '</p>
              </div>';
    }
    
    // Ler o arquivo atual
    $currentContent = file_get_contents($configFile);
    
    // Adicionar a configuração de cursos finais para cada polo
    $updatedContent = $currentContent;
    
    // Para cada polo que usa navegação hierárquica, adicionar a configuração
    $polosToUpdate = [
        'breu-branco' => [26, 27, 28, 29, 33],
        'repartimento' => [26, 27, 28, 29, 33],
        'bioquality' => [26, 27, 28, 29, 33],
        'ava' => [26, 27, 28, 29, 33, 283, 282]
    ];
    
    foreach ($polosToUpdate as $poloId => $finalCourses) {
        // Procurar a linha com 'hierarchical_navigation' => true para este polo
        $pattern = "/('$poloId'[^}]*'hierarchical_navigation'\s*=>\s*true[^,]*,)/";
        
        if (preg_match($pattern, $updatedContent)) {
            // Adicionar a configuração após hierarchical_navigation
            $replacement = '$1' . "\n    'final_course_categories' => [" . implode(', ', $finalCourses) . "], // IDs dos cursos técnicos que são finais";
            $updatedContent = preg_replace($pattern, $replacement, $updatedContent);
            
            echo '<div class="alert alert-success">
                    <p>✅ Configuração adicionada para o polo: ' . $poloId . '</p>
                  </div>';
        }
    }
    
    // Adicionar a variável global no final
    if (strpos($updatedContent, '$GLOBAL_FINAL_COURSE_CATEGORIES') === false) {
        $globalConfig = "\n// Lista global de cursos técnicos que devem ser tratados como finais\n";
        $globalConfig .= '$GLOBAL_FINAL_COURSE_CATEGORIES = [26, 27, 28, 29, 33];';
        
        // Inserir antes do ?>
        $updatedContent = str_replace('?>', $globalConfig . "\n?>", $updatedContent);
        
        echo '<div class="alert alert-success">
                <p>✅ Configuração global adicionada</p>
              </div>';
    }
    
    // Salvar o arquivo atualizado
    if (file_put_contents($configFile, $updatedContent)) {
        echo '<div class="alert alert-success">
                <h4>✅ Configuração atualizada com sucesso!</h4>
                <p>O arquivo polo_config.php foi atualizado para incluir a configuração de cursos técnicos finais.</p>
              </div>';
        
        // Verificar se a atualização funcionou
        include('polo_config.php');
        
        echo '<div class="card mt-4">
                <div class="card-header">
                    <h5>Verificação da Configuração Atualizada</h5>
                </div>
                <div class="card-body">';
        
        foreach ($polosToUpdate as $poloId => $expectedCourses) {
            if (isset($POLO_CONFIG[$poloId]['final_course_categories'])) {
                $actualCourses = $POLO_CONFIG[$poloId]['final_course_categories'];
                $match = array_diff($expectedCourses, $actualCourses) === array_diff($actualCourses, $expectedCourses);
                
                echo '<p><strong>' . $poloId . ':</strong> ';
                echo $match ? '<span class="text-success">✅ Configurado corretamente</span>' : '<span class="text-danger">❌ Configuração incorreta</span>';
                echo ' - Cursos: [' . implode(', ', $actualCourses) . ']</p>';
            } else {
                echo '<p><strong>' . $poloId . ':</strong> <span class="text-warning">⚠️ Configuração não encontrada</span></p>';
            }
        }
        
        echo '</div>
              </div>';
        
    } else {
        echo '<div class="alert alert-danger">
                <h4>❌ Erro ao salvar o arquivo</h4>
                <p>Não foi possível salvar as alterações no arquivo polo_config.php. Verifique as permissões.</p>
              </div>';
    }
    
} else {
    // Mostrar informações sobre a atualização
    echo '<div class="alert alert-warning">
            <h4>⚠️ Atualização Necessária</h4>
            <p>Para que os cursos técnicos (Enfermagem, Eletromecânica, Eletrotécnica, Segurança do Trabalho, NRs) sejam exibidos corretamente no modo hierárquico, é necessário atualizar a configuração dos polos.</p>
          </div>';
    
    // Verificar configuração atual
    include('polo_config.php');
    
    echo '<div class="card mb-4">
            <div class="card-header">
                <h5>Status Atual da Configuração</h5>
            </div>
            <div class="card-body">';
    
    $polosHierarchical = [];
    foreach ($POLO_CONFIG as $poloId => $polo) {
        if (isset($polo['hierarchical_navigation']) && $polo['hierarchical_navigation']) {
            $polosHierarchical[] = $poloId;
            
            $hasFinalCourses = isset($polo['final_course_categories']);
            echo '<p><strong>' . $polo['name'] . ' (' . $poloId . '):</strong> ';
            echo 'Navegação Hierárquica ';
            echo $hasFinalCourses ? '<span class="text-success">✅ Já configurado</span>' : '<span class="text-danger">❌ Precisa de atualização</span>';
            echo '</p>';
        }
    }
    
    if (empty($polosHierarchical)) {
        echo '<div class="alert alert-info">Nenhum polo está usando navegação hierárquica.</div>';
    }
    
    echo '</div>
          </div>';
    
    echo '<div class="card mb-4">
            <div class="card-header">
                <h5>O que será atualizado?</h5>
            </div>
            <div class="card-body">
                <ul>
                    <li>Backup do arquivo atual será criado</li>
                    <li>Adição da configuração <code>final_course_categories</code> para os polos com navegação hierárquica</li>
                    <li>IDs dos cursos técnicos: 26 (Enfermagem), 27 (Eletromecânica), 28 (Eletrotécnica), 29 (Segurança), 33 (NRs)</li>
                    <li>Adição de uma variável global de fallback</li>
                </ul>
            </div>
          </div>';
    
    echo '<div class="text-center">
            <a href="?update=confirm" class="btn btn-primary btn-lg">
                🔧 Executar Atualização
            </a>
            <a href="index.html" class="btn btn-secondary">
                Cancelar
            </a>
          </div>';
}

echo '<hr class="my-4">
      <div class="text-center">
        <a href="test_ava_categories.php" class="btn btn-info">Testar Configuração AVA</a>
        <a href="debug_categories.php?polo=ava" class="btn btn-outline-info">Debug Detalhado</a>
      </div>';

echo '</div>
    </body>
    </html>';
?>