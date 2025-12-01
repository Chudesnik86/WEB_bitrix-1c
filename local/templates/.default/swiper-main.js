function initSwiper(i,s,r) {
    new i.default(".swiper-main .swiper", {
        loop: !0,
        slidesPerView: "auto",
        modules: [r.Navigation, r.Pagination],
        navigation: {
            nextEl: ".swiper-main .swiper-button-next",
            prevEl: ".swiper-main .swiper-button-prev"
        },
        pagination: {el: ".swiper-main .swiper-pagination", type: "bullets", clickable: !0}
    })
}