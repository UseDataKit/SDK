import { get } from "@src/helpers.js";

/**
 * Returns the provided data as text.
 *
 * @since $ver$
 */
export default function Text( { name, item, context } ) {
    const is_nl2br = get( context, 'nl2br', false );
    const weight = get( context, 'weight', '' );
    const is_italic = get( context, 'italic', false );

    const styles = {
        'white-space': is_nl2br ? 'pre-line' : null,
        'font-weight': weight ? weight.toString() : null,
        'font-style': is_italic ? 'italic' : null,
    };

    return <div style={styles}>{item[ name ] || ''}</div>;
}
