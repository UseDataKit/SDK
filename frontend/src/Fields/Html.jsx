/**
 * JavaScript side of the HTML field.
 *
 * @since $ver$
 */
export default function Html( { name, item } ) {
    return <div className="datakit-field" dangerouslySetInnerHTML={{ __html: item[ name ] || '' }}></div>;
}
