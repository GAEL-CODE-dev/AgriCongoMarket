const menuButton = document.getElementById("menu-btn");
const mobileMenu = document.getElementById("mobile-menu");

if (menuButton && mobileMenu) {
  menuButton.addEventListener("click", () => {
    mobileMenu.classList.toggle("hidden");
  });
}

const filterButtons = document.querySelectorAll(".production-filter");
const productionCards = document.querySelectorAll(".production-card");

if (filterButtons.length > 0 && productionCards.length > 0) {
  filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const filter = button.getAttribute("data-filter");

      filterButtons.forEach((item) => item.classList.remove("active"));
      button.classList.add("active");

      productionCards.forEach((card) => {
        const category = card.getAttribute("data-category");
        const showCard = filter === "all" || filter === category;
        card.classList.toggle("is-hidden", !showCard);
      });
    });
  });
}
