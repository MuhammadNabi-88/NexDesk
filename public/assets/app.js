(() => {
  const menu = document.getElementById("menu");
  const side = document.getElementById("side");
  if (menu && side) {
    const set = (open) => {
      side.classList.toggle("open", open);
      menu.setAttribute("aria-expanded", String(open));
    };
    menu.addEventListener("click", () => set(!side.classList.contains("open")));
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") set(false);
    });
    document
      .querySelector(".main")
      ?.addEventListener("click", () => set(false));
  }

  document.querySelectorAll(".flash.success").forEach((el) => {
    setTimeout(() => {
      el.classList.add("gone");
    }, 5000);
  });
})();
