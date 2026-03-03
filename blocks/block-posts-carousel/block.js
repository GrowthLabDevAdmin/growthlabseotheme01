(() => {
  let postsCarousels = document.querySelectorAll(".posts-carousel__carousel");

  if (postsCarousels.length > 0) {
    postsCarousels.forEach((carousel) => {
      let carouselType = carousel.dataset.type;
      let splideElement = carousel.querySelector(".splide");

      if (splideElement) {
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
      }
    });
  }
})();
