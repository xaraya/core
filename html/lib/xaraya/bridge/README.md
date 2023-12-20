# Bridges with Xaraya

This contains various bridges between Xaraya and other PHP packages or framework components:

- [Logging (PSR-3)](#logging-psr-3)
- [Event Dispatcher (Symfony)](#event-dispatcher-symfony)
- [Routing Library (FastRoute)](#routing-library-fastroute)
- [HTTP Server Request (PSR-7)](#http-server-request-psr-7)
- [Middleware and Request Handler (PSR-15)](#middleware-and-request-handler-psr-15)
- [Middleware and Routing Combined](#middleware-and-routing-combined)
- [Non-blocking HTTP Server (ReactPHP)](#non-blocking-http-server-reactphp)

## Logging (PSR-3)

External packages using a standard [PSR-3 Logger](https://www.php-fig.org/psr/psr-3/) can send messages to the xarLog loggers. Example: sending the audit trail of Symfony Workflow component to xarLog in the workflow module.

Requirement: some package depending on [PSR-3 Logger Interface](https://packagist.org/packages/psr/log/dependents?order_by=downloads&requires=require)
```
$ composer require psr/log
```

Usage:
```
 use Xaraya\Bridge\Logging\LoggerBridge;
 
 $logger = new LoggerBridge();
 // some package class expecting a logger compatible with LoggerInterface
 $mypackage->setLogger($logger);
```

For logging the other way around, i.e. from xarLog to PSR-3 logger, a new xarLogger class will need to be created.

## Event Dispatcher (Symfony)

Interactions between Xaraya Event Management System (EMS) and [Symfony EventDispatcher](https://symfony.com/doc/current/components/event_dispatcher.html)

Requirement:
```
$ composer require symfony/event-dispatcher
```

### Event Subscriber (EventDispatcher -> Xaraya EMS)

Pass events from Symfony EventDispatcher via event subscriber to notify xarEvents or xarHooks and beyond

Usage:
```
use Symfony\Component\EventDispatcher\EventDispatcher;
//use Xaraya\Bridge\Events\EventSubscriber;
use Xaraya\Bridge\Events\HookSubscriber;
use Xaraya\Bridge\Events\DefaultEvent;
use Xaraya\Context\Context;

// subscriber bridge for events and/or hooks in your app
//$subscriber = new EventSubscriber();
$subscriber = new HookSubscriber();
//$eventlist = $subscriber::getSubscribedEvents();
//echo var_export($eventlist, true);
$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber($subscriber);

// current context
$context = new Context(['requestId' => 'something']);

// create an event with $subject corresponding to the $args in xarEvents::notify()
$subject = ['module' => 'dynamicdata', 'itemtype' => 3, 'itemid' => 123];
$event = new DefaultEvent($subject);
// set context if available
$event->setContext($context);

// this will call xarHooks::notify('ItemCreate', $subject) and save any response in the subscriber
$dispatcher->dispatch($event, 'xarHooks.item.ItemCreate');
$responses = $subscriber->getResponses();
```

### Observer Bridge (Xaraya EMS -> EventDispatcher)

Pass events from Xaraya xarEvents or xarHooks via observer bridge to Symfony EventDispatcher and beyond

Usage:
```
use Symfony\Component\EventDispatcher\EventDispatcher;
use Xaraya\Bridge\Events\EventObserverBridge;
use Xaraya\Bridge\Events\HookObserverBridge;

// get the event dispatcher we're going to bridge events to
$dispatcher = new EventDispatcher();
// set up the event observer bridge to dispatch a few events
$eventbridge = new EventObserverBridge($dispatcher, ['Event']);
// set up the hook observer bridge to dispatch a few hooks
$hookbridge = new HookObserverBridge($dispatcher, ['ItemUpdate']);

// have an event subscriber show interest in a few events and/or hooks - see testers.php
$subscriber = new TestObserverBridgeSubscriber(['Event'], ['ItemUpdate']);
// and add it to the event dispatcher to see something happen
$dispatcher->addSubscriber($subscriber);

// trigger an event or hook call in Xaraya
$args = ['module' => 'dynamicdata', 'itemtype' => 3, 'itemid' => 123];
xarHooks::notify('ItemUpdate', $args);

// receive the event via the event dispatcher in the event subscriber
```

### PSR-14 Event Listener Provider (WIP)

[PSR-14 Listener Provider](https://www.php-fig.org/psr/psr-14/) for ixarEventSubject and ixarHookSubject events - currently unused

Requirement: some package providing PSR-14 [psr/event-dispatcher-implementation](https://packagist.org/providers/psr/event-dispatcher-implementation)
```
$ composer require psr/event-dispatcher
```

## Routing Library (FastRoute)

Use a routing library like [nikic/FastRoute](https://github.com/nikic/FastRoute) as request mapper to Xaraya module GUI functions, data object UI methods, the REST API and GraphQL API.

Requirement: (already required for Xaraya REST API)
```
$ composer require nikic/fastroute
```

Usage:
```
// use some routing bridge
use Xaraya\Bridge\Routing\FastRouteBridge;
use xarServer;

// add route collection to your own dispatcher
// $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
//     $r->addGroup('/mysite', function (FastRoute\RouteCollector $r) {
//         FastRouteBridge::addRouteCollection($r);
//     });
// });
// $routeInfo = $dispatcher->dispatch(xarServer::getVar('REQUEST_METHOD'), xarServer::getVar('PATH_INFO') ?? '/');
// if ($routeInfo[0] == FastRoute\Dispatcher::FOUND) {
//     $handler = $routeInfo[1];
//     $vars = $routeInfo[2];
//     // ... call $handler with $vars
// }

// or get a route dispatcher to work with yourself, possibly in a group
// $dispatcher = FastRouteBridge::getSimpleDispatcher('/mysite');
// $routeInfo = $dispatcher->dispatch(xarServer::getVar('REQUEST_METHOD'), xarServer::getVar('PATH_INFO') ?? '/');

// or let the route dispatcher handle the request itself and return the result
[$result, $context] = FastRouteBridge::dispatchRequest(xarServer::getVar('REQUEST_METHOD'), xarServer::getVar('PATH_INFO') ?? '/', '/mysite');
FastRouteBridge::output($result, $context);

// or let it really do all the work here...
// FastRouteBridge::run('/mysite');
```

## HTTP Server Request (PSR-7)

Re-usable traits for both [PSR-7 Server Requests](https://www.php-fig.org/psr/psr-7/) and standard PHP [Server API (SAPI)](https://www.php.net/manual/en/function.php-sapi-name.php) using superglobals ($_SERVER, $_GET etc.): parse or build URI paths, run GUI or API requests for modules, data objects, blocks, ...

Requirement: when not using the standard Server API with superglobals, some package providing PSR-7 [psr/http-message-implementation](https://packagist.org/providers/psr/http-message-implementation) and PSR-17 [psr/http-factory-implementation](https://packagist.org/providers/psr/http-factory-implementation), for example nyholm/psr7 or guzzlehttp/psr7
```
$ composer require nyholm/psr7 nyholm/psr7-server
```

Usage:
```
use Xaraya\Bridge\Requests\CommonBridgeTrait;

class MyRequestHandler
{
    use CommonBridgeTrait;

    /**
     * This can handle both a PSR-7 server $request or (= null) standard Server API request using superglobals
     */
    function handleRequest($request = null)
    {
        $server = static::getServerParams($request);
        $query = static::getQueryParams($request);
        // ...
    }
}
```

## Middleware and Request Handler (PSR-15)

PSR-15 compatible middleware controllers to handle Module requests with xarMod::guiFunc() or DataObject requests with DataObjectUserInterface() as part of a middleware pipeline or request handler.

Requirement: some package providing PSR-7 [psr/http-message-implementation](https://packagist.org/providers/psr/http-message-implementation) and PSR-17 [psr/http-factory-implementation](https://packagist.org/providers/psr/http-factory-implementation), for example [nyholm/psr7](https://packagist.org/packages/nyholm/psr7) or [guzzlehttp/psr7](https://packagist.org/packages/guzzlehttp/psr7)
```
$ composer require nyholm/psr7 nyholm/psr7-server
```

Requirement: some framework or package capable of dispatching PSR-7 server requests to [PSR-15 Middleware](https://packagist.org/packages/psr/http-server-middleware), for example [middlewares/utils](https://packagist.org/packages/middlewares/utils) for testing purposes
```
$ composer require middlewares/utils
```

Usage:
```
// use some PSR-7 factory and PSR-15 dispatcher
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Middlewares\Utils\Dispatcher;
// use Xaraya PSR-15 compatible middleware(s)
use Xaraya\Bridge\Middleware\DefaultMiddleware;
use Xaraya\Bridge\Middleware\DataObjectMiddleware;
use Xaraya\Bridge\Middleware\ModuleMiddleware;

// get server request from somewhere
$psr17Factory = new Psr17Factory();
$requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
$request = $requestCreator->fromGlobals();

// the Xaraya PSR-15 middleware here (with option to wrap output in page)
$objects = new DataObjectMiddleware($psr17Factory, false);
$modules = new ModuleMiddleware($psr17Factory, false);

// some other middleware before or after...
$filter = function ($request, $next) {
    // @checkme strip baseUri from request path and set 'baseUri' request attribute here?
    $request = DefaultMiddleware::stripBaseUri($request);
    $response = $next->handle($request);
    return $response;
};
// page wrapper for object/module requests in response (if not specified above)
$wrapper = function ($request, $next) use ($psr17Factory) {
    $response = $next->handle($request);
    $response = DefaultMiddleware::wrapResponse($response, $psr17Factory);
    return $response;
};
// ...
$notfound = function ($request, $next) {
    $response = $next->handle($request);
    $path = $request->getUri()->getPath();
    $response->getBody()->write('Nothing to see here at ' . htmlspecialchars($path));
    return $response;
};

$stack = [
    $filter,
    //$wrapper,
    $objects,
    // Warning: we never get here if there's an object to be handled
    $modules,
    // Warning: we never get here if there's a module to be handled
    $notfound,
];

// dispatch the request
$response = Dispatcher::run($stack, $request);
// emit the respone
DefaultMiddleware::emitResponse($response);
```

## Middleware and Routing Combined

Integration of the routing library into a single PSR-15 middleware or request handler capable of handling all PSR-7 Module, DataObject, Block, REST API, GraphQL, Routes, ... server requests.

Requirement: some package providing PSR-7 [psr/http-message-implementation](https://packagist.org/providers/psr/http-message-implementation) and PSR-17 [psr/http-factory-implementation](https://packagist.org/providers/psr/http-factory-implementation), for example [nyholm/psr7](https://packagist.org/packages/nyholm/psr7) or [guzzlehttp/psr7](https://packagist.org/packages/guzzlehttp/psr7)
```
$ composer require nyholm/psr7 nyholm/psr7-server
```

Usage:
```
// use some PSR-7 factory
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
// use Xaraya PSR-15 compatible request handler + middleware
use Xaraya\Bridge\Middleware\FastRouteHandler;

// get server request from somewhere
$psr17Factory = new Psr17Factory();
$requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
$request = $requestCreator->fromGlobals();

// the Xaraya PSR-15 request handler + middleware here
$fastrouted = new FastRouteHandler($psr17Factory);

// handle the request directly, or use as middleware
$response = $fastrouted->handle($request);

// emit the respone
FastRouteHandler::emitResponse($response);
```

## Non-blocking HTTP Server (ReactPHP)

Non-blocking HTTP servers like [ReactPHP](https://reactphp.org/) [HttpServer](https://github.com/reactphp/http#httpserver), [AMPHP](https://amphp.org/) [http-server](https://github.com/amphp/http-server), [RoadRunner](https://roadrunner.dev/) etc. typically start by loading the web application once, and then work to handle each \[request -> response\] in an asynchronous non-blocking I/O loop.
This has the potential to significantly increase concurrent requests handled, but only if the overall web application and the individual request handler are well-suited for it.

Xaraya hasn't been designed with this architecture in mind (in fact it didn't exist back then), but the combined request handler above has the potential to be used here at least in some cases, e.g. where sessions and authentication are not required.
Please note that this is very much in the experimental stage, and that there are still many stateful variables and blocking calls left over in the normal request handling process.

Requirement: with ReactPHP for example
```
$ composer require react/http
$ cp html/lib/xaraya/bridge/reactphp.php developer/bin/react.php
```

Usage:
```
$ php developer/bin/react.php
Listening on http://0.0.0.0:8080
...
```

Enjoy :-)
