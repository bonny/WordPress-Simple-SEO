/**
 * Simple SEO panel in the block editor's document sidebar.
 *
 * Reads and writes the registered post meta through the post entity, so the
 * values save with the post, like the core Excerpt and Discussion panels.
 * A text field is used when it isn't empty.
 */
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { createInterpolateElement } from '@wordpress/element';
import {
	CheckboxControl,
	ExternalLink,
	Flex,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

const { otherPlugin, simpleHistoryUrl, frontPageId, faqUrl } =
	window.simpleSeoEditor || {};

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
		meta._simple_seo_title.trim() !== '' ||
		meta._simple_seo_description.trim() !== '' ||
		!! meta._simple_seo_noindex;

	// Meta edits are merged, so pass only the changed key.
	const update = ( key ) => ( value ) => setMeta( { [ key ]: value } );

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

				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'SEO title', 'simple-seo' ) }
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
					value={ meta._simple_seo_title }
					onChange={ update( '_simple_seo_title' ) }
				/>

				<TextareaControl
					__nextHasNoMarginBottom
					className="simple-seo-autogrow"
					rows={ 2 }
					label={ __( 'Meta description', 'simple-seo' ) }
					help={ __(
						'Often shown under the title in search results.',
						'simple-seo'
					) }
					value={ meta._simple_seo_description }
					onChange={ update( '_simple_seo_description' ) }
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
					<p className="simple-seo-tip">
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

				<div>
					<ExternalLink href={ faqUrl }>
						{ __( 'Learn more about these fields', 'simple-seo' ) }
					</ExternalLink>
				</div>
			</Flex>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'simple-seo', { render: SimpleSeoPanel } );
