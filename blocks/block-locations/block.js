document.addEventListener("DOMContentLoaded", () => {
  let locationsCarousel = document.querySelectorAll(
    ".locations .locations-cards__carousel"
  );

  if (locationsCarousel.length > 0) {
    locationsCarousel.forEach((carousel) => {
      let splideElement = carousel.querySelector(".splide");
      if (splideElement) {
        let locationsCarouselInstance = new Splide(splideElement, {
          type: "loop",
          perMove: 1,
          perPage: 4,
          arrows: true,
          pagination: false,
          breakpoints: {
            [tablet]: {
              perPage: 1,
            },
            [ldpi]: {
              perPage: 2,
            },
          },
        });
        locationsCarouselInstance.mount();
      }
    });
  }
});
