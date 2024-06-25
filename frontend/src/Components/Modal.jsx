import { get, replace_tags } from '@src/helpers';

export default function Modal( { items, closeModal, context } ) {
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

    return <>{url}</>;
}
