import { DataViews } from '@wordpress/dataviews';
import { useState } from "react";

import '@wordpress/dataviews/build-style/style.css';

export default function DataView( { view, fields, data, paginationInfo, supportedLayouts } ) {
    const [viewState, setView] = useState( view );
    let isLoading = false;

    return <DataViews
        view={viewState}
        fields={fields}
        data={data}
        paginationInfo={paginationInfo}
        supportedLayouts={supportedLayouts}
        onChangeView={setView}
        isLoading={isLoading}
    />

}
