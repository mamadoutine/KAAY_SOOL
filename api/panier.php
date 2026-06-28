<?php
// KAAY SOOL – API Panier (Session serveur)
// GET /api/panier.php → voir le panier
// POST /api/panier.php → ajouter un article
// PUT /api/panier.php → modifier la quantité
// DELETE /api/panier.php?id=1 → supprimer un article
// DELETE /api/panier.php?vider=1 → vider tout le panier

require_once __DIR__ . '/../includes/helpers.php';

startSession();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$db = getDB();
$method = method();

// Initialiser le panier en session si vide
if (!isset($_SESSION['panier'])) {
$_SESSION['panier'] = [];
}

// GET – Voir le contenu du panier

if ($method === 'GET') {
$panier = $_SESSION['panier'];
$total = 0;
$nb_articles = 0;

foreach ($panier as $item) {
$total += $item['prix'] * $item['quantite'];
$nb_articles += $item['quantite'];
}

jsonResponse([
'panier' => array_values($panier),
'total' => $total,
'nb_articles' => $nb_articles,
'livraison' => 0, // livraison gratuite
]);
}

// POST – Ajouter un article au panier

if ($method === 'POST') {
$body = getJsonBody();

$produit_id = (int)($body['produit_id'] ?? 0);
$quantite = max(1, (int)($body['quantite'] ?? 1));
$taille = clean($body['taille'] ?? '');
$couleur = clean($body['couleur'] ?? '');

if (!$produit_id) jsonResponse(['error' => 'produit_id manquant.'], 400);

// Vérifier que le produit existe en base
$stmt = $db->prepare("SELECT id, nom, prix, image, stock FROM produits WHERE id = ? AND actif = 1");
$stmt->execute([$produit_id]);
$produit = $stmt->fetch();

if (!$produit) jsonResponse(['error' => 'Produit introuvable.'], 404);
if ($produit['stock'] < $quantite) jsonResponse(['error' => 'Stock insuffisant.'], 400);

// Clé unique par produit + taille + couleur
$cle = $produit_id . '_' . $taille . '_' . $couleur;

if (isset($_SESSION['panier'][$cle])) {
// Augmenter la quantité si déjà dans le panier
$_SESSION['panier'][$cle]['quantite'] += $quantite;
} else {
// Ajouter nouvel article
$_SESSION['panier'][$cle] = [
'cle' => $cle,
'produit_id' => $produit_id,
'nom' => $produit['nom'],
'prix' => (float)$produit['prix'],
'image' => $produit['image'],
'taille' => $taille,
'couleur' => $couleur,
'quantite' => $quantite,
];
}

$nb_articles = array_sum(array_column($_SESSION['panier'], 'quantite'));

jsonResponse([
'success' => true,
'message' => 'Article ajouté au panier.',
'nb_articles' => $nb_articles,
]);
}

// PUT – Modifier la quantité d'un article

if ($method === 'PUT') {
$body = getJsonBody();
$cle = clean($body['cle'] ?? '');
$quantite = (int)($body['quantite'] ?? 1);

if (!$cle) jsonResponse(['error' => 'Clé article manquante.'], 400);

if (!isset($_SESSION['panier'][$cle]))
jsonResponse(['error' => 'Article non trouvé dans le panier.'], 404);

if ($quantite <= 0) {
// Supprimer si quantité = 0
unset($_SESSION['panier'][$cle]);
jsonResponse(['success' => true, 'message' => 'Article supprimé du panier.']);
}

$_SESSION['panier'][$cle]['quantite'] = $quantite;

$total = array_sum(array_map(
fn($i) => $i['prix'] * $i['quantite'],
$_SESSION['panier']
));

jsonResponse([
'success' => true,
'message' => 'Quantité mise à jour.',
'total' => $total,
'sous_total_article' => $_SESSION['panier'][$cle]['prix'] * $quantite,
]);
}


// DELETE – Supprimer un article ou vider le panier

if ($method === 'DELETE') {

// Vider tout le panier
if (isset($_GET['vider'])) {
$_SESSION['panier'] = [];
jsonResponse(['success' => true, 'message' => 'Panier vidé.']);
}

// Supprimer un article précis
$cle = clean($_GET['cle'] ?? '');
if (!$cle) jsonResponse(['error' => 'Clé article manquante.'], 400);

if (!isset($_SESSION['panier'][$cle]))
jsonResponse(['error' => 'Article non trouvé.'], 404);

unset($_SESSION['panier'][$cle]);

$total = array_sum(array_map(
fn($i) => $i['prix'] * $i['quantite'],
$_SESSION['panier']
));

jsonResponse([
'success' => true,
'message' => 'Article supprimé.',
'total' => $total,
'nb_articles' => array_sum(array_column($_SESSION['panier'], 'quantite')),
]);
}

jsonResponse(['error' => 'Méthode non autorisée.'], 405);