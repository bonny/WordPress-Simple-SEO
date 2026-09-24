// WordPress.org screenshots for Simple SEO. Run with the Playwright MCP tool
// browser_run_code_unsafe, filename: .claude/skills/visual-check/capture-screenshots.js
// after seed.php (see SKILL.md). Writes .wordpress-org/screenshot-1.png and -2.png
// uncompressed; run the pngquant + oxipng step afterwards.
async (page) => {
	const base = 'http://wordpress-php74.test:8299';
	const browser = page.context().browser();
	const results = {};

	// Shot 1: the fields on the Edit Page screen, logged in, 2x for sharp text.
	const admin = await browser.newContext( { viewport: { width: 1200, height: 800 }, deviceScaleFactor: 2 } );
	const a = await admin.newPage();
	await a.goto( base + '/?pagename=about-us' );
	const aboutId = await a.evaluate( () => ( document.body.className.match( /page-id-(\d+)/ ) || [] )[ 1 ] );
	await a.goto( base + '/wp-login.php' );
	await a.fill( '#user_login', 'admin' );
	await a.fill( '#user_pass', 'admin' );
	await Promise.all( [ a.waitForNavigation(), a.click( '#wp-submit' ) ] );
	await a.goto( base + '/wp-admin/post.php?action=edit&post=' + aboutId );
	await a.waitForLoadState( 'networkidle' );
	// Hide admin notices and the "Howdy" avatar noise; keep everything else stock.
	await a.addStyleTag( { content: '.notice, .update-nag, #screen-meta-links { display: none !important; }' } );
	const body = await a.locator( '#post-body' ).boundingBox();
	const editor = await a.locator( '#postdivrich' ).boundingBox();
	await a.screenshot( {
		path: '.wordpress-org/screenshot-1.png',
		clip: { x: body.x - 20, y: 50, width: body.width + 40, height: editor.y + 200 - 50 },
	} );
	results.shot1 = { aboutId, inputs: await a.locator( '#simple_seo_edit_wrapper input[type=text]' ).count() };
	await admin.close();

	// Shot 2: the front end, logged out, with a browser tab strip showing the real <title>.
	const visitor = await browser.newContext( { viewport: { width: 1200, height: 800 }, deviceScaleFactor: 2 } );
	const v = await visitor.newPage();
	await v.goto( base + '/?pagename=about-us' );
	await v.waitForLoadState( 'networkidle' );
	const title = await v.title();
	await v.evaluate( ( t ) => {
		const bar = document.createElement( 'div' );
		bar.style.cssText = 'position:relative;z-index:99999;background:#dee1e6;padding:8px 12px 0;font:13px/1 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;';
		const tab = document.createElement( 'div' );
		tab.style.cssText = 'display:inline-flex;align-items:center;gap:8px;background:#fff;border-radius:8px 8px 0 0;padding:9px 14px;max-width:560px;color:#202124;';
		tab.innerHTML = '<span style="width:14px;height:14px;border-radius:50%;background:#7a5230;flex:none"></span><span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></span>';
		tab.lastChild.textContent = t;
		bar.appendChild( tab );
		document.body.prepend( bar );
	}, title );
	const heading = await v.locator( 'h1' ).first().boundingBox();
	await v.screenshot( { path: '.wordpress-org/screenshot-2.png', clip: { x: 0, y: 0, width: 1200, height: heading.y + heading.height + 190 } } );
	results.shot2 = { title, nav: await v.locator( '.wp-block-navigation' ).first().innerText() };
	await visitor.close();

	return results;
}
