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
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { createInterpolateElement } from '@wordpress/element';
import {
	BaseControl,
	Button,
	CheckboxControl,
	ExternalLink,
	Flex,
	Notice,
	TextareaControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

const { otherPlugin, simpleHistoryUrl, frontPageId, faqUrl } =
	window.simpleSeoEditor || {};

/**
 * The post's share image: a preview and the media library buttons, like core's
 * Featured image panel. Shows nothing for users who can't upload files.
 *
 * @param {Object}               props
 * @param {number}               props.imageId  Attachment ID, or 0.
 * @param {(id: number) => void} props.onChange Called with the new attachment ID, 0 to remove it.
 */
function ShareImageControl( { imageId, onChange } ) {
	const media = useSelect(
		( select ) =>
			imageId ? select( coreStore ).getMedia( imageId ) : undefined,
		[ imageId ]
	);
	const previewUrl =
		media?.media_details?.sizes?.medium?.source_url ?? media?.source_url;

	return (
		<MediaUploadCheck>
			{ /* A visual label, not a <label>: that would replace the button's own name. */ }
			<BaseControl
				__nextHasNoMarginBottom
				id="simple-seo-share-image"
				help={ __(
					'Used in link previews instead of the featured image.',
					'simple-seo'
				) }
			>
				<BaseControl.VisualLabel>
					{ __( 'Share image', 'simple-seo' ) }
				</BaseControl.VisualLabel>
				<MediaUpload
					title={ __( 'Share image', 'simple-seo' ) }
					allowedTypes={ [ 'image' ] }
					value={ imageId }
					onSelect={ ( image ) => onChange( image.id ) }
					render={ ( { open } ) => (
						<Flex direction="column" gap={ 2 } align="flex-start">
							{ previewUrl && (
								<img
									src={ previewUrl }
									alt={ media?.alt_text ?? '' }
									style={ {
										maxWidth: '100%',
										height: 'auto',
									} }
								/>
							) }
							<Flex justify="flex-start" gap={ 2 }>
								<Button
									__next40pxDefaultSize
									id="simple-seo-share-image"
									aria-describedby="simple-seo-share-image__help"
									variant="secondary"
									onClick={ open }
								>
									{ imageId
										? __( 'Replace', 'simple-seo' )
										: __(
												'Set share image',
												'simple-seo'
											) }
								</Button>
								{ !! imageId && (
									<Button
										__next40pxDefaultSize
										variant="tertiary"
										isDestructive
										onClick={ () => onChange( 0 ) }
									>
										{ __( 'Remove', 'simple-seo' ) }
									</Button>
								) }
							</Flex>
						</Flex>
					) }
				/>
			</BaseControl>
		</MediaUploadCheck>
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
		meta._simple_seo_title.trim() !== '' ||
		meta._simple_seo_description.trim() !== '' ||
		!! meta._simple_seo_noindex ||
		!! meta._simple_seo_share_image;

	// Meta edits are merged, so pass only the changed key.
	const update = ( key ) => ( value ) => setMeta( { [ key ]: value } );

	return (
		<PluginDocumentSettingPanel
			name="simple-seo"
			title={ __( 'Simple SEO', 'simple-seo' ) }
		>
			<Flex direction="column" gap={ 4 }>
				{ /* A warning, not grey help text: easy to miss otherwise, and
				     then the fields look like they do something. */ }
				{ otherPlugin && (
					<Notice status="warning" isDismissible={ false }>
						{ sprintf(
							/* translators: %s: name of another SEO plugin, like "Yoast SEO" or "The SEO Framework". */
							__(
								'%s plugin is active and handles SEO, so these fields aren’t used.',
								'simple-seo'
							),
							otherPlugin
						) }
					</Notice>
				) }

				{ /* A textarea that looks like a text field until the title needs a
				     second line: a one-line field in the sidebar shows only about 30
				     characters. A title is one line, so Enter does nothing and pasted
				     line breaks become spaces. */ }
				<TextareaControl
					__nextHasNoMarginBottom
					className="simple-seo-autogrow simple-seo-title"
					rows={ 1 }
					label={ __( 'SEO title', 'simple-seo' ) }
					help={
						postId === frontPageId
							? __(
									'The whole title of the front page.',
									'simple-seo'
								)
							: __(
									'About 50 characters. The site name is added after it.',
									'simple-seo'
								)
					}
					value={ meta._simple_seo_title }
					onChange={ ( value ) =>
						update( '_simple_seo_title' )(
							value.replace( /[\r\n]+/g, ' ' )
						)
					}
					onKeyDown={ ( event ) => {
						if ( event.key === 'Enter' ) {
							event.preventDefault();
						}
					} }
				/>

				<TextareaControl
					__nextHasNoMarginBottom
					className="simple-seo-autogrow"
					rows={ 2 }
					label={ __( 'Meta description', 'simple-seo' ) }
					help={ __(
						'Often shown in search results.',
						'simple-seo'
					) }
					value={ meta._simple_seo_description }
					onChange={ update( '_simple_seo_description' ) }
				/>

				<ShareImageControl
					imageId={ meta._simple_seo_share_image ?? 0 }
					onChange={ update( '_simple_seo_share_image' ) }
				/>

				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __(
						'Discourage search engines from indexing this page',
						'simple-seo'
					) }
					help={ __(
						'It’s up to search engines to honor this request.',
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
