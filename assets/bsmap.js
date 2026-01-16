/**
 * BSMap - Beanstalk Foods
 */

document.addEventListener("DOMContentLoaded", function () {
  const mapEl = document.getElementById("bsmap-container");
  if (!mapEl) return;

  // Mapa centrado en Europa
  const map = L.map("bsmap-container", {
    zoomControl: true,
    scrollWheelZoom: true,
  }).setView([54, 10], 4);

  // Tiles
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
  }).addTo(map);

  // Colores por categoría (actualizados)
  const categoryColors = {
    distribuidor: "#6A2B58", // morado
    online: "#6D914C",       // verde
    fisica: "#116378",       // azul
  };

    // Texto + color por categoría (para la insignia del popup)
  const categoryMeta = {
    distribuidor: { label: "Distribuidor", color: "#6A2B58", textColor: "#ffffff" },
    online:       { label: "Venta Online", color: "#6D914C", textColor: "#ffffff" },
    fisica:       { label: "Tienda Física", color: "#116378", textColor: "#ffffff" }
  };

  // Datos desde WP
  const tiendas = (typeof bsmap_data !== "undefined" && Array.isArray(bsmap_data.tiendas))
    ? bsmap_data.tiendas
    : [];
  const promos = (typeof bsmap_data !== "undefined" && Array.isArray(bsmap_data.promos))
    ? bsmap_data.promos
    : [];
  const promoById = new Map(promos.map((p) => [String(p.id), p]));

  // Icono SVG tipo localizador
  function svgPin(color) {
    const svg =
      `<svg width="26" height="40" viewBox="0 0 26 40" xmlns="http://www.w3.org/2000/svg">
        <path d="M13 0C6 0 0.7 5.3 0.7 12.2C0.7 21.2 12.1 39.3 12.6 40C12.7 40.1 12.8 40.1 12.9 40C13.4 39.3 24.8 21.2 24.8 12.2C24.8 5.3 19.5 0 13 0Z" fill="${color}"/>
        <circle cx="13" cy="12" r="4.5" fill="#ffffff"/>
      </svg>`;
    return L.icon({
      iconUrl: "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(svg),
      iconSize: [26, 40],
      iconAnchor: [13, 40],
      popupAnchor: [0, -36],
      className: "bsmap-marker"
    });
  }

  const markerGroup = L.layerGroup().addTo(map);

  function getPromoForStore(t) {
    const promoId = String(t.promo_id || "");
    if (!t.veganuary_2x1 || !promoId || !promoById.has(promoId)) return null;
    return promoById.get(promoId);
  }

  function promoBadgeHTML(promo) {
    if (!promo) return "";
    if (promo.image) {
      return `<span class="bsmap-badge-veganuary"><img src="${promo.image}" alt="${promo.name || "Promocion"}"></span>`;
    }
    return `<span class="bsmap-badge-veganuary">${promo.name}</span>`;
  }

  function popupHTML(t) {
    const meta = categoryMeta[t.categoria] || { label: "Sin categoría", color: "#66594a", textColor: "#ffffff" };
    const zonaBlock = t.zona ? `<p>${t.zona}</p>` : "";
    const webBtn = t.web ? `<a class="btn-web" href="${t.web}" target="_blank" rel="noopener">Web</a>` : "";
    const igBtn  = t.instagram ? `<a class="btn-ig" href="${t.instagram}" target="_blank" rel="noopener">Instagram</a>` : "";
    const promo = getPromoForStore(t);
    const veganuaryBadge = promoBadgeHTML(promo);

    return `
      <div class="bsmap-popup" style="line-height:1.4">
        <div class="bsmap-popup-head">
          <h3>${t.nombre || "Sin nombre"}</h3>
          <div class="bsmap-badges">
            <span class="bsmap-badge" style="background:${meta.color};color:${meta.textColor}">${meta.label}</span>
            ${veganuaryBadge}
          </div>
        </div>
        ${t.direccion ? `<p>${t.direccion}</p>` : ""}
        ${zonaBlock}
        <div class="popup-btns">
          ${webBtn}
          ${igBtn}
        </div>
      </div>
    `;
  }


  function crearMarcador(t) {
    if (!t.lat || !t.lng) return null;
    const color = categoryColors[t.categoria] || "#66594a";
    const marker = L.marker([parseFloat(t.lat), parseFloat(t.lng)], { icon: svgPin(color) });
    marker.bindPopup(popupHTML(t));
    return marker;
  }

  function renderMarkers(filterCat = "todos", term = "", selectedPromos = new Set()) {
    markerGroup.clearLayers();
    const q = (term || "").trim().toLowerCase();
    const promoFilterActive = selectedPromos.size > 0;
    tiendas.forEach((t) => {
      if (!t.lat || !t.lng) return;
      if (filterCat !== "todos" && t.categoria !== filterCat) return;
      if (promoFilterActive) {
        const promoId = String(t.promo_id || "");
        if (!t.veganuary_2x1 || !promoId || !selectedPromos.has(promoId)) return;
      }

      if (q) {
        const hay = (t.nombre || "").toLowerCase().includes(q) ||
                    (t.direccion || "").toLowerCase().includes(q) ||
                    (t.zona || "").toLowerCase().includes(q);
        if (!hay) return;
      }

      const m = crearMarcador(t);
      if (m) markerGroup.addLayer(m);
    });
  }

  // Estado y pintado inicial
  let currentCat = "todos";
  let currentQuery = "";
  let selectedPromos = new Set();
  renderMarkers(currentCat, currentQuery, selectedPromos);

  // Mostrar/ocultar listado de promociones con checkboxes
  const usedPromos = promos.filter((p) => p && String(p.id));
  const promosWrap = document.querySelector(".bsmap-promos");
  const promosList = promosWrap ? promosWrap.querySelector(".bsmap-promos-list") : null;
  if (promosWrap && promosList) {
    if (usedPromos.length > 0) {
      usedPromos.forEach((promo) => {
        const label = document.createElement("label");
        label.className = "bsmap-promo-option";
        const input = document.createElement("input");
        input.type = "checkbox";
        input.value = String(promo.id);
        const span = document.createElement("span");
        if (promo.image) {
          const img = document.createElement("img");
          img.src = promo.image;
          img.alt = promo.name || "Promocion";
          span.appendChild(img);
        } else {
          span.textContent = promo.name;
        }
        label.appendChild(input);
        label.appendChild(span);
        promosList.appendChild(label);
        input.addEventListener("change", () => {
          if (input.checked) {
            selectedPromos.add(String(promo.id));
          } else {
            selectedPromos.delete(String(promo.id));
          }
          renderMarkers(currentCat, currentQuery, selectedPromos);
        });
      });
      promosWrap.hidden = false;
    } else {
      promosWrap.hidden = true;
    }
  }

  // Filtros
  const filterButtons = document.querySelectorAll(".bsmap-filters button");
  filterButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      filterButtons.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      currentCat = this.getAttribute("data-category") || "todos";
      renderMarkers(currentCat, currentQuery, selectedPromos);
    });
  });

  // Buscador
  const searchForm  = document.getElementById("bsmap-search");
  const searchInput = document.getElementById("bsmap-search-input");
  if (searchForm && searchInput) {
    const doSearch = () => {
      currentQuery = (searchInput.value || "").trim();
      renderMarkers(currentCat, currentQuery, selectedPromos);
    };
    searchForm.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();
      doSearch();
    });
    searchInput.addEventListener("input", function () {
      currentQuery = this.value || "";
      renderMarkers(currentCat, currentQuery, selectedPromos);
    });
  }

  // Encadre automático básico
  (function autoFit() {
    const pts = [];
    (tiendas || []).forEach(t => { if (t.lat && t.lng) pts.push([parseFloat(t.lat), parseFloat(t.lng)]); });
    if (pts.length < 2) return;
    const b = L.latLngBounds(pts);
    const latSpan = Math.abs(b.getNorth() - b.getSouth());
    const lngSpan = Math.abs(b.getEast() - b.getWest());
    if (latSpan > 5 || lngSpan > 5) {
      map.fitBounds(b, { padding: [40, 40], maxZoom: 6 });
    } else {
      map.setView(b.getCenter(), 6);
    }
  })();
});
