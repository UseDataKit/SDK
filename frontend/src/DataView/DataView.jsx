import { DataViews } from '@wordpress/dataviews';
import { RegistryProvider } from '@wordpress/data';
import { useState, StrictMode } from 'react';
import { stringify } from 'qs';

import { useRequest } from '@src/DataView/useRequest';
import { useRequestCallback } from '@src/DataView/useRequestCallback';

import '@wordpress/dataviews/build-style/style.css';
import '@wordpress/components/build-style/style.css';
import '@src/scss/fields.css';

/**
 * Creates the Dataview.
 * @since $ver$
 * @return {JSX.Element} The dataview object.
 * @constructor
 */
export default function DataView(
    { id, view, fields, actions, data, paginationInfo, supportedLayouts, search, searchLabel, apiUrl }
) {
    const [ viewState, setView ] = useState( view );
    const [ dataState, setData ] = useState( data );
    const [ paginationState, setPagination ] = useState( paginationInfo );
    const [ isLoading, setLoading ] = useState( false );
    const requestState = useRequest( id, viewState );

    /**
     * Updates the data and pagination based on a requestState.
     *
     * @since $ver$
     * @param {RequestState} request The request object.
     * @return {void}
     */
    const updateData = ( request ) => {
        setLoading( true );

        const query_params = stringify( request );
        const url = new URL( `${apiUrl}/views/${id}?${query_params}` );

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
            } );
    }

    /**
     * API passed to the callback actions.
     * @since $ver$
     */
    const public_api = {
        refreshData() {
            updateData( requestState );
        }
    }

    /**
     * Update the data whenever the request state changes.
     *
     * THe request state is a subset of the view object, since not every change to the view warrants a data refresh.
     *
     * @since $ver$
     */
    useRequestCallback( () => updateData( requestState ), requestState );

    return <StrictMode>
        <RegistryProvider value={public_api}>
            <DataViews
                id={id}
                view={viewState}
                data={dataState}
                paginationInfo={paginationState}
                onChangeView={setView}
                isLoading={isLoading}
                {...{ fields, actions, supportedLayouts, search, searchLabel }}
            />
        </RegistryProvider>
    </StrictMode>;
}
