/**
 * ESLint flat config: the @wordpress/scripts default, plus telling the import
 * resolver that the @wordpress/* packages come from WordPress at runtime (they
 * are externalized in the build, not installed). Same as CMS Tree Page View.
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...defaultConfig,
	{
		settings: {
			'import/core-modules': [
				'@wordpress/components',
				'@wordpress/core-data',
				'@wordpress/data',
				'@wordpress/editor',
				'@wordpress/element',
				'@wordpress/i18n',
				'@wordpress/plugins',
			],
		},
	},
];
