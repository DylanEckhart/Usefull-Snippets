/*
Deze functie checkt of de header gescrold is en voegt de class 'scrolled' toe of verwijdert deze op basis van de scrollpositie.
Hierdoor kan je bijvoorbeeld de styling van de header aanpassen wanneer er gescrold wordt. 
Belangrijk is dat de header .header als class heeft en sticky is, zodat deze functie correct werkt.
*/
function checkHeaderScrolled() {
    const header = document.querySelector('.header');

    if (window.scrollY > 0) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
}

window.addEventListener('scroll', checkHeaderScrolled);
window.addEventListener('load', checkHeaderScrolled);