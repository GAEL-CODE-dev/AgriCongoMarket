let productsData = [
  {
    id: 1,
    name: "Tomates de Brazzaville",
    description: "Tomates fraiches recoltees au petit matin.",
    ferme: "Cooperative Maraichere du Djoue",
    category: "fruits-legumes",
    categoryLabel: "Fruits et legumes",
    price: "1 500 FCFA / kg",
    priceValue: 1500,
    unit: "kg",
    image: "img/tomates-saison-scaled.webp",
    bgGradient: "from-red-50 to-orange-50"
  },
  {
    id: 2,
    name: "Bananes plantains",
    description: "Bananes plantains locales, fermes et savoureuses.",
    ferme: "Verger du Pool",
    category: "fruits-legumes",
    categoryLabel: "Fruits et legumes",
    price: "2 200 FCFA / regime",
    priceValue: 2200,
    unit: "regime",
    image: "img/bananes.webp",
    bgGradient: "from-green-50 to-emerald-50"
  },
  {
    id: 3,
    name: "Oeufs fermiers x6",
    description: "Oeufs frais issus d elevages familiaux locaux.",
    ferme: "Ferme Avicole de Kombe",
    category: "produits-laitiers",
    categoryLabel: "Produits frais et oeufs",
    price: "1 800 FCFA / boite",
    priceValue: 1800,
    unit: "boite",
    image: "img/oeufs.png",
    bgGradient: "from-stone-50 to-gray-50"
  },
  {
    id: 4,
    name: "Miel des Plateaux",
    description: "Miel artisanal congolais au parfum floral.",
    ferme: "Rucher des Plateaux",
    category: "epicerie",
    categoryLabel: "Epicerie et miel",
    price: "3 500 FCFA / pot",
    priceValue: 3500,
    unit: "pot",
    image: "img/miel-nitrub.jpg",
    bgGradient: "from-yellow-50 to-amber-50"
  },
  {
    id: 5,
    name: "Poulet fermier",
    description: "Poulet fermier prepare le jour meme.",
    ferme: "Elevage de la Lekoumou",
    category: "viandes",
    categoryLabel: "Viandes et volailles",
    price: "6 500 FCFA / piece",
    priceValue: 6500,
    unit: "piece",
    image: "img/viandes.jpg",
    bgGradient: "from-rose-50 to-red-50"
  },
  {
    id: 6,
    name: "Huile de palme rouge",
    description: "Huile locale non raffinee, ideale pour la cuisine.",
    ferme: "Transformation de Nkayi",
    category: "epicerie",
    categoryLabel: "Epicerie et miel",
    price: "2 800 FCFA / litre",
    priceValue: 2800,
    unit: "litre",
    image: "img/huile.png",
    bgGradient: "from-orange-100 to-yellow-50"
  }
];

const productsGrid = document.getElementById("productsGrid");
const noResultsDiv = document.getElementById("noResultsMessage");
const searchInput = document.getElementById("searchInput");
const resetSearchBtn = document.getElementById("resetSearchBtn");
const toastEl = document.getElementById("toastMessage");
const toastTextSpan = document.getElementById("toastText");
const cartItemsEl = document.getElementById("cartItems");
const cartEmptyStateEl = document.getElementById("cartEmptyState");
const cartCountEl = document.getElementById("cartCount");
const cartBadgeCountEl = document.getElementById("cartBadgeCount");
const cartTotalEl = document.getElementById("cartTotal");
const clearCartBtn = document.getElementById("clearCartBtn");
const checkoutBtn = document.getElementById("checkoutBtn");

let currentFilter = "all";
let currentSearch = "";
let toastTimeout;
let cart = [];
let useServerCart = false;
let csrfToken = "";

function formatPrice(value) {
  return `${Number(value).toLocaleString("fr-FR")} FCFA`;
}

function showToast(message = "Ajoute au panier") {
  if (!toastEl || !toastTextSpan) return;

  if (toastTimeout) clearTimeout(toastTimeout);
  toastTextSpan.innerText = message;
  toastEl.classList.add("show");

  toastTimeout = setTimeout(() => {
    toastEl.classList.remove("show");
  }, 2000);
}

function normalizeProduct(product) {
  return {
    id: Number(product.id),
    name: product.name,
    description: product.description,
    ferme: product.ferme,
    category: product.category,
    categoryLabel: product.categoryLabel,
    price: product.price,
    priceValue: Number(product.priceValue),
    unit: product.unit,
    image: product.image,
    bgGradient: product.bgGradient || "from-gray-50 to-white"
  };
}

function loadLocalCart() {
  try {
    const savedCart = localStorage.getItem("agricongo-cart");
    return savedCart ? JSON.parse(savedCart) : [];
  } catch (error) {
    return [];
  }
}

function saveLocalCart() {
  localStorage.setItem("agricongo-cart", JSON.stringify(cart));
}

