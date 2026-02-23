document.addEventListener("DOMContentLoaded", () => {
  if (document.querySelector(".selling-points__carousel")) {
    var sellingPointsCarousel = new Splide(
      ".selling-points__carousel .splide",
      {
        type: "loop",
        perPage: 1,
        perMove: 1,
        arrows: true,
        pagination: false,
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
      }
    );
    sellingPointsCarousel.mount();
  }
});
