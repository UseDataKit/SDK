import { get, replace_tags } from '@src/helpers';

/**
 * Action that takes the user to a url.
 *
 * @since $ver$
 * @typedef Context The context object.
 * @property {string} url The url to resolve.
 * @property {boolean} use_new_window Whether to open the url in a new window.
 *
 * @param {Object.<string, string>} items The data object.
 * @param {Context} context The context object.
 * @constructor
 */
export default function Url( items, context ) {
    for ( const i in items ) {
        const data = items[ i ];
        const url = replace_tags( context.url, data );
        const use_new_window = get( context, 'use_new_window', true );

        if ( url === '' ) {
            continue;
        }

        window.open( url, use_new_window ? '_blank' : '_self' );
    }
}
