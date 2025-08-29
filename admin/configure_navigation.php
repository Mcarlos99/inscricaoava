<?php
/**
 * Painel para configurar o tipo de navegação de cada polo
 */

// Verificar se o usuário está logado como administrador
$admin_key = $_GET['key'] ?? '';
if ($admin_key !== 'admin123') {
    die('Acesso não autorizado');
}

// Incluir arquivo de configuração dos polos
require_once('../polo_config.php');

// Processar alterações
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_navigation'])) {
    // Ler o arquivo atual
    $configContent = file_get_contents('../polo_config.php');
    
    // Atualizar cada polo
    foreach ($POLO_CONFIG as $poloId => $poloData) {
        if (isset($_POST['navigation'][$poloId])) {
            $newValue = $_POST['navigation'][$poloId] === '1' ? 'true' : 'false';
            
            // Procurar e substituir a configuração no arquivo
            $pattern = "/('{$poloId}'[^}]*'hierarchical_navigation'\s*=>\s*)(true|false)/";
            if (preg_match($pattern, $configContent)) {
                $configContent = preg_replace($pattern, '${1}' . $newValue, $configContent);
            } else {
                // Se não existe, adicionar após 'address'
                $pattern = "/('{$poloId}'[^}]*'address'\s*=>\s*'[^']*',)/";
                $replacement = '$1' . "\n    'hierarchical_navigation' => {$newValue},";
                $configContent = preg_replace($pattern, $replacement, $configContent);
            }
        }
    }
    
    // Salvar o arquivo
    if (file_put_contents('../polo_config.php', $configContent)) {
        $message = 'Configurações atualizadas com sucesso!';
        // Recarregar a configuração
        include('../polo_config.php');
    } else {
        $message = 'Erro ao salvar as configurações.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Navegação dos Polos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navigation-card {
            transition: all 0.3s ease;
        }
        .navigation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .mode-simple {
            border-left: 4px solid #28a745;
        }
        .mode-hierarchical {
            border-left: 4px solid #ffc107;
        }
        .preview-icon {
            font-size: 3rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Configurar Navegação dos Polos</h1>
            <div>
                <a href="prematriculas.php?key=<?php echo htmlspecialchars($admin_key); ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar para Pré-matrículas
                </a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="m-0">
                    <i class="fas fa-cogs me-2"></i>Tipos de Navegação Disponíveis
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mode-simple h-100">
                            <div class="card-body text-center">
                                <div class="preview-icon text-success">
                                    <i class="fas fa-list"></i>
                                </div>
                                <h5>Navegação Simples</h5>
                                <p>Mostra apenas as categorias principais como cursos finais. Ideal para polos com estrutura direta.</p>
                                <ul class="text-start">
                                    <li>Uma única tela de seleção</li>
                                    <li>Categorias = Cursos</li>
                                    <li>Navegação mais rápida</li>
                                    <li>Ideal para poucos cursos</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mode-hierarchical h-100">
                            <div class="card-body text-center">
                                <div class="preview-icon text-warning">
                                    <i class="fas fa-sitemap"></i>
                                </div>
                                <h5>Navegação Hierárquica</h5>
                                <p>Navega por Categoria → Subcategoria/Curso. Ideal para polos com muitos cursos organizados.</p>
                                <ul class="text-start">
                                    <li>Navegação em níveis</li>
                                    <li>Categorias → Subcategorias</li>
                                    <li>Breadcrumb para navegação</li>
                                    <li>Ideal para muitos cursos</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <form method="post">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="m-0">
                        <i class="fas fa-university me-2"></i>Configuração por Polo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($POLO_CONFIG as $poloId => $polo): ?>
                            <?php 
                            $currentMode = isset($polo['hierarchical_navigation']) ? $polo['hierarchical_navigation'] : false;
                            $cardClass = $currentMode ? 'mode-hierarchical' : 'mode-simple';
                            ?>
                            <div class="col-md-6 mb-4">
                                <div class="card navigation-card <?php echo $cardClass; ?>">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="m-0">
                                            <i class="fas fa-university me-2"></i>
                                            <?php echo htmlspecialchars($polo['name']); ?>
                                        </h6>
                                        <span class="badge <?php echo $currentMode ? 'bg-warning text-dark' : 'bg-success'; ?>">
                                            <?php echo $currentMode ? 'Hierárquica' : 'Simples'; ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3"><?php echo htmlspecialchars($polo['description']); ?></p>
                                        
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" 
                                                   name="navigation[<?php echo $poloId; ?>]" 
                                                   id="simple_<?php echo $poloId; ?>" 
                                                   value="0" 
                                                   <?php echo !$currentMode ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="simple_<?php echo $poloId; ?>">
                                                <i class="fas fa-list text-success me-2"></i>
                                                <strong>Navegação Simples</strong>
                                                <br><small class="text-muted">Categorias diretas como cursos</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="radio" 
                                                   name="navigation[<?php echo $poloId; ?>]" 
                                                   id="hierarchical_<?php echo $poloId; ?>" 
                                                   value="1" 
                                                   <?php echo $currentMode ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="hierarchical_<?php echo $poloId; ?>">
                                                <i class="fas fa-sitemap text-warning me-2"></i>
                                                <strong>Navegação Hierárquica</strong>
                                                <br><small class="text-muted">Categorias → Subcategorias</small>
                                            </label>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                URL: <?php echo htmlspecialchars($polo['moodle_url']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <button type="submit" name="update_navigation" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>Salvar Configurações
                </button>
            </div>
        </form>
        
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="m-0">
                    <i class="fas fa-lightbulb me-2"></i>Dicas de Uso
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-thumbs-up text-success me-2"></i>Use Navegação Simples quando:</h6>
                        <ul>
                            <li>O polo tem poucos cursos (até 10)</li>
                            <li>Os cursos estão em categorias principais</li>
                            <li>Você quer uma experiência mais direta</li>
                            <li>Os usuários são menos técnicos</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-thumbs-up text-warning me-2"></i>Use Navegação Hierárquica quando:</h6>
                        <ul>
                            <li>O polo tem muitos cursos (mais de 10)</li>
                            <li>Os cursos estão organizados em subcategorias</li>
                            <li>Você quer uma organização mais estruturada</li>
                            <li>Há diferentes áreas de conhecimento</li>
                        </ul>
                    </div>
                </div>
                
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Importante:</strong> As alterações serão aplicadas imediatamente no formulário de pré-matrícula. 
                    Teste sempre após fazer mudanças para garantir que a navegação está funcionando corretamente.
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Atualizar visual dos cards quando a seleção mudar
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[type="radio"]');
            
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    const card = this.closest('.navigation-card');
                    const badge = card.querySelector('.badge');
                    
                    // Remover classes existentes
                    card.classList.remove('mode-simple', 'mode-hierarchical');
                    
                    if (this.value === '1') {
                        // Hierárquica
                        card.classList.add('mode-hierarchical');
                        badge.textContent = 'Hierárquica';
                        badge.className = 'badge bg-warning text-dark';
                    } else {
                        // Simples
                        card.classList.add('mode-simple');
                        badge.textContent = 'Simples';
                        badge.className = 'badge bg-success';
                    }
                });
            });
        });
    </script>
</body>
</html>