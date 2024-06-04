import { createRoot } from 'react-dom/client';
import { createElement } from 'react';
import DataView from '@src/Components/DataView.jsx';

const views = document.querySelectorAll( '[data-dataview]' );
[...views].forEach( dataview => {
    const root = createRoot( dataview );
    const dataViewData = JSON.parse(dataview.dataset[ 'dataview' ] || '{}');

    if (!dataViewData.dataSource) {
        return;
    }

    const dataView = createElement( DataView, dataViewData );
    root.render( dataView );
} );
