// Elementor - Dienst parameter aan URL
// Deze snippet leest de 'dienst'-parameters uit de URL en vinkt bij het laden van de pagina automatisch de overeenkomende checkboxes in een formulier aan.

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.has('dienst')) {
        const dienstenString = urlParams.get('dienst');
        const geselecteerdeDiensten = dienstenString.split('&');
        const checkboxes = document.querySelectorAll('input[name="form_fields[diensten][]"]');

        checkboxes.forEach(checkbox => {
            if (geselecteerdeDiensten.includes(checkbox.value)) {
                checkbox.checked = true;
            }
        });
    }
});
