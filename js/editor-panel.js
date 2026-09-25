/**
 * Simple SEO panel in the block editor's document sidebar.
 *
 * Reads and writes the registered post meta through the post entity, so the
 * values save with the post, like the core Excerpt and Discussion panels.
 */
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import {
	CheckboxControl,
	Flex,
	Notice,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

const { otherPlugin } = window.simpleSeoEditor || {};

/**
 * One field: a checkbox that switches the value on or off, and the text.
 * Unticking keeps the text. Ticked means used.
 *
 * @param {Object}                                             props
 * @param {string}                                             props.label       Checkbox label.
 * @param {string}                                             props.help        Help text below the field.
 * @param {string}                                             props.text        The text.
 * @param {boolean}                                            props.disabled    The stored "disabled" flag.
 * @param {(value: {text: string, disabled: boolean}) => void} props.onChange    Called with the new text and flag.
 * @param {boolean}                                            [props.multiline] A textarea instead of one line.
 */
function Field( { label, help, text, disabled, onChange, multiline } ) {
	// Ticking an empty field is allowed (it means "use the default"), but the
	// stored meta can't tell that apart from never ticked, so keep it locally.
	const [ on, setOn ] = useState( ! disabled && text !== '' );
	const Input = multiline ? TextareaControl : TextControl;

	return (
		<Flex direction="column" gap={ 2 }>
			<CheckboxControl
				__nextHasNoMarginBottom
				label={ label }
				checked={ on }
				onChange={ ( checked ) => {
					setOn( checked );
					onChange( { text, disabled: ! checked } );
				} }
			/>
			<Input
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				hideLabelFromVision
				label={ label }
				help={ help }
				value={ text }
				rows={ multiline ? 3 : undefined }
				onChange={ ( value ) =>
					onChange( { text: value, disabled: ! on } )
				}
			/>
		</Flex>
	);
}

function SimpleSeoPanel() {
	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	// Post types without 'custom-fields' support have no meta in the REST API.
	if ( ! meta || ! ( '_simple_seo_title' in meta ) ) {
		return null;
	}

	const update =
		( key ) =>
		( { text, disabled } ) =>
			setMeta( {
				...meta,
				[ key ]: text,
				[ `${ key }_disabled` ]: disabled,
			} );

	return (
		<PluginDocumentSettingPanel
			name="simple-seo"
			title={ __( 'SEO', 'simple-seo' ) }
		>
			<Flex direction="column" gap={ 4 }>
				{ otherPlugin && (
					<Notice status="info" isDismissible={ false }>
						{ sprintf(
							/* translators: %s: name of another SEO plugin, like Yoast SEO. */
							__(
								'%s is active, so it handles titles, descriptions and search engines, and these fields aren’t used.',
								'simple-seo'
							),
							otherPlugin
						) }
					</Notice>
				) }

				<Field
					label={ __( 'Use a custom SEO title', 'simple-seo' ) }
					help={ __(
						'Shown in search results and browser tabs instead of the post title. The site name is added after it.',
						'simple-seo'
					) }
					text={ meta._simple_seo_title }
					disabled={ meta._simple_seo_title_disabled }
					onChange={ update( '_simple_seo_title' ) }
				/>

				<Field
					label={ __(
						'Use a custom meta description',
						'simple-seo'
					) }
					help={ __(
						'Short summary shown under the title in search results. Without it, search engines pick text from the page.',
						'simple-seo'
					) }
					text={ meta._simple_seo_description }
					disabled={ meta._simple_seo_description_disabled }
					onChange={ update( '_simple_seo_description' ) }
					multiline
				/>

				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Hide from search engines', 'simple-seo' ) }
					help={ __(
						'Search engines won’t list this page. Anyone with the link can still open it.',
						'simple-seo'
					) }
					checked={ !! meta._simple_seo_noindex }
					onChange={ ( checked ) =>
						setMeta( { ...meta, _simple_seo_noindex: checked } )
					}
				/>
			</Flex>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'simple-seo', { render: SimpleSeoPanel } );
