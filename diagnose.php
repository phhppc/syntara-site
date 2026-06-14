<?php
/**
 * Script de diagnóstico — remova após resolver o problema
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<h1>Syntara — Diagnóstico</h1>";
echo "<pre>";

// 1. Verificar versão do PHP
echo "PHP Version: " . PHP_VERSION . "\n";

// 2. Verificar extensões necessárias
$exts = ['mysqli', 'pdo', 'pdo_mysql', 'session', 'json', 'mbstring', 'openssl'];
foreach ($exts as $ext) {
    echo "Extensão $ext: " . (extension_loaded($ext) ? 'OK' : 'FALTANDO') . "\n";
}

// 3. Verificar se config.php carrega sem erros
echo "\n--- Carregando config.php ---\n";
try {
    require_once __DIR__ . '/includes/config.php';
    echo "config.php: OK\n";
} catch (Throwable $e) {
    echo "config.php ERRO: " . $e->getMessage() . "\n";
}

// 4. Verificar classes
echo "\n--- Classes ---\n";
$classes = glob(__DIR__ . '/classes/*.php');
if (empty($classes)) {
    echo "Nenhuma classe encontrada em classes/\n";
}
foreach ($classes as $c) {
    echo "Classe: " . basename($c) . "\n";
}

// 5. Verificar autoload
if (class_exists('Database')) {
    echo "Database class: OK (autoload funcionando)\n";
    try {
        $db = Database::getInstance();
        echo "Conexão DB: OK\n";
        $conn = $db->getConnection();
        echo "MySQL Server: " . $conn->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    } catch (Throwable $e) {
        echo "Conexão DB ERRO: " . $e->getMessage() . "\n";
    }
} else {
    echo "Database class: NÃO ENCONTRADA\n";
}

// 6. Verificar pasta layouts
echo "\n--- Layouts ---\n";
$layouts = glob(__DIR__ . '/layouts/*.php');
foreach ($layouts as $l) {
    echo "Layout: " . basename($l) . "\n";
}

// 7. Verificar secrets.php
echo "\n--- Secrets ---\n";
if (file_exists(__DIR__ . '/secrets.php')) {
    echo "secrets.php: existe\n";
} else {
    echo "secrets.php: NÃO EXISTE (necessário para DB)\n";
}

// 8. Permissões de escrita
echo "\n--- Permissões ---\n";
echo "app_logs/ " . (is_writable(__DIR__ . '/app_logs') ? 'gravável' : 'NÃO GRAVÁVEL') . "\n";
echo "uploads/ " . (is_dir(__DIR__ . '/uploads') ? (is_writable(__DIR__ . '/uploads') ? 'gravável' : 'NÃO GRAVÁVEL') : 'NÃO EXISTE') . "\n";

echo "\n--- Fim do diagnóstico ---";
echo "</pre>";
