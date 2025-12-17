import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\OrderController::orderbook
* @see app/Http/Controllers/OrderController.php:140
* @route '/api/orders'
*/
export const orderbook = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderbook.url(options),
    method: 'get',
})

orderbook.definition = {
    methods: ["get","head"],
    url: '/api/orders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrderController::orderbook
* @see app/Http/Controllers/OrderController.php:140
* @route '/api/orders'
*/
orderbook.url = (options?: RouteQueryOptions) => {




    return orderbook.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderController::orderbook
* @see app/Http/Controllers/OrderController.php:140
* @route '/api/orders'
*/
orderbook.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderbook.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderController::orderbook
* @see app/Http/Controllers/OrderController.php:140
* @route '/api/orders'
*/
orderbook.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: orderbook.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrderController::orderbook
* @see app/Http/Controllers/OrderController.php:140
* @route '/api/orders'
*/
const orderbookForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderbook.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderController::orderbook
* @see app/Http/Controllers/OrderController.php:140
* @route '/api/orders'
*/
orderbookForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderbook.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderController::orderbook
* @see app/Http/Controllers/OrderController.php:140
* @route '/api/orders'
*/
orderbookForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderbook.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

orderbook.form = orderbookForm

/**
* @see \App\Http\Controllers\OrderController::store
* @see app/Http/Controllers/OrderController.php:34
* @route '/api/orders'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/orders',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OrderController::store
* @see app/Http/Controllers/OrderController.php:34
* @route '/api/orders'
*/
store.url = (options?: RouteQueryOptions) => {




    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderController::store
* @see app/Http/Controllers/OrderController.php:34
* @route '/api/orders'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderController::store
* @see app/Http/Controllers/OrderController.php:34
* @route '/api/orders'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderController::store
* @see app/Http/Controllers/OrderController.php:34
* @route '/api/orders'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\OrderController::cancel
* @see app/Http/Controllers/OrderController.php:164
* @route '/api/orders/{order}/cancel'
*/
export const cancel = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

cancel.definition = {
    methods: ["post"],
    url: '/api/orders/{order}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OrderController::cancel
* @see app/Http/Controllers/OrderController.php:164
* @route '/api/orders/{order}/cancel'
*/
cancel.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { order: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { order: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            order: args[0],
        }
    }

    args = applyUrlDefaults(args)


    const parsedArgs = {
        order: typeof args.order === 'object'
        ? args.order.id
        : args.order,
    }

    return cancel.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderController::cancel
* @see app/Http/Controllers/OrderController.php:164
* @route '/api/orders/{order}/cancel'
*/
cancel.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderController::cancel
* @see app/Http/Controllers/OrderController.php:164
* @route '/api/orders/{order}/cancel'
*/
const cancelForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderController::cancel
* @see app/Http/Controllers/OrderController.php:164
* @route '/api/orders/{order}/cancel'
*/
cancelForm.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

cancel.form = cancelForm

/**
* @see \App\Http\Controllers\OrderController::myOrders
* @see app/Http/Controllers/OrderController.php:196
* @route '/api/my-orders'
*/
export const myOrders = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: myOrders.url(options),
    method: 'get',
})

myOrders.definition = {
    methods: ["get","head"],
    url: '/api/my-orders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrderController::myOrders
* @see app/Http/Controllers/OrderController.php:196
* @route '/api/my-orders'
*/
myOrders.url = (options?: RouteQueryOptions) => {




    return myOrders.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderController::myOrders
* @see app/Http/Controllers/OrderController.php:196
* @route '/api/my-orders'
*/
myOrders.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: myOrders.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderController::myOrders
* @see app/Http/Controllers/OrderController.php:196
* @route '/api/my-orders'
*/
myOrders.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: myOrders.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrderController::myOrders
* @see app/Http/Controllers/OrderController.php:196
* @route '/api/my-orders'
*/
const myOrdersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: myOrders.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderController::myOrders
* @see app/Http/Controllers/OrderController.php:196
* @route '/api/my-orders'
*/
myOrdersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: myOrders.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderController::myOrders
* @see app/Http/Controllers/OrderController.php:196
* @route '/api/my-orders'
*/
myOrdersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: myOrders.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

myOrders.form = myOrdersForm

const OrderController = { orderbook, store, cancel, myOrders }

export default OrderController