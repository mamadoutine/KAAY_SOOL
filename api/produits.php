<?php

// KAAY SOOL – API Produits
// GET /api/produits.php → tous les produits
// GET /api/produits.php?id=1 → un produit
// GET /api/produits.php?cat=homme → par catégorie
// POST /api/produits.php → ajouter (admin)
// PUT /api/produits.php?id=1 → modifier (admin)
// DELETE /api/produits.php?id=1 → supprimer (admin)


require_once __DIR__ . '/../includes/helpers.php';

startSession();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$db = getDB();
$method = method();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$cat = isset($_GET['cat']) ? clean($_GET['cat']) : null;

// ============================================================
// GET – Récupérer les produits
// ============================================================
if ($method === 'GET') {

// UN SEUL PRODUIT avec ses tailles et couleurs
if ($id) {
$stmt = $db->prepare("
SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
FROM produits p
JOIN categories c ON p.categorie_id = c.id
WHERE p.id = ? AND p.actif = 1
");
$stmt->execute([$id]);
$produit = $stmt->fetch();

if (!$produit) jsonResponse(['error' => 'Produit introuvable.'], 404);

// Tailles disponibles
$stmt2 = $db->prepare("SELECT taille, stock FROM produit_tailles WHERE produit_id = ?");
$stmt2->execute([$id]);
$produit['tailles'] = $stmt2->fetchAll();

// Couleurs disponibles
$stmt3 = $db->prepare("SELECT nom_couleur, code_hex FROM produit_couleurs WHERE produit_id = ?");
$stmt3->execute([$id]);
$produit['couleurs'] = $stmt3->fetchAll();

jsonResponse(['produit' => $produit]);
}

// TOUS LES PRODUITS (avec filtre catégorie optionnel)
if ($cat) {
$stmt = $db->prepare("
SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
FROM produits p
JOIN categories c ON p.categorie_id = c.id
WHERE c.slug = ? AND p.actif = 1
ORDER BY p.created_at DESC
");
$stmt->execute([$cat]);
} else {
$stmt = $db->prepare("
SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
FROM produits p
JOIN categories c ON p.categorie_id = c.id
WHERE p.actif = 1
ORDER BY p.created_at DESC
");
$stmt->execute();
}

$produits = $stmt->fetchAll();
jsonResponse(['produits' => $produits, 'total' => count($produits)]);
}

// ============================================================
// POST – Ajouter un produit (ADMIN SEULEMENT)
// ============================================================
if ($method === 'POST') {
if (!isAdmin()) jsonResponse(['error' => 'Accès refusé.'], 403);

$body = getJsonBody();

$nom = clean($body['nom'] ?? '');
$description = clean($body['description'] ?? '');
$prix = (float)($body['prix'] ?? 0);
$stock = (int)($body['stock'] ?? 0);
$categorie_id = (int)($body['categorie_id'] ?? 0);
$image = clean($body['image'] ?? '');

if (!$nom || !$prix || !$categorie_id)
jsonResponse(['error' => 'Nom, prix et catégorie sont obligatoires.'], 400);

$slug = slugify($nom);

$stmt = $db->prepare("
INSERT INTO produits (categorie_id, nom, slug, description, prix, stock, image)
VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$categorie_id, $nom, $slug, $description, $prix, $stock, $image]);
$newId = $db->lastInsertId();

// Ajouter les tailles si fournies
if (!empty($body['tailles']) && is_array($body['tailles'])) {
$stmtT = $db->prepare("INSERT INTO produit_tailles (produit_id, taille, stock) VALUES (?, ?, ?)");
foreach ($body['tailles'] as $t) {
$stmtT->execute([$newId, $t['taille'], $t['stock'] ?? 0]);
}
}

// Ajouter les couleurs si fournies
if (!empty($body['couleurs']) && is_array($body['couleurs'])) {
$stmtC = $db->prepare("INSERT INTO produit_couleurs (produit_id, nom_couleur, code_hex) VALUES (?, ?, ?)");
foreach ($body['couleurs'] as $c) {
$stmtC->execute([$newId, $c['nom'] ?? '', $c['hex']]);
}
}

jsonResponse(['success' => true, 'message' => 'Produit ajouté.', 'id' => $newId], 201);
}

// ============================================================
// PUT – Modifier un produit (ADMIN SEULEMENT)
// ============================================================
if ($method === 'PUT') {
if (!isAdmin()) jsonResponse(['error' => 'Accès refusé.'], 403);
if (!$id) jsonResponse(['error' => 'ID produit manquant.'], 400);

$body = getJsonBody();

$champs = [];
$valeurs = [];

if (isset($body['nom'])) { $champs[] = 'nom = ?'; $valeurs[] = clean($body['nom']); }
if (isset($body['description'])) { $champs[] = 'description = ?'; $valeurs[] = clean($body['description']); }
if (isset($body['prix'])) { $champs[] = 'prix = ?'; $valeurs[] = (float)$body['prix']; }
if (isset($body['stock'])) { $champs[] = 'stock = ?'; $valeurs[] = (int)$body['stock']; }
if (isset($body['image'])) { $champs[] = 'image = ?'; $valeurs[] = clean($body['image']); }
if (isset($body['actif'])) { $champs[] = 'actif = ?'; $valeurs[] = (int)$body['actif']; }

if (empty($champs)) jsonResponse(['error' => 'Aucune donnée à modifier.'], 400);

$valeurs[] = $id;
$sql = "UPDATE produits SET " . implode(', ', $champs) . " WHERE id = ?";
$db->prepare($sql)->execute($valeurs);

jsonResponse(['success' => true, 'message' => 'Produit modifié.']);
}

// ============================================================
// DELETE – Supprimer un produit (ADMIN SEULEMENT)
// ============================================================
if ($method === 'DELETE') {
if (!isAdmin()) jsonResponse(['error' => 'Accès refusé.'], 403);
if (!$id) jsonResponse(['error' => 'ID produit manquant.'], 400);

// Désactiver plutôt que supprimer (soft delete)
$stmt = $db->prepare("UPDATE produits SET actif = 0 WHERE id = ?");
$stmt->execute([$id]);

jsonResponse(['success' => true, 'message' => 'Produit supprimé.']);
}

jsonResponse(['error' => 'Méthode non autorisée.'], 405);
