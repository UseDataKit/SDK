/**
 * Returns the provided data as text.
 * @since $ver$
 */
export default function Text( { name, item } ) {
    return <div>{item[ name ] || ''}</div>;
}
