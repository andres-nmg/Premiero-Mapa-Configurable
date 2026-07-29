/* global L */
(function () {
  "use strict";

  function escapeHTML(value) {
    const element = document.createElement("div");
    element.textContent = value == null ? "" : String(value);
    return element.innerHTML;
  }

  function safeLink(value, type) {
    const text = value == null ? "" : String(value).trim();
    if (!text) return "";

    if (type === "email") {
      return `<a href="mailto:${encodeURIComponent(text)}">${escapeHTML(text)}</a>`;
    }
    if (type === "tel") {
      const phone = text.replace(/[^0-9+().\-\s]/g, "");
      return `<a href="tel:${escapeHTML(phone.replace(/\s/g, ""))}">${escapeHTML(text)}</a>`;
    }
    if (type === "url") {
      try {
        const url = new URL(text, window.location.origin);
        if (!["http:", "https:"].includes(url.protocol)) return escapeHTML(text);
        return `<a href="${escapeHTML(url.href)}" target="_blank" rel="noopener noreferrer">${escapeHTML(text)}</a>`;
      } catch (error) {
        return escapeHTML(text);
      }
    }
    return escapeHTML(text).replace(/\n/g, "<br>");
  }

  function safeWebURL(value) {
    try {
      const url = new URL(String(value || ""), window.location.origin);
      return ["http:", "https:"].includes(url.protocol) ? url.href : "";
    } catch (error) {
      return "";
    }
  }

  function pinIcon(color) {
    const safeColor = /^#[0-9a-f]{6}$/i.test(color || "") ? color : "#E76F51";
    const svg = `<svg width="30" height="44" viewBox="0 0 30 44" xmlns="http://www.w3.org/2000/svg">
      <path d="M15 0C6.7 0 0 6.7 0 15c0 10.9 13.8 28.1 14.4 28.8a.8.8 0 0 0 1.2 0C16.2 43.1 30 25.9 30 15 30 6.7 23.3 0 15 0Z" fill="${safeColor}"/>
      <circle cx="15" cy="15" r="5.5" fill="#fff"/>
    </svg>`;
    return L.icon({
      iconUrl: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`,
      iconSize: [30, 44],
      iconAnchor: [15, 44],
      popupAnchor: [0, -39],
      className: "pcm-map__marker",
    });
  }

  function popupHTML(location, webLabel) {
    const extras = Array.isArray(location.extras) ? location.extras : [];
    const extraHTML = extras
      .filter((field) => field && String(field.value || "").trim())
      .map(
        (field) =>
          `<p class="pcm-popup__field"><strong>${escapeHTML(field.label)}:</strong> ${safeLink(
            field.value,
            field.type
          )}</p>`
      )
      .join("");
    const web = safeWebURL(location.web);

    return `<article class="pcm-popup">
      <h3>${escapeHTML(location.name || "")}</h3>
      ${location.address ? `<p>${escapeHTML(location.address)}</p>` : ""}
      ${extraHTML}
      ${web ? `<a class="pcm-popup__web" href="${escapeHTML(web)}" target="_blank" rel="noopener noreferrer">${escapeHTML(webLabel || "Web")}</a>` : ""}
    </article>`;
  }

  function initMap(wrapper) {
    let payload;
    try {
      payload = JSON.parse(wrapper.dataset.map || "{}");
    } catch (error) {
      payload = {};
    }

    const canvas = wrapper.querySelector(".pcm-map__canvas");
    const form = wrapper.querySelector(".pcm-map__search");
    const input = form ? form.querySelector("input") : null;
    const count = wrapper.querySelector(".pcm-map__count");
    const locations = Array.isArray(payload.locations) ? payload.locations : [];
    if (!canvas || typeof L === "undefined") return;

    const map = L.map(canvas, {
      zoomControl: true,
      scrollWheelZoom: true,
    }).setView([40.4168, -3.7038], 6);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
    }).addTo(map);

    const markerLayer = L.layerGroup().addTo(map);
    const icon = pinIcon(payload.marker);

    function validCoordinates(location) {
      const lat = Number.parseFloat(location.lat);
      const lng = Number.parseFloat(location.lng);
      return Number.isFinite(lat) && Number.isFinite(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
    }

    function filterLocations(term) {
      const query = String(term || "").trim().toLocaleLowerCase();
      if (!query) return locations.filter(validCoordinates);
      return locations.filter((location) => {
        if (!validCoordinates(location)) return false;
        const extras = Array.isArray(location.extras)
          ? location.extras.map((field) => field.value || "").join(" ")
          : "";
        return [location.name, location.address, extras]
          .join(" ")
          .toLocaleLowerCase()
          .includes(query);
      });
    }

    function fitMarkers(markers) {
      if (!markers.length) return;
      if (markers.length === 1) {
        map.setView(markers[0].getLatLng(), 14);
        return;
      }
      const group = L.featureGroup(markers);
      map.fitBounds(group.getBounds(), { padding: [36, 36], maxZoom: 14 });
    }

    function render(term, shouldFit) {
      markerLayer.clearLayers();
      const visible = filterLocations(term);
      const markers = visible.map((location) => {
        const marker = L.marker(
          [Number.parseFloat(location.lat), Number.parseFloat(location.lng)],
          { icon }
        );
        marker.bindPopup(popupHTML(location, payload.webLabel));
        marker.addTo(markerLayer);
        return marker;
      });

      if (count) {
        count.textContent = visible.length
          ? `${visible.length} ${visible.length === 1 ? "resultado" : "resultados"}`
          : payload.emptyText || "No hay resultados";
      }
      if (shouldFit) fitMarkers(markers);
    }

    render("", true);
    if (form && input) {
      form.addEventListener("submit", (event) => {
        event.preventDefault();
        render(input.value, true);
      });
      input.addEventListener("input", () => render(input.value, false));
    }

    window.setTimeout(() => map.invalidateSize(), 50);
  }

  function initAll() {
    document.querySelectorAll(".pcm-map").forEach(initMap);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAll);
  } else {
    initAll();
  }
})();
