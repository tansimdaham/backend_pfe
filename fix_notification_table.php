<?php
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Récupérer les informations de connexion à la base de données
$dbUrl = $_ENV['DATABASE_URL'];
preg_match('/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $dbUrl, $matches);

$dbUser = $matches[1] ?? 'root';
$dbPass = $matches[2] ?? '';
$dbHost = $matches[3] ?? 'localhost';
$dbPort = $matches[4] ?? '3306';
$dbName = $matches[5] ?? 'back-symfony';

// Extraire le nom de la base de données sans les paramètres supplémentaires
if (strpos($dbName, '?') !== false) {
    $dbName = substr($dbName, 0, strpos($dbName, '?'));
}

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Correction de la table notification ===\n\n";
    
    // 1. Vérifier la structure actuelle de la table
    echo "1. Structure actuelle de la table notification :\n";
    $stmt = $pdo->query("DESCRIBE notification");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})" . ($column['Null'] === 'YES' ? ' NULL' : ' NOT NULL') . "\n";
    }
    echo "\n";
    
    // 2. Vérifier si la colonne 'read' existe
    $readColumnExists = false;
    $isReadColumnExists = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'read') {
            $readColumnExists = true;
        }
        if ($column['Field'] === 'is_read') {
            $isReadColumnExists = true;
        }
    }
    
    // 3. Renommer la colonne 'read' en 'is_read' si nécessaire
    if ($readColumnExists && !$isReadColumnExists) {
        echo "2. Renommage de la colonne 'read' en 'is_read'...\n";
        $pdo->exec("ALTER TABLE notification CHANGE `read` is_read TINYINT(1) DEFAULT NULL");
        echo "   Colonne renommée avec succès.\n\n";
    } elseif ($isReadColumnExists) {
        echo "2. La colonne 'is_read' existe déjà, aucune modification nécessaire.\n\n";
    } else {
        echo "2. La colonne 'read' n'existe pas, vérification de la structure de la table...\n\n";
    }
    
    // 4. Vérifier la nouvelle structure de la table
    echo "3. Nouvelle structure de la table notification :\n";
    $stmt = $pdo->query("DESCRIBE notification");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})" . ($column['Null'] === 'YES' ? ' NULL' : ' NOT NULL') . "\n";
    }
    echo "\n";
    
    echo "=== Correction terminée ===\n";
    
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
