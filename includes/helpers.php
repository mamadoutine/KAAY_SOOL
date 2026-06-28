 <?php
// KAAY SOOL – Fonctions utilitaires

require_once __DIR__ . '/../config/database.php';

// Démarrer la session de façon sécurisée
function startSession(): void {
if (session_status() === PHP_SESSION_NONE) {
session_set_cookie_params([
'lifetime' => SESSION_DURATION,
'path' => '/',
'secure' => false, // true en HTTPS
'httponly' => true,
'samesite' => 'Lax',
]);
session_start();
}
}

// Réponse JSON
function jsonResponse(mixed $data, int $code = 200): void {
http_response_code($code);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
exit;
}

// Sécuriser une valeur affichée en HTML
function e(string $value): string {
return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn(): bool {
startSession();
return isset($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin(): bool {
startSession();
return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Rediriger
function redirect(string $url): void {
header("Location: $url");
exit;
}

// Formater prix en FCFA
function formatPrix(float $prix): string {
return number_format($prix, 0, ',', '.') . ' FCFA';
}

// Générer un slug
function slugify(string $text): string {
$text = strtolower(trim($text));
$text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
$text = preg_replace('/[^a-z0-9-]/', '-', $text);
$text = preg_replace('/-+/', '-', $text);
return trim($text, '-');
}

// Valider email
function isValidEmail(string $email): bool {
return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Nettoyer les inputs
function clean(string $val): string {
return trim(strip_tags($val));
}

// Méthode HTTP actuelle
function method(): string {
return $_SERVER['REQUEST_METHOD'];
}

// Récupérer le body JSON d'une requête
function getJsonBody(): array {
$raw = file_get_contents('php://input');
return json_decode($raw, true) ?? [];
}

// Vérifier CSRF (simple)
function generateCsrf(): string {
startSession();
if (empty($_SESSION['csrf_token'])) {
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
startSession();
return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}