<?php
// Définir l'en-tête pour indiquer que la réponse est au format JSON
header('Content-Type: application/json');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Autoriser les requêtes CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Si la méthode est OPTIONS, terminer la requête ici (pour les requêtes préliminaires CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Créer une réponse JSON simple
$response = [
    'status' => 'success',
    'message' => 'API de test fonctionnelle',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP Built-in Server',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
];

// Envoyer la réponse
echo json_encode($response, JSON_PRETTY_PRINT);
