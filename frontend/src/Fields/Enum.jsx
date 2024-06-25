/**
 * Javascript side of the Enum field.
 * @since $ver$
 */

/**
 * Returns the value of the field on the item.
 * @since $ver$
 * @param {Object} item The item that contains the data.
 * @param {String} name The field name.
 * @return {String} The value of the field on the item.
 */
const get_value = ( item, name ) => item[ name ] || '';

/**
 * Returns the corresponding label of the field on the item if found, otherwise the value.
 * @since $ver$
 * @param {String} value The field value
 * @param {Object[]} elements The options.
 * @return {String} The label of the value.
 */
const get_label = ( value, elements ) => {
    for ( const i in elements ) {
        if ( elements[ i ].value !== value ) {
            continue;
        }

        return elements[ i ].label;
    }

    return value;
}

/**
 * The Enum field
 * @param name
 * @param item
 * @param context
 * @return {JSX.Element}
 * @constructor
 */
export default function Enum( { name, item, context } ) {
    const value = get_value( item, name );
    const elements = context.elements || [];

    return <div>{get_label( value, elements )}</div>;
}
