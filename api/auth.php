<?php

// KAAY SOOL – API Authentification
// POST /api/auth.php?action=register
// POST /api/auth.php?action=login
// POST /api/auth.php?action=logout
// GET /api/auth.php?action=me


require_once __DIR__ . '/../includes/helpers.php';

startSession();
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

switch ($action) {

// ---- INSCRIPTION ----

case 'register':
if (method() !== 'POST') jsonResponse(['error' => 'Méthode non autorisée'], 405);

$body = getJsonBody();
$nom = clean($body['nom'] ?? '');
$prenom = clean($body['prenom'] ?? '');
$email = clean($body['email'] ?? '');
$pass = $body['mot_de_passe'] ?? '';

// Validations

if (!$nom || !$prenom || !$email || !$pass)
jsonResponse(['error' => 'Tous les champs sont obligatoires.'], 400);
if (!isValidEmail($email))
jsonResponse(['error' => 'Adresse email invalide.'], 400);
if (strlen($pass) < 8)
jsonResponse(['error' => 'Le mot de passe doit contenir au moins 8 caractères.'], 400);

$db = getDB();

// Vérifier si email existe déjà

$stmt = $db->prepare('SELECT id FROM utilisateurs WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch())
jsonResponse(['error' => 'Cet email est déjà utilisé.'], 409);

// Insérer
$hash = password_hash($pass, PASSWORD_BCRYPT);
$stmt = $db->prepare('INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, "client")');
$stmt->execute([$nom, $prenom, $email, $hash]);

$userId = $db->lastInsertId();
$_SESSION['user_id'] = $userId;
$_SESSION['email'] = $email;
$_SESSION['nom'] = $nom;
$_SESSION['prenom'] = $prenom;
$_SESSION['role'] = 'client';

jsonResponse(['success' => true, 'message' => 'Compte créé avec succès.', 'user' => [
'id' => $userId, 'nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'role' => 'client'
]], 201);
break;

// ---- CONNEXION ----
case 'login':
if (method() !== 'POST') jsonResponse(['error' => 'Méthode non autorisée'], 405);

$body = getJsonBody();
$email = clean($body['email'] ?? '');
$pass = $body['mot_de_passe'] ?? '';

if (!$email || !$pass)
jsonResponse(['error' => 'Email et mot de passe requis.'], 400);

$db = getDB();
$stmt = $db->prepare('SELECT * FROM utilisateurs WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user['mot_de_passe']))
jsonResponse(['error' => 'Email ou mot de passe incorrect.'], 401);

// Ouvrir la session
$_SESSION['user_id'] = $user['id'];
$_SESSION['email'] = $user['email'];
$_SESSION['nom'] = $user['nom'];
$_SESSION['prenom'] = $user['prenom'];
$_SESSION['role'] = $user['role'];

jsonResponse(['success' => true, 'user' => [
'id' => $user['id'],
'nom' => $user['nom'],
'prenom' => $user['prenom'],
'email' => $user['email'],
'role' => $user['role'],
]]);
break;

// ---- DÉCONNEXION ----
case 'logout':
session_destroy();
jsonResponse(['success' => true, 'message' => 'Déconnecté.']);
break;

// ---- UTILISATEUR CONNECTÉ ----
case 'me':
if (!isLoggedIn())
jsonResponse(['error' => 'Non connecté.'], 401);

$db = getDB();
$stmt = $db->prepare('SELECT id, nom, prenom, email, telephone, quartier, role, created_at FROM utilisateurs WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

jsonResponse(['user' => $user]);
break;

default:
jsonResponse(['error' => 'Action inconnue.'], 404);
}