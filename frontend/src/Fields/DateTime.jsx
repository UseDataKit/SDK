/**
 * Javascript side of the Enum field.
 * @since $ver$
 */

/**
 * Returns the value as a time object.
 * @since $ver$
 * @param {string} name The field ID.
 * @param {object} item The item data.
 * @return {ReactElement} The React component.
 * @constructor
 */
export default function DateTime( { name, item } ) {
    return <time>{item[ name ] || ''}</time>;
}
