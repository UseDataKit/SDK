import { DataViews } from '@wordpress/dataviews';
import { RegistryProvider } from '@wordpress/data';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { useRequest } from '@src/DataView/useRequest';
import { useRequestCallback } from '@src/DataView/useRequestCallback.js';
import { stringify } from 'qs';

import '@wordpress/dataviews/build-style/style.css';
import '@wordpress/components/build-style/style.css';
import '@src/scss/fields.scss';

const getData = async ( request, apiUrl ) => {
    const { id, ...params } = request;
    const query_params = stringify( params );
    const append = apiUrl.indexOf( '?' ) !== -1 ? '&' : '?';

    const url = new URL( `${apiUrl}/views/${id}${append}${query_params}` );
    const res = await fetch( url );

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
    { id, view, data, paginationInfo, apiUrl, queryClient, ...props }
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
            onChangeView={setView}
            isLoading={isLoading}
            {...props}
        />
    </RegistryProvider>;
}
