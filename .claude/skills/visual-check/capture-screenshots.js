/**
 * WordPress.org screenshots for Simple SEO 1.0. Run with the Playwright MCP tool
 * browser_run_code_unsafe, filename: .claude/skills/visual-check/capture-screenshots.js,
 * after seeding both sites with seed.php (see SKILL.md). Edit the two IDs first.
 *
 * 1. Block editor SEO panel (wp-env dev site, localhost:8315, admin / password)
 * 2. Classic Editor box (Classic Editor site, :8314, admin / admin)
 * 3. Settings → General, Simple SEO section (wp-env)
 * 4. Simple History entry for an SEO change made in shot 1 (wp-env)
 *
 * Writes .wordpress-org/screenshot-1.png … -4.png, uncompressed. Compress after.
 */
async ( page ) => {
	const WP_ENV = 'http://localhost:8315';
	const CLASSIC = 'http://wp-playground-classiceditor.test:8314';
	const WP_ENV_PAGE = 27; // "About us" from seed.php on wp-env.
	const CLASSIC_PAGE = 63; // "About us" from seed.php on the Classic Editor site.

	const context = await page
		.context()
		.browser()
		.newContext( {
			viewport: { width: 1280, height: 1100 },
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

	// 1. Block editor.
	await login( WP_ENV, 'admin', 'password' );
	await p.goto( `${ WP_ENV }/wp-admin/post.php?post=${ WP_ENV_PAGE }&action=edit` );
	await p.waitForSelector( '.editor-header' );
	await p.waitForTimeout( 2000 );
	// No welcome guide, and the SEO panel open (plugin panels start collapsed).
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
	// A change for the Simple History shot.
	await p
		.getByRole( 'textbox', { name: 'Meta description' } )
		.fill( 'Small batch coffee, roasted to order in a shed in Stockholm since 1998.' );
	await p.getByRole( 'button', { name: 'Save', exact: true } ).click();
	await p.waitForTimeout( 2500 );
	await p.evaluate( () => document.activeElement?.blur() ); // No focus ring on Save.
	await p.evaluate( () => {
		const title = [ ...document.querySelectorAll( '.components-panel__body-title' ) ].find(
			( e ) => e.textContent === 'SEO'
		);
		title.scrollIntoView( { block: 'start' } );
		// Just below the sidebar's sticky tabs.
		document.querySelector( '.interface-complementary-area' ).scrollBy( 0, -72 );
		document.querySelector( '.components-snackbar-list' )?.remove();
	} );
	await p.waitForTimeout( 300 );
	await p.screenshot( { path: '.wordpress-org/screenshot-1.png' } );

	// 3. Settings → General.
	await p.goto( `${ WP_ENV }/wp-admin/options-general.php` );
	const heading = p.locator( 'h2', { hasText: 'Simple SEO' } );
	const help = p.locator( '.simple-seo-share-image + .description, td:has(.simple-seo-share-image) .description' ).first();
	await heading.scrollIntoViewIfNeeded();
	const top = ( await heading.boundingBox() ).y - 16;
	const bottom = ( await help.boundingBox() ).y + ( await help.boundingBox() ).height + 16;
	const left = ( await heading.boundingBox() ).x - 16;
	await p.screenshot( {
		path: '.wordpress-org/screenshot-3.png',
		clip: { x: left, y: top, width: 1000, height: bottom - top },
	} );

	// 4. Simple History.
	await p.goto( `${ WP_ENV }/wp-admin/admin.php?page=simple_history_admin_menu_page` );
	const entry = p.locator( 'li', { hasText: 'Updated the SEO for "About us"' } ).first();
	await entry.waitFor();
	await p.waitForTimeout( 800 );
	// The li's top edge sits under the "Today" date label, so start below it.
	const box = await entry.boundingBox();
	await p.screenshot( {
		path: '.wordpress-org/screenshot-4.png',
		clip: { x: box.x, y: box.y + 24, width: box.width, height: box.height - 24 },
	} );

	// 2. Classic Editor box.
	await login( CLASSIC, 'admin', 'admin' );
	await p.setViewportSize( { width: 1280, height: 1120 } );
	await p.goto( `${ CLASSIC }/wp-admin/post.php?post=${ CLASSIC_PAGE }&action=edit` );
	await p.waitForSelector( '#simple-seo' );
	await p.waitForTimeout( 800 );
	await p.screenshot( { path: '.wordpress-org/screenshot-2.png' } );

	await context.close();
	return 'ok';
}
