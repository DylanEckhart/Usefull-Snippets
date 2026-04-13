// JetSmartFilters - Paginering onnodige ruimte weghalen
// Deze code zorgt ervoor dat de paginering-widget van JetSmartFilters automatisch wordt verborgen als er geen pagina's zijn om weer te geven (bijvoorbeeld wanneer alle resultaten op één pagina passen).

jQuery(document).ready(function($) {
    function checkEmptyPagination() {
        $('.jet-smart-filters-pagination').each(function() {
            var $this = $(this);
            var $widget = $this.closest('.elementor-widget-jet-smart-filters-pagination');
            var $targetToHide = $widget.length > 0 ? $widget : $this;
            var pageCount = $this.find('.jet-filters-pagination__item').length;

            if (pageCount < 2) {
                $targetToHide.hide();
            } else {
                $targetToHide.show();
            }
        });
    }

    checkEmptyPagination();

    $(document).on('jet-filter-custom-content-render', function() {
        setTimeout(checkEmptyPagination, 50);
    });
});
