// GSAP - Scroll Text Animation
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://unpkg.com/gsap@3/dist/ScrollTrigger.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/split-type@0.3.4/umd/index.min.js"></script>

function textScroller() {
	gsap.registerPlugin( ScrollTrigger );

	const wrappers = document.querySelectorAll( '.tekst-scroll' );
	const triggers = document.querySelectorAll( '.tekst-scroll-trigger' );

	wrappers.forEach( ( wrapper, index ) => {
		const text    = wrapper.querySelector( 'div' );
		const trigger = triggers[ index ];

		if ( ! text || ! trigger ) return;

		const color = '#161716';
		const split = new SplitType( text, {
			types: 'words, chars'
		} );

		gsap.set( split.chars, {
			opacity: 0.2
		} );

		gsap.timeline( {
			scrollTrigger: {
				trigger: trigger,
				start: 'top 80%',
				end: 'bottom 60%',
				scrub: 0.4
			}
		} ).to( split.chars, {
			opacity: 1,
			color: color,
			stagger: 0.05
		} );
	} );
}

window.addEventListener( 'load', textScroller );