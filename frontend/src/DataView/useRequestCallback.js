import { useEffect, useState } from 'react';

/**
 * Custom request callback hook that skips the first call.
 *
 * Note: the first call is skipped because we provide the first data on initializing. This prevents
 * a useless extra API call.
 *
 * @since $ver$
 *
 * @param {CallableFunction} callback The callback to trigger on the effect.
 * @param {RequestState} requestState The request state used for the effect.
 *
 * @return void
 */
export function useRequestCallback( callback, requestState ) {
    const [ isInitialized, setInitialized ] = useState( false );

    useEffect( () => {
        if ( !isInitialized ) {
            setInitialized( true );
            return;
        }

        callback();
    }, [ requestState ] );
}
