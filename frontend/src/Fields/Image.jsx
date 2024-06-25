/**
 * Javascript side of the Image field.
 * @since $ver$
 */

import '../scss/image.css';

/**
 * Returns the provided data as an image.
 * @since $ver$
 *
 * @param {String} name The field ID.
 * @param {Object} item The item object.
 * @param {Object} context Any extra provided context.
 * @return {JSX.Element} The React component.
 * @constructor
 */
export default function Image( { name, item, context } ) {
    const alt = context?.alt || '';
    const height = context?.height || '';
    const width = context?.width || '';
    const className = context?.class || '';

    return <div>
        <img className={className} src={item[ name ]} width={width || ''} height={height || ''} alt={alt}/>
    </div>;
}
