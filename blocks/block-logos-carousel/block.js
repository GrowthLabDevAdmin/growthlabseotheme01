document.addEventListener("DOMContentLoaded", () => {
  const logosCarousels = document.querySelectorAll(
    ".logos-carousel__carousel .splide"
  );
  if (logosCarousels) {
    for (var i = 0; i < logosCarousels.length; i++) {
      new Splide(logosCarousels[i], {
        type: "loop",
        perPage: 7,
        perMove: 7,
        arrows: false,
        pagination: true,
        breakpoints: {
          [tablet]: {
            perPage: 3,
            perMove: 3,
          },
          [ldpi]: {
            perPage: 5,
            perMove: 5,
          },
        },
      }).mount();
    }
  }
});
