<?php

// KAAY SOOL – API Commandes
// POST /api/commandes.php → passer une commande
// GET /api/commandes.php → toutes les commandes (admin)
// GET /api/commandes.php?id=1 → une commande
// GET /api/commandes.php?mes=1 → commandes de l'utilisateur connecté
// PUT /api/commandes.php?id=1 → changer le statut (admin)


require_once __DIR__ . '/../includes/helpers.php';

startSession();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$db = getDB();
$method = method();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// POST – Passer une commande

if ($method === 'POST') {
$body = getJsonBody();

// Informations de livraison
$prenom = clean($body['prenom'] ?? '');
$nom = clean($body['nom'] ?? '');
$telephone = clean($body['telephone'] ?? '');
$quartier = clean($body['quartier'] ?? '');
$mode_paiement = clean($body['mode_paiement'] ?? '');
$code_coupon = clean($body['code_coupon'] ?? '');

// Validation
if (!$prenom || !$nom || !$telephone || !$quartier)
jsonResponse(['error' => 'Informations de livraison incomplètes.'], 400);

$modes_valides = ['wave', 'orange_money', 'free_money', 'cash'];
if (!in_array($mode_paiement, $modes_valides))
jsonResponse(['error' => 'Mode de paiement invalide.'], 400);

// Récupérer le panier depuis la session
if (empty($_SESSION['panier']))
jsonResponse(['error' => 'Votre panier est vide.'], 400);

$panier = $_SESSION['panier'];

// Vérifier le coupon si fourni
$reduction = 0;
if ($code_coupon) {
$stmtC = $db->prepare("SELECT * FROM coupons WHERE code = ? AND actif = 1 AND (date_expiry IS NULL OR date_expiry >= CURDATE())");
$stmtC->execute([$code_coupon]);
$coupon = $stmtC->fetch();
if ($coupon) $reduction = (float)$coupon['reduction'];
}

// Calculer le total
$sous_total = array_sum(array_map(
fn($i) => $i['prix'] * $i['quantite'],
$panier
));

$total = $reduction > 0
? $sous_total - ($sous_total * $reduction / 100)
: $sous_total;

// Utilisateur connecté ou invité
$utilisateur_id = $_SESSION['user_id'] ?? null;

// Démarrer une transaction
$db->beginTransaction();

try {
// Insérer la commande
$stmt = $db->prepare("
INSERT INTO commandes
(utilisateur_id, prenom, nom, telephone, quartier, mode_paiement, total, livraison, code_coupon)
VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
");
$stmt->execute([
$utilisateur_id, $prenom, $nom, $telephone,
$quartier, $mode_paiement, $total,
$code_coupon ?: null
]);
$commande_id = $db->lastInsertId();

// Insérer les articles de la commande
$stmtA = $db->prepare("
INSERT INTO commande_articles
(commande_id, produit_id, nom_produit, prix_unitaire, taille, couleur, quantite, sous_total)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($panier as $item) {
// Vérifier le stock
$stmtStock = $db->prepare("SELECT stock FROM produits WHERE id = ?");
$stmtStock->execute([$item['produit_id']]);
$stock_actuel = $stmtStock->fetchColumn();

if ($stock_actuel < $item['quantite']) {
$db->rollBack();
jsonResponse(['error' => 'Stock insuffisant pour : ' . $item['nom']], 400);
}

$stmtA->execute([
$commande_id,
$item['produit_id'],
$item['nom'],
$item['prix'],
$item['taille'],
$item['couleur'],
$item['quantite'],
$item['prix'] * $item['quantite'],
]);

// Décrémenter le stock
$db->prepare("UPDATE produits SET stock = stock - ? WHERE id = ?")
->execute([$item['quantite'], $item['produit_id']]);
}

$db->commit();

// Vider le panier après commande confirmée
$_SESSION['panier'] = [];

jsonResponse([
'success' => true,
'message' => 'Commande passée avec succès !',
'commande_id' => $commande_id,
'total' => $total,
], 201);

} catch (Exception $e) {
$db->rollBack();
jsonResponse(['error' => 'Erreur lors de la commande. Réessayez.'], 500);
}
}

// GET – Récupérer les commandes

if ($method === 'GET') {

// UNE SEULE COMMANDE avec ses articles
if ($id) {
// Vérifier accès : admin ou propriétaire
if (!isAdmin() && (!isLoggedIn() || true)) {
// On laisse passer pour l'instant (à sécuriser selon les besoins)
}

$stmt = $db->prepare("SELECT * FROM commandes WHERE id = ?");
$stmt->execute([$id]);
$commande = $stmt->fetch();

if (!$commande) jsonResponse(['error' => 'Commande introuvable.'], 404);

// Articles de la commande
$stmtA = $db->prepare("SELECT * FROM commande_articles WHERE commande_id = ?");
$stmtA->execute([$id]);
$commande['articles'] = $stmtA->fetchAll();

jsonResponse(['commande' => $commande]);
}

// MES COMMANDES (utilisateur connecté)
if (isset($_GET['mes']) && isLoggedIn()) {
$stmt = $db->prepare("
SELECT * FROM commandes
WHERE utilisateur_id = ?
ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$commandes = $stmt->fetchAll();
jsonResponse(['commandes' => $commandes]);
}

// TOUTES LES COMMANDES (admin seulement)
if (!isAdmin()) jsonResponse(['error' => 'Accès refusé.'], 403);

$stmt = $db->prepare("
SELECT c.*,
COUNT(ca.id) AS nb_articles
FROM commandes c
LEFT JOIN commande_articles ca ON ca.commande_id = c.id
GROUP BY c.id
ORDER BY c.created_at DESC
");
$stmt->execute();
$commandes = $stmt->fetchAll();

// Stats globales pour le dashboard
$stats = $db->query("
SELECT
COUNT(*) AS total_commandes,
SUM(total) AS chiffre_affaires,
SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) AS en_attente,
SUM(CASE WHEN statut = 'livré' THEN 1 ELSE 0 END) AS livrees
FROM commandes
")->fetch();

jsonResponse([
'commandes' => $commandes,
'stats' => $stats,
'total' => count($commandes),
]);
}

// PUT – Changer le statut d'une commande (ADMIN)

if ($method === 'PUT') {
if (!isAdmin()) jsonResponse(['error' => 'Accès refusé.'], 403);
if (!$id) jsonResponse(['error' => 'ID commande manquant.'], 400);

$body = getJsonBody();
$statut = clean($body['statut'] ?? '');

$statuts_valides = ['en_attente', 'en_cours', 'livré', 'annulé'];
if (!in_array($statut, $statuts_valides))
jsonResponse(['error' => 'Statut invalide.'], 400);

$db->prepare("UPDATE commandes SET statut = ? WHERE id = ?")
->execute([$statut, $id]);

jsonResponse(['success' => true, 'message' => 'Statut mis à jour : ' . $statut]);
}

jsonResponse(['error' => 'Méthode non autorisée.'], 405);