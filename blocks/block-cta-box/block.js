const ctaBoxes = document.querySelectorAll(".cta-box.float");
const contactFormFooterWrapper = document.querySelector(
  ".contact-form-footer__wrapper",
);

const positionedBoxes = new WeakSet();

function positionCTABox(element) {
  if (positionedBoxes.has(element)) return;

  const box = element.querySelector(".cta-box__box");
  if (!box) return;

  // FASE DE LECTURA — todo junto antes de tocar el DOM
  const boxHeight = box.offsetHeight || 0;
  if (boxHeight === 0) return;

  const topPosition = boxHeight * 0.55;
  const bottomPosition = boxHeight * 0.5;

  const firstChild = element.firstElementChild;
  const prevSibling = element.previousElementSibling;
  const nextSibling = element.nextElementSibling;
  const isFooter = nextSibling?.classList.contains("site-footer");

  // FASE DE ESCRITURA — todo dentro del rAF
  requestAnimationFrame(() => {
    element.style.setProperty("--cta-approximate-top", `${topPosition}px`);
    element.classList.add("cta-measured");

    if (firstChild) {
      firstChild.style.cssText += `margin-top:${-topPosition}px;padding-bottom:${topPosition}px;`;
    }

    if (prevSibling) {
      prevSibling.style.cssText += `padding-bottom:${topPosition + 73}px;border-bottom:8px solid rgb(var(--tertiary));`;
    }

    if (nextSibling) {
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

const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        positionCTABox(entry.target);
        observer.unobserve(entry.target);
      }
    });
  },
  {
    rootMargin: "300px",
    threshold: 0,
  },
);

ctaBoxes.forEach((ctaBox) => observer.observe(ctaBox));

function checkVisible() {
  // FASE DE LECTURA — recolectar todos los rects antes de posicionar
  const visible = [];
  ctaBoxes.forEach((element) => {
    const rect = element.getBoundingClientRect();
    if (rect.top < window.innerHeight + 300) {
      visible.push(element);
    }
  });

  // FASE DE ESCRITURA — posicionar después de leer todo
  visible.forEach((element) => positionCTABox(element));
}

if (document.readyState === "loading") {
  window.addEventListener("load", checkVisible, { once: true });
} else {
  checkVisible();
}

let resizeTimer;
window.addEventListener(
  "resize",
  () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      // FASE DE LECTURA primero
      const visible = [];
      ctaBoxes.forEach((element) => {
        const rect = element.getBoundingClientRect();
        if (rect.top < window.innerHeight + 300) {
          visible.push(element);
        }
      });

      // FASE DE ESCRITURA después
      positionedBoxes.clear();
      visible.forEach((element) => positionCTABox(element));
    }, 150);
  },
  { passive: true },
);
