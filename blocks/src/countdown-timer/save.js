/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

export default function Save() {
    const blockProps = useBlockProps.save();
    
    return (
        <div { ...blockProps }></div>
    );
}
