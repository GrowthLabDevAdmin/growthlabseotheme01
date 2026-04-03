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
  let locationsCarousel = document.querySelectorAll(
    ".locations .locations-cards__carousel",
  );

  if (locationsCarousel.length > 0) {
    locationsCarousel.forEach((carousel) => {
      let splideElement = carousel.querySelector(".splide");
      if (splideElement) {
        loadSplide(() => {
          let locationsCarouselInstance = new Splide(splideElement, {
            type: "loop",
            perMove: 1,
            perPage: 1,
            arrows: true,
            pagination: false,
            accessibility: true,
            slideFocus: false,
            mediaQuery: "min",
            breakpoints: {
              [tablet]: {
                perPage: splideElement.closest(".sidebar") ? 1 : 2,
              },
              [ldpi]: {
                perPage: splideElement.closest(".sidebar") ? 1 : 4,
              },
            },
          });
          locationsCarouselInstance.mount();
        });
      }
    });
  }
})();
