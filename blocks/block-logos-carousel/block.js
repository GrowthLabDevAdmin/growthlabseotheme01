(() => {
  const logosCarousels = document.querySelectorAll(
    ".logos-carousel__carousel .splide",
  );
  if (logosCarousels) {
    for (var i = 0; i < logosCarousels.length; i++) {
      const splideEl = logosCarousels[i];
      const slidesCount = splideEl.querySelectorAll('.splide__slide').length;
      const isSidebar = !!splideEl.closest('.sidebar');

      // determine perPage values for each breakpoint
      const perPageBase = 3;
      const perPageTablet = isSidebar ? 3 : 5;
      const perPageLdpi = isSidebar ? 3 : 7;

      // ensure perPage never exceeds the number of slides
      const effectiveBase = Math.min(perPageBase, slidesCount);
      const effectiveTablet = Math.min(perPageTablet, slidesCount);
      const effectiveLdpi = Math.min(perPageLdpi, slidesCount);


      const maxPerPage = Math.max(effectiveBase, effectiveTablet, effectiveLdpi);

      new Splide(splideEl, {
        type: slidesCount > maxPerPage ? 'loop' : 'slide',
        perPage: effectiveBase,
        perMove: effectiveBase,
        arrows: false,
        pagination: slidesCount > effectiveBase,
        accessibility: true,
        slideFocus: false,
        mediaQuery: "min",
        breakpoints: {
          [tablet]: {
            perPage: effectiveTablet,
            perMove: effectiveTablet,
          },
          [ldpi]: {
            perPage: effectiveLdpi,
            perMove: effectiveLdpi,
          },
        },
      }).mount();
    }
  }
})();
