(() => {
  let locationsCarousel = document.querySelectorAll(
    ".locations .locations-cards__carousel",
  );

  if (locationsCarousel.length > 0) {
    locationsCarousel.forEach((carousel) => {
      let splideElement = carousel.querySelector(".splide");
      if (splideElement) {
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
      }
    });
  }
})();
