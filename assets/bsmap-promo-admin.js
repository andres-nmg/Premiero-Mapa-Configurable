/* global wp */
document.addEventListener("DOMContentLoaded", function () {
  if (typeof wp === "undefined" || !wp.media) return;

  const frameTitle = "Selecciona una imagen";
  const frameButton = "Usar esta imagen";
  let mediaFrame = null;

  document.addEventListener("click", function (e) {
    const uploadBtn = e.target.closest(".bsmap-promo-image-upload");
    const removeBtn = e.target.closest(".bsmap-promo-image-remove");

    if (uploadBtn) {
      e.preventDefault();
      const wrap = uploadBtn.closest(".bsmap-promo-image-wrap");
      if (!wrap) return;
      const input = wrap.querySelector('input[name="bsmap_promo_image_id"]');
      const remove = wrap.querySelector(".bsmap-promo-image-remove");

      if (mediaFrame) {
        mediaFrame.off("select");
      }

      mediaFrame = wp.media({
        title: frameTitle,
        button: { text: frameButton },
        multiple: false,
      });

      mediaFrame.on("select", function () {
        const attachment = mediaFrame.state().get("selection").first().toJSON();
        if (input) input.value = attachment.id;
        const existingImg = wrap.querySelector("img");
        if (existingImg) existingImg.remove();
        const img = document.createElement("img");
        img.src = attachment.url;
        img.style.maxWidth = "160px";
        img.style.height = "auto";
        img.style.display = "block";
        img.style.marginBottom = "8px";
        wrap.prepend(img);
        if (remove) remove.disabled = false;
      });

      mediaFrame.open();
    }

    if (removeBtn) {
      e.preventDefault();
      const wrap = removeBtn.closest(".bsmap-promo-image-wrap");
      if (!wrap) return;
      const input = wrap.querySelector('input[name="bsmap_promo_image_id"]');
      if (input) input.value = "";
      const img = wrap.querySelector("img");
      if (img) img.remove();
      removeBtn.disabled = true;
    }
  });
});
