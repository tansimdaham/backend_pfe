<?php
// Script de diagnostic pour vérifier l'installation de Symfony

echo "=== Diagnostic de l'installation Symfony ===\n\n";

// Vérifier la version de PHP
echo "Version PHP: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    echo "ERREUR: Symfony 6.4 nécessite PHP 8.1 ou supérieur.\n";
}

// Vérifier les extensions PHP requises
$requiredExtensions = ['pdo', 'json', 'xml', 'ctype', 'iconv', 'tokenizer'];
echo "\nExtensions PHP requises:\n";
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext: Installée\n";
    } else {
        echo "❌ $ext: Non installée\n";
    }
}

// Vérifier l'existence des fichiers clés
echo "\nFichiers clés:\n";
$files = [
    'public/index.php',
    'config/routes.yaml',
    'config/packages/framework.yaml',
    'src/Kernel.php',
    'vendor/autoload.php',
    'vendor/autoload_runtime.php'
];

foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ $file: Existe\n";
    } else {
        echo "❌ $file: N'existe pas\n";
    }
}

// Vérifier les permissions des répertoires
echo "\nPermissions des répertoires:\n";
$dirs = [
    'public',
    'var',
    'var/cache',
    'var/log'
];

foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "✅ $dir: Accessible en écriture\n";
        } else {
            echo "❌ $dir: Non accessible en écriture\n";
        }
    } else {
        echo "❌ $dir: N'existe pas\n";
    }
}

// Vérifier la configuration de l'environnement
echo "\nConfiguration de l'environnement:\n";
if (file_exists(__DIR__ . '/.env')) {
    echo "✅ Fichier .env: Existe\n";
    $env = file_get_contents(__DIR__ . '/.env');
    if (strpos($env, 'APP_ENV=dev') !== false) {
        echo "✅ APP_ENV: dev\n";
    } else {
        echo "❓ APP_ENV: Non défini à 'dev'\n";
    }
} else {
    echo "❌ Fichier .env: N'existe pas\n";
}

echo "\n=== Fin du diagnostic ===\n";
