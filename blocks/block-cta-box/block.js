const ctaBoxes = document.querySelectorAll(".cta-box.float");
const contactFormFooterWrapper = document.querySelector(
  ".contact-form-footer__wrapper",
);

let isPositioned = false;

// CRÍTICO: Establecer valores aproximados INMEDIATAMENTE (sincrónicamente)
function setApproximateValues() {
  ctaBoxes.forEach((element) => {
    const box = element.querySelector(".cta-box__box");
    if (!box) return;

    // Medición inicial rápida
    const boxHeight = box.offsetHeight || 0;
    if (boxHeight === 0) return;

    const approximateTop = Math.round(boxHeight * 0.55);

    // Establecer CSS variable INMEDIATAMENTE
    element.style.setProperty("--cta-approximate-top", `${approximateTop}px`);

    // Aplicar clase para activar los estilos CSS
    element.classList.add("cta-measured");
  });
}

function setBoxPosition() {
  const measurements = Array.from(ctaBoxes).map((element) => {
    const box = element.querySelector(".cta-box__box");
    return {
      element,
      box,
      boxHeight: box?.offsetHeight || 0,
      firstChild: element.firstElementChild,
      prevSibling: element.previousElementSibling,
      nextSibling: element.nextElementSibling,
    };
  });

  requestAnimationFrame(() => {
    measurements.forEach(
      ({ element, box, boxHeight, firstChild, prevSibling, nextSibling }) => {
        if (!box || boxHeight === 0) return;

        const topPosition = boxHeight * 0.55;
        const bottomPosition = boxHeight * 0.5;

        // Actualizar CSS variable con valor preciso
        element.style.setProperty("--cta-top-position", `${topPosition}px`);

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
      },
    );

    isPositioned = true;
  });
}

// PASO 1: Establecer valores aproximados INMEDIATAMENTE (sincrónicamente)
setApproximateValues();

// PASO 2: Refinar con valores precisos
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", setBoxPosition, { once: true });
} else {
  requestAnimationFrame(setBoxPosition);
}

// PASO 3: Recalcular en resize
let resizeTimer;
window.addEventListener(
  "resize",
  () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      if (isPositioned) {
        setApproximateValues(); // Actualizar aproximación
        setBoxPosition(); // Luego refinar
      }
    }, 150);
  },
  { passive: true },
);
