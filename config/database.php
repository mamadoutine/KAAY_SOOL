<?php

// KAAY SOOL – Configuration Base de Données

define('DB_HOST', 'localhost');
define('DB_NAME', 'kaay_sool');
define('DB_USER', 'root'); 
define('DB_PASS', ''); 
define('DB_CHARSET', 'utf8mb4');

// URL de base du site
define('BASE_URL', 'http://localhost/kaay-sool-php');

// Clé secrète pour les sessions (changer en production !)
define('SECRET_KEY', 'kaaysool_secret_2026_@unchk');

// Durée de session (secondes) = 2h
define('SESSION_DURATION', 7200);


// CONNEXION PDO
function getDB(): PDO {
static $pdo = null;
if ($pdo === null) {
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
];
try {
$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
http_response_code(500);
die(json_encode(['error' => 'Erreur de connexion à la base de données.']));
}
}
return $pdo;
} 
