import { DataViews } from '@wordpress/dataviews';
import { useState, useRef, useEffect } from 'react';
import { stringify } from 'qs';

import '@wordpress/dataviews/build-style/style.css';

export default function DataView( { id, view, fields, data, paginationInfo, supportedLayouts } ) {
    const [viewState, setView] = useState( view );
    const [dataState, setData] = useState( data );
    const [paginationState, setPagination] = useState( paginationInfo );
    const [isLoading, setLoading] = useState( false );

    const updateData = ( request ) => {
        const query_params = stringify( request );
        const url = new URL( `${datakit_rest_endpoint}/view/${id}?${query_params}` );

        fetch( url ).then( ( response ) => {
            if ( !response.ok ) {
                throw new Error( 'Network response was not ok' );
            }

            return response.json();
        } )
            .then( ( { data, paginationInfo } ) => {
                setData( data );
                setPagination( paginationInfo )
            } )
            .finally( () => {
                setLoading( false );
            } )
    }

    const previousRequestState = useRef( null );
    useEffect( () => {
        const requestState = {
            id: id,
            search: viewState.search,
            filters: viewState.filters,
            sort: viewState.sort,
            page: viewState.page,
            per_page: viewState.perPage,
        };

        // Only call API when request changes.
        if (
            previousRequestState.current !== null
            && JSON.stringify( requestState ) !== JSON.stringify( previousRequestState.current )
        ) {
            updateData( requestState );
        }

        previousRequestState.current = requestState;
    }, [viewState] );

    return <DataViews
        id={id}
        view={viewState}
        fields={fields}
        data={dataState}
        paginationInfo={paginationState}
        supportedLayouts={supportedLayouts}
        onChangeView={setView}
        isLoading={isLoading}
    />
}
