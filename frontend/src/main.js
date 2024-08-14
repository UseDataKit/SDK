import { createRoot } from 'react-dom/client';
import { createElement } from 'react';
import DataView from '@src/DataView/DataView.jsx';
import Modal from '@src/Components/Modal';

import Text from '@src/Fields/Text';
import Html from '@src/Fields/Html';
import Url from '@src/Actions/Url';
import { QueryClient } from "@tanstack/react-query";

// Todo: extract window parameters somewhere outside the entry point.
window.datakit_fields = new Proxy( {
    html: Html,
    text: Text,
}, {
    get: ( fields, type ) => {
        // Force the same function signature for every object.
        return ( name, data, context = [] ) => {
            return ( fields[ type ] || fields[ 'text' ] )( { name, item: data.item, context } );
        }
    },
} );

window.datakit_dataviews_actions = {
    url: ( data, context ) => Url( data, context ),
};

window.datakit_modal = Modal;

const queryClient = new QueryClient();
const views = document.querySelectorAll( '[data-dataview]' );
[ ...views ].forEach( dataview => {

    const dataViewID = dataview.dataset[ 'dataview' ] ?? null;
    if ( !datakit_dataviews[ dataViewID ] ) {
        return;
    }

    const dataViewData = datakit_dataviews[ dataViewID ] ?? [];
    const wrapper = createRoot( dataview );
    const dataView = createElement( DataView, {
        id: dataViewID,
        apiUrl: datakit_dataviews_rest_endpoint,
        ...dataViewData,
        queryClient,
        element: dataview
    } );

    wrapper.render( dataView );
} );
