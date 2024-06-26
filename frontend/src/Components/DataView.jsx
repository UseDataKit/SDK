import { DataViews } from '@wordpress/dataviews';
import { useState, useRef, useEffect } from 'react';
import { stringify } from 'qs';

import '@wordpress/dataviews/build-style/style.css';
import '@wordpress/components/build-style/style.css';

export default function DataView( props ) {
    const { id, view, fields, actions, data, paginationInfo, supportedLayouts, search, searchLabel } = props;
    const [viewState, setView] = useState( view );
    const [dataState, setData] = useState( data );
    const [paginationState, setPagination] = useState( paginationInfo );
    const [isLoading, setLoading] = useState( false );

    const updateData = ( request ) => {
        const query_params = stringify( request );
        const url = new URL( `${datakit_dataviews_rest_endpoint}/view/${id}?${query_params}` );

        fetch( url )
            .then( ( response ) => {
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
        data={dataState}
        paginationInfo={paginationState}
        onChangeView={setView}
        isLoading={isLoading}
        {...{ fields, actions, supportedLayouts, search, searchLabel }}
    />
}
