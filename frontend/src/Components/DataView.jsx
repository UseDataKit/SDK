import { DataViews } from '@wordpress/dataviews';
import { useState, useEffect } from "react";

import '@wordpress/dataviews/build-style/style.css';

export default function DataView( { view, fields, data, paginationInfo, supportedLayouts } ) {
    const [viewState, setView] = useState( view );

    useEffect( () => {
        console.log( 'changed' );
    }, [viewState] );

    return <DataViews
        view={viewState}
        fields={fields}
        data={data}
        paginationInfo={paginationInfo}
        supportedLayouts={supportedLayouts}
        onChangeView={setView}
        isLoading={false}
    />

}