async function requestJson(url, options = {}) {
  const headers = {
    Accept: "application/json",
    ...(options.headers || {})
  };

  if (options.method && options.method !== "GET" && csrfToken) {
    headers["X-CSRF-Token"] = csrfToken;
  }

  const response = await fetch(url, {
    ...options,
    headers
  });

  const data = await response.json().catch(() => ({}));

  if (typeof data.csrf_token === "string" && data.csrf_token !== "") {
    csrfToken = data.csrf_token;
  }

  if (!response.ok) {
    throw new Error(data.error || "Une erreur serveur est survenue.");
  }

  return data;
}

async function bootstrapApiState() {
  try {
    const [productsResponse, cartResponse] = await Promise.all([
      requestJson("api/products.php"),
      requestJson("api/cart.php")
    ]);

    if (Array.isArray(productsResponse.products) && productsResponse.products.length > 0) {
      productsData = productsResponse.products.map(normalizeProduct);
    }

    useServerCart = true;
    cart = Array.isArray(cartResponse.items) ? cartResponse.items : [];
    updateCartUI();
  } catch (error) {
    useServerCart = false;
    cart = loadLocalCart();
    updateCartUI();
    showToast("Catalogue charge en mode local");
  }
}

function filterProducts() {
  return productsData.filter((product) => {
    const matchesCategory = currentFilter === "all" || product.category === currentFilter;
    const searchTerm = currentSearch.trim().toLowerCase();

    if (!searchTerm) return matchesCategory;

    const matchesSearch =
      product.name.toLowerCase().includes(searchTerm) ||
      product.description.toLowerCase().includes(searchTerm) ||
      product.ferme.toLowerCase().includes(searchTerm) ||
      product.categoryLabel.toLowerCase().includes(searchTerm);

    return matchesCategory && matchesSearch;
  });
}

function updateProductCounter(totalVisible) {
  const filterContainer = document.querySelector(".flex.flex-wrap.items-center.gap-2");
  if (!filterContainer) return;

  let badge = document.getElementById("resultCountBadge");
  if (!badge) {
    badge = document.createElement("div");
    badge.id = "resultCountBadge";
    badge.className = "ml-2 rounded-full bg-gray-200 px-2 py-1 text-xs";
    filterContainer.appendChild(badge);
  }

  if (totalVisible === productsData.length && currentSearch === "" && currentFilter === "all") {
    badge.textContent = `${productsData.length} produits`;
  } else {
    badge.textContent = `${totalVisible} / ${productsData.length} produits`;
  }
}

function getCartTotal() {
  return cart.reduce((total, item) => total + (Number(item.priceValue) * Number(item.quantity)), 0);
}

function updateCartUI() {
  if (!cartItemsEl || !cartEmptyStateEl || !cartCountEl || !cartBadgeCountEl || !cartTotalEl) return;

  const totalItems = cart.reduce((total, item) => total + Number(item.quantity), 0);
  cartCountEl.textContent = totalItems;
  cartBadgeCountEl.textContent = totalItems;
  cartTotalEl.textContent = formatPrice(getCartTotal());

  if (cart.length === 0) {
    cartItemsEl.innerHTML = "";
    cartItemsEl.classList.add("hidden");
    cartEmptyStateEl.classList.remove("hidden");
    return;
  }

  cartEmptyStateEl.classList.add("hidden");
  cartItemsEl.classList.remove("hidden");
  cartItemsEl.innerHTML = cart.map((item) => `
    <div class="cart-item">
      <img src="${item.image}" alt="${item.name}" class="cart-item-image">
      <div>
        <h3 class="font-semibold text-gray-900">${item.name}</h3>
        <p class="mt-1 text-sm text-gray-500">${formatPrice(item.priceValue)} x ${item.quantity}</p>
        <p class="mt-1 text-xs text-green-700">${item.ferme}</p>
      </div>
      <button class="cart-item-remove text-sm font-semibold text-gray-400" data-remove-id="${item.id}">
        <i class="fas fa-trash-alt"></i>
      </button>
    </div>
  `).join("");

  document.querySelectorAll("[data-remove-id]").forEach((button) => {
    button.addEventListener("click", async () => {
      const productId = Number(button.getAttribute("data-remove-id"));
      await removeFromCart(productId);
    });
  });
}

async function addToCart(product) {
  if (useServerCart) {
    try {
      const data = await requestJson("api/cart.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          action: "add",
          product_id: product.id,
          quantity: 1
        })
      });
      cart = Array.isArray(data.items) ? data.items : [];
      updateCartUI();
      return;
    } catch (error) {
      useServerCart = false;
    }
  }

  const existingItem = cart.find((item) => item.id === product.id);

  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cart.push({
      id: product.id,
      name: product.name,
      priceValue: product.priceValue,
      ferme: product.ferme,
      image: product.image,
      quantity: 1
    });
  }

  saveLocalCart();
  updateCartUI();
}

async function removeFromCart(productId) {
  if (useServerCart) {
    try {
      const data = await requestJson("api/cart.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          action: "remove",
          product_id: productId
        })
      });
      cart = Array.isArray(data.items) ? data.items : [];
      updateCartUI();
      showToast("Produit retire du panier");
      return;
    } catch (error) {
      useServerCart = false;
    }
  }

  cart = cart
    .map((item) => item.id === productId ? { ...item, quantity: item.quantity - 1 } : item)
    .filter((item) => item.quantity > 0);
  saveLocalCart();
  updateCartUI();
  showToast("Produit retire du panier");
}

