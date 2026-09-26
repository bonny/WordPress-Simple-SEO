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
import { createInterpolateElement, useState } from '@wordpress/element';
import {
	CheckboxControl,
	Flex,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

const { otherPlugin, simpleHistoryUrl, frontPageId } =
	window.simpleSeoEditor || {};

/**
 * One field: a checkbox that switches the value on or off, and the text.
 * Unticking keeps the text. Ticked means used.
 *
 * @param {Object}                                             props
 * @param {string}                                             props.label       Checkbox label.
 * @param {string}                                             props.inputLabel  Label of the text field, for screen readers.
 * @param {string}                                             props.help        Help text under the checkbox.
 * @param {string}                                             props.text        The text.
 * @param {boolean}                                            props.disabled    The stored "disabled" flag.
 * @param {(value: {text: string, disabled: boolean}) => void} props.onChange    Called with the new text and flag.
 * @param {boolean}                                            [props.multiline] A textarea instead of one line.
 */
function Field( {
	label,
	inputLabel,
	help,
	text,
	disabled,
	onChange,
	multiline,
} ) {
	// The meta can't tell "ticked but empty" (use the default) from never ticked,
	// so only that case lives in local state. Everything else follows the meta,
	// which keeps undo and redo right.
	const [ tickedEmpty, setTickedEmpty ] = useState( false );
	const on = ! disabled && ( text !== '' || tickedEmpty );

	const inputProps = {
		__nextHasNoMarginBottom: true,
		hideLabelFromVision: true,
		label: inputLabel,
		value: text,
		onChange: ( value ) => onChange( { text: value, disabled: ! on } ),
	};

	return (
		<Flex direction="column" gap={ 2 }>
			<CheckboxControl
				__nextHasNoMarginBottom
				label={ label }
				help={ help }
				checked={ on }
				onChange={ ( checked ) => {
					setTickedEmpty( checked );
					onChange( { text, disabled: ! checked } );
				} }
			/>
			{ multiline ? (
				<TextareaControl { ...inputProps } rows={ 2 } />
			) : (
				<TextControl { ...inputProps } __next40pxDefaultSize />
			) }
		</Flex>
	);
}

function SimpleSeoPanel() {
	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	);
	const postId = useSelect(
		( select ) => select( editorStore ).getCurrentPostId(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	// Post types without 'custom-fields' support have no meta in the REST API.
	if ( ! meta ) {
		return null;
	}

	// The Simple History tip only shows on posts that use the fields.
	const usesFields =
		( ! meta._simple_seo_title_disabled &&
			meta._simple_seo_title !== '' ) ||
		( ! meta._simple_seo_description_disabled &&
			meta._simple_seo_description !== '' ) ||
		!! meta._simple_seo_noindex;

	// Meta edits are merged, so pass only the changed keys.
	const update =
		( key ) =>
		( { text, disabled } ) =>
			setMeta( { [ key ]: text, [ `${ key }_disabled` ]: disabled } );

	return (
		<PluginDocumentSettingPanel
			name="simple-seo"
			title={ __( 'Simple SEO', 'simple-seo' ) }
		>
			<Flex direction="column" gap={ 4 }>
				{ otherPlugin && (
					<p className="components-base-control__help">
						{ sprintf(
							/* translators: %s: name of another SEO plugin, like Yoast SEO. */
							__(
								'%s is active and handles SEO, so these fields aren’t used.',
								'simple-seo'
							),
							otherPlugin
						) }
					</p>
				) }

				<Field
					label={ __( 'Use a custom SEO title', 'simple-seo' ) }
					inputLabel={ __( 'SEO title', 'simple-seo' ) }
					help={
						postId === frontPageId
							? __(
									'Used as the whole title of the front page.',
									'simple-seo'
								)
							: __(
									'The site name is added after it.',
									'simple-seo'
								)
					}
					text={ meta._simple_seo_title }
					disabled={ meta._simple_seo_title_disabled }
					onChange={ update( '_simple_seo_title' ) }
				/>

				<Field
					label={ __(
						'Use a custom meta description',
						'simple-seo'
					) }
					inputLabel={ __( 'Meta description', 'simple-seo' ) }
					help={ __(
						'Shown under the title in search results.',
						'simple-seo'
					) }
					text={ meta._simple_seo_description }
					disabled={ meta._simple_seo_description_disabled }
					onChange={ update( '_simple_seo_description' ) }
					multiline
				/>

				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __(
						'Discourage search engines from indexing this page',
						'simple-seo'
					) }
					help={ __(
						'It’s up to search engines to honor this request. Anyone with the link can still open the page.',
						'simple-seo'
					) }
					checked={ !! meta._simple_seo_noindex }
					onChange={ ( checked ) =>
						setMeta( { _simple_seo_noindex: checked } )
					}
				/>

				{ simpleHistoryUrl && usesFields && (
					<p className="components-base-control__help">
						{ createInterpolateElement(
							__(
								'Tip: <a>Simple History</a> logs every change to these fields.',
								'simple-seo'
							),
							// eslint-disable-next-line jsx-a11y/anchor-has-content -- The text comes from the translation.
							{ a: <a href={ simpleHistoryUrl } /> }
						) }
					</p>
				) }
			</Flex>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'simple-seo', { render: SimpleSeoPanel } );
