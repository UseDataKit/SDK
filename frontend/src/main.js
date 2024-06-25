import { createRoot } from 'react-dom/client';
import { createElement } from 'react';
import DataView from '@src/Components/DataView';

import Text from '@src/Fields/Text';
import Html from '@src/Fields/Html';
import Link from '@src/Fields/Link';
import Image from '@src/Fields/Image';
import Enum from '@src/Fields/Enum';
import DateTime from '@src/Fields/DateTime';

import Url from '@src/Actions/Url';

window.datakit_fields = new Proxy( {
    datetime: DateTime,
    enum: Enum,
    html: Html,
    image: Image,
    text: Text,
    link: Link,
}, {
    get: ( fields, type ) => {
        // Force the same function signature for every object.
        return ( name, data, context = [] ) => {
            return (fields[ type ] || fields[ 'text' ])( { name, item: data.item, context } );
        }
    },
} );

window.datakit_dataviews_actions = {
    url: ( data, context ) => Url( data, context ),
};

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
