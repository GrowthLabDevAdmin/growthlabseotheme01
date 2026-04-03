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
  let postsCarousels = document.querySelectorAll(".posts-carousel__carousel");

  if (postsCarousels.length > 0) {
    postsCarousels.forEach((carousel) => {
      let carouselType = carousel.dataset.type;
      let splideElement = carousel.querySelector(".splide");

      if (splideElement) {
        loadSplide(() => {
          new Splide(splideElement, {
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
                perPage: splideElement.closest(".sidebar")
                  ? 1
                  : carouselType === "case-result" ||
                      carouselType === "testimonial" ||
                      carouselType === "post"
                    ? 2
                    : 3,
              },
              [ldpi]: {
                perPage: splideElement.closest(".sidebar")
                  ? 1
                  : carouselType === "case-result"
                    ? 3
                    : carouselType === "testimonial" || carouselType === "post"
                      ? 2
                      : 4,
              },
            },
          }).mount();
        });
      }
    });
  }
})();
