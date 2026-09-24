// Render the WordPress.org icon and banner PNGs into .wordpress-org/.
// Run with the Playwright MCP tool browser_run_code_unsafe,
// filename: .claude/skills/generating-banner/render.js
// Then compress (see SKILL.md).
async (page) => {
	const root = 'file:///path/to/WordPress-Simple-SEO';
	const browser = page.context().browser();
	const out = [];

	for ( const scale of [ 1, 2 ] ) {
		const ctx = await browser.newContext( { viewport: { width: 772, height: 250 }, deviceScaleFactor: scale } );
		const p = await ctx.newPage();
		await p.goto( root + '/.claude/skills/generating-banner/banner.html' );
		await p.evaluate( () => document.fonts.ready );
		await p.waitForLoadState( 'networkidle' );
		const file = scale === 1 ? '.wordpress-org/banner-772x250.png' : '.wordpress-org/banner-1544x500.png';
		await p.locator( '.banner' ).screenshot( { path: file } );
		out.push( file );
		await ctx.close();
	}

	for ( const size of [ 128, 256 ] ) {
		const ctx = await browser.newContext( { viewport: { width: size, height: size } } );
		const p = await ctx.newPage();
		// Start on a file:// page: Chromium won't load a file:// image into about:blank.
		await p.goto( root + '/.claude/skills/generating-banner/banner.html' );
		await p.setContent( `<style>html,body{margin:0;background:transparent}</style><img src="${ root }/.wordpress-org/icon.svg" width="${ size }" height="${ size }" style="display:block">` );
		await p.locator( 'img' ).evaluate( ( img ) => img.complete || new Promise( ( r ) => ( img.onload = r ) ) );
		const file = `.wordpress-org/icon-${ size }x${ size }.png`;
		await p.screenshot( { path: file, omitBackground: true } );
		out.push( file );
		await ctx.close();
	}

	return out;
}
