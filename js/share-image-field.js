/**
 * Settings → General, Simple SEO section: pick the default share image from the
 * media library, like core's Site Icon field.
 */

document.querySelectorAll( '.simple-seo-share-image' ).forEach( ( field ) => {
	const input = field.querySelector( 'input[type="hidden"]' );
	const preview = field.querySelector( 'img' );
	const choose = field.querySelector( '.simple-seo-share-image-choose' );
	const remove = field.querySelector( '.simple-seo-share-image-remove' );
	let frame;

	const show = ( id, url ) => {
		input.value = id;
		preview.src = url;
		preview.hidden = ! url;
		remove.hidden = ! url;
		choose.textContent = url
			? choose.dataset.change
			: choose.dataset.choose;
	};

	choose.addEventListener( 'click', () => {
		frame ??= wp.media( {
			title: choose.dataset.choose,
			library: { type: 'image' },
			button: { text: choose.dataset.choose },
			multiple: false,
		} );

		frame.off( 'select' ).on( 'select', () => {
			const image = frame.state().get( 'selection' ).first().toJSON();
			show( image.id, image.sizes?.medium?.url ?? image.url );
		} );

		frame.open();
	} );

	remove.addEventListener( 'click', () => show( '', '' ) );
} );
