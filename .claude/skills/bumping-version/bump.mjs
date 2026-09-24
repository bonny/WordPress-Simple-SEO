#!/usr/bin/env node
// Bump the Simple SEO version everywhere it lives, in one go.
// Usage: node .claude/skills/bumping-version/bump.mjs patch|minor|major|X.Y.Z
// The current version is read from the "Version:" header in simple-seo.php.
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '../../..' );
const arg = process.argv[ 2 ];
const main = readFileSync( join( root, 'simple-seo.php' ), 'utf8' );
const current = main.match( /^ \* Version: (\d+\.\d+\.\d+)$/m )?.[ 1 ];

if ( ! current || ! arg ) {
	console.error( 'Usage: bump.mjs patch|minor|major|X.Y.Z (current: ' + current + ')' );
	process.exit( 1 );
}

let [ major, minor, patch ] = current.split( '.' ).map( Number );
let next;
if ( arg === 'patch' ) next = `${ major }.${ minor }.${ patch + 1 }`;
else if ( arg === 'minor' ) next = `${ major }.${ minor + 1 }.0`;
else if ( arg === 'major' ) next = `${ major + 1 }.0.0`;
else if ( /^\d+\.\d+\.\d+$/.test( arg ) ) next = arg;
else {
	console.error( 'Not a version or patch|minor|major: ' + arg );
	process.exit( 1 );
}

// Every place the version lives. Add new ones here. Each pattern has exactly two
// groups, the text before and after the version (the second may be empty).
const edits = [
	[ 'simple-seo.php', /^( \* Version: )\d+\.\d+\.\d+()$/m ],
	[ 'simple-seo.php', /^(define\( 'SIMPLE_SEO_VERSION', ')\d+\.\d+\.\d+(' \);)$/m ],
	[ 'readme.txt', /^(Stable tag: )\d+\.\d+\.\d+()$/m ],
];

const files = {};
for ( const [ file, re ] of edits ) {
	files[ file ] ??= readFileSync( join( root, file ), 'utf8' );
	if ( ! re.test( files[ file ] ) ) {
		console.error( `Pattern not found in ${ file }: ${ re }` );
		process.exit( 1 );
	}
	files[ file ] = files[ file ].replace( re, ( m, before, after ) => before + next + after );
}
for ( const [ file, content ] of Object.entries( files ) ) {
	writeFileSync( join( root, file ), content );
}
console.log( `${ current } -> ${ next }` );
