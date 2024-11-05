import { DataViews } from '@wordpress/dataviews';
import { RegistryProvider } from '@wordpress/data';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useEffect, useState } from 'react';
import { useRequest } from '@src/DataView/useRequest';
import { useRequestCallback } from '@src/DataView/useRequestCallback';
import { datakit_fetch } from '@src/helpers';
import { stringify } from 'qs';

import '@wordpress/components/build-style/style.css';
import '@wordpress/dataviews/build-style/style.css';
import '@src/scss/fields.scss';
import '@src/scss/modal.scss';

const getData = async ( request, apiUrl ) => {
    const { id, ...params } = request;
    const query_params = stringify( params );
    const append = apiUrl.indexOf( '?' ) !== -1 ? '&' : '?';

    const url = new URL( `${apiUrl}/views/${id}${append}${query_params}` );
    const res = await datakit_fetch( url );

    return res.json();
}

/**
 * Creates the DataView.
 *
 * @constructor
 *
 * @since $ver$
 *
 * @return {JSX.Element} The DataView object.
 */
export default function DataView(
    { id, view, data, paginationInfo, apiUrl, queryClient, element, ...props }
) {
    const [ viewState, setView ] = useState( view );
    const requestState = useRequest( id, viewState );
    const { isLoading, data: view_data } = useQuery( {
        queryKey: [ 'view-data', id ],
        queryFn: () => getData( requestState, apiUrl ),
        placeholderData: keepPreviousData,
        initialData: { data, paginationInfo },
        refetchOnMount: false,
    }, queryClient );

    useRequestCallback( () => queryClient.invalidateQueries( { queryKey: [ 'view-data', id ] } ), requestState );

    /**
     * Handles the change to the view state, and dispatched events around it.
     * @since $ver$
     * @param new_state The new View state.
     */
    const onChangeView = function ( new_state ) {
        element.dispatchEvent( new CustomEvent( 'datakit/view/change', {
            bubbles: true,
            detail: {
                id,
                old: { ...view }, // Send a copy.
                new: new_state // Send real reference.
            },
        } ) );

        setView( new_state );
    }

    useEffect( () => {
        element.dispatchEvent( new CustomEvent( 'datakit/view/changed', {
            bubbles: true,
            detail: { id, view: { ...viewState } },
        } ) );
    }, [ viewState ] );

    /**
     * Handles the selection of items on a view and dispatches an event when the selection changes.
     * @since $ver$
     * @param {string[]} items The selected IDs.
     */
    const onChangeSelection = function ( items ) {
        element.dispatchEvent( new CustomEvent( 'datakit/view/selected', {
            bubbles: true,
            detail: { id, items },
        } ) );
    }

    /**
     * API passed to the callback actions.
     *
     * @since $ver$
     */
    const public_api = {
        refreshData() {
            queryClient.invalidateQueries( { queryKey: [ 'view-data', id ] } );
        }
    }

    if ( !view_data ) {
        return <div>Loading ... </div>
    }

    return <RegistryProvider value={public_api}>
        <DataViews
            id={id}
            view={viewState}
            data={view_data.data}
            paginationInfo={view_data.paginationInfo}
            onChangeView={onChangeView}
            isLoading={isLoading}
            onChangeSelection={onChangeSelection}
            {...props}
        />
    </RegistryProvider>;
}
