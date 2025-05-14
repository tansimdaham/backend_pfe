<?php
// Script pour générer un certificat SSL auto-signé

echo "Génération d'un certificat SSL auto-signé pour le serveur HTTPS...\n";

// Créer le répertoire ssl s'il n'existe pas
if (!is_dir('ssl')) {
    mkdir('ssl', 0755, true);
    echo "Répertoire ssl créé.\n";
}

// Configuration pour le certificat
$dn = [
    "countryName" => "FR",
    "stateOrProvinceName" => "France",
    "localityName" => "Paris",
    "organizationName" => "PharmaLearn",
    "commonName" => "127.0.0.1",
    "emailAddress" => "admin@example.com"
];

// Générer une nouvelle paire de clés
echo "Génération de la clé privée...\n";
$privkey = openssl_pkey_new([
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
]);

if (!$privkey) {
    echo "Erreur lors de la génération de la clé privée: " . openssl_error_string() . "\n";
    exit(1);
}

// Générer une demande de certificat
echo "Génération de la demande de certificat...\n";
$csr = openssl_csr_new($dn, $privkey);
if (!$csr) {
    echo "Erreur lors de la génération de la demande de certificat: " . openssl_error_string() . "\n";
    exit(1);
}

// Signer la demande de certificat pour créer un certificat
echo "Signature du certificat...\n";
$x509 = openssl_csr_sign($csr, null, $privkey, 365);
if (!$x509) {
    echo "Erreur lors de la signature du certificat: " . openssl_error_string() . "\n";
    exit(1);
}

// Exporter le certificat et la clé privée
echo "Exportation du certificat et de la clé privée...\n";
if (!openssl_x509_export_to_file($x509, 'ssl/server.crt')) {
    echo "Erreur lors de l'exportation du certificat: " . openssl_error_string() . "\n";
    exit(1);
}

if (!openssl_pkey_export_to_file($privkey, 'ssl/server.key')) {
    echo "Erreur lors de l'exportation de la clé privée: " . openssl_error_string() . "\n";
    exit(1);
}

echo "\nCertificat SSL auto-signé généré avec succès.\n";
echo "Fichiers créés :\n";
echo "- ssl/server.crt\n";
echo "- ssl/server.key\n";
echo "\nVous pouvez maintenant démarrer le serveur HTTPS.\n";
?>
