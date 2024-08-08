import { datakit_fetch, get, replace_tags } from '@src/helpers';

/**
 * Action that takes the user to a URL.
 *
 * @since $ver$
 *
 * @constructor
 *
 * @typedef Context The context object.
 *
 * @property {string} url The url to resolve.
 * @property {?Object} headers The headers to add.
 * @property {string} type The action type.
 * @property {?string} method The action method.
 * @property {?Object} params The action params.
 * @property {?string} confirm A confirm message.
 * @property {?boolean} use_new_window Whether to open the url in a new window.
 * @property {?boolean} use_single_request Whether to open the url in a new window.
 * @property {?object} registry The provided registry which contains a public API for the component.
 *
 * @param {Object.<string, string>[]} items The data object.
 * @param {Context} context The context object.
 */
export default function Url( items, context ) {
    if ( ( context.confirm ?? null ) && !window.confirm( context.confirm ) ) {
        return;
    }

    context?.type === 'ajax'
        ? handleAjax( items, context )
        : handleUrl( items, context );
};

/**
 * @since $ver$
 *
 * @param {Object.<string, string>[]} items The data object.
 * @param {Context} context The context object.
 */
function handleAjax( items, context ) {
    if ( !context?.url ) {
        console.warn( 'No url provided.' );
        return;
    }

    const options = {
        method: context?.method ?? 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    };

    const use_single_request = context?.use_single_request ?? false;
    const grouped = {};

    for ( const i in items ) {
        const data = items[ i ];
        const url = replace_tags( context.url, data );

        const params = Object.fromEntries(
            Object.entries( context?.params ?? {} )
                .map( ( [ key, value ] ) => [ key, replace_tags( value, data ) ] )
        );

        if ( !use_single_request ) {
            datakit_fetch( url, {
                ...options,
                body: JSON.stringify( params ),
            } )
                .catch( r => console.error( r ) );

            continue;
        }

        for ( const key in params ) {
            if ( !Object.hasOwn( grouped, key ) ) {
                grouped[ key ] = [];
            }
            grouped[ key ].push( params[ key ] );
        }
    }

    if ( !use_single_request ) {
        context?.registry?.refreshData();
        return;
    }

    datakit_fetch( context.url, {
        ...options,
        body: JSON.stringify( grouped ),
    } )
        .then( () => context?.registry?.refreshData() )
        .catch( r => console.error( r ) );
}


/**
 * @since $ver$
 *
 * @param {Object.<string, string>[]} items The data object.
 * @param {Context} context The context object.
 */
function handleUrl( items, context ) {
    for ( const i in items ) {

        const data = items[ i ];
        const url = replace_tags( context.url ?? '', data );
        const use_new_window = get( context, 'use_new_window', true );

        if ( url === '' ) {
            continue;
        }

        window.open( url, use_new_window ? '_blank' : '_self' );
    }
}
