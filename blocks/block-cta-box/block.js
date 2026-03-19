const ctaBoxes = document.querySelectorAll(".cta-box.float");
const contactFormFooterWrapper = document.querySelector(
  ".contact-form-footer__wrapper",
);

const positionedBoxes = new WeakSet();

// Función para posicionar UN CTA box específico
function positionCTABox(element) {
  if (positionedBoxes.has(element)) return; // Ya posicionado

  const box = element.querySelector(".cta-box__box");
  if (!box) return;

  const boxHeight = box.offsetHeight || 0;
  if (boxHeight === 0) return;

  const topPosition = boxHeight * 0.55;
  const bottomPosition = boxHeight * 0.5;

  const firstChild = element.firstElementChild;
  const prevSibling = element.previousElementSibling;
  const nextSibling = element.nextElementSibling;

  requestAnimationFrame(() => {
    // Establecer CSS variable
    element.style.setProperty("--cta-approximate-top", `${topPosition}px`);
    element.classList.add("cta-measured");

    if (firstChild) {
      firstChild.style.cssText += `margin-top:${-topPosition}px;padding-bottom:${topPosition}px;`;
    }

    if (prevSibling) {
      prevSibling.style.cssText += `padding-bottom:${
        topPosition + 73
      }px;border-bottom:8px solid rgb(var(--tertiary));`;
    }

    if (nextSibling) {
      const isFooter = nextSibling.classList.contains("site-footer");
      const paddingTop = `${bottomPosition + 63}px`;

      nextSibling.style.marginTop = `${-boxHeight - 33}px`;

      if (isFooter && contactFormFooterWrapper) {
        contactFormFooterWrapper.style.paddingTop = paddingTop;
      } else {
        nextSibling.style.paddingTop = paddingTop;
      }
    }

    element.classList.add("positioned");
    positionedBoxes.add(element);
  });
}

// Intersection Observer para posicionar cuando sea necesario
const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        positionCTABox(entry.target);
        observer.unobserve(entry.target); // Ya no necesitamos observar más
      }
    });
  },
  {
    rootMargin: "300px", // Posicionar 300px antes de que sea visible
    threshold: 0,
  },
);

// Observar todos los CTA boxes
ctaBoxes.forEach((ctaBox) => observer.observe(ctaBox));

// Posicionar los que ya están en viewport
if (document.readyState === "loading") {
  document.addEventListener(
    "DOMContentLoaded",
    () => {
      ctaBoxes.forEach((element) => {
        const rect = element.getBoundingClientRect();
        if (rect.top < window.innerHeight + 300) {
          positionCTABox(element);
        }
      });
    },
    { once: true },
  );
} else {
  ctaBoxes.forEach((element) => {
    const rect = element.getBoundingClientRect();
    if (rect.top < window.innerHeight + 300) {
      positionCTABox(element);
    }
  });
}

// Resize handler
let resizeTimer;
window.addEventListener(
  "resize",
  () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      // Re-posicionar todos los boxes visibles
      positionedBoxes.clear();
      ctaBoxes.forEach((element) => {
        const rect = element.getBoundingClientRect();
        if (rect.top < window.innerHeight + 300) {
          positionCTABox(element);
        }
      });
    }, 150);
  },
  { passive: true },
);
