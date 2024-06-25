/**
 * Javascript side of the text field.
 * @since $ver$
 */

/**
 * Returns the provided data as text.
 * @since $ver$
 */
export default function Text( { name, item } ) {
    return <div>{item[ name ] || ''}</div>;
}
