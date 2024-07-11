/**
 * Modal component used to show HTML on an action.
 * While loading the content, the wrapper div will have a class of `loading`.
 *
 * The response for the URL must be a JSON object with a `html` key that contains the html to add to the modal.
 *
 * @since $ver$
 */
import { get, replace_tags } from '@src/helpers';
import { useState } from 'react';

export default function Modal( { items, closeModal, context } ) {
    const [ body, setBody ] = useState( null );
    const [ busy, setBusy ] = useState( false );

    // Close modal on any element that has `data-close-modal` as a data-attribute.
    const handleClick = ( e ) => e.target.matches( '[data-close-modal]' ) && closeModal();

    if ( items.length !== 1 ) {
        console.warn( 'Can only open a modal for one item.' )
        return;
    }

    const data = items[ 0 ];
    let url = get( context, 'url', null );

    if ( url === null ) {
        closeModal();
        return;
    }

    url = replace_tags( url, data );

    if ( body === null && !busy ) {
        setBusy( true );
        fetch( url )
            .then( ( response ) => {
                if ( !response.ok ) {
                    throw new Error( `Response status: ${response.status}` );
                }

                return response.json();
            } )
            .then( ( { html } ) => setBody( html ) )
            .catch( e => console.error( e ) )
            .finally( () => setBusy( false ) )
    }

    if ( busy ) {
        return <div className='loading'>Loading...</div>;
    }

    return <div onClick={handleClick} dangerouslySetInnerHTML={{ __html: body }}>< /div>;
}
