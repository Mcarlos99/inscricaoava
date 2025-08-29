<?php
/**
 * Script para atualizar o banco de dados adicionando o campo student_polo
 * Execute este arquivo uma vez para adicionar o novo campo
 */

// Configurações do banco de dados - AJUSTE CONFORME SEU AMBIENTE
$db_host = 'localhost';
$db_name = 'inscricaoavadb';
$db_user = 'inscricaoavauser';
$db_pass = '05hsqwjG8vLsIVBvQ7Iu';

// Exibir erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualização do Banco de Dados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Atualização do Banco de Dados</h1>
        <div class="card">
            <div class="card-header">
                <h5>Adicionando campo "Polo do Aluno" na tabela prematriculas</h5>
            </div>
            <div class="card-body">';

try {
    // Conectar ao banco de dados
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo '<div class="alert alert-info">✅ Conexão com o banco estabelecida com sucesso</div>';
    
    // Verificar se o campo já existe
    $stmt = $pdo->prepare("SHOW COLUMNS FROM prematriculas LIKE 'student_polo'");
    $stmt->execute();
    $fieldExists = $stmt->fetch();
    
    if ($fieldExists) {
        echo '<div class="alert alert-warning">
                <h6>⚠️ Campo já existe</h6>
                <p>O campo <code>student_polo</code> já existe na tabela <code>prematriculas</code>.</p>
                <p>Nenhuma alteração foi necessária.</p>
              </div>';
    } else {
        // Adicionar o campo
        $sql = "ALTER TABLE prematriculas 
                ADD COLUMN student_polo VARCHAR(100) NULL 
                COMMENT 'Polo/cidade onde o aluno está localizado' 
                AFTER education_level";
        
        $pdo->exec($sql);
        
        echo '<div class="alert alert-success">
                <h6>✅ Campo adicionado com sucesso!</h6>
                <p>O campo <code>student_polo</code> foi adicionado à tabela <code>prematriculas</code>.</p>
              </div>';
    }
    
    // Mostrar a estrutura atual da tabela
    echo '<h6 class="mt-4">Estrutura atual da tabela prematriculas:</h6>';
    
    $stmt = $pdo->prepare("DESCRIBE prematriculas");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Campo</th>
                        <th>Tipo</th>
                        <th>Nulo</th>
                        <th>Chave</th>
                        <th>Padrão</th>
                        <th>Extra</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($columns as $column) {
        // Destacar o novo campo
        $rowClass = ($column['Field'] === 'student_polo') ? 'table-success' : '';
        
        echo '<tr class="' . $rowClass . '">
                <td><strong>' . htmlspecialchars($column['Field']) . '</strong></td>
                <td>' . htmlspecialchars($column['Type']) . '</td>
                <td>' . htmlspecialchars($column['Null']) . '</td>
                <td>' . htmlspecialchars($column['Key']) . '</td>
                <td>' . htmlspecialchars($column['Default'] ?? '') . '</td>
                <td>' . htmlspecialchars($column['Extra']) . '</td>
              </tr>';
    }
    
    echo '</tbody>
          </table>
          </div>';
    
    // Verificar se há registros na tabela
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prematriculas");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo '<div class="alert alert-info mt-3">
            <p><strong>Total de registros na tabela:</strong> ' . $count . '</p>';
    
    if ($count > 0) {
        echo '<p><strong>Nota:</strong> Os registros existentes terão o valor NULL no campo student_polo até serem atualizados.</p>';
        
        // Mostrar alguns registros existentes
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, student_polo FROM prematriculas ORDER BY id DESC LIMIT 5");
        $stmt->execute();
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($recent)) {
            echo '<h6>Últimos 5 registros:</h6>
                  <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Polo do Aluno</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            foreach ($recent as $record) {
                echo '<tr>
                        <td>' . $record['id'] . '</td>
                        <td>' . htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) . '</td>
                        <td>' . htmlspecialchars($record['email']) . '</td>
                        <td>' . ($record['student_polo'] ? htmlspecialchars($record['student_polo']) : '<em class="text-muted">Não informado</em>') . '</td>
                      </tr>';
            }
            
            echo '</tbody>
                  </table>
                  </div>';
        }
    }
    
    echo '</div>';
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">
            <h6>❌ Erro na conexão ou execução</h6>
            <p><strong>Erro:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            <p><strong>Verifique:</strong></p>
            <ul>
                <li>As configurações de conexão do banco de dados</li>
                <li>Se o usuário tem permissões para alterar a estrutura da tabela</li>
                <li>Se a tabela "prematriculas" existe</li>
            </ul>
          </div>';
}

echo '        </div>
        </div>
        
        <div class="mt-4 text-center">
            <a href="index.html" class="btn btn-primary">Voltar ao Formulário</a>
            <a href="admin/prematriculas.php?key=admin123" class="btn btn-success">Painel Admin</a>
        </div>
    </div>
</body>
</html>';
?>