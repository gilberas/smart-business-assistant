export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
}

export interface ApiResponse<T> {
    data: T;
    message?: string;
}

export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
    code: number;
}

export type SortDirection = 'asc' | 'desc';

export interface FilterParams {
    [key: string]: string | string[] | undefined;
}

export interface QueryParams {
    page?: number;
    per_page?: number;
    sort?: string;
    q?: string;
    filter?: FilterParams;
}
