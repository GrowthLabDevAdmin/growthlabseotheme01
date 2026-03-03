(() => {
  const logosCarousels = document.querySelectorAll(
    ".logos-carousel__carousel .splide",
  );
  if (logosCarousels) {
    for (var i = 0; i < logosCarousels.length; i++) {
      new Splide(logosCarousels[i], {
        type: "loop",
        perPage: 3,
        perMove: 3,
        arrows: false,
        pagination: true,
        accessibility: true,
        slideFocus: false,
        mediaQuery: "min",
        breakpoints: {
          [tablet]: {
            perPage: logosCarousels[i].closest(".sidebar") ? 3 : 5,
            perMove: logosCarousels[i].closest(".sidebar") ? 3 : 5,
          },
          [ldpi]: {
            perPage: logosCarousels[i].closest(".sidebar") ? 3 : 7,
            perMove: logosCarousels[i].closest(".sidebar") ? 3 : 7,
          },
        },
      }).mount();
    }
  }
})();
