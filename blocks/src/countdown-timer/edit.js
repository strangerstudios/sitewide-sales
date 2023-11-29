/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * WordPress dependencies
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

/**
 * Render the Membership Checkout block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit ( props ) {
    const {
        attributes: { end_on },
        setAttributes,
        className,
        isSelected,
    } = props;
    const blockProps = useBlockProps( {
        className: 'swsales_countdown_timer'
    } );
    const onChangeEndOn = ( end_on ) => {
        setAttributes( { end_on: end_on } );
    };
   
    return (
        <>
        { isSelected && (
            <InspectorControls>
            <PanelBody>
                <p><strong>{ __( 'Timer Ends On', 'sitewide-sales' ) }</strong></p>
                <SelectControl
                    value={ end_on }
                    help={__( 'Select whether this timer counts down to the sale start date or end date.', 'sitewide-sales' ) }
                    options={ [
                        { label: __( 'Sale End Date', 'sitewide-sales' ), value: 'end_date' },
                        { label: __( 'Sale Start Date', 'sitewide-sales' ), value: 'start_date' },
                    ] }
                    onChange={ onChangeEndOn }
                />
            </PanelBody>
        </InspectorControls>
        )}
        <div { ...blockProps }>
            <div className="swsales_countdown_timer_element">
                <div className="swsales_countdown_timer_inner">
                    <span className="swsalesDays">{ '15' }</span>
                    <div className="swsales_countdown_timer_period">{ __( 'Days', 'sitewide-sales' ) }</div>
                </div>
            </div>
            <div className="swsales_countdown_timer_element">
                <div className="swsales_countdown_timer_inner">
                    <span className="swsalesHours">{ '11' }</span>
                    <div className="swsales_countdown_timer_period">{ __( 'Hours', 'sitewide-sales' ) }</div>
                </div>
            </div>
            <div className="swsales_countdown_timer_element">
                <div className="swsales_countdown_timer_inner">
                    <span className="swsalesMinutes">{ '50' }</span>
                    <div className="swsales_countdown_timer_period">{ __( 'Minutes', 'sitewide-sales' ) }</div>
                </div>
            </div>
            <div className="swsales_countdown_timer_element">
                <div className="swsales_countdown_timer_inner">
                    <span className="swsalesSeconds">{ '30' }</span>
                    <div className="swsales_countdown_timer_period">{ __( 'Seconds', 'sitewide-sales' ) }</div>
                </div>
            </div>
        </div>
        </>
    );
}
