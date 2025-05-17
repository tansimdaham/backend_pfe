<?php
/**
 * Script de test pour la connexion à l'API Ollama
 * 
 * Ce script peut être exécuté en ligne de commande pour tester la connexion
 * à l'API Ollama sans passer par le serveur web.
 * 
 * Usage: php test-ollama.php
 */

// Vérifier si cURL est disponible
if (!function_exists('curl_version')) {
    echo "❌ Erreur: L'extension cURL n'est pas disponible.\n";
    exit(1);
}

echo "=== Test de connexion à l'API Ollama ===\n\n";

// Configuration
$apiUrl = "http://localhost:11434";
$model = "mistral";

echo "URL de l'API: $apiUrl\n";
echo "Modèle: $model\n\n";

// Tester la connexion à l'API
echo "🔄 Test de connexion à l'API Ollama...\n";

$ch = curl_init("$apiUrl/api/tags");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "❌ Erreur de connexion: $error\n";
    echo "\nVérifiez que:\n";
    echo "1. Ollama est installé et en cours d'exécution\n";
    echo "2. L'URL de l'API est correcte\n";
    echo "3. Aucun pare-feu ne bloque la connexion\n";
    exit(1);
}

if ($httpCode != 200) {
    echo "❌ Erreur HTTP: $httpCode\n";
    echo "Réponse: $response\n";
    exit(1);
}

echo "✅ Connexion à l'API réussie (Code HTTP: $httpCode)\n\n";

// Vérifier les modèles disponibles
echo "🔄 Vérification des modèles disponibles...\n";

$data = json_decode($response, true);
if (isset($data['models']) && !empty($data['models'])) {
    echo "✅ Modèles trouvés: " . count($data['models']) . "\n";
    foreach ($data['models'] as $modelInfo) {
        echo "   - " . $modelInfo['name'] . "\n";
    }
} else {
    echo "⚠️ Aucun modèle trouvé. Vous devez télécharger un modèle avec 'ollama pull mistral'\n";
}

echo "\n";

// Tester l'envoi d'un message
echo "🔄 Test d'envoi d'un message...\n";

$messages = [
    [
        'role' => 'system',
        'content' => 'Vous êtes un assistant utile et concis.'
    ],
    [
        'role' => 'user',
        'content' => 'Bonjour, ceci est un test. Répondez simplement "Test réussi".'
    ]
];

$postData = json_encode([
    'model' => $model,
    'messages' => $messages,
    'stream' => false
]);

$ch = curl_init("$apiUrl/api/chat");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erreur lors de l'envoi du message: $error\n";
    exit(1);
}

if ($httpCode != 200) {
    echo "❌ Erreur HTTP: $httpCode\n";
    echo "Réponse: $response\n";
    exit(1);
}

$data = json_decode($response, true);
if (isset($data['message']['content'])) {
    echo "✅ Réponse reçue en " . round($endTime - $startTime, 2) . " secondes:\n";
    echo "   " . $data['message']['content'] . "\n";
} else {
    echo "❌ Format de réponse inattendu\n";
    echo "Réponse brute: $response\n";
}

echo "\n=== Test terminé ===\n";
echo "✅ Ollama fonctionne correctement et est prêt à être utilisé avec votre application.\n";