async function clearCart() {
  if (useServerCart) {
    try {
      const data = await requestJson("api/cart.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ action: "clear" })
      });
      cart = Array.isArray(data.items) ? data.items : [];
      updateCartUI();
      showToast("Panier vide");
      return;
    } catch (error) {
      useServerCart = false;
    }
  }

  cart = [];
  saveLocalCart();
  updateCartUI();
  showToast("Panier vide");
}

function renderProducts() {
  if (!productsGrid || !noResultsDiv) return;

  const filtered = filterProducts();
  updateProductCounter(filtered.length);

  if (filtered.length === 0) {
    productsGrid.innerHTML = "";
    productsGrid.classList.add("hidden");
    noResultsDiv.classList.remove("hidden");
    noResultsDiv.classList.add("flex");
    return;
  }

  noResultsDiv.classList.add("hidden");
  noResultsDiv.classList.remove("flex");
  productsGrid.classList.remove("hidden");

  const cardsHtml = filtered.map((product, idx) => {
    const animationDelay = `${idx * 0.05}s`;
    const bgGradient = product.bgGradient || "from-gray-50 to-white";

    return `
      <div class="product-card flex flex-col overflow-hidden rounded-2xl border border-green-100 bg-white shadow-md transition-all" style="animation-delay: ${animationDelay};">
        <div class="relative h-32 overflow-hidden bg-gradient-to-br ${bgGradient}">
          <img src="${product.image}" alt="${product.name}" class="h-full w-full object-cover" loading="lazy">
          <div class="absolute inset-0 bg-black/10"></div>
          <div class="absolute right-3 top-3 rounded-full bg-white/80 px-2 py-1 text-xs font-semibold text-green-800 shadow-sm backdrop-blur-sm">
            ${product.categoryLabel}
          </div>
        </div>
        <div class="flex flex-grow flex-col p-5">
          <h3 class="text-xl font-bold text-gray-800">${product.name}</h3>
          <p class="mt-1 text-sm leading-relaxed text-gray-500">${product.description}</p>
          <div class="mt-2 flex w-fit items-center gap-1 rounded-full bg-green-50 px-2 py-1 text-xs text-green-700">
            <i class="fas fa-tractor text-xs"></i>
            <span>${product.ferme}</span>
          </div>
          <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3">
            <div class="flex flex-col">
              <span class="text-2xl font-bold text-green-800">${product.price}</span>
              <span class="text-xs text-gray-400">prix fermier</span>
            </div>
            <button data-id="${product.id}" class="add-to-cart-btn flex items-center gap-2 rounded-full bg-green-700 px-4 py-2 text-sm font-medium text-white shadow-md transition hover:bg-green-800">
              <i class="fas fa-shopping-basket"></i> Ajouter
            </button>
          </div>
        </div>
      </div>
    `;
  }).join("");

  productsGrid.innerHTML = cardsHtml;

  document.querySelectorAll(".add-to-cart-btn").forEach((btn) => {
    btn.addEventListener("click", async (event) => {
      event.stopPropagation();
      const productId = Number(btn.getAttribute("data-id"));
      const product = productsData.find((item) => item.id === productId);

      if (!product) {
        showToast("Ajoute !");
        return;
      }

      await addToCart(product);
      showToast(`${product.name} ajoute au panier !`);
      btn.classList.add("scale-95");
      setTimeout(() => btn.classList.remove("scale-95"), 150);
    });
  });
}

function initFilterButtons() {
  const buttons = document.querySelectorAll(".filter-btn");

  buttons.forEach((button) => {
    if (button.getAttribute("data-filter") === "all") {
      button.classList.add("filter-active", "bg-green-700", "text-white");
    }

    button.addEventListener("click", () => {
      const filterValue = button.getAttribute("data-filter");
      if (!filterValue) return;

      currentFilter = filterValue;
      buttons.forEach((item) => item.classList.remove("filter-active", "bg-green-700", "text-white"));
      button.classList.add("filter-active", "bg-green-700", "text-white");
      renderProducts();
    });
  });
}

function initSearch() {
  if (searchInput) {
    searchInput.addEventListener("input", (event) => {
      currentSearch = event.target.value;
      renderProducts();
    });
  }

  if (resetSearchBtn && searchInput) {
    resetSearchBtn.addEventListener("click", () => {
      searchInput.value = "";
      currentSearch = "";
      renderProducts();
      showToast("Recherche reinitialisee");
      searchInput.focus();
    });
  }
}

async function initProductsPage() {
  if (!productsGrid) return;

  initFilterButtons();
  initSearch();

  if (clearCartBtn) {
    clearCartBtn.addEventListener("click", async () => {
      await clearCart();
    });
  }

  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", () => {
      if (cart.length === 0) {
        showToast("Votre panier est vide");
        return;
      }

      window.location.href = "checkout.php";
    });
  }

  await bootstrapApiState();
  renderProducts();
}

initProductsPage();
