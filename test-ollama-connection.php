<?php
// Simple script to test Ollama connection

// Configuration
$apiUrl = "http://127.0.0.1:11434";
$model = "mistral";

echo "=== Test de connexion à l'API Ollama ===\n\n";
echo "URL de l'API: $apiUrl\n";
echo "Modèle: $model\n\n";

// Test 1: Check if Ollama is running
echo "Test 1: Vérification que Ollama est en cours d'exécution...\n";
$ch = curl_init("$apiUrl/api/tags");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    echo "❌ Erreur: Impossible de se connecter à Ollama (HTTP $httpCode)\n";
    echo "Réponse: " . ($response ?: "Aucune réponse") . "\n";
    echo "\nVérifiez que:\n";
    echo "1. Ollama est installé et en cours d'exécution\n";
    echo "2. L'URL de l'API est correcte\n";
    echo "3. Aucun pare-feu ne bloque la connexion\n";
    exit(1);
}

$data = json_decode($response, true);
$models = $data['models'] ?? [];

echo "✅ Connexion réussie!\n";
echo "Modèles disponibles: " . count($models) . "\n";
foreach ($models as $index => $modelInfo) {
    echo " - " . $modelInfo['name'] . "\n";
}
echo "\n";

// Test 2: Try to send a simple message
echo "Test 2: Envoi d'un message simple...\n";
$message = "Bonjour, comment ça va?";
echo "Message: \"$message\"\n";

$data = [
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => 'Vous êtes un assistant utile.'],
        ['role' => 'user', 'content' => $message]
    ],
    'stream' => false
];

$ch = curl_init("$apiUrl/api/chat");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error || $httpCode !== 200) {
    echo "❌ Erreur lors de l'envoi du message: " . ($error ?: "HTTP $httpCode") . "\n";
    echo "Réponse: " . ($response ?: "Aucune réponse") . "\n";
    exit(1);
}

$responseData = json_decode($response, true);
$aiResponse = $responseData['message']['content'] ?? 'Pas de réponse';

echo "✅ Réponse reçue!\n";
echo "Réponse: \"" . substr($aiResponse, 0, 100) . (strlen($aiResponse) > 100 ? "..." : "") . "\"\n\n";

echo "=== Tests terminés avec succès ===\n";
