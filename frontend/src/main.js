import { createRoot } from 'react-dom/client';
import { createElement } from 'react';
import DataView from '@src/Components/DataView.jsx';

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
