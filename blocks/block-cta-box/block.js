const ctaBoxes = document.querySelectorAll(".cta-box.float");
const contactFormFooterWrapper = document.querySelector(
  ".contact-form-footer__wrapper"
);

let isPositioned = false; // Prevent multiple executions

function setBoxPosition() {
  // Batch all measurements (single reflow)
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

  // Batch all style updates (single repaint)
  requestAnimationFrame(() => {
    measurements.forEach(
      ({ element, box, boxHeight, firstChild, prevSibling, nextSibling }) => {
        if (!box || boxHeight === 0) return;

        const topPosition = boxHeight * 0.55;
        const bottomPosition = boxHeight * 0.50; // Adjusted to 50% for better coverage

        if (firstChild) {
          firstChild.style.cssText += `margin-top:${-topPosition}px;padding-bottom:${topPosition}px;`;
        }

        if (prevSibling) {
          prevSibling.style.cssText += `padding-bottom:${
            topPosition + 70
          }px;border-bottom:8px solid rgb(var(--tertiary));`;
        }

        if (nextSibling) {
          const isFooter = nextSibling.classList.contains("site-footer");
          const paddingTop = `${bottomPosition + 60}px`; // +8px for the border

          nextSibling.style.marginTop = `${-boxHeight - 30}px`; // Adjusted to compensate for 8px border

          if (isFooter && contactFormFooterWrapper) {
            contactFormFooterWrapper.style.paddingTop = paddingTop;
          } else {
            nextSibling.style.paddingTop = paddingTop;
          }
        }

        // Mark as positioned
        element.classList.add("positioned");
      }
    );

    isPositioned = true;
  });
}

// CRITICAL: Only run ONCE on initial load
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", setBoxPosition, { once: true });
} else {
  // If DOM already loaded, wait for next frame to ensure styles are applied
  requestAnimationFrame(setBoxPosition);
}

// Debounced resize handler - only if already positioned
let resizeTimer;
window.addEventListener(
  "resize",
  () => {
    if (!isPositioned) return; // Don't run if initial positioning hasn't happened

    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(setBoxPosition, 150);
  },
  { passive: true }
);
