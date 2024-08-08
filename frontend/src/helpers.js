/**
 * File with javascript helper functions.
 *
 * @since $ver$
 *
 * @param {object} data The data object to find the key on.
 * @param {string} key The key on the data object.
 * @param {any} fallback The fallback value if the key was not found.
 */
export function get( data, key, fallback = null ) {
    if ( !Object.hasOwn( data, key ) ) {
        return fallback;
    }

    return data[ key ];
}

/**
 * Replaces `{tag}` with the value form data.tag.
 *
 * @since $ver$
 *
 * @param {string} value The value to replace the tags on.
 * @param {Object.<string, string>} data The dataset containing the tags.
 *
 * @return {string|null}
 */
export function replace_tags( value, data ) {
    return value.replace( /{([^}]+)}/g, ( _, key ) => {
        let val = Object.hasOwn( data, key ) ? data[ key ] : null;

        if ( val === null ) {
            val = '';
        }

        return val;
    } );
}

/**
 * `fetch` wrapper that mixes in default options.
 *
 * @since $ver$
 *
 * @param {string} url The URL to call.
 * @param {object|null} options The options to provide to the fetch call.
 *
 * @return {Promise<Response>} The fetch promise.
 */
export function datakit_fetch( url, options ) {
    const defaults = typeof datakit_fetch_options === 'undefined' ? {} : datakit_fetch_options;
    const merged_options = {
        ...defaults,
        ...options ?? {},
        headers: {
            ...defaults?.headers ?? {},
            ...options?.headers ?? {},
        }
    };

    return fetch( url, merged_options );
}
