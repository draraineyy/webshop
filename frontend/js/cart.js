
// cart.js – Bereinigte AJAX-Funktionen für den Warenkorb
// Einheitlich: action=list (Items-Array), action=total (Zwischensumme), action=count (Badge)

(function () {
  // ---------- Hilfsfunktionen ----------

  // EUR-Formatierung (de-DE)
  const fmtEUR = (n) =>
    (Number(n) || 0).toLocaleString("de-DE", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }) + " €";

  // Generischer Fetch mit Credentials und Text→JSON-Parsing (bessere Fehlerdiagnose)
  async function apiFetch(url, options = {}) {
    const res = await fetch(url, { credentials: "include", ...options });
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch {
      console.error("API parse error:", url, text);
      throw new Error("Ungültige Serverantwort");
    }
    if (!res.ok || data.error) {
      const msg = data.error || `HTTP ${res.status}`;
      console.error("API error:", url, msg, data);
      throw new Error(msg);
    }
    return data;
  }

  // ---------- Renderer ----------

  // Warenkorb-Items als Tabelle in #cart-items darstellen
  function renderCartItems(items) {
    const container = document.getElementById("cart-items");
    if (!container) return;

    if (!items || items.length === 0) {
      container.innerHTML = "<p class='text-muted mb-0'>Ihr Warenkorb ist leer.</p>";
      const badge=document.getElementById("cart-count")||document.getElementById("cartBadge");
      if(badge)badge.innerText="0";
      return;
    }

    let html = `
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead>
          <tr>
            <th>Produkt</th>
            <th class="text-end">Menge</th>
            <th class="text-end">Preis/Stk</th>
            <th class="text-end">Summe</th>
            <th class="text-center">Entfernen</th>
          </tr>
        </thead>
        <tbody>
    `; 

    items.forEach((it) => {
      const discountText = it.discount
        ? ` (−${(it.discount * 100).toFixed(0)}%)`
        : "";
      const posSum =
        it.position_sum != null
          ? Number(it.position_sum)
          : Number(it.unit_price) * Number(it.quantity);

      html += `
        <tr>
          <td>${escapeHtml(it.title)}${discountText}</td>
          <td class="text-end" style="width: 140px;">
            <input
              type="number"
              min="1"
              value="${Number(it.quantity)}"
              class="form-control form-control-sm text-end cart-qty"
              data-product-id="${it.product_id}"
              aria-label="Menge für ${escapeHtml(it.title)}"
            />
          </td>
          <td class="text-end">${fmtEUR(it.unit_price)}</td>
          <td class="text-end">${fmtEUR(posSum)}</td>
          <td class="text-center" style="width: 120px;">
            <button
              type="button"
              class="btn btn-sm btn-danger cart-remove"
              data-remove-id="${it.product_id}"
              title="Artikel entfernen"
            >
              <i class="fa-solid fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
    });

    html += "</tbody></table></div>";
    container.innerHTML = html;

    // Menge ändern
    container.querySelectorAll(".cart-qty").forEach((input)=>{
      input.addEventListener("change", async (e)=>{
        const pid=e.target.getAttribute("data-product-id");
        const qty=parseInt(e.target.value, 10);
        if(isNaN(qty)||qty<1){
          e.target.value="1";
          alert("Menge muss mindestens 1 sein.");
          return;
        }
        try{
          await updateCart(pid, qty);
        }catch (err){
          console.error("Mengen-Update fehlgeschlagen:", err.message);
          await getCart();
        }
      });
    });

    //Entfernen
    container.querySelectorAll(".cart-remove").forEach((btn)=>{
      btn.addEventListener("click", async(e)=>{
        const pid=e.currentTarget.getAttribute("data-remove-id");
        if(!pid) return;
        try{
          await removeFromCart(pid);
        }catch(err){
          console.error("Entfernen fehlgeschlagen:", err.message);
          alert("Warenkorb-Fehler: " +err.message);
        }
      });
    });
    const totalQty=items.reduce((acc, it)=>acc + Number(it.quantity||0), 0);
    const badgeEl=document.getElementById("cart-count")||document.getElementById("cartBadge");
    if(badgeEl)badgeEl.innerText=String(totalQty);
  }

  // Badge neben Warenkorbsymbol (#cartBadge) aktualisieren
  async function updateCartBadge() {
    try {
      const data = await apiFetch("../backend/cartController.php?action=count");
      const badgeCount=Number(data.count)||0;
      const badge = document.getElementById("cart-count")||document.getElementById("cartBadge");
      if (badge) badge.innerText = badgeCount;
    } catch (err) {
      console.warn("updateCartBadge:", err.message);
    }
  }

  // Zwischensumme rechts (#cart-total) aktualisieren
  async function updateCartTotal() {
    try {
      const data = await apiFetch("../backend/cartController.php?action=total");
      const totalEl = document.getElementById("cart-total");
      if (totalEl) totalEl.innerText = fmtEUR(data.total);
    } catch (err) {
      console.warn("updateCartTotal:", err.message);
    }
  }

  // Escape für Titel (XSS-Schutz im Frontend)
  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  // ---------- Öffentliche API: Add / Update / Remove / Get ----------

  // Warenkorb-Liste neu laden
  async function getCart() {
    try {
      const items = await apiFetch("../backend/cartController.php?action=list");
      renderCartItems(items);
    } catch (err) {
      console.error("getCart:", err.message);
      const container = document.getElementById("cart-items");
      if (container)
        container.innerHTML =
          "<p class='text-danger mb-0'>Warenkorb konnte nicht geladen werden.</p>";
    }
  }

  // Produkt hinzufügen
  async function addToCart(productId, quantity = 1) {
    const body = new URLSearchParams({
      product_id: String(productId),
      quantity: String(quantity),
    });

    try {
      await apiFetch("../backend/cartController.php?action=add", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body,
      });

      // Nach Erfolg: Liste, Summe, Badge neu laden
      await Promise.all([getCart(), updateCartTotal(), updateCartBadge()]);
    } catch (err) {
      console.error("addToCart:", err.message);
      alert("Warenkorb-Fehler: " + err.message);
    }
  }

  // Produktmenge ändern
  async function updateCart(productId, quantity) {
    const body = new URLSearchParams({
      product_id: String(productId),
      quantity: String(quantity),
    });

    try {
      await apiFetch("../backend/cartController.php?action=update", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body,
      });

      await Promise.all([getCart(), updateCartTotal(), updateCartBadge()]);
    } catch (err) {
      console.error("updateCart:", err.message);
      alert("Warenkorb-Fehler: " + err.message);
    }
  }

  // Produkt entfernen
  async function removeFromCart(productId) {
    const body = new URLSearchParams({ product_id: String(productId) });

    try {
      await apiFetch("../backend/cartController.php?action=remove", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body,
      });

      await Promise.all([getCart(), updateCartTotal(), updateCartBadge()]);
    } catch (err) {
      console.error("removeFromCart:", err.message);
      alert("Warenkorb-Fehler: " + err.message);
    }
  }

  // Optional: Preis eines einzelnen Produkts laden (falls irgendwo benötigt)
  async function getProductPrice(productId) {
    try {
      const data = await apiFetch(
        "../backend/cartController.php?action=price&product_id=" + encodeURIComponent(productId)
      );
      const el = document.getElementById("price-" + productId);
      if (el) el.innerText = fmtEUR(data.price);
    } catch (err) {
      console.warn("getProductPrice:", err.message);
    }
  }

  // ---------- Init ----------
  document.addEventListener("DOMContentLoaded", () => {
    getCart();
    updateCartTotal();
    updateCartBadge();
  });

  // ---------- Exporte ins globale Scope ----------
  window.addToCart = addToCart;
  window.updateCart = updateCart;
  window.removeFromCart = removeFromCart;
  window.getCart = getCart;
  window.updateCartTotal = updateCartTotal;
  window.updateCartBadge = updateCartBadge;
  window.getProductPrice = getProductPrice;
})();
