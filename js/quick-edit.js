/**
 * Quick Edit in the posts and pages lists: fill the Simple SEO fields with the
 * row's values, which the SEO column keeps in a hidden element.
 */

const { inlineEditPost } = window;

if ( inlineEditPost ) {
	const edit = inlineEditPost.edit;

	inlineEditPost.edit = function ( id, ...args ) {
		edit.call( this, id, ...args );

		const postId = typeof id === 'object' ? this.getId( id ) : id;
		const data = document.querySelector(
			`#post-${ postId } .simple-seo-data`
		);
		const form = document.querySelector( `#edit-${ postId }` );

		if ( ! data || ! form ) {
			return;
		}

		const values = JSON.parse( data.dataset.values );

		Object.entries( values ).forEach( ( [ key, value ] ) => {
			const input = form.querySelector( `[name="simple_seo[${ key }]"]` );

			if ( ! input ) {
				return;
			}

			if ( input.type === 'checkbox' ) {
				input.checked = !! value;
			} else {
				input.value = value;
			}
		} );
	};
}
