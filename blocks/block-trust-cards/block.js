if (!window.loadSplide) {
  window.loadSplide = (callback) => {
    if (typeof Splide !== "undefined") return callback();
    if (window.__splideLoading) {
      window.__splideCallbacks = window.__splideCallbacks || [];
      window.__splideCallbacks.push(callback);
      return;
    }
    window.__splideLoading = true;
    window.__splideCallbacks = [callback];
    if (!window.splideData || !window.splideData.url) {
      console.error("Splide data not available");
      return;
    }
    const script = document.createElement("script");
    script.src = splideData.url;
    script.onload = () => {
      const checkSplide = (attempts = 0) => {
        if (typeof Splide !== "undefined") {
          window.__splideCallbacks.forEach((fn) => fn());
          window.__splideCallbacks = [];
          return;
        }
        if (attempts < 10) {
          setTimeout(() => checkSplide(attempts + 1), 100);
        } else {
          console.error("Splide failed to load after retries");
        }
      };
      checkSplide();
    };
    script.onerror = () => {
      console.error("Failed to load Splide script");
    };
    document.head.appendChild(script);
  };
}

(() => {
  if (document.querySelector(".trust-cards__carousel")) {
    loadSplide(() => {
      var trustCardsCarousel = new Splide(".trust-cards__carousel .splide", {
        type: "loop",
        perPage: 1,
        perMove: 1,
        arrows: true,
        pagination: false,
        accessibility: true,
        slideFocus: false,
        mediaQuery: "min",
        breakpoints: {
          [tablet]: {
            perPage: 3,
          },
          [ldpi]: {
            destroy: true,
            arrows: false,
          },
        },
      });
      trustCardsCarousel.mount();
    });
  }
})();
