import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ProfileController::show
* @see app/Http/Controllers/ProfileController.php:37
* @route '/api/profile'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProfileController::show
* @see app/Http/Controllers/ProfileController.php:37
* @route '/api/profile'
*/
show.url = (options?: RouteQueryOptions) => {




    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProfileController::show
* @see app/Http/Controllers/ProfileController.php:37
* @route '/api/profile'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProfileController::show
* @see app/Http/Controllers/ProfileController.php:37
* @route '/api/profile'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProfileController::show
* @see app/Http/Controllers/ProfileController.php:37
* @route '/api/profile'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProfileController::show
* @see app/Http/Controllers/ProfileController.php:37
* @route '/api/profile'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProfileController::show
* @see app/Http/Controllers/ProfileController.php:37
* @route '/api/profile'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const ProfileController = { show }

export default ProfileController