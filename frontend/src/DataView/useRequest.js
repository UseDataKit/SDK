import { useEffect, useReducer } from 'react';

/**
 * Reducer that only returns a new object if the data has changed.
 *
 * @since $ver$
 *
 * @param {RequestState} request The current state object.
 * @param {RequestState} new_request The possibly update state object.
 *
 * @return {RequestState} The possibly updated request object.
 */
function requestReducer( request, new_request ) {
    return JSON.stringify( request ) !== JSON.stringify( new_request )
        ? new_request
        : request;
}

/**
 * Generate a Request object from a view.
 *
 * @since $ver$
 *
 * @param {String} id The DataView ID.
 * @param {Object} viewState The view state.
 *
 * @return {RequestState} The resulting object.
 */
function generateRequestState( { id, viewState } ) {
    return {
        id: id,
        search: viewState.search,
        filters: viewState.filters,
        sort: viewState.sort,
        page: viewState.page,
        per_page: viewState.perPage,
    }
}

/**
 * Custom request hook that updates the request state from the view.
 *
 * It uses a custom reducer that only triggers a change if the subset changes.
 *
 * @since $ver$
 * @param {String} id The dataview ID.
 * @param {Object} viewState The view object.
 * @return {RequestState} The request object.
 */
export function useRequest( id, viewState ) {
    const [ requestState, setRequestState ] = useReducer( requestReducer, { id, viewState }, generateRequestState );

    useEffect( () => {
        setRequestState( generateRequestState( { id, viewState } ) );
    }, [ viewState ] );

    return requestState;
}
