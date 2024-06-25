/**
 * Javascript side of the HTML field.
 * @since $ver$
 */
export default function Html( { name, item } ) {
    return <div dangerouslySetInnerHTML={{ __html: item[ name ] || '' }}></div>;
}
