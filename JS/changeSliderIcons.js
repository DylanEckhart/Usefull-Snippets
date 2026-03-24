// Deze code past de iconen van de next en previous knoppen aan naar Font Awesome pijlen in plaats van de standaard JetSlider iconen.
document.addEventListener('DOMContentLoaded', function() {
    changePrevNext();
});

function changePrevNext() {
    const prevImageUrl = '/wp-content/uploads/prev-arrow.svg';
    const interval = setInterval(() => {
        const prevButtons = document.querySelectorAll('.jet-listing-grid__slider-icon.prev-arrow.slick-arrow');
        const nextButtons = document.querySelectorAll('.jet-listing-grid__slider-icon.next-arrow.slick-arrow');

        if (prevButtons.length > 0 && nextButtons.length > 0) {
            prevButtons.forEach(button => {
                button.innerHTML = `<img src="${prevImageUrl}" alt="Vorige" style="width: 17px; height: 13px;">`;
            });

            nextButtons.forEach(button => {
                button.innerHTML = `<img src="${prevImageUrl}" alt="Volgende" style="width: 17px; height: 13px;">`;
            });
            clearInterval(interval);
        }
    }, 200);
}