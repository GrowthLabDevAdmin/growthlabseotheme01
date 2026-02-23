document.addEventListener("DOMContentLoaded", () => {
  if (document.querySelector(".trust-cards__carousel")) {
    var trustCardsCarousel = new Splide(".trust-cards__carousel .splide", {
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
    });
    trustCardsCarousel.mount();
  }
});
