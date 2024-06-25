/**
 * Javascript side of the Link field.
 * @since $ver$
 *
 * @typedef {Object} Context The context shape.
 * @property {"none","field"} type The link type.
 * @property {string} link The label to use on the link.
 * @property {boolean} use_new_window Whether to use a target `_blank`.
 * @property {boolean} is_mail_to Whether this is a `mailto:` link.
 *
 * @param {string} name The field name.
 * @param {Object.<string, string>} item The data object.
 * @param {Context} context The context object.
 */
export default function Link( { name, item, context } ) {
    const label = item[ name ] || '';
    const href = context.type === 'field' ? item[ context.link ] || null : label;
    const use_new_window = context.hasOwnProperty( 'use_new_window' ) ? context.use_new_window : true;

    if ( !href ) {
        return <>{label}</>;
    }

    return <a target={use_new_window ? '_blank' : ''} href={href}>{label}</a>;
}
