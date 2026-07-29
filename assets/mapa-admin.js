/* global jQuery */
(function ($) {
  "use strict";

  function updatePreview(key, color) {
    const preview = document.querySelector(".pcm-preview");
    if (!preview || !key || !color) return;
    preview.style.setProperty(`--pcm-${key.replace(/_/g, "-")}`, color);
  }

  $(function () {
    $(".pcm-color-field").each(function () {
      const input = this;
      $(input).wpColorPicker({
        change: function (_event, ui) {
          updatePreview(input.dataset.colorKey, ui.color.toString());
        },
        clear: function () {
          updatePreview(input.dataset.colorKey, "#ffffff");
        },
      });
    });

    document.addEventListener("click", function (event) {
      const button = event.target.closest(".pcm-copy-shortcode");
      if (!button) return;
      const value = button.dataset.shortcode || "[mapa]";
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(value).then(function () {
          button.textContent = "Copiado";
        });
      } else {
        const area = document.createElement("textarea");
        area.value = value;
        document.body.appendChild(area);
        area.select();
        document.execCommand("copy");
        area.remove();
        button.textContent = "Copiado";
      }
    });
  });
})(jQuery);
