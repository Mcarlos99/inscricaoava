<?php
// Configuração dos polos e suas respectivas instâncias Moodle
$POLO_CONFIG = array (
  'tucurui' => 
  array (
    'name' => 'Tucuruí',
    'moodle_url' => 'https://tucurui.imepedu.com.br',
    'api_token' => 'e9fc1f04b3d1c6971ac86adbb218110b',
    'description' => 'Polo de Educação Superior de Tucuruí',
    'address' => 'Av. Principal, 1234 - Centro, Tucuruí - PA',
    'hierarchical_navigation' => true, // Navegação hierárquica - categorias > subcategorias
    'final_course_categories' => [26, 27, 28, 29, 33, 55], // IDs dos cursos técnicos que são finais
    'course_prices' => array(
      26 => array( // Técnico em Enfermagem 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      27 => array( // Técnico em Eletromecânica 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      28 => array( // Técnico em Eletrotécnica 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      29 => array( // Técnico em Segurança do Trabalho
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      33 => array( // NR'S 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      'default' => array(
          'price' => 1500.00,
          'duration' => '12 meses',
          'installments' => '12x de R$ 125,00',
      ),
    ),
  ),
  'breu-branco' => 
  array (
    'name' => 'Breu Branco',
    'moodle_url' => 'https://breubranco.imepedu.com.br',
    'api_token' => '0441051a5b5bc8968f3e65ff7d45c3de',
    'description' => 'Polo de Educação Superior de Breu Branco',
    'address' => 'Rua Parauapebas, 145 - Novo Horizonte, Breu Branco - PA',
    'hierarchical_navigation' => true, // Navegação hierárquica - categorias > subcategorias
    'final_course_categories' => [26, 27, 28, 29, 33], // IDs dos cursos técnicos que são finais
    'course_prices' => array(
      26 => array( // Técnico em Enfermagem 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      27 => array( // Técnico em Eletromecânica 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      28 => array( // Técnico em Eletrotécnica 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      29 => array( // Técnico em Segurança do Trabalho
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      33 => array( // NR'S 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      'default' => array(
          'price' => 1500.00,
          'duration' => '12 meses',
          'installments' => '12x de R$ 125,00',
      ),
    ),
  ),
  'igarape-miri' => 
  array (
    'name' => 'Igarapé-Miri',
    'moodle_url' => 'https://igarape.imepedu.com.br',
    'api_token' => '051a62d5f60167246607b195a9630d3b',
    'description' => 'Polo de Educação Superior de Igarapé-Miri',
    'address' => 'Tv. Principal, 890 - Centro, Igarapé-Miri - PA',
    'hierarchical_navigation' => false, // Navegação simples - só categorias
    'course_prices' => 
    array (
      1 => 
      array (
        'price' => 5.0,
        'duration' => '6 meses',
        'installments' => '6x de R$ 46,17',
      ),
      'default' => 
      array (
        'price' => 8.0,
        'duration' => '6 meses',
        'installments' => '6x de R$ 46,17',
      ),
    ),
  ),
  'repartimento' => 
  array (
    'name' => 'Novo Repartimento',
    'moodle_url' => 'https://repartimento.imepedu.com.br',
    'api_token' => '25c578c6ec5d4c1b75547ea52a6fcf7c',
    'description' => 'Polo de Educação Superior de Novo Repartimento',
    'address' => 'Centro, Novo Repartimento - PA',
    'hierarchical_navigation' => true, // Navegação hierárquica - categorias > subcategorias
    'final_course_categories' => [26, 27, 28, 29, 33], // IDs dos cursos técnicos que são finais
    'course_prices' => array(
      26 => array( // Técnico em Enfermagem 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      27 => array( // Técnico em Eletromecânica 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      28 => array( // Técnico em Eletrotécnica 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      29 => array( // Técnico em Segurança do Trabalho
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      33 => array( // NR'S 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      'default' => array(
          'price' => 1500.00,
          'duration' => '12 meses',
          'installments' => '12x de R$ 125,00',
      ),
    ),
  ),
  'bioquality' => 
  array (
    'name' => 'BIOQUALITY - INVISTA EM RESULTADOS',
    'moodle_url' => 'https://bioquality.imepedu.com.br/ava',
    'api_token' => 'a4f1bd19f54bff53ad028085c34cad48',
    'description' => 'Centro de Ensino & Consultoria',
    'address' => 'Rua Sapucaia, n36, Primavera, Parauapebas 68515-000',
    'hierarchical_navigation' => true, // Navegação hierárquica - categorias > subcategorias
    'final_course_categories' => [26, 27, 28, 29, 33], // IDs dos cursos técnicos que são finais
    'course_prices' => array(
      26 => array( // Técnico em Enfermagem 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      27 => array( // Técnico em Eletromecânica 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      28 => array( // Técnico em Eletrotécnica 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      29 => array( // Técnico em Segurança do Trabalho
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      33 => array( // NR'S 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      'default' => array(
          'price' => 1500.00,
          'duration' => '12 meses',
          'installments' => '12x de R$ 125,00',
      ),
    ),
  ),
  'ava' => 
  array (
    'name' => 'AVA',
    'moodle_url' => 'https://ava.imepedu.com.br/',
    'api_token' => '0c8ef233994c5ccfe22d6ed7d4e86a05',
    'description' => 'AVA - Ambiente Virtual de Aprendizagem',
    'address' => 'BRASIL',
    'hierarchical_navigation' => true, // Navegação hierárquica - categorias > subcategorias
    'final_course_categories' => [26, 27, 28, 29, 33, 55], // IDs dos cursos técnicos que são finais
    'course_prices' => array(
      26 => array( // Técnico em Enfermagem 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      27 => array( // Técnico em Eletromecânica 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      28 => array( // Técnico em Eletrotécnica 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      29 => array( // Técnico em Segurança do Trabalho
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      33 => array( // NR'S 
          'price' => 2500.00,
          'duration' => '18 meses',
          'installments' => '18x de R$ 138,89',
      ),
      'default' => array(
          'price' => 1500.00,
          'duration' => '12 meses',
          'installments' => '12x de R$ 125,00',
      ),
    ),
  ),
);

// Configuração padrão para qualquer polo que não tenha preços específicos
$DEFAULT_COURSE_PRICES = array(
  // Valor padrão para todos os cursos em todos os polos que não têm configuração específica
  'default' => array(
    'price' => 297.00,
    'duration' => '6 meses',
    'installments' => '6x de R$ 49,50'
  )
);

// Lista global de cursos técnicos que devem ser tratados como finais
$GLOBAL_FINAL_COURSE_CATEGORIES = [26, 27, 28, 29, 33];
?>