-- ================================================
--  BASE DE DONNÉES : KAAY SOOL
--  E-commerce Mode Sénégalaise
--  Licence 3 IDA UNCHK – 2025/2026
--  À importer dans phpMyAdmin
-- ================================================

CREATE DATABASE IF NOT EXISTS kaay_sool
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kaay_sool;

-- ================================================
-- TABLE : utilisateurs
-- ================================================
CREATE TABLE utilisateurs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom         VARCHAR(100) NOT NULL,
  prenom      VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255) NOT NULL,  -- bcrypt hash
  telephone   VARCHAR(20),
  quartier    VARCHAR(100),
  role        ENUM('client','admin') DEFAULT 'client',
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ================================================
-- TABLE : categories
-- ================================================
CREATE TABLE categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom         VARCHAR(100) NOT NULL,
  slug        VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ================================================
-- TABLE : produits
-- ================================================
CREATE TABLE produits (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categorie_id INT UNSIGNED NOT NULL,
  nom          VARCHAR(200) NOT NULL,
  slug         VARCHAR(200) NOT NULL UNIQUE,
  description  TEXT,
  prix         DECIMAL(10,2) NOT NULL,
  stock        INT UNSIGNED DEFAULT 0,
  image        VARCHAR(255),
  actif        TINYINT(1) DEFAULT 1,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ================================================
-- TABLE : produit_tailles  (relation N-N)
-- ================================================
CREATE TABLE produit_tailles (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produit_id  INT UNSIGNED NOT NULL,
  taille      ENUM('XS','S','M','L','XL','XXL') NOT NULL,
  stock       INT UNSIGNED DEFAULT 0,
  FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
  UNIQUE KEY uq_produit_taille (produit_id, taille)
) ENGINE=InnoDB;

-- ================================================
-- TABLE : produit_couleurs
-- ================================================
CREATE TABLE produit_couleurs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produit_id  INT UNSIGNED NOT NULL,
  nom_couleur VARCHAR(50),
  code_hex    VARCHAR(7) NOT NULL,
  FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ================================================
-- TABLE : commandes
-- ================================================
CREATE TABLE commandes (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id  INT UNSIGNED,                  -- NULL si commande invité
  prenom          VARCHAR(100) NOT NULL,
  nom             VARCHAR(100) NOT NULL,
  telephone       VARCHAR(20) NOT NULL,
  quartier        VARCHAR(150) NOT NULL,
  mode_paiement   ENUM('wave','orange_money','free_money','cash') NOT NULL,
  statut          ENUM('en_attente','en_cours','livré','annulé') DEFAULT 'en_attente',
  total           DECIMAL(10,2) NOT NULL,
  livraison       DECIMAL(10,2) DEFAULT 0.00,
  code_coupon     VARCHAR(50),
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ================================================
-- TABLE : commande_articles  (détails de commande)
-- ================================================
CREATE TABLE commande_articles (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  commande_id INT UNSIGNED NOT NULL,
  produit_id  INT UNSIGNED NOT NULL,
  nom_produit VARCHAR(200) NOT NULL,   -- snapshot au moment de la commande
  prix_unitaire DECIMAL(10,2) NOT NULL,
  taille      VARCHAR(10),
  couleur     VARCHAR(7),
  quantite    INT UNSIGNED NOT NULL DEFAULT 1,
  sous_total  DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  FOREIGN KEY (produit_id)  REFERENCES produits(id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ================================================
-- TABLE : coupons
-- ================================================
CREATE TABLE coupons (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(50) NOT NULL UNIQUE,
  reduction   DECIMAL(5,2) NOT NULL,          -- pourcentage ex: 10.00 = 10%
  actif       TINYINT(1) DEFAULT 1,
  date_expiry DATE,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ================================================
-- TABLE : avis  (optionnel)
-- ================================================
CREATE TABLE avis (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produit_id      INT UNSIGNED NOT NULL,
  utilisateur_id  INT UNSIGNED NOT NULL,
  note            TINYINT NOT NULL CHECK (note BETWEEN 1 AND 5),
  commentaire     TEXT,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (produit_id)     REFERENCES produits(id)      ON DELETE CASCADE,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ================================================
-- DONNÉES DE TEST
-- ================================================

-- Catégories
INSERT INTO categories (nom, slug, description) VALUES
('Homme',      'homme',      'Tenues et vêtements pour hommes'),
('Femme',      'femme',      'Tenues et vêtements pour femmes'),
('Ensembles',  'ensemble',   'Ensembles et tenues de couple'),
('Accessoires','accessoires','Accessoires de mode africaine');

-- Produits
INSERT INTO produits (categorie_id, nom, slug, description, prix, stock, image) VALUES
(1, 'KAAY SOOL – Grand Boubou Bazin Riche',
 'grand-boubou-bazin-riche',
 'Confection soignée avec un tissu haut de gamme, doux, léger et agréable à porter. Finitions raffinées, coupe élégante et confort exceptionnel pour les cérémonies, événements et occasions spéciales.',
 30000.00, 15, 'images/boubou-bazin.jpg'),

(1, 'KAAY SOOL – Costume Africain',
 'costume-africain',
 'Costume africain élégant, taillé dans des tissus de qualité supérieure pour une allure impeccable.',
 25000.00, 10, 'images/costume-africain.jpg'),

(2, 'KAAY SOOL – Tenue Africaine Femme',
 'tenue-africaine-femme',
 'Tenue africaine féminine colorée, parfaite pour toutes les occasions festives.',
 15000.00, 20, 'images/tenue-femme.jpg'),

(3, 'KAAY SOOL – Ensemble Tenue Africaine',
 'ensemble-tenue-africaine',
 'Ensemble traditionnel africain pour couple, symbole d\'harmonie et d\'élégance.',
 40000.00, 8, 'images/ensemble.jpg'),

(1, 'KAAY SOOL – Ensemble Traditionnel',
 'ensemble-traditionnel',
 'Ensemble traditionnel haut de gamme, idéal pour les grandes cérémonies.',
 65000.00, 5, 'images/ensemble-traditionnel.jpg'),

(2, 'KAAY SOOL – Tenue Africaine Femme Premium',
 'tenue-femme-premium',
 'Robe africaine premium avec broderies artisanales et tissus wax de qualité.',
 50000.00, 7, 'images/tenue-femme-premium.jpg'),

(2, 'KAAY SOOL – Robe Africaine',
 'robe-africaine',
 'Robe africaine légère et colorée, parfaite pour le quotidien ou les sorties.',
 20000.00, 12, 'images/robe-africaine.jpg'),

(1, 'KAAY SOOL – Tenue Africain Homme',
 'tenue-africain-homme',
 'Tenue africaine masculine moderne, alliant tradition et style contemporain.',
 35000.00, 9, 'images/tenue-homme.jpg');

-- Tailles par produit
INSERT INTO produit_tailles (produit_id, taille, stock) VALUES
(1,'M',5),(1,'L',5),(1,'XL',5),
(2,'M',3),(2,'L',4),(2,'XL',2),(2,'XXL',1),
(3,'S',7),(3,'M',8),(3,'L',5),
(4,'M',3),(4,'L',3),(4,'XL',2),
(5,'L',2),(5,'XL',2),(5,'XXL',1),
(6,'S',2),(6,'M',2),(6,'L',2),(6,'XL',1),
(7,'S',4),(7,'M',4),(7,'L',4),
(8,'M',3),(8,'L',3),(8,'XL',3);

-- Couleurs par produit
INSERT INTO produit_couleurs (produit_id, nom_couleur, code_hex) VALUES
(1,'Bleu ciel','#ADD8E6'),(1,'Vert forêt','#228B22'),(1,'Marron','#8B4513'),(1,'Kaki','#556B2F'),
(2,'Bleu roi','#4169E1'),(2,'Gris ardoise','#2F4F4F'),(2,'Marron','#8B4513'),
(3,'Rouge tomate','#FF6347'),(3,'Or','#FFD700'),(3,'Violet','#9370DB'),
(4,'Bleu acier','#4682B4'),(4,'Vert mer','#2E8B57'),
(5,'Bleu ciel','#87CEEB'),(5,'Blanc cassé','#F5F5DC'),
(6,'Cramoisi','#DC143C'),(6,'Or','#FFD700'),(6,'Violet foncé','#8B008B'),
(7,'Vert','#228B22'),(7,'Or','#FFD700'),
(8,'Bleu roi','#4169E1'),(8,'Noir','#1C1C1C');

-- Admin par défaut  (mot de passe : Admin1234!)
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('SOOL', 'Admin', 'admin@kaaysool.sn',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- hash bcrypt de "password" — À CHANGER
 'admin');

-- Coupon de test
INSERT INTO coupons (code, reduction, date_expiry) VALUES
('KAAY10', 10.00, '2026-12-31'),
('BIENVENUE', 15.00, '2026-08-01');

-- Commandes de test
INSERT INTO commandes (utilisateur_id, prenom, nom, telephone, quartier, mode_paiement, statut, total) VALUES
(NULL, 'Aïssatou', 'Diallo',  '+221771234567', 'Almadies',  'wave',         'livré',    60000.00),
(NULL, 'Mamadou',  'Bah',     '+221782345678', 'Plateau',   'orange_money', 'en_cours', 30000.00),
(NULL, 'Fatou',    'Sow',     '+221793456789', 'Parcelles', 'cash',         'en_cours', 25000.00);

INSERT INTO commande_articles (commande_id, produit_id, nom_produit, prix_unitaire, taille, couleur, quantite, sous_total) VALUES
(1, 5, 'KAAY SOOL – Ensemble Traditionnel',        65000.00, 'L',  '#87CEEB', 1, 60000.00),
(2, 1, 'KAAY SOOL – Grand Boubou Bazin Riche',     30000.00, 'M',  '#ADD8E6', 1, 30000.00),
(3, 2, 'KAAY SOOL – Costume Africain',             25000.00, 'XL', '#4169E1', 1, 25000.00);
