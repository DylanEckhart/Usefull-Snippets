jQuery(function ($) {
	function updateSliderSettings() {
		const $slider = $('.image-slider .jet-engine-gallery-slider');

		if (!$slider.length) return;

		if ($slider.hasClass('slick-initialized')) {
			$slider.slick('slickSetOption', {
				dots: true,
				autoplay: true,
				pauseOnHover: true,
				autoplaySpeed: 3000,
				speed: 250,
				responsive: [
					{
						breakpoint: 1200,
						settings: {
							dots: true,
							slidesToShow: 2
						}
					},
					{
						breakpoint: 767,
						settings: {
							dots: true,
						slidesToShow: 1
					}
					}
				]
			}, true);
		} else {
			setTimeout(updateSliderSettings, 100);
		}
	}

	updateSliderSettings();
});