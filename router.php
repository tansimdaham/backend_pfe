<?php
/**
 * Routeur personnalisé pour le serveur PHP intégré
 * Ce fichier permet de gérer correctement les requêtes vers l'URL racine et les autres routes
 */

// Récupérer l'URI demandée
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Si l'URI est la racine, servir la page d'accueil
if ($uri === '/' || $uri === '') {
    include __DIR__ . '/public/home.html';
    return true;
}

// Si le fichier demandé existe dans le répertoire public, le servir directement
$publicPath = __DIR__ . '/public' . $uri;
if (file_exists($publicPath) && !is_dir($publicPath)) {
    return false; // Laisser le serveur PHP servir le fichier directement
}

// Sinon, rediriger vers index.php pour que Symfony gère la requête
include __DIR__ . '/public/index.php';
return true;
