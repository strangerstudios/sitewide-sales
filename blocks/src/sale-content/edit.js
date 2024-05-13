/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';


/**
 * WordPress dependencies
 */
import { InnerBlocks, InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useState } from '@wordpress/element';


/**
 * Render the Membership Checkout block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit ( props ) {
    const { attributes: {period}, setAttributes, isSelected } = props;
    const blockProps = useBlockProps();
   
    return (
        <>
        <InspectorControls>
            <PanelBody>
                <p><strong>{ __( 'Sale Period', 'sitewide-sales' ) }</strong></p>
                <SelectControl
                    value={ period }
                    help={__( 'Select the sale period this content is visible for.', 'sitewide-sales' ) }
                    options={ [
                        { label: __( 'Always', 'sitewide-sales' ), value: '' },
                        { label: __( 'Before Sale', 'sitewide-sales' ), value: 'pre-sale' },
                        { label: __( 'During Sale', 'sitewide-sales' ), value: 'sale' },
                        { label: __( 'After Sale', 'sitewide-sales' ), value: 'post-sale' },
                    ] }
                     onChange={ period => setAttributes( { period } ) }
                />
            </PanelBody>
        </InspectorControls>
        <div {...blockProps}>
          <div className="swsales-wrapper-block" >
          <span className="swsales-block-title">{ __( 'Sitewide Sale Content', 'sitewide-sales' ) }</span>
          <InnerBlocks
              templateLock={ false }
          />
          </div>  
        </div>
        </>
    );
}
