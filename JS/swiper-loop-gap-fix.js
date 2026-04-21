// Elementor & JetEngine - Swiper Loop Gap Fix
// Deze snippet zoekt automatisch naar alle actieve Swiper-sliders op je website die de 'loop' functie gebruiken. Het voegt extra gekloonde slides toe en herlaadt de loop-functie, zodat de lege witte ruimte aan de rechterkant wordt opgevuld zonder dat je de originele broncode van de sliders hoeft aan te passen.

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const swiperElements = document.querySelectorAll('.swiper, .swiper-container');
        
        swiperElements.forEach(function(element) {
            const swiperInstance = element.swiper;
            
            if (swiperInstance && swiperInstance.params.loop) {
                swiperInstance.params.loopAdditionalSlides = 10;
                swiperInstance.params.loopedSlides = 10;
                
                swiperInstance.loopDestroy();
                swiperInstance.loopCreate();
                swiperInstance.update();
            }
        });
    }, 500);
});
