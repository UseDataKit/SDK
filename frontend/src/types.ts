import {Filter, SortDirection} from '@wordpress/dataviews';

/**
 * Subset of ViewData used for an API request.
 *
 * @since $ver$
 */
export interface RequestState {
    per_page: Number;
    page: Number;
    search: String;
    filters: Array<Filter>;
    sort: {
        field: string;
        direction: SortDirection;
    };
}
