/**
 * WordPress.org screenshots for Simple SEO 1.0. Run with the Playwright MCP tool
 * browser_run_code_unsafe, filename: .claude/skills/visual-check/capture-screenshots.js,
 * after seeding both sites with seed.php (see SKILL.md). Edit the two IDs first.
 *
 * All six at 2x and 1120 CSS px wide (the admin screens cropped right of the admin menu,
 * the block editor in a 1120 px window), so they show at the same zoom on WordPress.org.
 *
 * 1. Block editor "Simple SEO" panel (wp-env dev site, localhost:8315, admin / password)
 * 2. Pages list: the SEO column and Quick Edit (wp-env)
 * 3. Posts list: the SEO column (wp-env)
 * 4. Classic Editor box (Classic Editor site, :8314, admin / admin)
 * 5. Settings → General, Simple SEO section (wp-env)
 * 6. Simple History entry for the title and description change made in shot 1 (wp-env)
 *
 * Writes .wordpress-org/screenshot-1.png … -6.png, uncompressed. Compress after.
 */
async ( page ) => {
	const WP_ENV = 'http://localhost:8315';
	const CLASSIC = 'http://wp-playground-classiceditor.test:8314';
	const WP_ENV_PAGE = 241; // "About us" from seed.php on wp-env.
	const WP_ENV_QUICK_EDIT = 238; // "Our coffees", opened in Quick Edit in shot 2.
	const CLASSIC_PAGE = 130; // "About us" from seed.php on the Classic Editor site.
	const X = 160; // Right of the admin menu.
	const WIDTH = 1120;

	const context = await page
		.context()
		.browser()
		.newContext( {
			viewport: { width: WIDTH, height: 1200 }, // The block editor has no admin menu; tall enough for the whole panel.
			deviceScaleFactor: 2,
		} );
	const p = await context.newPage();

	const login = async ( base, user, pass ) => {
		await p.goto( `${ base }/wp-login.php` );
		await p.fill( '#user_login', user );
		await p.fill( '#user_pass', pass );
		await p.click( '#wp-submit' );
		await p.waitForLoadState( 'networkidle' );
	};

	// A band of the screen, full crop width.
	const band = ( path, top, bottom ) =>
		p.screenshot( {
			path,
			clip: { x: X, y: Math.max( 0, top ), width: WIDTH, height: bottom - Math.max( 0, top ) },
		} );

	// 1. Block editor.
	await login( WP_ENV, 'admin', 'password' );
	await p.goto( `${ WP_ENV }/wp-admin/post.php?post=${ WP_ENV_PAGE }&action=edit` );
	await p.waitForSelector( '.editor-header' );
	await p.waitForTimeout( 2000 );
	// No welcome guide, and the panel open (plugin panels start collapsed).
	await p.evaluate( () => {
		wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
		const panel = 'simple-seo/simple-seo';
		if ( ! wp.data.select( 'core/editor' ).isEditorPanelOpened( panel ) ) {
			wp.data.dispatch( 'core/editor' ).toggleEditorPanelOpened( panel );
		}
	} );
	const close = p.locator( '.components-modal__screen-overlay button[aria-label="Close"]' );
	if ( await close.count() ) {
		await close.first().click();
	}
	await p.waitForSelector( '.components-modal__screen-overlay', { state: 'detached' } );
	const tab = p.getByRole( 'tab', { name: 'Page' } );
	if ( await tab.count() ) {
		await tab.click();
	}
	await p.waitForTimeout( 500 );
	// A change to the title and description, for the Simple History shot.
	await p.getByRole( 'textbox', { name: 'SEO title' } ).fill( 'Our story – coffee by the water' );
	await p
		.getByRole( 'textbox', { name: 'Meta description' } )
		.fill( 'Small batch coffee, roasted to order by the water in Nacka since 1998.' );
	await p.getByRole( 'button', { name: 'Save', exact: true } ).click();
	await p.waitForTimeout( 2500 );
	await p.evaluate( () => document.activeElement?.blur() ); // No focus ring on Save.
	await p.evaluate( () => {
		const title = [ ...document.querySelectorAll( '.components-panel__body-title' ) ].find(
			( e ) => e.textContent === 'Simple SEO'
		);
		title.scrollIntoView( { block: 'start' } );
		// Just below the sidebar's sticky tabs.
		document.querySelector( '.interface-complementary-area' ).scrollBy( 0, -72 );
		document.querySelector( '.components-snackbar-list' )?.remove();
	} );
	await p.waitForTimeout( 300 );
	await p.screenshot( { path: '.wordpress-org/screenshot-1.png' } );
	await p.setViewportSize( { width: X + WIDTH, height: 1100 } );

	// 2. Pages list with Quick Edit open on "Our coffees".
	await p.goto( `${ WP_ENV }/wp-admin/edit.php?post_type=page&orderby=menu_order&order=asc` );
	const row = p.locator( `#post-${ WP_ENV_QUICK_EDIT }` );
	await row.hover();
	await row.locator( 'button.editinline' ).click();
	await p.locator( `#edit-${ WP_ENV_QUICK_EDIT }` ).waitFor();
	await p.mouse.move( 0, 0 );
	// Down to the page with search engines discouraged; the rest of the list adds nothing.
	await p.setViewportSize( { width: X + WIDTH, height: 2000 } );
	const table = await p.locator( '.wp-list-table' ).boundingBox();
	const last = await p.locator( '#the-list tr.iedit', { hasText: 'Thanks for your order' } ).boundingBox();
	await band( '.wordpress-org/screenshot-2.png', table.y - 1, last.y + last.height );
	await p.setViewportSize( { width: X + WIDTH, height: 1100 } );
	await p.locator( `#edit-${ WP_ENV_QUICK_EDIT } button.cancel` ).click();

	// 3. Posts list.
	await p.setViewportSize( { width: X + WIDTH, height: 2000 } );
	await p.goto( `${ WP_ENV }/wp-admin/edit.php` );
	await p.mouse.move( 0, 0 );
	const posts = await p.locator( '.wp-list-table' ).boundingBox();
	const rows = await p.locator( '#the-list' ).boundingBox(); // Without the repeated header at the bottom.
	await band( '.wordpress-org/screenshot-3.png', posts.y - 1, rows.y + rows.height );
	await p.setViewportSize( { width: X + WIDTH, height: 1100 } );

	// 5. Settings → General.
	await p.goto( `${ WP_ENV }/wp-admin/options-general.php` );
	const heading = p.locator( 'h2', { hasText: 'Simple SEO' } );
	await heading.scrollIntoViewIfNeeded();
	const help = p.locator( 'td:has(.simple-seo-share-image) .description' ).first();
	const top = ( await heading.boundingBox() ).y - 16;
	const helpBox = await help.boundingBox();
	await band( '.wordpress-org/screenshot-5.png', top, helpBox.y + helpBox.height + 16 );

	// 6. Simple History.
	await p.goto( `${ WP_ENV }/wp-admin/admin.php?page=simple_history_admin_menu_page` );
	const entry = p.locator( 'li', { hasText: 'Updated the SEO for "About us"' } ).first();
	await entry.waitFor();
	await p.waitForTimeout( 800 );
	await entry.scrollIntoViewIfNeeded();
	const box = await entry.boundingBox();
	// Just the entry: the li's top edge sits under the "Today" date label, and Simple History's
	// own sidebar (a digest promo) is to the right.
	await p.screenshot( {
		path: '.wordpress-org/screenshot-6.png',
		clip: { x: box.x, y: box.y + 16, width: box.width, height: box.height - 8 },
	} );

	// 4. Classic Editor box.
	await login( CLASSIC, 'admin', 'admin' );
	await p.setViewportSize( { width: X + WIDTH, height: 1160 } );
	await p.goto( `${ CLASSIC }/wp-admin/post.php?post=${ CLASSIC_PAGE }&action=edit` );
	await p.waitForSelector( '#simple-seo' );
	// The company's domain instead of the test site's (display only).
	await p.evaluate( () => {
		const link = document.querySelector( '#sample-permalink a' );
		link.innerHTML = link.innerHTML.replace( /^https?:\/\/[^/]+/, 'https://tallvikcoffee.se' );
	} );
	await p.waitForTimeout( 800 );
	const metaBox = await p.locator( '#simple-seo' ).boundingBox();
	await band( '.wordpress-org/screenshot-4.png', 40, metaBox.y + metaBox.height + 16 );

	await context.close();
	return 'ok';
}
