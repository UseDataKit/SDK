import { createRoot } from 'react-dom/client';
import { createElement } from 'react';
import DataView from '@src/Components/DataView.jsx';

import Text from '@src/Fields/Text';
import Html from '@src/Fields/Html';
import Image from '@src/Fields/Image';
import Enum from '@src/Fields/Enum';

window.datakit_fields = new Proxy( {
    text: Text,
    html: Html,
    image: Image,
    enum: Enum,
}, {
    get: ( fields, type ) => {
        // Force the same function signature for every object.
        return ( name, data, context = [] ) => {
            return fields[ type ]( { name, item: data.item, context } );
        }
    },
} );

const views = document.querySelectorAll( '[data-dataview]' );
[...views].forEach( dataview => {

    const dataViewID = dataview.dataset[ 'dataview' ] || null;
    if ( !datakit_dataviews[ dataViewID ] || null ) {
        return;
    }

    const dataViewData = datakit_dataviews[ dataViewID ];
    const wrapper = createRoot( dataview );
    const dataView = createElement( DataView, { id: dataViewID, ...dataViewData } );

    wrapper.render( dataView );
} );
