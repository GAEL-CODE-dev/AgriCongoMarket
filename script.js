const menuButton = document.getElementById("menu-btn");
const mobileMenu = document.getElementById("mobile-menu");

if (menuButton && mobileMenu) {
  menuButton.addEventListener("click", () => {
    mobileMenu.classList.toggle("hidden");
  });
}

const slides = document.getElementById("slides");
const dots = document.querySelectorAll(".dot");
const nextButton = document.getElementById("next");
const prevButton = document.getElementById("prev");

if (slides && dots.length > 0 && nextButton && prevButton) {
  let index = 0;
  const totalSlides = slides.children.length;

  function showSlide(i) {
    index = (i + totalSlides) % totalSlides;
    slides.style.transform = `translateX(-${index * 100}%)`;

    dots.forEach((dot, dotIndex) => {
      dot.classList.toggle("opacity-100", dotIndex === index);
      dot.classList.toggle("opacity-50", dotIndex !== index);
    });
  }

  nextButton.addEventListener("click", () => showSlide(index + 1));
  prevButton.addEventListener("click", () => showSlide(index - 1));

  dots.forEach((dot, dotIndex) => {
    dot.addEventListener("click", () => showSlide(dotIndex));
  });

  setInterval(() => {
    showSlide(index + 1);
  }, 5000);

  showSlide(0);
}

document.querySelectorAll(".card").forEach((card) => {
  const img = card.querySelector(".img");
  const overlay = card.querySelector(".overlay");
  const arrow = card.querySelector(".arrow");

  if (!img || !overlay || !arrow) return;

  card.addEventListener("mouseenter", () => {
    img.style.transform = "scale(1.08)";
    overlay.style.opacity = "1";
    arrow.style.transform = "translateX(6px)";
  });

  card.addEventListener("mouseleave", () => {
    img.style.transform = "scale(1)";
    overlay.style.opacity = "0";
    arrow.style.transform = "translateX(0)";
  });
});

const products = document.querySelectorAll(".product, .reveal");

if (products.length > 0 && "IntersectionObserver" in window) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("show");
      }
    });
  }, { threshold: 0.15 });

  products.forEach((item) => observer.observe(item));
}

document.querySelectorAll(".link").forEach((link) => {
  const arrow = link.querySelector(".arrow");
  if (!arrow) return;

  link.addEventListener("mouseenter", () => {
    arrow.style.transform = "translateX(6px)";
  });

  link.addEventListener("mouseleave", () => {
    arrow.style.transform = "translateX(0)";
  });
});
