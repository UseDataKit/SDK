import { useEffect } from 'react';
import { get, extract_javascript_fn, strip_javascript } from '@src/helpers.js';

/**
 * JavaScript side of the HTML field.
 *
 * @since $ver$
 */
export default function Html( { name, item, context } ) {
    const is_script_allowed = get( context, 'is_scripts_allowed', false );
    let content = item[ name ] || '';
    let script_func = null;

    if ( is_script_allowed ) {
        script_func = extract_javascript_fn( content );
    } else {
        content = strip_javascript( content );
    }

    useEffect( () => {
        if ( is_script_allowed && typeof script_func === 'function' ) {
            script_func();
        }
    }, [ content ] );

    return <div className="datakit-field" dangerouslySetInnerHTML={{ __html: content }}></div>;
}
