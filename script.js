// ===== DONNÉES PRODUITS =====
const products = [
  {
    id: 1,
    name: "KAAY SOOL – Grand Boubou Bazin Riche",
    price: 30000,
    cat: "homme",
    taille: ["M","L","XL"],
    couleurs: ["#ADD8E6","#228B22","#8B4513","#556B2F"],
    emoji: "👘",
    img: "images/boubou-bazin.jpg",
    desc: "Confection soignée avec un tissu haut de gamme, doux, léger et agréable à porter. Finitions raffinées, coupe élégante et confort exceptionnel pour les cérémonies, événements et occasions spéciales."
  },
  {
    id: 2,
    name: "KAAY SOOL – Costume Africain",
    price: 25000,
    cat: "homme",
    taille: ["M","L","XL","XXL"],
    couleurs: ["#4169E1","#2F4F4F","#8B4513"],
    emoji: "🧥",
    img: "images/costume-africain.jpg",
    desc: "Costume africain élégant, taillé dans des tissus de qualité supérieure pour une allure impeccable."
  },
  {
    id: 3,
    name: "KAAY SOOL – Tenue Africaine Femme",
    price: 15000,
    cat: "femme",
    taille: ["S","M","L"],
    couleurs: ["#FF6347","#FFD700","#9370DB"],
    emoji: "👗",
    img: "images/tenue-femme.jpg",
    desc: "Tenue africaine féminine colorée, parfaite pour toutes les occasions festives."
  },
  {
    id: 4,
    name: "KAAY SOOL – Ensemble Tenue Africaine",
    price: 40000,
    cat: "ensemble",
    taille: ["M","L","XL"],
    couleurs: ["#4682B4","#2E8B57"],
    emoji: "👔",
    img: "images/ensemble.jpg",
    desc: "Ensemble traditionnel africain pour couple, symbole d'harmonie et d'élégance."
  },
  {
    id: 5,
    name: "KAAY SOOL – Ensemble Traditionnel",
    price: 65000,
    cat: "homme",
    taille: ["L","XL","XXL"],
    couleurs: ["#87CEEB","#F5F5DC"],
    emoji: "🥻",
    img: "images/ensemble-traditionnel.jpg",
    desc: "Ensemble traditionnel haut de gamme, idéal pour les grandes cérémonies."
  },
  {
    id: 6,
    name: "KAAY SOOL – Tenue Africaine Femme Premium",
    price: 50000,
    cat: "femme",
    taille: ["S","M","L","XL"],
    couleurs: ["#DC143C","#FFD700","#8B008B"],
    emoji: "💃",
    img: "images/tenue-femme-premium.jpg",
    desc: "Robe africaine premium avec broderies artisanales et tissus wax de qualité."
  },
  {
    id: 7,
    name: "KAAY SOOL – Robe Africaine",
    price: 20000,
    cat: "femme",
    taille: ["S","M","L"],
    couleurs: ["#228B22","#FFD700"],
    emoji: "👒",
    img: "images/robe-africaine.jpg",
    desc: "Robe africaine légère et colorée, parfaite pour le quotidien ou les sorties."
  },
  {
    id: 8,
    name: "KAAY SOOL – Tenu Africain Homme",
    price: 35000,
    cat: "homme",
    taille: ["M","L","XL"],
    couleurs: ["#4169E1","#1C1C1C"],
    emoji: "🧣",
    img: "images/tenue-homme.jpg",
    desc: "Tenue africaine masculine moderne, alliant tradition et style contemporain."
  },
];

// ===== PANIER =====
function getCart() {
  return JSON.parse(localStorage.getItem('kaaysool_cart') || '[]');
}

function saveCart(cart) {
  localStorage.setItem('kaaysool_cart', JSON.stringify(cart));
}

function addToCart(productId, qty = 1, taille = 'M', couleur = null) {
  // Trouver le produit
  const product = products.find(p => p.id === productId);
  if (!product) {
    showNotification('Produit introuvable !');
    return;
  }

  const cart = getCart();
  const existing = cart.find(i => i.id === productId && i.taille === taille);

  if (existing) {
    existing.qty += qty;
  } else {
    cart.push({
      id:     productId,
      name:   product.name,
      price:  product.price,
      qty:    qty,
      taille: taille,
      couleur: couleur || product.couleurs[0],
      emoji:  product.emoji,
      img:    product.img
    });
  }

  saveCart(cart);
  updateCartUI();
  showNotification('✅ Article ajouté au panier !');
}

function removeFromCart(productId, taille) {
  let cart = getCart();
  cart = cart.filter(i => !(i.id === productId && i.taille === taille));
  saveCart(cart);
  updateCartUI();
}

function updateQty(productId, taille, delta) {
  const cart = getCart();
  const item = cart.find(i => i.id === productId && i.taille === taille);
  if (item) {
    item.qty = Math.max(1, item.qty + delta);
    saveCart(cart);
  }
}

function getCartTotal() {
  return getCart().reduce((sum, i) => sum + i.price * i.qty, 0);
}

function getCartCount() {
  return getCart().reduce((sum, i) => sum + i.qty, 0);
}

function updateCartUI() {
  const count = getCartCount();
  document.querySelectorAll('#cartCount').forEach(el => {
    el.textContent = count;
  });
}

// ===== NOTIFICATION =====
function showNotification(msg) {
  const old = document.getElementById('kaay-notif');
  if (old) old.remove();

  const notif = document.createElement('div');
  notif.id = 'kaay-notif';
  notif.textContent = msg;
  notif.style.cssText = `
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #C9A84C;
    color: #0A0A0A;
    padding: 14px 24px;
    border-radius: 30px;
    font-family: 'Josefin Sans', sans-serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1px;
    z-index: 9999;
    box-shadow: 0 8px 25px rgba(201,168,76,0.4);
    transition: opacity 0.3s;
  `;
  document.body.appendChild(notif);
  setTimeout(() => {
    notif.style.opacity = '0';
    setTimeout(() => notif.remove(), 300);
  }, 2500);
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', updateCartUI);