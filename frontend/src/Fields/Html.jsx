import { useEffect } from 'react';
import { get } from '@src/helpers.js';

/**
 * JavaScript side of the HTML field.
 *
 * @since $ver$
 */

const regex = /(?:\r\n)*<script[^>]*>(.*?)<\/[\r\n\s]*script>(?:\r\n)*/isg;

export default function Html( { name, item, context } ) {
    const is_script_allowed = get( context, 'is_scripts_allowed', false );
    let content = item[ name ] || '';
    let script_body = '';

    if ( is_script_allowed ) {
        // Record all scripts from the tags
        const scripts = content.match( regex );
        if ( scripts ) {
            for ( const script of scripts ) {
                script_body += script.replace( regex, '$1' );
            }
        }
    } else {
        // Remove script tags from the html.
        content = content.replace( regex, '' );
    }

    useEffect( () => {
        if ( is_script_allowed && script_body ) {
            const script_func = new Function( script_body );
            script_func();
        }
    }, [ content ] );

    return <div className="datakit-field" dangerouslySetInnerHTML={{ __html: content }}></div>;
}
